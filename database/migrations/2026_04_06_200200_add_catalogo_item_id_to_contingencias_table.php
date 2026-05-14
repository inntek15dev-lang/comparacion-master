<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carpeta_trabajador_contingencias', function (Blueprint $table) {
            if (!Schema::hasColumn('carpeta_trabajador_contingencias', 'catalogo_item_id')) {
                $table->foreignId('catalogo_item_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('catalogo_auditoria_items')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('carpeta_trabajador_contingencias', function (Blueprint $table) {
            $table->dropForeign(['catalogo_item_id']);
            $table->dropColumn('catalogo_item_id');
        });
    }
};
