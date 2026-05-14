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
        Schema::table('reglas_documentales', function (Blueprint $table) {
            if (!Schema::hasColumn('reglas_documentales', 'valida_solo_mandante')) {
                $table->boolean('valida_solo_mandante')->default(false)->after('requiere_validacion_mandante');
            }
        });

        Schema::table('documentos_cargados', function (Blueprint $table) {
            if (!Schema::hasColumn('documentos_cargados', 'valida_solo_mandante_snapshot')) {
                $table->boolean('valida_solo_mandante_snapshot')->default(false)->after('valida_vencimiento_snapshot');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reglas_documentales', function (Blueprint $table) {
            if (Schema::hasColumn('reglas_documentales', 'valida_solo_mandante')) {
                $table->dropColumn('valida_solo_mandante');
            }
        });

        Schema::table('documentos_cargados', function (Blueprint $table) {
            if (Schema::hasColumn('documentos_cargados', 'valida_solo_mandante_snapshot')) {
                $table->dropColumn('valida_solo_mandante_snapshot');
            }
        });
    }
};
