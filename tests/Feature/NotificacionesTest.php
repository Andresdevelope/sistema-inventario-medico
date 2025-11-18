<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Producto;
use App\Models\Movimiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NotificacionesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ejecutar migraciones
        $this->artisan('migrate');
    }

    public function test_notificaciones_movimientos_flujo_basico(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Crear dependencias mínimas
        $categoriaId = DB::table('categorias')->insertGetId(['nombre'=>'Analgesicos','created_at'=>now(),'updated_at'=>now()]);
        $subcategoriaId = DB::table('subcategorias')->insertGetId(['nombre'=>'Dolor','categoria_id'=>$categoriaId,'created_at'=>now(),'updated_at'=>now()]);
        $producto = Producto::create([
            'nombre' => 'Paracetamol',
            'descripcion' => 'Analgésico',
            'categoria_id' => $categoriaId,
            'subcategoria_id' => $subcategoriaId,
            'presentacion' => 'Tabletas',
            'unidad_medida' => 'mg',
            'proveedor_id' => 1,
            'stock' => 0,
            'stock_minimo' => 5,
            'fecha_ingreso' => Carbon::now()->format('Y-m-d'),
        ]);

        Movimiento::create([
            'producto_id' => $producto->id,
            'tipo' => 'ingreso',
            'salida' => 'general',
            'cantidad' => 20,
            'motivo' => 'Compra inicial',
            'fecha' => Carbon::now()->format('Y-m-d'),
            'usuario_id' => $user->id,
        ]);
        Movimiento::create([
            'producto_id' => $producto->id,
            'tipo' => 'egreso',
            'salida' => 'general',
            'cantidad' => 5,
            'motivo' => 'Consumo',
            'fecha' => Carbon::now()->format('Y-m-d'),
            'usuario_id' => $user->id,
        ]);

        $resp = $this->getJson(route('notificaciones.movimientos'));
        $resp->assertStatus(200);
        $data = $resp->json();
        $this->assertEquals(2, $data['unread']);
        $this->assertCount(2, $data['items']);

        $mark = $this->postJson(route('notificaciones.movimientos.leer'));
        $mark->assertStatus(200);

        $resp2 = $this->getJson(route('notificaciones.movimientos'));
        $resp2->assertStatus(200);
        $data2 = $resp2->json();
        $this->assertEquals(0, $data2['unread']);
    }
}
