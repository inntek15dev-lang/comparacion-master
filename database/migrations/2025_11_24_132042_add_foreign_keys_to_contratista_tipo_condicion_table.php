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
        Schema::table('contratista_tipo_condicion', function (Blueprint $table) {
            $table->foreign(['contratista_id'])->references(['id'])->on('contratistas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['tipo_condicion_id'])->references(['id'])->on('tipos_condicion')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratista_tipo_condicion', function (Blueprint $table) {
            $table->dropForeign('contratista_tipo_condicion_contratista_id_foreign');
            $table->dropForeign('contratista_tipo_condicion_tipo_condicion_id_foreign');
        });
    }
};
