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
            <a href="{{ route('movimientos.index') }}" class="btn inv-btn-primary d-flex align-items-center gap-2">
            <span class="badge bg-light text-warning fw-semibold text-uppercase small px-2 py-1">Nuevo</span>
            <i class="fa fa-bolt"></i>
            <span>Agregar Movimiento</span>
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
            <input type="text" name="search" id="search" value="{{ request('search') }}" class="form-control" maxlength="35" placeholder="Buscar por nombre...">
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
            <button type="submit" class="btn btn-sm inv-btn-primary w-100">
            <i class="fa fa-search me-1"></i> Buscar
            </button>
        </div>
        <div class="col-md-1">
            <a href="{{ route('inventario.index') }}" class="btn btn-sm inv-btn-outline w-100">
            <i class="fa fa-eraser me-1"></i> Limpiar
            </a>
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
                            <button type="button"
                                class="btn inv-btn-distr btn-sm ms-2 btn-ver-distribucion"
                                data-product-id="{{ $producto->id }}"
                                data-producto="{{ $producto->nombre }}"
                                data-endpoint="{{ route('movimientos.distribuciones', $producto->id) }}"
                                title="{{ !empty($producto->has_distribuciones) ? 'Ver distribución por destino' : 'Aún no hay distribuciones registradas' }}"
                                @if(empty($producto->has_distribuciones)) disabled aria-disabled="true" @endif>
                                DISTR
                            </button>
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
:root {
    --sys-orange: #ff7a1a;
    --sys-orange-hover: #ff9a50;
}

.inv-btn-primary {
    background-color: var(--sys-orange);
    border-color: var(--sys-orange);
    color: #ffffff;
    transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease, transform 0.1s ease;
}

.inv-btn-primary:hover,
.inv-btn-primary:focus {
    background-color: var(--sys-orange-hover);
    border-color: var(--sys-orange-hover);
    color: #ffffff;
}

.inv-btn-primary:active {
    transform: translateY(1px);
}

.inv-btn-outline {
    background-color: #ffffff;
    border-color: var(--sys-orange);
    color: var(--sys-orange);
    transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
}

.inv-btn-outline:hover,
.inv-btn-outline:focus {
    background-color: var(--sys-orange);
    border-color: var(--sys-orange);
    color: #ffffff;
}

