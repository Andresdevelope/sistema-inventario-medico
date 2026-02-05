<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventarios', function (Blueprint $table) {
            // Unidad operativa del lote: blister|unidad
            $table->string('um_operativa', 20)->nullable()->after('fecha_vencimiento');
            // Contenido por blíster: entero, sólo aplica cuando um_operativa=blister
            $table->unsignedInteger('contenido_por_blister')->nullable()->after('um_operativa');
        });
    }

    public function down(): void
    {
        Schema::table('inventarios', function (Blueprint $table) {
            $table->dropColumn(['um_operativa', 'contenido_por_blister']);
        });
    }
};
