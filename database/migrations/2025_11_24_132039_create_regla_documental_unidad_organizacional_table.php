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
        Schema::create('regla_documental_unidad_organizacional', function (Blueprint $table) {
            $table->unsignedBigInteger('regla_documental_id');
            $table->unsignedBigInteger('unidad_organizacional_mandante_id');

            $table->primary(['regla_documental_id', 'unidad_organizacional_mandante_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regla_documental_unidad_organizacional');
    }
};
