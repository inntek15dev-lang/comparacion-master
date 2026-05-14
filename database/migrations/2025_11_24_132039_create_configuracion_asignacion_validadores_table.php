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
        Schema::create('configuracion_asignacion_validadores', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('configuracion_id');
            $table->unsignedBigInteger('user_id')->index('configuracion_asignacion_validadores_user_id_foreign');
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['configuracion_id', 'user_id'], 'config_asign_valid_config_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracion_asignacion_validadores');
    }
};
