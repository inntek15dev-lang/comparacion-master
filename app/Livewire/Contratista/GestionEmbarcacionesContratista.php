<?php

namespace App\Livewire\Contratista;

use Livewire\Component;
use App\Models\Embarcacion;
use App\Models\Contratista;
use App\Models\Mandante;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\EmbarcacionAsignacion;
use App\Models\TipoEmbarcacion;
use App\Models\TenenciaVehiculo;
use App\Models\Dependencia;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\DocumentoRequeridoService;
use App\Services\CriticidadDocumentoService;

class GestionEmbarcacionesContratista extends Component
{
    use WithPagination;

    public ?int $mandanteId = null;
    public ?int $unidadOrganizacionalId = null;
    public $lugarDeTrabajoId = null;
    public string $nombreVinculacionSeleccionada = '';
    public string $vistaActual = 'listado_embarcaciones';
    public ?Embarcacion $embarcacionSeleccionada = null;
    public $contratistaId;
    public string $searchEmbarcacion = '';
    public string $sortByEmbarcacion = 'embarcaciones.id';
    public string $sortDirectionEmbarcacion = 'asc';
    public bool $showModalFichaEmbarcacion = false;
    public ?int $embarcacionId = null;
    public string $matricula_letras = '', $matricula_numeros = '';
    public ?string $ano_fabricacion = null;
    public ?int $tipo_embarcacion_id = null, $tenencia_vehiculo_id = null;
    public bool $embarcacion_is_active = true;
    
    public bool $showModalNuevaVinculacion = false;
    public ?int $vinculacionId = null;
    public ?int $v_mandante_id = null;
    public ?int $v_unidad_organizacional_mandante_id = null;
    public $v_dependencia_id = null;
    public ?string $v_fecha_asignacion = null;
    public bool $v_is_active = true;
    public ?string $v_fecha_desasignacion = null;
    public ?string $v_motivo_desasignacion = null;

    public $tiposEmbarcacion, $tenencias;
    public $mandantesDisponibles = [], $unidadesOrganizacionalesDisponibles = [], $dependenciasDisponibles = [];
    
    private DocumentoRequeridoService $documentoService;
    private CriticidadDocumentoService $criticidadService;
    public ?int $contratistaIdForzado = null;
    public ?int $abrirModalParaId = null;
    
    public bool $puedeEstarEnReserva = false;

    public array $documentosMaestros = [];
    public string $filtroEstado = 'activos';

    protected $listeners = ['documentosActualizados' => '$refresh'];

    public function boot(DocumentoRequeridoService $documentoService, CriticidadDocumentoService $criticidadService)
    {
        $this->documentoService = $documentoService;
        $this->criticidadService = $criticidadService;
    }

    protected function messages()
    {
        return [
            '*.required' => 'Este campo es obligatorio.',
            'matricula_letras.unique' => 'La matrícula ingresada ya existe para su empresa.',
            'v_unidad_organizacional_mandante_id.required' => 'Debe seleccionar una Unidad Organizacional.',
            'v_dependencia_id.required_without_all' => 'Debe seleccionar un Lugar de Trabajo.',
        ];
    }

    public function mount(?int $mandanteId = null, ?int $unidadOrganizacionalId = null, $lugarDeTrabajoId = null, array $documentosMaestros = [])
    {
        $this->mandanteId = $mandanteId;
        $this->unidadOrganizacionalId = $unidadOrganizacionalId;
        $this->lugarDeTrabajoId = $lugarDeTrabajoId;
        $this->documentosMaestros = $documentosMaestros;

        if ($this->contratistaIdForzado) {
            $this->contratistaId = $this->contratistaIdForzado;
        } else {
            $this->contratistaId = Auth::user()->contratista_id;
        }

        if (!$this->contratistaId) {
            session()->flash('error', 'Usuario no asociado a un contratista válido.');
            return;
        }

        $this->tiposEmbarcacion = TipoEmbarcacion::where('is_active', true)->orderBy('nombre')->get();
        $this->tenencias = TenenciaVehiculo::where('is_active', true)->orderBy('nombre')->get();
        $this->mandantesDisponibles = Mandante::whereHas('unidadesOrganizacionales.contratistasHabilitados', function ($query) {
            $query->where('contratistas.id', $this->contratistaId);
        })->orderBy('razon_social')->get();
    }

