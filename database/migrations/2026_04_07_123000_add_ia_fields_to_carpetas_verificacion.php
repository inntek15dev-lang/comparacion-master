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
            if (!Schema::hasColumn('carpetas_verificacion', 'ia_datos_extraidos')) {
                $table->boolean('ia_datos_extraidos')->default(false)->after('estado_revision');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carpetas_verificacion', function (Blueprint $table) {
            $table->dropColumn('ia_datos_extraidos');
        });
    }
};
