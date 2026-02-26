<?php

namespace Tests\Feature;

use App\Services\ReportesMovimientosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportesMovimientosServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_salidas_farmacia_interna_filtra_por_mes_y_destino_e_incluye_profesor(): void
    {
        $destinoA = $this->crearDestino('MED-A', 'Medicina A');
        $destinoB = $this->crearDestino('MED-B', 'Medicina B');

        $prodMed = $this->crearProducto('Paracetamol', 'P-001', 'medicamento');
        $prodIns = $this->crearProducto('Guantes', 'I-001', 'insumo');

        // Fuera de rango (no debe contar)
        $this->crearMovimiento($prodMed, 'egreso', 99, '2026-01-25', [
            'modalidad' => 'consumo',
            'destino_id' => $destinoA,
            'tipo_identificacion' => 'estudiante',
            'sexo' => 'F',
        ]);

        // Dentro de rango, destino A
        $this->crearMovimiento($prodMed, 'egreso', 10, '2026-02-10', [
            'modalidad' => 'consumo',
            'destino_id' => $destinoA,
            'tipo_identificacion' => 'profesor',
            'sexo' => 'M',
        ]);
        $this->crearMovimiento($prodIns, 'egreso', 4, '2026-02-12', [
            'modalidad' => 'consumo',
            'destino_id' => $destinoA,
            'tipo_identificacion' => 'trabajador',
            'sexo' => 'F',
        ]);

        // Dentro de rango, destino B (no debe contar por filtro destino)
        $this->crearMovimiento($prodMed, 'egreso', 7, '2026-02-14', [
            'modalidad' => 'consumo',
            'destino_id' => $destinoB,
            'tipo_identificacion' => 'comunidad',
            'sexo' => 'F',
        ]);

        $service = new ReportesMovimientosService();
        $rows = $service->salidasFarmaciaInterna('2026-02-01', '2026-02-28', $destinoA);

        $this->assertCount(1, $rows);
        $row = $rows[0];

        $this->assertSame(10, $row['meds_entregados']);
        $this->assertSame(4, $row['insumos_entregados']);
        $this->assertSame(2, $row['beneficiarios']);
        $this->assertSame(1, $row['F']);
        $this->assertSame(1, $row['M']);
        $this->assertSame(0, $row['EST']);
        $this->assertSame(1, $row['TRAB']);
        $this->assertSame(1, $row['PROF']);
        $this->assertSame(0, $row['COM']);
        $this->assertSame(2, $row['total']);
    }

    public function test_resumen_respeta_filtro_destino_en_totales_y_tops(): void
    {
        $destinoA = $this->crearDestino('MED-A', 'Medicina A');
        $destinoB = $this->crearDestino('MED-B', 'Medicina B');

        $prod1 = $this->crearProducto('Ibuprofeno', 'M-100', 'medicamento');
        $prod2 = $this->crearProducto('Omeprazol', 'M-101', 'medicamento');

        $this->crearMovimiento($prod1, 'egreso', 10, '2026-02-03', ['destino_id' => $destinoA]);
        $this->crearMovimiento($prod2, 'egreso', 5, '2026-02-05', ['destino_id' => $destinoA]);
        $this->crearMovimiento($prod1, 'egreso', 9, '2026-02-08', ['destino_id' => $destinoB]);

        $service = new ReportesMovimientosService();
        $resumen = $service->resumen('2026-02-01', '2026-02-28', $destinoA);

        $this->assertSame(15, $resumen['total_unidades']);
        $this->assertSame(2, $resumen['productos_distintos']);

        $this->assertCount(1, $resumen['top_destinos']);
        $this->assertSame((int)$destinoA, (int)$resumen['top_destinos'][0]['destino_id']);
        $this->assertSame(15, $resumen['top_destinos'][0]['total']);

        $this->assertNotEmpty($resumen['top_medicamentos']);
        $totalTopMedicamentos = collect($resumen['top_medicamentos'])->sum('total');
        $this->assertSame(15, $totalTopMedicamentos);
    }

    public function test_matriz_inventario_por_destino_mantiene_balance_global_y_central(): void
    {
        $destinoA = $this->crearDestino('MED-A', 'Medicina A');
        $prod = $this->crearProducto('Amoxicilina', 'M-200', 'medicamento');

        // saldo global al corte: 10 - (4 + 1 + 1) = 4
        $this->crearMovimiento($prod, 'ingreso', 10, '2026-02-01');
        $this->crearMovimiento($prod, 'egreso', 4, '2026-02-02', ['modalidad' => 'distribucion', 'destino_id' => $destinoA]);
        $this->crearMovimiento($prod, 'egreso', 1, '2026-02-04', ['modalidad' => 'consumo', 'destino_id' => $destinoA]);
        $this->crearMovimiento($prod, 'ajuste_neg', 1, '2026-02-05', ['destino_id' => $destinoA]);

        $service = new ReportesMovimientosService();
        $matriz = $service->inventarioMatrizPorDestino('2026-02-28');

        $this->assertNotEmpty($matriz['rows']);
        $row = collect($matriz['rows'])->firstWhere('producto_id', $prod);
        $this->assertNotNull($row);

        $keyDestino = collect($matriz['destinos'])
            ->firstWhere('id', (int)$destinoA)['codigo'];

        $this->assertSame(2, (int)$row['destinos'][$keyDestino]); // (distribución 4) - (consumo 1 + ajuste_neg 1)
        $this->assertSame(2, (int)$row['central']); // 4 global - 2 destino
        $this->assertSame(4, (int)$row['total']);
    }

    private function crearDestino(string $codigo, string $nombre): int
    {
        return (int) DB::table('destinos')->insertGetId([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearProducto(string $nombre, string $codigo, string $tipo): int
    {
        $categoriaId = DB::table('categorias')->insertGetId([
            'nombre' => $nombre . '-CAT-' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $subcategoriaId = DB::table('subcategorias')->insertGetId([
            'nombre' => $nombre . '-SUB-' . uniqid(),
            'categoria_id' => $categoriaId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('productos')->insertGetId([
            'nombre' => $nombre,
            'codigo' => $codigo,
            'descripcion' => $nombre,
            'categoria_id' => $categoriaId,
            'subcategoria_id' => $subcategoriaId,
            'presentacion' => $tipo === 'medicamento' ? 'Blíster' : 'Unidad',
            'unidad_medida' => $tipo === 'medicamento' ? 'Blíster' : 'Unidad',
            'tipo_producto' => $tipo,
            'categoria_inventario' => $tipo,
            'stock' => 0,
            'stock_minimo' => 5,
            'proveedor_id' => 1,
            'fecha_ingreso' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param array<string,mixed> $extra
     */
    private function crearMovimiento(int $productoId, string $tipo, int $cantidad, string $fecha, array $extra = []): int
    {
        return (int) DB::table('movimientos')->insertGetId(array_merge([
            'producto_id' => $productoId,
            'tipo' => $tipo,
            'modalidad' => null,
            'tipo_identificacion' => null,
            'sexo' => null,
            'salida' => 'general',
            'destino_id' => null,
            'inventario_id' => null,
            'entrada' => null,
            'cantidad' => $cantidad,
            'motivo' => 'test',
            'fecha' => $fecha,
            'usuario_id' => null,
            'observaciones' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $extra));
    }
}
