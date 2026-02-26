<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Inventario a la fecha</title>
  <style>
    @page { margin: 40px 30px 60px 30px; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
    h2 { margin: 0 0 6px 0; }
    .small { color: #666; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 6px 8px; }
    thead th { background: #f5f5f5; font-weight: 700; text-transform: uppercase; font-size: 12.5px; letter-spacing: .2px; }
    .text-right { text-align: right; }
    .pdf-header { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; }
    .pdf-header .logo { width: 60px; height: 60px; object-fit: contain; flex-shrink: 0; }
    .pdf-header .title-block { flex: 1; text-align: center; text-transform: uppercase; letter-spacing: .5px; color: #111; font-size: 11px; }
    .pdf-header .title-block .org { font-weight: 700; }
    .pdf-header .title-block .system { font-size: 10px; letter-spacing: .4px; color: #4b5563; }
    .pdf-header .title-block .timestamp { font-size: 9px; color: #6b7280; text-transform: none; }
  </style>
</head>
<body>
  <div class="pdf-header">
    <img src="{{ public_path('logouptag.png') }}" alt="Logo Servicios Médicos" class="logo">
    <div class="title-block">
      <div class="org">SERVICIOS MÉDICOS · UPTAG</div>
      <div class="system">Sistema de Inventario Médico</div>
      <div class="timestamp">Generado: {{ \Carbon\Carbon::now(config('app.timezone'))->format('d/m/Y H:i') }}</div>
    </div>
  </div>
  <h2>INVENTARIO DE MEDICAMENTOS E INSUMOS</h2>
  <div class="small">Fecha de corte: {{ \Carbon\Carbon::parse($cutoff)->format('d/m/Y') }}</div>
  <br>
  <table>
    <thead>
      <tr>
        <th>DESCRIPCIÓN</th>
        <th>PRESENTACIÓN</th>
        <th>UM</th>
        @foreach($destinos as $d)
          <th>{{ mb_strtoupper($d['nombre'], 'UTF-8') }}</th>
        @endforeach
        <th>DEPÓSITO/CENTRAL</th>
        <th>TOTAL</th>
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

  <script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->get_font('DejaVu Sans', 'normal');
        $text = utf8_decode('Sistema de Inventario Médico · Página {PAGE_NUM} de {PAGE_COUNT}');
        $pdf->page_text(40, $pdf->get_height() - 40, $text, $font, 9);
    }
  </script>
</body>
</html>
