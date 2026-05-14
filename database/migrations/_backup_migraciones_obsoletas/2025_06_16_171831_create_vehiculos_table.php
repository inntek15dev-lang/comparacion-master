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
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contratista_id')->constrained('contratistas')->onDelete('cascade');
            
            $table->string('patente_letras', 4);
            $table->string('patente_numeros', 4);

            $table->year('ano_fabricacion');

            $table->foreignId('marca_vehiculo_id')->nullable()->constrained('marcas_vehiculo')->onDelete('set null');
            $table->foreignId('color_vehiculo_id')->nullable()->constrained('colores_vehiculo')->onDelete('set null');
            $table->foreignId('tipo_vehiculo_id')->nullable()->constrained('tipos_vehiculo')->onDelete('set null');
            $table->foreignId('tenencia_vehiculo_id')->nullable()->constrained('tenencias_vehiculo')->onDelete('set null');
            
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Asegurar que la patente sea única por contratista
            $table->unique(['contratista_id', 'patente_letras', 'patente_numeros'], 'vehiculo_patente_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};