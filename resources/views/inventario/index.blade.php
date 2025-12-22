@extends('layouts.dashboard')

@php
    /**
     * Calcula información de stock y barra para un producto de inventario.
     * Devuelve: stockMostrar, isLow, colorBarraClass, porcentajeBarra, tooltipStock, inventarios.
     */
    if (! function_exists('inv_calcular_stock_meta')) {
        function inv_calcular_stock_meta($producto) {
            $inventarios = $producto->inventarios;

            // Mínimo definido: por inventarios o por el propio producto como fallback
            $stockMin = optional($inventarios->whereNotNull('stock_minimo'))->min('stock_minimo');
            if (is_null($stockMin)) {
                $stockMin = $producto->stock_minimo;
            }

            // Fuente de stock: preferir inventarios (stock_total), si no, atributo del producto (modo transición)
            $tieneInventarios = $inventarios && $inventarios->count() > 0;
            $stockMostrar = $tieneInventarios ? $producto->stock_total : ($producto->stock ?? 0);
            $isLow = $stockMin !== null && $stockMostrar < $stockMin;

            // Referencia "máxima" para la barra: al menos 3× el mínimo o el propio stock
            if ($stockMin !== null) {
                $stockMaxRef = max($stockMin * 3, $stockMostrar, 1);
            } else {
                $stockMaxRef = max($stockMostrar, 1);
            }

            $porcentajeBarra = $stockMaxRef > 0
                ? max(0, min(100, ($stockMostrar / $stockMaxRef) * 100))
                : 0;

            // Color y tooltip según nivel
            if ($stockMin !== null && $stockMostrar <= $stockMin) {
                $colorBarraClass = 'stock-bar__fill--rojo';
                $tooltipStock = 'Stock bajo: ' . $stockMostrar . ' (mín: ' . $stockMin . ')';
            } elseif ($stockMin !== null && $stockMostrar <= $stockMin * 2) {
                $colorBarraClass = 'stock-bar__fill--amarillo';
                $tooltipStock = 'Stock en alerta: ' . $stockMostrar . ' (mín: ' . $stockMin . ')';
            } else {
                $colorBarraClass = 'stock-bar__fill--verde';
                $tooltipStock = $stockMin !== null
                    ? 'Stock saludable: ' . $stockMostrar . ' (mín: ' . $stockMin . ')'
                    : 'Stock actual: ' . $stockMostrar;
            }

            return compact('stockMostrar', 'isLow', 'colorBarraClass', 'porcentajeBarra', 'tooltipStock', 'inventarios');
        }
    }

    /**
     * Calcula el estado de vencimiento más próximo para un conjunto de inventarios.
     * Devuelve: fechaVencimiento, badgeClass, labelVence, tooltipVence.
     */
    if (! function_exists('inv_calcular_vencimiento')) {
        function inv_calcular_vencimiento($inventarios) {
            // Tomar solo lotes con saldo y fecha de vencimiento definida, ordenados por la fecha más cercana
            $vencimientoProximo = $inventarios
                ->where('cantidad', '>', 0)
                ->whereNotNull('fecha_vencimiento')
                ->sortBy('fecha_vencimiento')
                ->first();

            $fechaVencimiento = optional($vencimientoProximo)->fecha_vencimiento;
            $badgeClass = 'secondary';
            $labelVence = '-';
            $tooltipVence = null;

            if ($fechaVencimiento) {
                $hoy = \Carbon\Carbon::now()->startOfDay();
                $fv = \Carbon\Carbon::parse($fechaVencimiento)->startOfDay();
                $dias = (int) $hoy->diffInDays($fv, false);
                $diasAbs = abs($dias);

                if ($dias < 0) {
                    // Ya vencido
                    $badgeClass = 'danger';
                    $labelVence = 'Vencido';
                    $tooltipVence = 'Vencido hace ' . $diasAbs . ' día(s) (Venció: ' . \Carbon\Carbon::parse($fechaVencimiento)->format('d/m/Y') . ')';
                } elseif ($dias === 0) {
                    // Vence hoy
                    $badgeClass = 'warning';
                    $labelVence = 'Vence hoy';
                    $tooltipVence = 'Vence hoy (' . \Carbon\Carbon::parse($fechaVencimiento)->format('d/m/Y') . ')';
                } elseif ($dias <= 30) {
                    // Próximo a vencer (<= 30 días)
                    $badgeClass = 'warning';
                    $labelVence = 'Próx. (' . $dias . ' días)';
                    $tooltipVence = 'Vence en ' . $dias . ' día(s) (' . \Carbon\Carbon::parse($fechaVencimiento)->format('d/m/Y') . ')';
                } else {
                    // Aún con margen: mostrar meses/años en lugar de muchos días
                    $badgeClass = 'success';

                    // Aproximación simple: 30 días ~ 1 mes, 12 meses ~ 1 año
                    $meses = intdiv($dias, 30);
                    if ($meses < 12) {
                        $labelVence = 'En ' . $meses . ' mes' . ($meses > 1 ? 'es' : '');
                    } else {
                        $anios = intdiv($meses, 12);
                        $mesesRest = $meses % 12;
                        if ($mesesRest > 0) {
                            $labelVence = 'En ' . $anios . ' año' . ($anios > 1 ? 's' : '') . ' y ' . $mesesRest . ' mes' . ($mesesRest > 1 ? 'es' : '');
                        } else {
                            $labelVence = 'En ' . $anios . ' año' . ($anios > 1 ? 's' : '');
                        }
                    }

                    // Tooltip mantiene los días exactos
                    $tooltipVence = 'Vence en ' . $dias . ' día(s) (' . \Carbon\Carbon::parse($fechaVencimiento)->format('d/m/Y') . ')';
                }
            }

            return compact('fechaVencimiento', 'badgeClass', 'labelVence', 'tooltipVence');
        }
    }