    // ================== INICIO DE LA MODIFICACIÓN (RESETEO DE PAGINACIÓN) ==================
    public function updatedSearchEmbarcacion()
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado()
    {
        $this->resetPage();
    }
    // ================== FIN DE LA MODIFICACIÓN ========================================

    public function eliminarEmbarcacion($id)
    {
        $embarcacion = Embarcacion::where('id', $id)->where('contratista_id', $this->contratistaId)->first();
        if ($embarcacion) {
            try {
                $embarcacion->forceDelete();
                session()->flash('message_embarcacion', 'Embarcación y todas sus asignaciones han sido eliminadas PERMANENTEMENTE.');
                $this->cerrarModalFichaEmbarcacion();
                $this->irAListadoEmbarcaciones();
            } catch (\Exception $e) {
                Log::error("Error al eliminar permanentemente embarcación ID {$id}: " . $e->getMessage());
                session()->flash('error_embarcacion', 'Ocurrió un error al eliminar la embarcación.');
            }
        }
    }

    public function eliminarVinculacion($vinculacionId)
    {
        $vinculacion = EmbarcacionAsignacion::with('embarcacion')->find($vinculacionId);
        if ($vinculacion && $vinculacion->embarcacion->contratista_id == $this->contratistaId) {

            if ($vinculacion->embarcacion->vinculaciones()->count() <= 1) {
                session()->flash('error_vinculacion', 'Acción no permitida. No se puede eliminar la última asignación de una embarcación. Para desvincularla, edite la asignación para moverla a "Reserva" o desactive la embarcación desde su ficha.');
                return;
            }

            try {
                $vinculacion->delete();
                session()->flash('message_vinculacion', 'La asignación ha sido eliminada correctamente.');
            } catch (\Exception $e) {
                Log::error("Error al eliminar vinculación de embarcación ID {$vinculacionId}: " . $e->getMessage());
                session()->flash('error_vinculacion', 'Ocurrió un error al eliminar la asignación.');
            }
        } else {
            session()->flash('error_vinculacion', 'No se pudo eliminar la asignación. No fue encontrada o no pertenece a su empresa.');
        }
    }

    public function seleccionarEmbarcacionParaVinculaciones($embarcacionId)
    {
        $this->embarcacionSeleccionada = Embarcacion::find($embarcacionId);
        if ($this->embarcacionSeleccionada && $this->embarcacionSeleccionada->contratista_id == $this->contratistaId) {
            $this->vistaActual = 'listado_vinculaciones';
            $this->resetPage('vinculacionesPage');
        }
    }

    public function irAListadoEmbarcaciones()
    {
        $this->vistaActual = 'listado_embarcaciones';
        $this->embarcacionSeleccionada = null;
        $this->resetPage('embarcacionesPage');
    }

    public function rulesFichaEmbarcacion()
    {
        $rules = [
            'matricula_letras' => ['required', 'string', 'min:2', 'max:10', Rule::unique('embarcaciones')->where(fn($query) => $query->where('contratista_id', $this->contratistaId)->where('matricula_numeros', $this->matricula_numeros))->ignore($this->embarcacionId)],
            'matricula_numeros' => 'required|string|min:1|max:10',
            'ano_fabricacion' => 'required|integer|digits:4|min:1950|max:' . (date('Y') + 1),
            'tipo_embarcacion_id' => 'required|exists:tipos_embarcacion,id',
            'tenencia_vehiculo_id' => 'nullable|exists:tenencias_vehiculo,id',
            'embarcacion_is_active' => 'boolean',
        ];

        if (!$this->embarcacionId) {
            $rules['v_unidad_organizacional_mandante_id'] = 'required|exists:unidades_organizacionales_mandante,id';
        }

        return $rules;
    }

    private function resetFichaEmbarcacionFields()
    {
        $this->embarcacionId = null;
        $this->matricula_letras = '';
        $this->matricula_numeros = '';
        $this->ano_fabricacion = null;
        $this->tipo_embarcacion_id = null;
        $this->tenencia_vehiculo_id = null;
        $this->embarcacion_is_active = true;
        $this->v_unidad_organizacional_mandante_id = null;
        $this->resetValidation();
    }

