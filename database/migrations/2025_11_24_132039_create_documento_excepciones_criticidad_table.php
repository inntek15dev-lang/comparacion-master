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
            $table->bigIncrements('id');
            $table->unsignedBigInteger('mandante_id');
            $table->unsignedBigInteger('nombre_documento_id')->index('documento_excepciones_criticidad_nombre_documento_id_foreign');
            $table->unsignedBigInteger('excepcionable_id');
            $table->string('excepcionable_type');
            $table->boolean('afecta_cumplimiento_override')->nullable();
            $table->boolean('restringe_acceso_override')->nullable();
            $table->boolean('es_perseguidor_override')->nullable();
            $table->date('valido_hasta');
            $table->text('justificacion')->nullable();
            $table->string('accion_override', 20)->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable()->index('fk_excepciones_criticidad_user');
            $table->timestamps();

            $table->index(['excepcionable_type', 'excepcionable_id'], 'doc_excepcion_criticidad_morph_idx');
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
