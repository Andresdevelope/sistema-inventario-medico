<?php

namespace App\Services;

use App\Models\Inventario;
use App\Models\Movimiento;
use App\Models\Producto;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class InventarioService
{
    /**
     * Salvaguarda operativa: si un medicamento quedó registrado con fecha de
     * vencimiento pasada y aún no tiene movimientos, se obliga a corregirlo
     * en edición de producto antes de operar.
     */
    private function validarCorreccionFechaProductoAntesDeMover(Producto $producto): void
    {
        if (empty($producto->fecha_vencimiento)) {
            return;
        }

        if (!Carbon::parse($producto->fecha_vencimiento)->lt(Carbon::today())) {
            return;
        }

        $tieneMovimientos = Movimiento::where('producto_id', $producto->id)->exists();
        if ($tieneMovimientos) {
            return;
        }

        throw new InvalidArgumentException(
            'Este medicamento fue registrado con fecha de vencimiento pasada. Corrige la fecha en Editar producto antes de registrar movimientos o consumo.'
        );
    }

    /**
     * Determina si un inventario está vencido respecto a hoy.
     */
    private function isInventarioVencido(Inventario $inv): bool
    {
        if (empty($inv->fecha_vencimiento)) {
            return false;
        }

        return Carbon::parse($inv->fecha_vencimiento)->lt(Carbon::today());
    }

    /**
     * Define si debe bloquearse el uso de lotes vencidos en un egreso según modalidad.
     * - consumo: siempre bloqueado (regla sanitaria dura).
     * - distribucion: configurable.
     */
    private function debeBloquearVencidosEnEgreso(?string $modalidad): bool
    {
        if ($modalidad === 'consumo') {
            return true;
        }

        if ($modalidad === 'distribucion') {
            return (bool) config('inventario.bloquear_vencidos_distribucion', false);
        }

        return false;
    }

    /**
     * Define si debe bloquearse el uso de lotes vencidos en ajuste negativo.
     */
    private function debeBloquearVencidosEnAjusteNeg(): bool
    {
        return (bool) config('inventario.bloquear_vencidos_ajuste_neg', false);
    }

    /**
     * Determina si un inventario es compatible con el tipo de producto.
     * - Medicamento: sólo lotes blister (o null heredado para datos antiguos).
     * - Insumo: sólo lotes unidad (o null heredado para datos antiguos).
     */
    private function isInventarioCompatibleConTipo(Inventario $inv, string $tipoProd): bool
    {
        $um = strtolower((string) ($inv->um_operativa ?? ''));
        if ($tipoProd === 'medicamento' && $um !== '' && $um !== 'blister') {
            return false;
        }
        if ($tipoProd !== 'medicamento' && $um !== '' && $um !== 'unidad') {
            return false;
        }
        return true;
    }

    /**
     * Sincroniza el campo Producto.stock con la suma de inventarios cuando existan registros de inventario.
     * Evita desajustes cuando el stock del producto ha sido editado manualmente.
     */
    private function syncProductoStock(Producto $producto): void
    {
        $inventariosCount = Inventario::where('producto_id', $producto->id)->count();
        if ($inventariosCount > 0) {
            $stockInventariosActual = Inventario::where('producto_id', $producto->id)->sum('cantidad');
            if ((int)$producto->stock !== (int)$stockInventariosActual) {
                $producto->stock = (int)$stockInventariosActual;
                $producto->save();
            }
        }
    }

    /**
     * Obtiene la lista de inventarios para consumo FEFO/FIFO.
     * FEFO: fecha de vencimiento más próxima primero; NULL al final.
     * FIFO: en empates por fecha, prioriza created_at asc.
     */
    private function getInventariosFefoFifo(Producto $producto)
    {
        return Inventario::where('producto_id', $producto->id)
            ->where('cantidad', '>', 0)
            ->orderByRaw('CASE WHEN fecha_vencimiento IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('fecha_vencimiento', 'asc')
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();
    }

    /**
     * Busca (con lock) o crea un inventario agrupado por lote + fecha_vencimiento.
     */
    private function findOrCreateInventario(Producto $producto, ?string $lote, ?string $fechaVencimiento): Inventario
    {
        $inv = Inventario::where('producto_id', $producto->id)
            ->when($lote === null, fn($q)=>$q->whereNull('lote'))
            ->when($lote !== null, fn($q)=>$q->where('lote', $lote))
            ->when($fechaVencimiento === null, fn($q)=>$q->whereNull('fecha_vencimiento'))
            ->when($fechaVencimiento !== null, fn($q)=>$q->whereDate('fecha_vencimiento', $fechaVencimiento))
            ->lockForUpdate()
            ->first();
        if (!$inv) {
            $inv = Inventario::create([
                'producto_id' => $producto->id,
                'lote' => $lote,
                'cantidad' => 0,
                'fecha_vencimiento' => $fechaVencimiento,
                // um_operativa y contenido_por_blister se fijarán al momento del primer ingreso/ajuste +
                'um_operativa' => null,
                'contenido_por_blister' => null,
                'stock_minimo' => $producto->stock_minimo,
                'estado' => 'activo',
            ]);
        }
        return $inv;
    }

    /**
     * Procesa un movimiento y actualiza inventario en una transacción.
     * $data keys: producto_id, tipo (ingreso|egreso|ajuste_pos|ajuste_neg), cantidad, fecha(optional),
     *  fecha_vencimiento(optional para ingreso/ajuste_pos), motivo, observaciones, usuario_id(optional), area(optional)
     */
    public function procesarMovimiento(array $data): void
    {
        $tipo = strtolower($data['tipo'] ?? '');
        $cantidad = (int)($data['cantidad'] ?? 0);
        if (!in_array($tipo, ['ingreso','egreso','ajuste_pos','ajuste_neg'])) {
            throw new InvalidArgumentException('Tipo de movimiento inválido');
        }
        if ($cantidad <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor que 0');
        }

        DB::transaction(function() use ($data, $tipo, $cantidad) {
            /** @var Producto $producto */
            $producto = Producto::lockForUpdate()->findOrFail($data['producto_id']);
            $this->validarCorreccionFechaProductoAntesDeMover($producto);
            $tipoProd = strtolower($producto->tipo_producto ?? 'medicamento');
            // Sincronizar stock con inventarios cuando existan
            $this->syncProductoStock($producto);
            $destinoId = $data['destino_id'] ?? null;
            $destino = null;
            if ($destinoId) {
                $destino = \App\Models\Destino::find($destinoId);
            }
            $area = $destino ? ($destino->codigo ?? $destino->nombre) : ($data['area'] ?? ($producto->categoria_inventario ?? 'general'));
            $motivo = $data['motivo'] ?? null;
            $observaciones = $data['observaciones'] ?? null;
            $usuarioId = $data['usuario_id'] ?? Auth::id();
            $fecha = !empty($data['fecha'])
                ? Carbon::parse($data['fecha'])->toDateString()
                : Carbon::today()->toDateString();

            // Modalidad del egreso: distribucion (Central -> Destino) o consumo (entrega real)
            $modalidad = $data['modalidad'] ?? null;
            $tipoIdent = $data['tipo_identificacion'] ?? null;
            $sexo = $data['sexo'] ?? null;

            if ($tipo === 'egreso') {
                // Validaciones de reglas de negocio
                if ($modalidad === 'consumo') {
                    // No se permite consumo desde CENTRAL
                    if ($destino && strtoupper($destino->codigo ?? $destino->nombre) === 'CENTRAL') {
                        throw new InvalidArgumentException('El consumo no puede originarse en CENTRAL. Seleccione un destino operativo.');
                    }
                    // Datos mínimos de beneficiario
                    if (empty($tipoIdent) || empty($sexo)) {
                        throw new InvalidArgumentException('Para consumo se requiere tipo de identificación y sexo del beneficiario.');
                    }
                    // Odontología: sólo insumos
                    if (stripos($area, 'odont') !== false) {
                        $tipoProd = strtolower($producto->tipo_producto ?? 'medicamento');
                        if ($tipoProd !== 'insumo') {
                            throw new InvalidArgumentException('Odontología sólo permite egresos de insumos.');
                        }
                    }
                }
                elseif ($modalidad === 'distribucion') {
                    // Distribución requiere destino
                    if (!$destino) {
                        throw new InvalidArgumentException('La distribución requiere seleccionar un destino.');
                    }
                    // Odontología: sólo insumos también en distribución
                    if ($destino && stripos(($destino->codigo ?? $destino->nombre), 'odont') !== false) {
                        $tipoProd = strtolower($producto->tipo_producto ?? 'medicamento');
                        if ($tipoProd !== 'insumo') {
                            throw new InvalidArgumentException('En Odontología sólo se puede distribuir insumos.');
                        }
                    }
                }
            }

            // Egresos con modalidad DISTRIBUCIÓN: NO modifican inventarios ni stock, sólo registran el envío.
            if ($tipo === 'egreso' && $modalidad === 'distribucion') {
                $bloquearVencidos = $this->debeBloquearVencidosEnEgreso($modalidad);
                // Asegurarnos de que haya saldo suficiente para no "distribuir" más de lo disponible.
                $totalInv = Inventario::where('producto_id', $producto->id)->sum('cantidad');
                if ($totalInv <= 0 && ($producto->stock ?? 0) > 0) {
                    // Auto-regularización inicial similar al egreso por consumo: trasladar stock declarado a un inventario neutro.
                    Inventario::create([
                        'producto_id' => $producto->id,
                        'lote' => null,
                        'cantidad' => (int)$producto->stock,
                        'fecha_vencimiento' => null,
                        'stock_minimo' => $producto->stock_minimo,
                        'estado' => 'activo',
                    ]);
                    $totalInv = (int)$producto->stock;
                }

                $inventarios = $this->getInventariosFefoFifo($producto);
                $saldoTotal = $inventarios->filter(function (Inventario $inv) use ($tipoProd, $bloquearVencidos) {
                    if (!$this->isInventarioCompatibleConTipo($inv, $tipoProd)) {
                        return false;
                    }
                    if ($bloquearVencidos && $this->isInventarioVencido($inv)) {
                        return false;
                    }
                    return true;
                })
                    ->sum('cantidad');

                if ($saldoTotal < $cantidad) {
                    throw new InvalidArgumentException(
                        $bloquearVencidos
                            ? 'Stock insuficiente para distribución con lotes vigentes (lotes vencidos bloqueados por política).'
                            : 'Stock insuficiente para distribución'
                    );
                }

                Movimiento::create([
                    'producto_id' => $producto->id,
                    'tipo' => 'egreso',
                    'modalidad' => $modalidad,
                    'tipo_identificacion' => $tipoIdent,
                    'sexo' => $sexo,
                    'salida' => $area,
                    'destino_id' => $destino?->id,
                    'inventario_id' => null,
                    'entrada' => null,
                    'cantidad' => $cantidad,
                    'motivo' => $motivo,
                    'fecha' => $fecha,
                    'usuario_id' => $usuarioId,
                    'observaciones' => $observaciones,
                ]);

                return; // fin de egreso por distribución (sin afectar inventario/stock)
            }

            if (in_array($tipo, ['ingreso','ajuste_pos'])) {
                // Agrupar por lote + fecha de vencimiento
                $fv = !empty($data['fecha_vencimiento']) ? Carbon::parse($data['fecha_vencimiento'])->toDateString() : null;
                $lote = $data['lote'] ?? null;
                $inv = $this->findOrCreateInventario($producto, $lote, $fv);
                // Reglas por tipo de producto: Medicamento => blíster con contenido obligatorio; Insumo => unidad
                if ($tipoProd === 'medicamento') {
                    $contenido = (int)($data['contenido_por_blister'] ?? 0);
                    if ($contenido <= 0) {
                        throw new InvalidArgumentException('Para medicamentos, el contenido por blíster es obligatorio y debe ser mayor que 0');
                    }
                    // Si el inventario ya existe y tiene um_operativa distinta o contenido distinto, bloquear mezcla
                    if (!empty($inv->um_operativa) || !empty($inv->contenido_por_blister)) {
                        if (($inv->um_operativa !== 'blister') || ((int)$inv->contenido_por_blister !== $contenido)) {
                            throw new InvalidArgumentException('El lote seleccionado tiene un contenido por blíster distinto. Cree un nuevo lote para mantener trazabilidad.');
                        }
                    } else {
                        // Fijar atributos críticos al crear/primer uso del lote
                        $inv->um_operativa = 'blister';
                        $inv->contenido_por_blister = $contenido;
                    }
                } else { // insumo
                    // Para insumo, um_operativa unidad y contenido_por_blister null
                    if (!empty($inv->um_operativa) && $inv->um_operativa !== 'unidad') {
                        throw new InvalidArgumentException('El lote seleccionado fue creado como blíster. Cree un nuevo lote o use productos de tipo insumo correctamente.');
                    }
                    $inv->um_operativa = 'unidad';
                    $inv->contenido_por_blister = null;
                }
                $inv->cantidad += $cantidad;
                $inv->save();

                // Actualizar stock agregado del producto
                $producto->stock += $cantidad;
                $producto->save();

                    Movimiento::create([
                    'producto_id' => $producto->id,
                    'tipo' => $tipo,
                        'modalidad' => $modalidad,
                        'tipo_identificacion' => $tipoIdent,
                        'sexo' => $sexo,
                    'salida' => $area,
                    'destino_id' => $destino?->id,
                    'inventario_id' => $inv->id,
                    'entrada' => $data['entrada'] ?? null,
                    'cantidad' => $cantidad,
                    'motivo' => $motivo,
                    'fecha' => $fecha,
                    'usuario_id' => $usuarioId,
                    'observaciones' => $observaciones,
                ]);
            }
            elseif ($tipo === 'egreso') {
                $bloquearVencidos = $this->debeBloquearVencidosEnEgreso($modalidad);
                // Si el usuario eligió un lote específico, consumir sólo de ese lote
                $targetId = $data['inventario_objetivo_id'] ?? null;
                if ($targetId) {
                    $inv = Inventario::where('id', (int)$targetId)
                        ->where('producto_id', $producto->id)
                        ->lockForUpdate()
                        ->first();
                    if (!$inv) {
                        throw new InvalidArgumentException('El lote seleccionado no existe para este producto');
                    }
                    if ((int)$inv->cantidad < $cantidad) {
                        throw new InvalidArgumentException('La cantidad supera el saldo del lote seleccionado');
                    }
                    // Validación de unidad operativa según tipo de producto
                    if (!$this->isInventarioCompatibleConTipo($inv, $tipoProd)) {
                        if ($tipoProd === 'medicamento') {
                            throw new InvalidArgumentException('El lote no opera en blíster. Verifique el tipo de producto y el lote.');
                        }
                        throw new InvalidArgumentException('El lote no opera en unidad. Verifique el tipo de producto y el lote.');
                    }

                    if ($bloquearVencidos && $this->isInventarioVencido($inv)) {
                        throw new InvalidArgumentException(
                            $modalidad === 'consumo'
                                ? 'No se puede registrar consumo con lotes vencidos.'
                                : 'No se puede registrar egreso con lotes vencidos (bloqueo activo por política).'
                        );
                    }

                    $inv->cantidad -= $cantidad;
                    $inv->save();

                    Movimiento::create([
                        'producto_id' => $producto->id,
                        'tipo' => 'egreso',
                        'modalidad' => $modalidad,
                        'tipo_identificacion' => $tipoIdent,
                        'sexo' => $sexo,
                        'salida' => $area,
                        'destino_id' => $destino?->id,
                        'inventario_id' => $inv->id,
                        'entrada' => null,
                        'cantidad' => $cantidad,
                        'motivo' => $motivo,
                        'fecha' => $fecha,
                        'usuario_id' => $usuarioId,
                        'observaciones' => $observaciones,
                    ]);

                    $producto->stock -= $cantidad;
                    if ($producto->stock < 0) { $producto->stock = 0; }
                    $producto->save();
                    return; // fin de egreso dirigido
                }
                // Auto-regularización: si no hay inventarios pero el producto tiene stock, crear uno inicial
                $totalInv = Inventario::where('producto_id', $producto->id)->sum('cantidad');
                if ($totalInv <= 0 && ($producto->stock ?? 0) > 0) {
                    $invInicial = Inventario::create([
                        'producto_id' => $producto->id,
                        'lote' => null,
                        'cantidad' => (int)$producto->stock, // trasladar el stock declarado al primer registro de inventario
                        'fecha_vencimiento' => null,
                        'stock_minimo' => $producto->stock_minimo,
                        'estado' => 'activo',
                    ]);
                    // Mantener productos.stock para validaciones posteriores y consistencia.
                }

                // Consumir por FEFO (fecha de vencimiento más próxima primero, null al final)
                $porConsumir = $cantidad;
                $inventarios = $this->getInventariosFefoFifo($producto);

                // Saldo total sólo considerando inventarios compatibles con la unidad operativa del producto
                $saldoTotal = $inventarios->filter(function (Inventario $inv) use ($tipoProd) {
                    if ($tipoProd === 'medicamento' && (!empty($inv->um_operativa) && $inv->um_operativa !== 'blister')) {
                        return false;
                    }
                    if ($tipoProd !== 'medicamento' && (!empty($inv->um_operativa) && $inv->um_operativa !== 'unidad')) {
                        return false;
                    }
                    if ($this->debeBloquearVencidosEnEgreso($modalidad ?? null) && $this->isInventarioVencido($inv)) {
                        return false;
                    }
                    return true;
                })->sum('cantidad');
                // Validar también contra stock agregado del producto por coherencia
                // Validar sólo contra el saldo real de inventarios (el campo productos.stock puede haber sido editado manualmente)
                if ($saldoTotal < $porConsumir) {
                    throw new InvalidArgumentException(
                        $bloquearVencidos
                            ? (($modalidad === 'consumo')
                                ? 'Stock insuficiente en lotes vigentes para consumo (lotes vencidos bloqueados).'
                                : 'Stock insuficiente en lotes vigentes para egreso (lotes vencidos bloqueados por política).')
                            : 'Stock insuficiente para egreso'
                    );
                }

                foreach ($inventarios as $inv) {
                    if ($porConsumir <= 0) break;
                    if (!$this->isInventarioCompatibleConTipo($inv, $tipoProd)) { continue; }
                    if ($bloquearVencidos && $this->isInventarioVencido($inv)) { continue; }
                    $consume = min($inv->cantidad, $porConsumir);
                    $inv->cantidad -= $consume;
                    $inv->save();

                    Movimiento::create([
                        'producto_id' => $producto->id,
                        'tipo' => 'egreso',
                        'modalidad' => $modalidad,
                        'tipo_identificacion' => $tipoIdent,
                        'sexo' => $sexo,
                        'salida' => $area,
                        'destino_id' => $destino?->id,
                        'inventario_id' => $inv->id,
                        'entrada' => null,
                        'cantidad' => $consume,
                        'motivo' => $motivo,
                        'fecha' => $fecha,
                        'usuario_id' => $usuarioId,
                        'observaciones' => $observaciones,
                    ]);

                    $porConsumir -= $consume;
                }
                // Actualizar stock agregado del producto (restar cantidad total egresada)
                $producto->stock -= $cantidad;
                if ($producto->stock < 0) { $producto->stock = 0; }
                $producto->save();
            }
            elseif ($tipo === 'ajuste_neg') {
                $bloquearVencidosAjusteNeg = $this->debeBloquearVencidosEnAjusteNeg();
                // Si el usuario eligió un lote específico, consumir sólo de ese lote
                $targetId = $data['inventario_objetivo_id'] ?? null;
                if ($targetId) {
                    $inv = Inventario::where('id', (int)$targetId)
                        ->where('producto_id', $producto->id)
                        ->lockForUpdate()
                        ->first();
                    if (!$inv) {
                        throw new InvalidArgumentException('El lote seleccionado no existe para este producto');
                    }
                    if ((int)$inv->cantidad < $cantidad) {
                        throw new InvalidArgumentException('La cantidad supera el saldo del lote seleccionado');
                    }
                    if (!$this->isInventarioCompatibleConTipo($inv, $tipoProd)) {
                        if ($tipoProd === 'medicamento') {
                            throw new InvalidArgumentException('El lote no opera en blíster. Verifique el tipo de producto y el lote.');
                        }
                        throw new InvalidArgumentException('El lote no opera en unidad. Verifique el tipo de producto y el lote.');
                    }

                    if ($bloquearVencidosAjusteNeg && $this->isInventarioVencido($inv)) {
                        throw new InvalidArgumentException('No se puede aplicar ajuste negativo sobre lotes vencidos (bloqueo activo por política).');
                    }

                    $inv->cantidad -= $cantidad;
                    $inv->save();

                    Movimiento::create([
                        'producto_id' => $producto->id,
                        'tipo' => 'ajuste_neg',
                        'modalidad' => null,
                        'tipo_identificacion' => null,
                        'sexo' => null,
                        'salida' => $area,
                        'destino_id' => $destino?->id,
                        'inventario_id' => $inv->id,
                        'entrada' => null,
                        'cantidad' => $cantidad,
                        'motivo' => $motivo ?? 'ajuste negativo',
                        'fecha' => $fecha,
                        'usuario_id' => $usuarioId,
                        'observaciones' => $observaciones,
                    ]);

                    $producto->stock -= $cantidad;
                    if ($producto->stock < 0) { $producto->stock = 0; }
                    $producto->save();
                    return; // fin de ajuste negativo dirigido
                }
                // Ajuste negativo: bajar de inventarios (FEFO) validando suficiente saldo
                $porAjustar = $cantidad;
                $inventarios = $this->getInventariosFefoFifo($producto);

                $saldoTotal = $inventarios->filter(function (Inventario $inv) use ($tipoProd, $bloquearVencidosAjusteNeg) {
                    if (!$this->isInventarioCompatibleConTipo($inv, $tipoProd)) {
                        return false;
                    }
                    if ($bloquearVencidosAjusteNeg && $this->isInventarioVencido($inv)) {
                        return false;
                    }
                    return true;
                })
                    ->sum('cantidad');
                // Validar sólo contra inventarios
                if ($saldoTotal < $porAjustar) {
                    throw new InvalidArgumentException(
                        $bloquearVencidosAjusteNeg
                            ? 'Stock insuficiente en lotes vigentes para ajuste negativo (lotes vencidos bloqueados por política).'
                            : 'Stock insuficiente para ajuste negativo'
                    );
                }

                foreach ($inventarios as $inv) {
                    if ($porAjustar <= 0) break;
                    if (!$this->isInventarioCompatibleConTipo($inv, $tipoProd)) { continue; }
                    if ($bloquearVencidosAjusteNeg && $this->isInventarioVencido($inv)) { continue; }
                    $consume = min($inv->cantidad, $porAjustar);
                    $inv->cantidad -= $consume;
                    $inv->save();

                    Movimiento::create([
                        'producto_id' => $producto->id,
                        'tipo' => 'ajuste_neg',
                        'modalidad' => null,
                        'tipo_identificacion' => null,
                        'sexo' => null,
                        'salida' => $area,
                        'destino_id' => $destino?->id,
                        'inventario_id' => $inv->id,
                        'entrada' => null,
                        'cantidad' => $consume,
                        'motivo' => $motivo ?? 'ajuste negativo',
                        'fecha' => $fecha,
                        'usuario_id' => $usuarioId,
                        'observaciones' => $observaciones,
                    ]);

                    $porAjustar -= $consume;
                }
                $producto->stock -= $cantidad;
                if ($producto->stock < 0) { $producto->stock = 0; }
                $producto->save();
            }
        });
    }
}
