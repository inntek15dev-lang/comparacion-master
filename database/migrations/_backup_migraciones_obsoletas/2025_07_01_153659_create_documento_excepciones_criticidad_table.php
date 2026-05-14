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
        Schema::create('documento_excepciones_criticidad', function (Blueprint $table) {
            $table->id();
            
            // Relación con el documento y el mandante
            $table->foreignId('mandante_id')->constrained('mandantes')->onDelete('cascade');
            $table->foreignId('nombre_documento_id')->constrained('nombre_documentos')->onDelete('cascade');

            // =================================================================================
            // INICIO DE LA CORRECCIÓN
            // Descomponemos el método morphs() en sus partes para poder nombrar el índice.
            // =================================================================================
            $table->unsignedBigInteger('excepcionable_id');
            $table->string('excepcionable_type');
            // Creamos el índice manualmente con un nombre más corto.
            $table->index(['excepcionable_type', 'excepcionable_id'], 'doc_excepcion_criticidad_morph_idx');
            // =================================================================================
            // FIN DE LA CORRECCIÓN
            // =================================================================================

            // Valores de anulación (override). Null significa "no aplicar excepción para este campo".
            $table->boolean('afecta_cumplimiento_override')->nullable();
            $table->boolean('restringe_acceso_override')->nullable();
            $table->boolean('es_perseguidor_override')->nullable();

            // Vigencia de la excepción
            $table->date('valido_hasta');
            
            $table->timestamps();

            // También le daremos un nombre corto al otro índice para ser consistentes.
            $table->index(['mandante_id', 'nombre_documento_id', 'excepcionable_id', 'excepcionable_type'], 'doc_excepcion_criticidad_unica_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documento_excepciones_criticidad');
    }
};