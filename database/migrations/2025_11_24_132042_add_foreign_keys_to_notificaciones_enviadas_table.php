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
        Schema::table('notificaciones_enviadas', function (Blueprint $table) {
            $table->foreign(['contratista_id'])->references(['id'])->on('contratistas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['despachado_por_user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notificaciones_enviadas', function (Blueprint $table) {
            $table->dropForeign('notificaciones_enviadas_contratista_id_foreign');
            $table->dropForeign('notificaciones_enviadas_despachado_por_user_id_foreign');
        });
    }
};
