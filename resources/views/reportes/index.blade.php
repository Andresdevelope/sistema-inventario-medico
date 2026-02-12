
@extends('layouts.dashboard')
@section('content')
<div class="container mt-4">
  <h2 class="mb-3">Reportes</h2>
  @php
    $modalidadSeleccionada = $modalidad_reporte ?? 'inventario';
    $hasResultados = $resumen && request()->filled('from') && request()->filled('to');
  @endphp
  <form method="GET" class="row g-3 align-items-end mb-4">
    <div class="col-12">
      <label class="form-label fw-semibold text-uppercase small text-muted">Modalidad de reporte</label>
      <div class="row g-3 report-mode-grid" role="radiogroup" aria-label="Seleccionar modalidad de reporte">
        <div class="col-md-6">
          <input type="radio" class="btn-check" name="modalidad_reporte" id="modo-inventario" value="inventario" autocomplete="off" @checked($modalidadSeleccionada==='inventario')>
          <label class="report-mode-card" for="modo-inventario">
            <div class="report-mode-card__icon">
              <i class="fa fa-warehouse"></i>
            </div>
            <div class="report-mode-card__body">
              <h6>Inventario de Medicamentos e Insumos</h6>
              <p>Balance consolidado por destino + central para descargar en PDF.</p>
              <span class="report-mode-card__meta"><i class="fa fa-file-pdf me-1"></i>Formato PDF</span>
            </div>
          </label>
        </div>
        <div class="col-md-6">
          <input type="radio" class="btn-check" name="modalidad_reporte" id="modo-consumo" value="consumo" autocomplete="off" @checked($modalidadSeleccionada==='consumo')>
          <label class="report-mode-card" for="modo-consumo">
            <div class="report-mode-card__icon">
              <i class="fa fa-user-nurse"></i>
            </div>
            <div class="report-mode-card__body">
              <h6>Salidas – Farmacia interna</h6>
              <p>Detalle de consumos (modalidad consumo) por servicio y beneficiarios.</p>
              <span class="report-mode-card__meta"><i class="fa fa-chart-pie me-1"></i>Métricas de consumo</span>
            </div>
          </label>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <label class="form-label">Desde</label>
      <input type="date" name="from" class="form-control" value="{{ $from ?? '' }}" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Hasta</label>
      <input type="date" name="to" class="form-control" value="{{ $to ?? '' }}" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Periodo</label>
      <select name="periodo" id="periodo" class="form-select">
        <option value="">Personalizado</option>
        @foreach(['mensual'=>'Mensual','trimestral'=>'Trimestral','semestral'=>'Semestral','anual'=>'Anual'] as $k=>$v)
          <option value="{{ $k }}" @selected(($periodo ?? '')===$k)>{{ $v }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Destino (opcional)</label>
      <select name="destino_id" class="form-select">
        <option value="">Todos</option>
        @foreach($destinos as $d)
          <option value="{{ $d->id }}" @selected($destino_id==$d->id)>{{ $d->nombre }} ({{ $d->codigo }})</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Tipo</label>
      <select name="tipo" class="form-select">
        <option value="" @selected(($tipo ?? '')==='')>Todos</option>
        <option value="medicamento" @selected(($tipo ?? '')==='medicamento')>Medicamento</option>
        <option value="insumo" @selected(($tipo ?? '')==='insumo')>Insumo</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Categoría</label>
      <select name="categoria_id" id="categoria_id" class="form-select">
        <option value="">Todas</option>
        @foreach($categorias as $c)
          <option value="{{ $c->id }}" @selected(($categoria_id ?? '')==$c->id)>{{ $c->nombre }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Subcategoría</label>
      <select name="subcategoria_id" id="subcategoria_id" class="form-select">
        <option value="">Todas</option>
        @foreach($subcategorias as $s)
          <option value="{{ $s->id }}" data-cat="{{ $s->categoria_id }}" @selected(($subcategoria_id ?? '')==$s->id)>{{ $s->nombre }}</option>
        @endforeach
      </select>
      <small class="text-muted">Se filtra según la categoría seleccionada.</small>
    </div>
    <div class="col-md-3 form-check form-switch mt-4" id="mostrar-insumos-wrapper" style="{{ $modalidadSeleccionada==='consumo' ? '' : 'display:none;' }}">
      <input class="form-check-input" type="checkbox" name="mostrar_insumos" id="mostrar_insumos" value="1" @checked(($mostrar_insumos ?? false))>
      <label class="form-check-label" for="mostrar_insumos">Mostrar columna "Insumos entregados" en 10.2</label>
    </div>
    <div class="col-md-3 d-flex gap-2 flex-wrap">
      <button class="btn btn-primary flex-grow-1"><i class="fa fa-chart-bar me-1"></i> Generar</button>
      @if($hasResultados)
        <a href="{{ route('reportes.index') }}" class="btn btn-light border"><i class="fa fa-rotate-left me-1"></i> Limpiar</a>
      @endif
    </div>
  </form>

  @if(session('error'))
    <div class="alert alert-danger mb-3">{{ session('error') }}</div>
  @endif

  @if(!$hasResultados)
    <div class="report-placeholder">
      <div class="report-placeholder__icon">
        <i class="fa fa-clipboard-list"></i>
      </div>
      <div>
        <h5 class="mb-1">Aún no has generado un reporte</h5>
        <p class="text-muted mb-2">Define la modalidad, el rango de fechas y presiona <strong>Generar</strong> para ver el detalle. Este panel permanecerá vacío hasta que envíes la búsqueda.</p>
        <a href="{{ route('reportes.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-rotate-left me-1"></i>Limpiar filtros</a>
      </div>
    </div>
  @else
    @if($modalidadSeleccionada==='inventario' && $inventario_matriz)
    <div class="bg-white p-3 rounded shadow-sm border mb-4">
      <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
        <h6 class="m-0">INVENTARIO DE MEDICAMENTOS E INSUMOS</h6>
        <a href="{{ route('reportes.export.pdf.inventario',[ 'to'=>$inventario_matriz['cutoff'], 'tipo'=>$tipo, 'categoria_id'=>$categoria_id, 'subcategoria_id'=>$subcategoria_id, 'periodo'=>$periodo ]) }}" class="btn btn-sm btn-outline-danger">
          <i class="fa fa-file-pdf me-1"></i> Exportar PDF
        </a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th>Descripción</th>
              <th>Presentación</th>
              <th>UM</th>
              @foreach(($inventario_matriz['destinos'] ?? []) as $d)
                <th>{{ $d['nombre'] }} {{ \Carbon\Carbon::parse($inventario_matriz['cutoff'])->format('d/m/y') }}</th>
              @endforeach
              <th>Depósito/Central {{ \Carbon\Carbon::parse($inventario_matriz['cutoff'])->format('d/m/y') }}</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            @forelse(($inventario_matriz['rows'] ?? []) as $row)
              <tr>
                <td>{{ $row['descripcion'] }}</td>
                <td>{{ $row['presentacion'] }}</td>
                <td>{{ $row['um'] }}</td>
                @foreach(($inventario_matriz['destinos'] ?? []) as $d)
                  @php $key = $d['codigo'] ?: $d['nombre']; @endphp
                  <td>{{ $row['destinos'][$key] ?? 0 }}</td>
                @endforeach
                <td>{{ $row['central'] }}</td>
                <td>{{ $row['total'] }}</td>
              </tr>
            @empty
              <tr><td colspan="{{ 3 + ($inventario_matriz['destinos'] ? count($inventario_matriz['destinos']) : 0) + 2 }}" class="text-center text-muted">Sin existencias a la fecha de corte</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="small text-muted">UM: Blíster para medicamentos; Unidad para insumos (o la unidad definida por el producto). La cifra corresponde al estado a la fecha de corte.</div>
    </div>
    @elseif($modalidadSeleccionada==='inventario' && !$inventario_matriz)
      <div class="alert alert-warning">No se encontraron saldos para el corte seleccionado.</div>
    @endif
    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <div class="p-3 bg-white rounded shadow-sm border">
          <div class="text-muted small">Unidades egresadas</div>
          <div class="fs-4 fw-bold">{{ $resumen['total_unidades'] }}</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="p-3 bg-white rounded shadow-sm border">
          <div class="text-muted small">Medicamentos distintos</div>
          <div class="fs-4 fw-bold">{{ $resumen['productos_distintos'] }}</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="p-3 bg-white rounded shadow-sm border">
          <div class="text-muted small">Ajustes negativos</div>
          <div class="fs-4 fw-bold">{{ $resumen['ajustes_negativos'] }}</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="p-3 bg-white rounded shadow-sm border">
          <div class="text-muted small">Productos con stock bajo</div>
          <div class="fs-4 fw-bold">{{ count($resumen['stock_bajo']) }}</div>
        </div>
      </div>
    </div>
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="p-3 bg-white rounded shadow-sm border">
          <div class="text-muted small">Unidades ingresadas</div>
          <div class="fs-4 fw-bold">{{ $resumen['total_ingresos'] ?? 0 }}</div>
        </div>
      </div>
      <div class="col-md-9 d-flex align-items-center">
        <div class="alert alert-secondary w-100 mb-0 small">Las "Unidades ingresadas" incluyen movimientos tipo ingreso y ajuste positivo. El filtro de destino sólo afecta las salidas.</div>
      </div>
    </div>
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="bg-white p-3 rounded shadow-sm border h-100">
          <h6 class="border-bottom pb-2 mb-3">Top destinos</h6>
          @if(count($resumen['top_destinos'])===0)
            <div class="text-muted small">Sin datos</div>
          @else
            <ul class="list-unstyled mb-0">
              @foreach($resumen['top_destinos'] as $td)
                <li class="d-flex justify-content-between mb-1"><span>{{ $td['nombre'] }}</span><span class="badge bg-primary">{{ $td['total'] }}</span></li>
              @endforeach
            </ul>
          @endif
        </div>
      </div>
      <div class="col-md-6">
        <div class="bg-white p-3 rounded shadow-sm border h-100">
          <h6 class="border-bottom pb-2 mb-3">Top medicamentos</h6>
          @if(count($resumen['top_medicamentos'])===0)
            <div class="text-muted small">Sin datos</div>
          @else
            <ul class="list-unstyled mb-0">
              @foreach($resumen['top_medicamentos'] as $tm)
                <li class="d-flex justify-content-between mb-1"><span>{{ $tm['nombre'] }} ({{ $tm['codigo'] }})</span><span class="badge bg-success">{{ $tm['total'] }}</span></li>
              @endforeach
            </ul>
          @endif
        </div>
      </div>
    </div>
    <div class="bg-white p-3 rounded shadow-sm border mb-4">
      <h6 class="border-bottom pb-2 mb-3">Caducidad próxima (≤30 días)</h6>
      @if(count($resumen['caducidad_proxima'])===0)
        <div class="text-muted small">Sin lotes próximos a vencer.</div>
      @else
        <div class="table-responsive">
          <table class="table table-sm table-bordered mb-0">
            <thead class="table-light">
              <tr><th>Medicamento</th><th>Fecha vencimiento</th><th>Cantidad</th></tr>
            </thead>
            <tbody>
              @foreach($resumen['caducidad_proxima'] as $c)
                <tr><td>{{ $c['nombre'] }}</td><td>{{ $c['fecha_vencimiento'] }}</td><td>{{ $c['cantidad'] }}</td></tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
    <div class="bg-white p-3 rounded shadow-sm border mb-4">
      <h6 class="border-bottom pb-2 mb-3">Detalle de consumo</h6>
      <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th>Código</th>
              <th>Medicamento</th>
              <th>Entradas</th>
              <th>Salidas</th>
              <th>Movimientos</th>
              <th>Stock final</th>
            </tr>
          </thead>
          <tbody>
            @forelse($detalle as $row)
              <tr>
                <td>{{ $row['codigo'] }}</td>
                <td>{{ $row['nombre'] }}</td>
                <td><span class="badge bg-success">{{ $row['entradas'] }}</span></td>
                <td><span class="badge bg-primary">{{ $row['salidas'] }}</span></td>
                <td>{{ $row['movimientos'] }}</td>
                <td>{{ $row['stock_final'] }}</td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center text-muted">Sin datos en el rango</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($modalidadSeleccionada==='consumo')
      <div class="bg-white p-3 rounded shadow-sm border mb-4">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
          <h6 class="m-0">Salidas – FARMACIA INTERNA (modalidad: consumo)</h6>
          @php
            $paramsConsumo = array_filter([
              'from' => $from,
              'to' => $to,
              'destino_id' => $destino_id,
              'mostrar_insumos' => ($mostrar_insumos ?? false) ? 1 : null,
            ], fn($v) => !is_null($v));
          @endphp
          <a href="{{ route('reportes.export.pdf.consumo', $paramsConsumo) }}" class="btn btn-sm btn-outline-danger">
            <i class="fa fa-file-pdf me-1"></i> Exportar PDF
          </a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th>Servicios Médicos</th>
                <th>Medicamentos entregados (blíster)</th>
                @if(($mostrar_insumos ?? false))
                <th>Insumos entregados (unidades)</th>
                @endif
                <th>Beneficiarios</th>
                <th>F</th>
                <th>M</th>
                <th>EST</th>
                <th>TRAB</th>
                <th>COM</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              @forelse(($interno ?? []) as $row)
                <tr>
                  <td>{{ $row['destino'] }}</td>
                  <td><span class="badge bg-success">{{ $row['meds_entregados'] }}</span></td>
                  @if(($mostrar_insumos ?? false))
                  <td><span class="badge bg-info text-dark">{{ $row['insumos_entregados'] }}</span></td>
                  @endif
                  <td>{{ $row['beneficiarios'] }}</td>
                  <td>{{ $row['F'] }}</td>
                  <td>{{ $row['M'] }}</td>
                  <td>{{ $row['EST'] }}</td>
                  <td>{{ $row['TRAB'] }}</td>
                  <td>{{ $row['COM'] }}</td>
                  <td>{{ $row['total'] }}</td>
                </tr>
              @empty
                <tr><td colspan="{{ ($mostrar_insumos ?? false) ? 10 : 9 }}" class="text-center text-muted">Sin consumos en el rango.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="small text-muted">Nota: “Medicamentos entregados” excluye insumos; Beneficiarios = conteo de movimientos (no personas únicas).</div>
      </div>
    @endif
  @endif
</div>
@endsection

@push('scripts')
<script>
  // Ajuste rápido: cuando se selecciona periodo, rellena fechas desde/hasta en el cliente
  document.addEventListener('DOMContentLoaded', function() {
    const periodoSel = document.getElementById('periodo');
    const desde = document.querySelector('input[name="from"]');
    const hasta = document.querySelector('input[name="to"]');
    function applyPeriodo() {
      const v = periodoSel.value;
      if (!v) return;
      const now = new Date();
      const y = now.getFullYear(); const m = now.getMonth();
      let start = new Date(y, m, 1);
      let end = new Date(y, m+1, 0);
      if (v === 'trimestral') {
        start = new Date(y, m-2, 1);
        end = new Date(y, m+1, 0);
      } else if (v === 'semestral') {
        start = new Date(y, m-5, 1);
        end = new Date(y, m+1, 0);
      } else if (v === 'anual') {
        start = new Date(y, m-11, 1);
        end = new Date(y, m+1, 0);
      }
      const fmt = (d) => d.toISOString().slice(0,10);
      desde.value = fmt(start);
      hasta.value = fmt(end);
    }
    periodoSel.addEventListener('change', applyPeriodo);
    // filtrar subcategorías por categoría seleccionada
    const catSel = document.getElementById('categoria_id');
    const subSel = document.getElementById('subcategoria_id');
    function filterSubcats(){
      const cat = catSel.value || '';
      Array.from(subSel.options).forEach(opt => {
        const ok = !opt.value || !cat || String(opt.dataset.cat || '') === String(cat);
        opt.style.display = ok ? '' : 'none';
        if (!ok && opt.selected) { opt.selected = false; }
      });
    }
    catSel.addEventListener('change', filterSubcats);
    filterSubcats();

    const modeRadios = document.querySelectorAll('input[name="modalidad_reporte"]');
    const insumosWrapper = document.getElementById('mostrar-insumos-wrapper');
    function syncModeControls() {
      const selected = document.querySelector('input[name="modalidad_reporte"]:checked')?.value || 'inventario';
      if (insumosWrapper) {
        insumosWrapper.style.display = selected === 'consumo' ? '' : 'none';
      }
    }
    modeRadios.forEach(r => r.addEventListener('change', syncModeControls));
    syncModeControls();
  });
</script>
@endpush

@push('styles')
<style>
.report-mode-grid .report-mode-card {
  display: flex;
  gap: 1rem;
  align-items: center;
  border: 1px solid rgba(255, 138, 0, 0.15);
  border-radius: 16px;
  padding: 1rem 1.15rem;
  background: linear-gradient(120deg, rgba(255, 250, 245, 0.95), rgba(255, 229, 204, 0.7));
  cursor: pointer;
  transition: all 0.2s ease;
  height: 100%;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.6);
}

.btn-check:checked + .report-mode-card {
  border-color: #ff8a00;
  background: linear-gradient(120deg, #ff8a00, #f97316);
  color: #fff;
  box-shadow: 0 10px 25px rgba(249, 115, 22, 0.35);
}

.btn-check:checked + .report-mode-card .report-mode-card__icon {
  background: rgba(255,255,255,0.15);
  color: #fff;
}

.btn-check:checked + .report-mode-card p,
.btn-check:checked + .report-mode-card .report-mode-card__meta {
  color: rgba(255,255,255,0.9);
}

.report-mode-card__icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(255, 138, 0, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: #c2410c;
}

.report-mode-card__body h6 {
  margin-bottom: 0.25rem;
  font-weight: 600;
}

.report-mode-card__body p {
  margin-bottom: 0.4rem;
  color: #6b7280;
  font-size: 0.9rem;
}

.report-mode-card__meta {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.8rem;
  font-weight: 600;
  color: #c2410c;
}

.report-placeholder {
  border: 1px dashed rgba(148, 163, 184, 0.6);
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  gap: 1rem;
  align-items: center;
  background: #fff;
  box-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
}

.report-placeholder__icon {
  width: 72px;
  height: 72px;
  border-radius: 16px;
  background: linear-gradient(135deg, rgba(255, 138, 0, 0.15), rgba(255, 138, 0, 0.05));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  color: #ff8a00;
}
</style>
@endpush
