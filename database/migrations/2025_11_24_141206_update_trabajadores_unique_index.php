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
        Schema::table('trabajadores', function (Blueprint $table) {
            // Eliminar el índice único existente en 'rut'
            $table->dropUnique(['rut']);
            
            // Crear el nuevo índice único compuesto (rut + contratista_id)
            $table->unique(['rut', 'contratista_id'], 'trabajadores_rut_contratista_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajadores', function (Blueprint $table) {
            // Revertir cambios: eliminar el índice compuesto
            $table->dropUnique('trabajadores_rut_contratista_unique');
            
            // Restaurar el índice único global en 'rut'
            $table->unique('rut');
        });
    }
};
