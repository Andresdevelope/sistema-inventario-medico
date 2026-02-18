@extends('layouts.dashboard')

@section('content')
<div class="container mt-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="m-0">Historial de Consumo</h2>
    <div class="d-flex gap-2">
      <a href="{{ route('movimientos.index') }}" class="btn btn-orange"><i class="fa fa-exchange me-1"></i> Registrar Movimiento <span class="btn-badge ms-1">MOV</span></a>
      <a href="{{ route('reportes.index') }}" class="btn btn-orange"><i class="fa fa-bar-chart me-1"></i> Reportes <span class="btn-badge ms-1">REP</span></a>
    </div>
  </div>
  <style>
    .btn-orange {
      background-color: var(--color-orange-600, #FF7300);
      color: #fff !important;
      border: 1px solid var(--color-orange-700, #E56200);
      transition: background-color .2s ease, box-shadow .2s ease, transform .05s ease;
    }
    .btn-orange:hover { background-color: var(--color-orange-500, #FF8A00); color: #fff !important; box-shadow: 0 2px 6px rgba(0,0,0,.1); }
    .btn-orange:active { transform: translateY(1px); }
    .btn-badge {
      display: inline-block;
      background-color: rgba(255,255,255,.18);
      border: 1px solid rgba(255,255,255,.35);
      color: #fff;
      font-size: .75rem;
      padding: .1rem .35rem;
      border-radius: .5rem;
      vertical-align: middle;
    }

    /* Tabla unificada con "Últimos movimientos" */
    .table-mov-ultimos {
      margin-bottom: 0;
      border: 1px solid var(--slate-border, #d9e0e6);
      border-radius: .75rem;
      overflow: hidden;
      background: var(--slate-surface, #fff);
    }

    .table-mov-ultimos thead th {
      background: var(--slate-surface-soft, #f4f7fa);
      color: var(--txt, #1f2937);
      font-weight: 700;
      border-bottom: 1px solid var(--slate-border, #d9e0e6);
      white-space: nowrap;
    }

    .table-mov-ultimos tbody td {
      background: var(--slate-surface, #fff);
      color: var(--txt, #1f2937);
      border-color: var(--slate-border, #e5e7eb);
      vertical-align: middle;
    }

    .table-mov-ultimos.table-hover tbody tr:hover > td {
      background: rgba(255, 106, 23, .08);
      transition: background-color .15s ease;
    }

    /* Badges igual que en últimos movimientos */
    .table-mov-ultimos .badge.bg-success { background: #ecfdf5 !important; color: #047857 !important; border: 1px solid #6ee7b7; }
    .table-mov-ultimos .badge.bg-danger { background: #fff1f2 !important; color: #b42318 !important; border: 1px solid #fda4af; }
    .table-mov-ultimos .badge.bg-primary { background: #eff6ff !important; color: #1d4ed8 !important; border: 1px solid #93c5fd; }
    .table-mov-ultimos .badge.bg-warning,
    .table-mov-ultimos .badge.text-dark { background: #fffbeb !important; color: #92400e !important; border: 1px solid #fcd34d; }
    .table-mov-ultimos .badge.bg-dark { background: #1f2937 !important; color: #f9fafb !important; border: 1px solid #374151; }
    .table-mov-ultimos .badge.bg-info { background: #ecfeff !important; color: #155e75 !important; border: 1px solid #67e8f9; }
    .table-mov-ultimos .badge.bg-secondary { background: #f1f5f9 !important; color: #475569 !important; border: 1px solid #cbd5e1; }
    .table-mov-ultimos .badge.bg-light.text-dark { background: #f8fafc !important; color: #334155 !important; border: 1px solid #cbd5e1; }
  </style>

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Desde</label>
          <input type="date" class="form-control" name="desde" value="{{ request('desde') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Hasta</label>
          <input type="date" class="form-control" name="hasta" value="{{ request('hasta') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Destino</label>
          <select name="destino_id" class="form-select">
            <option value="">Todos</option>
            @foreach($destinos as $d)
              <option value="{{ $d->id }}" @selected(request('destino_id') == $d->id)>{{ $d->nombre }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Producto</label>
          <select name="producto_id" class="form-select">
            <option value="">Todos</option>
            @foreach($productos as $p)
              <option value="{{ $p->id }}" @selected(request('producto_id') == $p->id)>{{ $p->nombre }} ({{ $p->codigo }})</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Sexo</label>
          <select name="sexo" class="form-select">
            <option value="">Todos</option>
            @foreach(['F','M','otro'] as $sx)
              <option value="{{ $sx }}" @selected(request('sexo') === $sx)>{{ strtoupper($sx) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Tipo identificación</label>
          <select name="tipo_identificacion" class="form-select">
            <option value="">Todos</option>
            @foreach(['estudiante','trabajador','profesor','comunidad'] as $ti)
              <option value="{{ $ti }}" @selected(request('tipo_identificacion') === $ti)>{{ strtoupper($ti) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Por página</label>
          <select name="per_page" class="form-select">
            @foreach([10,20,50] as $pp)
              <option value="{{ $pp }}" @selected((int)request('per_page',20) === $pp)>{{ $pp }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-12 d-flex justify-content-end">
          <button type="submit" class="btn btn-primary"><i class="fa fa-search me-1"></i> Filtrar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="m-0">Resultados</h5>
        <small class="text-muted">Modalidad: <span class="badge bg-dark">consumo</span></small>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle table-mov-ultimos">
          <thead class="table-light">
            <tr>
              <th>Fecha</th>
              <th>Destino</th>
              <th>Producto</th>
              <th>Lote</th>
              <th>Vence</th>
              <th>Cant.</th>
              <th>Sexo</th>
              <th>Ident.</th>
              <th>Usuario</th>
              <th>Obs.</th>
            </tr>
          </thead>
          <tbody>
            @forelse($consumos as $m)
              @php
                $fv = optional($m->inventario)->fecha_vencimiento;
                $fvC = $fv ? \Carbon\Carbon::parse($fv) : null;
                $dias = $fvC ? now()->diffInDays($fvC, false) : null;
                $fvBadge = $fvC ? ($dias < 0 ? 'danger' : ($dias <= 30 ? 'warning text-dark' : 'info')) : null;
              @endphp
              <tr>
                <td>{{ \Carbon\Carbon::parse($m->fecha)->format('d/m/Y') }}</td>
                <td><span class="badge bg-secondary">{{ $m->destino->nombre ?? '-' }}</span></td>
                <td>
                  <strong>{{ $m->producto->nombre ?? '—' }}</strong>
                  <span class="text-muted">({{ $m->producto->codigo ?? '' }})</span>
                </td>
                <td>{{ $m->inventario->lote ?? '—' }}</td>
                <td>
                  @if($fvC)
                    <span class="badge bg-{{ $fvBadge }}">{{ $fvC->format('d/m/Y') }}</span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>{{ $m->cantidad }}</td>
                <td><span class="badge bg-light text-dark">{{ strtoupper($m->sexo ?? '-') }}</span></td>
                <td><span class="badge bg-light text-dark">{{ strtoupper($m->tipo_identificacion ?? '-') }}</span></td>
                <td>{{ $m->usuario->name ?? '-' }}</td>
                <td class="text-truncate" style="max-width:220px;" title="{{ $m->observaciones }}">{{ $m->observaciones ?? '-' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="text-center text-muted">Sin consumos en el criterio seleccionado.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="d-flex justify-content-end">
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap w-100">
          <div class="text-muted small mb-2 mb-md-0">
            @php
              $from = $consumos->firstItem();
              $to = $consumos->lastItem();
              $total = $consumos->total();
            @endphp
            Mostrando <b>{{ $from }}</b> - <b>{{ $to }}</b> de <b>{{ $total }}</b> consumos
          </div>
          <nav aria-label="Paginador historial consumo">
            <ul class="pagination mb-0">
              <li class="page-item {{ $consumos->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $consumos->url(1) }}" aria-label="Primera"><i class="fa fa-angle-double-left"></i></a>
              </li>
              <li class="page-item {{ $consumos->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $consumos->previousPageUrl() }}" aria-label="Anterior"><i class="fa fa-angle-left"></i></a>
              </li>
              @foreach ($consumos->getUrlRange(max(1, $consumos->currentPage()-2), min($consumos->lastPage(), $consumos->currentPage()+2)) as $page => $url)
                <li class="page-item {{ $page == $consumos->currentPage() ? 'active' : '' }}">
                  <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                </li>
              @endforeach
              <li class="page-item {{ $consumos->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $consumos->nextPageUrl() }}" aria-label="Siguiente"><i class="fa fa-angle-right"></i></a>
              </li>
              <li class="page-item {{ $consumos->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $consumos->url($consumos->lastPage()) }}" aria-label="Última"><i class="fa fa-angle-double-right"></i></a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
