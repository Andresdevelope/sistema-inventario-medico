<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('movimientos', function (Blueprint $table) {
            // Modalidad del egreso: distribucion (Central -> Destino) o consumo (entrega real a beneficiario)
            $table->string('modalidad', 20)->nullable()->after('tipo');
            // Datos mínimos del beneficiario para reportes de consumo
            $table->string('tipo_identificacion', 20)->nullable()->after('modalidad');
            $table->string('sexo', 10)->nullable()->after('tipo_identificacion');

            // Índices útiles para reportes
            $table->index(['modalidad','tipo','fecha']);
            $table->index(['tipo_identificacion','sexo']);
        });
    }

    public function down(): void {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropIndex(['modalidad','tipo','fecha']);
            $table->dropIndex(['tipo_identificacion','sexo']);
            $table->dropColumn('sexo');
            $table->dropColumn('tipo_identificacion');
            $table->dropColumn('modalidad');
        });
    }
};
