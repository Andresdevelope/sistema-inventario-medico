@extends('layouts.dashboard')

@section('content')
<div class="container mt-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="m-0">Registrar Movimiento</h2>
    <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary">Ver Inventario</a>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif
  @if (session('success'))
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        showToast(@json(session('success')), 'success');
      });
    </script>
  @endif
  @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form method="POST" action="{{ route('movimientos.store') }}" class="row g-3">
        @csrf
        <div class="col-md-5">
          <label class="form-label">Medicamento</label>
          <select name="producto_id" class="form-select" required>
            <option value="">Seleccione...</option>
            @foreach($productos as $p)
              <option value="{{ $p->id }}" @selected(old('producto_id')==$p->id)>{{ $p->nombre }} ({{ $p->codigo }})</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Tipo</label>
          <select name="tipo" id="tipo" class="form-select" required>
            <option value="ingreso" @selected(old('tipo')==='ingreso')>Entrada</option>
            <option value="egreso" @selected(old('tipo')==='egreso')>Salida</option>
            <option value="ajuste_pos" @selected(old('tipo')==='ajuste_pos')>Ajuste +</option>
            <option value="ajuste_neg" @selected(old('tipo')==='ajuste_neg')>Ajuste -</option>
          </select>
        </div>
        <div class="col-md-4" id="destino-wrapper" style="display:none;">
          <label class="form-label">Destino (solo SALIDA)</label>
          <select name="destino_id" id="destino_id" class="form-select">
            <option value="">Seleccione destino...</option>
            @isset($destinos)
              @foreach($destinos as $d)
                <option value="{{ $d->id }}" @selected(old('destino_id')==$d->id)>{{ $d->nombre }} ({{ $d->codigo }})</option>
              @endforeach
            @endisset
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Cantidad</label>
          <input type="number" min="1" class="form-control" name="cantidad" value="{{ old('cantidad',1) }}" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Fecha</label>
          <input type="date" class="form-control" name="fecha" value="{{ old('fecha', now()->toDateString()) }}">
        </div>

        <div class="col-md-3" id="fv-wrapper">
          <label class="form-label">Fecha de vencimiento (solo ENTRADA)</label>
          <input type="date" class="form-control" name="fecha_vencimiento" value="{{ old('fecha_vencimiento') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Motivo</label>
          <input type="text" class="form-control" name="motivo" value="{{ old('motivo') }}" placeholder="Opcional">
        </div>
        <div class="col-md-6">
          <label class="form-label">Observaciones</label>
          <input type="text" class="form-control" name="observaciones" value="{{ old('observaciones') }}" placeholder="Opcional">
        </div>

        <div class="col-12 d-flex justify-content-end">
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="m-0">Últimos movimientos</h5>
        <form method="GET" class="d-flex align-items-center gap-2">
          <label class="text-muted small">Ver</label>
          <select class="form-select form-select-sm" name="per_page" onchange="this.form.submit()" style="width: 90px;">
            @foreach([10,20,50] as $pp)
              <option value="{{ $pp }}" @selected((int)request('per_page',10) === $pp)>{{ $pp }}</option>
            @endforeach
          </select>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Fecha</th>
              <th>Producto</th>
              <th>Tipo</th>
              <th>Cant.</th>
              <th>Área</th>
              <th>Vencimiento</th>
              <th>Usuario</th>
              <th>Motivo</th>
            </tr>
          </thead>
          <tbody>
            @forelse($ultimos as $m)
              @php
                $badge = [
                  'ingreso' => 'success',
                  'egreso' => 'danger',
                  'ajuste_pos' => 'primary',
                  'ajuste_neg' => 'warning',
                ][$m->tipo] ?? 'secondary';
                $fv = optional($m->inventario)->fecha_vencimiento;
              @endphp
              <tr>
                <td>{{ \Carbon\Carbon::parse($m->fecha)->format('d/m/Y') }}</td>
                <td>
                  <strong>{{ $m->producto->nombre ?? '—' }}</strong>
                  <span class="text-muted">({{ $m->producto->codigo ?? '' }})</span>
                </td>
                <td><span class="badge bg-{{ $badge }} text-uppercase">{{ $m->tipo }}</span></td>
                <td>{{ $m->cantidad }}</td>
                <td><span class="badge bg-dark">{{ $m->salida ?? '-' }}</span></td>
                <td>
                  @if($fv)
                    @php $fvC = \Carbon\Carbon::parse($fv); $dias = now()->diffInDays($fvC, false); @endphp
                    <span class="badge bg-{{ $dias < 0 ? 'danger' : ($dias <= 30 ? 'warning text-dark' : 'info') }}">{{ $fvC->format('d/m/Y') }}</span>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td>{{ $m->usuario->name ?? '-' }}</td>
                <td class="text-truncate" style="max-width:240px;" title="{{ $m->motivo }}">{{ $m->motivo ?? '-' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-muted">Aún no hay movimientos registrados.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="d-flex justify-content-end">
        <!-- Paginador moderno igual que bitácora -->
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap w-100">
          <div class="text-muted small mb-2 mb-md-0">
            @php
              $from = $ultimos->firstItem();
              $to = $ultimos->lastItem();
              $total = $ultimos->total();
            @endphp
            Mostrando <b>{{ $from }}</b> - <b>{{ $to }}</b> de <b>{{ $total }}</b> movimientos
          </div>
          <nav aria-label="Paginador movimientos">
            <ul class="pagination pagination-lg mb-0">
              <!-- Primera página -->
              <li class="page-item {{ $ultimos->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $ultimos->url(1) }}" aria-label="Primera">
                  <i class="fa fa-angle-double-left"></i>
                </a>
              </li>
              <!-- Página anterior -->
              <li class="page-item {{ $ultimos->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $ultimos->previousPageUrl() }}" aria-label="Anterior">
                  <i class="fa fa-angle-left"></i>
                </a>
              </li>
              <!-- Páginas -->
              @foreach ($ultimos->getUrlRange(max(1, $ultimos->currentPage()-2), min($ultimos->lastPage(), $ultimos->currentPage()+2)) as $page => $url)
                <li class="page-item {{ $page == $ultimos->currentPage() ? 'active' : '' }}">
                  <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                </li>
              @endforeach
              <!-- Página siguiente -->
              <li class="page-item {{ $ultimos->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $ultimos->nextPageUrl() }}" aria-label="Siguiente">
                  <i class="fa fa-angle-right"></i>
                </a>
              </li>
              <!-- Última página -->
              <li class="page-item {{ $ultimos->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $ultimos->url($ultimos->lastPage()) }}" aria-label="Última">
                  <i class="fa fa-angle-double-right"></i>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  const tipoSel = document.getElementById('tipo');
  const fvWrap = document.getElementById('fv-wrapper');
  const destinoWrap = document.getElementById('destino-wrapper');
  function toggleExtras(){
    fvWrap.style.display = (tipoSel.value === 'ingreso') ? 'block' : 'none';
    destinoWrap.style.display = (tipoSel.value === 'egreso') ? 'block' : 'none';
  }
  document.addEventListener('DOMContentLoaded', toggleExtras);
  tipoSel.addEventListener('change', toggleExtras);
// existing scripts...
</script>
@endpush
@endsection
