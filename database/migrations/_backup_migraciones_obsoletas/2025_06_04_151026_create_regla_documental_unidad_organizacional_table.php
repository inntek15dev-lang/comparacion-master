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
        Schema::create('regla_documental_unidad_organizacional', function (Blueprint $table) {
            // Clave foránea para regla_documental_id con nombre explícito corto
            $table->foreignId('regla_documental_id')
                  ->constrained('reglas_documentales')
                  ->onDelete('cascade')
                  ->references('id') 
                  ->name('fk_rduo_regla_doc_id'); // Nombre corto para la FK de regla_documental_id

            // Clave foránea para unidad_organizacional_mandante_id con nombre explícito corto
            $table->foreignId('unidad_organizacional_mandante_id')
                  ->constrained('unidades_organizacionales_mandante')
                  ->onDelete('cascade')
                  ->references('id')
                  ->name('fk_rduo_uo_mand_id'); // Nombre corto para la FK de unidad_organizacional_mandante_id

            // Clave primaria compuesta con nombre explícito corto
            $table->primary(
                ['regla_documental_id', 'unidad_organizacional_mandante_id'],
                'pk_rduo_regla_doc_uo' 
            );
            
            // No timestamps para esta tabla pivote, según tu resumen general.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regla_documental_unidad_organizacional');
    }
};