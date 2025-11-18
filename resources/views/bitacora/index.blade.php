@extends('layouts.dashboard')

@section('content')
<div class="container mt-4">
    <h2 class="mb-3">Bitácora de movimientos</h2>

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
            <button class="btn btn-primary w-100"><i class="fa fa-search me-1"></i> Filtrar</button>
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
                <tr>
                    <td><span class="text-muted small">{{ \Carbon\Carbon::parse($b->fecha_hora)->format('Y-m-d H:i:s') }}</span></td>
                    <td>{{ optional($b->user)->name ?? 'Sistema' }}</td>
                    <td><span class="badge bg-secondary">{{ $b->accion }}</span></td>
                    <td class="small">
                        @php
                            $det = $b->detalles;
                            $parsed = null;
                            try { $parsed = $det ? json_decode($det, true, 512, JSON_THROW_ON_ERROR) : null; } catch (\Throwable $e) {}
                        @endphp
                        @if(is_array($parsed))
                            <pre class="m-0" style="white-space: pre-wrap;">{{ json_encode($parsed, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>
                        @else
                            {{ $det }}
                        @endif
                    </td>
                </tr>
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
                    // Limpiar todos los filtros y recargar a la ruta base
                    window.location.href = '{{ route('bitacora.index') }}';
                }
            });
        }
    });
    </script>
@endsection
