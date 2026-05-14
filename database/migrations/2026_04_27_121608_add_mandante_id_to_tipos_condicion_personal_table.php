<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_condicion_personal', function (Blueprint $table) {
            $table->foreignId('mandante_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('mandantes')
                  ->nullOnDelete();

            $table->dropUnique(['nombre']);
            $table->unique(['mandante_id', 'nombre'], 'tipos_cond_pers_mandante_nombre_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tipos_condicion_personal', function (Blueprint $table) {
            $table->dropUnique('tipos_cond_pers_mandante_nombre_unique');
            $table->unique('nombre');
            $table->dropConstrainedForeignId('mandante_id');
        });
    }
};
