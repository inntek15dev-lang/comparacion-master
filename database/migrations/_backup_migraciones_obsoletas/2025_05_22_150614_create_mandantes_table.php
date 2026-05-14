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
        Schema::create('mandantes', function (Blueprint $table) {
            $table->id(); // Clave primaria, BigInt Unsigned, Auto-incremento

            $table->string('razon_social'); // Columna para la razón social, VARCHAR, obligatoria por defecto
            $table->string('rut')->unique(); // Columna para el RUT, VARCHAR, obligatoria y única
            $table->string('persona_contacto_nombre'); // Nombre del contacto, VARCHAR, obligatoria
            $table->string('persona_contacto_email'); // Email del contacto, VARCHAR, obligatoria
            $table->string('persona_contacto_telefono'); // Teléfono del contacto, VARCHAR, obligatoria
            $table->boolean('is_active')->default(true); // Columna booleana para estado activo, con valor por defecto 'true'

            $table->timestamps(); // Columnas 'created_at' y 'updated_at'
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('mandantes');
    }
};