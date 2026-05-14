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
        Schema::create('regla_documental_tipo_embarcacion', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('regla_documental_id');
            $table->unsignedBigInteger('tipo_embarcacion_id')->index('regla_documental_tipo_embarcacion_tipo_embarcacion_id_foreign');

            $table->unique(['regla_documental_id', 'tipo_embarcacion_id'], 'regla_tipo_embarcacion_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regla_documental_tipo_embarcacion');
    }
};
