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
        Schema::create('mandante_color_configuraciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('mandante_id')->index('mandante_color_configuraciones_mandante_id_foreign');
            $table->integer('horas_inicio');
            $table->integer('horas_fin');
            $table->string('color_fondo', 50);
            $table->string('color_texto', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mandante_color_configuraciones');
    }
};
