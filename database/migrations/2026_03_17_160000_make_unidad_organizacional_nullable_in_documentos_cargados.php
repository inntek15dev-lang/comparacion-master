<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_cargados', function (Blueprint $table) {
            $table->unsignedBigInteger('unidad_organizacional_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('documentos_cargados', function (Blueprint $table) {
            $table->unsignedBigInteger('unidad_organizacional_id')->nullable(false)->change();
        });
    }
};
