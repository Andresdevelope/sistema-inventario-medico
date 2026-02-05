<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Bitacora;
use App\Models\Destino;
use App\Services\ReportesMovimientosService;
use App\Models\Movimiento;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportesController extends Controller
{
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
        // Si el usuario seleccionó sólo periodo, intentar construir desde/hasta
        if (!$from || !$to) {
            if ($periodo) {
                $hoy = \Carbon\Carbon::today();
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
        $data = null; $detalle = null; $interno = null; // Se eliminó evolución mensual (gráfico)
        $inventarioMatriz = null;
        if ($from && $to) {
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

    public function exportCsv(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        if (!$from || !$to) { return redirect()->route('reportes.index')->with('error','Debe seleccionar rango de fechas'); }
        $destinoId = $request->input('destino_id');
        $service = new ReportesMovimientosService();
        $detalle = $service->detalle($from,$to, $destinoId ? (int)$destinoId : null);
        $filename = 'reporte_consumo_'.$from.'_'.$to.'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        $output = fopen('php://temp','w');
        fputcsv($output, ['CODIGO','MEDICAMENTO','ENTRADAS','SALIDAS','MOVIMIENTOS','STOCK_FINAL']);
        foreach ($detalle as $row) {
            fputcsv($output, [$row['codigo'],$row['nombre'],$row['entradas'],$row['salidas'],$row['movimientos'],$row['stock_final']]);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        // Bitácora export
        try { if (Auth::check()) { Bitacora::create([
            'user_id'=>Auth::id(),'accion'=>'reportes.export.csv',
            'detalles'=>json_encode(['from'=>$from,'to'=>$to,'destino_id'=>$destinoId,'rows'=>count($detalle)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'fecha_hora'=>now(),]); }} catch(\Throwable $e) {}
        return response($csv, 200, $headers);
    }

    public function exportInventarioPdf(Request $request)
    {
        $to = $request->input('to');
        $periodo = $request->input('periodo');
        $tipo = $request->input('tipo');
        $categoriaId = $request->input('categoria_id');
        $subcategoriaId = $request->input('subcategoria_id');
        // Construir fecha de corte si viene sólo el periodo
        if (!$to && $periodo) {
            $hoy = \Carbon\Carbon::today();
            switch($periodo){
                case 'mensual': $to = $hoy->copy()->endOfMonth()->toDateString(); break;
                case 'trimestral': $to = $hoy->copy()->endOfMonth()->toDateString(); break;
                case 'semestral': $to = $hoy->copy()->endOfMonth()->toDateString(); break;
                case 'anual': $to = $hoy->copy()->endOfMonth()->toDateString(); break;
            }
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
        $from = $request->input('from');
        $to = $request->input('to');
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

}
