<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clasificaciones_verificacion', function (Blueprint $table) {
            $table->string('nombre', 240)->change();
        });
    }

    public function down(): void
    {
        Schema::table('clasificaciones_verificacion', function (Blueprint $table) {
            $table->string('nombre', 100)->change();
        });
    }
};
