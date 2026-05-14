<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega valor_esperado a ia_campos_configuracion.
     * Usado para campos de tipo "criterio" donde el match del sistema
     * compara el valor extraído por la IA contra este valor esperado.
     * La IA SOLO extrae — no compara.
     */
    public function up(): void
    {
        Schema::table('ia_campos_configuracion', function (Blueprint $table) {
            // Valor que el sistema usará para comparar (ej: "Clase A1, A2, B")
            // Solo se usa en campos de tipo criterio. NULL = solo extrae, no compara.
            $table->string('valor_esperado')->nullable()->after('descripcion_ia')
                  ->comment('Valor(es) esperados para match. Solo aplica a campos de tipo criterio.');

            // ID del criterio de evaluación de origen (para trazabilidad)
            $table->unsignedBigInteger('criterio_evaluacion_id')->nullable()->after('valor_esperado')
                  ->comment('FK al criterio_evaluacion_id de origen (solo para campos tipo criterio).');
        });
    }

    public function down(): void
    {
        Schema::table('ia_campos_configuracion', function (Blueprint $table) {
            $table->dropColumn(['valor_esperado', 'criterio_evaluacion_id']);
        });
    }
};
