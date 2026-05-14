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
        Schema::create('regla_documental_tipo_permanencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regla_documental_id')->constrained('reglas_documentales')->onDelete('cascade');
            $table->foreignId('tipo_permanencia_id')->constrained('tipos_permanencias')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regla_documental_tipo_permanencia');
    }
};
