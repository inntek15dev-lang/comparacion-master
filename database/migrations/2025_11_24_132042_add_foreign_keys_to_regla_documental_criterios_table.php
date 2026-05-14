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
        Schema::table('regla_documental_criterios', function (Blueprint $table) {
            $table->foreign(['aclaracion_criterio_id'])->references(['id'])->on('aclaraciones_criterio')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['criterio_evaluacion_id'])->references(['id'])->on('criterios_evaluacion')->onUpdate('no action')->onDelete('restrict');
            $table->foreign(['regla_documental_id'])->references(['id'])->on('reglas_documentales')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['sub_criterio_id'])->references(['id'])->on('sub_criterios')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['texto_rechazo_id'])->references(['id'])->on('textos_rechazo')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regla_documental_criterios', function (Blueprint $table) {
            $table->dropForeign('regla_documental_criterios_aclaracion_criterio_id_foreign');
            $table->dropForeign('regla_documental_criterios_criterio_evaluacion_id_foreign');
            $table->dropForeign('regla_documental_criterios_regla_documental_id_foreign');
            $table->dropForeign('regla_documental_criterios_sub_criterio_id_foreign');
            $table->dropForeign('regla_documental_criterios_texto_rechazo_id_foreign');
        });
    }
};
