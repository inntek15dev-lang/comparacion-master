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
        Schema::create('contratista_uo_entidad', function (Blueprint $table) {
            $table->unsignedBigInteger('contratista_unidad_organizacional_id');
            $table->unsignedBigInteger('tipo_entidad_controlable_id');

            $table->primary(['contratista_unidad_organizacional_id', 'tipo_entidad_controlable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratista_uo_entidad');
    }
};
