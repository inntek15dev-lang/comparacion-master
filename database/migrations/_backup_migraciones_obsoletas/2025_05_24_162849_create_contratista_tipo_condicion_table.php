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
        Schema::create('contratista_tipo_condicion', function (Blueprint $table) {
            // No necesitamos un $table->id() aquí a menos que la relación tenga atributos propios importantes
            // o vayamos a referenciarla directamente, lo cual es raro para una tabla pivote simple.
            // Una clave primaria compuesta por las dos claves foráneas es común.

            $table->foreignId('contratista_id')->constrained('contratistas')->onDelete('cascade');
            // onDelete('cascade') significa que si se borra un contratista,
            // sus entradas en esta tabla pivote también se borran.

            $table->foreignId('tipo_condicion_id')->constrained('tipos_condicion')->onDelete('cascade');
            // onDelete('cascade') significa que si se borra un tipo_condicion,
            // sus entradas en esta tabla pivote también se borran.

            // Definir una clave primaria compuesta para asegurar que cada par contratista-condicion sea único
            $table->primary(['contratista_id', 'tipo_condicion_id']);

            // $table->timestamps(); // Opcional para tablas pivote, si quieres saber cuándo se creó/actualizó la relación.
                                  // Por ahora, lo omitiremos para simplificar.
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('contratista_tipo_condicion');
    }
};