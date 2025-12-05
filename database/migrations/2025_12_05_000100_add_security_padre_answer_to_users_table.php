<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la columna `security_padre_answer` a la tabla users si no existe.
     * Esta migración sirve para entornos donde la tabla fue creada antes
     * de añadir esta columna al esquema inicial.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'security_padre_answer')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('security_padre_answer')->after('security_animal_answer');
            });
        }
    }

    /**
     * Reversa el cambio eliminando la columna, si existe.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'security_padre_answer')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('security_padre_answer');
            });
        }
    }
};
