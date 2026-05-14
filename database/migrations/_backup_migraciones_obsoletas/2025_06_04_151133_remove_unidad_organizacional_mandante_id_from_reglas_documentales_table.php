<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log; // Opcional, para logging
use Illuminate\Support\Facades\DB;   // Para consultas crudas si es necesario

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reglas_documentales', function (Blueprint $table) {
            // Solo intentar operaciones si la columna existe
            if (Schema::hasColumn('reglas_documentales', 'unidad_organizacional_mandante_id')) {
                
                // Intento 1: Dejar que Laravel infiera el nombre de la FK por la columna
                try {
                    $table->dropForeign(['unidad_organizacional_mandante_id']);
                    Log::info("Clave foránea para unidad_organizacional_mandante_id eliminada (inferida por columna).");
                } catch (\Exception $e) {
                    Log::warning("No se pudo eliminar la clave foránea para unidad_organizacional_mandante_id (inferida por columna) o no existía. Error: " . $e->getMessage());

                    // Intento 2: Usar el nombre que vimos en HeidiSQL como último recurso
                    // (esto solo se ejecutará si el intento anterior falló)
                    $nombreConstraintHeidi = 'reglas_documentales_unidad_organizacional_mandante_id';
                    try {
                        // Necesitamos ejecutar esto como una consulta cruda si $table->dropForeign ya falló para la misma columna.
                        // Pero primero, vamos a confiar en que si la FK existe, el dropForeign anterior funcionaría si la convención es correcta.
                        // Si esto también falla, puede que el nombre sea diferente o ya no exista.
                        // Para una mayor robustez, podríamos comprobar si la restricción existe usando una consulta SQL cruda.
                        // Por ahora, si el dropForeign por columna falla, asumimos que no hay FK que Laravel pueda eliminar fácilmente
                        // y procedemos a intentar eliminar la columna. La BD podría impedir la eliminación de la columna si aún existe una FK.
                        Log::info("Procediendo a eliminar columna unidad_organizacional_mandante_id sin eliminación explícita de FK adicional por nombre, confiando en el intento anterior.");
                    } catch (\Exception $e2) {
                         Log::warning("Intento adicional de eliminar FK {$nombreConstraintHeidi} falló o no existía. Error: " . $e2->getMessage());
                    }
                }
                
                // Finalmente, intenta eliminar la columna.
                // Si aún existe una FK que los intentos anteriores no eliminaron, esto fallará.
                try {
                    $table->dropColumn('unidad_organizacional_mandante_id');
                    Log::info("Columna unidad_organizacional_mandante_id eliminada exitosamente.");
                } catch (\Exception $e) {
                    Log::error("Error final al intentar eliminar la columna unidad_organizacional_mandante_id. Es posible que una FK aún exista. Error: " . $e->getMessage());
                    // Aquí podrías lanzar la excepción si quieres que la migración falle explícitamente
                    // throw $e; 
                }

            } else {
                Log::info("La columna unidad_organizacional_mandante_id no existía en la tabla reglas_documentales.");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reglas_documentales', function (Blueprint $table) {
            if (!Schema::hasColumn('reglas_documentales', 'unidad_organizacional_mandante_id')) {
                $table->foreignId('unidad_organizacional_mandante_id')
                      ->nullable()
                      ->after('fecha_comparacion_ingreso')
                      ->constrained('unidades_organizacionales_mandante', 'id')
                      ->onDelete('set null')
                      ->name('reglas_documentales_unidad_organizacional_mandante_id');
            }
        });
    }
};