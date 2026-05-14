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
        Schema::create('maquinaria_asignaciones', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('maquinaria_id');
            $table->unsignedBigInteger('unidad_organizacional_mandante_id');
            
            // Definición manual y explícita de las claves foráneas con nombres cortos
            $table->foreign('maquinaria_id', 'maq_asig_maq_id_foreign')
                  ->references('id')->on('maquinarias')->onDelete('cascade');
            
            $table->foreign('unidad_organizacional_mandante_id', 'maq_asig_uo_id_foreign')
                  ->references('id')->on('unidades_organizacionales_mandante')->onDelete('cascade');

            $table->date('fecha_asignacion');
            $table->date('fecha_desasignacion')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('motivo_desasignacion')->nullable();

            $table->timestamps();

            // Constraint de unicidad con nombre corto
            $table->unique(
                ['maquinaria_id', 'unidad_organizacional_mandante_id', 'is_active'], 
                'maq_asig_unique_active'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maquinaria_asignaciones');
    }
};