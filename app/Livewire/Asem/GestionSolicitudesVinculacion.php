<?php

namespace App\Livewire\Asem;

use Livewire\Component;
use App\Models\SolicitudVinculacion;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\ContratistaUnidadOrganizacional;
use App\Models\OnboardingContratista;
use App\Models\Contratista;
use App\Models\Mandante;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class GestionSolicitudesVinculacion extends Component
{
    use WithPagination;

    public bool $showModalAprobacion = false;
    public ?SolicitudVinculacion $solicitudParaAprobar = null;
    public $unidadesOrganizacionalesDisponibles = [];
    public $unidadOrganizacionalSeleccionadaId = null;
    public array $vinculacionesSeleccionadas = []; // Para sub-contratistas (múltiples vinculaciones)
    public bool $esSubContratista = false; // Indica si la solicitud es de sub-contratista
    public $vinculacionesPadreDisponibles = []; // Vinculaciones del contratista padre
    public bool $showModalRechazo = false;
    public ?SolicitudVinculacion $solicitudParaRechazar = null;
    public string $motivoRechazo = '';
    public string $search = '';
    public string $filtroEstado = 'PENDIENTE';
    public array $nombresPasos = [
        1 => 'Capacitación Carga Docs.',
        2 => 'Prueba de Carga',
        3 => 'Paso Genérico 3',
        4 => 'Paso Genérico 4',
        5 => 'Paso Genérico 5',
        6 => 'Paso Genérico 6',
        7 => 'Paso Genérico 7',
    ];
    public array $pasoData = [];
    public array $comentariosOnboarding = [];

    // ================== INICIO DE LA MODIFICACIÓN CANÓNICA ==================
    public bool $esAdminAsem = false;

    public function mount()
    {
        $this->esAdminAsem = auth()->user()->hasRole('ASEM_Admin');
        
        // Si el usuario no es ASEM_Admin, no puede ver la vista de Onboarding.
        // Se le redirige al estado por defecto 'PENDIENTE'.
        if (!$this->esAdminAsem && $this->filtroEstado === 'APROBADA') {
            $this->filtroEstado = 'PENDIENTE';
        }
    }
    // ================== FIN DE LA MODIFICACIÓN CANÓNICA ====================

    protected function rules()
    {
        // Para sub-contratistas usamos vinculaciones seleccionadas (array)
        if ($this->esSubContratista) {
            return [
                'vinculacionesSeleccionadas' => 'required|array|min:1',
                'vinculacionesSeleccionadas.*' => 'exists:contratista_unidad_organizacional,id',
                'motivoRechazo' => 'required|string|min:10|max:500',
            ];
        }
        return [
            'unidadOrganizacionalSeleccionadaId' => 'required|exists:unidades_organizacionales_mandante,id',
            'motivoRechazo' => 'required|string|min:10|max:500',
        ];
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado()
    {
        $this->resetPage();
    }

    protected $messages = [
        'unidadOrganizacionalSeleccionadaId.required' => 'Debe seleccionar una Unidad Organizacional para vincular al contratista.',
        'motivoRechazo.required' => 'El motivo de rechazo es obligatorio.',
        'motivoRechazo.min' => 'El motivo debe tener al menos 10 caracteres.',
    ];

    public function abrirModalAprobacion(int $solicitudId)
    {
        $this->solicitudParaAprobar = SolicitudVinculacion::with('contratista', 'mandante', 'contratistaPadre')->findOrFail($solicitudId);
        
        // Determinar si es solicitud de sub-contratista
        $this->esSubContratista = $this->solicitudParaAprobar->tipo_solicitud === 'SUBCONTRATISTA';
        
        if ($this->esSubContratista && $this->solicitudParaAprobar->contratista_padre_id) {
            // Para sub-contratistas: cargar vinculaciones del contratista padre
            $this->vinculacionesPadreDisponibles = ContratistaUnidadOrganizacional::where('contratista_id', $this->solicitudParaAprobar->contratista_padre_id)
                ->with(['unidadOrganizacionalMandante.mandante', 'dependencia'])
                ->get();
            $this->unidadesOrganizacionalesDisponibles = [];
        } else {
            // Para contratistas directos: cargar unidades del mandante
            $this->unidadesOrganizacionalesDisponibles = UnidadOrganizacionalMandante::where('mandante_id', $this->solicitudParaAprobar->mandante_id)
                ->where('is_active', true)
                ->orderBy('nombre_unidad')
                ->get();
            $this->vinculacionesPadreDisponibles = [];
        }
        
        $this->reset('unidadOrganizacionalSeleccionadaId', 'vinculacionesSeleccionadas');
        $this->resetValidation();
        $this->showModalAprobacion = true;
    }

    public function aprobarSolicitud()
    {
        DB::beginTransaction();
        try {
            if ($this->esSubContratista) {
                // Validar vinculaciones seleccionadas para sub-contratistas
                $this->validate([
                    'vinculacionesSeleccionadas' => 'required|array|min:1',
                ], [
                    'vinculacionesSeleccionadas.required' => 'Debe seleccionar al menos una vinculación.',
                    'vinculacionesSeleccionadas.min' => 'Debe seleccionar al menos una vinculación.',
                ]);
                
                // Crear vinculaciones basadas en las del padre
                foreach ($this->vinculacionesSeleccionadas as $vinculacionPadreId) {
                    $vinculacionPadre = ContratistaUnidadOrganizacional::find($vinculacionPadreId);
                    if ($vinculacionPadre) {
                        ContratistaUnidadOrganizacional::create([
                            'contratista_id' => $this->solicitudParaAprobar->contratista_id,
                            'unidad_organizacional_mandante_id' => $vinculacionPadre->unidad_organizacional_mandante_id,
                            'tipo_condicion_id' => $vinculacionPadre->tipo_condicion_id,
                            'dependencia_id' => $vinculacionPadre->dependencia_id,
                            'numero_contrato' => $vinculacionPadre->numero_contrato, // Heredar contrato
                            'contratista_padre_vinculacion_id' => $vinculacionPadreId, // Referencia a la vinculación padre
                        ]);
                    }
                }
            } else {
                // Contratista directo: validar y crear vinculación normal
                $this->validate(['unidadOrganizacionalSeleccionadaId' => 'required|exists:unidades_organizacionales_mandante,id']);
                
                ContratistaUnidadOrganizacional::create([
                    'contratista_id' => $this->solicitudParaAprobar->contratista_id,
                    'unidad_organizacional_mandante_id' => $this->unidadOrganizacionalSeleccionadaId,
                    'tipo_condicion_id' => null,
                ]);
            }
            
            // Activar contratista/sub-contratista
            $contratista = $this->solicitudParaAprobar->contratista;
            $contratista->estado_plataforma = 'Activo';
            $contratista->is_active = true;
            $contratista->save();
            
            // Actualizar solicitud
            $this->solicitudParaAprobar->estado = 'APROBADA';
            $this->solicitudParaAprobar->aprobado_por_user_id = auth()->id();
            $this->solicitudParaAprobar->fecha_aprobacion = now();
            $this->solicitudParaAprobar->save();
            
            // Crear onboarding
            OnboardingContratista::firstOrCreate(['contratista_id' => $contratista->id]);
            
            DB::commit();
            $mensaje = $this->esSubContratista 
                ? 'Solicitud aprobada. Sub-contratista vinculado con ' . count($this->vinculacionesSeleccionadas) . ' vinculación(es).'
                : 'Solicitud aprobada y contratista vinculado exitosamente.';
            session()->flash('message', $mensaje);
            $this->cerrarModalAprobacion();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Ocurrió un error al aprobar la solicitud: ' . $e->getMessage());
        }
    }

    public function cerrarModalAprobacion()
    {
        $this->showModalAprobacion = false;
        $this->solicitudParaAprobar = null;
    }

    public function abrirModalRechazo(int $solicitudId)
    {
        $this->solicitudParaRechazar = SolicitudVinculacion::with('contratista')->findOrFail($solicitudId);
        $this->reset('motivoRechazo');
        $this->resetValidation();
        $this->showModalRechazo = true;
    }

    public function rechazarSolicitud()
    {
        $this->validate(['motivoRechazo' => $this->rules()['motivoRechazo']]);
        DB::beginTransaction();
        try {
            $contratista = $this->solicitudParaRechazar->contratista;
            $contratista->estado_plataforma = 'Inactivo';
            $contratista->is_active = false;
            $contratista->save();
            $this->solicitudParaRechazar->estado = 'RECHAZADA';
            $this->solicitudParaRechazar->motivo_rechazo = $this->motivoRechazo;
            $this->solicitudParaRechazar->aprobado_por_user_id = auth()->id();
            $this->solicitudParaRechazar->fecha_aprobacion = now();
            $this->solicitudParaRechazar->save();
            DB::commit();
            session()->flash('message', 'Solicitud rechazada exitosamente.');
            $this->cerrarModalRechazo();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Ocurrió un error al rechazar la solicitud: ' . $e->getMessage());
        }
    }

    public function cerrarModalRechazo()
    {
        $this->showModalRechazo = false;
        $this->solicitudParaRechazar = null;
    }

    public function marcarPaso(int $contratistaId, int $pasoNumero)
    {
        $onboarding = OnboardingContratista::firstOrCreate(['contratista_id' => $contratistaId]);
        $campoCompleto = $this->getCampoPaso($pasoNumero, 'completo');
        $onboarding->update([$campoCompleto => !$onboarding->$campoCompleto]);
        $this->verificarCompletado($onboarding);
    }

    public function guardarDatosPaso(int $contratistaId, int $pasoNumero)
    {
        $onboarding = OnboardingContratista::firstOrCreate(['contratista_id' => $contratistaId]);
        $data = $this->pasoData[$contratistaId][$pasoNumero] ?? [];
        $onboarding->update([
            $this->getCampoPaso($pasoNumero, 'fecha') => $data['fecha'] ?? null,
            $this->getCampoPaso($pasoNumero, 'comentario') => $data['comentario'] ?? null,
            $this->getCampoPaso($pasoNumero, 'user_id') => auth()->id(),
        ]);
        $this->dispatch('datos-paso-guardados', contratistaId: $contratistaId, paso: $pasoNumero);
    }

    public function guardarComentarioGeneral(int $contratistaId)
    {
        $onboarding = OnboardingContratista::firstOrCreate(['contratista_id' => $contratistaId]);
        $comentario = $this->comentariosOnboarding[$contratistaId] ?? null;
        $onboarding->update(['comentarios_proceso' => $comentario]);
        $this->dispatch('comentario-guardado', contratistaId: $contratistaId);
    }

    public function revertirEstado(int $solicitudId, string $nuevoEstado)
    {
        $solicitud = SolicitudVinculacion::findOrFail($solicitudId);
        DB::beginTransaction();
        try {
            $contratista_id = $solicitud->contratista_id;
            
            // Eliminar la vinculación principal de la tabla pivote
            ContratistaUnidadOrganizacional::where('contratista_id', $contratista_id)
                ->whereHas('unidadOrganizacionalMandante', function ($query) use ($solicitud) {
                    $query->where('mandante_id', $solicitud->mandante_id);
                })->delete();

            // Verificar si el contratista tiene OTRAS solicitudes aprobadas
            $tieneOtrasAprobadas = SolicitudVinculacion::where('contratista_id', $contratista_id)
                ->where('id', '!=', $solicitudId)
                ->where('estado', 'APROBADA')
                ->exists();

            if ($nuevoEstado === 'PENDIENTE') {
                if (!$tieneOtrasAprobadas) {
                    $solicitud->contratista->update(['estado_plataforma' => 'Pendiente de Aprobacion', 'is_active' => false]);
                }
                $solicitud->update(['estado' => 'PENDIENTE', 'fecha_aprobacion' => null, 'aprobado_por_user_id' => null]);
            } elseif ($nuevoEstado === 'RECHAZADA') {
                if (!$tieneOtrasAprobadas) {
                    $solicitud->contratista->update(['estado_plataforma' => 'Inactivo', 'is_active' => false]);
                }
                $solicitud->update(['estado' => 'RECHAZADA', 'motivo_rechazo' => 'Estado revertido por administrador.']);
            }
            DB::commit();
            session()->flash('message', 'El estado de la solicitud ha sido revertido.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error al revertir el estado: ' . $e->getMessage());
        }
    }

    public function abrirModalManual()
    {
        return redirect()->route('gestion.solicitudes.crear-manual');
    }

    public function archivarCompletados()
    {
        OnboardingContratista::where('estado_onboarding', 'Completado')->update(['estado_onboarding' => 'Archivado']);
        session()->flash('message', 'Todos los contratistas con onboarding completo han sido archivados.');
    }

    private function getCampoPaso(int $paso, string $sufijo): string
    {
        $prefijo = 'paso' . $paso;
        if ($paso === 1) $prefijo = 'paso1_capacitacion';
        if ($paso === 2) $prefijo = 'paso2_prueba_carga';
        if ($paso > 2) $prefijo = 'paso' . $paso . '_generico';
        if (!in_array($sufijo, ['completo', 'fecha', 'user_id', 'comentario'])) {
            return 'paso' . $paso . '_generico_completo';
        }
        return $prefijo . '_' . $sufijo;
    }

    private function verificarCompletado(OnboardingContratista $onboarding)
    {
        $todosCompletos = true;
        for ($i = 1; $i <= 7; $i++) {
            if (!$onboarding->{$this->getCampoPaso($i, 'completo')}) {
                $todosCompletos = false;
                break;
            }
        }
        $onboarding->update(['estado_onboarding' => $todosCompletos ? 'Completado' : 'En Proceso']);
    }

    public function render()
    {
        $query = SolicitudVinculacion::query();

        // ================== INICIO DE LA MODIFICACIÓN CANÓNICA ==================
        // Aplicar filtro de soberanía para el Mandante
        if (!$this->esAdminAsem) {
            $mandanteId = auth()->user()->mandante_id;
            if ($mandanteId) {
                $query->where('mandante_id', $mandanteId);
            } else {
                // Si un usuario con rol de mandante no tiene un mandante_id asociado, no debe ver ninguna solicitud.
                $query->whereRaw('1 = 0');
            }
        }
        // ================== FIN DE LA MODIFICACIÓN CANÓNICA ====================

        if ($this->filtroEstado === 'APROBADA') {
            $query->with(['contratista.onboarding', 'mandante', 'aprobador'])
                  ->where(function ($q) {
                      $q->whereHas('contratista.onboarding', function ($subQ) {
                          $subQ->where('estado_onboarding', '!=', 'Archivado');
                      })
                      ->orWhereDoesntHave('contratista.onboarding');
                  });
        } else {
            $query->with(['contratista', 'mandante', 'contratistaPadre', 'aprobador']);
        }
        $query->where('estado', $this->filtroEstado);
        if ($this->search) {
            $query->whereHas('contratista', function ($q) {
                $q->where('razon_social', 'like', '%' . $this->search . '%')
                  ->orWhere('rut', 'like', '%' . $this->search . '%');
            });
        }
        $solicitudes = $query->orderBy('created_at', 'desc')->paginate(3);
        if ($this->filtroEstado === 'APROBADA') {
            foreach ($solicitudes as $solicitud) {
                $contratistaId = $solicitud->contratista->id;
                if (!isset($this->comentariosOnboarding[$contratistaId])) {
                    $this->comentariosOnboarding[$contratistaId] = $solicitud->contratista->onboarding->comentarios_proceso ?? '';
                }
                for ($i = 1; $i <= 7; $i++) {
                    if (!isset($this->pasoData[$contratistaId][$i])) {
                        $this->pasoData[$contratistaId][$i] = [
                            'fecha' => $solicitud->contratista->onboarding?->{$this->getCampoPaso($i, 'fecha')}?->format('Y-m-d'),
                            'comentario' => $solicitud->contratista->onboarding?->{$this->getCampoPaso($i, 'comentario')},
                        ];
                    }
                }
            }
        }
        return view('livewire.asem.gestion-solicitudes-vinculacion', [
            'solicitudes' => $solicitudes,
        ]);
    }
}