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
        Schema::table('vehiculo_asignaciones', function (Blueprint $table) {
            $table->foreign(['dependencia_id'], 'fk_vehiculo_asignaciones_dependencia')->references(['id'])->on('dependencias')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['unidad_organizacional_mandante_id'])->references(['id'])->on('unidades_organizacionales_mandante')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['vehiculo_id'])->references(['id'])->on('vehiculos')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehiculo_asignaciones', function (Blueprint $table) {
            $table->dropForeign('fk_vehiculo_asignaciones_dependencia');
            $table->dropForeign('vehiculo_asignaciones_unidad_organizacional_mandante_id_foreign');
            $table->dropForeign('vehiculo_asignaciones_vehiculo_id_foreign');
        });
    }
};
