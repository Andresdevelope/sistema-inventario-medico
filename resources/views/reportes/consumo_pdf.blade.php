<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>Salidas – Farmacia Interna</title>
	<style>
		body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111827; }
		h2 { margin: 0 0 4px 0; }
		.meta { color: #6b7280; margin-bottom: 12px; }
		table { width: 100%; border-collapse: collapse; }
		th, td { border: 1px solid #d1d5db; padding: 6px 8px; }
		thead th { background: #f8fafc; font-weight: 600; }
		.text-center { text-align: center; }
		.text-right { text-align: right; }
	</style>
</head>
<body>
	<h2>Salidas – FARMACIA INTERNA</h2>
	<div class="meta">
		Modalidad: consumo · Periodo: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }} · Destino: {{ $destino }}
	</div>
	<table>
		<thead>
			<tr>
				<th>Servicios Médicos</th>
				<th>Medicamentos entregados (blíster)</th>
				@if($mostrar_insumos)
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
			@forelse($rows as $row)
				<tr>
					<td>{{ $row['destino'] }}</td>
					<td class="text-right">{{ $row['meds_entregados'] }}</td>
					@if($mostrar_insumos)
					<td class="text-right">{{ $row['insumos_entregados'] }}</td>
					@endif
					<td class="text-right">{{ $row['beneficiarios'] }}</td>
					<td class="text-right">{{ $row['F'] }}</td>
					<td class="text-right">{{ $row['M'] }}</td>
					<td class="text-right">{{ $row['EST'] }}</td>
					<td class="text-right">{{ $row['TRAB'] }}</td>
					<td class="text-right">{{ $row['COM'] }}</td>
					<td class="text-right">{{ $row['total'] }}</td>
				</tr>
			@empty
				<tr>
					<td colspan="{{ $mostrar_insumos ? 10 : 9 }}" class="text-center">Sin consumos registrados para el rango solicitado.</td>
				</tr>
			@endforelse
		</tbody>
	</table>
	<p class="meta" style="margin-top:12px;">Las cifras consideran movimientos tipo egreso con modalidad consumo en el rango indicado.</p>
</body>
</html>
