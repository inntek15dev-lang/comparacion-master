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
        Schema::create('formatos_documento_muestra', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique(); // Nombre descriptivo del formato, ej: "Formato Contrato Indefinido V.2"
            $table->text('descripcion')->nullable(); // Descripción adicional
            $table->string('ruta_archivo'); // Ruta donde se guarda el archivo en el storage, ej: "formatos_muestra/nombre_unico_archivo.pdf"
            $table->string('nombre_archivo_original'); // Nombre original del archivo subido por el usuario
            $table->boolean('is_active')->default(true); // Para activar/desactivar el formato
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
        Schema::dropIfExists('formatos_documento_muestra');
    }
};