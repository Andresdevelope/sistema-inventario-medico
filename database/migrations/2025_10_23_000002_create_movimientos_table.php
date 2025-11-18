<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->string('tipo', 20)->comment('ingreso, egreso, ajuste');
            $table->string('salida', 20)->comment('principal, acinf, agroalimentacion, odontologia');
               $table->unsignedBigInteger('inventario_id')->nullable()->comment('Lote específico del inventario para el movimiento');
            $table->string('entrada', 30)->nullable()->comment('origen de entrada: compra, donacion, transferencia, etc');
            $table->integer('cantidad');
            $table->string('motivo', 100)->nullable()->comment('motivo del movimiento');
            $table->date('fecha');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->text('observaciones')->nullable();
               // Relaciones
               $table->foreign('inventario_id')->references('id')->on('inventarios')->onDelete('set null');
            $table->timestamps();
            $table->foreign('producto_id')->references('id')->on('productos');
            $table->foreign('usuario_id')->references('id')->on('users');
        });
    }
    public function down() {
        Schema::dropIfExists('movimientos');
    }
};
