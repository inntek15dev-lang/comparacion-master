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
            // Datos de Cierre (Finalización)
            $table->date('fin_fecha_recepcion_doc')->nullable()->after('observaciones_auditor');
            $table->date('fin_fecha_recepcion_planilla')->nullable()->after('fin_fecha_recepcion_doc');
            $table->boolean('fin_sin_planilla')->default(false)->after('fin_fecha_recepcion_planilla');
            $table->integer('fin_horas_hombre')->nullable()->after('fin_sin_planilla');
            $table->bigInteger('fin_remuneraciones_pagadas')->nullable()->after('fin_horas_hombre');
            $table->bigInteger('fin_cotizaciones_pagadas')->nullable()->after('fin_remuneraciones_pagadas');
            
            // Indemnizaciones
            $table->integer('fin_aviso_previo_trabajadores')->nullable()->after('fin_cotizaciones_pagadas');
            $table->bigInteger('fin_aviso_previo_total')->nullable()->after('fin_aviso_previo_trabajadores');
            $table->integer('fin_anio_servicio_trabajadores')->nullable()->after('fin_aviso_previo_total');
            $table->bigInteger('fin_anio_servicio_total')->nullable()->after('fin_anio_servicio_trabajadores');
            $table->integer('fin_feriado_trabajadores')->nullable()->after('fin_anio_servicio_total');
            $table->bigInteger('fin_feriado_total')->nullable()->after('fin_feriado_trabajadores');
            
            // Confirmación y Estado Final
            $table->integer('fin_confirmacion_interna')->nullable()->after('fin_feriado_total');
            $table->boolean('fin_doy_finalizado')->default(false)->after('fin_confirmacion_interna');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carpetas_verificacion', function (Blueprint $table) {
            $table->dropColumn([
                'fin_fecha_recepcion_doc',
                'fin_fecha_recepcion_planilla',
                'fin_sin_planilla',
                'fin_horas_hombre',
                'fin_remuneraciones_pagadas',
                'fin_cotizaciones_pagadas',
                'fin_aviso_previo_trabajadores',
                'fin_aviso_previo_total',
                'fin_anio_servicio_trabajadores',
                'fin_anio_servicio_total',
                'fin_feriado_trabajadores',
                'fin_feriado_total',
                'fin_confirmacion_interna',
                'fin_doy_finalizado'
            ]);
        });
    }
};