.inv-btn-distr {
    background: linear-gradient(90deg, var(--color-orange-600, #ff7300), var(--color-orange-500, #ff8a00));
    border-color: var(--color-orange-700, #e56200);
    color: #ffffff;
    box-shadow: 0 12px 24px rgba(255, 138, 0, 0.25);
    transition: transform 0.1s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.inv-btn-distr:hover,
.inv-btn-distr:focus {
    background: linear-gradient(90deg, var(--color-orange-700, #e56200), var(--color-orange-600, #ff7300));
    border-color: var(--color-orange-800, #c74c00);
    box-shadow: 0 14px 28px rgba(229, 98, 0, 0.35);
    color: #ffffff;
}

.inv-btn-distr:active {
    transform: translateY(1px);
}

.inv-btn-distr:disabled,
.inv-btn-distr[aria-disabled="true"] {
    opacity: 0.55;
    cursor: not-allowed;
    box-shadow: none;
    background: linear-gradient(90deg, rgba(255, 138, 0, 0.65), rgba(255, 138, 0, 0.65));
    border-color: rgba(229, 98, 0, 0.4);
}

.inv-badge-code {
    background-color: #111827; /* gris muy oscuro */
    color: #f9fafb;
}

.inv-badge-stock-ok {
    background-color: var(--sys-orange);
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
    background-color: var(--sys-orange); /* naranja del sistema para estados lejanos a vencer */
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

.inv-distrib-summary {
    border: 1px dashed rgba(255, 138, 0, 0.4);
    border-radius: 14px;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, rgba(255, 243, 224, 0.9), rgba(255, 229, 204, 0.8));
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
}

.inv-distrib-summary__label {
    font-size: 0.75rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #c2410c;
    margin-bottom: 0.25rem;
}

.inv-distrib-summary__value {
    font-size: 1.4rem;
    font-weight: 600;
    color: #a34100;
}

.inv-modal-header {
    background: linear-gradient(90deg, var(--color-orange-700, #e56200), var(--color-orange-500, #ff8a00));
    color: #fff;
    border-bottom: none;
    box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.2);
}

.inv-modal-header small {
    color: rgba(255, 255, 255, 0.8);
}

.inv-spinner {
    color: var(--color-orange-600, #ff7300) !important;
}
</style>
@endpush

@push('modals')
<div class="modal fade" id="modalDistribucion" tabindex="-1" aria-labelledby="modalDistribucionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header inv-modal-header py-3">
                <div>
                    <h5 class="modal-title mb-0" id="modalDistribucionLabel">Distribución por destino</h5>
                    <small>Producto: <span id="modalDistribucionProducto">—</span></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info d-flex align-items-center gap-2 py-2 small">
                    <i class="fa fa-info-circle"></i>
                    <span>Este panel separa <b>histórico distribuido</b> y <b>pendiente por reportar en destino</b>. El valor distribuido no siempre representa stock actual en Central.</span>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Destino</th>
                                <th class="text-end">Distribuido (hist.)</th>
                                <th class="text-end">Consumido</th>
                                <th class="text-end">Pendiente destino</th>
                            </tr>
                        </thead>
                        <tbody id="modalDistribucionBody">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Selecciona un producto para ver su distribución.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="inv-distrib-summary h-100">
                            <div class="inv-distrib-summary__label">Distribuido histórico</div>
                            <div class="inv-distrib-summary__value" id="modalDistribucionTotal">—</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="inv-distrib-summary h-100">
                            <div class="inv-distrib-summary__label">Pendiente por reportar en destinos</div>
                            <div class="inv-distrib-summary__value" id="modalDistribucionSaldo">—</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="inv-distrib-summary h-100">
                            <div class="inv-distrib-summary__label">Stock real en Central</div>
                            <div class="inv-distrib-summary__value" id="modalDistribucionStock">—</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <small class="text-muted" id="modalDistribucionUpdated">—</small>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('modalDistribucion');
    if (!modalEl) return;

    const modal = new bootstrap.Modal(modalEl);
    const bodyEl = modalEl.querySelector('#modalDistribucionBody');
    const totalEl = modalEl.querySelector('#modalDistribucionTotal');
    const saldoEl = modalEl.querySelector('#modalDistribucionSaldo');
    const stockEl = modalEl.querySelector('#modalDistribucionStock');
    const productoEl = modalEl.querySelector('#modalDistribucionProducto');
    const updatedEl = modalEl.querySelector('#modalDistribucionUpdated');

    const setLoading = () => {
        bodyEl.innerHTML = `<tr><td colspan="5" class="py-4 text-center text-muted"><div class="spinner-border spinner-border-sm inv-spinner me-2" role="status"></div>Consultando distribuciones...</td></tr>`;
        totalEl.textContent = '—';
        saldoEl.textContent = '—';
        stockEl.textContent = '—';
        updatedEl.textContent = 'Sincronizando...';
    };

    const setError = (message) => {
        bodyEl.innerHTML = `<tr><td colspan="5" class="py-4 text-center text-danger">${message}</td></tr>`;
        totalEl.textContent = '—';
        saldoEl.textContent = '—';
        stockEl.textContent = '—';
        updatedEl.textContent = 'Intento fallido';
    };

    const formatNumber = (value) => new Intl.NumberFormat('es-CO').format(value ?? 0);

    document.querySelectorAll('.btn-ver-distribucion').forEach((btn) => {
        btn.addEventListener('click', () => {
            const endpoint = btn.dataset.endpoint;
            if (!endpoint) return;

            productoEl.textContent = btn.dataset.producto ?? 'Producto sin nombre';
            setLoading();
            modal.show();

            fetch(endpoint, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then((response) => {
                    if (!response.ok) throw new Error('No se pudo obtener la información.');
                    return response.json();
                })
                .then((data) => {
                    const distribuciones = data.distribuciones ?? [];
                    if (distribuciones.length === 0) {
                        bodyEl.innerHTML = `<tr><td colspan="5" class="py-4 text-center text-muted">No hay distribuciones registradas para este producto.</td></tr>`;
                    } else {
                        bodyEl.innerHTML = distribuciones.map((destino, index) => `
                            <tr>
                                <td>${index + 1}</td>
                                <td>
                                    <div class="fw-semibold">${destino.destino ?? 'Sin destino'}</div>
                                    <small class="text-muted">Último movimiento: ${destino.ultimo_movimiento ?? '—'}</small>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-dark-subtle text-dark">${formatNumber(destino.total_distribuido)}</span>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-secondary-subtle text-dark">${formatNumber(destino.total_consumido)}</span>
                                </td>
                                <td class="text-end">
                                    <span class="badge ${(Number(destino.saldo_estimado) < 0 ? 'bg-danger-subtle' : 'bg-success-subtle')} text-dark">${formatNumber(destino.saldo_estimado)}</span>
                                </td>
                            </tr>
                        `).join('');
                    }

                    totalEl.textContent = formatNumber(data.total_distribuido_historico ?? 0);
                    saldoEl.textContent = formatNumber(data.saldo_destinos_estimado ?? 0);
                    stockEl.textContent = formatNumber(data.stock_real ?? 0);
                    updatedEl.textContent = `Actualizado: ${data.actualizado ?? 'Hace un momento'}`;
                })
                .catch((error) => {
                    console.error(error);
                    setError('Ups, ocurrió un error al consultar las distribuciones.');
                });
        });
    });
});
</script>
@endpush

