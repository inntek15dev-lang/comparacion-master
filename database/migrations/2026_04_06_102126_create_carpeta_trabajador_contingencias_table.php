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
        Schema::create('carpeta_trabajador_contingencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('carpeta_verificacion_trabajador_id');
            $table->foreign('carpeta_verificacion_trabajador_id', 'fk_cont_trab_id')
                  ->references('id')
                  ->on('carpetas_verificacion_trabajadores')
                  ->onDelete('cascade');
            $table->text('causal');
            $table->decimal('monto', 12, 2)->default(0);
            $table->enum('estado_subsanacion', ['PENDIENTE', 'SUBSANADO'])->default('PENDIENTE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carpeta_trabajador_contingencias');
    }
};
