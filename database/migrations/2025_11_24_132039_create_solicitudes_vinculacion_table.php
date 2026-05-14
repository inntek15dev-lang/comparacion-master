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
        Schema::create('solicitudes_vinculacion', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('contratista_id')->index('fk_solicitudes_vinculacion_contratista')->comment('FK a la empresa recién creada en la tabla contratistas');
            $table->enum('tipo_solicitud', ['CONTRATISTA', 'SUBCONTRATISTA'])->comment('Define si la solicitud es para ser contratista directo o subcontratista');
            $table->unsignedBigInteger('mandante_id')->index('fk_solicitudes_vinculacion_mandante')->comment('FK al mandante al que se desea vincular');
            $table->unsignedBigInteger('contratista_padre_id')->nullable()->index('fk_solicitudes_vinculacion_contratista_padre')->comment('FK al contratista principal (solo si tipo_solicitud es SUBCONTRATISTA)');
            $table->string('estado', 50);
            $table->unsignedBigInteger('aprobado_por_user_id')->nullable()->index('fk_solicitudes_vinculacion_aprobador')->comment('FK al usuario (ASEM/Mandante) que aprobó la solicitud');
            $table->dateTime('fecha_aprobacion')->nullable()->comment('Fecha y hora exactas de la aprobación');
            $table->text('motivo_rechazo')->nullable()->comment('Justificación textual en caso de rechazo');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_vinculacion');
    }
};
