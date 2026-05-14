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
        Schema::table('configuracion_asignacion_validadores', function (Blueprint $table) {
            $table->foreign(['configuracion_id'])->references(['id'])->on('configuraciones_asignacion_automatica')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_asignacion_validadores', function (Blueprint $table) {
            $table->dropForeign('configuracion_asignacion_validadores_configuracion_id_foreign');
            $table->dropForeign('configuracion_asignacion_validadores_user_id_foreign');
        });
    }
};
