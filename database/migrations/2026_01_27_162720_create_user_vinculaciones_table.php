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
        Schema::create('user_vinculaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('contratista_unidad_organizacional_id');
            $table->timestamps();
            
            // Índice único para evitar duplicados
            $table->unique(['user_id', 'contratista_unidad_organizacional_id'], 'user_vinc_unique');
            
            // FK a la tabla de vinculaciones
            $table->foreign('contratista_unidad_organizacional_id', 'fk_user_vinc_cuo')
                  ->references('id')
                  ->on('contratista_unidad_organizacional')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_vinculaciones');
    }
};
