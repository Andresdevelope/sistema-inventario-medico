<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Bitacora;
use App\Models\Movimiento;
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
            'fecha_vencimiento' => 'nullable|date|after:today',
            'destino_id' => 'required_if:tipo,egreso|nullable|exists:destinos,id',
        ], [
            'fecha_vencimiento.after' => 'La fecha de vencimiento debe ser posterior a hoy',
            'destino_id.required_if' => 'Debe seleccionar un destino para egresos',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $service->procesarMovimiento([
                'producto_id' => (int)$data['producto_id'],
                'tipo' => $data['tipo'],
                'cantidad' => (int)$data['cantidad'],
                'fecha' => $data['fecha'] ?? null,
                'fecha_vencimiento' => $data['tipo']==='ingreso' ? ($data['fecha_vencimiento'] ?? null) : null,
                'motivo' => $data['motivo'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'usuario_id' => Auth::id(),
                'area' => $data['area'] ?? null,
                'entrada' => $data['entrada'] ?? null,
                'destino_id' => $data['tipo']==='egreso' ? ($data['destino_id'] ?? null) : null,
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
                            'fecha_vencimiento' => $data['tipo']==='ingreso' ? ($data['fecha_vencimiento'] ?? null) : null,
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
}
