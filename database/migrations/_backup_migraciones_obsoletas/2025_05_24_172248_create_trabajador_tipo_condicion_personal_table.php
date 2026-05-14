<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('trabajador_tipo_condicion_personal', function (Blueprint $table) {
            $table->foreignId('trabajador_id')
                  ->constrained('trabajadores')
                  ->onDelete('cascade');

            // Dando un nombre personalizado al índice de la FK para 'tipo_condicion_personal_id'
            // para evitar que el nombre generado automáticamente sea demasiado largo.
            $table->foreignId('tipo_condicion_personal_id', 'ttcp_tipo_cond_pers_id_foreign')
                  ->constrained('tipos_condicion_personal', 'id', 'ttcp_tipo_cond_pers_constrained')
                  ->onDelete('cascade');

            // Clave primaria compuesta
            $table->primary(
                ['trabajador_id', 'tipo_condicion_personal_id'],
                'pk_trabajador_tipo_cond_pers' // Nombre personalizado para la clave primaria
            );

            // $table->timestamps(); // Opcional
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('trabajador_tipo_condicion_personal');
    }
};