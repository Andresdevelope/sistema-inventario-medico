<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inventarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id')->comment('FK a productos');
            $table->string('lote', 50)->nullable()->comment('Número de lote');
            $table->integer('cantidad')->comment('Cantidad disponible en este lote/registro');
            $table->date('fecha_vencimiento')->nullable()->comment('Fecha de vencimiento del lote');
            $table->integer('stock_minimo')->nullable()->comment('Stock mínimo recomendado');
            $table->string('estado', 20)->nullable()->comment('Estado del inventario: activo, agotado, etc');
            $table->timestamps();

            $table->foreign('producto_id')->references('id')->on('productos');
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventarios');
    }
};
