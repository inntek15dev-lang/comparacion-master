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
        Schema::table('documento_excepciones_criticidad', function (Blueprint $table) {
            $table->foreign(['mandante_id'])->references(['id'])->on('mandantes')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['nombre_documento_id'])->references(['id'])->on('nombre_documentos')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['created_by_user_id'], 'fk_excepciones_criticidad_user')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documento_excepciones_criticidad', function (Blueprint $table) {
            $table->dropForeign('documento_excepciones_criticidad_mandante_id_foreign');
            $table->dropForeign('documento_excepciones_criticidad_nombre_documento_id_foreign');
            $table->dropForeign('fk_excepciones_criticidad_user');
        });
    }
};
