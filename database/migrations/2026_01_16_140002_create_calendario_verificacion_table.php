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
        Schema::create('calendario_verificacion', function (Blueprint $table) {
            $table->id();
            $table->integer('anio');
            $table->integer('mes');
            $table->date('fecha_apertura');
            $table->date('fecha_cierre');
            $table->timestamps();
            
            $table->unique(['anio', 'mes']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendario_verificacion');
    }
};
