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
        Schema::table('calendario_verificacion', function (Blueprint $table) {
            $table->date('fecha_emision')->nullable()->after('fecha_cierre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendario_verificacion', function (Blueprint $table) {
            $table->dropColumn('fecha_emision');
        });
    }
};
