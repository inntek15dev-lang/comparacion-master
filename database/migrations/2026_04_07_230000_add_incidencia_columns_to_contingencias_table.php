<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carpeta_trabajador_contingencias', function (Blueprint $table) {
            // FK a la carpeta (para ítems que aplican a toda la empresa)
            $table->unsignedBigInteger('carpeta_verificacion_id')->nullable()->after('id');
            $table->foreign('carpeta_verificacion_id', 'fk_ctc_carpeta_id')
                  ->references('id')
                  ->on('carpetas_verificacion')
                  ->onDelete('cascade');

            // Hacer nullable la FK de trabajador (puede no haber si es empresa)
            $table->unsignedBigInteger('carpeta_verificacion_trabajador_id')->nullable()->change();

            // Tipo de ítem
            $table->enum('tipo', ['observacion', 'contingencia'])->default('contingencia')->after('carpeta_verificacion_id');

            // Subtipo solo para contingencias
            $table->enum('subtipo', ['retenible', 'no_retenible'])->nullable()->after('tipo');

            // ¿Aplica a toda la empresa? (solo observaciones)
            $table->boolean('aplica_empresa')->default(false)->after('subtipo');

            // Código único incremental por carpeta (desde 100001)
            $table->unsignedBigInteger('codigo')->nullable()->after('aplica_empresa');

            // Índice normalizado
            $table->index('carpeta_verificacion_id', 'idx_ctc_carpeta_id');
            $table->index(['carpeta_verificacion_id', 'codigo'], 'idx_ctc_carpeta_codigo');
        });
    }

    public function down(): void
    {
        Schema::table('carpeta_trabajador_contingencias', function (Blueprint $table) {
            $table->dropForeign('fk_ctc_carpeta_id');
            $table->dropIndex('idx_ctc_carpeta_id');
            $table->dropIndex('idx_ctc_carpeta_codigo');
            $table->dropColumn([
                'carpeta_verificacion_id',
                'tipo',
                'subtipo',
                'aplica_empresa',
                'codigo',
            ]);
            // Revertir nullable a not-null requiere saber el estado original,
            // por simplicidad no se revierte automáticamente
        });
    }
};
