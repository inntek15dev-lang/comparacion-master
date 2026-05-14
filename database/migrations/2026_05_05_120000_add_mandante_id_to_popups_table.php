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
        Schema::table('popups', function (Blueprint $table) {
            $table->foreignId('mandante_id')->nullable()->constrained('mandantes')->onDelete('cascade')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('popups', function (Blueprint $table) {
            $table->dropForeign(['mandante_id']);
            $table->dropColumn('mandante_id');
        });
    }
};
