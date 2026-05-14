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
        Schema::create('regla_criterio_sub_criterio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('regla_documental_criterio_id');
            $table->unsignedBigInteger('sub_criterio_id');
            $table->timestamps();

            $table->foreign('regla_documental_criterio_id', 'fk_rcsc_criterio_id')
                ->references('id')
                ->on('regla_documental_criterios')
                ->onDelete('cascade');
            
            $table->foreign('sub_criterio_id', 'fk_rcsc_sub_criterio_id')
                ->references('id')
                ->on('sub_criterios')
                ->onDelete('cascade');
        });

        // Migrar datos existentes de regla_documental_criterios.sub_criterio_id a la nueva tabla pivote
        $existing = DB::table('regla_documental_criterios')
            ->whereNotNull('sub_criterio_id')
            ->get(['id', 'sub_criterio_id']);

        foreach ($existing as $row) {
            DB::table('regla_criterio_sub_criterio')->insert([
                'regla_documental_criterio_id' => $row->id,
                'sub_criterio_id' => $row->sub_criterio_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regla_criterio_sub_criterio');
    }
};
