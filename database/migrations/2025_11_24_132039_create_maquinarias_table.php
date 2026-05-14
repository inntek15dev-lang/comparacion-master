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
        Schema::create('maquinarias', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('contratista_id');
            $table->string('identificador_letras', 20);
            $table->string('identificador_numeros', 20);
            $table->year('ano_fabricacion');
            $table->unsignedBigInteger('marca_vehiculo_id')->nullable()->index('maquinarias_marca_vehiculo_id_foreign');
            $table->unsignedBigInteger('tipo_maquinaria_id')->nullable()->index('maquinarias_tipo_maquinaria_id_foreign');
            $table->unsignedBigInteger('tenencia_vehiculo_id')->nullable()->index('maquinarias_tenencia_vehiculo_id_foreign');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['contratista_id', 'identificador_letras', 'identificador_numeros'], 'maquinaria_identificador_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maquinarias');
    }
};
