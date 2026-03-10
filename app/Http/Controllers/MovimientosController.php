<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Producto;
use App\Models\Bitacora;
use App\Models\Movimiento;
use App\Models\Inventario;
use App\Services\InventarioService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MovimientosController extends Controller
{
    private function canAccessMovimientosModule(?User $user = null): bool
    {
        $user = $user ?: $this->currentUser();
        return (bool) ($user && $user->hasAnyPermission([
            'movimientos.entrada',
            'movimientos.distribucion',
            'movimientos.consumo',
            'movimientos.ajuste_positivo',
            'movimientos.ajuste_negativo',
        ]));
    }

    private function canAccessMovimientosSupportData(?User $user = null): bool
    {
        $user = $user ?: $this->currentUser();
        if (!$user) {
            return false;
        }

        // Se permite soporte de datos si puede operar movimientos o al menos consultar inventario.
        return $this->canAccessMovimientosModule($user) || $user->hasPermission('inventario.ver');
    }

    private function currentUser(): ?User
    {
        $user = Auth::user();
        return $user instanceof User ? $user : null;
    }

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = $this->currentUser();
        $canAccess = $this->canAccessMovimientosModule($user);
        if (!$canAccess) {
            return redirect('/dashboard')->with('error', 'Acceso restringido: no tienes permisos para usar el módulo de movimientos.');
        }

        // Incluir tipo_producto para auto-clasificación en la vista (Medicamento/Insumo)
        // Limitamos el set inicial para evitar renderizar cientos de opciones; el resto se consulta vía AJAX.
        $productos = Producto::orderBy('nombre')
            ->limit(50)
            ->get(['id','nombre','codigo','tipo_producto']);
        $destinos = \App\Models\Destino::where('activo', true)->orderBy('nombre')->get(['id','nombre','codigo']);
        // Últimos movimientos (paginados)
        $ultimos = Movimiento::with(['producto:id,nombre,codigo', 'usuario:id,name', 'inventario:id,fecha_vencimiento'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate((int)$request->input('per_page', 10))
            ->appends($request->query());
        $productosFrecuentes = Movimiento::select('producto_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('producto_id')
            ->groupBy('producto_id')
            ->orderByDesc('total')
            ->with('producto:id,nombre,codigo,tipo_producto')
            ->limit(8)
            ->get()
            ->map(function ($row) {
                $producto = $row->producto;
                if (!$producto) {
                    return null;
                }
                return [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'codigo' => $producto->codigo,
                    'tipo' => $producto->tipo_producto,
                    'uso' => (int) $row->total,
                ];
            })
            ->filter()
            ->values()
            ->toArray();
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
        return view('movimientos.index', compact('productos','ultimos','destinos','productosFrecuentes'));
    }

    public function store(Request $request, InventarioService $service)
    {
        $request->merge([
            'lote' => $this->normalizarTexto($request->input('lote')),
            'motivo' => $this->normalizarTexto($request->input('motivo')),
            'observaciones' => $this->normalizarTexto($request->input('observaciones')),
        ]);

        $tipo = (string) $request->input('tipo');
        $modalidad = (string) $request->input('modalidad');
        $requiredPermission = match ($tipo) {
            'ingreso' => 'movimientos.entrada',
            'ajuste_pos' => 'movimientos.ajuste_positivo',
            'ajuste_neg' => 'movimientos.ajuste_negativo',
            'egreso' => $modalidad === 'consumo' ? 'movimientos.consumo' : 'movimientos.distribucion',
            default => null,
        };
        $user = $this->currentUser();
        if (!$requiredPermission || !$user || !$user->hasPermission($requiredPermission)) {
            return back()->with('error', 'No tienes permisos para registrar este tipo de movimiento.')->withInput();
        }

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
            'lote' => [
                'nullable',
                'required_if:tipo,ingreso,ajuste_pos',
                'string',
                'min:3',
                'max:35',
                'regex:/^[\pL\pN\s\-\.\/\#]+$/u',
                function ($attribute, $value, $fail) {
                    if (! is_string($value) || ! $this->textoPareceValido($value, true)) {
                        $fail('El número de lote ingresado no parece válido. Usa un formato real (ej. L-2025-AX13).');
                    }
                },
            ],
            'motivo' => [
                'nullable',
                'string',
                'min:5',
                'max:40',
                function ($attribute, $value, $fail) {
                    if (! is_string($value) || ! $this->textoPareceValido($value, false)) {
                        $fail('El motivo ingresado no parece válido. Evita textos aleatorios o solo números.');
                    }
                },
            ],
            'observaciones' => [
                'nullable',
                'string',
                'min:5',
                'max:60',
                function ($attribute, $value, $fail) {
                    if (! is_string($value) || ! $this->textoPareceValido($value, false)) {
                        $fail('La observación ingresada no parece válida. Evita textos aleatorios o solo números.');
                    }
                },
            ],
            'destino_id' => 'required_if:tipo,egreso|nullable|exists:destinos,id',
            // Modalidad requerida en egresos: distribucion o consumo
            'modalidad' => 'nullable|required_if:tipo,egreso|in:distribucion,consumo',
            // Datos mínimos de beneficiario cuando modalidad = consumo
            'tipo_identificacion' => 'nullable|required_if:modalidad,consumo|prohibited_unless:modalidad,consumo|in:estudiante,trabajador,profesor,comunidad',
            'sexo' => 'nullable|required_if:modalidad,consumo|prohibited_unless:modalidad,consumo|in:F,M',
            'inventario_objetivo_id' => 'nullable|exists:inventarios,id',
            // Campo adicional: contenido por blíster (se validará en servicio según tipo de producto)
            'contenido_por_blister' => 'nullable|integer|min:1',
        ], [
            'fecha_vencimiento.required_if' => 'Debe ingresar la fecha de vencimiento para entradas y ajustes positivos',
            'fecha_vencimiento.after' => 'La fecha de vencimiento debe ser posterior a hoy',
            'lote.required_if' => 'Debe ingresar el número de lote o seleccionar uno de la tabla para entradas y ajustes positivos',
            'lote.min' => 'El número de lote debe tener al menos 3 caracteres.',
            'lote.max' => 'El número de lote no puede superar 35 caracteres.',
            'lote.regex' => 'El número de lote contiene caracteres no permitidos.',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres.',
            'motivo.max' => 'El motivo no puede superar 40 caracteres.',
            'observaciones.min' => 'La observación debe tener al menos 5 caracteres.',
            'observaciones.max' => 'La observación no puede superar 60 caracteres.',
            'destino_id.required_if' => 'Debe seleccionar un destino para egresos',
            'modalidad.required_if' => 'Para egresos indique si es distribución o consumo',
            'tipo_identificacion.required_if' => 'Para consumo debe indicar el tipo de beneficiario',
            'tipo_identificacion.prohibited_unless' => 'El tipo de beneficiario solo debe enviarse cuando la modalidad es consumo.',
            'sexo.required_if' => 'Para consumo debe indicar el sexo del beneficiario',
            'sexo.prohibited_unless' => 'El sexo del beneficiario solo debe enviarse cuando la modalidad es consumo.',
            'contenido_por_blister.min' => 'El contenido por blíster debe ser mayor que 0',
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
                'modalidad' => $data['tipo']==='egreso' ? ($data['modalidad'] ?? null) : null,
                'tipo_identificacion' => $data['tipo']==='egreso' ? ($data['tipo_identificacion'] ?? null) : null,
                'sexo' => $data['tipo']==='egreso' ? ($data['sexo'] ?? null) : null,
                'contenido_por_blister' => in_array($data['tipo'], ['ingreso','ajuste_pos']) ? ($data['contenido_por_blister'] ?? null) : null,
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
                            'modalidad' => $data['tipo']==='egreso' ? ($data['modalidad'] ?? null) : null,
                            'tipo_identificacion' => $data['tipo']==='egreso' ? ($data['tipo_identificacion'] ?? null) : null,
                            'sexo' => $data['tipo']==='egreso' ? ($data['sexo'] ?? null) : null,
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
        if (! $this->canAccessMovimientosSupportData()) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso restringido: no tienes permisos para consultar lotes de movimientos.',
            ], 403);
        }

        $inventarios = \App\Models\Inventario::where('producto_id', $productoId)
            // Mostrar primero los inventarios con cantidad > 0; los agotados al final
            ->orderByRaw('CASE WHEN cantidad <= 0 THEN 1 ELSE 0 END ASC')
            // FEFO/FIFO para los que tienen stock
            ->orderByRaw('CASE WHEN fecha_vencimiento IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('fecha_vencimiento','asc')
            ->orderBy('created_at','asc')
            ->get(['id','lote','cantidad','fecha_vencimiento','um_operativa','contenido_por_blister','created_at']);
        return response()->json([
            'producto_id' => $productoId,
            'inventarios' => $inventarios,
        ]);
    }

    /**
     * Devuelve el resumen de distribuciones acumuladas por destino para un producto.
     */
    public function distribucionesPorProducto(Producto $producto)
    {
        if (! $this->canAccessMovimientosSupportData()) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso restringido: no tienes permisos para consultar distribuciones por producto.',
            ], 403);
        }

        $distribuciones = Movimiento::where('producto_id', $producto->id)
            ->whereNotNull('destino_id')
            ->selectRaw(implode(', ', [
                'destino_id',
                "SUM(CASE WHEN tipo = 'egreso' AND modalidad = 'distribucion' THEN cantidad ELSE 0 END) as total_distribuido",
                "SUM(CASE WHEN tipo = 'egreso' AND modalidad = 'consumo' THEN cantidad ELSE 0 END) as total_consumido",
                "SUM(CASE WHEN tipo = 'ajuste_pos' THEN cantidad ELSE 0 END) as total_ajuste_pos",
                "SUM(CASE WHEN tipo = 'ajuste_neg' THEN cantidad ELSE 0 END) as total_ajuste_neg",
                'MAX(fecha) as ultima_fecha',
            ]))
            ->groupBy('destino_id')
            ->with('destino:id,nombre,codigo')
            ->orderByDesc('total_distribuido')
            ->get()
            ->map(function (Movimiento $mov) {
                $distribuido = (int) $mov->total_distribuido;
                $consumido = (int) $mov->total_consumido;
                $ajustePos = (int) $mov->total_ajuste_pos;
                $ajusteNeg = (int) $mov->total_ajuste_neg;
                $saldoEstimado = $distribuido + $ajustePos - $consumido - $ajusteNeg;

                return [
                    'destino_id' => $mov->destino_id,
                    'destino' => $mov->destino->nombre ?? 'Sin destino',
                    'codigo' => $mov->destino->codigo ?? null,
                    'total_distribuido' => $distribuido,
                    'total_consumido' => $consumido,
                    'saldo_estimado' => $saldoEstimado,
                    'ultimo_movimiento' => $mov->ultima_fecha
                        ? Carbon::parse($mov->ultima_fecha)->translatedFormat('d/m/Y')
                        : null,
                ];
            })
            ->filter(fn(array $row) => $row['total_distribuido'] > 0 || $row['total_consumido'] > 0 || $row['saldo_estimado'] !== 0)
            ->values();

        $stockReal = (int) Inventario::where('producto_id', $producto->id)->sum('cantidad');
        $totalDistribuidoHistorico = (int) $distribuciones->sum('total_distribuido');
        $saldoDestinosEstimado = (int) $distribuciones->sum('saldo_estimado');

        return response()->json([
            'producto' => [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'codigo' => $producto->codigo,
            ],
            'distribuciones' => $distribuciones,
            'tiene_distribuciones' => $distribuciones->isNotEmpty(),
            'total_distribuido_historico' => $totalDistribuidoHistorico,
            'saldo_destinos_estimado' => $saldoDestinosEstimado,
            'stock_real' => $stockReal,
            'actualizado' => now()->format('d/m/Y H:i'),
        ]);
    }

    /**
     * Historial de Consumo: lista de movimientos tipo egreso con modalidad consumo,
     * con filtros por periodo, destino, producto, sexo y tipo_identificacion.
     */
    public function historialConsumo(Request $request)
    {
        $user = $this->currentUser();
        if (!$user || !$user->hasPermission('movimientos.consumo')) {
            return redirect('/dashboard')->with('error', 'Acceso restringido: no tienes permisos para ver el historial de consumo.');
        }

        $query = Movimiento::with(['producto:id,nombre,codigo', 'usuario:id,name', 'inventario:id,lote,fecha_vencimiento', 'destino:id,nombre'])
            ->where('tipo', 'egreso')
            ->where('modalidad', 'consumo');

        // Filtros
        if ($request->filled('destino_id')) {
            $query->where('destino_id', (int)$request->input('destino_id'));
        }
        if ($request->filled('producto_id')) {
            $query->where('producto_id', (int)$request->input('producto_id'));
        }
        if ($request->filled('sexo')) {
            $query->where('sexo', $request->input('sexo'));
        }
        if ($request->filled('tipo_identificacion')) {
            $query->where('tipo_identificacion', $request->input('tipo_identificacion'));
        }
        if ($request->filled('desde')) {
            $query->whereDate('fecha', '>=', $request->input('desde'));
        }
        if ($request->filled('hasta')) {
            $query->whereDate('fecha', '<=', $request->input('hasta'));
        }

        $perPage = (int)$request->input('per_page', 20);
        $consumos = $query->orderByDesc('fecha')->orderByDesc('id')->paginate($perPage)->appends($request->query());

        // Datos para filtros
        $destinos = \App\Models\Destino::where('activo', true)->orderBy('nombre')->get(['id','nombre']);
        $productos = Producto::orderBy('nombre')->get(['id','nombre','codigo']);

        // Bitácora: ingreso a historial de consumo
        try {
            if (Auth::check()) {
                Bitacora::create([
                    'user_id' => Auth::id(),
                    'accion' => 'consumo.historial',
                    'detalles' => json_encode([
                        'filtros' => $request->except(['_token'])
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'fecha_hora' => now(),
                ]);
            }
        } catch (\Throwable $e) {}

        return view('movimientos.historial_consumo', compact('consumos','destinos','productos'));
    }

    private function normalizarTexto($valor): ?string
    {
        if (! is_string($valor)) {
            return null;
        }

        $texto = trim($valor);
        if ($texto === '') {
            return null;
        }

        return preg_replace('/\s+/u', ' ', $texto) ?? $texto;
    }

    private function textoPareceValido(string $texto, bool $esLote = false): bool
    {
        $texto = trim(mb_strtolower($texto));
        if ($texto === '') {
            return false;
        }

        // Bloquear entradas puramente numéricas.
        if (preg_match('/^\d+$/u', $texto)) {
            return false;
        }

        // Bloquear repeticiones excesivas del mismo carácter.
        if (preg_match('/(.)\1{4,}/u', $texto)) {
            return false;
        }

        // Bloquear patrones típicos de "teclado" aleatorio.
        if (preg_match('/(asdf|asd|qwer|qwe|zxcv|zxc|sdfg|jkl|lkj|mnb)/iu', $texto)) {
            return false;
        }

        // Para campos descriptivos, exigir letras y presencia razonable de vocales.
        if (! $esLote) {
            if (! preg_match('/\pL/u', $texto)) {
                return false;
            }

            $soloLetras = preg_replace('/[^\pL]/u', '', $texto) ?? '';
            if (mb_strlen($soloLetras) < 4) {
                return false;
            }

            $vocales = preg_match_all('/[aeiouáéíóú]/u', $soloLetras);
            if ($vocales === false || $vocales < 2) {
                return false;
            }
        }

        return true;
    }
}
