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
        Schema::table('vehiculo_unidad_organizacional', function (Blueprint $table) {
            $table->foreign(['vehiculo_id'])->references(['id'])->on('vehiculos')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehiculo_unidad_organizacional', function (Blueprint $table) {
            $table->dropForeign('vehiculo_unidad_organizacional_vehiculo_id_foreign');
        });
    }
};
