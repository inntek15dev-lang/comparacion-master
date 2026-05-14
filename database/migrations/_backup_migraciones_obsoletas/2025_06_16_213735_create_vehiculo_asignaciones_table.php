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
        Schema::create('vehiculo_asignaciones', function (Blueprint $table) {
            $table->id();
            
            // Usando nombres cortos para los constraints para evitar errores de longitud
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->onDelete('cascade');
            $table->foreignId('unidad_organizacional_mandante_id', 'veh_asig_uo_foreign')->constrained('unidades_organizacionales_mandante')->onDelete('cascade');
            
            $table->date('fecha_asignacion');
            $table->date('fecha_desasignacion')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('motivo_desasignacion')->nullable();

            $table->timestamps();

            // Evitar que un vehículo tenga múltiples asignaciones activas a la misma UO
            $table->unique(
                ['vehiculo_id', 'unidad_organizacional_mandante_id', 'is_active'], 
                'veh_asig_unique_active'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculo_asignaciones');
    }
};