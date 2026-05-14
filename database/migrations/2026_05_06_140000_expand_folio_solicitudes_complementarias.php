<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ampliar la columna folio de solicitudes_complementarias de VARCHAR(20) a VARCHAR(30)
     * para soportar el formato SC-{id_registro}-{anio}-{mes}-{id} (ej: SC-909253-2024-10-0016 = 23 chars)
     */
    public function up(): void
    {
        // 1. Eliminar el índice único existente para poder modificar la columna
        Schema::table('solicitudes_complementarias', function (Blueprint $table) {
            $table->dropUnique(['folio']);
        });

        // 2. Ampliar la columna de 20 → 30 caracteres
        Schema::table('solicitudes_complementarias', function (Blueprint $table) {
            $table->string('folio', 30)->nullable()->change();
        });

        // 3. Volver a agregar el índice único
        Schema::table('solicitudes_complementarias', function (Blueprint $table) {
            $table->unique('folio');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_complementarias', function (Blueprint $table) {
            $table->string('folio', 20)->nullable()->unique()->change();
        });
    }
};
