<?php

namespace App\Livewire\Contratista;

use Livewire\Component;
use App\Models\Vehiculo;
use App\Models\Contratista;
use App\Models\Mandante;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\VehiculoAsignacion;
use App\Models\TipoVehiculo;
use App\Models\MarcaVehiculo;
use App\Models\ColorVehiculo;
use App\Models\TenenciaVehiculo;
use App\Models\Dependencia;
use App\Models\SubTipoVehiculoMandante;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\DocumentoRequeridoService;
use App\Services\CriticidadDocumentoService;

class GestionVehiculosContratista extends Component
{
    use WithPagination;

    public ?int $mandanteId = null;
    public ?int $unidadOrganizacionalId = null;
    public $lugarDeTrabajoId = null;
    public string $nombreVinculacionSeleccionada = '';
    public string $vistaActual = 'listado_vehiculos';
    public ?Vehiculo $vehiculoSeleccionado = null;
    public $contratistaId;
    public string $searchVehiculo = '';
    public string $sortByVehiculo = 'vehiculos.id';
    public string $sortDirectionVehiculo = 'asc';
    public bool $showModalFichaVehiculo = false;
    public ?int $vehiculoId = null;
    public string $patente_letras = '', $patente_numeros = '';
    public ?string $ano_fabricacion = null;
    public ?int $marca_vehiculo_id = null, $color_vehiculo_id = null, $tipo_vehiculo_id = null, $tenencia_vehiculo_id = null;
    public bool $vehiculo_is_active = true;
    
    public bool $showModalNuevaVinculacion = false;
    public ?int $vinculacionId = null;
    public ?int $v_mandante_id = null;
    public ?int $v_unidad_organizacional_mandante_id = null;
    public ?int $v_dependencia_id = null;
    public ?int $v_sub_tipo_vehiculo_mandante_id = null;
    public array $v_condiciones_vehiculo_ids = [];
    public ?string $v_fecha_asignacion = null;
    public bool $v_is_active = true;
    public ?string $v_fecha_desasignacion = null;
    public ?string $v_motivo_desasignacion = null;

    public $tiposVehiculo, $marcasVehiculo, $coloresVehiculo, $tenenciasVehiculo;
    public $mandantesDisponibles = [], $unidadesOrganizacionalesDisponibles = [], $dependenciasDisponibles = [];
    public $subTiposVehiculoDisponibles = [];
    public $condicionesVehiculoDisponibles = [];
    
    private DocumentoRequeridoService $documentoService;
    private CriticidadDocumentoService $criticidadService;
    public ?int $contratistaIdForzado = null;
    public ?int $abrirModalParaId = null;
    
    public bool $puedeEstarEnReserva = false;

    public array $documentosMaestros = [];
    public string $filtroEstado = 'activos';

    // ================== NUEVAS PROPIEDADES PARA AISLAMIENTO (IGUAL QUE TRABAJADORES) ==================
    /**
     * Almacena los valores pre-calculados de estado_acceso y porcentaje_cumplimiento
     * extraÃ­dos directamente de la BD vehiculo_asignaciones.
     */
    public array $estadosPreCalculados = [];
    
