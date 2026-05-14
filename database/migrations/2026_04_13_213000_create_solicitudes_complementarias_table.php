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
        Schema::create('solicitudes_complementarias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('carpeta_trabajador_contingencia_id');
            $table->foreign('carpeta_trabajador_contingencia_id', 'fk_sol_comp_ctc')
                  ->references('id')
                  ->on('carpeta_trabajador_contingencias')
                  ->onDelete('cascade');
                
            // Quién solicita (el contratista de ese entonces)
            $table->unsignedBigInteger('contratista_unidad_organizacional_id');
            $table->foreign('contratista_unidad_organizacional_id', 'fk_sol_comp_cuo')
                  ->references('id')
                  ->on('contratista_unidad_organizacional')
                  ->onDelete('cascade');

            // Quien revisará (puede asignarse un auditor después)
            $table->foreignId('auditor_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // CREADO, ENVIADO, EN_REVISION, SOLUCIONADO, RECHAZADO
            $table->string('estado')->default('ENVIADO');

            $table->dateTime('fecha_envio')->nullable();
            $table->dateTime('fecha_revision')->nullable();

            // Notas del auditor si es rechazado o comentarios.
            $table->text('observaciones_auditor')->nullable();
            $table->timestamps();
        });

        Schema::create('documentos_solic_complementarias', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('solicitud_complementaria_id');
            $table->foreign('solicitud_complementaria_id', 'fk_doc_sol_comp')
                  ->references('id')
                  ->on('solicitudes_complementarias')
                  ->onDelete('cascade');
                
            $table->string('path', 500);
            $table->string('nombre_original');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos_solic_complementarias');
        Schema::dropIfExists('solicitudes_complementarias');
    }
};
