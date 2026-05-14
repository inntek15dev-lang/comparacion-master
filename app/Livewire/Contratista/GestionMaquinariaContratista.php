<?php

namespace App\Livewire\Contratista;

use Livewire\Component;
use App\Models\Maquinaria;
use App\Models\Contratista;
use App\Models\Mandante;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\MaquinariaAsignacion;
use App\Models\TipoMaquinaria;
use App\Models\MarcaVehiculo;
use App\Models\TenenciaVehiculo;
use App\Models\Dependencia;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\DocumentoRequeridoService;
use App\Services\CriticidadDocumentoService;

class GestionMaquinariaContratista extends Component
{
    use WithPagination;

    public ?int $mandanteId = null;
    public ?int $unidadOrganizacionalId = null;
    public $lugarDeTrabajoId = null;
    public string $nombreVinculacionSeleccionada = '';
    public string $vistaActual = 'listado_maquinaria';
    public ?Maquinaria $maquinariaSeleccionada = null;
    public $contratistaId;
    public string $searchMaquinaria = '';
    public string $sortByMaquinaria = 'maquinarias.id';
    public string $sortDirectionMaquinaria = 'asc';
    public bool $showModalFichaMaquinaria = false;
    public ?int $maquinariaId = null;
    public string $identificador_letras = '', $identificador_numeros = '';
    public ?string $ano_fabricacion = null;
    public ?int $marca_vehiculo_id = null, $tipo_maquinaria_id = null, $tenencia_vehiculo_id = null;
    public bool $maquinaria_is_active = true;
    
    public bool $showModalNuevaVinculacion = false;
    public ?int $vinculacionId = null;
    public ?int $v_mandante_id = null;
    public ?int $v_unidad_organizacional_mandante_id = null;
    public $v_dependencia_id = null;
    public ?string $v_fecha_asignacion = null;
    public bool $v_is_active = true;
    public ?string $v_fecha_desasignacion = null;
    public ?string $v_motivo_desasignacion = null;

    public $tiposMaquinaria, $marcas, $tenencias;
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
            'identificador_letras.unique' => 'El identificador ingresado ya existe para su empresa.',
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

