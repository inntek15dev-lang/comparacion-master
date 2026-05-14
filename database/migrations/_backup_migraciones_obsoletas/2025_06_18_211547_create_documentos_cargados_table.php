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
            // --- Identificadores Principales ---
            $table->id();
            $table->foreignId('contratista_id')->constrained('contratistas')->onDelete('cascade');
            $table->foreignId('mandante_id')->constrained('mandantes')->onDelete('cascade');
            
            // CORRECCIÓN AQUÍ: Se cambió el nombre de la tabla a 'unidades_organizacionales_mandante'
            $table->foreignId('unidad_organizacional_id')->constrained('unidades_organizacionales_mandante')->onDelete('cascade');

            // --- Relación Polimórfica para la Entidad (Trabajador, Vehiculo, etc.) ---
            $table->unsignedBigInteger('entidad_id');
            $table->string('entidad_type');
            $table->index(['entidad_id', 'entidad_type']);

            // --- Origen y Usuario ---
            $table->foreignId('regla_documental_id_origen')->constrained('reglas_documentales')->onDelete('restrict');
            $table->foreignId('usuario_carga_id')->constrained('users')->onDelete('restrict');

            // --- Información del Archivo Físico ---
            $table->string('ruta_archivo', 1024);
            $table->string('nombre_original_archivo', 255);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('tamano_archivo')->nullable(); // en bytes

            // --- Fechas y Períodos del Documento ---
            $table->date('fecha_emision')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->string('periodo', 7)->nullable()->comment('Formato YYYY-MM');

            // --- CICLO DE VIDA Y ESTADO DEL DOCUMENTO (CAMPOS SEPARADOS) ---
            $table->string('estado_validacion', 50)->default('Pendiente')->comment('Pendiente, En Revisión, Validado');
            $table->string('resultado_validacion', 50)->nullable()->comment('Aprobado, Rechazado');
            $table->boolean('archivado')->default(false);
            $table->foreignId('asem_validador_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('fecha_validacion')->nullable();
            $table->text('observacion_interna_asem')->nullable();
            $table->text('observacion_rechazo')->nullable();
            $table->boolean('requiere_revalidacion')->default(false);
            $table->text('motivo_revalidacion')->nullable();
            
            // --- Timestamps de Laravel ---
            $table->timestamps(); // created_at y updated_at

            // --- CAMPOS SNAPSHOT (INMUTABLES) ---
            $table->string('nombre_documento_snapshot', 255);
            $table->string('tipo_vencimiento_snapshot', 50);
            $table->boolean('valida_emision_snapshot');
            $table->boolean('valida_vencimiento_snapshot');
            $table->integer('valor_nominal_snapshot')->nullable();
            $table->boolean('habilita_acceso_snapshot');
            $table->boolean('afecta_cumplimiento_snapshot');
            $table->boolean('es_perseguidor_snapshot');
            $table->json('criterios_snapshot')->nullable();
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