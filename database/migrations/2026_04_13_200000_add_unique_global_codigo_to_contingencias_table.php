<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Hace que el código de incidencia sea ÚNICO EN TODO EL SISTEMA.
     *
     * Antes: el MAX se calculaba por carpeta → podía repetirse en distintas carpetas.
     * Ahora: el MAX es global → un código = una incidencia en todo el sistema.
     *
     * Caso de uso: un contratista en terreno llama y dice "resolví el 100.347".
     * El operador puede buscarlo directamente sin saber el RUT, ID, contrato ni lugar.
     *
     * IMPORTANTE: Si ya existen registros con códigos duplicados entre carpetas,
     * esta migración fallará. En ese caso correr primero el script de saneamiento
     * o truncar la tabla carpeta_trabajador_contingencias si el ambiente es de desarrollo.
     */
    public function up(): void
    {
        // 1. Saneamiento: renumerar todo para evitar colisiones antes de poner el índice UNIQUE
        $incidencias = DB::table('carpeta_trabajador_contingencias')->orderBy('id')->get();
        $codigoGlobal = 100001;
        foreach ($incidencias as $inc) {
            DB::table('carpeta_trabajador_contingencias')
                ->where('id', $inc->id)
                ->update(['codigo' => $codigoGlobal++]);
        }

        // 2. Aplicar el índice UNIQUE global
        $indexExists = collect(DB::select("SHOW INDEX FROM carpeta_trabajador_contingencias WHERE Key_name = 'unique_codigo_global'"))->isNotEmpty();

        if (!$indexExists) {
            Schema::table('carpeta_trabajador_contingencias', function (Blueprint $table) {
                $table->unique('codigo', 'unique_codigo_global');
            });
        }
    }

    public function down(): void
    {
        Schema::table('carpeta_trabajador_contingencias', function (Blueprint $table) {
            $table->dropUnique('unique_codigo_global');
        });
    }
};
