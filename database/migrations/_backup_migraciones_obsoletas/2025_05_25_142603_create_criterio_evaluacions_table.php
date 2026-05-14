<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criterios_evaluacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_criterio')->unique(); // Ej: "Vigencia del Documento"
            $table->text('descripcion_criterio')->nullable(); // Explicación de qué se revisa
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criterios_evaluacion');
    }
};