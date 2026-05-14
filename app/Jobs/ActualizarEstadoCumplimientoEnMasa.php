<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Mandante;
use App\Models\Contratista;
use App\Models\Trabajador;
use App\Models\Vehiculo;
use App\Models\Maquinaria;
use App\Models\Embarcacion;
use Illuminate\Support\Facades\Log;

class ActualizarEstadoCumplimientoEnMasa implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $mandanteId;

    public function __construct(int $mandanteId)
    {
        $this->mandanteId = $mandanteId;
    }

    public function handle(): void
    {
        Log::info("Iniciando Job de actualización masiva de estado de cumplimiento para Mandante ID: {$this->mandanteId}");

        $recursosAfectados = collect();

        // Contratistas
        $contratistas = Contratista::whereHas('unidadesOrganizacionalesMandante', function ($query) {
            $query->where('mandante_id', $this->mandanteId);
        })->get();
        foreach ($contratistas as $contratista) {
            // Despachar un job individual para cada contratista
            ActualizarEstadoRecursoIndividual::dispatch($contratista);
        }

        // Recursos (Trabajadores, Vehículos, etc.)
        $tiposDeRecurso = [Trabajador::class, Vehiculo::class, Maquinaria::class, Embarcacion::class];
        $relaciones = ['vinculaciones.unidadOrganizacionalMandante', 'vinculaciones.unidadOrganizacionalMandante'];

        foreach ($tiposDeRecurso as $tipo) {
            $recursos = $tipo::whereHas('vinculaciones.unidadOrganizacionalMandante', function ($query) {
                $query->where('mandante_id', $this->mandanteId);
            })->get();

            foreach ($recursos as $recurso) {
                // Despachar un job individual para cada recurso
                ActualizarEstadoRecursoIndividual::dispatch($recurso);
            }
        }
        
        Log::info("Finalizado Job de actualización masiva para Mandante ID: {$this->mandanteId}. Se han despachado jobs individuales.");
    }
}