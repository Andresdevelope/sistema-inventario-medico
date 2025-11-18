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
                // Si viene fecha de vencimiento, agrupar por esa fecha, si no, usar null
                $fv = !empty($data['fecha_vencimiento']) ? Carbon::parse($data['fecha_vencimiento'])->toDateString() : null;
                $inv = Inventario::where('producto_id', $producto->id)
                    ->when($fv === null, fn($q)=>$q->whereNull('fecha_vencimiento'))
                    ->when($fv !== null, fn($q)=>$q->whereDate('fecha_vencimiento', $fv))
                    ->first();
                if (!$inv) {
                    $inv = Inventario::create([
                        'producto_id' => $producto->id,
                        'lote' => null,
                        'cantidad' => 0,
                        'fecha_vencimiento' => $fv,
                        'stock_minimo' => $producto->stock_minimo,
                        'estado' => 'activo',
                    ]);
                }
                $inv->cantidad += $cantidad;
                $inv->save();

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
                // Auto-regularización: si no hay inventarios pero el producto tiene stock, crear uno inicial
                $totalInv = Inventario::where('producto_id', $producto->id)->sum('cantidad');
                if ($totalInv <= 0 && ($producto->stock ?? 0) > 0) {
                    $invInicial = Inventario::create([
                        'producto_id' => $producto->id,
                        'lote' => null,
                        'cantidad' => (int)$producto->stock,
                        'fecha_vencimiento' => null,
                        'stock_minimo' => $producto->stock_minimo,
                        'estado' => 'activo',
                    ]);
                    // Dejar productos.stock en 0 para evitar doble conteo
                    $producto->stock = 0;
                    $producto->save();
                }

                // Consumir por FEFO (fecha de vencimiento más próxima primero, null al final)
                $porConsumir = $cantidad;
                $inventarios = Inventario::where('producto_id', $producto->id)
                    ->where('cantidad', '>', 0)
                    ->orderByRaw('CASE WHEN fecha_vencimiento IS NULL THEN 1 ELSE 0 END ASC')
                    ->orderBy('fecha_vencimiento', 'asc')
                    ->lockForUpdate()
                    ->get();

                $saldoTotal = $inventarios->sum('cantidad');
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
            }
            elseif ($tipo === 'ajuste_neg') {
                // Ajuste negativo: bajar de inventarios (FEFO) validando suficiente saldo
                $porAjustar = $cantidad;
                $inventarios = Inventario::where('producto_id', $producto->id)
                    ->where('cantidad', '>', 0)
                    ->orderByRaw('CASE WHEN fecha_vencimiento IS NULL THEN 1 ELSE 0 END ASC')
                    ->orderBy('fecha_vencimiento', 'asc')
                    ->lockForUpdate()
                    ->get();

                $saldoTotal = $inventarios->sum('cantidad');
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
            }
        });
    }
}
