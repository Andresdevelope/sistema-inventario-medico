<?php

namespace App\Services;

use App\Models\Movimiento;
use App\Models\Inventario;
use App\Models\Destino;
use App\Models\Producto;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportesMovimientosService
{
    /**
     * Normaliza el nombre de destino para presentar etiquetas cortas y consistentes en reportes.
     */
    private function normalizarDestinoReporte(string $codigo = '', string $nombre = ''): string
    {
        $codigoL = strtolower(trim($codigo));
        $nombreL = strtolower(trim($nombre));
        $joined = trim($codigoL . ' ' . $nombreL);

        if (str_contains($joined, 'acinf') || str_contains($joined, 'contadur') || str_contains($joined, 'informat') || str_contains($joined, 'administr')) {
            return 'ACI';
        }
        if (str_contains($joined, 'agro')) {
            return 'AGRO';
        }
        if (str_contains($joined, 'principal')) {
            return 'AREA PRINCIPAL';
        }
        if (str_contains($joined, 'odont')) {
            return 'ODONTOLOGIA';
        }

        $base = trim($codigo !== '' ? $codigo : $nombre);
        return $base !== '' ? strtoupper($base) : 'SIN DESTINO';
    }

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

            $topDestinos = DB::table('movimientos as m')
                ->leftJoin('destinos as d', 'd.id', '=', 'm.destino_id')
                ->where('m.tipo','egreso')
                ->whereBetween('m.fecha', [$from,$to])
                ->when($destinoId, fn($q) => $q->where('m.destino_id', $destinoId))
                ->groupBy('m.destino_id', 'd.nombre', 'd.codigo')
                ->orderByDesc('total')
                ->limit(5)
                ->selectRaw(implode(', ', [
                    'm.destino_id as destino_id',
                    'COALESCE(d.codigo, "N/D") as codigo',
                    'COALESCE(d.nombre, "Sin destino") as nombre',
                    'SUM(m.cantidad) as total',
                ]))
                ->get()
                ->map(fn($r) => [
                    'destino_id' => $r->destino_id,
                    'codigo' => $r->codigo,
                    'nombre' => $r->nombre,
                    'total' => (int)$r->total,
                ]);

            $topMedicamentos = DB::table('movimientos as m')
                ->leftJoin('productos as p', 'p.id', '=', 'm.producto_id')
                ->where('m.tipo','egreso')
                ->whereBetween('m.fecha',[$from,$to])
                ->when($destinoId, fn($q) => $q->where('m.destino_id', $destinoId))
                ->groupBy('m.producto_id', 'p.nombre', 'p.codigo')
                ->orderByDesc('total')
                ->limit(5)
                ->selectRaw(implode(', ', [
                    'm.producto_id as producto_id',
                    'COALESCE(p.nombre, "N/D") as nombre',
                    'COALESCE(p.codigo, "—") as codigo',
                    'SUM(m.cantidad) as total',
                ]))
                ->get()
                ->map(fn($r) => [
                    'producto_id'=>$r->producto_id,
                    'nombre'=>$r->nombre,
                    'codigo'=>$r->codigo,
                    'total'=>(int)$r->total,
                ]);

            $stockBajo = DB::table('inventarios as i')
                ->join('productos as p', 'p.id', '=', 'i.producto_id')
                ->selectRaw('i.producto_id, p.nombre, p.stock_minimo, SUM(i.cantidad) as stock')
                ->whereNotNull('p.stock_minimo')
                ->groupBy('i.producto_id', 'p.nombre', 'p.stock_minimo')
                ->havingRaw('SUM(i.cantidad) < p.stock_minimo')
                ->get()
                ->map(fn($r)=>[
                    'producto_id'=>$r->producto_id,
                    'nombre'=>$r->nombre ?? 'N/D',
                    'stock'=>(int)$r->stock,
                    'stock_minimo'=>(int)$r->stock_minimo,
                ])->values();

            $caducidadProxima = DB::table('inventarios as i')
                ->leftJoin('productos as p', 'p.id', '=', 'i.producto_id')
                ->whereNotNull('i.fecha_vencimiento')
                ->where('i.fecha_vencimiento','<=', now()->addDays(30)->toDateString())
                ->where('i.cantidad', '>', 0)
                ->selectRaw('i.producto_id, COALESCE(p.nombre, "N/D") as nombre, i.fecha_vencimiento, SUM(i.cantidad) as cant')
                ->groupBy('i.producto_id','p.nombre','i.fecha_vencimiento')
                ->orderBy('i.fecha_vencimiento')
                ->get()
                ->map(fn($r)=>[
                    'producto_id'=>$r->producto_id,
                    'nombre'=>$r->nombre,
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

    /**
     * Reporte 10.1: Inventario Periódico (matriz por destino)
     * Calcula el saldo por producto a una fecha de corte, desglosado por destinos y Central.
     * Sin inventario por destino en tabla, se reconstruye desde el libro de movimientos:
     *   saldo_destino = distribuciones hacia destino + ajustes positivos (destino)
     *                  - consumos desde destino - ajustes negativos (destino)
     *   saldo_global = ingresos + ajustes positivos - egresos - ajustes negativos
     *   saldo_central = saldo_global - suma(saldo_destino)
     * Devuelve filas consolidadas por producto (una por producto).
     */
    public function inventarioMatrizPorDestino(string $cutoffDate, array $opts = []): array
    {
        $cutoff = \Carbon\Carbon::parse($cutoffDate)->endOfDay()->toDateString();
        // Filtros opcionales
        $tipo = strtolower($opts['tipo'] ?? ''); // 'medicamento' | 'insumo' | ''
        $categoriaId = $opts['categoria_id'] ?? null;
        $subcategoriaId = $opts['subcategoria_id'] ?? null;

        $cacheKey = implode('_', [
            'reporte_matriz',
            $cutoff,
            $tipo ?: 'all',
            $categoriaId ?: 'cat-all',
            $subcategoriaId ?: 'sub-all',
        ]);

        return Cache::remember($cacheKey, 600, function () use ($cutoff, $tipo, $categoriaId, $subcategoriaId) {
            $destinos = Destino::where('activo', true)->orderBy('nombre')->get(['id','nombre','codigo']);

            // Productos filtrados
            $productosQ = Producto::select('id','nombre','codigo','presentacion','unidad_medida','categoria_inventario','categoria_id','subcategoria_id')
                ->orderBy('nombre');
            if ($tipo === 'medicamento') {
                $productosQ->whereRaw("LOWER(COALESCE(categoria_inventario,'')) LIKE 'medicamento%'");
            } elseif ($tipo === 'insumo') {
                $productosQ->whereRaw("LOWER(COALESCE(categoria_inventario,'')) LIKE 'insum%'");
            }
            if ($categoriaId) { $productosQ->where('categoria_id', (int)$categoriaId); }
            if ($subcategoriaId) { $productosQ->where('subcategoria_id', (int)$subcategoriaId); }
            $productos = $productosQ->get();
            $productoIds = $productos->pluck('id')->all();

            if (empty($productoIds)) {
                return [
                    'cutoff' => $cutoff,
                    'columnas' => ['Depósito/Central ' . \Carbon\Carbon::parse($cutoff)->format('d/m/y'), 'Total'],
                    'destinos' => $destinos->map(fn($d)=>[
                        'id'=>$d->id,
                        'codigo'=>$d->codigo,
                        'nombre'=>$this->normalizarDestinoReporte((string)($d->codigo ?? ''), (string)($d->nombre ?? '')),
                    ])->toArray(),
                    'rows' => [],
                    'filters' => [ 'tipo'=>$tipo, 'categoria_id'=>$categoriaId, 'subcategoria_id'=>$subcategoriaId ],
                ];
            }

            // Saldos globales por producto a la fecha de corte (filtrando productos)
            $global = DB::table('movimientos')
                ->selectRaw("producto_id,
                SUM(CASE WHEN tipo IN ('ingreso','ajuste_pos') AND fecha <= ? THEN cantidad ELSE 0 END) AS entradas,
                SUM(CASE WHEN tipo IN ('egreso','ajuste_neg') AND fecha <= ? THEN cantidad ELSE 0 END) AS salidas",
                    [$cutoff, $cutoff])
                ->whereIn('producto_id', $productoIds)
                ->where('fecha', '<=', $cutoff)
                ->groupBy('producto_id')
                ->get()
                ->keyBy('producto_id');

            // Preparar mapa de saldos por destino y producto (una sola consulta agregada)
            $saldoDestino = [];
            $destinoIds = $destinos->pluck('id')->all();
            if (!empty($destinoIds)) {
                $rows = DB::table('movimientos')
                    ->selectRaw("producto_id, destino_id,
                        SUM(CASE WHEN ((tipo = 'egreso' AND modalidad = 'distribucion') OR tipo = 'ajuste_pos') THEN cantidad ELSE 0 END) AS plus,
                        SUM(CASE WHEN ((tipo = 'egreso' AND modalidad = 'consumo') OR tipo = 'ajuste_neg') THEN cantidad ELSE 0 END) AS minus")
                    ->where('fecha', '<=', $cutoff)
                    ->whereIn('producto_id', $productoIds)
                    ->whereIn('destino_id', $destinoIds)
                    ->groupBy('producto_id', 'destino_id')
                    ->get();
                foreach ($rows as $r) {
                    $saldoDestino[$r->producto_id][$r->destino_id] = (int)$r->plus - (int)$r->minus;
                }
            }

            // Construir filas por producto
            $rows = [];
            foreach ($productos as $p) {
                $g = $global->get($p->id);
                $saldoGlobal = $g ? ((int)$g->entradas - (int)$g->salidas) : 0;

                // Columnas por destino
                $colsDestino = [];
                $sumDestinos = 0;
                foreach ($destinos as $d) {
                    $sd = $saldoDestino[$p->id][$d->id] ?? 0;
                    $destinoLabel = $this->normalizarDestinoReporte((string)($d->codigo ?? ''), (string)($d->nombre ?? ''));
                    $colsDestino[$d->codigo ?: $destinoLabel] = (int)$sd;
                    $sumDestinos += (int)$sd;
                }
                // Central = Global - suma destinos
                $central = $saldoGlobal - $sumDestinos;

                // UM y presentación
                $esMedicamento = str_starts_with(strtolower($p->categoria_inventario ?? ''), 'medicamento');
                $um = $esMedicamento ? 'Blíster' : ($p->unidad_medida ?: 'Unidad');
                $presentacion = $p->presentacion ?: ($esMedicamento ? 'Blíster' : '');

                // Filtrar filas sin stock total (opcional): incluir si hay algo en cualquier columna
                $total = $sumDestinos + $central;
                if ($total <= 0) { continue; }

                $rows[] = [
                    'producto_id' => $p->id,
                    'descripcion' => $p->nombre,
                    'presentacion' => $presentacion,
                    'um' => $um,
                    'destinos' => $colsDestino,
                    'central' => (int)$central,
                    'total' => (int)$total,
                ];
            }

            // Etiquetas de columnas (mostrar fecha de corte abreviada)
            $fechaLabel = \Carbon\Carbon::parse($cutoff)->format('d/m/y');
            $columnas = [];
            foreach ($destinos as $d) {
                $destinoLabel = $this->normalizarDestinoReporte((string)($d->codigo ?? ''), (string)($d->nombre ?? ''));
                $columnas[] = ($destinoLabel ?: 'Destino') . ' ' . $fechaLabel;
            }
            $columnas[] = 'Depósito/Central ' . $fechaLabel;
            $columnas[] = 'Total';

            return [
                'cutoff' => $cutoff,
                'columnas' => $columnas,
                'destinos' => $destinos->map(fn($d)=>[
                    'id'=>$d->id,
                    'codigo'=>$d->codigo,
                    'nombre'=>$this->normalizarDestinoReporte((string)($d->codigo ?? ''), (string)($d->nombre ?? '')),
                ])->toArray(),
                'rows' => $rows,
                'filters' => [ 'tipo'=>$tipo, 'categoria_id'=>$categoriaId, 'subcategoria_id'=>$subcategoriaId ],
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
        $rows = $q->get();
        $stockMap = Inventario::selectRaw('producto_id, SUM(cantidad) as stock_final')
            ->whereIn('producto_id', $rows->pluck('producto_id')->all())
            ->groupBy('producto_id')
            ->pluck('stock_final', 'producto_id');

        return $rows->map(function($r) use ($stockMap){
            $p = $r->producto;
            return [
                'producto_id'=>$r->producto_id,
                'nombre'=>$p->nombre ?? 'N/D',
                'codigo'=>$p->codigo ?? '—',
                'entradas'=>(int)$r->entradas,
                'salidas'=>(int)$r->salidas,
                'movimientos'=>(int)$r->movimientos,
                'stock_final'=> (int)($stockMap[$r->producto_id] ?? 0),
            ];
        });
    }

    /**
     * Reporte 10.2: Salidas – Farmacia Interna (modalidad=consumo)
     * Devuelve métricas por destino: medicamentos entregados (blíster), beneficiarios,
    * F, M, EST, TRAB, PROF, COM, Total.
     */
    public function salidasFarmaciaInterna(string $from, string $to, ?int $destinoId = null): array
    {
        $q = DB::table('movimientos as m')
            ->leftJoin('productos as p', 'p.id', '=', 'm.producto_id')
            ->leftJoin('destinos as d', 'd.id', '=', 'm.destino_id')
            ->where('m.tipo', 'egreso')
            ->where('m.modalidad', 'consumo')
            ->whereBetween('m.fecha', [$from, $to])
            ->groupBy('m.destino_id','d.nombre','d.codigo')
            ->selectRaw(implode(', ', [
                'm.destino_id as destino_id',
                'COALESCE(d.nombre, "Sin destino") as destino_nombre',
                'COALESCE(d.codigo, "N/D") as destino_codigo',
                // Medicamentos entregados: sólo categoría inventario = medicamento
                "SUM(CASE WHEN LOWER(COALESCE(p.categoria_inventario,'')) LIKE 'medicamento%' THEN m.cantidad ELSE 0 END) AS meds_entregados",
                // Insumos entregados (opcional)
                "SUM(CASE WHEN LOWER(COALESCE(p.categoria_inventario,'')) LIKE 'insum%' THEN m.cantidad ELSE 0 END) AS insumos_entregados",
                'COUNT(*) as beneficiarios',
                "SUM(CASE WHEN m.sexo = 'F' THEN 1 ELSE 0 END) AS F",
                "SUM(CASE WHEN m.sexo = 'M' THEN 1 ELSE 0 END) AS M",
                "SUM(CASE WHEN m.tipo_identificacion = 'estudiante' THEN 1 ELSE 0 END) AS EST",
                "SUM(CASE WHEN m.tipo_identificacion = 'trabajador' THEN 1 ELSE 0 END) AS TRAB",
                "SUM(CASE WHEN m.tipo_identificacion = 'profesor' THEN 1 ELSE 0 END) AS PROF",
                "SUM(CASE WHEN m.tipo_identificacion = 'comunidad' THEN 1 ELSE 0 END) AS COM",
                'COUNT(*) as total'
            ]));

        if ($destinoId) { $q->where('m.destino_id', $destinoId); }

        $rows = $q->get();

        // Mapear a arreglo simple
        return $rows->map(function($r){
            $destinoNormalizado = $this->normalizarDestinoReporte(
                (string)($r->destino_codigo ?? ''),
                (string)($r->destino_nombre ?? '')
            );
            return [
                'destino_id' => $r->destino_id,
                'destino' => $destinoNormalizado,
                'meds_entregados' => (int)$r->meds_entregados,
                'insumos_entregados' => (int)$r->insumos_entregados,
                'beneficiarios' => (int)$r->beneficiarios,
                'F' => (int)$r->F,
                'M' => (int)$r->M,
                'EST' => (int)$r->EST,
                'TRAB' => (int)$r->TRAB,
                'PROF' => (int)$r->PROF,
                'COM' => (int)$r->COM,
                'total' => (int)$r->total,
            ];
        })->toArray();
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
