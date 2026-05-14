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
        Schema::create('regla_documental_tipo_maquinaria', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('regla_documental_id');
            $table->unsignedBigInteger('tipo_maquinaria_id')->index('regla_documental_tipo_maquinaria_tipo_maquinaria_id_foreign');

            $table->unique(['regla_documental_id', 'tipo_maquinaria_id'], 'regla_tipo_maquinaria_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regla_documental_tipo_maquinaria');
    }
};
