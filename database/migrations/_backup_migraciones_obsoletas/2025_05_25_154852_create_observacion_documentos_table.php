<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observaciones_documento', function (Blueprint $table) {
            $table->id();
            $table->text('titulo'); // Cambiado a TEXT, sin restricción de unicidad en DB
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observaciones_documento');
    }
};