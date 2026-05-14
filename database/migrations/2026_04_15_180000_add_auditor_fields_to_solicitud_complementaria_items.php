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
        Schema::table('solicitud_complementaria_items', function (Blueprint $table) {
            // PENDIENTE, TOTAL (Solución Total), PARCIAL (Solución Parcial), RECHAZADO (Documento no sirve)
            $table->string('estado_auditor')->default('PENDIENTE')->after('carpeta_trabajador_contingencia_id');
            $table->decimal('monto_solucionado', 15, 2)->nullable()->after('estado_auditor');
            $table->text('observaciones_auditor')->nullable()->after('monto_solucionado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitud_complementaria_items', function (Blueprint $table) {
            $table->dropColumn(['estado_auditor', 'monto_solucionado', 'observaciones_auditor']);
        });
    }
};
