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
        Schema::table('embarcacion_asignaciones', function (Blueprint $table) {
            $table->foreign(['embarcacion_id'], 'emb_asig_emb_id_foreign')->references(['id'])->on('embarcaciones')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['unidad_organizacional_mandante_id'], 'emb_asig_uo_id_foreign')->references(['id'])->on('unidades_organizacionales_mandante')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['dependencia_id'])->references(['id'])->on('dependencias')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('embarcacion_asignaciones', function (Blueprint $table) {
            $table->dropForeign('emb_asig_emb_id_foreign');
            $table->dropForeign('emb_asig_uo_id_foreign');
            $table->dropForeign('embarcacion_asignaciones_dependencia_id_foreign');
        });
    }
};
