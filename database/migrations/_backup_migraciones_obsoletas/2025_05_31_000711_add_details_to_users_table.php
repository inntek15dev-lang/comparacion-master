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
        Schema::table('users', function (Blueprint $table) {
            // Añadir nuevas columnas si no existen
            if (!Schema::hasColumn('users', 'rut')) {
                $table->string('rut', 12)->nullable()->unique()->after('email_verified_at');
            }
            if (!Schema::hasColumn('users', 'telefono')) {
                $table->string('telefono', 25)->nullable()->after('rut');
            }
            if (!Schema::hasColumn('users', 'cargo')) {
                $table->string('cargo', 255)->nullable()->after('telefono');
            }

            // Asegurar que mandante_id exista y tenga las propiedades correctas
            if (!Schema::hasColumn('users', 'mandante_id')) {
                $table->foreignId('mandante_id')->nullable()->after('user_type')
                      ->constrained('mandantes')->onDelete('set null');
            } else {
                // Si ya existe, solo intentamos modificarla para asegurar nullability y onDelete
                // Nota: Cambiar constraints de FK existentes puede ser problemático y a veces
                // requiere dropear la FK y volverla a crear.
                // Por ahora, asumiremos que si existe, su constraint es aceptable
                // o que solo necesitamos cambiar la nullability de la columna en sí.
                $table->unsignedBigInteger('mandante_id')->nullable()->change();
            }

            // Asegurar que contratista_id exista y tenga las propiedades correctas
            if (!Schema::hasColumn('users', 'contratista_id')) {
                $table->foreignId('contratista_id')->nullable()->after('mandante_id')
                      ->constrained('contratistas')->onDelete('set null');
            } else {
                $table->unsignedBigInteger('contratista_id')->nullable()->change();
            }

            // Asegurar is_platform_admin
            if (!Schema::hasColumn('users', 'is_platform_admin')) {
                $table->boolean('is_platform_admin')->default(false)->after('contratista_id');
            } else {
                 $table->boolean('is_platform_admin')->default(false)->change();
            }

            // Asegurar is_active
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_platform_admin');
            } else {
                $table->boolean('is_active')->default(true)->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // La lógica de down debe ser cuidadosa para solo revertir lo que 'up' hizo.
            // Si 'up' solo modificó una columna existente, 'down' podría intentar revertir esa modificación
            // o simplemente no hacer nada para esa columna específica si la reversión es compleja.

            if (Schema::hasColumn('users', 'cargo')) {
                $table->dropColumn('cargo');
            }
            if (Schema::hasColumn('users', 'telefono')) {
                $table->dropColumn('telefono');
            }
            if (Schema::hasColumn('users', 'rut')) {
                // Considerar dropear el índice unique si fue explícitamente creado.
                // $table->dropUnique(['rut']); // Laravel podría haber nombrado el índice users_rut_unique
                $table->dropColumn('rut');
            }

            // Para las columnas que podrían haber sido solo modificadas (mandante_id, contratista_id, etc.),
            // revertir el ->change() es más complejo y a menudo se omite o requiere
            // saber el estado exacto anterior.
            // Si fueron creadas por esta migración porque no existían, se pueden dropear.
            // Ejemplo: Si sabes que 'mandante_id' fue CREADO aquí y no solo modificado:
            // if (Schema::hasColumn('users', 'mandante_id') && /* condición de que fue creada aquí */) {
            //    $table->dropForeign(['mandante_id']);
            //    $table->dropColumn('mandante_id');
            // }
        });
    }
};