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
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('rut', 12)->nullable();
            $table->string('telefono', 25)->nullable();
            $table->string('cargo')->nullable();
            $table->string('password');
            $table->string('user_type');
            $table->unsignedBigInteger('mandante_id')->nullable()->index('users_mandante_id_foreign');
            $table->unsignedBigInteger('contratista_id')->nullable()->index('users_contratista_id_foreign');
            $table->boolean('is_platform_admin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->string('two_factor_code', 8)->nullable();
            $table->dateTime('two_factor_expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
