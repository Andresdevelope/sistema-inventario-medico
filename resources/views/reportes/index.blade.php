
@extends('layouts.dashboard')
@section('content')
<div class="container mt-4">
  <h2 class="mb-3">Reporte de Consumo de Medicamentos (MVP)</h2>
  <form method="GET" class="row g-2 align-items-end mb-4">
    <div class="col-md-3">
      <label class="form-label">Desde</label>
      <input type="date" name="from" class="form-control" value="{{ $from ?? '' }}" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Hasta</label>
      <input type="date" name="to" class="form-control" value="{{ $to ?? '' }}" required>
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
    <div class="col-md-3 d-flex gap-2">
      <button class="btn btn-primary flex-grow-1"><i class="fa fa-chart-bar me-1"></i> Generar</button>
      @if($resumen && $detalle)
        <a href="{{ route('reportes.export.csv',['from'=>$from,'to'=>$to,'destino_id'=>$destino_id]) }}" class="btn btn-outline-secondary" title="Exportar CSV"><i class="fa fa-file-csv"></i></a>
      @endif
    </div>
  </form>

  @if(session('error'))
    <div class="alert alert-danger mb-3">{{ session('error') }}</div>
  @endif

  @if(!$resumen)
    <div class="alert alert-info">Seleccione un rango de fechas y opcionalmente un destino para generar el reporte.</div>
  @else
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
  @endif
</div>
@endsection
