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
        Schema::create('vehiculo_vinc_condicion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_asignacion_id')
                  ->constrained('vehiculo_asignaciones', 'id', 'fk_veh_vinc_cond_asignacion_id')
                  ->onDelete('cascade');
            $table->foreignId('tipo_condicion_vehiculo_id')
                  ->constrained('tipos_condicion_vehiculo', 'id', 'fk_veh_vinc_cond_tipo_id')
                  ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculo_vinc_condicion');
    }
};
