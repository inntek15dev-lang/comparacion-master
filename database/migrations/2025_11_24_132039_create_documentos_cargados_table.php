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
        Schema::create('documentos_cargados', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('contratista_id')->index('documentos_cargados_contratista_id_foreign');
            $table->unsignedBigInteger('mandante_id')->index('documentos_cargados_mandante_id_foreign');
            $table->unsignedBigInteger('unidad_organizacional_id')->index('documentos_cargados_unidad_organizacional_id_foreign');
            $table->unsignedBigInteger('entidad_id');
            $table->string('entidad_type');
            $table->unsignedBigInteger('regla_documental_id_origen')->index('documentos_cargados_regla_documental_id_origen_foreign');
            $table->unsignedBigInteger('usuario_carga_id')->index('documentos_cargados_usuario_carga_id_foreign');
            $table->string('ruta_archivo')->nullable();
            $table->string('nombre_original_archivo')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->integer('tamano_archivo')->nullable();
            $table->date('fecha_emision')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->boolean('es_vencimiento_modificado')->default(false);
            $table->text('motivo_modificacion_vencimiento')->nullable();
            $table->string('ruta_justificativo_modificacion')->nullable();
            $table->string('periodo', 7)->nullable()->comment('Formato YYYY-MM');
            $table->string('estado_validacion', 50)->default('SIN ASIGNAR')->comment('SIN ASIGNAR, ASIGNADO,DEVUELTO,ASIGNADO-REVALIDAR,NO ASIGNADO-REVALIDAR, ARCHIVADO, ARCHIVADO-REVALIDADO');
            $table->string('resultado_validacion', 50)->nullable()->comment('Aprobado, Rechazado');
            $table->unsignedBigInteger('asem_validador_id')->nullable()->index('documentos_cargados_asem_validador_id_foreign');
            $table->unsignedBigInteger('mandante_validador_id')->nullable()->index('documentos_cargados_mandante_validador_id_foreign');
            $table->timestamp('fecha_validacion')->nullable();
            $table->dateTime('fecha_validacion_asem')->nullable()->comment('Timestamp de la validación de ASEM en flujos de doble validación');
            $table->dateTime('fecha_validacion_mandante')->nullable()->comment('Timestamp de la validación final del Mandante');
            $table->text('observacion_interna_asem')->nullable();
            $table->text('observacion_rechazo')->nullable();
            $table->text('observacion_validador')->nullable();
            $table->text('motivo_revalidacion')->nullable();
            $table->unsignedBigInteger('reemplaza_a_id')->nullable()->index('documentos_cargados_reemplaza_a_id_foreign');
            $table->boolean('es_error_validador')->default(false)->comment('Bandera para marcar si la revalidación fue por un error del validador original');
            $table->timestamps();
            $table->string('nombre_documento_snapshot');
            $table->string('tipo_vencimiento_snapshot', 50);
            $table->boolean('valida_emision_snapshot');
            $table->boolean('valida_vencimiento_snapshot');
            $table->integer('valor_nominal_snapshot')->nullable();
            $table->json('criterios_snapshot')->nullable();
            $table->text('observacion_documento_snapshot')->nullable();
            $table->string('formato_documento_snapshot')->nullable();
            $table->unsignedBigInteger('documento_relacionado_id_snapshot')->nullable();

            $table->index(['entidad_id', 'entidad_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos_cargados');
    }
};
