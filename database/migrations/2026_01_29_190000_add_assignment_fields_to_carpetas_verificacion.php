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
        Schema::table('carpetas_verificacion', function (Blueprint $table) {
            // Campos de asignación
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('analista_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('auditor_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Fechas del flujo
            $table->datetime('fecha_asignacion')->nullable();
            $table->datetime('fecha_inicio_revision')->nullable();
            $table->datetime('fecha_fin_revision')->nullable();
            $table->datetime('fecha_auditoria')->nullable();
            
            // Estado de revisión
            $table->string('estado_revision', 30)->default('PENDIENTE_ASIGNAR');
            // Posibles estados:
            // PENDIENTE_ASIGNAR - Enviado por contratista, esperando asignación
            // ASIGNADO - Asignado a un analista
            // EN_REVISION - Analista está revisando
            // REVISADO - Analista terminó revisión
            // EN_AUDITORIA - Auditor está revisando
            // APROBADO - Aprobado definitivamente
            // RECHAZADO - Rechazado, requiere corrección
            
            // Observaciones
            $table->text('observaciones_analista')->nullable();
            $table->text('observaciones_auditor')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carpetas_verificacion', function (Blueprint $table) {
            $table->dropForeign(['supervisor_id']);
            $table->dropForeign(['analista_id']);
            $table->dropForeign(['auditor_id']);
            
            $table->dropColumn([
                'supervisor_id',
                'analista_id',
                'auditor_id',
                'fecha_asignacion',
                'fecha_inicio_revision',
                'fecha_fin_revision',
                'fecha_auditoria',
                'estado_revision',
                'observaciones_analista',
                'observaciones_auditor',
            ]);
        });
    }
};
