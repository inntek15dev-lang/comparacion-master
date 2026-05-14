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
            // Situación de Trabajadores (Snapshot del periodo)
            if (!Schema::hasColumn('carpetas_verificacion', 'fin_contratados_periodo')) {
                $table->integer('fin_contratados_periodo')->nullable()->after('observaciones_auditor');
            }
            if (!Schema::hasColumn('carpetas_verificacion', 'fin_desvinculados_periodo')) {
                $table->integer('fin_desvinculados_periodo')->nullable()->after('fin_contratados_periodo');
            }
            if (!Schema::hasColumn('carpetas_verificacion', 'fin_total_vigentes')) {
                $table->integer('fin_total_vigentes')->nullable()->after('fin_desvinculados_periodo');
            }
            
            // Datos del Analista
            if (!Schema::hasColumn('carpetas_verificacion', 'fin_trabajadores_revisados')) {
                $table->integer('fin_trabajadores_revisados')->nullable()->after('fin_total_vigentes');
            }
            if (!Schema::hasColumn('carpetas_verificacion', 'fin_remuneraciones_pagadas')) {
                $table->bigInteger('fin_remuneraciones_pagadas')->nullable()->after('fin_trabajadores_revisados');
            }
            if (!Schema::hasColumn('carpetas_verificacion', 'fin_cotizaciones_pagadas')) {
                $table->bigInteger('fin_cotizaciones_pagadas')->nullable()->after('fin_remuneraciones_pagadas');
            }
            
            // Indemnizaciones
            if (!Schema::hasColumn('carpetas_verificacion', 'fin_aviso_previo_trabajadores')) {
                $table->integer('fin_aviso_previo_trabajadores')->nullable()->after('fin_cotizaciones_pagadas');
            }
            if (!Schema::hasColumn('carpetas_verificacion', 'fin_aviso_previo_total')) {
                $table->bigInteger('fin_aviso_previo_total')->nullable()->after('fin_aviso_previo_trabajadores');
            }
            
            if (!Schema::hasColumn('carpetas_verificacion', 'fin_anio_servicio_trabajadores')) {
                $table->integer('fin_anio_servicio_trabajadores')->nullable()->after('fin_aviso_previo_total');
            }
            if (!Schema::hasColumn('carpetas_verificacion', 'fin_anio_servicio_total')) {
                $table->bigInteger('fin_anio_servicio_total')->nullable()->after('fin_anio_servicio_trabajadores');
            }
            
            if (!Schema::hasColumn('carpetas_verificacion', 'fin_feriado_trabajadores')) {
                $table->integer('fin_feriado_trabajadores')->nullable()->after('fin_anio_servicio_total');
            }
            if (!Schema::hasColumn('carpetas_verificacion', 'fin_feriado_total')) {
                $table->bigInteger('fin_feriado_total')->nullable()->after('fin_feriado_trabajadores');
            }
            
            // Estado Final
            if (!Schema::hasColumn('carpetas_verificacion', 'fin_doy_finalizado')) {
                $table->boolean('fin_doy_finalizado')->default(false)->after('fin_feriado_total');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carpetas_verificacion', function (Blueprint $table) {
            $table->dropColumn([
                'fin_contratados_periodo',
                'fin_desvinculados_periodo',
                'fin_total_vigentes',
                'fin_trabajadores_revisados',
                'fin_remuneraciones_pagadas',
                'fin_cotizaciones_pagadas',
                'fin_aviso_previo_trabajadores',
                'fin_aviso_previo_total',
                'fin_anio_servicio_trabajadores',
                'fin_anio_servicio_total',
                'fin_feriado_trabajadores',
                'fin_feriado_total',
                'fin_doy_finalizado'
            ]);
        });
    }
};
