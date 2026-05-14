<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trabajador_vinculaciones', function (Blueprint $table) {
            $table->date('fecha_finiquito')->nullable()->after('fecha_desactivacion')->comment('Fecha legal de término/movimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajador_vinculaciones', function (Blueprint $table) {
            $table->dropColumn('fecha_finiquito');
        });
    }
};
