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
        Schema::create('onboarding_contratista', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('contratista_id')->unique('contratista_id')->comment('FK a la empresa contratista en proceso de onboarding');
            $table->boolean('paso1_capacitacion_completo')->default(false);
            $table->dateTime('paso1_fecha')->nullable();
            $table->unsignedBigInteger('paso1_user_id')->nullable()->index('fk_onboarding_paso1_user');
            $table->text('paso1_comentario')->nullable();
            $table->boolean('paso2_prueba_carga_completo')->default(false);
            $table->dateTime('paso2_fecha')->nullable();
            $table->unsignedBigInteger('paso2_user_id')->nullable()->index('fk_onboarding_paso2_user');
            $table->text('paso2_comentario')->nullable();
            $table->boolean('paso3_generico_completo')->default(false);
            $table->dateTime('paso3_fecha')->nullable();
            $table->unsignedBigInteger('paso3_user_id')->nullable()->index('fk_onboarding_paso3_user');
            $table->text('paso3_comentario')->nullable();
            $table->boolean('paso4_generico_completo')->default(false);
            $table->dateTime('paso4_fecha')->nullable();
            $table->unsignedBigInteger('paso4_user_id')->nullable()->index('fk_onboarding_paso4_user');
            $table->text('paso4_comentario')->nullable();
            $table->boolean('paso5_generico_completo')->default(false);
            $table->dateTime('paso5_fecha')->nullable();
            $table->unsignedBigInteger('paso5_user_id')->nullable()->index('fk_onboarding_paso5_user');
            $table->text('paso5_comentario')->nullable();
            $table->boolean('paso6_generico_completo')->default(false);
            $table->dateTime('paso6_fecha')->nullable();
            $table->unsignedBigInteger('paso6_user_id')->nullable()->index('fk_onboarding_paso6_user');
            $table->text('paso6_comentario')->nullable();
            $table->boolean('paso7_generico_completo')->default(false);
            $table->dateTime('paso7_fecha')->nullable();
            $table->unsignedBigInteger('paso7_user_id')->nullable()->index('fk_onboarding_paso7_user');
            $table->text('paso7_comentario')->nullable();
            $table->enum('estado_onboarding', ['En Proceso', 'Completado', 'Archivado'])->default('En Proceso');
            $table->text('comentarios_proceso')->nullable()->comment('Bitácora de comentarios y notas del ASEM Admin durante el proceso de onboarding');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_contratista');
    }
};
