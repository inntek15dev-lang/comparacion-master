<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('datos_extraidos_ia', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Relación 1:1 con documentos_cargados
            $table->unsignedBigInteger('documento_cargado_id')
                  ->unique()
                  ->comment('Relación 1:1 con documentos_cargados. Se actualiza al reprocesar.');

            // ─── Extracción ───────────────────────────────────────────────
            $table->enum('fuente', ['API', 'EXCEL'])
                  ->comment('Origen de los datos extraídos');
            $table->string('proveedor_ia', 100)->nullable()
                  ->comment('Ej: google/gemini-3.1-flash-lite-preview');

            // Datos extraídos en JSON flexible
            $table->json('datos_extraidos')
                  ->comment('Ej: {"rut_titular":"12.345.678-9","fecha_emision":"2026-01-01"}');
            $table->json('respuesta_cruda_ia')->nullable()
                  ->comment('Response raw completo del LLM para auditoría y debugging');

            // Métricas de consumo (auditable y facturable)
            $table->integer('tokens_entrada')->nullable();
            $table->integer('tokens_salida')->nullable();
            $table->decimal('costo_estimado_usd', 10, 6)->nullable();

            // ─── Match calculado por el sistema ───────────────────────────
            $table->enum('match_calculado', ['APROBADO', 'RECHAZADO', 'REVISION_MANUAL'])
                  ->nullable()
                  ->comment('Resultado calculado por IaMatchService');
            $table->json('detalle_match')->nullable()
                  ->comment('Array: [{campo, esperado, extraido, ok, mensaje}]');
            $table->text('observacion_match')->nullable()
                  ->comment('Texto legible campo a campo para el operador');

            // ─── Estado del proceso ───────────────────────────────────────
            $table->enum('estado', [
                'PENDIENTE_EXTRACCION',
                'EXTRAIDO',
                'MATCH_CALCULADO',
                'CONFIRMADO',
                'RECHAZADO_OPERADOR',
            ])->default('PENDIENTE_EXTRACCION');

            // ─── Confirmación (quién y cuándo apretó el botón) ────────────
            $table->unsignedBigInteger('usuario_confirma_id')->nullable()
                  ->comment('FK al usuario que confirmó el resultado del match');
            $table->timestamp('fecha_confirmacion')->nullable();

            $table->timestamps();

            // ─── Foreign Keys ─────────────────────────────────────────────
            $table->foreign('documento_cargado_id', 'datos_ia_doc_fk')
                  ->references('id')
                  ->on('documentos_cargados')
                  ->onDelete('cascade');

            $table->foreign('usuario_confirma_id', 'datos_ia_user_fk')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            // ─── Índices ──────────────────────────────────────────────────
            $table->index('estado', 'datos_ia_estado_idx');
            $table->index('match_calculado', 'datos_ia_match_idx');
            $table->index('fuente', 'datos_ia_fuente_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('datos_extraidos_ia');
    }
};
