<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Este refactor consolida el flujo de solicitudes complementarias:
     * - Antes: 1 SolicitudComplementaria → 1 CódigoIncidencia (1:1)
     * - Ahora: 1 SolicitudComplementaria → N CódigoIncidencias (1:N via items)
     *
     * Se agrega carpeta_verificacion_id para agrupar por certificado/carpeta.
     * Los registros históricos (carpeta_verificacion_id = NULL) siguen funcionando.
     */
    public function up(): void
    {
        // 1. Agregar carpeta_verificacion_id a la tabla principal
        Schema::table('solicitudes_complementarias', function (Blueprint $table) {
            $table->unsignedBigInteger('carpeta_verificacion_id')
                  ->nullable()
                  ->after('carpeta_trabajador_contingencia_id')
                  ->comment('NULL = registro histórico (flujo viejo 1:1). NOT NULL = nuevo flujo consolidado.');

            $table->foreign('carpeta_verificacion_id', 'fk_sol_comp_carpeta_v')
                  ->references('id')
                  ->on('carpetas_verificacion')
                  ->onDelete('set null');
        });

        // 2. Crear tabla pivote para los ítems del complementario consolidado
        Schema::create('solicitud_complementaria_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('solicitud_complementaria_id');
            $table->foreign('solicitud_complementaria_id', 'fk_sci_solicitud')
                  ->references('id')
                  ->on('solicitudes_complementarias')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('carpeta_trabajador_contingencia_id');
            $table->foreign('carpeta_trabajador_contingencia_id', 'fk_sci_contingencia')
                  ->references('id')
                  ->on('carpeta_trabajador_contingencias')
                  ->onDelete('cascade');

            $table->timestamps();

            // Evitar duplicados: el mismo código no puede aparecer 2 veces en la misma solicitud
            $table->unique(
                ['solicitud_complementaria_id', 'carpeta_trabajador_contingencia_id'],
                'uq_sci_sol_cont'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_complementaria_items');

        Schema::table('solicitudes_complementarias', function (Blueprint $table) {
            $table->dropForeign('fk_sol_comp_carpeta_v');
            $table->dropColumn('carpeta_verificacion_id');
        });
    }
};
