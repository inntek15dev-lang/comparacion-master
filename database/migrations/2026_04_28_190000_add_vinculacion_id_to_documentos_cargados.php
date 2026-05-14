<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Agrega trabajador_vinculacion_id a documentos_cargados para soportar
     * la lógica de Documento Perseguidor vs. Documento por Vinculación.
     * 
     * - Si es_perseguidor = true  → trabajador_vinculacion_id = NULL (un doc para todas las vinculaciones)
     * - Si es_perseguidor = false → trabajador_vinculacion_id = ID  (un doc por vinculación específica)
     */
    public function up(): void
    {
        Schema::table('documentos_cargados', function (Blueprint $table) {
            if (!Schema::hasColumn('documentos_cargados', 'trabajador_vinculacion_id')) {
                $table->unsignedBigInteger('trabajador_vinculacion_id')
                      ->nullable()
                      ->after('unidad_organizacional_id')
                      ->comment('NULL = documento perseguidor (aplica a todas las vinculaciones). Valor = documento específico de esa vinculación.');

                $table->foreign('trabajador_vinculacion_id', 'dc_trabajador_vinculacion_fk')
                      ->references('id')
                      ->on('trabajador_vinculaciones')
                      ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_cargados', function (Blueprint $table) {
            if (Schema::hasColumn('documentos_cargados', 'trabajador_vinculacion_id')) {
                $table->dropForeign('dc_trabajador_vinculacion_fk');
                $table->dropColumn('trabajador_vinculacion_id');
            }
        });
    }
};
