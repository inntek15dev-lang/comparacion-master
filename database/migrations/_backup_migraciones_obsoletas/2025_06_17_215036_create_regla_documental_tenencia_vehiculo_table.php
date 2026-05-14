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
        Schema::create('regla_documental_tenencia_vehiculo', function (Blueprint $table) {
            $table->unsignedBigInteger('regla_documental_id');
            $table->unsignedBigInteger('tenencia_vehiculo_id');

            // Definición de claves foráneas con nombres de constraint cortos y explícitos
            $table->foreign('regla_documental_id', 'regla_ten_regla_id_foreign')
                  ->references('id')
                  ->on('reglas_documentales')
                  ->onDelete('cascade');

            $table->foreign('tenencia_vehiculo_id', 'regla_ten_tenencia_id_foreign')
                  ->references('id')
                  ->on('tenencias_vehiculo')
                  ->onDelete('cascade');

            // Definición de la clave primaria compuesta
            $table->primary(['regla_documental_id', 'tenencia_vehiculo_id'], 'regla_tenencia_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regla_documental_tenencia_vehiculo');
    }
};