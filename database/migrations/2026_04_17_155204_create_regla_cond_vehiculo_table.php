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
        Schema::create('regla_cond_vehiculo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regla_documental_id')
                  ->constrained('reglas_documentales', 'id', 'fk_regla_cond_veh_regla_id')
                  ->cascadeOnDelete();
            
            $table->foreignId('tipo_condicion_vehiculo_id')
                  ->constrained('tipos_condicion_vehiculo', 'id', 'fk_regla_cond_veh_tipo_id')
                  ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regla_cond_vehiculo');
    }
};
