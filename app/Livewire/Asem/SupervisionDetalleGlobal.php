<?php

namespace App\Livewire\Asem;

use Livewire\Component;
use App\Models\Contratista;
use App\Models\Mandante;
use App\Models\DocumentoExcepcionCriticidad;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Attributes\Layout;
use App\Models\Dependencia;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\Trabajador;
use App\Models\TrabajadorVinculacion;
use App\Services\EstadoCumplimientoService;

#[Layout('layouts.app')]
class SupervisionDetalleGlobal extends Component
{
    public Contratista $contratista;
    public Mandante $mandante;
    public Dependencia $lugarDeTrabajo;
    public UnidadOrganizacionalMandante $uo;

    #[Url(as: 'pestaña')]
    public $pestañaActiva = 'trabajadores';

    public bool $showAnulacionModal = false;
    public $recursoSeleccionado = null;
    public ?string $recursoType = null;
    public ?string $accionAnulacion = null;
    public string $justificacion = '';
    public ?string $valido_hasta = null;

    public function mount($contratistaId, $mandanteId, $lugarDeTrabajoId, $uoId)
    {
        $this->contratista = Contratista::findOrFail($contratistaId);
        $this->mandante = Mandante::findOrFail($mandanteId);
        $this->lugarDeTrabajo = Dependencia::findOrFail($lugarDeTrabajoId);
        $this->uo = UnidadOrganizacionalMandante::findOrFail($uoId);
    }

    public function seleccionarPestaña($pestaña)
    {
        $this->pestañaActiva = $pestaña;
    }

    public function abrirModalAnulacion($recursoId, $recursoType, $accion)
    {
        $this->recursoSeleccionado = $recursoType::find($recursoId);
        $this->recursoType = $recursoType;
        $this->accionAnulacion = $accion;
        $this->justificacion = '';
        $this->valido_hasta = null;
        $this->resetErrorBag();
        $this->showAnulacionModal = true;
    }

    public function cerrarModalAnulacion()
    {
        $this->showAnulacionModal = false;
    }

    public function guardarAnulacionAcceso()
    {
        $this->validate([
            'justificacion' => 'required|string|min:20',
            'valido_hasta' => 'nullable|date|after_or_equal:today',
        ]);

        try {
            $placeholderDocumentoId = 99999;

            DocumentoExcepcionCriticidad::updateOrCreate(
                [
                    'mandante_id' => $this->mandante->id,
                    'excepcionable_type' => $this->recursoType,
                    'excepcionable_id' => $this->recursoSeleccionado->id,
                    'nombre_documento_id' => $placeholderDocumentoId,
                ],
                [
                    'accion_override' => $this->accionAnulacion,
                    'justificacion' => $this->justificacion,
                    'valido_hasta' => $this->valido_hasta,
                    'created_by_user_id' => Auth::id(),
                    'afecta_cumplimiento_override' => null,
                    'restringe_acceso_override' => null,
                    'es_perseguidor_override' => null,
                ]
            );

            if ($this->recursoSeleccionado instanceof Trabajador) {
                Log::info('Buscando vinculación para actualizar tras anulación manual...', [
                    'trabajador_id' => $this->recursoSeleccionado->id,
                    'uo_id' => $this->uo->id,
                    'dependencia_id' => $this->lugarDeTrabajo->id,
                ]);

                $vinculacion = TrabajadorVinculacion::where('trabajador_id', $this->recursoSeleccionado->id)
                    ->where('unidad_organizacional_mandante_id', $this->uo->id)
                    ->where('dependencia_id', $this->lugarDeTrabajo->id)
                    ->first();

                if ($vinculacion) {
                    Log::info('Vinculación encontrada (ID: ' . $vinculacion->id . '). Actualizando con anulación manual.');
                    $estadoService = app(EstadoCumplimientoService::class);
                    $estadoService->actualizarEstadoParaVinculacion($vinculacion);
                    Log::info('Anulación manual aplicada a vinculación ID: ' . $vinculacion->id);
                } else {
                    Log::error('¡FALLO CRÍTICO! No se encontró la vinculación para aplicar anulación manual.');
                }
            }

            $this->dispatch('notificacion-exito', 'La anulación de acceso ha sido registrada correctamente.');
            $this->cerrarModalAnulacion();
        } catch (\Exception $e) {
            Log::error("Error al guardar anulación de acceso: " . $e->getMessage());
            $this->dispatch('notificacion-error', 'Ocurrió un error al registrar la anulación.');
        }
    }

