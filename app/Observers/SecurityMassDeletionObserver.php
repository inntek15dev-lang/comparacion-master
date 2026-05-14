<?php

namespace App\Observers;

use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;

class SecurityMassDeletionObserver
{
    protected static $deletionCount = 0;
    protected $threshold = 10; // Umbral de alerta (más de 10 en un solo request/proceso)

    /**
     * Handle the model "deleted" event.
     */
    public function deleted($model): void
    {
        self::$deletionCount++;

        // Si superamos el umbral en este proceso
        if (self::$deletionCount === $this->threshold) {
            $user = Auth::user();
            $modelName = class_basename($model);

            AuditService::log(
                'seguridad-alerta',
                "Detección de borrado masivo en curso: El modelo [$modelName] está siendo eliminado en gran volumen.",
                [
                    'contexto' => 'BORRADO_MASIVO',
                    'modelo' => $modelName,
                    'conteo_actual' => self::$deletionCount,
                    'ejecutado_por' => $user ? $user->email : 'Sistema/CLI'
                ]
            );
        }
    }
}
