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
        Schema::table('onboarding_contratista', function (Blueprint $table) {
            $table->foreign(['contratista_id'], 'fk_onboarding_contratista')->references(['id'])->on('contratistas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['paso1_user_id'], 'fk_onboarding_paso1_user')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['paso2_user_id'], 'fk_onboarding_paso2_user')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['paso3_user_id'], 'fk_onboarding_paso3_user')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['paso4_user_id'], 'fk_onboarding_paso4_user')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['paso5_user_id'], 'fk_onboarding_paso5_user')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['paso6_user_id'], 'fk_onboarding_paso6_user')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['paso7_user_id'], 'fk_onboarding_paso7_user')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('onboarding_contratista', function (Blueprint $table) {
            $table->dropForeign('fk_onboarding_contratista');
            $table->dropForeign('fk_onboarding_paso1_user');
            $table->dropForeign('fk_onboarding_paso2_user');
            $table->dropForeign('fk_onboarding_paso3_user');
            $table->dropForeign('fk_onboarding_paso4_user');
            $table->dropForeign('fk_onboarding_paso5_user');
            $table->dropForeign('fk_onboarding_paso6_user');
            $table->dropForeign('fk_onboarding_paso7_user');
        });
    }
};
