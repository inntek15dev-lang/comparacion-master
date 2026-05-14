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
        Schema::create('documentos_verificacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carpeta_verificacion_id')->constrained('carpetas_verificacion')->onDelete('cascade');
            $table->foreignId('requisito_verificacion_id')->constrained('requisitos_verificacion')->onDelete('cascade');
            $table->string('path');
            $table->string('nombre_original')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos_verificacion');
    }
};
