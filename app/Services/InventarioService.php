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

            if (in_array($tipo, ['ingreso','ajuste_pos'])) {
                // Agrupar por lote + fecha de vencimiento
                $fv = !empty($data['fecha_vencimiento']) ? Carbon::parse($data['fecha_vencimiento'])->toDateString() : null;
                $lote = $data['lote'] ?? null;
                $inv = $this->findOrCreateInventario($producto, $lote, $fv);
                $inv->cantidad += $cantidad;
                $inv->save();

                // Actualizar stock agregado del producto
                $producto->stock += $cantidad;
                $producto->save();

                Movimiento::create([
                    'producto_id' => $producto->id,
                    'tipo' => $tipo,
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

                    $inv->cantidad -= $cantidad;
                    $inv->save();

                    Movimiento::create([
                        'producto_id' => $producto->id,
                        'tipo' => 'egreso',
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

                $saldoTotal = $inventarios->sum('cantidad');
                // Validar también contra stock agregado del producto por coherencia
                // Validar sólo contra el saldo real de inventarios (el campo productos.stock puede haber sido editado manualmente)
                if ($saldoTotal < $porConsumir) {
                    throw new InvalidArgumentException('Stock insuficiente para egreso');
                }

                foreach ($inventarios as $inv) {
                    if ($porConsumir <= 0) break;
                    $consume = min($inv->cantidad, $porConsumir);
                    $inv->cantidad -= $consume;
                    $inv->save();

                    Movimiento::create([
                        'producto_id' => $producto->id,
                        'tipo' => 'egreso',
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

                    $inv->cantidad -= $cantidad;
                    $inv->save();

                    Movimiento::create([
                        'producto_id' => $producto->id,
                        'tipo' => 'ajuste_neg',
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

                $saldoTotal = $inventarios->sum('cantidad');
                // Validar sólo contra inventarios
                if ($saldoTotal < $porAjustar) {
                    throw new InvalidArgumentException('Stock insuficiente para ajuste negativo');
                }

                foreach ($inventarios as $inv) {
                    if ($porAjustar <= 0) break;
                    $consume = min($inv->cantidad, $porAjustar);
                    $inv->cantidad -= $consume;
                    $inv->save();

                    Movimiento::create([
                        'producto_id' => $producto->id,
                        'tipo' => 'ajuste_neg',
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
