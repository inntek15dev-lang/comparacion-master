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
        Schema::table('solicitudes_complementarias', function (Blueprint $table) {
            // Permitir nulos en la llave foránea antigua para soportar el flujo consolidado 1:N
            $table->unsignedBigInteger('carpeta_trabajador_contingencia_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_complementarias', function (Blueprint $table) {
            $table->unsignedBigInteger('carpeta_trabajador_contingencia_id')->nullable(false)->change();
        });
    }
};
