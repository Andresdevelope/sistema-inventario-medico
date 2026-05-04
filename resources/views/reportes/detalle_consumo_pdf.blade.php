<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>Detalle de consumo</title>
	<style>
		@page { margin: 40px 30px 60px 30px; }
		body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111827; }
		h2 { margin: 0 0 4px 0; }
		.meta { color: #6b7280; margin-bottom: 12px; }
		table { width: 100%; border-collapse: collapse; }
		th, td { border: 1px solid #d1d5db; padding: 6px 8px; }
		thead th { background: #f8fafc; font-weight: 700; text-transform: uppercase; font-size: 12.5px; letter-spacing: .2px; }
		.text-center { text-align: center; }
		.text-right { text-align: right; }
		.pdf-header { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; }
		.pdf-header .logo { width: 60px; height: 60px; object-fit: contain; flex-shrink: 0; }
		.pdf-header .title-block { flex: 1; text-align: center; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #111; }
		.pdf-header .title-block .org { font-weight: 700; }
		.pdf-header .title-block .system { font-size: 10px; letter-spacing: .4px; color: #4b5563; }
		.pdf-header .title-block .timestamp { font-size: 9px; color: #6b7280; text-transform: none; }
	</style>
</head>
<body>
	<div class="pdf-header">
		<img src="{{ public_path('Logo IUTAG.jpg') }}" alt="Logo Servicios Médicos" class="logo">
		<div class="title-block">
			<div class="org">SERVICIOS MÉDICOS · UPTAG</div>
			<div class="system">Sistema de Inventario Médico</div>
			<div class="timestamp">Generado: {{ \Carbon\Carbon::now(config('app.timezone'))->format('d/m/Y H:i') }}</div>
		</div>
	</div>
	<h2>DETALLE DE CONSUMO</h2>
	<div class="meta">
		Periodo: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }} · Destino: {{ $destino }}
	</div>
	<table>
		<thead>
			<tr>
				<th>CÓDIGO</th>
				<th>MEDICAMENTO</th>
				<th>ENTRADAS</th>
				<th>SALIDAS</th>
				<th>MOVIMIENTOS</th>
				<th>STOCK FINAL</th>
			</tr>
		</thead>
		<tbody>
			@forelse($rows as $row)
				<tr>
					<td>{{ $row['codigo'] }}</td>
					<td>{{ $row['nombre'] }}</td>
					<td class="text-right">{{ $row['entradas'] }}</td>
					<td class="text-right">{{ $row['salidas'] }}</td>
					<td class="text-right">{{ $row['movimientos'] }}</td>
					<td class="text-right">{{ $row['stock_final'] }}</td>
				</tr>
			@empty
				<tr>
					<td colspan="6" class="text-center">Sin datos en el rango solicitado.</td>
				</tr>
			@endforelse
		</tbody>
	</table>
	<p class="meta" style="margin-top:12px;">Las cifras corresponden al detalle consolidado por producto dentro del periodo indicado.</p>

	<script type="text/php">
		if (isset($pdf)) {
       $font = $fontMetrics->get_font("DejaVu Sans", "normal");
        $size = 10;
        $text = "{PAGE_NUM} / {PAGE_COUNT}";
        $width = $fontMetrics->get_text_width($text, $font, $size);
        $x = ($pdf->get_width() - $width) / 2;
        $y = $pdf->get_height() - 35;
        
        $pdf->page_text($x, $y, $text, $font, $size);
    }
	</script>
</body>
</html>
