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
        Schema::create('regla_documental_cargo_mandante', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('regla_documental_id');
            $table->unsignedBigInteger('cargo_mandante_id');
            // No necesitamos timestamps para esta tabla pivote simple,
            // a menos que quieras rastrear cuándo se hizo una asociación específica.
            // $table->timestamps(); 

            $table->foreign('regla_documental_id')
                  ->references('id')
                  ->on('reglas_documentales')
                  ->onDelete('cascade'); // Si se elimina una regla, se eliminan sus asociaciones de cargo.

            $table->foreign('cargo_mandante_id')
                  ->references('id')
                  ->on('cargos_mandante')
                  ->onDelete('cascade'); // Si se elimina un cargo, se eliminan sus asociaciones a reglas.

            // Para asegurar que no haya duplicados de la misma regla con el mismo cargo.
            $table->unique(['regla_documental_id', 'cargo_mandante_id'], 'regla_cargo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regla_documental_cargo_mandante');
    }
};