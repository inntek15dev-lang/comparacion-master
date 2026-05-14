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
            $table->decimal('imc_meses_estimados', 8, 2)->nullable()->after('dias_gracia_carga');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reglas_documentales', function (Blueprint $table) {
            $table->dropColumn('imc_meses_estimados');
        });
    }
};
