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
            $table->date('fecha_inicio_acredita')->nullable()->after('acredita');
            $table->date('fecha_fin_acredita')->nullable()->after('fecha_inicio_acredita');
            $table->date('fecha_inicio_verifica')->nullable()->after('verifica');
            $table->date('fecha_fin_verifica')->nullable()->after('fecha_inicio_verifica');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratista_unidad_organizacional', function (Blueprint $table) {
            $table->dropColumn([
                'fecha_inicio_acredita',
                'fecha_fin_acredita',
                'fecha_inicio_verifica',
                'fecha_fin_verifica',
            ]);
        });
    }
};
