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
            $table->bigIncrements('id');
            $table->unsignedBigInteger('mandante_id');
            $table->unsignedBigInteger('nombre_documento_id')->index('documento_configuraciones_criticidad_nombre_documento_id_foreign');
            $table->boolean('afecta_cumplimiento')->default(false)->comment('Afecta el % de cumplimiento del contratista/activo.');
            $table->boolean('restringe_acceso')->default(false)->comment('Impide el acceso si el documento no está aprobado y vigente.');
            $table->boolean('es_perseguidor')->default(false)->comment('Genera notificaciones persistentes hasta su carga.');
            $table->timestamps();

            $table->unique(['mandante_id', 'nombre_documento_id'], 'doc_criticidad_mandante_doc_unique');
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
