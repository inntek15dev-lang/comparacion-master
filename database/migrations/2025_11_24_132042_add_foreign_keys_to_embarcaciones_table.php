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
        Schema::table('embarcaciones', function (Blueprint $table) {
            $table->foreign(['contratista_id'])->references(['id'])->on('contratistas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['tenencia_vehiculo_id'])->references(['id'])->on('tenencias_vehiculo')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['tipo_embarcacion_id'])->references(['id'])->on('tipos_embarcacion')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('embarcaciones', function (Blueprint $table) {
            $table->dropForeign('embarcaciones_contratista_id_foreign');
            $table->dropForeign('embarcaciones_tenencia_vehiculo_id_foreign');
            $table->dropForeign('embarcaciones_tipo_embarcacion_id_foreign');
        });
    }
};
