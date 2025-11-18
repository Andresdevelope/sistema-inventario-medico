<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('codigo')->unique();
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger('categoria_id');
            $table->unsignedBigInteger('subcategoria_id');
            $table->string('presentacion');
            $table->string('unidad_medida');
            $table->string('categoria_inventario', 30)->default('general')->comment('general u odontologia');
            $table->integer('stock')->default(0);
            $table->integer('stock_minimo')->nullable()->default(null)->comment('Stock mínimo recomendado para alerta');
            $table->unsignedBigInteger('proveedor_id');
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            // Relaciones (descomenta si tienes las tablas)
            $table->foreign('categoria_id')->references('id')->on('categorias');
             $table->foreign('subcategoria_id')->references('id')->on('subcategorias');
            // $table->foreign('proveedor_id')->references('id')->on('proveedores');
        });
    }

    public function down()
    {
        Schema::dropIfExists('productos');
    }
};
