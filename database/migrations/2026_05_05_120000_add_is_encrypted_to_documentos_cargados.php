<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Escudo Criptográfico — Columna is_encrypted
 *
 * Agrega la bandera `is_encrypted` a documentos_cargados para soportar
 * la coexistencia entre archivos existentes (planos, disk:public) y
 * nuevos archivos encriptados (AES-256-CBC, disk:local).
 *
 * is_encrypted = false → archivo plano en storage/app/public/ (legado)
 * is_encrypted = true  → archivo .enc en storage/app/encrypted/ (nuevo)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_cargados', function (Blueprint $table) {
            if (!Schema::hasColumn('documentos_cargados', 'is_encrypted')) {
                $table->boolean('is_encrypted')->default(false)->after('ruta_archivo')
                      ->comment('true = archivo AES-256-CBC en disk:local; false = plano en disk:public (legado)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documentos_cargados', function (Blueprint $table) {
            if (Schema::hasColumn('documentos_cargados', 'is_encrypted')) {
                $table->dropColumn('is_encrypted');
            }
        });
    }
};
