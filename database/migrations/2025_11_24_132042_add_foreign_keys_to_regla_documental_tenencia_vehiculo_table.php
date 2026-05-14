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
        Schema::table('regla_documental_tenencia_vehiculo', function (Blueprint $table) {
            $table->foreign(['regla_documental_id'], 'regla_ten_regla_id_foreign')->references(['id'])->on('reglas_documentales')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['tenencia_vehiculo_id'], 'regla_ten_tenencia_id_foreign')->references(['id'])->on('tenencias_vehiculo')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regla_documental_tenencia_vehiculo', function (Blueprint $table) {
            $table->dropForeign('regla_ten_regla_id_foreign');
            $table->dropForeign('regla_ten_tenencia_id_foreign');
        });
    }
};
