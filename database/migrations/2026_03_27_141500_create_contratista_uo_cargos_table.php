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
        Schema::create('contratista_uo_cargos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contratista_uo_id')
                  ->constrained('contratista_unidad_organizacional')
                  ->onDelete('cascade');
            $table->foreignId('cargo_mandante_id')
                  ->constrained('cargos_mandante')
                  ->onDelete('cascade');
            $table->integer('cuota')->nullable();
            $table->timestamps();
            
            $table->unique(['contratista_uo_id', 'cargo_mandante_id'], 'unique_uo_cargo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratista_uo_cargos');
    }
};
