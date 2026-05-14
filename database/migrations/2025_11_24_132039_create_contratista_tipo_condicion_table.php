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
        Schema::create('contratista_tipo_condicion', function (Blueprint $table) {
            $table->unsignedBigInteger('contratista_id');
            $table->unsignedBigInteger('tipo_condicion_id')->index('contratista_tipo_condicion_tipo_condicion_id_foreign');

            $table->primary(['contratista_id', 'tipo_condicion_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratista_tipo_condicion');
    }
};
