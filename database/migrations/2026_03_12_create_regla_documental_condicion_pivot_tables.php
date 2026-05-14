<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear tabla pivote para condición empresa
        Schema::create('regla_documental_tipo_condicion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('regla_documental_id');
            $table->unsignedBigInteger('tipo_condicion_id');
            $table->foreign('regla_documental_id', 'rdtc_rd_fk')->references('id')->on('reglas_documentales')->onDelete('cascade');
            $table->foreign('tipo_condicion_id', 'rdtc_tc_fk')->references('id')->on('tipos_condicion')->onDelete('cascade');
            $table->timestamps();
        });

        // 2. Crear tabla pivote para condición persona
        Schema::create('regla_documental_tipo_condicion_personal', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('regla_documental_id');
            $table->unsignedBigInteger('tipo_condicion_personal_id');
            $table->foreign('regla_documental_id', 'rdtcp_rd_fk')->references('id')->on('reglas_documentales')->onDelete('cascade');
            $table->foreign('tipo_condicion_personal_id', 'rdtcp_tcp_fk')->references('id')->on('tipos_condicion_personal')->onDelete('cascade');
            $table->timestamps();
        });

        // 3. Migrar datos existentes a las tablas pivote
        DB::statement("
            INSERT INTO regla_documental_tipo_condicion (regla_documental_id, tipo_condicion_id, created_at, updated_at)
            SELECT id, aplica_empresa_condicion_id, NOW(), NOW()
            FROM reglas_documentales
            WHERE aplica_empresa_condicion_id IS NOT NULL
        ");

        DB::statement("
            INSERT INTO regla_documental_tipo_condicion_personal (regla_documental_id, tipo_condicion_personal_id, created_at, updated_at)
            SELECT id, aplica_persona_condicion_id, NOW(), NOW()
            FROM reglas_documentales
            WHERE aplica_persona_condicion_id IS NOT NULL
        ");

        // 4. Eliminar columnas antiguas
        Schema::table('reglas_documentales', function (Blueprint $table) {
            // Dropear FK primero si existen
            $fks = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'reglas_documentales'
                  AND COLUMN_NAME IN ('aplica_empresa_condicion_id', 'aplica_persona_condicion_id')
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            foreach ($fks as $fk) {
                $table->dropForeign($fk->CONSTRAINT_NAME);
            }

            $table->dropColumn(['aplica_empresa_condicion_id', 'aplica_persona_condicion_id']);
        });
    }

    public function down(): void
    {
        Schema::table('reglas_documentales', function (Blueprint $table) {
            $table->unsignedBigInteger('aplica_empresa_condicion_id')->nullable();
            $table->unsignedBigInteger('aplica_persona_condicion_id')->nullable();
        });

        Schema::dropIfExists('regla_documental_tipo_condicion_personal');
        Schema::dropIfExists('regla_documental_tipo_condicion');
    }
};
