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
        Schema::table('carpetas_verificacion_trabajadores', function (Blueprint $table) {
            $table->foreignId('destino_trabajador_vinculacion_id')
                  ->nullable()
                  ->after('trabajador_vinculacion_id')
                  ->constrained('trabajador_vinculaciones', 'id', 'cvt_dest_fk')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carpetas_verificacion_trabajadores', function (Blueprint $table) {
            $table->dropForeign('cvt_dest_fk');
            $table->dropColumn('destino_trabajador_vinculacion_id');
        });
    }
};
