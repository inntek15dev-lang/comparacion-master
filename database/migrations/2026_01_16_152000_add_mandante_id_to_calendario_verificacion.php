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
        Schema::table('calendario_verificacion', function (Blueprint $table) {
            $table->foreignId('mandante_id')->after('id')->nullable()->constrained('mandantes')->onDelete('cascade');
            
            // Eliminar la unicidad anterior y crear una nueva que incluya mandante_id
            $table->dropUnique(['anio', 'mes']);
            $table->unique(['mandante_id', 'anio', 'mes'], 'unique_mandante_periodo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendario_verificacion', function (Blueprint $table) {
            $table->dropUnique('unique_mandante_periodo');
            $table->dropConstrainedForeignId('mandante_id');
            $table->unique(['anio', 'mes']);
        });
    }
};
