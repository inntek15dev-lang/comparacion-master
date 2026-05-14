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
        Schema::table('maquinarias', function (Blueprint $table) {
            $table->foreign(['contratista_id'])->references(['id'])->on('contratistas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['marca_vehiculo_id'])->references(['id'])->on('marcas_vehiculo')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['tenencia_vehiculo_id'])->references(['id'])->on('tenencias_vehiculo')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['tipo_maquinaria_id'])->references(['id'])->on('tipos_maquinaria')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maquinarias', function (Blueprint $table) {
            $table->dropForeign('maquinarias_contratista_id_foreign');
            $table->dropForeign('maquinarias_marca_vehiculo_id_foreign');
            $table->dropForeign('maquinarias_tenencia_vehiculo_id_foreign');
            $table->dropForeign('maquinarias_tipo_maquinaria_id_foreign');
        });
    }
};
