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
        Schema::table('solicitudes_vinculacion', function (Blueprint $table) {
            $table->foreign(['aprobado_por_user_id'], 'fk_solicitudes_vinculacion_aprobador')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['contratista_id'], 'fk_solicitudes_vinculacion_contratista')->references(['id'])->on('contratistas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['contratista_padre_id'], 'fk_solicitudes_vinculacion_contratista_padre')->references(['id'])->on('contratistas')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['mandante_id'], 'fk_solicitudes_vinculacion_mandante')->references(['id'])->on('mandantes')->onUpdate('no action')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_vinculacion', function (Blueprint $table) {
            $table->dropForeign('fk_solicitudes_vinculacion_aprobador');
            $table->dropForeign('fk_solicitudes_vinculacion_contratista');
            $table->dropForeign('fk_solicitudes_vinculacion_contratista_padre');
            $table->dropForeign('fk_solicitudes_vinculacion_mandante');
        });
    }
};
