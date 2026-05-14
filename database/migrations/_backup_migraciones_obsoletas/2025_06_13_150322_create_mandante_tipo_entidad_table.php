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
        Schema::create('mandante_tipo_entidad', function (Blueprint $table) {
            // Clave foránea para la tabla mandantes
            $table->foreignId('mandante_id')
                  ->constrained('mandantes')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');

            // Clave foránea para la tabla tipos_entidad_controlable
            $table->foreignId('tipo_entidad_controlable_id')
                  ->constrained('tipos_entidad_controlable')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');

            // Definir la clave primaria compuesta
            $table->primary(['mandante_id', 'tipo_entidad_controlable_id']);

            // No añadimos timestamps aquí
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mandante_tipo_entidad');
    }
};