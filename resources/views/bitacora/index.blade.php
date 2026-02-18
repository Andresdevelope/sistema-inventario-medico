@extends('layouts.dashboard')

@section('content')
<div class="container mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h2 class="mb-1">Bitácora de movimientos</h2>
            <div class="text-muted">Registro detallado de acciones realizadas por los usuarios en el sistema.</div>
        </div>
         
    </div>

    <style>
        .btn-bitacora {
            background: linear-gradient(135deg, #fb923c, #f97316);
            border: none;
            color: #fff;
            font-weight: 600;
            letter-spacing: .02em;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .btn-bitacora:hover {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 0.5rem 1.2rem rgba(249, 115, 22, 0.35);
        }
        .accion-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .25rem .75rem;
            border-radius: 999px;
            font-size: .85rem;
            font-weight: 600;
            border: 1px solid transparent;
        }
        .accion-pill-warning { background: #fff7ed; border-color: #fdba74; color: #9a3412; }
        .accion-pill-info { background: #eef2ff; border-color: #c7d2fe; color: #3730a3; }
        .accion-pill-primary { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
        .accion-pill-dark { background: #e5e7eb; border-color: #d1d5db; color: #111827; }
        .accion-pill-success { background: #ecfdf5; border-color: #6ee7b7; color: #047857; }
        .accion-pill-teal { background: #f0fdfa; border-color: #5eead4; color: #0f766e; }
        .accion-pill-purple { background: #f5f3ff; border-color: #ddd6fe; color: #6d28d9; }
        .accion-pill-secondary { background: #f3f4f6; border-color: #e5e7eb; color: #374151; }
        .accion-chip {
            display: inline-flex;
            align-items: center;
            padding: .1rem .65rem;
            font-size: .75rem;
            border-radius: 999px;
            background: rgba(15, 118, 110, .08);
            color: #0f766e;
            border: 1px solid rgba(14, 116, 144, .2);
            text-transform: capitalize;
        }
        .detail-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            padding: 1rem 1.25rem;
            margin-top: .75rem;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }
        .detail-section {
            background: #f9fafb;
            border-radius: .85rem;
            padding: .9rem 1rem;
            border: 1px dashed #e0e7ff;
        }
        .detail-section-title {
            font-size: .85rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: .35rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .detail-section-list {
            margin: 0;
        }
        .detail-section-list dt {
            font-size: .75rem;
            text-transform: uppercase;
            color: #6b7280;
            margin-top: .5rem;
            margin-bottom: .1rem;
        }
        .detail-section-list dd {
            margin-left: 0;
            font-weight: 600;
            color: #111827;
        }
        .detail-row td {
            background: #f9fafb;
        }
        .table-hover tbody tr:hover > td {
            background-color: #fffbeb;
        }
    </style>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <label class="form-label">Usuario</label>
            <select name="user" class="form-select">
                <option value="">Todos</option>
                @foreach($usuarios as $u)
                    <option value="{{ $u->id }}" {{ request('user')==$u->id?'selected':'' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Acción contiene</label>
            <input name="accion" id="filtro-accion" class="form-control" value="{{ request('accion') }}" placeholder="crear, actualizar, eliminar...">
        </div>
        <div class="col-md-2">
            <label class="form-label">Desde</label>
            <input type="datetime-local" name="desde" class="form-control" value="{{ request('desde') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Hasta</label>
            <input type="datetime-local" name="hasta" class="form-control" value="{{ request('hasta') }}">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-bitacora w-100"><i class="fa fa-search me-1"></i> Filtrar</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 110px;">Fecha/Hora</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Detalles</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bitacora as $b)
                @php
                    $detailId = 'detalle-'.$b->id;
                    $ui = $b->ui ?? [];
                    $hasSections = !empty($ui['sections']);
                    $rawFallback = !$hasSections ? trim((string)($b->detalles ?? '')) : '';
                @endphp
                <tr>
                    <td><span class="text-muted small">{{ \Carbon\Carbon::parse($b->fecha_hora)->format('Y-m-d H:i:s') }}</span></td>
                    <td>{{ optional($b->user)->name ?? 'Sistema' }}</td>
                    <td>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="accion-pill accion-pill-{{ $ui['badge_class'] ?? 'secondary' }}">
                                <i class="fa {{ $ui['badge_icon'] ?? 'fa-clipboard-list' }}"></i>
                                {{ $ui['badge_text'] ?? 'Acción' }}
                            </span>
                            @if(!empty($ui['action_context']))
                                <span class="accion-chip">{{ $ui['action_context'] }}</span>
                            @endif
                            @foreach($ui['chips'] ?? [] as $chip)
                                <span class="accion-chip">{{ $chip }}</span>
                            @endforeach
                        </div>
                        @if(!empty($ui['action_description']))
                            <div class="text-muted small mt-1">{{ $ui['action_description'] }}</div>
                        @endif
                    </td>
                    <td class="small">
                        <div class="fw-semibold text-body">{{ $ui['summary'] ?? ($b->detalles ?? 'Sin detalles registrados') }}</div>
                        @if(!empty($ui['description']))
                            <div class="text-muted">{{ $ui['description'] }}</div>
                        @endif

        @if($hasSections)
                        <button type="button" class="btn btn-link btn-sm px-0 mt-2" data-detail-toggle="true" data-detail-target="{{ $detailId }}" data-detail-open-text="Ocultar detalle" data-detail-close-text="Ver detalle" aria-expanded="false" aria-controls="{{ $detailId }}">
                            Ver detalle
                        </button>
        @elseif($rawFallback)
                        <div class="detail-card mt-2 small text-muted">{{ $rawFallback }}</div>
        @endif
                    </td>
                </tr>
        @if($hasSections)
                <tr class="detail-row">
                    <td colspan="4" class="p-0 border-top-0">
                        <div class="detail-card detail-panel" id="{{ $detailId }}" data-detail-panel hidden>
                            <div class="detail-grid">
                                @foreach($ui['sections'] as $section)
                                    <div class="detail-section">
                                        <div class="detail-section-title">{{ $section['title'] }}</div>
                                        <dl class="detail-section-list">
                                            @foreach($section['items'] as $item)
                                                <dt>{{ $item['label'] }}</dt>
                                                <dd>{{ $item['value'] }}</dd>
                                            @endforeach
                                        </dl>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </td>
                </tr>
        @endif
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted">Sin registros</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

        <!-- Paginador moderno -->
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
            <div class="text-muted small mb-2 mb-md-0">
                @php
                    $from = $bitacora->firstItem();
                    $to = $bitacora->lastItem();
                    $total = $bitacora->total();
                @endphp
                Mostrando <b>{{ $from }}</b> - <b>{{ $to }}</b> de <b>{{ $total }}</b> registros
            </div>
            <nav aria-label="Paginador bitácora">
                <ul class="pagination pagination-lg mb-0">
                    <!-- Primera página -->
                    <li class="page-item {{ $bitacora->onFirstPage() ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $bitacora->url(1) }}" aria-label="Primera">
                            <i class="fa fa-angle-double-left"></i>
                        </a>
                    </li>
                    <!-- Página anterior -->
                    <li class="page-item {{ $bitacora->onFirstPage() ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $bitacora->previousPageUrl() }}" aria-label="Anterior">
                            <i class="fa fa-angle-left"></i>
                        </a>
                    </li>
                    <!-- Páginas -->
                    @foreach ($bitacora->getUrlRange(max(1, $bitacora->currentPage()-2), min($bitacora->lastPage(), $bitacora->currentPage()+2)) as $page => $url)
                        <li class="page-item {{ $page == $bitacora->currentPage() ? 'active' : '' }}">
                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                        </li>
                    @endforeach
                    <!-- Página siguiente -->
                    <li class="page-item {{ $bitacora->hasMorePages() ? '' : 'disabled' }}">
                        <a class="page-link" href="{{ $bitacora->nextPageUrl() }}" aria-label="Siguiente">
                            <i class="fa fa-angle-right"></i>
                        </a>
                    </li>
                    <!-- Última página -->
                    <li class="page-item {{ $bitacora->hasMorePages() ? '' : 'disabled' }}">
                        <a class="page-link" href="{{ $bitacora->url($bitacora->lastPage()) }}" aria-label="Última">
                            <i class="fa fa-angle-double-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
</div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var accionInput = document.getElementById('filtro-accion');
        if (accionInput) {
            accionInput.addEventListener('input', function() {
                if (this.value === '') {
                    window.location.href = '{{ route('bitacora.index') }}';
                }
            });
        }

        document.querySelectorAll('[data-detail-toggle]').forEach(function(button) {
            var targetId = button.getAttribute('data-detail-target');
            var target = document.getElementById(targetId);
            if (!target) return;
            var openText = button.getAttribute('data-detail-open-text') || 'Ocultar detalle';
            var closeText = button.getAttribute('data-detail-close-text') || 'Ver detalle';

            button.addEventListener('click', function() {
                var isOpen = target.hasAttribute('data-open');
                if (isOpen) {
                    target.removeAttribute('data-open');
                    target.hidden = true;
                    button.setAttribute('aria-expanded', 'false');
                    button.textContent = closeText;
                } else {
                    target.setAttribute('data-open', 'true');
                    target.hidden = false;
                    button.setAttribute('aria-expanded', 'true');
                    button.textContent = openText;
                }
            });
        });
    });
    </script>
@endsection
