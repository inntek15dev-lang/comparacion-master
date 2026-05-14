<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega campos de contrato a las vinculaciones de trabajadores.
     * Los campos son OPCIONALES ya que algunos mandantes no operan con contratos.
     */
    public function up(): void
    {
        Schema::table('trabajador_vinculaciones', function (Blueprint $table) {
            // Campos opcionales de contrato
            $table->string('numero_contrato', 100)->nullable()->after('dependencia_id');
            $table->unsignedBigInteger('tipo_contrato_id')->nullable()->after('numero_contrato');
            
            // Relación con tipos de contrato
            $table->foreign('tipo_contrato_id', 'fk_trab_vinc_tipo_contrato')
                  ->references('id')->on('tipos_contrato')
                  ->onDelete('set null');
        });

        // Nota: NO se modifica el constraint único porque los campos son opcionales
        // y la lógica de negocio permite múltiples vinculaciones con o sin contrato
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajador_vinculaciones', function (Blueprint $table) {
            $table->dropForeign('fk_trab_vinc_tipo_contrato');
            $table->dropColumn(['numero_contrato', 'tipo_contrato_id']);
        });
    }
};
