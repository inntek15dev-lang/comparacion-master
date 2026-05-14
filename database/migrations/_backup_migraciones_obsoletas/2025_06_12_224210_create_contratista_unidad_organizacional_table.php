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
            $table->id();

            $table->foreignId('contratista_id')
                  ->constrained('contratistas')
                  ->onDelete('cascade')
                  ->references('id')->on('contratistas') // Necesario si queremos nombrar la constraint
                  ->name('fk_contr_uo_contratista'); // Nombre corto para la constraint

            $table->foreignId('unidad_organizacional_mandante_id')
                  ->constrained('unidades_organizacionales_mandante')
                  ->onDelete('cascade')
                  ->references('id')->on('unidades_organizacionales_mandante') // Necesario
                  ->name('fk_contr_uo_uo_mandante'); // Nombre corto para la constraint

            $table->foreignId('tipo_condicion_id')
                  ->nullable()
                  ->constrained('tipos_condicion')
                  ->onDelete('set null')
                  ->references('id')->on('tipos_condicion') // Necesario
                  ->name('fk_contr_uo_tipo_condicion'); // Nombre corto para la constraint

            $table->unique(['contratista_id', 'unidad_organizacional_mandante_id'], 'idx_contr_uo_unique'); // Nombre corto para el índice único
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