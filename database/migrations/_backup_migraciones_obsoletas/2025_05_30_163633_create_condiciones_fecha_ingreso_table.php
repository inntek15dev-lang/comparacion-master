<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Nombre de la tabla: condiciones_fecha_ingreso
        Schema::create('condiciones_fecha_ingreso', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->text('descripcion')->nullable();
            $table->date('fecha_tope_anterior_o_igual')->nullable()->comment('Aplica si Fecha Ingreso Trabajador <= esta fecha');
            $table->date('fecha_tope_posterior_o_igual')->nullable()->comment('Aplica si Fecha Ingreso Trabajador >= esta fecha');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('condiciones_fecha_ingreso');
    }
};