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
        Schema::create('trabajador_vinculaciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('trabajador_id');
            $table->unsignedBigInteger('unidad_organizacional_mandante_id');
            $table->unsignedBigInteger('cargo_mandante_id');
            $table->unsignedBigInteger('dependencia_id')->nullable()->index('trabajador_vinculaciones_dependencia_id_foreign');
            $table->unsignedBigInteger('tipo_condicion_personal_id')->nullable();
            $table->date('fecha_ingreso_vinculacion')->comment('Fecha de ingreso a esta vinculación específica (UO/Mandante)');
            $table->date('fecha_contrato')->nullable()->comment('Fecha del contrato para esta vinculación');
            $table->boolean('is_active')->default(true)->comment('Estado de esta vinculación (activa/inactiva)');
            $table->date('fecha_desactivacion')->nullable();
            $table->text('motivo_desactivacion')->nullable();
            $table->unsignedTinyInteger('porcentaje_cumplimiento')->nullable();
            $table->json('estado_acceso')->nullable();
            $table->timestamps();

            $table->unique(['trabajador_id', 'unidad_organizacional_mandante_id', 'cargo_mandante_id', 'is_active'], 'uq_trab_vinc_activa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trabajador_vinculaciones');
    }
};
