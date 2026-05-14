<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratista_uo_tipo_condicion', function (Blueprint $table) {
            $table->unsignedBigInteger('contratista_uo_id');
            $table->unsignedBigInteger('tipo_condicion_id');

            $table->primary(['contratista_uo_id', 'tipo_condicion_id']);

            $table->foreign('contratista_uo_id')
                  ->references('id')
                  ->on('contratista_unidad_organizacional')
                  ->onDelete('cascade');

            $table->foreign('tipo_condicion_id')
                  ->references('id')
                  ->on('tipos_condicion')
                  ->onDelete('cascade');
        });

        // Migrar datos existentes: tipo_condicion_id del CUO → tabla pivot
        DB::statement("
            INSERT IGNORE INTO contratista_uo_tipo_condicion (contratista_uo_id, tipo_condicion_id)
            SELECT id, tipo_condicion_id
            FROM contratista_unidad_organizacional
            WHERE tipo_condicion_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('contratista_uo_tipo_condicion');
    }
};
