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
        Schema::create('vehiculo_unidad_organizacional', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('vehiculo_id')->index('vehiculo_unidad_organizacional_vehiculo_id_foreign');
            $table->unsignedBigInteger('unidad_organizacional_mandante_id');
            $table->date('fecha_asignacion');
            $table->boolean('is_active')->default(true);
            $table->date('fecha_desactivacion')->nullable();
            $table->text('motivo_desactivacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculo_unidad_organizacional');
    }
};
