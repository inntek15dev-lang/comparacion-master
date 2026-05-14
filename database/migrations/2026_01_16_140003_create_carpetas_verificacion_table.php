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
        Schema::create('carpetas_verificacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contratista_unidad_organizacional_id')
                  ->constrained('contratista_unidad_organizacional', 'id')
                  ->name('fk_carpetas_vinculacion')
                  ->onDelete('cascade');
            $table->integer('anio');
            $table->integer('mes');
            $table->string('estado')->default('CARGADO'); // O PENDIENTE, etc.
            $table->timestamps();
            
            $table->unique(['contratista_unidad_organizacional_id', 'anio', 'mes'], 'unique_carpeta_periodo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carpetas_verificacion');
    }
};
