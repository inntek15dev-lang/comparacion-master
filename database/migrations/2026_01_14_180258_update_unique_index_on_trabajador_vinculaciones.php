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
        Schema::table('trabajador_vinculaciones', function (Blueprint $table) {
            // Eliminar el índice antiguo
            $table->dropUnique('uq_trab_vinc_activa');
            
            // Crear el nuevo índice basado en el par (UO + Lugar)
            $table->unique(['trabajador_id', 'unidad_organizacional_mandante_id', 'dependencia_id', 'is_active'], 'uq_trabajador_uo_lugar_activo');
        });
    }

    public function down(): void
    {
        Schema::table('trabajador_vinculaciones', function (Blueprint $table) {
            $table->dropUnique('uq_trabajador_uo_lugar_activo');
            
            // Restaurar el índice antiguo
            $table->unique(['trabajador_id', 'unidad_organizacional_mandante_id', 'cargo_mandante_id', 'is_active'], 'uq_trab_vinc_activa');
        });
    }
};
