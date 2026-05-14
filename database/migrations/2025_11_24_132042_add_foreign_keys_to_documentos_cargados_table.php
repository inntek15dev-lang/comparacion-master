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
        Schema::table('documentos_cargados', function (Blueprint $table) {
            $table->foreign(['asem_validador_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['contratista_id'])->references(['id'])->on('contratistas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['mandante_id'])->references(['id'])->on('mandantes')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['mandante_validador_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['reemplaza_a_id'])->references(['id'])->on('documentos_cargados')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['regla_documental_id_origen'])->references(['id'])->on('reglas_documentales')->onUpdate('no action')->onDelete('restrict');
            $table->foreign(['unidad_organizacional_id'])->references(['id'])->on('unidades_organizacionales_mandante')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['usuario_carga_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_cargados', function (Blueprint $table) {
            $table->dropForeign('documentos_cargados_asem_validador_id_foreign');
            $table->dropForeign('documentos_cargados_contratista_id_foreign');
            $table->dropForeign('documentos_cargados_mandante_id_foreign');
            $table->dropForeign('documentos_cargados_mandante_validador_id_foreign');
            $table->dropForeign('documentos_cargados_reemplaza_a_id_foreign');
            $table->dropForeign('documentos_cargados_regla_documental_id_origen_foreign');
            $table->dropForeign('documentos_cargados_unidad_organizacional_id_foreign');
            $table->dropForeign('documentos_cargados_usuario_carga_id_foreign');
        });
    }
};
