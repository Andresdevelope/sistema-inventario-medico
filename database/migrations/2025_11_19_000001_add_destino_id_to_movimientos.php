<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('destino_id')->nullable()->after('salida');
            $table->foreign('destino_id')->references('id')->on('destinos')->onDelete('set null');
            $table->index(['destino_id','tipo','fecha']);
        });
    }
    public function down(): void {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropIndex(['destino_id','tipo','fecha']);
            $table->dropForeign(['destino_id']);
            $table->dropColumn('destino_id');
        });
    }
};
