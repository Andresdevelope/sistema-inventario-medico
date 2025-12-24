<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Bitacora;
use App\Models\Movimiento;
use App\Models\Inventario;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class MovimientosController extends Controller
{
    public function index(Request $request)
    {
        $productos = Producto::orderBy('nombre')->get(['id','nombre','codigo']);
        $destinos = \App\Models\Destino::where('activo', true)->orderBy('nombre')->get(['id','nombre','codigo']);
        // Últimos movimientos (paginados)
        $ultimos = Movimiento::with(['producto:id,nombre,codigo', 'usuario:id,name', 'inventario:id,fecha_vencimiento'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate((int)$request->input('per_page', 10))
            ->appends($request->query());
        // Bitácora: ingreso a módulo movimientos
        try {
            if (Auth::check()) {
                Bitacora::create([
                    'user_id' => Auth::id(),
                    'accion' => 'movimientos.index',
                    'detalles' => json_encode([
                        'per_page' => (int)$request->input('per_page', 10)
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'fecha_hora' => now(),
                ]);
            }
        } catch (\Throwable $e) {}
        return view('movimientos.index', compact('productos','ultimos','destinos'));
    }

    public function store(Request $request, InventarioService $service)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'producto_id' => 'required|exists:productos,id',
            'tipo' => 'required|in:ingreso,egreso,ajuste_pos,ajuste_neg',
            'cantidad' => 'required|integer|min:1',
            'fecha' => 'nullable|date',
            // Para ENTRADA y AJUSTE +, la fecha de vencimiento es obligatoria y futura.
            // Para SALIDA y AJUSTE − se ignora (campo opcional y puede ir vacío).
            'fecha_vencimiento' => 'nullable|required_if:tipo,ingreso,ajuste_pos|date|after:today',
            // Para ENTRADA y AJUSTE +, debe indicarse un número de lote (nuevo o existente).
            // En otros tipos se permite que quede vacío sin validar como string.
            'lote' => 'nullable|required_if:tipo,ingreso,ajuste_pos|string|max:50',
            'destino_id' => 'required_if:tipo,egreso|nullable|exists:destinos,id',
            'inventario_objetivo_id' => 'nullable|exists:inventarios,id',
        ], [
            'fecha_vencimiento.required_if' => 'Debe ingresar la fecha de vencimiento para entradas y ajustes positivos',
            'fecha_vencimiento.after' => 'La fecha de vencimiento debe ser posterior a hoy',
            'lote.required_if' => 'Debe ingresar el número de lote o seleccionar uno de la tabla para entradas y ajustes positivos',
            'destino_id.required_if' => 'Debe seleccionar un destino para egresos',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Centralizar la lógica en el servicio para evitar duplicidad y futuros desajustes
            $service->procesarMovimiento([
                'producto_id' => (int)$data['producto_id'],
                'tipo' => $data['tipo'],
                'cantidad' => (int)$data['cantidad'],
                'fecha' => $data['fecha'] ?? null,
                'fecha_vencimiento' => in_array($data['tipo'], ['ingreso','ajuste_pos']) ? ($data['fecha_vencimiento'] ?? null) : null,
                'lote' => in_array($data['tipo'], ['ingreso','ajuste_pos']) ? ($data['lote'] ?? null) : null,
                'motivo' => $data['motivo'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'usuario_id' => Auth::id(),
                'area' => $data['area'] ?? null,
                'entrada' => $data['entrada'] ?? null,
                'destino_id' => $data['tipo']==='egreso' ? ($data['destino_id'] ?? null) : null,
                'inventario_objetivo_id' => in_array($data['tipo'], ['egreso','ajuste_neg']) ? ($data['inventario_objetivo_id'] ?? null) : null,
            ]);
            // Bitácora: movimiento creado
            try {
                if (Auth::check()) {
                    Bitacora::create([
                        'user_id' => Auth::id(),
                        'accion' => 'movimiento.crear',
                        'detalles' => json_encode([
                            'producto_id' => (int)$data['producto_id'],
                            'tipo' => $data['tipo'],
                            'cantidad' => (int)$data['cantidad'],
                            'fecha' => $data['fecha'] ?? null,
                            'fecha_vencimiento' => in_array($data['tipo'], ['ingreso','ajuste_pos']) ? ($data['fecha_vencimiento'] ?? null) : null,
                            'lote' => in_array($data['tipo'], ['ingreso','ajuste_pos']) ? ($data['lote'] ?? null) : null,
                            'motivo' => $data['motivo'] ?? null,
                            'area' => $data['area'] ?? null,
                            'destino_id' => $data['tipo']==='egreso' ? ($data['destino_id'] ?? null) : null,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'fecha_hora' => now(),
                    ]);
                }
            } catch (\Throwable $e) {}
        } catch (\Throwable $e) {
            // Bitácora: error al crear movimiento
            try {
                if (Auth::check()) {
                    Bitacora::create([
                        'user_id' => Auth::id(),
                        'accion' => 'movimiento.error',
                        'detalles' => json_encode([
                            'mensaje' => $e->getMessage(),
                            'payload' => $request->except(['_token']),
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'fecha_hora' => now(),
                    ]);
                }
            } catch (\Throwable $e2) {}
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('movimientos.index')->with('success', 'Movimiento registrado correctamente');
    }

    /**
     * Devuelve los lotes (inventarios) del producto con sus cantidades y vencimientos.
     * Útil para que el usuario decida ingresos/egresos sin afectar registros previos.
     */
    public function inventariosPorProducto(int $productoId)
    {
        $inventarios = \App\Models\Inventario::where('producto_id', $productoId)
            // Mostrar primero los inventarios con cantidad > 0; los agotados al final
            ->orderByRaw('CASE WHEN cantidad <= 0 THEN 1 ELSE 0 END ASC')
            // FEFO/FIFO para los que tienen stock
            ->orderByRaw('CASE WHEN fecha_vencimiento IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('fecha_vencimiento','asc')
            ->orderBy('created_at','asc')
            ->get(['id','lote','cantidad','fecha_vencimiento','created_at']);
        return response()->json([
            'producto_id' => $productoId,
            'inventarios' => $inventarios,
        ]);
    }
}
