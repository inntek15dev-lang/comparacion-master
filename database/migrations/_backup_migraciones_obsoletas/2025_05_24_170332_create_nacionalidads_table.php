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
        Schema::create('nacionalidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique(); // Ej: "Chilena", "Argentina", "Peruana"
            $table->string('codigo_iso_3166_1_alpha_2')->nullable()->unique(); // Opcional: Código de país de 2 letras (CL, AR, PE)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('nacionalidades');
    }
};