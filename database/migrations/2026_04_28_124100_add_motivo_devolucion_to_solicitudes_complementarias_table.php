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
        Schema::table('solicitudes_complementarias', function (Blueprint $table) {
            $table->text('motivo_devolucion')->nullable()->after('observaciones_auditor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_complementarias', function (Blueprint $table) {
            $table->dropColumn('motivo_devolucion');
        });
    }
};
