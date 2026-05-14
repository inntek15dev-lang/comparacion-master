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
        Schema::table('solicitudes_complementarias', function (Blueprint $table) {
            $table->string('folio', 20)->nullable()->unique()->after('id');
        });

        // Llenar retroactivamente todas las solicitudes existentes para que no queden nulas
        DB::table('solicitudes_complementarias')->orderBy('id')->chunk(100, function ($solicitudes) {
            foreach ($solicitudes as $sol) {
                $folioStr = 'SC-' . str_pad($sol->id, 4, '0', STR_PAD_LEFT);
                DB::table('solicitudes_complementarias')
                    ->where('id', $sol->id)
                    ->update(['folio' => $folioStr]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_complementarias', function (Blueprint $table) {
            $table->dropColumn('folio');
        });
    }
};
