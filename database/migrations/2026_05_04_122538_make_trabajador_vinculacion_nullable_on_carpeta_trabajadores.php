<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permite importar períodos históricos desde certificados PDF sin necesidad
     * de tener al trabajador registrado en TrabajadorVinculacion.
     * Los registros históricos usan solo los campos snapshot_* para identificar
     * al trabajador, con trabajador_vinculacion_id = NULL.
     */
    public function up(): void
    {
        Schema::table('carpetas_verificacion_trabajadores', function (Blueprint $table) {
            // Primero eliminamos la FK constraint existente
            $table->dropForeign('cvt_tv_fk');
            // Luego hacemos la columna nullable y reestablecemos FK con nullOnDelete
            $table->unsignedBigInteger('trabajador_vinculacion_id')->nullable()->change();
            $table->foreign('trabajador_vinculacion_id', 'cvt_tv_fk')
                  ->references('id')
                  ->on('trabajador_vinculaciones')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('carpetas_verificacion_trabajadores', function (Blueprint $table) {
            $table->dropForeign('cvt_tv_fk');
            $table->unsignedBigInteger('trabajador_vinculacion_id')->nullable(false)->change();
            $table->foreign('trabajador_vinculacion_id', 'cvt_tv_fk')
                  ->references('id')
                  ->on('trabajador_vinculaciones')
                  ->onDelete('cascade');
        });
    }
};
