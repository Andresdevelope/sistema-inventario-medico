@extends('layouts.dashboard')

@section('title', 'Medicamentos')

@section('content')
<div class="container-fluid px-2 px-md-4 mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3 gap-2">
        <h3 class="mb-0"><i class="fas fa-capsules me-2"></i>Medicamentos</h3>
        <a href="{{ route('productos.create') }}" class="btn btn-success shadow-sm"><i class="fas fa-plus"></i> Nuevo Medicamento</a>
    </div>

    <!-- Opción A: Barra compacta inline (limpia) -->
    <form id="compactSearchForm" method="GET" class="row g-2 mb-3 align-items-center" role="search" aria-label="Buscar medicamentos">
        <div class="col-auto">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search" aria-hidden="true"></i></span>
                <input type="text" name="search" id="searchInput" value="{{ request('search') }}" class="form-control" placeholder="Buscar por nombre, código o presentación" aria-label="Buscar medicamentos">
            </div>
        </div>
        <div class="col-auto">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-tags" aria-hidden="true"></i></span>
                <select name="categoria" class="form-select">
                    <option value="">Todas las categorías</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}" @if(request('categoria') == $cat->id) selected @endif>{{ $cat->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-auto">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-list-ol" aria-hidden="true"></i></span>
                <select name="per_page" class="form-select">
                    @foreach([10,25,50,100] as $n)
                        <option value="{{ $n }}" @if(request('per_page',25) == $n) selected @endif>{{ $n }} / pág</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    <!-- Toast container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100">
        <div id="flashToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="flashToastMsg"><!-- mensaje dinámico --></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Tooltips
        [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).forEach(el=> new bootstrap.Tooltip(el));

        // Toasts from session
        const toastEl = document.getElementById('flashToast');
        const toastMsg = document.getElementById('flashToastMsg');
        if (toastEl && toastMsg) {
            @if(session('success'))
                toastMsg.textContent = {!! json_encode(session('success')) !!};
                toastEl.classList.remove('bg-danger'); toastEl.classList.add('bg-success');
                new bootstrap.Toast(toastEl).show();
            @elseif(session('error'))
                toastMsg.textContent = {!! json_encode(session('error')) !!};
                toastEl.classList.remove('bg-success'); toastEl.classList.add('bg-danger');
                new bootstrap.Toast(toastEl).show();
            @endif
        }

        // Debounce helper
        function debounce(fn, wait){ let t; return function(...a){ clearTimeout(t); t = setTimeout(()=> fn.apply(this,a), wait); }; }

        // Compact search handlers
        try {
            const form = document.getElementById('compactSearchForm');
            const input = form.querySelector('input[name="search"]');
            const categoria = form.querySelector('select[name="categoria"]');
            const perPage = form.querySelector('select[name="per_page"]');

            if (input) {
                input.addEventListener('input', debounce(function(){ form.submit(); }, 450));
                input.addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); form.submit(); } });
            }

            if (categoria) categoria.addEventListener('change', function(){ form.submit(); });
            if (perPage) perPage.addEventListener('change', function(){ form.submit(); });
        } catch (e) { console.error('Compact search error', e); }
    });
    </script>
    @endpush
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0 text-nowrap">
                    <thead class="table-primary text-center align-middle">
                        <tr>
                            @php
                                $baseQuery = request()->except('page');
                                $currentSort = request('sort', 'nombre');
                                $currentDir = request('dir', 'asc');
                                $toggleDir = function($col) use ($currentSort, $currentDir) {
                                    if ($currentSort === $col) return $currentDir === 'asc' ? 'desc' : 'asc';
                                    return 'asc';
                                };
                            @endphp
                            <th scope="col">Código</th>
                            <th scope="col">
                                @php $dirNombre = $toggleDir('nombre'); @endphp
                                <a href="?{{ http_build_query(array_merge($baseQuery, ['sort'=>'nombre','dir'=>$dirNombre])) }}" aria-label="Ordenar por nombre">Nombre
                                    @if($currentSort === 'nombre')
                                        <i class="fas fa-sort-{{ $currentDir === 'asc' ? 'up' : 'down' }} ms-1" aria-hidden="true"></i>
                                    @else
                                        <i class="fas fa-sort ms-1 text-muted" aria-hidden="true"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col">
                                @php $dirCat = $toggleDir('categoria_id'); @endphp
                                <a href="?{{ http_build_query(array_merge($baseQuery, ['sort'=>'categoria_id','dir'=>$dirCat])) }}" aria-label="Ordenar por categoría">Categoría
                                    @if($currentSort === 'categoria_id')
                                        <i class="fas fa-sort-{{ $currentDir === 'asc' ? 'up' : 'down' }} ms-1" aria-hidden="true"></i>
                                    @else
                                        <i class="fas fa-sort ms-1 text-muted" aria-hidden="true"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col">Subcat.</th>
                            <th scope="col">
                                @php $dirPres = $toggleDir('presentacion'); @endphp
                                <a href="?{{ http_build_query(array_merge($baseQuery, ['sort'=>'presentacion','dir'=>$dirPres])) }}" aria-label="Ordenar por presentación">Presentación
                                    @if($currentSort === 'presentacion')
                                        <i class="fas fa-sort-{{ $currentDir === 'asc' ? 'up' : 'down' }} ms-1" aria-hidden="true"></i>
                                    @else
                                        <i class="fas fa-sort ms-1 text-muted" aria-hidden="true"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col">
                                @php $dirStock = $toggleDir('stock'); @endphp
                                <a href="?{{ http_build_query(array_merge($baseQuery, ['sort'=>'stock','dir'=>$dirStock])) }}" aria-label="Ordenar por stock">Stock
                                    @if($currentSort === 'stock')
                                        <i class="fas fa-sort-{{ $currentDir === 'asc' ? 'up' : 'down' }} ms-1" aria-hidden="true"></i>
                                    @else
                                        <i class="fas fa-sort ms-1 text-muted" aria-hidden="true"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col">Proveedor</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productos as $producto)
                        <tr>
                            <td class="fw-bold text-primary small">{{ $producto->codigo }}</td>
                            <td class="small">
                                <span class="fw-semibold">{{ $producto->nombre }}</span>
                                @if($producto->fecha_vencimiento && \Carbon\Carbon::parse($producto->fecha_vencimiento)->isPast())
                                    <span class="badge bg-danger ms-1" title="Vencido"><i class="fas fa-exclamation-triangle"></i></span>
                                @elseif($producto->fecha_vencimiento && \Carbon\Carbon::parse($producto->fecha_vencimiento)->diffInDays(now()) <= 30)
                                    <span class="badge bg-warning text-dark ms-1" title="Próximo a vencer"><i class="fas fa-hourglass-half"></i></span>
                                @endif
                            </td>
                            <td><span class="badge bg-info text-dark small">{{ $producto->categoria->nombre ?? '-' }}</span></td>
                            <td><span class="badge bg-light text-dark border small">{{ $producto->subcategoria->nombre ?? '-' }}</span></td>
                            <td><span class="text-secondary small">{{ $producto->presentacion }}</span></td>
                            <td class="small">
                                @if($producto->stock == 0)
                                    <span class="badge bg-danger">Agotado</span>
                                @elseif($producto->stock <= 10)
                                    <span class="badge bg-warning text-dark">Bajo ({{ $producto->stock }})</span>
                                @else
                                    <span class="badge bg-success">{{ $producto->stock }}</span>
                                @endif
                            </td>
                            <td><span class="badge bg-secondary small">{{ $producto->proveedor->nombre ?? '-' }}</span></td>
                            <td class="text-center">
                                <div class="d-flex flex-nowrap justify-content-center gap-1">
                                    <a href="{{ route('productos.show', $producto) }}" class="btn btn-outline-info btn-sm px-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Ver Detalle"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('productos.edit', $producto) }}" class="btn btn-outline-warning btn-sm px-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Editar"><i class="fas fa-edit"></i></a>
                                    <form action="{{ route('productos.destroy', $producto) }}" method="POST" class="d-inline-block" onsubmit="return confirm('¿Seguro que deseas eliminar este medicamento?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm px-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Eliminar"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">No hay medicamentos registrados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="small text-muted">Mostrando {{ $productos->firstItem() ?? 0 }} - {{ $productos->lastItem() ?? 0 }} de {{ $productos->total() }} resultados</div>
                    <div>
                        {{ $productos->links() }}
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>
@endsection
