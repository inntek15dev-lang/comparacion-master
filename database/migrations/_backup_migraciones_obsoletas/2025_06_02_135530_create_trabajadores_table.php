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
            $table->id();
            $table->foreignId('contratista_id')->constrained('contratistas')->onDelete('cascade');

            // Información Personal
            $table->string('nombres');
            $table->string('apellido_paterno');
            $table->string('apellido_materno')->nullable();
            $table->string('rut', 12)->unique(); // RUT único a nivel de plataforma
            $table->date('fecha_nacimiento')->nullable();
            $table->foreignId('sexo_id')->nullable()->constrained('sexos')->onDelete('set null');
            $table->foreignId('nacionalidad_id')->nullable()->constrained('nacionalidades')->onDelete('set null');

            // Contacto
            $table->string('email')->nullable();
            $table->string('celular', 25)->nullable();

            // Información Adicional
            $table->foreignId('estado_civil_id')->nullable()->constrained('estados_civiles')->onDelete('set null');
            $table->foreignId('nivel_educacional_id')->nullable()->constrained('niveles_educacionales')->onDelete('set null');
            $table->foreignId('etnia_id')->nullable()->constrained('etnias')->onDelete('set null'); // Para "Pueblo Originario"
            $table->date('fecha_ingreso_empresa')->nullable()->comment('Fecha de ingreso al contratista');

            // Domicilio
            $table->string('direccion_calle')->nullable();
            $table->string('direccion_numero', 50)->nullable();
            $table->string('direccion_departamento', 50)->nullable();
            $table->foreignId('comuna_id')->nullable()->constrained('comunas')->onDelete('set null');
            // La región se obtiene a través de la comuna

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes(); // Si queremos borrado lógico
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