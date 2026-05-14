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
        Schema::create('contratista_dependencia', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('contratista_id')->index('contratista_dependencia_contratista_id_foreign');
            $table->unsignedBigInteger('dependencia_id')->index('contratista_dependencia_dependencia_id_foreign');
            $table->timestamps();

            $table->unique(['contratista_id', 'dependencia_id'], 'contratista_dependencia_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratista_dependencia');
    }
};
