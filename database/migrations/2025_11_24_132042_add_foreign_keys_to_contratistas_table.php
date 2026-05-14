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
            $table->foreign(['admin_user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['comuna_id'])->references(['id'])->on('comunas')->onUpdate('no action')->onDelete('restrict');
            $table->foreign(['mutualidad_id'])->references(['id'])->on('mutualidades')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['rango_cantidad_trabajadores_id'])->references(['id'])->on('rangos_cantidad_trabajadores')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['rubro_id'])->references(['id'])->on('rubros')->onUpdate('no action')->onDelete('restrict');
            $table->foreign(['tipo_empresa_legal_id'])->references(['id'])->on('tipos_empresa_legal')->onUpdate('no action')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratistas', function (Blueprint $table) {
            $table->dropForeign('contratistas_admin_user_id_foreign');
            $table->dropForeign('contratistas_comuna_id_foreign');
            $table->dropForeign('contratistas_mutualidad_id_foreign');
            $table->dropForeign('contratistas_rango_cantidad_trabajadores_id_foreign');
            $table->dropForeign('contratistas_rubro_id_foreign');
            $table->dropForeign('contratistas_tipo_empresa_legal_id_foreign');
        });
    }
};
