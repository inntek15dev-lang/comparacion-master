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
        Schema::create('comunas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // No necesariamente único globalmente, sí dentro de una región
            $table->unsignedBigInteger('region_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('region_id')->references('id')->on('regiones')->onDelete('cascade');
            // Asegurar que la combinación de nombre y region_id sea única
            $table->unique(['nombre', 'region_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comunas');
    }
};