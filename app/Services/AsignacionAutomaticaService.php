<?php

namespace App\Services;

use App\Models\DocumentoCargado;
use App\Models\ConfiguracionAsignacionAutomatica;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AsignacionAutomaticaService
{
    /**
     * Intenta asignar un documento recién cargado a un validador según las reglas automáticas.
     *
     * @param DocumentoCargado $documento
     * @return void
     */
    public function intentarAsignar(DocumentoCargado $documento): void
    {
        Log::info("Iniciando intento de asignación automática para DocumentoCargado ID: {$documento->id}");

        // Buscar una configuración activa para el mandante del documento.
        $configuracion = ConfiguracionAsignacionAutomatica::where('mandante_id', $documento->mandante_id)
            ->where('is_active', true)
            ->first();

        if (!$configuracion) {
            Log::info("No se encontró configuración de asignación automática activa para el mandante ID: {$documento->mandante_id}.");
            return;
        }

        // Obtener los validadores activos asociados a la configuración.
        $validadores = $configuracion->validadores()->where('is_active', true)->get();

        if ($validadores->isEmpty()) {
            Log::warning("Configuración de asignación automática encontrada para mandante ID: {$documento->mandante_id}, pero no hay validadores activos asociados.");
            return;
        }

        // Lógica de Round-Robin usando el caché.
        $cacheKey = 'asignacion_automatica_contador_' . $documento->mandante_id;
        $indiceActual = Cache::get($cacheKey, 0);

        $validadorSeleccionado = $validadores[$indiceActual % $validadores->count()];

        // Incrementar y guardar el nuevo índice en el caché por un día.
        Cache::put($cacheKey, $indiceActual + 1, now()->addDay());

        // Actualizar el documento cargado.
        $documento->update([
            'asem_validador_id' => $validadorSeleccionado->id,
            'estado_validacion' => 'Asignado',
        ]);

        Log::info("Documento ID: {$documento->id} asignado automáticamente al validador ID: {$validadorSeleccionado->id} ({$validadorSeleccionado->name}).");
    }
}