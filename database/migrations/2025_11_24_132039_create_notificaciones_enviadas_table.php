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
        Schema::create('notificaciones_enviadas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tipo_notificacion')->comment('Ej: Manual, Vencimiento Automatico');
            $table->unsignedBigInteger('contratista_id')->index('notificaciones_enviadas_contratista_id_foreign');
            $table->string('email_destino');
            $table->string('asunto');
            $table->text('mensaje');
            $table->json('documentos_notificados_ids');
            $table->unsignedBigInteger('despachado_por_user_id')->nullable()->index('notificaciones_enviadas_despachado_por_user_id_foreign')->comment('ID del usuario que inició la acción manual');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones_enviadas');
    }
};
