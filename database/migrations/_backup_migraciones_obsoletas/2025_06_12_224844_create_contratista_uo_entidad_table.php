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
        Schema::create('contratista_uo_entidad', function (Blueprint $table) {
            // Esta FK apuntará al 'id' de la tabla 'contratista_unidad_organizacional'
            $table->foreignId('contratista_unidad_organizacional_id')
                  ->constrained('contratista_unidad_organizacional') // Tabla con 'id' PK
                  ->onDelete('cascade')
                  ->references('id')->on('contratista_unidad_organizacional') // Especificar columna y tabla
                  ->name('fk_cuoe_contr_uo_id'); // Nombre corto de constraint
            
            $table->foreignId('tipo_entidad_controlable_id')
                  ->constrained('tipos_entidad_controlable')
                  ->onDelete('cascade')
                  ->references('id')->on('tipos_entidad_controlable') // Especificar columna y tabla
                  ->name('fk_cuoe_tec_id'); // Nombre corto de constraint

            // Clave primaria compuesta para esta tabla pivote
            $table->primary([
                'contratista_unidad_organizacional_id', 
                'tipo_entidad_controlable_id'
            ], 'pk_contratista_uo_entidad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratista_uo_entidad');
    }
};