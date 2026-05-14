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
            $table->bigIncrements('id');
            $table->unsignedBigInteger('regla_documental_id')->index('regla_documental_criterios_regla_documental_id_foreign');
            $table->enum('fuente_validacion', ['asem', 'mandante'])->default('asem');
            $table->unsignedBigInteger('criterio_evaluacion_id')->index('regla_documental_criterios_criterio_evaluacion_id_foreign');
            $table->unsignedBigInteger('sub_criterio_id')->nullable()->index('regla_documental_criterios_sub_criterio_id_foreign');
            $table->unsignedBigInteger('texto_rechazo_id')->nullable()->index('regla_documental_criterios_texto_rechazo_id_foreign');
            $table->unsignedBigInteger('aclaracion_criterio_id')->nullable()->index('regla_documental_criterios_aclaracion_criterio_id_foreign');
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
