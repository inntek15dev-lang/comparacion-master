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
        Schema::table('requisitos_verificacion', function (Blueprint $table) {
            if (!Schema::hasColumn('requisitos_verificacion', 'codigo')) {
                $table->string('codigo', 50)->nullable()->after('clasificacion_id');
            }
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('requisitos_verificacion', function (Blueprint $table) {
            $table->dropColumn('codigo');
        });
    }
};
