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
        Schema::create('regla_documental_criterios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regla_documental_id')->constrained('reglas_documentales')->onDelete('cascade');
            $table->foreignId('criterio_evaluacion_id')->constrained('criterios_evaluacion')->onDelete('restrict');
            $table->foreignId('sub_criterio_id')->nullable()->constrained('sub_criterios')->onDelete('set null');
            $table->foreignId('texto_rechazo_id')->nullable()->constrained('textos_rechazo')->onDelete('set null');
            $table->foreignId('aclaracion_criterio_id')->nullable()->constrained('aclaraciones_criterio')->onDelete('set null');
            $table->integer('orden')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regla_documental_criterios');
    }
};