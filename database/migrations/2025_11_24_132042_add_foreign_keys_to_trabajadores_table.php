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
        Schema::table('trabajadores', function (Blueprint $table) {
            $table->foreign(['comuna_id'])->references(['id'])->on('comunas')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['contratista_id'])->references(['id'])->on('contratistas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['estado_civil_id'])->references(['id'])->on('estados_civiles')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['etnia_id'])->references(['id'])->on('etnias')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['nacionalidad_id'])->references(['id'])->on('nacionalidades')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['nivel_educacional_id'])->references(['id'])->on('niveles_educacionales')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['sexo_id'])->references(['id'])->on('sexos')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajadores', function (Blueprint $table) {
            $table->dropForeign('trabajadores_comuna_id_foreign');
            $table->dropForeign('trabajadores_contratista_id_foreign');
            $table->dropForeign('trabajadores_estado_civil_id_foreign');
            $table->dropForeign('trabajadores_etnia_id_foreign');
            $table->dropForeign('trabajadores_nacionalidad_id_foreign');
            $table->dropForeign('trabajadores_nivel_educacional_id_foreign');
            $table->dropForeign('trabajadores_sexo_id_foreign');
        });
    }
};
