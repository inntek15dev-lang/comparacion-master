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
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('contratista_id');
            $table->string('patente_letras', 4);
            $table->string('patente_numeros', 4);
            $table->year('ano_fabricacion');
            $table->unsignedBigInteger('marca_vehiculo_id')->nullable()->index('vehiculos_marca_vehiculo_id_foreign');
            $table->unsignedBigInteger('color_vehiculo_id')->nullable()->index('vehiculos_color_vehiculo_id_foreign');
            $table->unsignedBigInteger('tipo_vehiculo_id')->nullable()->index('vehiculos_tipo_vehiculo_id_foreign');
            $table->unsignedBigInteger('tenencia_vehiculo_id')->nullable()->index('vehiculos_tenencia_vehiculo_id_foreign');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['contratista_id', 'patente_letras', 'patente_numeros'], 'vehiculo_patente_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
