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
        // Nombre de la tabla: tipos_condicion_personal (pluralizando 'tipo' y 'condicion')
        Schema::create('tipos_condicion_personal', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique(); // Ej: "Mayor de 60 años", "Discapacidad Visual", "Daltonismo"
            $table->text('descripcion')->nullable();
            $table->boolean('requires_special_document')->default(false); // Indicador si esta condición suele implicar un documento específico
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_condicion_personal');
    }
};