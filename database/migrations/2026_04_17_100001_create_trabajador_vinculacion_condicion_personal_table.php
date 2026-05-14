<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trabajador_vinculacion_condicion_personal', function (Blueprint $table) {
            $table->unsignedBigInteger('trabajador_vinculacion_id');
            $table->unsignedBigInteger('tipo_condicion_personal_id');

            $table->primary(['trabajador_vinculacion_id', 'tipo_condicion_personal_id']);

            $table->foreign('trabajador_vinculacion_id', 'tvcp_vinc_fk')
                  ->references('id')
                  ->on('trabajador_vinculaciones')
                  ->onDelete('cascade');

            $table->foreign('tipo_condicion_personal_id', 'tvcp_cond_fk')
                  ->references('id')
                  ->on('tipos_condicion_personal')
                  ->onDelete('cascade');
        });

        // Migrar datos existentes: tipo_condicion_personal_id (único) → tabla pivot
        DB::statement("
            INSERT IGNORE INTO trabajador_vinculacion_condicion_personal (trabajador_vinculacion_id, tipo_condicion_personal_id)
            SELECT id, tipo_condicion_personal_id
            FROM trabajador_vinculaciones
            WHERE tipo_condicion_personal_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('trabajador_vinculacion_condicion_personal');
    }
};
