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
        Schema::create('embarcaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contratista_id')->constrained('contratistas')->onDelete('cascade');

            $table->string('matricula_letras', 10);
            $table->string('matricula_numeros', 10);

            $table->year('ano_fabricacion');

            $table->foreignId('tipo_embarcacion_id')->nullable()->constrained('tipos_embarcacion')->onDelete('set null');
            
            // Reutilizando la tabla de tenencias de vehículo
            $table->foreignId('tenencia_vehiculo_id')->nullable()->constrained('tenencias_vehiculo')->onDelete('set null');

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Asegurar que la matrícula sea única por contratista
            $table->unique(['contratista_id', 'matricula_letras', 'matricula_numeros'], 'embarcacion_matricula_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('embarcaciones');
    }
};