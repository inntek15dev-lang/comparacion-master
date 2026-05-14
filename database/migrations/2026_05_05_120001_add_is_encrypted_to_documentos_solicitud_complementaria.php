<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Escudo Criptográfico — documentos_solicitud_complementaria
 *
 * Agrega is_encrypted también a los documentos de verificación complementaria.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_solic_complementarias', function (Blueprint $table) {
            if (!Schema::hasColumn('documentos_solic_complementarias', 'is_encrypted')) {
                $table->boolean('is_encrypted')->default(false)->after('path')
                      ->comment('true = archivo AES-256-CBC en disk:local; false = plano en disk:public (legado)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documentos_solic_complementarias', function (Blueprint $table) {
            if (Schema::hasColumn('documentos_solic_complementarias', 'is_encrypted')) {
                $table->dropColumn('is_encrypted');
            }
        });
    }
};
