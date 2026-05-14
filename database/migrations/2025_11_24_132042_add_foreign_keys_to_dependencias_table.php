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
        Schema::table('dependencias', function (Blueprint $table) {
            $table->foreign(['dependencia_padre_id'])->references(['id'])->on('dependencias')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['mandante_id'])->references(['id'])->on('mandantes')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dependencias', function (Blueprint $table) {
            $table->dropForeign('dependencias_dependencia_padre_id_foreign');
            $table->dropForeign('dependencias_mandante_id_foreign');
        });
    }
};
