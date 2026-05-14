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
        Schema::create('regla_documental_tipo_vehiculo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('regla_documental_id');
            $table->unsignedBigInteger('tipo_vehiculo_id')->index('regla_documental_tipo_vehiculo_tipo_vehiculo_id_foreign');

            $table->unique(['regla_documental_id', 'tipo_vehiculo_id'], 'regla_tipo_vehiculo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regla_documental_tipo_vehiculo');
    }
};
