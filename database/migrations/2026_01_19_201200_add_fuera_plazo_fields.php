<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Añadir campos al calendario de verificación
        Schema::table('calendario_verificacion', function (Blueprint $table) {
            $table->date('fecha_cierre_fuera_plazo')->nullable()->after('fecha_cierre');
            $table->date('fecha_emision_fuera_plazo')->nullable()->after('fecha_emision');
        });

        // Añadir campos a las carpetas de verificación
        Schema::table('carpetas_verificacion', function (Blueprint $table) {
            $table->enum('tipo_envio', ['NORMAL', 'FUERA_PLAZO', 'FUERA_PERIODO'])->nullable()->after('estado');
            $table->date('fecha_emision_asignada')->nullable()->after('tipo_envio');
            $table->timestamp('fecha_envio')->nullable()->after('fecha_emision_asignada');
        });
    }

    public function down(): void
    {
        Schema::table('calendario_verificacion', function (Blueprint $table) {
            $table->dropColumn(['fecha_cierre_fuera_plazo', 'fecha_emision_fuera_plazo']);
        });

        Schema::table('carpetas_verificacion', function (Blueprint $table) {
            $table->dropColumn(['tipo_envio', 'fecha_emision_asignada', 'fecha_envio']);
        });
    }
};
