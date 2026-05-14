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
        Schema::table('vehiculo_asignaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('sub_tipo_vehiculo_mandante_id')
                  ->nullable()
                  ->after('dependencia_id')
                  ->comment('Sub-tipo de vehículo según el mandante (análogo al cargo en trabajadores)');

            $table->foreign('sub_tipo_vehiculo_mandante_id')
                  ->references('id')
                  ->on('sub_tipos_vehiculo_mandante')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('vehiculo_asignaciones', function (Blueprint $table) {
            $table->dropForeign(['sub_tipo_vehiculo_mandante_id']);
            $table->dropColumn('sub_tipo_vehiculo_mandante_id');
        });
    }
};
