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
        Schema::create('vehiculo_asignaciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('vehiculo_id');
            $table->unsignedBigInteger('unidad_organizacional_mandante_id')->index('vehiculo_asignaciones_unidad_organizacional_mandante_id_foreign');
            $table->unsignedBigInteger('dependencia_id')->nullable()->index('fk_vehiculo_asignaciones_dependencia');
            $table->date('fecha_asignacion');
            $table->date('fecha_desasignacion')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('motivo_desasignacion')->nullable();
            $table->unsignedTinyInteger('porcentaje_cumplimiento')->nullable();
            $table->json('estado_acceso')->nullable();
            $table->timestamps();

            $table->unique(['vehiculo_id', 'unidad_organizacional_mandante_id', 'is_active'], 'veh_asig_unique_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculo_asignaciones');
    }
};
