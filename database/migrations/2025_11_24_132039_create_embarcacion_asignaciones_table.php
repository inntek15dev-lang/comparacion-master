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
        Schema::create('embarcacion_asignaciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('embarcacion_id');
            $table->unsignedBigInteger('unidad_organizacional_mandante_id')->index('emb_asig_uo_id_foreign');
            $table->unsignedBigInteger('dependencia_id')->nullable()->index('embarcacion_asignaciones_dependencia_id_foreign');
            $table->date('fecha_asignacion');
            $table->date('fecha_desasignacion')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('motivo_desasignacion')->nullable();
            $table->unsignedTinyInteger('porcentaje_cumplimiento')->nullable();
            $table->json('estado_acceso')->nullable();
            $table->timestamps();

            $table->unique(['embarcacion_id', 'unidad_organizacional_mandante_id', 'is_active'], 'emb_asig_unique_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('embarcacion_asignaciones');
    }
};
