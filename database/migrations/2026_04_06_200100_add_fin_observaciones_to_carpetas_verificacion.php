<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carpetas_verificacion', function (Blueprint $table) {
            if (!Schema::hasColumn('carpetas_verificacion', 'fin_observaciones_json')) {
                $table->json('fin_observaciones_json')->nullable()->after('fin_doy_finalizado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('carpetas_verificacion', function (Blueprint $table) {
            $table->dropColumn('fin_observaciones_json');
        });
    }
};
