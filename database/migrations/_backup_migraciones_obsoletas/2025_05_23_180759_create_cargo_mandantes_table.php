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
        Schema::create('cargos_mandante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mandante_id')->constrained('mandantes')->onDelete('cascade');
            $table->string('nombre_cargo');
            $table->text('descripcion')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['mandante_id', 'nombre_cargo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cargos_mandante');
    }
};