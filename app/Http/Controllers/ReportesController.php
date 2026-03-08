<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Bitacora;
use App\Models\Destino;
use App\Services\ReportesMovimientosService;
use App\Models\Movimiento;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:reportes.inventario')->only(['exportInventarioPdf']);
        $this->middleware('permission:reportes.salida')->only(['exportConsumoPdf', 'exportDetalleConsumoPdf']);
    }

    private function currentUser(): ?User
    {
        $user = Auth::user();
        return $user instanceof User ? $user : null;
    }

    public function index(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $destinoId = $request->input('destino_id');
        $periodo = $request->input('periodo'); // mensual|trimestral|semestral|anual
        $tipo = $request->input('tipo'); // ''|medicamento|insumo
        $categoriaId = $request->input('categoria_id');
        $subcategoriaId = $request->input('subcategoria_id');
        $mostrarInsumos = (bool)$request->input('mostrar_insumos');
        $modalidadReporte = $request->input('modalidad_reporte', 'inventario');
        if (!in_array($modalidadReporte, ['inventario', 'consumo'])) {
            $modalidadReporte = 'inventario';
        }

        $requiredPermission = $modalidadReporte === 'consumo' ? 'reportes.salida' : 'reportes.inventario';
        $user = $this->currentUser();
        if (!$user || !$user->hasPermission($requiredPermission)) {
            return redirect('/dashboard')->with('error', 'Acceso restringido: no tienes permisos para consultar ese tipo de reporte.');
        }
        // Si el usuario seleccionó sólo periodo, intentar construir desde/hasta
        if (!$from || !$to) {
            if ($periodo) {
                $hoy = Carbon::today();
                switch($periodo){
                    case 'mensual':
                        $from = $hoy->copy()->startOfMonth()->toDateString();
                        $to = $hoy->copy()->endOfMonth()->toDateString();
                        break;
                    case 'trimestral':
                        $from = $hoy->copy()->subMonths(2)->startOfMonth()->toDateString();
                        $to = $hoy->copy()->endOfMonth()->toDateString();
                        break;
                    case 'semestral':
                        $from = $hoy->copy()->subMonths(5)->startOfMonth()->toDateString();
                        $to = $hoy->copy()->endOfMonth()->toDateString();
                        break;
                    case 'anual':
                        $from = $hoy->copy()->subMonths(11)->startOfMonth()->toDateString();
                        $to = $hoy->copy()->endOfMonth()->toDateString();
                        break;
                }
            }
        }

        [$from, $to, $rangeError] = $this->normalizeDateRange($from, $to);
        if ($rangeError) {
            session()->flash('error', $rangeError);
        }

        $data = null; $detalle = null; $interno = null; // Se eliminó evolución mensual (gráfico)
        $inventarioMatriz = null;
        if ($from && $to && !$rangeError) {
            $service = new ReportesMovimientosService();
            $data = $service->resumen($from,$to, $destinoId ? (int)$destinoId : null);
            $detalle = $service->detalle($from,$to, $destinoId ? (int)$destinoId : null);
            $interno = $service->salidasFarmaciaInterna($from,$to, $destinoId ? (int)$destinoId : null);
            $inventarioMatriz = $service->inventarioMatrizPorDestino($to, [
                'tipo' => $tipo,
                'categoria_id' => $categoriaId ? (int)$categoriaId : null,
                'subcategoria_id' => $subcategoriaId ? (int)$subcategoriaId : null,
            ]);
            // Eliminado cálculo de evolución mensual (gráfico retirado)
            // Fallback si cache antiguo sin nueva clave
            if (is_array($data) && !array_key_exists('total_ingresos', $data)) {
                $data['total_ingresos'] = (int)Movimiento::whereIn('tipo',["ingreso","ajuste_pos"])->whereBetween('fecha',[$from,$to])->sum('cantidad');
            }
            // Bitácora generación
            try { if (Auth::check()) { Bitacora::create([
                'user_id'=>Auth::id(),
                'accion'=>'reportes.generar',
                'detalles'=>json_encode(['from'=>$from,'to'=>$to,'destino_id'=>$destinoId], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                'fecha_hora'=>now(),]); }} catch(\Throwable $e) {}
        } else {
            // Bitácora: ingreso a módulo reportes
            try { if (Auth::check()) { Bitacora::create([
                'user_id'=>Auth::id(),
                'accion'=>'reportes.index',
                'detalles'=>json_encode(['filtros'=>$request->query(),'ip'=>$request->ip()], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                'fecha_hora'=>now(),]); }} catch(\Throwable $e) {}
        }
        $destinos = \App\Models\Destino::where('activo',true)->orderBy('nombre')->get(['id','nombre','codigo']);
        $categorias = \App\Models\Categoria::orderBy('nombre')->get(['id','nombre']);
        $subcategorias = \App\Models\Subcategoria::orderBy('nombre')->get(['id','nombre','categoria_id']);
        return view('reportes.index', [
            'from'=>$from,'to'=>$to,'destino_id'=>$destinoId,
            'periodo'=>$periodo,'tipo'=>$tipo,'categoria_id'=>$categoriaId,'subcategoria_id'=>$subcategoriaId,
            'mostrar_insumos'=>$mostrarInsumos,
            'resumen'=>$data,'detalle'=>$detalle,'interno'=>$interno,'destinos'=>$destinos,
            'inventario_matriz' => $inventarioMatriz ?? null,
            'categorias'=>$categorias,'subcategorias'=>$subcategorias,
            'modalidad_reporte' => $modalidadReporte,
        ]);
    }

    public function exportInventarioPdf(Request $request)
    {
        $user = $this->currentUser();
        if (!$user || !$user->hasPermission('reportes.inventario')) {
            return redirect()->route('reportes.index')->with('error', 'No tienes permisos para exportar reportes de inventario.');
        }

        $to = $request->input('to');
        $periodo = $request->input('periodo');
        $tipo = $request->input('tipo');
        $categoriaId = $request->input('categoria_id');
        $subcategoriaId = $request->input('subcategoria_id');
        // Construir fecha de corte si viene sólo el periodo
        if (!$to && $periodo) {
            $hoy = Carbon::today();
            switch($periodo){
                case 'mensual': $to = $hoy->copy()->endOfMonth()->toDateString(); break;
                case 'trimestral': $to = $hoy->copy()->endOfMonth()->toDateString(); break;
                case 'semestral': $to = $hoy->copy()->endOfMonth()->toDateString(); break;
                case 'anual': $to = $hoy->copy()->endOfMonth()->toDateString(); break;
            }
        }
        [$to, $cutoffError] = $this->normalizeSingleDate($to, 'fecha de corte');
        if ($cutoffError) {
            return redirect()->route('reportes.index')->with('error', $cutoffError);
        }
        if (!$to) { return redirect()->route('reportes.index')->with('error','Debe indicar la fecha de corte (Hasta) o seleccionar un periodo.'); }
        $service = new ReportesMovimientosService();
        $matriz = $service->inventarioMatrizPorDestino($to, [
            'tipo' => $tipo,
            'categoria_id' => $categoriaId ? (int)$categoriaId : null,
            'subcategoria_id' => $subcategoriaId ? (int)$subcategoriaId : null,
        ]);
        $pdf = Pdf::loadView('reportes.inventario_pdf', [
            'cutoff' => $matriz['cutoff'],
            'destinos' => $matriz['destinos'],
            'rows' => $matriz['rows'],
        ])->setPaper('a4', 'landscape');
        // Bitácora export
        try { if (Auth::check()) { Bitacora::create([
            'user_id'=>Auth::id(),'accion'=>'reportes.export.pdf.inventario',
            'detalles'=>json_encode(['to'=>$to,'filters'=>$matriz['filters']], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'fecha_hora'=>now(),]); }} catch(\Throwable $e) {}
        $filename = 'inventario_matriz_'.$to.'.pdf';
        return $pdf->download($filename);
    }

    public function exportConsumoPdf(Request $request)
    {
        $user = $this->currentUser();
        if (!$user || !$user->hasPermission('reportes.salida')) {
            return redirect()->route('reportes.index')->with('error', 'No tienes permisos para exportar reportes de salidas.');
        }

        $from = $request->input('from');
        $to = $request->input('to');
        [$from, $to, $rangeError] = $this->normalizeDateRange($from, $to);
        if ($rangeError) {
            return redirect()->route('reportes.index')->with('error', $rangeError);
        }
        if (!$from || !$to) {
            return redirect()->route('reportes.index')->with('error', 'Debe seleccionar el rango de fechas para exportar.');
        }

        $destinoId = $request->input('destino_id');
        $mostrarInsumos = (bool)$request->input('mostrar_insumos');
        $service = new ReportesMovimientosService();
        $interno = $service->salidasFarmaciaInterna($from, $to, $destinoId ? (int)$destinoId : null);

        $destinoEtiqueta = 'Todos los destinos';
        if ($destinoId) {
            $destino = Destino::find((int)$destinoId);
            if ($destino) {
                $destinoEtiqueta = trim(($destino->nombre ?? 'Sin destino') . ' (' . ($destino->codigo ?? 'N/D') . ')');
            }
        }

        $pdf = Pdf::loadView('reportes.consumo_pdf', [
            'rows' => $interno,
            'from' => $from,
            'to' => $to,
            'destino' => $destinoEtiqueta,
            'mostrar_insumos' => $mostrarInsumos,
        ])->setPaper('a4', 'landscape');

        try {
            if (Auth::check()) {
                Bitacora::create([
                    'user_id' => Auth::id(),
                    'accion' => 'reportes.export.pdf.consumo',
                    'detalles' => json_encode([
                        'from' => $from,
                        'to' => $to,
                        'destino_id' => $destinoId,
                        'mostrar_insumos' => $mostrarInsumos,
                        'rows' => count($interno),
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'fecha_hora' => now(),
                ]);
            }
        } catch (\Throwable $e) {}

        $filename = 'salidas_farmacia_interna_'.$from.'_'.$to.'.pdf';
        return $pdf->download($filename);
    }

    public function exportDetalleConsumoPdf(Request $request)
    {
        $user = $this->currentUser();
        if (!$user || !$user->hasPermission('reportes.salida')) {
            return redirect()->route('reportes.index')->with('error', 'No tienes permisos para exportar el detalle de consumo.');
        }

        $from = $request->input('from');
        $to = $request->input('to');
        [$from, $to, $rangeError] = $this->normalizeDateRange($from, $to);
        if ($rangeError) {
            return redirect()->route('reportes.index')->with('error', $rangeError);
        }
        if (!$from || !$to) {
            return redirect()->route('reportes.index')->with('error', 'Debe seleccionar el rango de fechas para exportar.');
        }

        $destinoId = $request->input('destino_id');
        $service = new ReportesMovimientosService();
        $detalle = $service->detalle($from, $to, $destinoId ? (int)$destinoId : null);

        $destinoEtiqueta = 'Todos los destinos';
        if ($destinoId) {
            $destino = Destino::find((int)$destinoId);
            if ($destino) {
                $destinoEtiqueta = trim(($destino->nombre ?? 'Sin destino') . ' (' . ($destino->codigo ?? 'N/D') . ')');
            }
        }

        $pdf = Pdf::loadView('reportes.detalle_consumo_pdf', [
            'rows' => $detalle,
            'from' => $from,
            'to' => $to,
            'destino' => $destinoEtiqueta,
        ])->setPaper('a4', 'landscape');

        try {
            if (Auth::check()) {
                Bitacora::create([
                    'user_id' => Auth::id(),
                    'accion' => 'reportes.export.pdf.detalle',
                    'detalles' => json_encode([
                        'from' => $from,
                        'to' => $to,
                        'destino_id' => $destinoId,
                        'rows' => count($detalle),
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'fecha_hora' => now(),
                ]);
            }
        } catch (\Throwable $e) {}

        $filename = 'detalle_consumo_'.$from.'_'.$to.'.pdf';
        return $pdf->download($filename);
    }

    /**
     * Normaliza un rango de fechas y devuelve error amigable si es inválido.
     *
     * @return array{0:?string,1:?string,2:?string}
     */
    private function normalizeDateRange(?string $from, ?string $to): array
    {
        [$fromNorm, $fromErr] = $this->normalizeSingleDate($from, 'fecha inicial');
        [$toNorm, $toErr] = $this->normalizeSingleDate($to, 'fecha final');

        if ($fromErr) {
            return [null, $toNorm, $fromErr];
        }
        if ($toErr) {
            return [$fromNorm, null, $toErr];
        }
        if ($fromNorm && $toNorm && Carbon::parse($fromNorm)->gt(Carbon::parse($toNorm))) {
            return [$fromNorm, $toNorm, 'La fecha "Desde" no puede ser mayor que la fecha "Hasta".'];
        }

        return [$fromNorm, $toNorm, null];
    }

    /**
     * @return array{0:?string,1:?string}
     */
    private function normalizeSingleDate(?string $date, string $label): array
    {
        if (!$date) {
            return [null, null];
        }

        try {
            return [Carbon::parse($date)->toDateString(), null];
        } catch (\Throwable $e) {
            return [null, "La {$label} indicada no es válida."];
        }
    }

}