    /**
     * Almacena los estados de documentos individuales calculados por el servicio.
     */
    public array $estadosDocumentosPorVinculacion = [];
    // ================== FIN NUEVAS PROPIEDADES ==================

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
            'patente_letras.unique' => 'La patente ingresada ya existe para su empresa.',
            'v_unidad_organizacional_mandante_id.required' => 'Debe seleccionar una Unidad Organizacional.',
            'v_dependencia_id.required_without_all' => 'Debe seleccionar un Lugar de Trabajo.',
            'v_unidad_organizacional_mandante_id.required_without' => 'Debe seleccionar una Unidad Organizacional para la vinculaciÃ³n inicial.',
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
            session()->flash('error', 'Usuario no asociado a un contratista vÃ¡lido.');
            return;
        }

        $this->tiposVehiculo = TipoVehiculo::where('is_active', true)->orderBy('nombre')->get();
        $this->marcasVehiculo = MarcaVehiculo::where('is_active', true)->orderBy('nombre')->get();
        $this->coloresVehiculo = ColorVehiculo::where('is_active', true)->orderBy('nombre')->get();
        $this->tenenciasVehiculo = TenenciaVehiculo::where('is_active', true)->orderBy('nombre')->get();
        
        $this->condicionesVehiculoDisponibles = \App\Models\TipoCondicionVehiculo::where('is_active', true)->orderBy('nombre')->get();
        $this->mandantesDisponibles = Mandante::whereHas('unidadesOrganizacionales.contratistasHabilitados', function ($query) {
            $query->where('contratistas.id', $this->contratistaId);
        })->orderBy('razon_social')->get();
        
        if ($this->abrirModalParaId) {
            // LÃ³gica para abrir modal si viene forzado (opcional, similar a trabajadores)
        }
    }

    public function updatedSearchVehiculo()
    {
        $this->resetPage('vehiculosPage');
    }

    public function updatedFiltroEstado()
    {
        $this->resetPage('vehiculosPage');
    }

    public function eliminarVehiculo($id)
    {
        $vehiculo = Vehiculo::where('id', $id)->where('contratista_id', $this->contratistaId)->first();
        if ($vehiculo) {
            try {
                $vehiculo->forceDelete();
                session()->flash('message_vehiculo', 'VehÃ­culo y todas sus asignaciones han sido eliminados PERMANENTEMENTE.');
                $this->cerrarModalFichaVehiculo();
                $this->irAListadoVehiculos();
            } catch (\Exception $e) {
                Log::error("Error al eliminar permanentemente vehÃ­culo ID {$id}: " . $e->getMessage());
                session()->flash('error_vehiculo', 'OcurriÃ³ un error al eliminar el vehÃ­culo.');
            }
        }
    }

    public function eliminarVinculacion($vinculacionId)
    {
        $vinculacion = VehiculoAsignacion::with('vehiculo')->find($vinculacionId);
        if ($vinculacion && $vinculacion->vehiculo->contratista_id == $this->contratistaId) {
            
            if ($vinculacion->vehiculo->vinculaciones()->count() <= 1) {
                session()->flash('error_vinculacion', 'AcciÃ³n no permitida. No se puede eliminar la Ãºltima asignaciÃ³n de un vehÃ­culo. Para desvincularlo, edite la asignaciÃ³n para moverlo a "Reserva" o desactive el vehÃ­culo desde su ficha.');
                return;
            }

            try {
                $vinculacion->delete();
                session()->flash('message_vinculacion', 'La asignaciÃ³n ha sido eliminada correctamente.');
            } catch (\Exception $e) {
                Log::error("Error al eliminar vinculaciÃ³n de vehÃ­culo ID {$vinculacionId}: " . $e->getMessage());
                session()->flash('error_vinculacion', 'OcurriÃ³ un error al eliminar la asignaciÃ³n.');
            }
        } else {
            session()->flash('error_vinculacion', 'No se pudo eliminar la asignaciÃ³n. No fue encontrada o no pertenece a su empresa.');
        }
    }

    public function seleccionarVehiculoParaVinculaciones($vehiculoId)
    {
        $this->vehiculoSeleccionado = Vehiculo::find($vehiculoId);
        if ($this->vehiculoSeleccionado && $this->vehiculoSeleccionado->contratista_id == $this->contratistaId) {
            $this->vistaActual = 'listado_vinculaciones';
            $this->resetPage('vinculacionesPage');
        }
    }

    public function irAListadoVehiculos()
    {
        $this->vistaActual = 'listado_vehiculos';
        $this->vehiculoSeleccionado = null;
        $this->resetPage('vehiculosPage');
    }

    public function rulesFichaVehiculo()
    {
        $rules = [
            'patente_letras' => ['required', 'string', 'min:2', 'max:4', Rule::unique('vehiculos')->where(fn ($query) => $query->where('contratista_id', $this->contratistaId)->where('patente_numeros', $this->patente_numeros))->ignore($this->vehiculoId, 'id')],
            'patente_numeros' => 'required|string|min:2|max:4',
            'ano_fabricacion' => 'required|integer|digits:4|min:1950|max:' . (date('Y') + 1),
            'marca_vehiculo_id' => 'required|exists:marcas_vehiculo,id',
            'color_vehiculo_id' => 'required|exists:colores_vehiculo,id',
            'tipo_vehiculo_id' => 'required|exists:tipos_vehiculo,id',
            'tenencia_vehiculo_id' => 'nullable|exists:tenencias_vehiculo,id',
            'vehiculo_is_active' => 'boolean',
        ];

        if (!$this->vehiculoId) {
            $rules['v_unidad_organizacional_mandante_id'] = 'required|exists:unidades_organizacionales_mandante,id';
        }

        return $rules;
    }

    private function resetFichaVehiculoFields()
    {
        $this->vehiculoId = null;
        $this->patente_letras = '';
        $this->patente_numeros = '';
        $this->ano_fabricacion = null;
        $this->marca_vehiculo_id = null;
        $this->color_vehiculo_id = null;
        $this->tipo_vehiculo_id = null;
        $this->tenencia_vehiculo_id = null;
        $this->vehiculo_is_active = true;
        $this->v_unidad_organizacional_mandante_id = null;
        $this->resetValidation();
    }

    public function abrirModalNuevoVehiculo()
    {
        if (!$this->mandanteId || !$this->lugarDeTrabajoId || in_array($this->lugarDeTrabajoId, ['orphaned', 'in_reserve'])) {
            session()->flash('error', 'Debe seleccionar un Lugar de Trabajo especÃ­fico y vÃ¡lido para agregar un nuevo vehÃ­culo.');
            return;
        }
        $this->resetFichaVehiculoFields();
        $this->unidadesOrganizacionalesDisponibles = UnidadOrganizacionalMandante::where('mandante_id', $this->mandanteId)
            ->where('is_active', true)
            ->whereHas('contratistasHabilitados', fn ($q) => $q->where('contratista_id', $this->contratistaId))
            ->get()->sortBy('nombre_jerarquico');
        $this->showModalFichaVehiculo = true;
    }

    public function abrirModalEditarVehiculo($id)
    {
        $vehiculo = Vehiculo::find($id);
        if ($vehiculo && $vehiculo->contratista_id == $this->contratistaId) {
            $this->vehiculoId = $vehiculo->id;
            $this->vehiculoSeleccionado = $vehiculo;
            $this->patente_letras = $vehiculo->patente_letras;
            $this->patente_numeros = $vehiculo->patente_numeros;
            $this->ano_fabricacion = $vehiculo->ano_fabricacion;
            $this->marca_vehiculo_id = $vehiculo->marca_vehiculo_id;
            $this->color_vehiculo_id = $vehiculo->color_vehiculo_id;
            $this->tipo_vehiculo_id = $vehiculo->tipo_vehiculo_id;
            $this->tenencia_vehiculo_id = $vehiculo->tenencia_vehiculo_id;
            $this->vehiculo_is_active = $vehiculo->is_active;
            $this->showModalFichaVehiculo = true;
        }
    }

    public function guardarVehiculo()
    {
        if (!$this->vehiculoId) {
            if (!$this->mandanteId) {
                session()->flash('error_vehiculo', 'El contexto del Mandante no estÃ¡ definido para crear un nuevo vehÃ­culo.');
                return;
            }
            if (!$this->lugarDeTrabajoId || in_array($this->lugarDeTrabajoId, ['orphaned', 'in_reserve'])) {
                session()->flash('error_vehiculo', 'Debe seleccionar un Lugar de Trabajo vÃ¡lido para crear un nuevo vehÃ­culo.');
                return;
            }
        }

        $validatedData = $this->validate($this->rulesFichaVehiculo());
        $dataToSave = collect($validatedData)->except('v_unidad_organizacional_mandante_id')->toArray();
        $dataToSave['contratista_id'] = $this->contratistaId;

        DB::beginTransaction();
        try {
            $vehiculo = Vehiculo::updateOrCreate(['id' => $this->vehiculoId], $dataToSave);
            $esNuevoVehiculo = !$this->vehiculoId;

            if ($esNuevoVehiculo) {
                VehiculoAsignacion::create([
                    'vehiculo_id' => $vehiculo->id,
                    'unidad_organizacional_mandante_id' => $validatedData['v_unidad_organizacional_mandante_id'],
                    'dependencia_id' => $this->lugarDeTrabajoId,
                    'fecha_asignacion' => now(),
                    'is_active' => true,
                ]);
                session()->flash('message_vehiculo', 'VehÃ­culo agregado y vinculado correctamente.');
            } else {
                session()->flash('message_vehiculo', 'Ficha del vehÃ­culo actualizada correctamente.');
            }
            DB::commit();
            $this->cerrarModalFichaVehiculo();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al guardar vehÃ­culo: " . $e->getMessage());
            session()->flash('error_vehiculo', 'OcurriÃ³ un error al guardar la ficha del vehÃ­culo.');
        }
    }

    public function cerrarModalFichaVehiculo()
    {
        $this->showModalFichaVehiculo = false;
        $this->resetFichaVehiculoFields();
    }

    public function toggleActivoVehiculo($vehiculoId)
    {
        $vehiculo = Vehiculo::find($vehiculoId);
        if ($vehiculo && $vehiculo->contratista_id == $this->contratistaId) {
            $vehiculo->is_active = !$vehiculo->is_active;
            $vehiculo->save();
            session()->flash('message_vehiculo', 'Estado del vehÃ­culo cambiado correctamente.');
        }
    }

    public function rulesVinculacion()
    {
        $rules = [
            'v_mandante_id' => 'required|exists:mandantes,id',
            'v_unidad_organizacional_mandante_id' => [
                'nullable', 
                function ($attribute, $value, $fail) {
                    // Si NO es reserva (hay dependencia), la UO es obligatoria
                    if ($this->v_dependencia_id !== null && $this->v_dependencia_id !== 'null' && empty($value)) {
                        $fail('Debe seleccionar una Unidad Operativa.');
                    }
                },
                'exists:unidades_organizacionales_mandante,id'
            ],
            'v_dependencia_id' => 'nullable|exists:dependencias,id',
            'v_fecha_asignacion' => 'required|date',
            'v_is_active' => 'required|boolean',
            'v_fecha_desasignacion' => 'nullable|required_if:v_is_active,false|date|after_or_equal:v_fecha_asignacion',
            'v_motivo_desasignacion' => 'nullable|required_if:v_is_active,false|string|max:500',
        ];

        $rules['v_unidad_organizacional_mandante_id'][] = function ($attribute, $value, $fail) {
            if ($this->v_is_active && $this->v_dependencia_id) {
                if (!$this->vehiculoSeleccionado) {
                    $fail('No hay un vehÃ­culo seleccionado para esta validaciÃ³n.'); 
                    return;
                }
                $query = VehiculoAsignacion::where('vehiculo_id', $this->vehiculoSeleccionado->id)
                    ->where('unidad_organizacional_mandante_id', $value)
                    ->where('dependencia_id', $this->v_dependencia_id)
                    ->where('is_active', true);
                
                if ($this->vinculacionId) {
                    $query->where('id', '!=', $this->vinculacionId);
                }

                if ($query->exists()) {
                    $fail('El vehÃ­culo ya tiene una asignaciÃ³n activa en esta U.O. y Lugar de Trabajo.');
                }
            }
        };
        return $rules;
    }

    public function updatedVMandanteId($mandanteId)
    {
        $this->unidadesOrganizacionalesDisponibles = [];
        $this->dependenciasDisponibles = [];
        $this->subTiposVehiculoDisponibles = [];
        $this->v_unidad_organizacional_mandante_id = null;
        $this->v_dependencia_id = null;
        $this->v_sub_tipo_vehiculo_mandante_id = null;
        $this->v_condiciones_vehiculo_ids = [];
        if ($mandanteId) {
            $this->unidadesOrganizacionalesDisponibles = UnidadOrganizacionalMandante::where('mandante_id', $mandanteId)->where('is_active', true)->whereHas('contratistasHabilitados', fn ($q) => $q->where('contratista_id', $this->contratistaId))->get()->sortBy('nombre_jerarquico');
            $contratista = Contratista::find($this->contratistaId);
            if ($contratista) {
                $this->dependenciasDisponibles = $contratista->dependencias()->where('mandante_id', $mandanteId)->where('estado', true)->get()->sortBy('nombre_jerarquico');
            }
            $this->subTiposVehiculoDisponibles = SubTipoVehiculoMandante::where('mandante_id', $mandanteId)
                ->where('is_active', true)
                ->orderBy('nombre')
                ->get();
        }
    }

    public function updatedVDependenciaId($value)
    {
        if ($value === 'null' || $value === null) {
            $this->v_unidad_organizacional_mandante_id = null;
        }
    }

    private function resetVinculacionFields()
    {
        $this->vinculacionId = null;
        $this->v_mandante_id = null;
        $this->v_unidad_organizacional_mandante_id = null;
        $this->v_dependencia_id = null;
        $this->v_sub_tipo_vehiculo_mandante_id = null;
        $this->v_condiciones_vehiculo_ids = [];
        $this->v_fecha_asignacion = null;
        $this->v_is_active = true;
        $this->v_fecha_desasignacion = null;
        $this->v_motivo_desasignacion = null;
        $this->unidadesOrganizacionalesDisponibles = [];
        $this->dependenciasDisponibles = [];
        $this->subTiposVehiculoDisponibles = [];
        $this->resetValidation();
    }

    public function abrirModalNuevaVinculacion()
    {
        if (!$this->vehiculoSeleccionado || !$this->mandanteId) return;
        $this->resetVinculacionFields();
        $this->v_mandante_id = $this->mandanteId;
        $this->updatedVMandanteId($this->v_mandante_id);
        $this->v_fecha_asignacion = now()->format('Y-m-d');
        $this->showModalNuevaVinculacion = true;
    }

    public function abrirModalEditarVinculacion($id)
    {
        $vinculacion = VehiculoAsignacion::find($id);
        if ($vinculacion && $vinculacion->vehiculo_id == $this->vehiculoSeleccionado?->id) {
            $this->vinculacionId = $vinculacion->id;
            $this->v_mandante_id = $vinculacion->unidadOrganizacionalMandante?->mandante_id;
            $this->updatedVMandanteId($this->v_mandante_id);
            $this->v_unidad_organizacional_mandante_id = $vinculacion->unidad_organizacional_mandante_id;
            $this->v_dependencia_id = $vinculacion->dependencia_id;
            $this->v_sub_tipo_vehiculo_mandante_id = $vinculacion->sub_tipo_vehiculo_mandante_id;
            $this->v_condiciones_vehiculo_ids = $vinculacion->condiciones->pluck('id')->toArray();
            $this->v_fecha_asignacion = $vinculacion->fecha_asignacion->format('Y-m-d');
            $this->v_is_active = $vinculacion->is_active;
            $this->v_fecha_desasignacion = $vinculacion->fecha_desasignacion ? $vinculacion->fecha_desasignacion->format('Y-m-d') : null;
            $this->v_motivo_desasignacion = $vinculacion->motivo_desasignacion;
            $this->puedeEstarEnReserva = $this->vehiculoSeleccionado->vinculaciones()->count() === 1;
            $this->showModalNuevaVinculacion = true;
        }
    }

    public function guardarVinculacion()
    {
        if (!$this->vehiculoSeleccionado || !$this->v_mandante_id) return;
        
        // LÃ“GICA DE RESERVA: Si no hay dependencia, la UO tambiÃ©n debe ser NULL
        if ($this->v_dependencia_id === 'null' || $this->v_dependencia_id === null) {
            $this->v_dependencia_id = null;
            $this->v_unidad_organizacional_mandante_id = null;
        }

        $validatedData = $this->validate($this->rulesVinculacion());
        if ($validatedData['v_is_active']) {
            $validatedData['v_fecha_desasignacion'] = null;
            $validatedData['v_motivo_desasignacion'] = null;
        }
        $dataToSave = [
            'vehiculo_id' => $this->vehiculoSeleccionado->id,
            'unidad_organizacional_mandante_id' => $validatedData['v_unidad_organizacional_mandante_id'],
            'dependencia_id' => $validatedData['v_dependencia_id'],
            'sub_tipo_vehiculo_mandante_id' => $this->v_sub_tipo_vehiculo_mandante_id ?: null,
            'fecha_asignacion' => $validatedData['v_fecha_asignacion'],
            'is_active' => $validatedData['v_is_active'],
            'fecha_desasignacion' => $validatedData['v_fecha_desasignacion'],
            'motivo_desasignacion' => $validatedData['v_motivo_desasignacion'],
        ];
        $asignacion = VehiculoAsignacion::updateOrCreate(['id' => $this->vinculacionId], $dataToSave);
        
        // Sync de condiciones de vehiculo
        $asignacion->condiciones()->sync($this->v_condiciones_vehiculo_ids);

        session()->flash('message_vinculacion', 'VinculaciÃ³n guardada correctamente.');
        $this->cerrarModalVinculacion();
    }

    public function cerrarModalVinculacion()
    {
        $this->showModalNuevaVinculacion = false;
        $this->resetVinculacionFields();
    }

    public function toggleActivoVinculacion(VehiculoAsignacion $vinculacion)
    {
        if ($vinculacion && $vinculacion->vehiculo_id == $this->vehiculoSeleccionado?->id) {
            if ($vinculacion->is_active) {
                $vinculacion->is_active = false;
                $vinculacion->fecha_desasignacion = $vinculacion->fecha_desasignacion ?? now();
                $vinculacion->motivo_desasignacion = $vinculacion->motivo_desasignacion ?? 'Desactivado manualmente desde listado.';
            } else {
                $existeOtraActivaEnMismaUO = VehiculoAsignacion::where('vehiculo_id', $vinculacion->vehiculo_id)
                    ->where('unidad_organizacional_mandante_id', $vinculacion->unidad_organizacional_mandante_id)
                    ->where('dependencia_id', $vinculacion->dependencia_id)
                    ->where('is_active', true)
                    ->where('id', '!=', $vinculacion->id)->exists();
                if ($existeOtraActivaEnMismaUO) { session()->flash('error_vinculacion', 'No se puede activar. El vehÃ­culo ya tiene otra asignaciÃ³n activa en esta UO y Lugar de Trabajo.'); return; }
                $vinculacion->is_active = true;
                $vinculacion->fecha_desasignacion = null;
                $vinculacion->motivo_desasignacion = null;
            }
            $vinculacion->save();
            session()->flash('message_vinculacion', 'Estado de la vinculaciÃ³n cambiado.');
        }
    }

    public function abrirModalCargaDocumentos(int $vehiculoId, ?int $mandanteId, ?int $unidadOrganizacionalId = null, string $contexto = 'VehÃ­culo en Reserva')
    {
        if (!$mandanteId) {
            session()->flash('error_vehiculo', 'No se puede gestionar documentos: No se pudo determinar el Mandante.');
            return;
        }

        $this->dispatch('abrirModalDocumentos', 
            recursoId: $vehiculoId, 
            recursoType: Vehiculo::class,
            mandanteId: $mandanteId,
            unidadOrganizacionalId: $unidadOrganizacionalId,
            contexto: $contexto
        );
    }

    public function render()
    {
        $vinculacionesPaginadas = collect();
        $totalVehiculosUnicos = 0;
        $totalAsignaciones = 0;

        if ($this->vistaActual === 'listado_vehiculos' && $this->contratistaId) {
            
            $baseQuery = VehiculoAsignacion::query()
                ->join('vehiculos', 'vehiculo_asignaciones.vehiculo_id', '=', 'vehiculos.id')
                ->where('vehiculos.contratista_id', $this->contratistaId);

            // Filtro Estado
            $baseQuery->when($this->filtroEstado === 'activos', fn($q) => $q->where('vehiculos.is_active', true))
                      ->when($this->filtroEstado === 'inactivos', fn($q) => $q->where('vehiculos.is_active', false));

            // Filtro Lugar de Trabajo (Dependencia)
            if ($this->lugarDeTrabajoId === 'orphaned') {
                $idsDependenciasAsignadas = Contratista::find($this->contratistaId)->dependencias()->pluck('dependencias.id')->toArray();
                $baseQuery->whereNotNull('vehiculo_asignaciones.dependencia_id')->whereNotIn('vehiculo_asignaciones.dependencia_id', $idsDependenciasAsignadas);
                if ($this->mandanteId) {
                    $baseQuery->where(function($q) {
                        $q->whereHas('unidadOrganizacionalMandante', fn ($sq) => $sq->where('mandante_id', $this->mandanteId))
                          ->orWhereHas('subTipoVehiculo', fn ($sq) => $sq->where('mandante_id', $this->mandanteId));
                    });
                }
            } elseif ($this->lugarDeTrabajoId === 'in_reserve') {
                $baseQuery->whereNull('vehiculo_asignaciones.dependencia_id');
                if ($this->mandanteId) {
                    $baseQuery->where(function($q) {
                        $q->whereHas('unidadOrganizacionalMandante', fn ($sq) => $sq->where('mandante_id', $this->mandanteId))
                          ->orWhereHas('subTipoVehiculo', fn ($sq) => $sq->where('mandante_id', $this->mandanteId));
                    });
                }
            } else {
                $contratista = Contratista::find($this->contratistaId);
                $idsDependenciasValidas = $contratista->dependencias()->pluck('dependencias.id')->toArray();

                if ($this->lugarDeTrabajoId) {
                    $baseQuery->where('vehiculo_asignaciones.dependencia_id', $this->lugarDeTrabajoId);
                } else {
                    $baseQuery->whereIn('vehiculo_asignaciones.dependencia_id', $idsDependenciasValidas);
                }
                
                if ($this->mandanteId) {
                    $baseQuery->where(function($q) {
                        $q->whereHas('unidadOrganizacionalMandante', fn ($sq) => $sq->where('mandante_id', $this->mandanteId))
                          ->orWhereHas('subTipoVehiculo', fn ($sq) => $sq->where('mandante_id', $this->mandanteId));
                    });
                }
            }

            // Filtro Unidad Organizacional
            if ($this->unidadOrganizacionalId) {
                $baseQuery->where('vehiculo_asignaciones.unidad_organizacional_mandante_id', $this->unidadOrganizacionalId);
            }

            // Filtro BÃºsqueda
            $baseQuery->when($this->searchVehiculo, function ($query) {
                $searchTerm = '%' . str_replace(' ', '%', $this->searchVehiculo) . '%';
                $query->where(fn($q) => $q->where('vehiculos.patente_letras', 'like', $searchTerm)
                      ->orWhere('vehiculos.patente_numeros', 'like', $searchTerm)
                      ->orWhere(DB::raw("CONCAT(vehiculos.patente_letras, vehiculos.patente_numeros)"), 'like', $searchTerm)
                      ->orWhereHas('vehiculo.marcaVehiculo', fn ($sub) => $sub->where('nombre', 'like', $searchTerm)));
            });

            $totalAsignaciones = (clone $baseQuery)->count();
            $totalVehiculosUnicos = (clone $baseQuery)->distinct('vehiculo_id')->count();

            $vinculacionesPaginadas = (clone $baseQuery)
                ->with([
                    'vehiculo' => function($q) {
                        $q->withCount('vinculaciones')->with(['marcaVehiculo', 'tipoVehiculo']);
                    }, 
                    'dependencia.parent', 
                    'unidadOrganizacionalMandante' => function($q) {
                        $q->with(['parent', 'mandante:id,razon_social']);
                    },
                    'subTipoVehiculo'
                ])
                ->select('vehiculo_asignaciones.*')
                ->orderBy($this->sortByVehiculo, $this->sortDirectionVehiculo)
                ->paginate(100, ['*'], 'vehiculosPage');

            // ================== INICIO AISLAMIENTO DEFENSIVO (PASO 1) ==================
            // Extraer valores pre-calculados de la BD (Acceso y %)
            $this->estadosPreCalculados = [];
            foreach ($vinculacionesPaginadas as $vinculacion) {
                // Decodificar JSON si viene como string, o usar array si ya estÃ¡ casteado
                $estadoAcceso = $vinculacion->estado_acceso;
                if (is_string($estadoAcceso)) {
                    $estadoAcceso = json_decode($estadoAcceso, true);
                }
                
                $this->estadosPreCalculados[$vinculacion->id] = [
                    'estado_acceso' => $estadoAcceso ?? ['habilitado' => false, 'motivo' => 'Estado no calculado'],
                    'porcentaje_cumplimiento' => $vinculacion->porcentaje_cumplimiento ?? 0,
                ];
            }
            // ================== FIN PASO 1 ==================

            // ================== INICIO AISLAMIENTO DEFENSIVO (PASO 2) ==================
            // Calcular estados de documentos individuales (Columnas 1, 2, etc.)
            $this->estadosDocumentosPorVinculacion = [];
            if (!empty($this->documentosMaestros)) {
                foreach ($vinculacionesPaginadas as $vinculacion) {
                    $mId = $vinculacion->unidadOrganizacionalMandante?->mandante_id ?? $vinculacion->subTipoVehiculo?->mandante_id;
                    $uoId = $vinculacion->unidad_organizacional_mandante_id;

                    if ($vinculacion->vehiculo && $mId) {
                        $estados = $this->documentoService->obtenerEstadoDocumentosParaEntidad(
                            $vinculacion->vehiculo, 
                            $mId, 
                            $uoId
                        );
                        $this->estadosDocumentosPorVinculacion[$vinculacion->id] = collect($estados)
                            ->mapWithKeys(fn($item) => [$item['nombre_documento_id'] => $item['estado_actual_documento']]);
                    } else {
                        $this->estadosDocumentosPorVinculacion[$vinculacion->id] = collect();
                    }
                }
            }
            // ================== FIN PASO 2 ==================

        } elseif ($this->vistaActual === 'listado_vinculaciones' && $this->vehiculoSeleccionado) {
            $vinculacionesPaginadas = $this->vehiculoSeleccionado->vinculaciones()
                ->with(['unidadOrganizacionalMandante.parent', 'dependencia.parent'])
                ->orderBy('is_active', 'desc')->orderBy('fecha_asignacion', 'desc')
                ->paginate(10, ['*'], 'vinculacionesPage');
        }

        return view('livewire.contratista.gestion-vehiculos-contratista', [
            'vinculacionesPaginadas' => $vinculacionesPaginadas,
            'totalVehiculosUnicos' => $totalVehiculosUnicos,
            'totalAsignaciones' => $totalAsignaciones,
        ]);
    }
}
