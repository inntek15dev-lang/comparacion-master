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
        Schema::create('unidades_organizacionales_mandante', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('mandante_id');
            $table->string('nombre_unidad');
            $table->string('codigo_unidad')->nullable()->unique();
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable()->index('unidades_organizacionales_mandante_parent_id_foreign');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['mandante_id', 'parent_id', 'nombre_unidad'], 'uom_mandante_parent_nombre_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidades_organizacionales_mandante');
    }
};
