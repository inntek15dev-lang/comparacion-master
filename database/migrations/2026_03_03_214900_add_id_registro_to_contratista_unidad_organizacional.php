<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    /**
     * Run the migrations.
     * Agrega campo id_registro a contratista_unidad_organizacional
     * para almacenar IDs de sistemas legacy durante migración.
     */
    public function up(): void
    {
        Schema::table('contratista_unidad_organizacional', function (Blueprint $table) {
            $table->string('id_registro', 50)->nullable()->after('contratista_id')
                ->comment('ID de registro legacy o autogenerado para la vinculación');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratista_unidad_organizacional', function (Blueprint $table) {
            $table->dropColumn('id_registro');
        });
    }
};