    public function abrirModalNuevaEmbarcacion()
    {
        if (!$this->mandanteId || !$this->lugarDeTrabajoId || in_array($this->lugarDeTrabajoId, ['orphaned', 'in_reserve'])) {
            session()->flash('error', 'Debe seleccionar un Lugar de Trabajo específico y válido para agregar una nueva embarcación.');
            return;
        }
        $this->resetFichaEmbarcacionFields();
        $this->unidadesOrganizacionalesDisponibles = UnidadOrganizacionalMandante::where('mandante_id', $this->mandanteId)
            ->where('is_active', true)
            ->whereHas('contratistasHabilitados', fn ($q) => $q->where('contratista_id', $this->contratistaId))
            ->get()->sortBy('nombre_jerarquico');
        $this->showModalFichaEmbarcacion = true;
    }

    public function abrirModalEditarEmbarcacion($id)
    {
        $embarcacion = Embarcacion::find($id);
        if ($embarcacion && $embarcacion->contratista_id == $this->contratistaId) {
            $this->embarcacionId = $embarcacion->id;
            $this->embarcacionSeleccionada = $embarcacion;
            $this->matricula_letras = $embarcacion->matricula_letras;
            $this->matricula_numeros = $embarcacion->matricula_numeros;
            $this->ano_fabricacion = $embarcacion->ano_fabricacion;
            $this->tipo_embarcacion_id = $embarcacion->tipo_embarcacion_id;
            $this->tenencia_vehiculo_id = $embarcacion->tenencia_vehiculo_id;
            $this->embarcacion_is_active = $embarcacion->is_active;
            $this->showModalFichaEmbarcacion = true;
        }
    }

    public function guardarEmbarcacion()
    {
        if (!$this->embarcacionId) {
            if (!$this->mandanteId) {
                session()->flash('error_embarcacion', 'El contexto del Mandante no está definido para crear una nueva embarcación.');
                return;
            }
            if (!$this->lugarDeTrabajoId || in_array($this->lugarDeTrabajoId, ['orphaned', 'in_reserve'])) {
                session()->flash('error_embarcacion', 'Debe seleccionar un Lugar de Trabajo válido para crear una nueva embarcación.');
                return;
            }
        }

        $validatedData = $this->validate($this->rulesFichaEmbarcacion());
        $dataToSave = collect($validatedData)->except('v_unidad_organizacional_mandante_id')->toArray();
        $dataToSave['contratista_id'] = $this->contratistaId;

        DB::beginTransaction();
        try {
            $embarcacion = Embarcacion::updateOrCreate(['id' => $this->embarcacionId], $dataToSave);
            $esNuevaEmbarcacion = !$this->embarcacionId;

            if ($esNuevaEmbarcacion) {
                EmbarcacionAsignacion::create([
                    'embarcacion_id' => $embarcacion->id,
                    'unidad_organizacional_mandante_id' => $validatedData['v_unidad_organizacional_mandante_id'],
                    'dependencia_id' => $this->lugarDeTrabajoId,
                    'fecha_asignacion' => now(),
                    'is_active' => true,
                ]);
                session()->flash('message_embarcacion', 'Embarcación agregada y vinculada correctamente.');
            } else {
                session()->flash('message_embarcacion', 'Ficha de la embarcación actualizada correctamente.');
            }
            DB::commit();
            $this->cerrarModalFichaEmbarcacion();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al guardar embarcación: " . $e->getMessage());
            session()->flash('error_embarcacion', 'Ocurrió un error al guardar la ficha de la embarcación.');
        }
    }

    public function cerrarModalFichaEmbarcacion()
    {
        $this->showModalFichaEmbarcacion = false;
        $this->resetFichaEmbarcacionFields();
    }

    public function toggleActivoEmbarcacion($embarcacionId)
    {
        $embarcacion = Embarcacion::find($embarcacionId);
        if ($embarcacion && $embarcacion->contratista_id == $this->contratistaId) {
            $embarcacion->is_active = !$embarcacion->is_active;
            $embarcacion->save();
            session()->flash('message_embarcacion', 'Estado de la embarcación cambiado correctamente.');
        }
    }

