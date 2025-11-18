<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('destinos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 40)->unique()->comment('slug corto: principal, aci, agro, odontologia');
            $table->string('nombre', 120)->comment('Nombre legible');
            $table->boolean('activo')->default(true);
            $table->string('tipo', 40)->nullable()->comment('clasificación opcional');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('destinos');
    }
};
