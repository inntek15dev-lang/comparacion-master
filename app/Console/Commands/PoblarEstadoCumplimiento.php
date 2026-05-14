<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TrabajadorVinculacion;
use App\Models\VehiculoAsignacion;
use App\Models\MaquinariaAsignacion;
use App\Models\EmbarcacionAsignacion;
use App\Models\ContratistaUnidadOrganizacional;
use App\Jobs\ActualizarEstadoRecursoIndividual;
use App\Models\Contratista;

class PoblarEstadoCumplimiento extends Command
{
    protected $signature = 'app:poblar-estado-cumplimiento';
    protected $description = 'Calcula y guarda el estado de cumplimiento inicial para todas las vinculaciones y recursos existentes.';

    public function handle()
    {
        $this->info('Iniciando la población del estado de cumplimiento para todas las entidades...');

        $vinculaciones = collect()
            ->concat(TrabajadorVinculacion::all())
            ->concat(VehiculoAsignacion::all())
            ->concat(MaquinariaAsignacion::all())
            ->concat(EmbarcacionAsignacion::all())
            ->concat(ContratistaUnidadOrganizacional::all());

        $bar = $this->output->createProgressBar($vinculaciones->count());
        $bar->start();

        foreach ($vinculaciones as $vinculacion) {
            app(\App\Services\EstadoCumplimientoService::class)->actualizarEstadoParaVinculacion($vinculacion);
            $bar->advance();
        }

        $bar->finish();
        $this->info("\n¡Población completada exitosamente!");

        return 0;
    }
}
