<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Inventario a la fecha</title>
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
    h2 { margin: 0 0 6px 0; }
    .small { color: #666; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 6px 8px; }
    thead th { background: #f5f5f5; }
    .text-right { text-align: right; }
  </style>
</head>
<body>
  <h2>INVENTARIO DE MEDICAMENTOS E INSUMOS</h2>
  <div class="small">Fecha de corte: {{ \Carbon\Carbon::parse($cutoff)->format('d/m/Y') }}</div>
  <br>
  <table>
    <thead>
      <tr>
        <th>Descripción</th>
        <th>Presentación</th>
        <th>UM</th>
        @foreach($destinos as $d)
          <th>{{ $d['nombre'] }}</th>
        @endforeach
        <th>Depósito/Central</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $row)
        <tr>
          <td>{{ $row['descripcion'] }}</td>
          <td>{{ $row['presentacion'] }}</td>
          <td>{{ $row['um'] }}</td>
          @foreach($destinos as $d)
            @php $key = $d['codigo'] ?: $d['nombre']; @endphp
            <td class="text-right">{{ $row['destinos'][$key] ?? 0 }}</td>
          @endforeach
          <td class="text-right">{{ $row['central'] }}</td>
          <td class="text-right">{{ $row['total'] }}</td>
        </tr>
      @empty
        <tr><td colspan="{{ 3 + count($destinos) + 2 }}" class="text-right">Sin existencias a la fecha de corte</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
