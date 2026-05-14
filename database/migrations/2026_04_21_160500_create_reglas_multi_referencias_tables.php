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
        Schema::create('regla_observacion_documento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regla_documental_id')->constrained('reglas_documentales')->onDelete('cascade');
            $table->foreignId('observacion_documento_id')->constrained('observaciones_documento')->name('fk_regla_obs_doc_id')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('regla_formato_documento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regla_documental_id')->constrained('reglas_documentales')->onDelete('cascade');
            $table->foreignId('formato_documento_id')->constrained('formatos_documento_muestra')->name('fk_regla_fmt_doc_id')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('regla_doc_relacionado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regla_documental_id')->constrained('reglas_documentales')->onDelete('cascade');
            $table->foreignId('documento_relacionado_id')->constrained('nombre_documentos')->name('fk_regla_doc_rel_id')->onDelete('cascade');
            $table->timestamps();
        });

        // Opcional: Migrar datos existentes (si aplica, lo hacemos directo por DB later si es necesario)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regla_doc_relacionado');
        Schema::dropIfExists('regla_formato_documento');
        Schema::dropIfExists('regla_observacion_documento');
    }
};
