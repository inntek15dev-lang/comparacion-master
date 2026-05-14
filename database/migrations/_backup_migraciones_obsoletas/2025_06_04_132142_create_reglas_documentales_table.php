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
            $table->id();
            $table->foreignId('mandante_id')->constrained('mandantes')->onDelete('cascade');
            $table->foreignId('tipo_entidad_controlada_id')->constrained('tipos_entidad_controlable')->onDelete('restrict');
            $table->foreignId('nombre_documento_id')->constrained('nombre_documentos')->onDelete('restrict');
            $table->integer('valor_nominal_documento')->nullable()->default(1);

            $table->foreignId('aplica_empresa_condicion_id')->nullable()->constrained('tipos_condicion')->onDelete('set null');
            $table->foreignId('aplica_persona_condicion_id')->nullable()->constrained('tipos_condicion_personal')->onDelete('set null');
            $table->foreignId('aplica_cargo_id')->nullable()->constrained('cargos_mandante')->onDelete('set null');
            $table->foreignId('aplica_nacionalidad_id')->nullable()->constrained('nacionalidades')->onDelete('set null');
            $table->foreignId('condicion_fecha_ingreso_id')->nullable()->constrained('condiciones_fecha_ingreso')->onDelete('set null');
            $table->date('fecha_comparacion_ingreso')->nullable();

            $table->foreignId('unidad_organizacional_mandante_id')->nullable()->constrained('unidades_organizacionales_mandante')->onDelete('set null');

            $table->text('rut_especificos')->nullable();
            $table->text('rut_excluidos')->nullable();

            $table->foreignId('observacion_documento_id')->nullable()->constrained('observaciones_documento')->onDelete('set null');
            $table->foreignId('formato_documento_id')->nullable()->constrained('formatos_documento_muestra')->onDelete('set null'); // La imagen indica formatos_documento_muestra
            $table->foreignId('documento_relacionado_id')->nullable()->constrained('nombre_documentos')->onDelete('set null');

            $table->foreignId('tipo_vencimiento_id')->nullable()->constrained('tipos_vencimiento')->onDelete('set null');
            $table->integer('dias_validez_documento')->nullable();
            $table->integer('dias_aviso_vencimiento')->nullable()->default(30);

            $table->boolean('valida_emision')->default(false);
            $table->boolean('valida_vencimiento')->default(false);

            $table->foreignId('configuracion_validacion_id')->nullable()->constrained('configuraciones_validacion')->onDelete('set null');

            $table->boolean('restringe_acceso')->default(false);
            $table->boolean('afecta_porcentaje_cumplimiento')->default(false);
            $table->boolean('documento_es_perseguidor')->default(false);
            $table->boolean('mostrar_historico_documento')->default(false);

            $table->boolean('permite_ver_nacionalidad_trabajador')->default(false);
            $table->boolean('permite_modificar_nacionalidad_trabajador')->default(false);
            $table->boolean('permite_ver_fecha_nacimiento_trabajador')->default(false);
            $table->boolean('permite_modificar_fecha_nacimiento_trabajador')->default(false);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
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