    public function rulesVinculacion()
    {
        // ================== INICIO DE LA MODIFICACIÓN (VALIDACIÓN DE DUPLICADOS) ==================
        $rules = [
            'v_mandante_id' => 'required|exists:mandantes,id',
            'v_unidad_organizacional_mandante_id' => ['required', 'exists:unidades_organizacionales_mandante,id'],
            'v_dependencia_id' => 'nullable|exists:dependencias,id',
            'v_fecha_asignacion' => 'required|date',
            'v_is_active' => 'required|boolean',
            'v_fecha_desasignacion' => 'nullable|required_if:v_is_active,false|date|after_or_equal:v_fecha_asignacion',
            'v_motivo_desasignacion' => 'nullable|required_if:v_is_active,false|string|max:500',
        ];

        $rules['v_unidad_organizacional_mandante_id'][] = function ($attribute, $value, $fail) {
            if ($this->v_is_active && $this->v_dependencia_id) {
                if (!$this->embarcacionSeleccionada) {
                    $fail('No hay una embarcación seleccionada para esta validación.'); 
                    return;
                }
                $query = EmbarcacionAsignacion::where('embarcacion_id', $this->embarcacionSeleccionada->id)
                    ->where('unidad_organizacional_mandante_id', $value)
                    ->where('dependencia_id', $this->v_dependencia_id)
                    ->where('is_active', true);
                
                if ($this->vinculacionId) {
                    $query->where('id', '!=', $this->vinculacionId);
                }

                if ($query->exists()) {
                    $fail('La embarcación ya tiene una asignación activa en esta U.O. y Lugar de Trabajo.');
                }
            }
        };
        return $rules;
        // ================== FIN DE LA MODIFICACIÓN ========================================
    }

    public function updatedVMandanteId($mandanteId)
    {
        $this->unidadesOrganizacionalesDisponibles = [];
        $this->dependenciasDisponibles = [];
        $this->v_unidad_organizacional_mandante_id = null;
        $this->v_dependencia_id = null;
        if ($mandanteId) {
            $this->unidadesOrganizacionalesDisponibles = UnidadOrganizacionalMandante::where('mandante_id', $mandanteId)->where('is_active', true)->whereHas('contratistasHabilitados', fn ($q) => $q->where('contratista_id', $this->contratistaId))->get()->sortBy('nombre_jerarquico');
            $contratista = Contratista::find($this->contratistaId);
            if ($contratista) {
                $this->dependenciasDisponibles = $contratista->dependencias()->where('mandante_id', $mandanteId)->where('estado', true)->get()->sortBy('nombre_jerarquico');
            }
        }
    }

    private function resetVinculacionFields()
    {
        $this->vinculacionId = null;
        $this->v_mandante_id = null;
        $this->v_unidad_organizacional_mandante_id = null;
        $this->v_dependencia_id = null;
        $this->v_fecha_asignacion = null;
        $this->v_is_active = true;
        $this->v_fecha_desasignacion = null;
        $this->v_motivo_desasignacion = null;
        $this->unidadesOrganizacionalesDisponibles = [];
        $this->dependenciasDisponibles = [];
        $this->resetValidation();
    }

    public function abrirModalNuevaVinculacion()
    {
        if (!$this->embarcacionSeleccionada || !$this->mandanteId) return;
        $this->resetVinculacionFields();
        $this->v_mandante_id = $this->mandanteId;
        $this->updatedVMandanteId($this->v_mandante_id);
        $this->v_fecha_asignacion = now()->format('Y-m-d');
        $this->showModalNuevaVinculacion = true;
    }

    public function abrirModalEditarVinculacion($id)
    {
        $vinculacion = EmbarcacionAsignacion::find($id);
        if ($vinculacion && $vinculacion->embarcacion_id == $this->embarcacionSeleccionada?->id) {
            $this->vinculacionId = $vinculacion->id;
            $this->v_mandante_id = $vinculacion->unidadOrganizacionalMandante?->mandante_id;
            $this->updatedVMandanteId($this->v_mandante_id);
            $this->v_unidad_organizacional_mandante_id = $vinculacion->unidad_organizacional_mandante_id;
            $this->v_dependencia_id = $vinculacion->dependencia_id;
            $this->v_fecha_asignacion = $vinculacion->fecha_asignacion->format('Y-m-d');
            $this->v_is_active = $vinculacion->is_active;
            $this->v_fecha_desasignacion = $vinculacion->fecha_desasignacion ? $vinculacion->fecha_desasignacion->format('Y-m-d') : null;
            $this->v_motivo_desasignacion = $vinculacion->motivo_desasignacion;
            $this->puedeEstarEnReserva = $this->embarcacionSeleccionada->vinculaciones()->count() === 1;
            $this->showModalNuevaVinculacion = true;
        }
    }

