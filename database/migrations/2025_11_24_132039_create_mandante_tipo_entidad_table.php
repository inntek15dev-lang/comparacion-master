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
        Schema::create('mandante_tipo_entidad', function (Blueprint $table) {
            $table->unsignedBigInteger('mandante_id');
            $table->unsignedBigInteger('tipo_entidad_controlable_id')->index('mandante_tipo_entidad_tipo_entidad_controlable_id_foreign');

            $table->primary(['mandante_id', 'tipo_entidad_controlable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mandante_tipo_entidad');
    }
};