    /**
     * ================== MÉTODO MODIFICADO PARA REVERSIÓN CORRECTA ==================
     * 
     * Revierte una anulación manual y FUERZA el recálculo del estado.
     * 
     * @param int $recursoId ID del recurso
     * @param string $recursoType Clase del recurso
     * @return void
     */
    public function revertirAnulacionManual($recursoId, $recursoType)
    {
        try {
            $placeholderDocumentoId = 99999;

            Log::info('===== INICIANDO REVERSIÓN DE ANULACIÓN MANUAL =====', [
                'recurso_id' => $recursoId,
                'recurso_type' => $recursoType,
                'mandante_id' => $this->mandante->id,
            ]);

            // PASO 1: Buscar y eliminar la excepción de la tabla
            $anulacion = DocumentoExcepcionCriticidad::where('mandante_id', $this->mandante->id)
                ->where('excepcionable_type', $recursoType)
                ->where('excepcionable_id', $recursoId)
                ->where('nombre_documento_id', $placeholderDocumentoId)
                ->first();

            if (!$anulacion) {
                Log::warning('No se encontró anulación manual para revertir.', [
                    'recurso_id' => $recursoId,
                    'recurso_type' => $recursoType,
                ]);
                $this->dispatch('notificacion-info', 'No se encontró una anulación manual para revertir.');
                return;
            }

            $recurso = $anulacion->excepcionable;
            
            Log::info('Anulación manual encontrada. Eliminando registro...', [
                'anulacion_id' => $anulacion->id,
                'accion_override' => $anulacion->accion_override,
                'justificacion' => $anulacion->justificacion,
            ]);

            $anulacion->delete();

            Log::info('Registro de anulación eliminado correctamente.');

            // PASO 2: Si es un trabajador, buscar su vinculación y FORZAR el recálculo
            if ($recurso instanceof Trabajador) {
                Log::info('Recurso es Trabajador. Buscando vinculación para FORZAR recálculo...', [
                    'trabajador_id' => $recurso->id,
                    'uo_id' => $this->uo->id,
                    'dependencia_id' => $this->lugarDeTrabajo->id,
                ]);

                $vinculacion = TrabajadorVinculacion::where('trabajador_id', $recurso->id)
                    ->where('unidad_organizacional_mandante_id', $this->uo->id)
                    ->where('dependencia_id', $this->lugarDeTrabajo->id)
                    ->first();

                if (!$vinculacion) {
                    Log::error('¡ERROR CRÍTICO! No se encontró la vinculación para recalcular tras revertir.', [
                        'trabajador_id' => $recurso->id,
                        'uo_id' => $this->uo->id,
                        'dependencia_id' => $this->lugarDeTrabajo->id,
                    ]);
                    $this->dispatch('notificacion-error', 'Error: No se pudo localizar la vinculación del trabajador.');
                    return;
                }

                Log::info('Vinculación encontrada. Estado ANTES de limpiar:', [
                    'vinculacion_id' => $vinculacion->id,
                    'estado_acceso_actual' => $vinculacion->estado_acceso,
                    'porcentaje_cumplimiento_actual' => $vinculacion->porcentaje_cumplimiento,
                ]);

                // PASO 3: Usar el método de recálculo FORZADO
                $estadoService = app(EstadoCumplimientoService::class);
                $estadoService->recalcularEstadoForzado($vinculacion);

                // PASO 4: Refrescar la vinculación y verificar
                $vinculacion->refresh();

                Log::info('===== REVERSIÓN COMPLETADA CON ÉXITO =====', [
                    'vinculacion_id' => $vinculacion->id,
                    'estado_acceso_nuevo' => $vinculacion->estado_acceso,
                    'porcentaje_cumplimiento_nuevo' => $vinculacion->porcentaje_cumplimiento,
                ]);
            }

            $this->dispatch('notificacion-exito', 'La anulación manual ha sido revertida. El estado del recurso ahora es calculado por el sistema.');

        } catch (\Exception $e) {
            Log::error('===== ERROR EN REVERSIÓN DE ANULACIÓN MANUAL =====', [
                'recurso_id' => $recursoId,
                'recurso_type' => $recursoType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('notificacion-error', 'Ocurrió un error al revertir la anulación: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.asem.supervision-detalle-global');
    }
}