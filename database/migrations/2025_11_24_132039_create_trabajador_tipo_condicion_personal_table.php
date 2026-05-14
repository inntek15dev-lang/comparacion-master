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
        Schema::create('trabajador_tipo_condicion_personal', function (Blueprint $table) {
            $table->unsignedBigInteger('trabajador_id');
            $table->unsignedBigInteger('tipo_condicion_personal_id')->index('ttcp_tipo_cond_pers_constrained');

            $table->primary(['trabajador_id', 'tipo_condicion_personal_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trabajador_tipo_condicion_personal');
    }
};