@endphp

@section('content')
<div class="container mt-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="m-0">Inventario Consolidado</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('movimientos.index') }}" class="btn inv-btn-primary">Agregar Movimiento</a>
            <a href="{{ route('inventario.export', request()->query()) }}" class="btn inv-btn-outline">
                Exportar CSV
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Productos registrados</div>
                        <div class="h4 m-0">{{ $totalItems ?? 0 }}</div>
                    </div>
                    <i class="fa fa-boxes text-secondary fs-3"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Con stock bajo</div>
                        <div class="h4 m-0 text-danger">{{ $stockBajo ?? 0 }}</div>
                    </div>
                    <i class="fa fa-triangle-exclamation text-danger fs-3"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Por vencer (≤ 30 días)</div>
                        <div class="h4 m-0 text-warning">{{ $proximosAVencer ?? 0 }}</div>
                    </div>
                    <i class="fa fa-hourglass-half text-warning fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <!-- Formulario de búsqueda y filtros -->
    <form method="GET" class="row g-3 mb-4 align-items-end">
        <div class="col-md-3">
            <label for="search" class="form-label">Nombre</label>
            <input type="text" name="search" id="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar por nombre...">
        </div>
        <div class="col-md-3">
            <label for="categoria" class="form-label">Categoría</label>
            <select name="categoria" id="categoria" class="form-select">
                <option value="">Todas</option>
                @foreach($categorias as $cat)
                    <option value="{{ $cat->id }}" @if(request('categoria') == $cat->id) selected @endif>{{ $cat->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="categoria_inventario" class="form-label">Área (Inventario)</label>
            <select name="categoria_inventario" id="categoria_inventario" class="form-select">
                <option value="">Todas</option>
                <option value="general" @selected(request('categoria_inventario')==='general')>General</option>
                <option value="odontologia" @selected(request('categoria_inventario')==='odontologia')>Odontología</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="fecha" class="form-label">Fecha de Ingreso</label>
            <input type="date" name="fecha" id="fecha" value="{{ request('fecha') }}" class="form-control">
        </div>
        <div class="col-md-1">
            <label for="per_page" class="form-label">Ver</label>
            <select class="form-select" name="per_page" id="per_page" onchange="this.form.submit()">
                @foreach([10,25,50,100] as $pp)
                    <option value="{{ $pp }}" @selected((int)request('per_page',25) === $pp)>{{ $pp }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-sm inv-btn-primary w-100">Buscar</button>
        </div>
        <div class="col-md-1">
            <a href="{{ route('inventario.index') }}" class="btn btn-sm inv-btn-outline w-100">Limpiar</a>
        </div>
    </form>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
            <table class="table table-hover align-middle table-inventario">
                <thead class="table-light">
                    <tr>
                        <th>Producto</th>
                        <th>Código</th>
                        <th>Categoría</th>
                        <!-- Proveedor eliminado para simplificar la vista -->
                        <th>Presentación</th>
                        <th>Unidad</th>
                        <!-- Categoría Inventario eliminada -->
                        <th>Stock Total</th>
                        <th>Vencimiento más próximo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($productos as $producto)
                    @php
                        // Cálculos de stock (número, barra y tooltip) centralizados en un helper local
                        $stockMeta = inv_calcular_stock_meta($producto);
                        $inventarios = $stockMeta['inventarios'];
                        $stockMostrar = $stockMeta['stockMostrar'];
                        $isLow = $stockMeta['isLow'];
                        $colorBarraClass = $stockMeta['colorBarraClass'];
                        $porcentajeBarra = $stockMeta['porcentajeBarra'];
                        $tooltipStock = $stockMeta['tooltipStock'];

                        // Estado de vencimiento más próximo, también encapsulado en helper
                        $vencMeta = inv_calcular_vencimiento($inventarios);
                        $fechaVencimiento = $vencMeta['fechaVencimiento'];
                        $badgeClass = $vencMeta['badgeClass'];
                        $labelVence = $vencMeta['labelVence'];
                        $tooltipVence = $vencMeta['tooltipVence'];
                    @endphp
                    <tr>
                        <td>{{ $producto->nombre }}</td>
                        <td><span class="badge inv-badge-code">{{ $producto->codigo }}</span></td>
                        <td>{{ $producto->categoria->nombre ?? '-' }}</td>
                        <!-- Proveedor oculto -->
                        <td>{{ $producto->presentacion }}</td>
                        <td>{{ $producto->unidad_medida }}</td>
                        <!-- Categoría Inventario ocultada -->
                        <td>
                            <span class="badge {{ $isLow ? 'inv-badge-stock-low' : 'inv-badge-stock-ok' }}">
                                {{ $stockMostrar }}
                            </span>
                            <div class="stock-bar mt-1" title="{{ $tooltipStock ?? ('Stock actual: ' . $stockMostrar) }}">
                                <div class="stock-bar__fill {{ $colorBarraClass }}" style="width: {{ $porcentajeBarra }}%;"></div>
                            </div>
                        </td>
                        <td>
                            <span class="badge inv-badge-exp inv-badge-exp-{{ $badgeClass }}" title="{{ $fechaVencimiento ? ($tooltipVence ?? ('Vence: ' . \Carbon\Carbon::parse($fechaVencimiento)->format('d/m/Y'))) : 'Sin fecha' }}">{{ $labelVence }}</span>
                        </td>
                        <td>
                            <a href="{{ route('productos.show', ['producto' => $producto, 'from' => 'inventario']) }}" class="btn inv-btn-outline btn-sm">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">No hay productos registrados en inventario.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                <div class="small text-muted">
                    Mostrando {{ $productos->firstItem() ?? 0 }} - {{ $productos->lastItem() ?? 0 }} de {{ $productos->total() }} resultados
                </div>
                <div>
                    <nav aria-label="Paginación de inventario">
                        {!! $productos->links('pagination::bootstrap-5') !!}
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.inv-btn-primary {
    background-color: #ff9800;
    border-color: #ff9800;
    color: #ffffff;
}

.inv-btn-primary:hover,
.inv-btn-primary:focus {
    background-color: #fb8c00;
    border-color: #fb8c00;
    color: #ffffff;
}

.inv-btn-outline {
    background-color: #ffffff;
    border-color: #ff9800;
    color: #ff9800;
}

.inv-btn-outline:hover,
.inv-btn-outline:focus {
    background-color: #ff9800;
    border-color: #ff9800;
    color: #ffffff;
}

.inv-badge-code {
    background-color: #111827; /* gris muy oscuro */
    color: #f9fafb;
}

.inv-badge-stock-ok {
    background-color: #ff9800;
    color: #ffffff;
}

.inv-badge-stock-low {
    background-color: #dc2626;
    color: #ffffff;
}

/* Badges de vencimiento (colores alineados al sistema) */
.inv-badge-exp {
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.55rem;
}

.inv-badge-exp-danger {
    background-color: #e74c3c; /* mismo rojo que sp-dot-danger */
    color: #ffffff;
}

.inv-badge-exp-warning {
    background-color: var(--accent); /* usar el mismo naranja/accent del sistema */
    color: #ffffff;
}

.inv-badge-exp-success {
    background-color: #ff9800; /* naranja del sistema para estados lejanos a vencer */
    color: #ffffff;
}

.inv-badge-exp-secondary {
    background-color: #e5e7eb; /* sin fecha */
    color: #374151;
}

.stock-bar {
    width: 100%;
    height: 10px;
    background-image: repeating-linear-gradient(
        to right,
        #e5e7eb 0,
        #e5e7eb 10px,
        #ffffff 10px,
        #ffffff 12px
    );
    border-radius: 9999px;
    overflow: hidden;
    position: relative;
    box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.5);
}

.stock-bar__fill {
    height: 100%;
    transition: width 0.4s ease-out;
}

.stock-bar__fill--verde {
    background: linear-gradient(90deg, #22c55e, #15803d);
}

.stock-bar__fill--amarillo {
    background: linear-gradient(90deg, #facc15, #b45309);
}

.stock-bar__fill--rojo {
    background: linear-gradient(90deg, #fb7185, #b91c1c);
}
</style>
@endpush

