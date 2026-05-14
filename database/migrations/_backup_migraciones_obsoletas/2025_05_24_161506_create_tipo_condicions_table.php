<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('tipos_condicion', function (Blueprint $table) {
            $table->id(); // Clave primaria

            $table->string('nombre')->unique(); // Ej: "Pintura en Altura", "Manejo de Sustancias Peligrosas". Único.
            $table->text('descripcion')->nullable(); // Descripción opcional de la condición
            $table->boolean('is_active')->default(true); // Para activar/desactivar esta opción en los listados

            $table->timestamps(); // created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_condicion');
    }
};