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
        Schema::create('reglas_documentales', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('mandante_id')->index('reglas_documentales_mandante_id_foreign');
            $table->unsignedBigInteger('tipo_entidad_controlada_id')->index('reglas_documentales_tipo_entidad_controlada_id_foreign');
            $table->unsignedBigInteger('nombre_documento_id')->index('reglas_documentales_nombre_documento_id_foreign');
            $table->integer('valor_nominal_documento')->nullable()->default(1);
            $table->unsignedBigInteger('aplica_empresa_condicion_id')->nullable()->index('reglas_documentales_aplica_empresa_condicion_id_foreign');
            $table->unsignedBigInteger('aplica_persona_condicion_id')->nullable()->index('reglas_documentales_aplica_persona_condicion_id_foreign');
            $table->unsignedBigInteger('aplica_cargo_id')->nullable()->index('reglas_documentales_aplica_cargo_id_foreign');
            $table->unsignedBigInteger('aplica_nacionalidad_id')->nullable()->index('reglas_documentales_aplica_nacionalidad_id_foreign');
            $table->unsignedBigInteger('condicion_fecha_ingreso_id')->nullable()->index('reglas_documentales_condicion_fecha_ingreso_id_foreign');
            $table->date('fecha_comparacion_ingreso')->nullable();
            $table->text('rut_especificos')->nullable();
            $table->text('rut_excluidos')->nullable();
            $table->unsignedBigInteger('observacion_documento_id')->nullable()->index('reglas_documentales_observacion_documento_id_foreign');
            $table->unsignedBigInteger('formato_documento_id')->nullable()->index('reglas_documentales_formato_documento_id_foreign');
            $table->unsignedBigInteger('documento_relacionado_id')->nullable()->index('reglas_documentales_documento_relacionado_id_foreign');
            $table->unsignedBigInteger('tipo_vencimiento_id')->nullable()->index('reglas_documentales_tipo_vencimiento_id_foreign');
            $table->integer('dias_validez_documento')->nullable();
            $table->integer('dias_gracia_carga')->nullable()->comment('Días de gracia para cargar documentos periódicos en el mes siguiente');
            $table->integer('dias_aviso_vencimiento')->nullable()->default(30);
            $table->boolean('valida_emision')->default(false);
            $table->boolean('valida_vencimiento')->default(false);
            $table->boolean('requiere_validacion_mandante')->default(false);
            $table->boolean('mostrar_historico_documento')->default(false);
            $table->boolean('permite_ver_nacionalidad_trabajador')->default(false);
            $table->boolean('permite_modificar_nacionalidad_trabajador')->default(false);
            $table->boolean('permite_ver_fecha_nacimiento_trabajador')->default(false);
            $table->boolean('permite_modificar_fecha_nacimiento_trabajador')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable()->index('fk_reglas_documentales_created_by');
            $table->unsignedBigInteger('updated_by')->nullable()->index('fk_reglas_documentales_updated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reglas_documentales');
    }
};
