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
        Schema::table('mandante_color_configuraciones', function (Blueprint $table) {
            $table->foreign(['mandante_id'])->references(['id'])->on('mandantes')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mandante_color_configuraciones', function (Blueprint $table) {
            $table->dropForeign('mandante_color_configuraciones_mandante_id_foreign');
        });
    }
};
