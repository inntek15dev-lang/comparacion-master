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
        Schema::create('trabajadores', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('contratista_id')->index('trabajadores_contratista_id_foreign');
            $table->string('nombres');
            $table->string('apellido_paterno');
            $table->string('apellido_materno')->nullable();
            $table->string('rut', 12)->unique();
            $table->date('fecha_nacimiento')->nullable();
            $table->unsignedBigInteger('sexo_id')->nullable()->index('trabajadores_sexo_id_foreign');
            $table->unsignedBigInteger('nacionalidad_id')->nullable()->index('trabajadores_nacionalidad_id_foreign');
            $table->string('email')->nullable();
            $table->string('celular', 25)->nullable();
            $table->unsignedBigInteger('estado_civil_id')->nullable()->index('trabajadores_estado_civil_id_foreign');
            $table->unsignedBigInteger('nivel_educacional_id')->nullable()->index('trabajadores_nivel_educacional_id_foreign');
            $table->unsignedBigInteger('etnia_id')->nullable()->index('trabajadores_etnia_id_foreign');
            $table->date('fecha_ingreso_empresa')->nullable()->comment('Fecha de ingreso al contratista');
            $table->string('direccion_calle')->nullable();
            $table->string('direccion_numero', 50)->nullable();
            $table->string('direccion_departamento', 50)->nullable();
            $table->unsignedBigInteger('comuna_id')->nullable()->index('trabajadores_comuna_id_foreign');
            $table->boolean('is_active')->default(true);
            $table->dateTime('fecha_baja')->nullable()->comment('Fecha y hora de la desactivación del trabajador');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trabajadores');
    }
};