    public function guardarVinculacion()
    {
        if (!$this->embarcacionSeleccionada || !$this->v_mandante_id) return;
        if ($this->v_dependencia_id === 'null') $this->v_dependencia_id = null;
        $validatedData = $this->validate($this->rulesVinculacion());
        if ($validatedData['v_is_active']) {
            $validatedData['v_fecha_desasignacion'] = null;
            $validatedData['v_motivo_desasignacion'] = null;
        }
        $dataToSave = [
            'embarcacion_id' => $this->embarcacionSeleccionada->id,
            'unidad_organizacional_mandante_id' => $validatedData['v_unidad_organizacional_mandante_id'],
            'dependencia_id' => $validatedData['v_dependencia_id'],
            'fecha_asignacion' => $validatedData['v_fecha_asignacion'],
            'is_active' => $validatedData['v_is_active'],
            'fecha_desasignacion' => $validatedData['v_fecha_desasignacion'],
            'motivo_desasignacion' => $validatedData['v_motivo_desasignacion'],
        ];
        EmbarcacionAsignacion::updateOrCreate(['id' => $this->vinculacionId], $dataToSave);
        session()->flash('message_vinculacion', 'Vinculación guardada correctamente.');
        $this->cerrarModalVinculacion();
    }

    public function cerrarModalVinculacion()
    {
        $this->showModalNuevaVinculacion = false;
        $this->resetVinculacionFields();
    }

    public function abrirModalCargaDocumentos(int $embarcacionId, int $mandanteId, int $unidadOrganizacionalId, string $contexto)
    {
        if (!$unidadOrganizacionalId) {
            session()->flash('error_embarcacion', 'Por favor, asegúrese de que el contexto de la Unidad Organizacional esté seleccionado para operar.');
            return;
        }

        $this->dispatch('abrirModalDocumentos', 
            recursoId: $embarcacionId, 
            recursoType: Embarcacion::class,
            mandanteId: $mandanteId,
            unidadOrganizacionalId: $unidadOrganizacionalId,
            contexto: $contexto
        );
    }

