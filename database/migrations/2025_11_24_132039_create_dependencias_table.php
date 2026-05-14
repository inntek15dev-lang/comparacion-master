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
        Schema::create('dependencias', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('mandante_id')->index('dependencias_mandante_id_foreign');
            $table->string('nombre');
            $table->unsignedBigInteger('dependencia_padre_id')->nullable()->index('dependencias_dependencia_padre_id_foreign');
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dependencias');
    }
};
