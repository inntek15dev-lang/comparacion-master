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
        Schema::create('contratista_unidad_organizacional', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('contratista_id');
            $table->unsignedBigInteger('unidad_organizacional_mandante_id');
            $table->unsignedBigInteger('tipo_condicion_id')->nullable();
            $table->unsignedTinyInteger('porcentaje_cumplimiento')->nullable();
            $table->json('estado_acceso')->nullable();

            $table->unique(['contratista_id', 'unidad_organizacional_mandante_id'], 'idx_contr_uo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratista_unidad_organizacional');
    }
};
