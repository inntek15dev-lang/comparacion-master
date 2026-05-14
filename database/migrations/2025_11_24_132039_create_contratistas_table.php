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
        Schema::create('contratistas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('razon_social');
            $table->string('nombre_fantasia')->nullable();
            $table->string('rut')->unique();
            $table->string('direccion_calle');
            $table->string('direccion_numero')->nullable();
            $table->unsignedBigInteger('comuna_id')->index('contratistas_comuna_id_foreign');
            $table->string('telefono_empresa')->nullable();
            $table->string('email_empresa')->unique();
            $table->unsignedBigInteger('tipo_empresa_legal_id')->index('contratistas_tipo_empresa_legal_id_foreign');
            $table->unsignedBigInteger('rubro_id')->index('contratistas_rubro_id_foreign');
            $table->unsignedBigInteger('rango_cantidad_trabajadores_id')->nullable()->index('contratistas_rango_cantidad_trabajadores_id_foreign');
            $table->unsignedBigInteger('mutualidad_id')->nullable()->index('contratistas_mutualidad_id_foreign');
            $table->unsignedBigInteger('admin_user_id')->nullable()->unique();
            $table->string('rep_legal_nombres');
            $table->string('rep_legal_apellido_paterno');
            $table->string('rep_legal_apellido_materno')->nullable();
            $table->string('rep_legal_rut')->nullable();
            $table->string('rep_legal_telefono')->nullable();
            $table->string('rep_legal_email')->nullable();
            $table->string('tipo_inscripcion')->nullable()->comment('Tipo: Contratista o Subcontratista');
            $table->boolean('is_active')->default(false);
            $table->enum('estado_plataforma', ['Activo', 'Pendiente de Aprobacion', 'Inactivo'])->default('Pendiente de Aprobacion')->comment('Estado del contratista dentro del ciclo de vida de la plataforma');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratistas');
    }
};
