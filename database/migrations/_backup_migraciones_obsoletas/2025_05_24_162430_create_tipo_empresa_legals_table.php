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
        Schema::create('tipos_empresa_legal', function (Blueprint $table) {
            $table->id(); // Clave primaria

            $table->string('nombre')->unique(); // Ej: "Sociedad Anónima", "Limitada", "Empresa Individual de Responsabilidad Limitada". Único.
            $table->string('sigla')->nullable()->unique(); // Ej: "S.A.", "Ltda.", "E.I.R.L.". Opcional, pero única si se usa.
            $table->boolean('is_active')->default(true); // Para activar/desactivar esta opción en los listados

            $table->timestamps(); // created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_empresa_legal');
    }
};