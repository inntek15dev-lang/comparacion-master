<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modificar el constraint único para permitir múltiples vinculaciones
     * del mismo trabajador en la misma UO/Lugar pero con diferente contrato.
     */
    public function up(): void
    {
        Schema::table('trabajador_vinculaciones', function (Blueprint $table) {
            // Eliminar el constraint anterior
            $table->dropUnique('uq_trabajador_uo_lugar_activo');
        });

        Schema::table('trabajador_vinculaciones', function (Blueprint $table) {
            // Crear nuevo constraint que incluye numero_contrato
            // Esto permite: mismo trabajador + misma UO + mismo lugar + activo = OK si contrato es diferente
            $table->unique(
                ['trabajador_id', 'unidad_organizacional_mandante_id', 'dependencia_id', 'numero_contrato', 'is_active'],
                'uq_trabajador_uo_lugar_contrato_activo'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajador_vinculaciones', function (Blueprint $table) {
            $table->dropUnique('uq_trabajador_uo_lugar_contrato_activo');
        });

        Schema::table('trabajador_vinculaciones', function (Blueprint $table) {
            $table->unique(
                ['trabajador_id', 'unidad_organizacional_mandante_id', 'dependencia_id', 'is_active'],
                'uq_trabajador_uo_lugar_activo'
            );
        });
    }
};
