<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Sub-Criterios Condicionales (Nivel Dios)
 *
 * Agrega dos columnas nullable a la tabla pivot `regla_criterio_sub_criterio`:
 *
 *  - tipo_condicion_personal_id : FK a tipos_condicion_personal (condición del trabajador)
 *  - tipo_condicion_id          : FK a tipos_condicion (condición de la empresa)
 *
 * REGLA DE FILTRADO (aplicada en DocumentoRequeridoService):
 *   Un sub-criterio SE INCLUYE en la respuesta si:
 *     (tipo_condicion_personal_id IS NULL AND tipo_condicion_id IS NULL)  → universal
 *     OR tipo_condicion_personal_id IN (condiciones personales del trabajador)
 *     OR tipo_condicion_id          IN (condiciones de empresa del contratista en la UO)
 *
 * COMPATIBILIDAD BACKWARD GARANTIZADA:
 *   Las filas existentes sin condición (NULL en ambos campos) se comportan como "universales":
 *   se incluyen siempre, idéntico al comportamiento actual. Ninguna regla existente se rompe.
 *
 * SCOPE: Genérico — aplica a CUALQUIER sub-criterio de CUALQUIER criterio de CUALQUIER documento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regla_criterio_sub_criterio', function (Blueprint $table) {
            // Condición del Trabajador (vinculación): opcional, null = aplica a todos
            $table->unsignedBigInteger('tipo_condicion_personal_id')
                ->nullable()
                ->default(null)
                ->after('sub_criterio_id')
                ->comment('Condición personal del trabajador requerida para incluir este sub-criterio. NULL = universal.');

            // Condición de la Empresa (CUO): opcional, null = aplica a todos
            $table->unsignedBigInteger('tipo_condicion_id')
                ->nullable()
                ->default(null)
                ->after('tipo_condicion_personal_id')
                ->comment('Condición de empresa (CUO) requerida para incluir este sub-criterio. NULL = universal.');

            // FKs
            $table->foreign('tipo_condicion_personal_id', 'fk_rcsc_condicion_personal')
                ->references('id')
                ->on('tipos_condicion_personal')
                ->onDelete('set null');

            $table->foreign('tipo_condicion_id', 'fk_rcsc_condicion_empresa')
                ->references('id')
                ->on('tipos_condicion')
                ->onDelete('set null');

            // Índices para filtrado eficiente en DocumentoRequeridoService
            $table->index('tipo_condicion_personal_id', 'idx_rcsc_cond_personal');
            $table->index('tipo_condicion_id', 'idx_rcsc_cond_empresa');
        });
    }

    public function down(): void
    {
        Schema::table('regla_criterio_sub_criterio', function (Blueprint $table) {
            $table->dropForeign('fk_rcsc_condicion_personal');
            $table->dropForeign('fk_rcsc_condicion_empresa');
            $table->dropIndex('idx_rcsc_cond_personal');
            $table->dropIndex('idx_rcsc_cond_empresa');
            $table->dropColumn(['tipo_condicion_personal_id', 'tipo_condicion_id']);
        });
    }
};
