<?php

namespace App\Services;

use App\Models\Contratista;
use App\Models\Trabajador;
use App\Models\Vehiculo;
use App\Models\Maquinaria;
use App\Models\Embarcacion;
use App\Models\TrabajadorVinculacion;
use App\Models\ContratistaUnidadOrganizacional;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EstadoCumplimientoService
{
    private CriticidadDocumentoService $criticidadService;

    public function __construct(CriticidadDocumentoService $criticidadService)
    {
        $this->criticidadService = $criticidadService;
    }

    public function actualizarEstadoParaRecurso(Model $recurso): void
    {
        if ($recurso instanceof Contratista) {
            $this->actualizarEstadoParaContratista($recurso);
            return;
        }

        if (!method_exists($recurso, 'vinculaciones')) {
            return;
        }

        $vinculacionesActivas = $recurso->vinculaciones()->where('is_active', true)->get();

        foreach ($vinculacionesActivas as $vinculacion) {
            $this->actualizarEstadoParaVinculacion($vinculacion);
        }
    }

    public function actualizarEstadoParaContratista(Contratista $contratista): void
    {
        $vinculaciones = ContratistaUnidadOrganizacional::where('contratista_id', $contratista->id)->get();
        foreach ($vinculaciones as $vinculacion) {
            $this->actualizarEstadoParaVinculacion($vinculacion);
        }
    }

    public function actualizarEstadoParaVinculacion(Pivot $vinculacion): void
    {
        try {
            $estadoAccesoActual = $vinculacion->estado_acceso;
            if (isset($estadoAccesoActual['es_excepcion']) && $estadoAccesoActual['es_excepcion'] === true) {
                Log::info('Cálculo de estado omitido: Se respeta la anulación manual existente.', [
                    'vinculacion_id' => $vinculacion->id,
                    'vinculacion_type' => get_class($vinculacion),
                    'estado_actual' => $estadoAccesoActual,
                ]);
                return;
            }

            $recurso = null;
            $mandanteId = null;
            $uoId = null;

            if ($vinculacion instanceof ContratistaUnidadOrganizacional) {
                $recurso = $vinculacion->contratista;
                $uoId = $vinculacion->unidad_organizacional_mandante_id;
                $mandanteData = DB::table('unidades_organizacionales_mandante')->where('id', $uoId)->select('mandante_id')->first();
                $mandanteId = $mandanteData->mandante_id ?? null;
            } else {
                $recurso = $vinculacion->trabajador ?? $vinculacion->vehiculo ?? $vinculacion->maquinaria ?? $vinculacion->embarcacion;
                $uoId = $vinculacion->unidad_organizacional_mandante_id;
                $mandanteData = DB::table('unidades_organizacionales_mandante')->where('id', $uoId)->select('mandante_id')->first();
                $mandanteId = $mandanteData->mandante_id ?? null;
            }

            if (!$recurso || !$mandanteId || !$uoId) {
                Log::warning('No se pudo actualizar estado: datos de contexto incompletos.', ['vinculacion_id' => $vinculacion->id, 'vinculacion_type' => get_class($vinculacion)]);
                return;
            }

            $porcentaje = $this->criticidadService->calcularPorcentajeCumplimientoParaEntidad($recurso, $mandanteId, $uoId);
            $acceso = $this->criticidadService->determinarAccesoFinalRecurso($recurso, $mandanteId, $uoId);

            $vinculacion->porcentaje_cumplimiento = $porcentaje;
            $vinculacion->estado_acceso = $acceso;
            $vinculacion->save();

            Log::info('¡VICTORIA! Vinculación actualizada y guardada en la base de datos.', [
                'vinculacion_id' => $vinculacion->id,
                'vinculacion_type' => get_class($vinculacion),
                'nuevo_porcentaje' => $porcentaje,
                'nuevo_estado_acceso' => $acceso,
            ]);

        } catch (\Exception $e) {
            Log::error('Error en EstadoCumplimientoService::actualizarEstadoParaVinculacion', [
                'vinculacion_id' => $vinculacion->id,
                'vinculacion_type' => get_class($vinculacion),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * ================== NUEVO MÉTODO PARA RECÁLCULO FORZADO ==================
     * 
     * Este método FUERZA el recálculo del estado ignorando cualquier excepción manual.
     * Se debe usar SOLO cuando se revierte una anulación manual.
     * 
     * @param Pivot $vinculacion La vinculación a recalcular
     * @return void
     */
    public function recalcularEstadoForzado(Pivot $vinculacion): void
    {
        try {
            Log::info('RECÁLCULO FORZADO INICIADO: Limpiando estado_acceso para forzar recálculo.', [
                'vinculacion_id' => $vinculacion->id,
                'vinculacion_type' => get_class($vinculacion),
                'estado_acceso_anterior' => $vinculacion->estado_acceso,
            ]);

            // PASO 1: LIMPIAR el campo estado_acceso para eliminar cualquier flag de excepción
            $vinculacion->estado_acceso = null;
            $vinculacion->porcentaje_cumplimiento = null;
            $vinculacion->save();

            Log::info('Estado limpiado. Procediendo a recalcular...', [
                'vinculacion_id' => $vinculacion->id,
            ]);

            // PASO 2: Refrescar la vinculación desde la BD para asegurar que no hay datos en caché
            $vinculacion->refresh();

            // PASO 3: Llamar al método normal de actualización (ahora no encontrará es_excepcion)
            $this->actualizarEstadoParaVinculacion($vinculacion);

            Log::info('¡RECÁLCULO FORZADO COMPLETADO CON ÉXITO!', [
                'vinculacion_id' => $vinculacion->id,
                'estado_acceso_nuevo' => $vinculacion->fresh()->estado_acceso,
                'porcentaje_cumplimiento_nuevo' => $vinculacion->fresh()->porcentaje_cumplimiento,
            ]);

        } catch (\Exception $e) {
            Log::error('Error en recalcularEstadoForzado', [
                'vinculacion_id' => $vinculacion->id,
                'vinculacion_type' => get_class($vinculacion),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}