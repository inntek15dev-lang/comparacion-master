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
        Schema::table('trabajadores', function (Blueprint $table) {
            $table->unsignedBigInteger('tipo_permanencia_id')->nullable()->after('nacionalidad_id');
            // Como SQLite no soporta alter constraints complejas bien temporalmente, 
            // no usaremos cascade o lo dejamos simple
            $table->foreign('tipo_permanencia_id')->references('id')->on('tipos_permanencias')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajadores', function (Blueprint $table) {
            $table->dropForeign(['tipo_permanencia_id']);
            $table->dropColumn('tipo_permanencia_id');
        });
    }
};
