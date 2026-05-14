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
            $table->id();

            // Aquí especificamos nombres más cortos para las restricciones
            $table->foreignId('trabajador_id')
                  ->constrained('trabajadores')
                  ->onDelete('cascade')
                  ->name('fk_trab_vinc_trabajador'); // Nombre corto

            $table->foreignId('unidad_organizacional_mandante_id')
                  ->constrained('unidades_organizacionales_mandante')
                  ->onDelete('cascade')
                  ->name('fk_trab_vinc_uo'); // Nombre corto

            $table->foreignId('cargo_mandante_id')
                  ->constrained('cargos_mandante')
                  ->onDelete('cascade')
                  ->name('fk_trab_vinc_cargo'); // Nombre corto

            $table->foreignId('tipo_condicion_personal_id')
                  ->nullable()
                  ->constrained('tipos_condicion_personal')
                  ->onDelete('set null')
                  ->name('fk_trab_vinc_tipo_cond_pers'); // Nombre corto

            $table->date('fecha_ingreso_vinculacion')->comment('Fecha de ingreso a esta vinculación específica (UO/Mandante)');
            $table->date('fecha_contrato')->nullable()->comment('Fecha del contrato para esta vinculación');

            $table->boolean('is_active')->default(true)->comment('Estado de esta vinculación (activa/inactiva)');
            $table->date('fecha_desactivacion')->nullable();
            $table->text('motivo_desactivacion')->nullable();

            $table->timestamps();

            // Evitar duplicados exactos de vinculación activa para un mismo trabajador en la misma UO y cargo
            // El nombre de la restricción unique también puede ser largo, lo acortamos si es necesario
            $table->unique(
                ['trabajador_id', 'unidad_organizacional_mandante_id', 'cargo_mandante_id', 'is_active'],
                'uq_trab_vinc_activa' // Nombre corto para la restricción unique
            );
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