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
        Schema::create('regla_documental_tenencia_vehiculo', function (Blueprint $table) {
            $table->unsignedBigInteger('regla_documental_id');
            $table->unsignedBigInteger('tenencia_vehiculo_id')->index('regla_ten_tenencia_id_foreign');

            $table->primary(['regla_documental_id', 'tenencia_vehiculo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regla_documental_tenencia_vehiculo');
    }
};
