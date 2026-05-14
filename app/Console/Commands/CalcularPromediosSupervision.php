<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Mandante;
use App\Models\Contratista;
use App\Models\Trabajador;
use App\Models\Vehiculo;
use App\Models\Maquinaria;
use App\Models\Embarcacion;

class CalcularPromediosSupervision extends Command
{
    protected $signature = 'supervision:calcular-promedios';
    protected $description = 'Calcula y cachea los promedios de cumplimiento de contratistas para todos los mandantes activos.';

    public function handle()
    {
        $this->info('Iniciando el Vigilante Nocturno: Cálculo de promedios de supervisión...');
        Log::info('Tarea programada [supervision:calcular-promedios] iniciada.');

        try {
            $mandantesActivos = Mandante::where('is_active', true)->with('tiposEntidadControlable')->get();

            if ($mandantesActivos->isEmpty()) {
                $this->info('No hay mandantes activos para procesar. Misión completada.');
                Log::info('Tarea programada [supervision:calcular-promedios]: No hay mandantes activos.');
                return 0;
            }

            foreach ($mandantesActivos as $mandante) {
                $this->line("Procesando Mandante: {$mandante->razon_social} (ID: {$mandante->id})");
                
                $entidadesPermitidas = $mandante->tiposEntidadControlable->pluck('nombre_entidad')->map(fn($nombre) => strtoupper($nombre))->toArray();

                $contratistas = Contratista::whereHas('unidadesOrganizacionalesMandante', fn($q) => $q->where('mandante_id', $mandante->id))
                    ->with('unidadesOrganizacionalesMandante')
                    ->get();

                if ($contratistas->isEmpty()) {
                    $this->warn("  -> No se encontraron contratistas asociados. Saltando.");
                    continue;
                }

                $resultados = [];
                foreach ($contratistas as $contratista) {
                    $this->comment("  -> Calculando para Contratista: {$contratista->razon_social}");
                    
                    $uosDelMandante = $contratista->unidadesOrganizacionalesMandante->where('mandante_id', $mandante->id);
                    if ($uosDelMandante->isEmpty()) continue;
                    
                    $uoContextoId = $uosDelMandante->first()->id;

                    // ================== INICIO DE LA MODIFICACIÓN CANÓNICA ==================
                    $resultadoContratista = [
                        'id' => $contratista->id,
                        'razon_social' => $contratista->razon_social,
                        'rut' => $contratista->rut,
                        'mandante_nombre' => $mandante->razon_social,
                        'mandante_id' => $mandante->id,
                    ];

                    if (in_array('EMPRESA', $entidadesPermitidas)) {
                        $resultadoContratista['cumplimiento_empresa'] = $contratista->calcularPorcentajeCumplimiento($mandante->id, $uoContextoId);
                    }
                    if (in_array('PERSONA', $entidadesPermitidas)) {
                        $resultadoContratista['promedio_trabajadores'] = $this->calcularPromedioParaEntidad(Trabajador::class, $contratista->id, $mandante->id, $uoContextoId);
                    }
                    if (in_array('VEHICULO', $entidadesPermitidas)) {
                        $resultadoContratista['promedio_vehiculos'] = $this->calcularPromedioParaEntidad(Vehiculo::class, $contratista->id, $mandante->id, $uoContextoId);
                    }
                    if (in_array('MAQUINARIA', $entidadesPermitidas)) {
                        $resultadoContratista['promedio_maquinarias'] = $this->calcularPromedioParaEntidad(Maquinaria::class, $contratista->id, $mandante->id, $uoContextoId);
                    }
                    if (in_array('EMBARCACION', $entidadesPermitidas)) {
                        $resultadoContratista['promedio_embarcaciones'] = $this->calcularPromedioParaEntidad(Embarcacion::class, $contratista->id, $mandante->id, $uoContextoId);
                    }
                    $resultados[] = $resultadoContratista;
                    // ================== FIN DE LA MODIFICACIÓN CANÓNICA ==================
                }

                $cacheKey = "supervision_mandante_{$mandante->id}";
                $fecha = now()->format('d-m-Y H:i:s');
                Cache::put($cacheKey, ['promedios' => $resultados, 'fecha' => $fecha], now()->addHours(24));
                
                $this->info("  -> Datos para Mandante ID {$mandante->id} calculados y guardados en caché.");
            }

            $this->info('Misión del Vigilante Nocturno completada exitosamente.');
            Log::info('Tarea programada [supervision:calcular-promedios] finalizada con éxito.');
            return 0;

        } catch (\Exception $e) {
            $this->error('¡FALLA CRÍTICA! El Vigilante Nocturno encontró un error.');
            $this->error($e->getMessage());
            Log::error('Error en tarea programada [supervision:calcular-promedios]: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return 1;
        }
    }

    private function calcularPromedioParaEntidad($modelo, $contratistaId, $mandanteId, $uoId)
    {
        $entidades = $modelo::where('contratista_id', $contratistaId)->get();
        if ($entidades->isEmpty()) {
            return ['promedio' => 100, 'total' => 0];
        }
        $totalPorcentaje = 0;
        foreach ($entidades as $entidad) {
            $totalPorcentaje += $entidad->calcularPorcentajeCumplimiento($mandanteId, $uoId);
        }
        return ['promedio' => (int) round($totalPorcentaje / $entidades->count()), 'total' => $entidades->count()];
    }
}