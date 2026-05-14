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
        Schema::table('contratista_unidad_organizacional', function (Blueprint $table) {
            // Añadir la nueva columna para el ID del tipo de condición.
            // Es nullable porque la condición es opcional.
            // Debe ser unsignedBigInteger para coincidir con la columna 'id' de 'tipos_condicion' si es auto-incremental.
            $table->unsignedBigInteger('tipo_condicion_id')->nullable()->after('unidad_organizacional_mandante_id');

            // Definir la clave foránea.
            // El nombre de la restricción puede variar, Laravel lo genera automáticamente si no se especifica.
            // Asegúrate que 'tipos_condicion' es el nombre correcto de tu tabla de tipos de condición.
            $table->foreign('tipo_condicion_id', 'fk_contr_uo_tipo_condicion')
                ->references('id')
                ->on('tipos_condicion')
                ->onDelete('set null'); // Si se elimina un tipo de condición, se establece a NULL en la tabla pivote. Puedes cambiarlo a 'restrict' o 'cascade' si prefieres otro comportamiento.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratista_unidad_organizacional', function (Blueprint $table) {
            // Para revertir, primero eliminamos la clave foránea.
            // El nombre de la restricción debe coincidir con el que se usó al crearla o el que Laravel generó.
            // Si no estás seguro, puedes inspeccionar tu base de datos para ver el nombre.
            // Laravel suele generar nombres como: nombredetablapivote_nombredelacolumna_foreign
            // En este caso podría ser 'contratista_unidad_organizacional_tipo_condicion_id_foreign' o el que definimos 'fk_contr_uo_tipo_condicion'.
            // Es más seguro usar el array de columnas para dropForeign si no se especificó el nombre al crear.
            $table->dropForeign(['tipo_condicion_id']); // Método más seguro para eliminar la FK

            // Luego eliminamos la columna.
            $table->dropColumn('tipo_condicion_id');
        });
    }
};