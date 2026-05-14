<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trabajador_vinculaciones', function (Blueprint $table) {
            // Drop constraint if needed, but change() handles it in most modern Laravel versions
            $table->unsignedBigInteger('unidad_organizacional_mandante_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trabajador_vinculaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('unidad_organizacional_mandante_id')->nullable(false)->change();
        });
    }
};
