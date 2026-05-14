<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\EstadoCumplimientoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ActualizarEstadoRecursoIndividual implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $recurso;

    public function __construct(Model $recurso)
    {
        $this->recurso = $recurso;
    }

    public function handle(EstadoCumplimientoService $estadoService): void
    {
        try {
            $estadoService->actualizarEstadoParaRecurso($this->recurso);
        } catch (\Exception $e) {
            Log::error('Fallo el Job ActualizarEstadoRecursoIndividual', [
                'recurso_type' => get_class($this->recurso),
                'recurso_id' => $this->recurso->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}