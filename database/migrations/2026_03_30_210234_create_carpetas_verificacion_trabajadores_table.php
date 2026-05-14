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
        Schema::create('carpetas_verificacion_trabajadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carpeta_verificacion_id')
                  ->constrained('carpetas_verificacion', 'id', 'cvt_cv_fk')
                  ->onDelete('cascade');
            $table->foreignId('trabajador_vinculacion_id')
                  ->constrained('trabajador_vinculaciones', 'id', 'cvt_tv_fk')
                  ->onDelete('cascade');
            
            $table->string('tipo_registro')->default('VIGENTE'); // VIGENTE, ARRASTRE
            $table->string('estado_revision')->default('PENDIENTE'); // PENDIENTE, APROBADO, FINIQUITADO, MOVIDO
            $table->text('observaciones')->nullable();
            
            $table->timestamps();

            // Índices para velocidad de consulta
            $table->index(['carpeta_verificacion_id', 'estado_revision'], 'cvt_cv_er_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carpetas_verificacion_trabajadores');
    }
};
