@extends('layouts.dashboard')

@section('title', 'Medicamentos')

@section('content')
<div class="container-fluid px-2 px-md-4 mt-4">
    @if(session('success'))
        <script>document.addEventListener('DOMContentLoaded',()=>{ window.showToast(@json(session('success')), 'success'); });</script>
    @endif
    @if(session('error'))
        <script>document.addEventListener('DOMContentLoaded',()=>{ window.showToast(@json(session('error')), 'error'); });</script>
    @endif
    @if($errors->any())
        <script>
        document.addEventListener('DOMContentLoaded',()=>{
            window.showToast('Por favor corrige los errores del formulario', 'error');
        });
        </script>
    @endif
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3 gap-2">
        <h3 class="mb-0"><i class="fas fa-capsules me-2"></i>Medicamentos</h3>
        <a href="{{ route('productos.create') }}" class="btn sp-btn-accent shadow-sm"><i class="fas fa-plus"></i> Nuevo Medicamento</a>
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

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Tooltips
        [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).forEach(el=> new bootstrap.Tooltip(el));

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
                <style>
                        /* Paleta clínica: azul oscuro + naranjita (acento) */
                        :root{
                            --primary-dark:#0f2742; /* azul oscuro dashboard */
                            --primary:#153a66;       /* azul medio para títulos */
                            --accent:#ff7a1a;        /* naranjita principal */
                            --accent-soft:#ff9a50;   /* naranjita suave hover */
                            --slate-surface:#f7f8fa; /* fondos claros */
                            --slate-surface-soft:#f1f4f8;
                            --slate-border:#e2e6ee;
                            --slate-line:#dfe3eb;
                            --txt:#1e293b;
                            --txt-sec:#5b6b82;
                        }
                    /* Slate Pro: tabla refinada con zebra suave */
                    .sp-btn-accent{ background:var(--accent); color:#fff; border:1px solid var(--accent); }
                    .sp-btn-accent:hover{ background:var(--accent-soft); border-color:var(--accent-soft); color:#fff; }
                    .sp-btn-outline-accent{ background:transparent; color:var(--accent); border:1px solid var(--accent); }
                    .sp-btn-outline-accent:hover{ background:var(--accent); color:#fff; }
                    .sp-btn-outline-muted{ background:transparent; color:var(--txt-sec); border:1px solid var(--slate-border); }
                    .sp-btn-outline-muted:hover{ background:var(--slate-surface-soft); color:var(--txt); border-color:var(--accent); }

                    .sp-text-accent{ color:var(--accent)!important; }
                    .sp-text-muted{ color:var(--txt-sec)!important; }

                    .sticky-actions{ position:sticky; right:0; background:var(--slate-surface); z-index:2; box-shadow:-2px 0 8px -4px rgba(0,0,0,0.18); }
                    .table-sm th, .table-sm td{ font-size:0.92rem; padding:0.45rem 0.55rem; }

                    .sp-thead th{ background:var(--slate-surface); color:var(--txt-sec); text-transform:uppercase; font-size:.72rem; letter-spacing:.4px; border-bottom:1px solid var(--slate-line); position:sticky; top:0; z-index:1; }
                    .sp-thead a{ color:var(--txt-sec); font-weight:700; }
                    .sp-thead a:hover{ color:var(--accent); }

                    .sp-table tbody tr{ border-bottom:1px solid var(--slate-border); }
                    .sp-table tbody tr:nth-child(even){ background:rgba(255,255,255,.02); }
                    .sp-table tbody tr:hover{ background:var(--slate-surface-soft); }

                    .sp-chip{ background:transparent; color:var(--txt); border:1px solid var(--slate-border); border-radius:999px; padding:.15rem .5rem; font-size:.78em; font-weight:600; }
                    .sp-chip-muted{ background:transparent; color:var(--txt-sec); border:1px solid var(--slate-border); border-radius:999px; padding:.15rem .5rem; font-size:.78em; font-weight:600; }

                    .sp-status{ display:inline-flex; align-items:center; gap:.4rem; font-size:.85em; font-weight:600; }
                    .sp-dot{ width:.5rem; height:.5rem; border-radius:50%; display:inline-block; }
                    .sp-dot-ok{ background:#2ecc71; }
                    .sp-dot-warn{ background:var(--accent); }
                    .sp-dot-danger{ background:#e74c3c; }
                    .sp-state-pill{ border-radius:999px; padding:.18rem .6rem; border:1px solid var(--slate-border); }

                    /* Paginación de medicamentos en naranja del sistema */
                    .pagination .page-link{
                        color:var(--accent);
                        border-color:var(--accent);
                        background-color:#fff;
                    }
                    .pagination .page-link:hover{
                        color:#fff;
                        background-color:var(--accent-soft);
                        border-color:var(--accent-soft);
                    }
                    .pagination .page-item.active .page-link{
                        color:#fff;
                        background-color:var(--accent);
                        border-color:var(--accent);
                    }
                    .pagination .page-item.disabled .page-link{
                        color:var(--txt-sec);
                        background-color:#f0f0f0;
                        border-color:var(--slate-border);
                    }
                </style>
                <table class="table table-hover table-sm align-middle mb-0 text-nowrap sp-table">
                    <thead class="text-center align-middle sp-thead">
                        <tr>
                            @php
                                $dirNombre = (request('sort')==='nombre') ? (request('dir','asc')==='asc' ? 'desc':'asc') : 'asc';
                                $dirCat = (request('sort')==='categoria_id') ? (request('dir','asc')==='asc' ? 'desc':'asc') : 'asc';
                                $dirPres = (request('sort')==='presentacion') ? (request('dir','asc')==='asc' ? 'desc':'asc') : 'asc';
                            @endphp
                            <th scope="col" style="width: 70px;">Código</th>
                            <th scope="col" style="min-width: 140px;">
                                <a href="{{ request()->fullUrlWithQuery(['sort'=>'nombre','dir'=>$dirNombre]) }}" aria-label="Ordenar por nombre">Nombre
                                    @if(request('sort')==='nombre')
                                        <i class="fas fa-sort-{{ request('dir','asc')==='asc' ? 'up' : 'down' }} ms-1" aria-hidden="true"></i>
                                    @else
                                        <i class="fas fa-sort ms-1 text-muted" aria-hidden="true"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col">
                                <a href="{{ request()->fullUrlWithQuery(['sort'=>'categoria_id','dir'=>$dirCat]) }}" aria-label="Ordenar por categoría">Categoría
                                    @if(request('sort')==='categoria_id')
                                        <i class="fas fa-sort-{{ request('dir','asc')==='asc' ? 'up' : 'down' }} ms-1" aria-hidden="true"></i>
                                    @else
                                        <i class="fas fa-sort ms-1 text-muted" aria-hidden="true"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col" style="width: 90px;">Subcat.</th>
                            <th scope="col">
                                <a href="{{ request()->fullUrlWithQuery(['sort'=>'presentacion','dir'=>$dirPres]) }}" aria-label="Ordenar por presentación">Presentación
                                    @if(request('sort')==='presentacion')
                                        <i class="fas fa-sort-{{ request('dir','asc')==='asc' ? 'up' : 'down' }} ms-1" aria-hidden="true"></i>
                                    @else
                                        <i class="fas fa-sort ms-1 text-muted" aria-hidden="true"></i>
                                    @endif
                                </a>
                            </th>
                            <th scope="col" style="width: 120px;">Estado</th>
                            <!-- Columna de stock eliminada para que el stock solo se vea en inventario y detalle -->
                            <th scope="col" style="width: 110px;">Proveedor</th>
                            <th scope="col" class="sticky-actions" style="width: 110px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productos as $producto)
                        <tr>
                            <td class="fw-bold sp-text-accent small">{{ $producto->codigo }}</td>
                            <td class="small"><span class="fw-semibold">{{ $producto->nombre }}</span></td>
                            <td><span class="sp-chip small">{{ $producto->categoria->nombre ?? '-' }}</span></td>
                            <td><span class="sp-chip-muted small">{{ $producto->subcategoria->nombre ?? '-' }}</span></td>
                            <td><span class="sp-text-muted small">{{ $producto->presentacion }}</span></td>
                            <td>
                                @php
                                    $estado = 'ok'; $estadoTxt = 'Activo';
                                    if($producto->fecha_vencimiento){
                                        $fv = \Carbon\Carbon::parse($producto->fecha_vencimiento);
                                        if($fv->isPast()){ $estado='danger'; $estadoTxt='Vencido'; }
                                        else {
                                            // Calcular días enteros restantes, sin decimales
                                            $dias = \Carbon\Carbon::now()->startOfDay()->diffInDays($fv->copy()->startOfDay(), false);
                                            $diasEnteros = (int) $dias; // asegurar entero
                                            if($diasEnteros <= 30){ $estado='warn'; $estadoTxt = 'Próx. (' . $diasEnteros . ' días)'; }
                                            else { $estado='ok'; $estadoTxt = 'Activo'; }
                                        }
                                    }
                                @endphp
                                <span class="sp-status" title="{{ $producto->fecha_vencimiento ? 'Vence: ' . \Carbon\Carbon::parse($producto->fecha_vencimiento)->format('d/m/Y') : 'Sin fecha' }}">
                                    <span class="sp-dot sp-dot-{{ $estado }}" aria-hidden="true"></span>
                                    <span class="sp-state-pill">{{ $estadoTxt }}</span>
                                </span>
                            </td>
                            <!-- Celda de stock eliminada -->
                            <td><span class="sp-chip-muted small">{{ $producto->proveedor->nombre ?? '-' }}</span></td>
                            <td class="text-center sticky-actions">
                                <div class="d-flex flex-nowrap justify-content-center gap-1">
                                    <a href="{{ route('productos.show', $producto) }}" class="btn sp-btn-outline-accent btn-sm px-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Ver Detalle"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('productos.edit', $producto) }}" class="btn sp-btn-outline-muted btn-sm px-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Editar"><i class="fas fa-edit"></i></a>
                                    @php
                                        $invCount = \App\Models\Inventario::where('producto_id',$producto->id)->count();
                                        $movCount = \App\Models\Movimiento::where('producto_id',$producto->id)->count();
                                    @endphp
                                    <form action="{{ route('productos.destroy', $producto) }}" method="POST" class="d-inline-block form-eliminar-medicamento"
                                          data-inv="{{ $invCount }}" data-mov="{{ $movCount }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-outline-danger btn-sm px-1 btn-modal-eliminar" data-bs-toggle="tooltip" data-bs-placement="top" title="Eliminar" data-nombre="{{ $producto->nombre }}"><i class="fas fa-trash"></i></button>
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
<!-- Modal de confirmación para eliminar medicamento -->
<div id="modalEliminarMedicamento" class="modal" tabindex="-1" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.25); z-index:9999; justify-content:center; align-items:center;">
    <div style="display:flex; justify-content:center; align-items:center; width:100%; height:100%;">
        <div class="modal-content" style="margin:auto; max-width:380px; width:100%; text-align:center; box-sizing:border-box; padding:1.5rem 1.2rem; background:#fff; border-radius:10px; box-shadow:0 4px 24px rgba(0,0,0,0.18);">
            <h3 style="color:#e74c3c; margin-bottom:1rem;">¿Eliminar medicamento?</h3>
            <div id="mensajeEliminarMedicamento" style="font-size:1.08rem; margin-bottom:1.5rem; color:#333;">¿Estás seguro de que deseas eliminar este medicamento?</div>
            <div style="display:flex; justify-content:center; gap:1.2rem;">
                <button id="btnCancelarEliminar" type="button" style="background:#ccc;color:#222;padding:0.5rem 1.5rem;border:none;border-radius:5px;">Cancelar</button>
                <button id="btnConfirmarEliminar" type="button" style="background:#e74c3c;color:#fff;padding:0.5rem 1.5rem;border:none;border-radius:5px;font-weight:bold;">Sí, eliminar</button>
            </div>
        </div>
    </div>
</div>
<script>
    let formEliminarActual = null;
    document.querySelectorAll('.btn-modal-eliminar').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            formEliminarActual = btn.closest('form');
            const nombre = btn.getAttribute('data-nombre');
            const inv = formEliminarActual?.getAttribute('data-inv') ?? 0;
            const mov = formEliminarActual?.getAttribute('data-mov') ?? 0;
            const detalle = (Number(inv)>0 || Number(mov)>0)
                ? `\nEste producto tiene ${inv} inventario(s) y ${mov} movimiento(s) asociados.\nDebe eliminar/ajustar esos registros antes.`
                : '';
            document.getElementById('mensajeEliminarMedicamento').innerText = `¿Estás seguro de que deseas eliminar "${nombre}"?${detalle}`;
            document.getElementById('modalEliminarMedicamento').style.display = 'block';
        });
    });
    document.getElementById('btnCancelarEliminar').onclick = function() {
        document.getElementById('modalEliminarMedicamento').style.display = 'none';
        formEliminarActual = null;
    };
    document.getElementById('btnConfirmarEliminar').onclick = function() {
        if(formEliminarActual) formEliminarActual.submit();
        document.getElementById('modalEliminarMedicamento').style.display = 'none';
    };
</script>
                    <div class="small text-muted">Mostrando {{ $productos->firstItem() ?? 0 }} - {{ $productos->lastItem() ?? 0 }} de {{ $productos->total() }} resultados</div>
                    <div>
                        <nav aria-label="Paginación de medicamentos">
                            {!! $productos->links('pagination::bootstrap-5') !!}
                        </nav>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>
@endsection
