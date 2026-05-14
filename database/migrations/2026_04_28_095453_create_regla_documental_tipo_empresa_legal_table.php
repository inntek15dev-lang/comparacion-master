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
        Schema::create('regla_documental_tipo_empresa_legal', function (Blueprint $table) {
            $table->unsignedBigInteger('regla_documental_id');
            $table->unsignedBigInteger('tipo_empresa_legal_id');

            $table->foreign('regla_documental_id', 'rd_tel_rd_fk')->references('id')->on('reglas_documentales')->onDelete('cascade');
            $table->foreign('tipo_empresa_legal_id', 'rd_tel_tel_fk')->references('id')->on('tipos_empresa_legal')->onDelete('cascade');
            
            $table->unique(['regla_documental_id', 'tipo_empresa_legal_id'], 'rd_tel_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regla_documental_tipo_empresa_legal');
    }
};