        $this->tiposMaquinaria = TipoMaquinaria::where('is_active', true)->orderBy('nombre')->get();
        $this->marcas = MarcaVehiculo::where('is_active', true)->orderBy('nombre')->get();
        $this->tenencias = TenenciaVehiculo::where('is_active', true)->orderBy('nombre')->get();
        $this->mandantesDisponibles = Mandante::whereHas('unidadesOrganizacionales.contratistasHabilitados', function ($query) {
            $query->where('contratistas.id', $this->contratistaId);
        })->orderBy('razon_social')->get();
    }

    // ================== INICIO DE LA MODIFICACIÓN (RESETEO DE PAGINACIÓN) ==================
    public function updatedSearchMaquinaria()
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado()
    {
        $this->resetPage();
    }
    // ================== FIN DE LA MODIFICACIÓN ========================================

    public function eliminarMaquinaria($id)
    {
        $maquinaria = Maquinaria::where('id', $id)->where('contratista_id', $this->contratistaId)->first();
        if ($maquinaria) {
            try {
                $maquinaria->forceDelete();
                session()->flash('message_maquinaria', 'Maquinaria y todas sus asignaciones han sido eliminadas PERMANENTEMENTE.');
                $this->cerrarModalFichaMaquinaria();
                $this->irAListadoMaquinaria();
            } catch (\Exception $e) {
                Log::error("Error al eliminar permanentemente maquinaria ID {$id}: " . $e->getMessage());
                session()->flash('error_maquinaria', 'Ocurrió un error al eliminar la maquinaria.');
            }
        }
    }

    public function eliminarVinculacion($vinculacionId)
    {
        $vinculacion = MaquinariaAsignacion::with('maquinaria')->find($vinculacionId);
        if ($vinculacion && $vinculacion->maquinaria->contratista_id == $this->contratistaId) {
            
            if ($vinculacion->maquinaria->vinculaciones()->count() <= 1) {
                session()->flash('error_vinculacion', 'Acción no permitida. No se puede eliminar la última asignación de una maquinaria. Para desvincularla, edite la asignación para moverla a "Reserva" o desactive la maquinaria desde su ficha.');
                return;
            }

            try {
                $vinculacion->delete();
                session()->flash('message_vinculacion', 'La asignación ha sido eliminada correctamente.');
            } catch (\Exception $e) {
                Log::error("Error al eliminar vinculación de maquinaria ID {$vinculacionId}: " . $e->getMessage());
                session()->flash('error_vinculacion', 'Ocurrió un error al eliminar la asignación.');
            }
        } else {
            session()->flash('error_vinculacion', 'No se pudo eliminar la asignación. No fue encontrada o no pertenece a su empresa.');
        }
    }

    public function seleccionarMaquinariaParaVinculaciones($maquinariaId)
    {
        $this->maquinariaSeleccionada = Maquinaria::find($maquinariaId);
        if ($this->maquinariaSeleccionada && $this->maquinariaSeleccionada->contratista_id == $this->contratistaId) {
            $this->vistaActual = 'listado_vinculaciones';
            $this->resetPage('vinculacionesPage');
        }
    }

    public function irAListadoMaquinaria()
    {
        $this->vistaActual = 'listado_maquinaria';
        $this->maquinariaSeleccionada = null;
        $this->resetPage('maquinariaPage');
    }

    public function rulesFichaMaquinaria()
    {
        $rules = [
            'identificador_letras' => ['required', 'string', 'min:1', 'max:20', Rule::unique('maquinarias')->where(fn($query) => $query->where('contratista_id', $this->contratistaId)->where('identificador_numeros', $this->identificador_numeros))->ignore($this->maquinariaId)],
            'identificador_numeros' => 'required|string|min:1|max:20',
            'ano_fabricacion' => 'required|integer|digits:4|min:1950|max:' . (date('Y') + 1),
            'marca_vehiculo_id' => 'required|exists:marcas_vehiculo,id',
            'tipo_maquinaria_id' => 'required|exists:tipos_maquinaria,id',
            'tenencia_vehiculo_id' => 'nullable|exists:tenencias_vehiculo,id',
            'maquinaria_is_active' => 'boolean',
        ];

        if (!$this->maquinariaId) {
            $rules['v_unidad_organizacional_mandante_id'] = 'required|exists:unidades_organizacionales_mandante,id';
        }

        return $rules;
    }

    private function resetFichaMaquinariaFields()
    {
        $this->maquinariaId = null;
        $this->identificador_letras = '';
        $this->identificador_numeros = '';
        $this->ano_fabricacion = null;
        $this->marca_vehiculo_id = null;
        $this->tipo_maquinaria_id = null;
        $this->tenencia_vehiculo_id = null;
        $this->maquinaria_is_active = true;
        $this->v_unidad_organizacional_mandante_id = null;
        $this->resetValidation();
    }

    public function abrirModalNuevaMaquinaria()
    {
        if (!$this->mandanteId || !$this->lugarDeTrabajoId || in_array($this->lugarDeTrabajoId, ['orphaned', 'in_reserve'])) {
            session()->flash('error', 'Debe seleccionar un Lugar de Trabajo específico y válido para agregar nueva maquinaria.');
            return;
        }
        $this->resetFichaMaquinariaFields();
        $this->unidadesOrganizacionalesDisponibles = UnidadOrganizacionalMandante::where('mandante_id', $this->mandanteId)
            ->where('is_active', true)
            ->whereHas('contratistasHabilitados', fn ($q) => $q->where('contratista_id', $this->contratistaId))
            ->get()->sortBy('nombre_jerarquico');
        $this->showModalFichaMaquinaria = true;
    }

    public function abrirModalEditarMaquinaria($id)
    {
        $maquinaria = Maquinaria::find($id);
        if ($maquinaria && $maquinaria->contratista_id == $this->contratistaId) {
            $this->maquinariaId = $maquinaria->id;
            $this->maquinariaSeleccionada = $maquinaria;
            $this->identificador_letras = $maquinaria->identificador_letras;
            $this->identificador_numeros = $maquinaria->identificador_numeros;
            $this->ano_fabricacion = $maquinaria->ano_fabricacion;
            $this->marca_vehiculo_id = $maquinaria->marca_vehiculo_id;
            $this->tipo_maquinaria_id = $maquinaria->tipo_maquinaria_id;
            $this->tenencia_vehiculo_id = $maquinaria->tenencia_vehiculo_id;
            $this->maquinaria_is_active = $maquinaria->is_active;
            $this->showModalFichaMaquinaria = true;
        }
    }

    public function guardarMaquinaria()
    {
        if (!$this->maquinariaId) {
            if (!$this->mandanteId) {
                session()->flash('error_maquinaria', 'El contexto del Mandante no está definido para crear nueva maquinaria.');
                return;
            }
            if (!$this->lugarDeTrabajoId || in_array($this->lugarDeTrabajoId, ['orphaned', 'in_reserve'])) {
                session()->flash('error_maquinaria', 'Debe seleccionar un Lugar de Trabajo válido para crear nueva maquinaria.');
                return;
            }
        }

        $validatedData = $this->validate($this->rulesFichaMaquinaria());
        $dataToSave = collect($validatedData)->except('v_unidad_organizacional_mandante_id')->toArray();
        $dataToSave['contratista_id'] = $this->contratistaId;

        DB::beginTransaction();
        try {
            $maquinaria = Maquinaria::updateOrCreate(['id' => $this->maquinariaId], $dataToSave);
            $esNuevaMaquinaria = !$this->maquinariaId;

            if ($esNuevaMaquinaria) {
                MaquinariaAsignacion::create([
                    'maquinaria_id' => $maquinaria->id,
                    'unidad_organizacional_mandante_id' => $validatedData['v_unidad_organizacional_mandante_id'],
                    'dependencia_id' => $this->lugarDeTrabajoId,
                    'fecha_asignacion' => now(),
                    'is_active' => true,
                ]);
                session()->flash('message_maquinaria', 'Maquinaria agregada y vinculada correctamente.');
            } else {
                session()->flash('message_maquinaria', 'Ficha de la maquinaria actualizada correctamente.');
            }
            DB::commit();
            $this->cerrarModalFichaMaquinaria();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al guardar maquinaria: " . $e->getMessage());
            session()->flash('error_maquinaria', 'Ocurrió un error al guardar la ficha de la maquinaria.');
        }
    }

    public function cerrarModalFichaMaquinaria()
    {
        $this->showModalFichaMaquinaria = false;
        $this->resetFichaMaquinariaFields();
    }

    public function toggleActivoMaquinaria($maquinariaId)
    {
        $maquinaria = Maquinaria::find($maquinariaId);
        if ($maquinaria && $maquinaria->contratista_id == $this->contratistaId) {
            $maquinaria->is_active = !$maquinaria->is_active;
            $maquinaria->save();
            session()->flash('message_maquinaria', 'Estado de la maquinaria cambiado correctamente.');
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
                if (!$this->maquinariaSeleccionada) {
                    $fail('No hay una maquinaria seleccionada para esta validación.'); 
                    return;
                }
                $query = MaquinariaAsignacion::where('maquinaria_id', $this->maquinariaSeleccionada->id)
                    ->where('unidad_organizacional_mandante_id', $value)
                    ->where('dependencia_id', $this->v_dependencia_id)
                    ->where('is_active', true);
                
                if ($this->vinculacionId) {
                    $query->where('id', '!=', $this->vinculacionId);
                }

                if ($query->exists()) {
                    $fail('La maquinaria ya tiene una asignación activa en esta U.O. y Lugar de Trabajo.');
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
        if (!$this->maquinariaSeleccionada || !$this->mandanteId) return;
        $this->resetVinculacionFields();
        $this->v_mandante_id = $this->mandanteId;
        $this->updatedVMandanteId($this->v_mandante_id);
        $this->v_fecha_asignacion = now()->format('Y-m-d');
        $this->showModalNuevaVinculacion = true;
    }

    public function abrirModalEditarVinculacion($id)
    {
        $vinculacion = MaquinariaAsignacion::find($id);
        if ($vinculacion && $vinculacion->maquinaria_id == $this->maquinariaSeleccionada?->id) {
            $this->vinculacionId = $vinculacion->id;
            $this->v_mandante_id = $vinculacion->unidadOrganizacionalMandante?->mandante_id;
            $this->updatedVMandanteId($this->v_mandante_id);
            $this->v_unidad_organizacional_mandante_id = $vinculacion->unidad_organizacional_mandante_id;
            $this->v_dependencia_id = $vinculacion->dependencia_id;
            $this->v_fecha_asignacion = $vinculacion->fecha_asignacion->format('Y-m-d');
            $this->v_is_active = $vinculacion->is_active;
            $this->v_fecha_desasignacion = $vinculacion->fecha_desasignacion ? $vinculacion->fecha_desasignacion->format('Y-m-d') : null;
            $this->v_motivo_desasignacion = $vinculacion->motivo_desasignacion;
            $this->puedeEstarEnReserva = $this->maquinariaSeleccionada->vinculaciones()->count() === 1;
            $this->showModalNuevaVinculacion = true;
        }
    }

    public function guardarVinculacion()
    {
        if (!$this->maquinariaSeleccionada || !$this->v_mandante_id) return;
        if ($this->v_dependencia_id === 'null') $this->v_dependencia_id = null;
        $validatedData = $this->validate($this->rulesVinculacion());
        if ($validatedData['v_is_active']) {
            $validatedData['v_fecha_desasignacion'] = null;
            $validatedData['v_motivo_desasignacion'] = null;
        }
        $dataToSave = [
            'maquinaria_id' => $this->maquinariaSeleccionada->id,
            'unidad_organizacional_mandante_id' => $validatedData['v_unidad_organizacional_mandante_id'],
            'dependencia_id' => $validatedData['v_dependencia_id'],
            'fecha_asignacion' => $validatedData['v_fecha_asignacion'],
            'is_active' => $validatedData['v_is_active'],
            'fecha_desasignacion' => $validatedData['v_fecha_desasignacion'],
            'motivo_desasignacion' => $validatedData['v_motivo_desasignacion'],
        ];
        MaquinariaAsignacion::updateOrCreate(['id' => $this->vinculacionId], $dataToSave);
        session()->flash('message_vinculacion', 'Vinculación guardada correctamente.');
        $this->cerrarModalVinculacion();
    }

    public function cerrarModalVinculacion()
    {
        $this->showModalNuevaVinculacion = false;
        $this->resetVinculacionFields();
    }

    public function abrirModalCargaDocumentos(int $maquinariaId, int $mandanteId, int $unidadOrganizacionalId, string $contexto)
    {
        if (!$unidadOrganizacionalId) {
            session()->flash('error_maquinaria', 'Por favor, asegúrese de que el contexto de la Unidad Organizacional esté seleccionado para operar.');
            return;
        }

        $this->dispatch('abrirModalDocumentos', 
            recursoId: $maquinariaId, 
            recursoType: Maquinaria::class,
            mandanteId: $mandanteId,
            unidadOrganizacionalId: $unidadOrganizacionalId,
            contexto: $contexto
        );
    }

    public function render()
    {
        $gruposDeVinculaciones = collect();
        $vinculacionesPaginadas = null;
        $totalMaquinariasUnicas = 0;
        $totalAsignaciones = 0;

        if ($this->vistaActual === 'listado_maquinaria') {
            if ($this->contratistaId) {
                $baseQuery = MaquinariaAsignacion::query()
                    ->join('maquinarias', 'maquinaria_asignaciones.maquinaria_id', '=', 'maquinarias.id')
                    ->where('maquinarias.contratista_id', $this->contratistaId);

                $baseQuery->when($this->filtroEstado === 'activos', fn($q) => $q->where('maquinarias.is_active', true))
                          ->when($this->filtroEstado === 'inactivos', fn($q) => $q->where('maquinarias.is_active', false));

                if ($this->lugarDeTrabajoId === 'orphaned') {
                    $idsDependenciasAsignadas = Contratista::find($this->contratistaId)->dependencias()->pluck('dependencias.id')->toArray();
                    $baseQuery->whereNotNull('maquinaria_asignaciones.dependencia_id')->whereNotIn('maquinaria_asignaciones.dependencia_id', $idsDependenciasAsignadas);
                    if ($this->mandanteId) $baseQuery->whereHas('unidadOrganizacionalMandante', fn ($q) => $q->where('mandante_id', $this->mandanteId));
                } elseif ($this->lugarDeTrabajoId === 'in_reserve') {
                    $baseQuery->whereNull('maquinaria_asignaciones.dependencia_id');
                    if ($this->mandanteId) $baseQuery->whereHas('unidadOrganizacionalMandante', fn ($q) => $q->where('mandante_id', $this->mandanteId));
                } else {
                    $contratista = Contratista::find($this->contratistaId);
                    $idsDependenciasValidas = $contratista->dependencias()->pluck('dependencias.id')->toArray();

                    if ($this->lugarDeTrabajoId) {
                        $baseQuery->where('maquinaria_asignaciones.dependencia_id', $this->lugarDeTrabajoId);
                    } else {
                        $baseQuery->whereIn('maquinaria_asignaciones.dependencia_id', $idsDependenciasValidas);
                    }

                    if ($this->mandanteId) $baseQuery->whereHas('unidadOrganizacionalMandante', fn ($q) => $q->where('mandante_id', $this->mandanteId));
                }

                if ($this->unidadOrganizacionalId) $baseQuery->where('maquinaria_asignaciones.unidad_organizacional_mandante_id', $this->unidadOrganizacionalId);

                $queryVinculaciones = (clone $baseQuery)
                    ->with([
                        'maquinaria' => function($q) {
                            $q->withCount('vinculaciones')->with(['marca', 'tipoMaquinaria']);
                        },
                        'dependencia.parent', 
                        'unidadOrganizacionalMandante' => function($q) {
                            $q->with(['parent', 'mandante:id,razon_social']);
                        }
                    ])
                    ->select('maquinaria_asignaciones.*')
                    // ================== INICIO DE LA MODIFICACIÓN (BÚSQUEDA FLEXIBLE) ==================
                    ->when($this->searchMaquinaria, function ($query) {
                        $searchTerm = '%' . str_replace(' ', '%', $this->searchMaquinaria) . '%';
                        $query->where(function ($q) use ($searchTerm) {
                            $q->where('maquinarias.identificador_letras', 'like', $searchTerm)
                              ->orWhere('maquinarias.identificador_numeros', 'like', $searchTerm)
                              ->orWhere(DB::raw("CONCAT(maquinarias.identificador_letras, maquinarias.identificador_numeros)"), 'like', $searchTerm)
                              ->orWhereHas('maquinaria.marca', fn ($sub) => $sub->where('nombre', 'like', $searchTerm));
                        });
                    })
                    // ================== FIN DE LA MODIFICACIÓN ========================================
                    ->orderBy($this->sortByMaquinaria, $this->sortDirectionMaquinaria);

                $todasLasVinculaciones = $queryVinculaciones->get();

                $totalMaquinariasUnicas = $todasLasVinculaciones->pluck('maquinaria_id')->unique()->count();
                $totalAsignaciones = $todasLasVinculaciones->count();

                if (!empty($this->documentosMaestros)) {
                    $todasLasVinculaciones->each(function ($vinculacion) {
                        if ($vinculacion->maquinaria && $vinculacion->unidadOrganizacionalMandante) {
                            $estados = $this->documentoService->obtenerEstadoDocumentosParaEntidad(
                                $vinculacion->maquinaria, 
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
                        return $item->dependencia->nombre_jerarquico ?? 'MAQUINARIAS EN RESERVA';
                    },
                    function ($item) {
                        return $item->unidadOrganizacionalMandante->nombre_jerarquico ?? 'SIN U.O. ASIGNADA';
                    }
                ], $preserveKeys = true);
            }
        } elseif ($this->vistaActual === 'listado_vinculaciones' && $this->maquinariaSeleccionada) {
            $vinculacionesPaginadas = $this->maquinariaSeleccionada->vinculaciones()
                ->with(['unidadOrganizacionalMandante.parent', 'dependencia.parent'])
                ->orderBy('is_active', 'desc')->orderBy('fecha_asignacion', 'desc')
                ->paginate(10, ['*'], 'vinculacionesPage');
        }

        return view('livewire.contratista.gestion-maquinaria-contratista', [
            'gruposDeVinculaciones' => $gruposDeVinculaciones,
            'vinculacionesPaginadas' => $vinculacionesPaginadas,
            'totalMaquinariasUnicas' => $totalMaquinariasUnicas,
            'totalAsignaciones' => $totalAsignaciones,
        ]);
    }
}