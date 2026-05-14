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
        Schema::table('carpetas_verificacion', function (Blueprint $row) {
            $row->datetime('fecha_emision')->nullable()->after('fecha_envio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carpetas_verificacion', function (Blueprint $row) {
            $row->dropColumn('fecha_emision');
        });
    }
};
