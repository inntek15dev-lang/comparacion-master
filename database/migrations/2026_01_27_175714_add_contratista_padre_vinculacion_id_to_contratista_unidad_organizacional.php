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
            // Columna para referenciar la vinculación del contratista padre
            // Esto permite rastrear qué vinculaciones del sub-contratista heredan de cuáles del padre
            $table->unsignedBigInteger('contratista_padre_vinculacion_id')->nullable()->after('dependencia_id');
            
            // Foreign key (opcional, ya que la vinculación padre podría ser eliminada)
            $table->foreign('contratista_padre_vinculacion_id', 'fk_vinculacion_padre')
                  ->references('id')
                  ->on('contratista_unidad_organizacional')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratista_unidad_organizacional', function (Blueprint $table) {
            $table->dropForeign('fk_vinculacion_padre');
            $table->dropColumn('contratista_padre_vinculacion_id');
        });
    }
};
