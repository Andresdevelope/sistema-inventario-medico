<?php

namespace App\Services;

use App\Models\Movimiento;
use App\Models\Inventario;
use App\Models\Destino;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportesMovimientosService
{
    /**
     * Resumen operativo del periodo.
     */
    public function resumen(string $from, string $to, ?int $destinoId = null): array
    {
        $cacheKey = "reporte_resumen_{$from}_{$to}_" . ($destinoId ?: 'all');
        return Cache::remember($cacheKey, 600, function() use ($from,$to,$destinoId){
            $base = Movimiento::query()
                ->where('tipo','egreso')
                ->whereBetween('fecha', [$from,$to]);
            if ($destinoId) { $base->where('destino_id',$destinoId); }

            $totalUnidades = (clone $base)->sum('cantidad'); // egresos
            $totalIngresos = Movimiento::whereIn('tipo',['ingreso','ajuste_pos'])
                ->whereBetween('fecha',[$from,$to])
                ->sum('cantidad');
            $productosDistintos = (clone $base)->distinct()->count('producto_id');

            $topDestinos = Movimiento::selectRaw('destino_id, SUM(cantidad) as total')
                ->where('tipo','egreso')
                ->whereBetween('fecha', [$from,$to])
                ->groupBy('destino_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get()
                ->map(function($r){
                    $dest = $r->destino_id ? Destino::find($r->destino_id) : null;
                    return [
                        'destino_id' => $r->destino_id,
                        'codigo' => $dest->codigo ?? 'N/D',
                        'nombre' => $dest->nombre ?? 'Sin destino',
                        'total' => (int)$r->total
                    ];
                });

            $topMedicamentos = Movimiento::selectRaw('producto_id, SUM(cantidad) as total')
                ->where('tipo','egreso')
                ->whereBetween('fecha',[$from,$to])
                ->groupBy('producto_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get()
                ->map(function($r){
                    $prod = $r->producto; return [
                        'producto_id'=>$r->producto_id,
                        'nombre'=>$prod->nombre ?? 'N/D',
                        'codigo'=>$prod->codigo ?? '—',
                        'total'=>(int)$r->total
                    ];
                });

            $stockBajo = Inventario::selectRaw('producto_id, SUM(cantidad) as stock')
                ->groupBy('producto_id')
                ->get()
                ->filter(function($r){
                    $p = $r->producto; return $p && $p->stock_minimo !== null && $r->stock < $p->stock_minimo; })
                ->map(fn($r)=>[
                    'producto_id'=>$r->producto_id,
                    'nombre'=>$r->producto->nombre ?? 'N/D',
                    'stock'=>(int)$r->stock,
                    'stock_minimo'=>$r->producto->stock_minimo
                ])->values();

            $caducidadProxima = Inventario::whereNotNull('fecha_vencimiento')
                ->where('fecha_vencimiento','<=', now()->addDays(30)->toDateString())
                ->selectRaw('producto_id, fecha_vencimiento, SUM(cantidad) as cant')
                ->groupBy('producto_id','fecha_vencimiento')
                ->orderBy('fecha_vencimiento')
                ->get()
                ->map(fn($r)=>[
                    'producto_id'=>$r->producto_id,
                    'nombre'=>$r->producto->nombre ?? 'N/D',
                    'fecha_vencimiento'=>$r->fecha_vencimiento,
                    'cantidad'=>(int)$r->cant
                ]);

            $ajustesNeg = Movimiento::where('tipo','ajuste_neg')
                ->whereBetween('fecha',[$from,$to])
                ->sum('cantidad');

            return [
                'total_unidades' => (int)$totalUnidades,
                'total_ingresos' => (int)$totalIngresos,
                'productos_distintos' => (int)$productosDistintos,
                'top_destinos' => $topDestinos,
                'top_medicamentos' => $topMedicamentos,
                'stock_bajo' => $stockBajo,
                'caducidad_proxima' => $caducidadProxima,
                'ajustes_negativos' => (int)$ajustesNeg,
            ];
        });
    }

    /** Tabla detallada de consumo por producto */
    public function detalle(string $from, string $to, ?int $destinoId = null)
    {
        $q = Movimiento::selectRaw(
            "producto_id,
             SUM(CASE WHEN tipo IN ('ingreso','ajuste_pos') THEN cantidad ELSE 0 END) AS entradas,
             SUM(CASE WHEN tipo = 'egreso' THEN cantidad ELSE 0 END) AS salidas,
             COUNT(*) AS movimientos"
        )
        ->whereBetween('fecha',[$from,$to])
        ->groupBy('producto_id')
        ->orderByDesc('salidas');
        if ($destinoId) {
            // Filtrar egresos por destino, ingresos se mantienen globales (sin destino)
            $q->where(function($w) use ($destinoId){
                $w->whereIn('tipo',['ingreso','ajuste_pos'])->orWhere(function($we) use ($destinoId){
                    $we->where('tipo','egreso')->where('destino_id',$destinoId);
                });
            });
        }
        return $q->get()->map(function($r){
            $p = $r->producto;
            return [
                'producto_id'=>$r->producto_id,
                'nombre'=>$p->nombre ?? 'N/D',
                'codigo'=>$p->codigo ?? '—',
                'entradas'=>(int)$r->entradas,
                'salidas'=>(int)$r->salidas,
                'movimientos'=>(int)$r->movimientos,
                'stock_final'=> (int)Inventario::where('producto_id',$r->producto_id)->sum('cantidad')
            ];
        });
    }

    /** Evolución mensual ingresos vs egresos (llenando meses vacíos) */
    public function evolucionMensual(string $from, string $to, ?int $destinoId = null): array
    {
        $raw = Movimiento::selectRaw("DATE_FORMAT(fecha,'%Y-%m') as ym,
            SUM(CASE WHEN tipo IN ('ingreso','ajuste_pos') THEN cantidad ELSE 0 END) AS ingresos,
            SUM(CASE WHEN tipo='egreso' THEN cantidad ELSE 0 END) AS egresos")
            ->whereBetween('fecha',[$from,$to]);
        if ($destinoId) {
            $raw->where(function($w) use ($destinoId){
                $w->whereIn('tipo',['ingreso','ajuste_pos'])->orWhere(function($we) use ($destinoId){
                    $we->where('tipo','egreso')->where('destino_id',$destinoId);
                });
            });
        }
        $rows = $raw->groupBy('ym')->orderBy('ym')->get()->keyBy('ym');
        // Generar lista meses entre from y to
        $start = \Carbon\Carbon::parse($from)->startOfMonth();
        $end = \Carbon\Carbon::parse($to)->startOfMonth();
        $out = [];
        while ($start->lte($end)) {
            $ym = $start->format('Y-m');
            $r = $rows->get($ym);
            $out[] = [
                'mes' => $ym,
                'ingresos' => $r ? (int)$r->ingresos : 0,
                'egresos' => $r ? (int)$r->egresos : 0,
            ];
            $start->addMonth();
        }
        return $out;
    }
}
