<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_tipos_vehiculo_mandante', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('mandante_id');
            $table->unsignedBigInteger('tipo_vehiculo_id')->nullable()->comment('Tipo de vehículo asociado (opcional, para filtrar el UI)');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['mandante_id', 'nombre'], 'sub_tipo_vehiculo_mandante_unique');
            $table->foreign('mandante_id')->references('id')->on('mandantes')->onDelete('cascade');
            $table->foreign('tipo_vehiculo_id')->references('id')->on('tipos_vehiculo')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_tipos_vehiculo_mandante');
    }
};
