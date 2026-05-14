<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('regla_documental_sub_tipo_vehiculo_mandante')) {
            Schema::create('regla_documental_sub_tipo_vehiculo_mandante', function (Blueprint $table) {
                $table->unsignedBigInteger('regla_documental_id');
                $table->unsignedBigInteger('sub_tipo_vehiculo_mandante_id');

                $table->primary(['regla_documental_id', 'sub_tipo_vehiculo_mandante_id'], 'rdstvm_primary');

                $table->foreign('regla_documental_id', 'fk_rdstvm_regla')
                    ->references('id')->on('reglas_documentales')->onDelete('cascade');

                $table->foreign('sub_tipo_vehiculo_mandante_id', 'fk_rdstvm_subtipo')
                    ->references('id')->on('sub_tipos_vehiculo_mandante')->onDelete('cascade');
            });
        }
    }


    public function down(): void
    {
        Schema::dropIfExists('regla_documental_sub_tipo_vehiculo_mandante');
    }
};
