<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('exclusiones_verificacion_periodo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('mandante_id');
            $table->unsignedBigInteger('contratista_unidad_organizacional_id');
            $table->date('periodo')->comment('Primer día del mes: 2026-03-01');
            $table->unsignedBigInteger('excluido_por_user_id')->nullable();
            $table->timestamps();

            $table->unique(
            ['mandante_id', 'contratista_unidad_organizacional_id', 'periodo'],
                'idx_excl_verif_unique'
            );

            $table->foreign('mandante_id')->references('id')->on('mandantes')->onDelete('cascade');
            $table->foreign('contratista_unidad_organizacional_id', 'fk_excl_cuo')
                ->references('id')->on('contratista_unidad_organizacional')->onDelete('cascade');
            $table->foreign('excluido_por_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exclusiones_verificacion_periodo');
    }
};
