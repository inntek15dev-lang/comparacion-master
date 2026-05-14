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
        Schema::table('mandantes', function (Blueprint $table) {
            $table->boolean('tiene_oval')->default(false)->after('is_active');
            $table->string('oval_cod')->nullable()->after('tiene_oval');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mandantes', function (Blueprint $table) {
            $table->dropColumn(['tiene_oval', 'oval_cod']);
        });
    }
};
