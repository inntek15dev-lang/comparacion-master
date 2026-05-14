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
        Schema::table('documentos_solic_complementarias', function (Blueprint $table) {
            $table->unsignedBigInteger('requisito_verificacion_id')->nullable()->after('solicitud_complementaria_id');
            $table->foreign('requisito_verificacion_id', 'fk_doc_sol_comp_req')
                  ->references('id')
                  ->on('requisitos_verificacion')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_solic_complementarias', function (Blueprint $table) {
            $table->dropForeign('fk_doc_sol_comp_req');
            $table->dropColumn('requisito_verificacion_id');
        });
    }
};
