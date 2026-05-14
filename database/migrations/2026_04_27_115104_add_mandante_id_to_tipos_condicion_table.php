<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_condicion', function (Blueprint $table) {
            // Nullable porque las condiciones existentes no tienen mandante asignado aún.
            // El admin las reasignará desde la pantalla de gestión.
            $table->foreignId('mandante_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('mandantes')
                  ->nullOnDelete();

            // El nombre ya no puede ser globalmente único: dos mandantes distintos pueden
            // tener una condición con el mismo nombre (ej: "TRANSPORTE" en ambos).
            // Eliminamos el UNIQUE global y creamos uno por (mandante_id, nombre).
            $table->dropUnique(['nombre']);
            $table->unique(['mandante_id', 'nombre'], 'tipos_condicion_mandante_nombre_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tipos_condicion', function (Blueprint $table) {
            $table->dropUnique('tipos_condicion_mandante_nombre_unique');
            $table->unique('nombre');
            $table->dropConstrainedForeignId('mandante_id');
        });
    }
};
