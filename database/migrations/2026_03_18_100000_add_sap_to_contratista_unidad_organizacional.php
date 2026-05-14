<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratista_unidad_organizacional', function (Blueprint $table) {
            $table->string('sap', 50)->nullable()->after('id_registro');
        });
    }

    public function down(): void
    {
        Schema::table('contratista_unidad_organizacional', function (Blueprint $table) {
            $table->dropColumn('sap');
        });
    }
};
