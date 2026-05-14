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
        Schema::create('documento_configuraciones_criticidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mandante_id')->constrained('mandantes')->onDelete('cascade');
            $table->foreignId('nombre_documento_id')->constrained('nombre_documentos')->onDelete('cascade');

            $table->boolean('afecta_cumplimiento')->default(false)->comment('Afecta el % de cumplimiento del contratista/activo.');
            $table->boolean('restringe_acceso')->default(false)->comment('Impide el acceso si el documento no está aprobado y vigente.');
            $table->boolean('es_perseguidor')->default(false)->comment('Genera notificaciones persistentes hasta su carga.');
            
            $table->timestamps();

            // =================================================================================
            // INICIO DE LA CORRECCIÓN
            // Le damos un nombre explícito y más corto al índice unique.
            // =================================================================================
            $table->unique(['mandante_id', 'nombre_documento_id'], 'doc_criticidad_mandante_doc_unique');
            // =================================================================================
            // FIN DE LA CORRECCIÓN
            // =================================================================================
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documento_configuraciones_criticidad');
    }
};