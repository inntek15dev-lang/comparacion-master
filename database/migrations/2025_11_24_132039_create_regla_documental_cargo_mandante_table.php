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
        Schema::create('regla_documental_cargo_mandante', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('regla_documental_id');
            $table->unsignedBigInteger('cargo_mandante_id')->index('regla_documental_cargo_mandante_cargo_mandante_id_foreign');

            $table->unique(['regla_documental_id', 'cargo_mandante_id'], 'regla_cargo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regla_documental_cargo_mandante');
    }
};
