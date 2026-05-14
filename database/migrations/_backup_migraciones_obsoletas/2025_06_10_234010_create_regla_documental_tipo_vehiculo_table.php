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
        Schema::create('regla_documental_tipo_vehiculo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regla_documental_id')->constrained('reglas_documentales')->onDelete('cascade');
            $table->foreignId('tipo_vehiculo_id')->constrained('tipos_vehiculo')->onDelete('cascade');
            // No necesitamos timestamps para esta tabla pivote simple
            // $table->timestamps(); // Opcional, si quieres saber cuándo se hizo la asociación

            // Para evitar duplicados
            $table->unique(['regla_documental_id', 'tipo_vehiculo_id'], 'regla_tipo_vehiculo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regla_documental_tipo_vehiculo');
    }
};