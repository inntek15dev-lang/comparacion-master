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
        Schema::create('regla_documental_nacionalidad', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('regla_documental_id');
            $table->unsignedBigInteger('nacionalidad_id');
            // Timestamps no son estrictamente necesarios para esta tabla pivote simple.

            $table->foreign('regla_documental_id')
                  ->references('id')
                  ->on('reglas_documentales')
                  ->onDelete('cascade');

            $table->foreign('nacionalidad_id')
                  ->references('id')
                  ->on('nacionalidades')
                  ->onDelete('cascade');

            $table->unique(['regla_documental_id', 'nacionalidad_id'], 'regla_nacionalidad_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regla_documental_nacionalidad');
    }
};