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
        Schema::create('embarcaciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('contratista_id');
            $table->string('matricula_letras', 10);
            $table->string('matricula_numeros', 10);
            $table->year('ano_fabricacion');
            $table->unsignedBigInteger('tipo_embarcacion_id')->nullable()->index('embarcaciones_tipo_embarcacion_id_foreign');
            $table->unsignedBigInteger('tenencia_vehiculo_id')->nullable()->index('embarcaciones_tenencia_vehiculo_id_foreign');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['contratista_id', 'matricula_letras', 'matricula_numeros'], 'embarcacion_matricula_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('embarcaciones');
    }
};
