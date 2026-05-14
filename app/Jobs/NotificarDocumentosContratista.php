<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\DocumentoCargado;
use App\Models\NotificacionEnviada;
use App\Mail\NotificacionDocumentos;
use Carbon\Carbon;

class NotificarDocumentosContratista implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $documentoIds;
    protected string $mensajePersonalizado;
    protected string $tipoNotificacion;
    protected ?int $usuarioId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $documentoIds, string $mensajePersonalizado, string $tipoNotificacion, ?int $usuarioId = null)
    {
        $this->documentoIds = $documentoIds;
        $this->mensajePersonalizado = $mensajePersonalizado;
        $this->tipoNotificacion = $tipoNotificacion;
        $this->usuarioId = $usuarioId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Iniciando Job 'NotificarDocumentosContratista' para " . count($this->documentoIds) . " documentos. Tipo: {$this->tipoNotificacion}.");

        // ================== INICIO: MODIFICACIÓN CANÓNICA #1 (Eager Loading) ==================
        // Se añade 'contratista.adminUser' para cargar eficientemente el usuario administrador.
        $documentos = DocumentoCargado::with(['contratista.adminUser', 'mandante', 'entidad'])
            ->whereIn('id', $this->documentoIds)
            ->get();
        // =================== FIN: MODIFICACIÓN CANÓNICA #1 (Eager Loading) ====================
            
        if ($documentos->isEmpty()) {
            Log::warning("Job 'NotificarDocumentosContratista' abortado: No se encontraron documentos válidos para los IDs proporcionados.");
            return;
        }

        $documentosPorContratista = $documentos->groupBy('contratista_id');

        foreach ($documentosPorContratista as $contratistaId => $docs) {
            $contratista = $docs->first()->contratista;
            $mandante = $docs->first()->mandante;

            // ================== INICIO: MODIFICACIÓN CANÓNICA #2 (Lógica de Destino) ==================
            // La antigua lógica es demolida. La nueva doctrina se instaura.
            // El objetivo es el email del usuario administrador de la plataforma.
            $emailDestino = $contratista->adminUser?->email;
            // =================== FIN: MODIFICACIÓN CANÓNICA #2 (Lógica de Destino) ====================

            if (!$emailDestino) {
                Log::warning("Contratista ID {$contratistaId} no tiene un email de administrador de plataforma válido. Omitiendo notificación.");
                continue;
            }
            
            $documentosVencidos = $docs->filter(function ($doc) {
                return $doc->resultado_validacion === 'Aprobado' && $doc->fecha_vencimiento;
            });

            $documentosRechazados = $docs->filter(function ($doc) {
                return $doc->resultado_validacion === 'Rechazado';
            });
            
            $mailable = new NotificacionDocumentos(
                $contratista,
                $documentosVencidos,
                $documentosRechazados,
                $mandante->razon_social,
                $this->mensajePersonalizado
            );
            $asuntoDelCorreo = $mailable->envelope()->subject;

            try {
                NotificacionEnviada::create([
                    'tipo_notificacion' => $this->tipoNotificacion,
                    'contratista_id' => $contratistaId,
                    'email_destino' => $emailDestino,
                    'asunto' => $asuntoDelCorreo,
                    'mensaje' => $this->mensajePersonalizado,
                    'documentos_notificados_ids' => $docs->pluck('id')->toArray(),
                    'despachado_por_user_id' => $this->usuarioId,
                ]);

                Mail::to($emailDestino)->send($mailable);

                Log::info("Registro y correo de notificación exitosos para {$emailDestino} (Contratista ID {$contratistaId}).");

            } catch (\Exception $e) {
                Log::error("Fallo al enviar el correo para Contratista ID {$contratistaId} (pero la notificación fue registrada). Error: " . $e->getMessage());
            }
        }
        
        Log::info("Job 'NotificarDocumentosContratista' completado.");
    }
}