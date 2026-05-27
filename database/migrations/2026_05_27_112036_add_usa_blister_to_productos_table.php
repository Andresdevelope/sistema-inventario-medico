<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->boolean('usa_blister')->default(false)->after('unidad_medida');
        });

        // Migrar datos existentes: productos con unidad_medida 'mg' o 'mcg' se marcan con usa_blister = true
        DB::table('productos')
            ->whereIn(DB::raw('LOWER(unidad_medida)'), ['mg', 'mcg'])
            ->update(['usa_blister' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('usa_blister');
        });
    }
};

