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
        Schema::table('unidades_organizacionales_mandante', function (Blueprint $table) {
            $table->foreign(['mandante_id'])->references(['id'])->on('mandantes')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['parent_id'])->references(['id'])->on('unidades_organizacionales_mandante')->onUpdate('no action')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unidades_organizacionales_mandante', function (Blueprint $table) {
            $table->dropForeign('unidades_organizacionales_mandante_mandante_id_foreign');
            $table->dropForeign('unidades_organizacionales_mandante_parent_id_foreign');
        });
    }
};
