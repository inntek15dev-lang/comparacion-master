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
        Schema::table('documentos_cargados', function (Blueprint $table) {
            // Añadimos las 3 columnas que faltan para el snapshot
            // Las colocamos después de la columna 'criterios_snapshot' para mantener el orden lógico.
            
            $table->text('observacion_documento_snapshot')->nullable()->after('criterios_snapshot');
            $table->string('formato_documento_snapshot')->nullable()->after('observacion_documento_snapshot');
            $table->unsignedBigInteger('documento_relacionado_id_snapshot')->nullable()->after('formato_documento_snapshot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_cargados', function (Blueprint $table) {
            // Esto permite revertir los cambios si algo sale mal
            $table->dropColumn([
                'observacion_documento_snapshot',
                'formato_documento_snapshot',
                'documento_relacionado_id_snapshot'
            ]);
        });
    }
};