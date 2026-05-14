<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('sub_criterios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique(); // Columna para el nombre, debe ser único
            $table->boolean('is_active')->default(true); // Columna para el estado
            $table->timestamps(); // created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_criterios');
    }
};