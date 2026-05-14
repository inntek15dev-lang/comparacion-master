<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ia_campos_configuracion', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('regla_documental_id')
                  ->comment('Regla a la que aplica esta configuración de extracción IA');
            $table->string('campo_clave', 100)
                  ->comment('Identificador del campo. Ej: rut_titular, fecha_emision, periodo_certificado');
            $table->string('etiqueta', 200)
                  ->comment('Nombre legible del campo. Ej: RUT del Titular del Documento');
            $table->enum('tipo_dato', ['texto', 'fecha', 'numero', 'rut', 'boolean'])
                  ->default('texto');
            $table->boolean('es_requerido')->default(true)
                  ->comment('Si es true, un fallo en este campo implica rechazo del documento');
            $table->string('mapea_a_columna', 100)->nullable()
                  ->comment('Si se especifica, al confirmar el match escribe en esta columna de documentos_cargados. Valores válidos: fecha_emision, fecha_vencimiento');
            $table->text('descripcion_ia')->nullable()
                  ->comment('Instrucción adicional para la IA sobre cómo encontrar este campo');
            $table->integer('orden')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('regla_documental_id', 'ia_campos_conf_regla_fk')
                  ->references('id')
                  ->on('reglas_documentales')
                  ->onDelete('cascade');

            $table->index('regla_documental_id', 'ia_campos_conf_regla_idx');
            $table->index(['regla_documental_id', 'is_active'], 'ia_campos_conf_activos_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ia_campos_configuracion');
    }
};
