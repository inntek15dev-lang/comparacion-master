<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\Models\DocumentoCargado;
use App\Models\SistemaConfiguracion;
use App\Jobs\NotificarDocumentosContratista;

class EnviarNotificacionesVencimiento extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notificaciones:vencimiento';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Busca documentos aprobados prontos a vencer y despacha notificaciones por correo.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando proceso de notificación de vencimientos...');
        Log::info('Ejecutando el comando [notificaciones:vencimiento].');

        try {
            // Obtener los umbrales de días desde la base de datos
            $configuraciones = SistemaConfiguracion::whereIn('clave', [
                'dias_anticipacion_notificacion_vencimiento_1',
                'dias_anticipacion_notificacion_vencimiento_2'
            ])->pluck('valor');
            
            if ($configuraciones->isEmpty()) {
                $this->warn('No se encontraron configuraciones de días de anticipación. Abortando.');
                Log::warning('No se encontraron claves de configuración para los días de notificación. El comando no se ejecutó.');
                return 1;
            }

            $diasDeAnticipacion = $configuraciones->map(fn($val) => (int)$val)->all();
            $idsDocumentosNotificar = [];

            foreach ($diasDeAnticipacion as $dias) {
                $fechaObjetivo = Carbon::today()->addDays($dias)->toDateString();
                $this->line("Buscando documentos que vencen en {$dias} días (Fecha objetivo: {$fechaObjetivo})...");

                $documentos = DocumentoCargado::where('resultado_validacion', 'Aprobado')
                               ->whereDate('fecha_vencimiento', $fechaObjetivo)
                               ->get();
                
                if ($documentos->isNotEmpty()) {
                    $this->info("Se encontraron {$documentos->count()} documentos que vencen el {$fechaObjetivo}.");
                    $idsDocumentosNotificar = array_merge($idsDocumentosNotificar, $documentos->pluck('id')->toArray());
                } else {
                    $this->line("No se encontraron documentos para la fecha objetivo {$fechaObjetivo}.");
                }
            }
            
            if (!empty($idsDocumentosNotificar)) {
                $mensaje = "Le informamos que se ha detectado la siguiente documentación próxima a vencer en nuestra plataforma, en relación a su prestación de servicios.\n\nAgradecemos proceder a su regularización a la brevedad para evitar inconvenientes en su operación.";

                // Despachar un único job con todos los IDs. El job se encargará de agrupar por contratista.
                NotificarDocumentosContratista::dispatch($idsDocumentosNotificar, $mensaje);
                
                $this->info('Se ha despachado el Job para notificar a los contratistas correspondientes.');
                Log::info('Se despachó el Job [NotificarDocumentosContratista] para ' . count($idsDocumentosNotificar) . ' documentos.');
            } else {
                $this->info('No hubo documentos para notificar en esta ejecución.');
            }

            $this->info('Proceso de notificación de vencimientos finalizado exitosamente.');
            Log::info('Comando [notificaciones:vencimiento] finalizado.');
            return 0;

        } catch (\Exception $e) {
            $this->error('Ocurrió un error durante la ejecución del comando.');
            Log::error('Error en comando [notificaciones:vencimiento]: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return 1;
        }
    }
}