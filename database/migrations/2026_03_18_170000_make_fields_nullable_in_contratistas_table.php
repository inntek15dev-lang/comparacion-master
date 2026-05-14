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
        Schema::table('contratistas', function (Blueprint $table) {
            $table->unsignedBigInteger('comuna_id')->nullable()->change();
            $table->unsignedBigInteger('tipo_empresa_legal_id')->nullable()->change();
            $table->unsignedBigInteger('rubro_id')->nullable()->change();
            $table->string('email_empresa')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratistas', function (Blueprint $table) {
            $table->unsignedBigInteger('comuna_id')->nullable(false)->change();
            $table->unsignedBigInteger('tipo_empresa_legal_id')->nullable(false)->change();
            $table->unsignedBigInteger('rubro_id')->nullable(false)->change();
            $table->string('email_empresa')->nullable(false)->change();
        });
    }
};
