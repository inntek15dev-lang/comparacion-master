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
        Schema::table('maquinaria_asignaciones', function (Blueprint $table) {
            $table->foreign(['maquinaria_id'], 'maq_asig_maq_id_foreign')->references(['id'])->on('maquinarias')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['unidad_organizacional_mandante_id'], 'maq_asig_uo_id_foreign')->references(['id'])->on('unidades_organizacionales_mandante')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['dependencia_id'])->references(['id'])->on('dependencias')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maquinaria_asignaciones', function (Blueprint $table) {
            $table->dropForeign('maq_asig_maq_id_foreign');
            $table->dropForeign('maq_asig_uo_id_foreign');
            $table->dropForeign('maquinaria_asignaciones_dependencia_id_foreign');
        });
    }
};
