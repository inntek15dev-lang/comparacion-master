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
        Schema::create('trabajador_dependencia', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('trabajador_id');
            $table->unsignedBigInteger('dependencia_id')->index('trabajador_dependencia_dependencia_id_foreign');
            $table->timestamps();

            $table->unique(['trabajador_id', 'dependencia_id'], 'trabajador_dependencia_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trabajador_dependencia');
    }
};
