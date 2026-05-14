<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clasificaciones_verificacion', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('nombre');
            $blueprint->text('descripcion')->nullable();
            $blueprint->boolean('is_active')->default(true);
            $blueprint->timestamps();
        });

        // Añadir clasificacion_id a requisitos_verificacion
        Schema::table('requisitos_verificacion', function (Blueprint $blueprint) {
            $blueprint->foreignId('clasificacion_id')->nullable()->after('mandante_id')->constrained('clasificaciones_verificacion')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('requisitos_verificacion', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['clasificacion_id']);
            $blueprint->dropColumn('clasificacion_id');
        });
        Schema::dropIfExists('clasificaciones_verificacion');
    }
};
