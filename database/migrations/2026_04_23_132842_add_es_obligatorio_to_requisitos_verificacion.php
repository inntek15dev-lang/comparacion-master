<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('requisitos_verificacion', function (Blueprint $table) {
            $table->boolean('es_obligatorio')->default(false)->after('is_active');
        });

        // Marcar los 3 requisitos obligatorios usando LIKE case-insensitive
        DB::table('requisitos_verificacion')
            ->where('nombre', 'LIKE', '%liquidaci%sueldo%')
            ->orWhere('nombre', 'LIKE', '%certificado%cotizacion%')
            ->orWhere('nombre', 'LIKE', '%pago%mutualidad%')
            ->update(['es_obligatorio' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requisitos_verificacion', function (Blueprint $table) {
            $table->dropColumn('es_obligatorio');
        });
    }
};
