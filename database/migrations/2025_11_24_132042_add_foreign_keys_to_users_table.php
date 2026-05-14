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
        Schema::table('users', function (Blueprint $table) {
            $table->foreign(['contratista_id'])->references(['id'])->on('contratistas')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['mandante_id'])->references(['id'])->on('mandantes')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_contratista_id_foreign');
            $table->dropForeign('users_mandante_id_foreign');
        });
    }
};
