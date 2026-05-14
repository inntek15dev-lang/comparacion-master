<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Añadir columna orden si no existe
        if (!Schema::hasColumn('clasificaciones_verificacion', 'orden')) {
            Schema::table('clasificaciones_verificacion', function (Blueprint $table) {
                $table->integer('orden')->default(999)->after('descripcion');
            });
        }
        
        // Actualizar el orden según la lógica del Maestro:
        // 1. COMPROBANTES DE PAGO AL TRABAJADOR
        // 2. COMPROBANTES DE PAGO A INSTITUCIONES PREVISIONALES
        // 3. OTROS (Licencias médicas, Finiquitos...)
        
        DB::table('clasificaciones_verificacion')
            ->where('nombre', 'LIKE', '%PAGO AL TRABAJADOR%')
            ->update(['orden' => 1]);
            
        DB::table('clasificaciones_verificacion')
            ->where('nombre', 'LIKE', '%INSTITUCIONES PREVISIONALES%')
            ->update(['orden' => 2]);
            
        DB::table('clasificaciones_verificacion')
            ->where('nombre', 'LIKE', '%OTROS%')
            ->orWhere('nombre', 'LIKE', '%LICENCIAS%')
            ->orWhere('nombre', 'LIKE', '%FINIQUITOS%')
            ->update(['orden' => 3]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('clasificaciones_verificacion', 'orden')) {
            Schema::table('clasificaciones_verificacion', function (Blueprint $table) {
                $table->dropColumn('orden');
            });
        }
    }
};
