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
        Schema::table('reglas_documentales', function (Blueprint $table) {
            $table->foreign(['created_by'], 'fk_reglas_documentales_created_by')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['updated_by'], 'fk_reglas_documentales_updated_by')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['aplica_cargo_id'])->references(['id'])->on('cargos_mandante')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['aplica_empresa_condicion_id'])->references(['id'])->on('tipos_condicion')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['aplica_nacionalidad_id'])->references(['id'])->on('nacionalidades')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['aplica_persona_condicion_id'])->references(['id'])->on('tipos_condicion_personal')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['condicion_fecha_ingreso_id'])->references(['id'])->on('condiciones_fecha_ingreso')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['documento_relacionado_id'])->references(['id'])->on('nombre_documentos')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['formato_documento_id'])->references(['id'])->on('formatos_documento_muestra')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['mandante_id'])->references(['id'])->on('mandantes')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['nombre_documento_id'])->references(['id'])->on('nombre_documentos')->onUpdate('no action')->onDelete('restrict');
            $table->foreign(['observacion_documento_id'])->references(['id'])->on('observaciones_documento')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['tipo_entidad_controlada_id'])->references(['id'])->on('tipos_entidad_controlable')->onUpdate('no action')->onDelete('restrict');
            $table->foreign(['tipo_vencimiento_id'])->references(['id'])->on('tipos_vencimiento')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reglas_documentales', function (Blueprint $table) {
            $table->dropForeign('fk_reglas_documentales_created_by');
            $table->dropForeign('fk_reglas_documentales_updated_by');
            $table->dropForeign('reglas_documentales_aplica_cargo_id_foreign');
            $table->dropForeign('reglas_documentales_aplica_empresa_condicion_id_foreign');
            $table->dropForeign('reglas_documentales_aplica_nacionalidad_id_foreign');
            $table->dropForeign('reglas_documentales_aplica_persona_condicion_id_foreign');
            $table->dropForeign('reglas_documentales_condicion_fecha_ingreso_id_foreign');
            $table->dropForeign('reglas_documentales_documento_relacionado_id_foreign');
            $table->dropForeign('reglas_documentales_formato_documento_id_foreign');
            $table->dropForeign('reglas_documentales_mandante_id_foreign');
            $table->dropForeign('reglas_documentales_nombre_documento_id_foreign');
            $table->dropForeign('reglas_documentales_observacion_documento_id_foreign');
            $table->dropForeign('reglas_documentales_tipo_entidad_controlada_id_foreign');
            $table->dropForeign('reglas_documentales_tipo_vencimiento_id_foreign');
        });
    }
};
