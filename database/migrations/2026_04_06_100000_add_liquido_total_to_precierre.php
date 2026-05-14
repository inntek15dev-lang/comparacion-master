<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carpetas_verificacion', function (Blueprint $table) {
            if (!Schema::hasColumn('carpetas_verificacion', 'fin_liquido_total')) {
                $table->bigInteger('fin_liquido_total')->nullable()->after('fin_cotizaciones_pagadas');
            }
        });
    }

    public function down(): void
    {
        Schema::table('carpetas_verificacion', function (Blueprint $table) {
            if (Schema::hasColumn('carpetas_verificacion', 'fin_liquido_total')) {
                $table->dropColumn('fin_liquido_total');
            }
        });
    }
};
