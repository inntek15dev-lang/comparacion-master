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
        Schema::table('ia_campos_configuracion', function (Blueprint $table) {
            $table->foreignId('formato_muestra_id')->nullable()->after('criterio_evaluacion_id')->constrained('formatos_documento_muestra')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ia_campos_configuracion', function (Blueprint $table) {
            $table->dropForeign(['formato_muestra_id']);
            $table->dropColumn('formato_muestra_id');
        });
    }
};
