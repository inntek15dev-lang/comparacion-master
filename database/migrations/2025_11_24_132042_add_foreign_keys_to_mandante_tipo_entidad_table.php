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
        Schema::table('mandante_tipo_entidad', function (Blueprint $table) {
            $table->foreign(['mandante_id'])->references(['id'])->on('mandantes')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['tipo_entidad_controlable_id'])->references(['id'])->on('tipos_entidad_controlable')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mandante_tipo_entidad', function (Blueprint $table) {
            $table->dropForeign('mandante_tipo_entidad_mandante_id_foreign');
            $table->dropForeign('mandante_tipo_entidad_tipo_entidad_controlable_id_foreign');
        });
    }
};
