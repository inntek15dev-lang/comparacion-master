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
        Schema::create('popup_visualizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('popup_id')->constrained('popups')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('veces_mostrado')->default(1);
            $table->boolean('acepto_condiciones')->default(false);
            $table->timestamp('ultima_visualizacion')->useCurrent();
            $table->timestamps();
            
            $table->unique(['popup_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('popup_visualizaciones');
    }
};
