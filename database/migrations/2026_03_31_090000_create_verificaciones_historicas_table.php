<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla para almacenar los resultados históricos de verificación de dotación anterior.
     * El id_registro es el mismo que existe en contratista_unidad_organizacional.id_registro,
     * único por contratista dentro de un Mandante y compartido entre sus vinculaciones.
     *
     * REGLA CRÍTICA: Las retenciones son período-específicas y NO se arrastran.
     * Esta tabla es sólo de referencia histórica para el snapshot inicial.
     */
    public function up(): void
    {
        Schema::create('verificaciones_historicas', function (Blueprint $table) {
            $table->id();

            // Identificador del contratista en el sistema (igual a contratista_unidad_organizacional.id_registro)
            $table->string('id_registro', 100);

            // Mandante al que pertenece
            $table->foreignId('mandante_id')
                  ->constrained('mandantes', 'id', 'vh_mandante_fk')
                  ->onDelete('restrict');

            // Datos del lugar y contrato (desnormalizados para consulta histórica)
            $table->string('lugar', 255)->comment('Nombre del Lugar de Trabajo / Dependencia');
            $table->string('contrato', 100)->comment('Número de contrato');

            // Período verificado
            $table->smallInteger('periodo_anio');
            $table->tinyInteger('periodo_mes'); // 1-12

            // Resultado de verificación (NOT NULL: la dotación anterior ya fue verificada)
            $table->enum('resultado', ['Limpio', 'Obs', 'Contingencia', 'Ambos'])
                  ->comment('Limpio=sin obs ni ret; Obs=con observación; Contingencia=retención; Ambos=obs+ret');

            // Montos período (CLP, sólo presentes en casos Contingencia o Ambos)
            $table->bigInteger('monto_retenible')->nullable()->comment('Monto CLP sujeto a retención');
            $table->bigInteger('monto_no_retenible')->nullable()->comment('Monto CLP liberado de retención');

            // Auditoría
            $table->foreignId('importado_por')
                  ->nullable()
                  ->constrained('users', 'id', 'vh_user_fk')
                  ->onDelete('set null');

            $table->timestamps();

            // Índice único: un resultado por id_registro + mandante + período
            $table->unique(
                ['id_registro', 'mandante_id', 'periodo_anio', 'periodo_mes'],
                'vh_unique_registro_periodo'
            );

            // Índices de consulta
            $table->index(['id_registro', 'mandante_id'], 'vh_registro_mandante_idx');
            $table->index(['periodo_anio', 'periodo_mes'], 'vh_periodo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verificaciones_historicas');
    }
};
