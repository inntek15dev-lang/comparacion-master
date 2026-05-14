<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_auditoria_items', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['observacion', 'contingencia']);
            $table->string('texto', 500);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_auditoria_items');
    }
};
