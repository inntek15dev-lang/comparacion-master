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
        Schema::create('embarcacion_asignaciones', function (Blueprint $table) {
            $table->id();
            
            // --- INICIO DE LA SINTAXIS EXPLÍCITA Y SEGURA ---
            
            // Columnas para las claves foráneas
            $table->unsignedBigInteger('embarcacion_id');
            $table->unsignedBigInteger('unidad_organizacional_mandante_id');
            
            // Definición manual de las claves foráneas con nombres cortos y explícitos
            $table->foreign('embarcacion_id', 'emb_asig_emb_id_foreign')
                  ->references('id')->on('embarcaciones')->onDelete('cascade');
            
            $table->foreign('unidad_organizacional_mandante_id', 'emb_asig_uo_id_foreign')
                  ->references('id')->on('unidades_organizacionales_mandante')->onDelete('cascade');
            
            // --- FIN DE LA SINTAXIS EXPLÍCITA ---

            $table->date('fecha_asignacion');
            $table->date('fecha_desasignacion')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('motivo_desasignacion')->nullable();

            $table->timestamps();

            // Constraint de unicidad con nombre corto
            $table->unique(
                ['embarcacion_id', 'unidad_organizacional_mandante_id', 'is_active'], 
                'emb_asig_unique_active'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('embarcacion_asignaciones');
    }
};