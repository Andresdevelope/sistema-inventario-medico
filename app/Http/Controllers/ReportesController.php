<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Bitacora;
use App\Services\ReportesMovimientosService;
use App\Models\Movimiento;

class ReportesController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $destinoId = $request->input('destino_id');
        $data = null; $detalle = null; // Se eliminó evolución mensual (gráfico)
        if ($from && $to) {
            $service = new ReportesMovimientosService();
            $data = $service->resumen($from,$to, $destinoId ? (int)$destinoId : null);
            $detalle = $service->detalle($from,$to, $destinoId ? (int)$destinoId : null);
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
        return view('reportes.index', [
            'from'=>$from,'to'=>$to,'destino_id'=>$destinoId,
            'resumen'=>$data,'detalle'=>$detalle,'destinos'=>$destinos
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
}
