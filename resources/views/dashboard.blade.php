@extends('layouts.dashboard')

@section('content')
    <div style="margin-bottom: 2.2rem;">
        <div style="background: #e3f4fd; color: #2176ae; padding: 1.5rem 1.5rem 1.2rem 1.5rem; border-radius: 10px; text-align: center; box-shadow: 0 2px 8px rgba(44,62,80,0.06);">
            <div style="font-size: 1.2rem; font-weight: 500; letter-spacing: 0.5px; margin-bottom: 0.2rem;">Bienvenido</div>
            <div style="font-size: 2.1rem; font-weight: bold; margin-bottom: 0.2rem; color: #145a8a; line-height: 1.1;">
                <i class="fa fa-user" style="margin-right: 10px; color: #2176ae;"></i>{{ Auth::user()->name ?? 'Usuario' }}
            </div>
            <div style="font-size: 1.08rem; color: #2176ae;">al Sistma de inventario de medicamentos</div>
        </div>
    <div class="cards" style="gap: 1.5rem; display: flex; flex-wrap: wrap; justify-content: flex-start;">
        <a href="{{ url('/categorias') }}" class="card-dashboard bg-primary text-white" style="min-width:220px; flex:1; max-width:260px; text-decoration:none;">
            <div class="d-flex align-items-center mb-2">
                <i class="fa fa-folder fa-2x me-3"></i>
                <div>
                    <div class="fs-4 fw-bold">{{ $totalCategorias ?? '-' }}</div>
                    <div class="fs-6">Categorías</div>
                </div>
            </div>
        </a>
        <a href="{{ url('/productos') }}" class="card-dashboard bg-success text-white" style="min-width:220px; flex:1; max-width:260px; text-decoration:none;">
            <div class="d-flex align-items-center mb-2">
                <i class="fa fa-pills fa-2x me-3"></i>
                <div>
                    <div class="fs-4 fw-bold">{{ $totalProductos ?? '-' }}</div>
                    <div class="fs-6">Medicamento</div>
                </div>
            </div>
        </a>
        <a href="{{ url('/movimientos') }}" class="card-dashboard bg-warning text-dark" style="min-width:220px; flex:1; max-width:260px; text-decoration:none;">
            <div class="d-flex align-items-center mb-2">
                <i class="fa fa-exchange-alt fa-2x me-3"></i>
                <div>
                    <div class="fs-4 fw-bold">{{ $totalMovimientos ?? '-' }}</div>
                    <div class="fs-6">Movimientos</div>
                </div>
            </div>
        </a>
        <a href="{{ url('/inventario') }}" class="card-dashboard bg-danger text-white" style="min-width:220px; flex:1; max-width:260px; text-decoration:none;">
            <div class="d-flex align-items-center mb-2">
                <i class="fa fa-warehouse fa-2x me-3"></i>
                <div>
                    <div class="fs-4 fw-bold">{{ $totalInventario ?? '-' }}</div>
                    <div class="fs-6">Inventario</div>
                </div>
            </div>
        </a>
        <a href="{{ url('/usuarios') }}" class="card-dashboard bg-dark text-white" style="min-width:220px; flex:1; max-width:260px; text-decoration:none;">
            <div class="d-flex align-items-center mb-2">
                <i class="fa fa-users fa-2x me-3"></i>
                <div>
                    <div class="fs-4 fw-bold">{{ $totalUsuarios ?? '-' }}</div>
                    <div class="fs-6">Usuarios</div>
                </div>
            </div>
        </a>
        <a href="{{ url('/reportes') }}" class="card-dashboard" style="background:#6c3483; color:#fff; min-width:220px; flex:1; max-width:260px; text-decoration:none;">
            <div class="d-flex align-items-center mb-2">
                <i class="fa fa-chart-bar fa-2x me-3"></i>
                <div>
                    <div class="fs-4 fw-bold">-</div>
                    <div class="fs-6">Reportes</div>
                </div>
            </div>
        </a>

    </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .card-dashboard {
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(44,62,80,0.08);
        padding: 1.5rem 1.2rem 1.2rem 1.2rem;
        margin-bottom: 1.5rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        transition: box-shadow 0.2s, transform 0.2s;
        cursor: pointer;
    }
    .card-dashboard:hover {
        box-shadow: 0 6px 18px rgba(44,62,80,0.18);
        transform: translateY(-3px) scale(1.03);
    }
    .card-dashboard .fa {
        opacity: 0.85;
    }
    @media (max-width: 900px) {
        .row {
            flex-direction: column;
            gap: 0.8rem;
        }
        .card-dashboard {
            max-width: 100%;
        }
    }
</style>
@endpush
