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
        Schema::table('trabajador_vinculaciones', function (Blueprint $table) {
            $table->foreign(['dependencia_id'])->references(['id'])->on('dependencias')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajador_vinculaciones', function (Blueprint $table) {
            $table->dropForeign('trabajador_vinculaciones_dependencia_id_foreign');
        });
    }
};
