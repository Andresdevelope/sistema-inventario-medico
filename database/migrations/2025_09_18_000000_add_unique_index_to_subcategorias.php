<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Limpiar duplicados previos conservando el menor id
        $duplicates = DB::table('subcategorias')
            ->select('categoria_id','nombre')
            ->groupBy('categoria_id','nombre')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        foreach ($duplicates as $dup) {
            $rows = DB::table('subcategorias')
                ->where('categoria_id', $dup->categoria_id)
                ->where('nombre', $dup->nombre)
                ->orderBy('id')
                ->get();
            $keep = $rows->shift();
            foreach ($rows as $extra) {
                DB::table('subcategorias')->where('id', $extra->id)->delete();
            }
        }
        Schema::table('subcategorias', function(Blueprint $table){
            if (!app()->environment('testing')) { // evitar fallo si ya existe en re-ejecuciones
                try { $table->unique(['categoria_id','nombre'],'subcategorias_categoria_id_nombre_unique'); } catch (Throwable $e) { /* ignorar si ya existe */ }
            } else {
                try { $table->unique(['categoria_id','nombre'],'subcategorias_categoria_id_nombre_unique'); } catch (Throwable $e) { }
            }
        });
    }
    public function down(): void
    {
        Schema::table('subcategorias', function(Blueprint $table){
            try { $table->dropUnique('subcategorias_categoria_id_nombre_unique'); } catch (Throwable $e) { }
        });
    }
};
