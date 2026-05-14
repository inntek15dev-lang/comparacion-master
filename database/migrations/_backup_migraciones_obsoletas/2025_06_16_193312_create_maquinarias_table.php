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
        Schema::create('maquinarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contratista_id')->constrained('contratistas')->onDelete('cascade');

            // Campo genérico para Patente o Código Interno
            $table->string('identificador_letras', 20);
            $table->string('identificador_numeros', 20);

            $table->year('ano_fabricacion');

            // Relaciones con tablas existentes (algunas reutilizadas)
            $table->foreignId('marca_vehiculo_id')->nullable()->constrained('marcas_vehiculo')->onDelete('set null');
            $table->foreignId('tipo_maquinaria_id')->nullable()->constrained('tipos_maquinaria')->onDelete('set null');
            $table->foreignId('tenencia_vehiculo_id')->nullable()->constrained('tenencias_vehiculo')->onDelete('set null');

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unicidad del identificador por contratista
            $table->unique(['contratista_id', 'identificador_letras', 'identificador_numeros'], 'maquinaria_identificador_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maquinarias');
    }
};