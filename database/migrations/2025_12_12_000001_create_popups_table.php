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
        Schema::create('popups', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 150);
            $table->text('contenido');
            $table->string('archivo_contenido')->nullable(); // Ruta al archivo subido
            $table->json('roles_destino'); // ['ASEM_Admin', 'Contratista_Admin', ...]
            $table->integer('max_visualizaciones')->default(1); // 0 = ilimitado
            $table->boolean('requiere_aceptacion')->default(false);
            $table->text('texto_aceptacion')->nullable(); // "Acepto los términos..."
            $table->enum('tipo_interaccion', ['solo_cerrar', 'requiere_click'])->default('solo_cerrar');
            $table->string('url_destino')->nullable(); // URL opcional al hacer click
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('popups');
    }
};
