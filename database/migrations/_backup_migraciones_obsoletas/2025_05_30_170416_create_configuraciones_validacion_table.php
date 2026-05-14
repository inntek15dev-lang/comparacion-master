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
        Schema::create('configuraciones_validacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->text('descripcion')->nullable();

            $table->unsignedBigInteger('primer_rol_validador_id')->nullable();
            $table->foreign('primer_rol_validador_id')
                  ->references('id')
                  ->on('roles')
                  ->onDelete('set null'); // O 'restrict' si no se debe permitir borrar el rol si está en uso

            $table->unsignedBigInteger('segundo_rol_validador_id')->nullable();
            $table->foreign('segundo_rol_validador_id')
                  ->references('id')
                  ->on('roles')
                  ->onDelete('set null'); // O 'restrict'

            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('configuraciones_validacion');
    }
};