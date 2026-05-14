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
        Schema::table('trabajador_dependencia', function (Blueprint $table) {
            $table->foreign(['dependencia_id'])->references(['id'])->on('dependencias')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['trabajador_id'])->references(['id'])->on('trabajadores')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajador_dependencia', function (Blueprint $table) {
            $table->dropForeign('trabajador_dependencia_dependencia_id_foreign');
            $table->dropForeign('trabajador_dependencia_trabajador_id_foreign');
        });
    }
};
