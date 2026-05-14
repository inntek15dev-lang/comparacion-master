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
        Schema::table('carpeta_trabajador_contingencias', function (Blueprint $table) {
            $table->string('clasificacion')->nullable()->after('catalogo_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carpeta_trabajador_contingencias', function (Blueprint $table) {
            $table->dropColumn('clasificacion');
        });
    }
};
