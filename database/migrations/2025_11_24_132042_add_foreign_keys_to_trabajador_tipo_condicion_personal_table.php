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
        Schema::table('trabajador_tipo_condicion_personal', function (Blueprint $table) {
            $table->foreign(['trabajador_id'])->references(['id'])->on('trabajadores')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['tipo_condicion_personal_id'], 'ttcp_tipo_cond_pers_constrained')->references(['id'])->on('tipos_condicion_personal')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajador_tipo_condicion_personal', function (Blueprint $table) {
            $table->dropForeign('trabajador_tipo_condicion_personal_trabajador_id_foreign');
            $table->dropForeign('ttcp_tipo_cond_pers_constrained');
        });
    }
};
