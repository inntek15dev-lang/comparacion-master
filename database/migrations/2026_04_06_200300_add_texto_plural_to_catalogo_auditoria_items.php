<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalogo_auditoria_items', function (Blueprint $table) {
            if (!Schema::hasColumn('catalogo_auditoria_items', 'texto_plural')) {
                $table->string('texto_plural', 500)->nullable()->after('texto')
                    ->comment('Forma plural para agrupar en el certificado cuando aplica a múltiples trabajadores');
            }
        });
    }

    public function down(): void
    {
        Schema::table('catalogo_auditoria_items', function (Blueprint $table) {
            $table->dropColumn('texto_plural');
        });
    }
};
