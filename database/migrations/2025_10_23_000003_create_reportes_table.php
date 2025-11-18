<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('reportes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipo', 50)->comment('kardex, vencimiento, consumo, etc');
            $table->json('para  metros')->nullable()->comment('configuración o filtros usados');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();
            $table->foreign('usuario_id')->references('id')->on('users');
        });
    }
    public function down() {
        Schema::dropIfExists('reportes');
    }
};
