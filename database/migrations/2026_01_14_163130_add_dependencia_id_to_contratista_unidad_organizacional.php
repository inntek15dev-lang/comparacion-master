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
        Schema::table('contratista_unidad_organizacional', function (Blueprint $table) {
            $table->unsignedBigInteger('dependencia_id')->nullable()->after('unidad_organizacional_mandante_id');
            
            // Intentar borrar el índice único anterior si existe
            // El nombre suele ser 'idx_contr_uo_unique' según mi revisión de la migración original
            try {
                $table->dropUnique('idx_contr_uo_unique');
            } catch (\Exception $e) {
                // Si falla es porque el nombre es distinto o no existe
            }

            // Nuevo índice único para permitir el par (UA + UO)
            $table->unique(['contratista_id', 'unidad_organizacional_mandante_id', 'dependencia_id'], 'idx_contr_uo_dep_unique');
            
            // Relación con dependencias
            $table->foreign('dependencia_id', 'fk_cuo_dependencia')
                  ->references('id')->on('dependencias')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratista_unidad_organizacional', function (Blueprint $table) {
            $table->dropForeign('fk_cuo_dependencia');
            $table->dropUnique('idx_contr_uo_dep_unique');
            $table->unique(['contratista_id', 'unidad_organizacional_mandante_id'], 'idx_contr_uo_unique');
            $table->dropColumn('dependencia_id');
        });
    }
};