    public function render()
    {
        $gruposDeVinculaciones = collect();
        $vinculacionesPaginadas = null;
        $totalEmbarcacionesUnicas = 0;
        $totalAsignaciones = 0;

        if ($this->vistaActual === 'listado_embarcaciones') {
            if ($this->contratistaId) {
                $baseQuery = EmbarcacionAsignacion::query()
                    ->join('embarcaciones', 'embarcacion_asignaciones.embarcacion_id', '=', 'embarcaciones.id')
                    ->where('embarcaciones.contratista_id', $this->contratistaId);

                $baseQuery->when($this->filtroEstado === 'activos', fn($q) => $q->where('embarcaciones.is_active', true))
                          ->when($this->filtroEstado === 'inactivos', fn($q) => $q->where('embarcaciones.is_active', false));

                if ($this->lugarDeTrabajoId === 'orphaned') {
                    $idsDependenciasAsignadas = Contratista::find($this->contratistaId)->dependencias()->pluck('dependencias.id')->toArray();
                    $baseQuery->whereNotNull('embarcacion_asignaciones.dependencia_id')->whereNotIn('embarcacion_asignaciones.dependencia_id', $idsDependenciasAsignadas);
                    if ($this->mandanteId) $baseQuery->whereHas('unidadOrganizacionalMandante', fn ($q) => $q->where('mandante_id', $this->mandanteId));
                } elseif ($this->lugarDeTrabajoId === 'in_reserve') {
                    $baseQuery->whereNull('embarcacion_asignaciones.dependencia_id');
                    if ($this->mandanteId) $baseQuery->whereHas('unidadOrganizacionalMandante', fn ($q) => $q->where('mandante_id', $this->mandanteId));
                } else {
                    $contratista = Contratista::find($this->contratistaId);
                    $idsDependenciasValidas = $contratista->dependencias()->pluck('dependencias.id')->toArray();

                    if ($this->lugarDeTrabajoId) {
                        $baseQuery->where('embarcacion_asignaciones.dependencia_id', $this->lugarDeTrabajoId);
                    } else {
                        $baseQuery->whereIn('embarcacion_asignaciones.dependencia_id', $idsDependenciasValidas);
                    }
                    
                    if ($this->mandanteId) $baseQuery->whereHas('unidadOrganizacionalMandante', fn ($q) => $q->where('mandante_id', $this->mandanteId));
                }

                if ($this->unidadOrganizacionalId) $baseQuery->where('embarcacion_asignaciones.unidad_organizacional_mandante_id', $this->unidadOrganizacionalId);

                $queryVinculaciones = (clone $baseQuery)
                    ->with([
                        'embarcacion' => function($q) {
                            $q->withCount('vinculaciones')->with('tipoEmbarcacion');
                        },
                        'dependencia.parent', 
                        'unidadOrganizacionalMandante' => function($q) {
                            $q->with(['parent', 'mandante:id,razon_social']);
                        }
                    ])
                    ->select('embarcacion_asignaciones.*')
                    // ================== INICIO DE LA MODIFICACIÓN (BÚSQUEDA FLEXIBLE) ==================
                    ->when($this->searchEmbarcacion, function ($query) {
                        $searchTerm = '%' . str_replace(' ', '%', $this->searchEmbarcacion) . '%';
                        $query->where(function ($q) use ($searchTerm) {
                            $q->where('embarcaciones.matricula_letras', 'like', $searchTerm)
                              ->orWhere('embarcaciones.matricula_numeros', 'like', $searchTerm)
                              ->orWhere(DB::raw("CONCAT(embarcaciones.matricula_letras, embarcaciones.matricula_numeros)"), 'like', $searchTerm)
                              ->orWhereHas('embarcacion.tipoEmbarcacion', fn ($sub) => $sub->where('nombre', 'like', $searchTerm));
                        });
                    })
                    // ================== FIN DE LA MODIFICACIÓN ========================================
                    ->orderBy($this->sortByEmbarcacion, $this->sortDirectionEmbarcacion);

                $todasLasVinculaciones = $queryVinculaciones->get();

                $totalEmbarcacionesUnicas = $todasLasVinculaciones->pluck('embarcacion_id')->unique()->count();
                $totalAsignaciones = $todasLasVinculaciones->count();

                if (!empty($this->documentosMaestros)) {
                    $todasLasVinculaciones->each(function ($vinculacion) {
                        if ($vinculacion->embarcacion && $vinculacion->unidadOrganizacionalMandante) {
                            $estados = $this->documentoService->obtenerEstadoDocumentosParaEntidad(
                                $vinculacion->embarcacion, 
                                $vinculacion->unidadOrganizacionalMandante->mandante_id, 
                                $vinculacion->unidad_organizacional_mandante_id
                            );
                            $vinculacion->estadosDocumentos = collect($estados)->mapWithKeys(fn($item) => [$item['nombre_documento_id'] => $item['estado_actual_documento']]);
                        } else {
                            $vinculacion->estadosDocumentos = collect();
                        }
                    });
                }

                $gruposDeVinculaciones = $todasLasVinculaciones->groupBy([
                    function ($item) {
                        return $item->dependencia->nombre_jerarquico ?? 'EMBARCACIONES EN RESERVA';
                    },
                    function ($item) {
                        return $item->unidadOrganizacionalMandante->nombre_jerarquico ?? 'SIN U.O. ASIGNADA';
                    }
                ], $preserveKeys = true);
            }
        } elseif ($this->vistaActual === 'listado_vinculaciones' && $this->embarcacionSeleccionada) {
            $vinculacionesPaginadas = $this->embarcacionSeleccionada->vinculaciones()
                ->with(['unidadOrganizacionalMandante.parent', 'dependencia.parent'])
                ->orderBy('is_active', 'desc')->orderBy('fecha_asignacion', 'desc')
                ->paginate(10, ['*'], 'vinculacionesPage');
        }

        return view('livewire.contratista.gestion-embarcaciones-contratista', [
            'gruposDeVinculaciones' => $gruposDeVinculaciones,
            'vinculacionesPaginadas' => $vinculacionesPaginadas,
            'totalEmbarcacionesUnicas' => $totalEmbarcacionesUnicas,
            'totalAsignaciones' => $totalAsignaciones,
        ]);
    }
}