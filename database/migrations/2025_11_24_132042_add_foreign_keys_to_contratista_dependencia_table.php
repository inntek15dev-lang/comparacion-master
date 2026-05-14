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
        Schema::table('contratista_dependencia', function (Blueprint $table) {
            $table->foreign(['contratista_id'])->references(['id'])->on('contratistas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['dependencia_id'])->references(['id'])->on('dependencias')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratista_dependencia', function (Blueprint $table) {
            $table->dropForeign('contratista_dependencia_contratista_id_foreign');
            $table->dropForeign('contratista_dependencia_dependencia_id_foreign');
        });
    }
};
