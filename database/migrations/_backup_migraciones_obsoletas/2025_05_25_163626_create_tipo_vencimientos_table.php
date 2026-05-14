<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_vencimiento', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique(); // Ej: "Fecha Fija en Documento", "X días desde emisión", "Indefinido"
            $table->text('descripcion')->nullable(); // Explicación de cómo se interpreta este tipo de vencimiento
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_vencimiento');
    }
};