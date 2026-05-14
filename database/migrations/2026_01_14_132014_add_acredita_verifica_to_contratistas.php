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
        Schema::table('contratistas', function (Blueprint $table) {
            $table->boolean('acredita')->default(true)->after('is_active');
            $table->boolean('verifica')->default(false)->after('acredita');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratistas', function (Blueprint $table) {
            $table->dropColumn(['acredita', 'verifica']);
        });
    }
};
