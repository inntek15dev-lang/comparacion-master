<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Modifica el constraint único para permitir que un mismo contratista
     * tenga múltiples vinculaciones en la misma UO/Lugar de Trabajo,
     * diferenciadas por el número de contrato.
     */
    public function up(): void
    {
        Schema::table('contratista_unidad_organizacional', function (Blueprint $table) {
            // 1. Eliminar el constraint único anterior (UO + Dependencia)
            try {
                $table->dropUnique('idx_contr_uo_dep_unique');
            } catch (\Exception $e) {
                // Si no existe, intentar con el nombre original
                try {
                    $table->dropUnique('idx_contr_uo_unique');
                } catch (\Exception $e2) {
                    // Index no existe
                }
            }

            // 2. Crear nuevo constraint que incluye numero_contrato
            // Esto permite: mismo contratista + misma UO + mismo lugar + DIFERENTE contrato
            $table->unique(
                ['contratista_id', 'unidad_organizacional_mandante_id', 'dependencia_id', 'numero_contrato'],
                'idx_contr_uo_dep_contrato_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratista_unidad_organizacional', function (Blueprint $table) {
            // Eliminar el nuevo constraint
            $table->dropUnique('idx_contr_uo_dep_contrato_unique');

            // Restaurar el constraint anterior
            $table->unique(
                ['contratista_id', 'unidad_organizacional_mandante_id', 'dependencia_id'],
                'idx_contr_uo_dep_unique'
            );
        });
    }
};
