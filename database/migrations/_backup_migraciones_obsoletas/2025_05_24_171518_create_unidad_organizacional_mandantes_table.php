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
        Schema::create('unidades_organizacionales_mandante', function (Blueprint $table) {
            $table->id(); // Clave primaria

            // A qué mandante pertenece esta estructura organizacional
            $table->foreignId('mandante_id')
                  ->constrained('mandantes')
                  ->onDelete('cascade'); // Si se borra el mandante, se borran sus unidades organizacionales

            $table->string('nombre_unidad'); // Ej: "Empresa A - Operaciones", "Zona Norte", "Abastecimiento"
            $table->string('codigo_unidad')->nullable()->unique(); // Código interno opcional, único si se usa
            $table->text('descripcion')->nullable(); // Descripción opcional de la unidad

            // Para la jerarquía: parent_id apunta a otra fila en esta misma tabla.
            // Si parent_id es NULL, es una unidad de nivel raíz para ese mandante (ej: "Empresa A - Operaciones").
            $table->foreignId('parent_id')
                  ->nullable()
                  ->constrained('unidades_organizacionales_mandante') // Se auto-referencia
                  ->onDelete('restrict'); // Impide borrar una unidad padre si tiene unidades hijas

            $table->boolean('is_active')->default(true); // Para activar/desactivar esta unidad
            $table->timestamps(); // created_at y updated_at

            // Asegura que el nombre de la unidad sea único bajo el mismo padre y para el mismo mandante.
            // El nombre del índice 'uom_mandante_parent_nombre_unique' es para evitar conflictos de longitud.
            $table->unique(['mandante_id', 'parent_id', 'nombre_unidad'], 'uom_mandante_parent_nombre_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('unidades_organizacionales_mandante');
    }
};