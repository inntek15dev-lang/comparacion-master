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
            // Columna para excluir contratistas completos de la aplicación de la regla
            // Se ingresan RUTs de contratistas separados por ; — excluye a TODOS sus trabajadores/vehículos
            $table->text('rut_contratistas_excluidos')->nullable()->after('rut_excluidos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reglas_documentales', function (Blueprint $table) {
            $table->dropColumn('rut_contratistas_excluidos');
        });
    }
};
