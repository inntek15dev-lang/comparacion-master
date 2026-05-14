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
        Schema::table('regla_documental_nacionalidad', function (Blueprint $table) {
            $table->foreign(['nacionalidad_id'])->references(['id'])->on('nacionalidades')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['regla_documental_id'])->references(['id'])->on('reglas_documentales')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regla_documental_nacionalidad', function (Blueprint $table) {
            $table->dropForeign('regla_documental_nacionalidad_nacionalidad_id_foreign');
            $table->dropForeign('regla_documental_nacionalidad_regla_documental_id_foreign');
        });
    }
};
