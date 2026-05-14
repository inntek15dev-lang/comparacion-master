<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre completo del usuario
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Nuevas columnas añadidas
            $table->string('user_type'); // 'asem', 'mandante', 'contratista'

            // Nullable Foreign Keys: un usuario puede pertenecer a un mandante O a un contratista, o a ninguno (si es ASEM)
            $table->foreignId('mandante_id')->nullable()->constrained('mandantes')->onDelete('set null');
            // onDelete('set null'): si se borra el mandante, el user_id en mandante_id se vuelve null.
            // Otra opción es 'cascade' (borra el usuario) o 'restrict' (impide borrar el mandante si tiene usuarios).
            // 'set null' puede ser útil si quieres mantener el usuario pero desvincularlo.

            $table->foreignId('contratista_id')->nullable()->constrained('contratistas')->onDelete('set null');
            // onDelete('set null'): si se borra el contratista, el user_id en contratista_id se vuelve null.

            $table->boolean('is_platform_admin')->default(false); // True si es el admin de su empresa en la plataforma
            $table->boolean('is_active')->default(true); // Para activar/desactivar el usuario

            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};