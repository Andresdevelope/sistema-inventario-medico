<?php

namespace Tests\Feature;

use App\Services\InventarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class InventarioVencidosPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_no_permite_distribucion_con_lotes_vencidos_si_politica_esta_activa(): void
    {
        config()->set('inventario.bloquear_vencidos_distribucion', true);

        $categoriaId = DB::table('categorias')->insertGetId([
            'nombre' => 'Cat Vencidos Dist',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $subcategoriaId = DB::table('subcategorias')->insertGetId([
            'nombre' => 'Sub Vencidos Dist',
            'categoria_id' => $categoriaId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productoId = DB::table('productos')->insertGetId([
            'nombre' => 'Dexametasona Test',
            'codigo' => 'DEX-TEST-001',
            'descripcion' => 'Medicamento de prueba',
            'categoria_id' => $categoriaId,
            'subcategoria_id' => $subcategoriaId,
            'presentacion' => 'Blíster',
            'unidad_medida' => 'Blíster',
            'tipo_producto' => 'medicamento',
            'categoria_inventario' => 'medicamento',
            'stock' => 8,
            'stock_minimo' => 1,
            'proveedor_id' => 1,
            'fecha_ingreso' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventarios')->insert([
            'producto_id' => $productoId,
            'lote' => 'LOT-DEX-1',
            'cantidad' => 8,
            'fecha_vencimiento' => now()->subDays(2)->toDateString(),
            'um_operativa' => 'blister',
            'contenido_por_blister' => 10,
            'stock_minimo' => 1,
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $destinoId = DB::table('destinos')->insertGetId([
            'codigo' => 'MED-PRUEBA',
            'nombre' => 'Destino Prueba',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(InventarioService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock insuficiente para distribución con lotes vigentes');

        $service->procesarMovimiento([
            'producto_id' => $productoId,
            'tipo' => 'egreso',
            'modalidad' => 'distribucion',
            'destino_id' => $destinoId,
            'cantidad' => 1,
            'fecha' => now()->toDateString(),
            'motivo' => 'Prueba de política',
            'usuario_id' => null,
        ]);
    }

    public function test_permite_ingreso_con_lote_nuevo_aun_si_existe_lote_vencido(): void
    {
        $categoriaId = DB::table('categorias')->insertGetId([
            'nombre' => 'Cat Vencidos Ingreso',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $subcategoriaId = DB::table('subcategorias')->insertGetId([
            'nombre' => 'Sub Vencidos Ingreso',
            'categoria_id' => $categoriaId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productoId = DB::table('productos')->insertGetId([
            'nombre' => 'Ibuprofeno Test',
            'codigo' => 'IBU-TEST-001',
            'descripcion' => 'Medicamento de prueba',
            'categoria_id' => $categoriaId,
            'subcategoria_id' => $subcategoriaId,
            'presentacion' => 'Blíster',
            'unidad_medida' => 'Blíster',
            'tipo_producto' => 'medicamento',
            'categoria_inventario' => 'medicamento',
            'stock' => 10,
            'stock_minimo' => 1,
            'proveedor_id' => 1,
            'fecha_ingreso' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventarios')->insert([
            'producto_id' => $productoId,
            'lote' => 'LOT-IBU-OLD',
            'cantidad' => 10,
            'fecha_vencimiento' => now()->subDays(1)->toDateString(),
            'um_operativa' => 'blister',
            'contenido_por_blister' => 10,
            'stock_minimo' => 1,
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(InventarioService::class);
        $service->procesarMovimiento([
            'producto_id' => $productoId,
            'tipo' => 'ingreso',
            'cantidad' => 1,
            'fecha' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(120)->toDateString(),
            'lote' => 'LOT-IBU-NEW',
            'contenido_por_blister' => 10,
            'motivo' => 'Prueba bloqueo global por vencidos',
            'usuario_id' => null,
        ]);

        $nuevoLote = DB::table('inventarios')
            ->where('producto_id', $productoId)
            ->where('lote', 'LOT-IBU-NEW')
            ->first();

        $this->assertNotNull($nuevoLote);
        $this->assertSame(1, (int) $nuevoLote->cantidad);
    }

    public function test_no_permite_consumo_fefo_con_lotes_vencidos(): void
    {
        $categoriaId = DB::table('categorias')->insertGetId([
            'nombre' => 'Cat Vencidos Consumo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $subcategoriaId = DB::table('subcategorias')->insertGetId([
            'nombre' => 'Sub Vencidos Consumo',
            'categoria_id' => $categoriaId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productoId = DB::table('productos')->insertGetId([
            'nombre' => 'Dexametasona Consumo Test',
            'codigo' => 'DEX-CONS-001',
            'descripcion' => 'Medicamento de prueba',
            'categoria_id' => $categoriaId,
            'subcategoria_id' => $subcategoriaId,
            'presentacion' => 'Blíster',
            'unidad_medida' => 'Blíster',
            'tipo_producto' => 'medicamento',
            'categoria_inventario' => 'medicamento',
            'stock' => 8,
            'stock_minimo' => 1,
            'proveedor_id' => 1,
            'fecha_ingreso' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventarios')->insert([
            'producto_id' => $productoId,
            'lote' => 'LOT-DEX-CONS-OLD',
            'cantidad' => 8,
            'fecha_vencimiento' => now()->subDays(3)->toDateString(),
            'um_operativa' => 'blister',
            'contenido_por_blister' => 10,
            'stock_minimo' => 1,
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $destinoId = DB::table('destinos')->insertGetId([
            'codigo' => 'CONS-PRUEBA',
            'nombre' => 'Destino Consumo Prueba',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(InventarioService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('lotes vigentes para consumo');

        $service->procesarMovimiento([
            'producto_id' => $productoId,
            'tipo' => 'egreso',
            'modalidad' => 'consumo',
            'destino_id' => $destinoId,
            'tipo_identificacion' => 'estudiante',
            'sexo' => 'F',
            'cantidad' => 1,
            'fecha' => now()->toDateString(),
            'motivo' => 'Prueba bloqueo FEFO consumo vencido',
            'usuario_id' => null,
        ]);
    }
}
