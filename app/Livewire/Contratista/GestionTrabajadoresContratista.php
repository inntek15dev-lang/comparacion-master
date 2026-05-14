<?php

namespace App\Livewire\Contratista;

use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\Trabajador;
use App\Models\Contratista;
use App\Models\Mandante;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\CargoMandante;
use App\Models\TipoCondicionPersonal;
use App\Models\TrabajadorVinculacion;
use App\Models\ReglaDocumental;
use App\Models\CondicionFechaIngreso;
use App\Models\TipoEntidadControlable;
use App\Models\Nacionalidad;
use App\Models\Sexo;
use App\Models\EstadoCivil;
use App\Models\NivelEducacional;
use App\Models\Etnia;
use App\Models\Region;
use App\Models\Comuna;
use App\Models\TipoPermanencia;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use App\Rules\ValidarRutRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Livewire\WithFileUploads;
use App\Models\DocumentoCargado;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\DocumentoRequeridoService;
use App\Services\CriticidadDocumentoService;
use App\Services\EstadoCumplimientoService;
use App\Models\Dependencia;
use App\Models\DocumentoExcepcionCriticidad;
use App\Jobs\ActualizarEstadoRecursoIndividual;

class GestionTrabajadoresContratista extends Component
{
    use WithPagination;
    use WithFileUploads;

    public ?int $mandanteId = null;
    public ?int $unidadOrganizacionalId = null;
    public $lugarDeTrabajoId = null;
    public string $nombreVinculacionSeleccionada = '';
    public string $vistaActual = 'listado_trabajadores';
    public ?Trabajador $trabajadorSeleccionado = null;
    public $contratistaId;
    public string $searchTrabajador = '';
    public string $sortByTrabajador = 'trabajadores.apellido_paterno';
    public string $sortDirectionTrabajador = 'asc';
    public bool $showModalFichaTrabajador = false;
    public ?int $trabajadorId = null;
    
    public string $nombres = '';
    public string $apellido_paterno = '';
    public ?string $apellido_materno = null;
    public string $rut_trabajador = '';

    public ?string $fecha_nacimiento = null, $email_trabajador = null, $celular_trabajador = null, $fecha_ingreso_empresa = null;
    public ?int $nacionalidad_id = null, $tipo_permanencia_id = null, $sexo_id = null, $estado_civil_id = null, $nivel_educacional_id = null, $etnia_id = null;
    public ?string $direccion_calle = null, $direccion_numero = null, $direccion_departamento = null;
    public ?int $trabajador_region_id = null, $trabajador_comuna_id = null;
    public bool $trabajador_is_active = true;
    public ?int $cargo_mandante_id_nuevo = null;
    
    public bool $showModalNuevaVinculacion = false;

    public ?int $vinculacionId = null;
    public ?int $v_mandante_id = null;
    public ?int $v_unidad_organizacional_mandante_id = null;
    public ?int $v_cargo_mandante_id = null;
    public $v_dependencia_id = null;
    public array $v_condiciones_personales_ids = [];
    public ?string $v_fecha_ingreso_vinculacion = null;
    public ?string $v_fecha_contrato = null;
    public bool $v_is_active = true;
    public ?string $v_fecha_desactivacion = null;
    public ?string $v_motivo_desactivacion = null;
    public ?string $v_fecha_finiquito = null;

    // Desactivacion Modal
    public bool $showModalDesactivacion = false;
    public bool $showErrorFiniquitoModal = false;
    public string $desactivacionContext = 'FINIQUITADO'; // Opciones: FINIQUITADO, CESACION_PRINCIPAL, RECONOCIMIENTO_ANTIGUEDAD, PRESENTE_EN_OTRA_VINCULACION
    public ?string $vinculacion_correcta_id = '';
    public ?int $vinculacionADesactivar = null;
    public $nacionalidades = [], $tiposPermanencias = [], $sexos = [], $estadosCiviles = [], $nivelesEducacionales = [], $etnias = [];
    public $regiones = [], $comunasDisponiblesTrabajador = [];
    public $mandantesDisponibles = [], $unidadesOrganizacionalesDisponibles = [], $cargosMandanteDisponibles = [];
    public $dependenciasDisponibles = [];
    public $tiposCondicionPersonal = [];
    
    // Campos de contrato para vinculación
    public ?string $v_numero_contrato = null;
    public ?int $v_tipo_contrato_id = null;
    public $contratosDisponibles = [];
    public $tiposContratoDisponibles = [];
    
    // Filtros adicionales
    public string $filtroNumeroContrato = '';
    
    // Contexto de contrato heredado del panel padre
    public ?string $numeroContratoContexto = null;
    public ?int $filtroTipoContratoId = null;
    
    private DocumentoRequeridoService $documentoService;
    private CriticidadDocumentoService $criticidadService;
    public ?int $contratistaIdForzado = null;
    public ?int $abrirModalParaId = null;

    // CAMBIO 5: Si el CUO no acredita, ocultar % Cumplimiento, Acceso y botón de carga de documentos.
    public bool $sinAcreditacion = false;
    
    public bool $puedeEstarEnReserva = false;

    public array $documentosMaestros = [];
    public string $filtroEstado = 'activos';

    public array $estadosDocumentosPorVinculacion = [];
    
    // ================== NUEVAS PROPIEDADES PARA AISLAMIENTO ==================
    /**
     * Almacena los valores pre-calculados de estado_acceso y porcentaje_cumplimiento
     * protegidos de cualquier recalculo durante el renderizado.
     * 
     * Estructura: [vinculacion_id => ['estado_acceso' => [...], 'porcentaje_cumplimiento' => int]]
     */
    public array $estadosPreCalculados = [];
    // ================== FIN NUEVAS PROPIEDADES ==================

    // ================== PROPIEDADES PARA MODAL DE EXCEPCIONES ==================
    public bool $showAnulacionModal = false;
    public $recursoSeleccionado = null;
    public ?string $recursoType = null;
    public ?string $accionAnulacion = null;
    public string $justificacion = '';
    public ?string $valido_hasta = null;
    // ================== FIN PROPIEDADES EXCEPCIONES ==================

    public bool $mostrarAvisoHistorial = false;

    protected $listeners = ['documentosActualizados' => '$refresh'];

    public function setVDependenciaIdAttribute($value)
    {
        $this->v_dependencia_id = ($value === 'null' || $value === '') ? null : (int)$value;
    }
    
    public function boot(DocumentoRequeridoService $documentoService, CriticidadDocumentoService $criticidadService)
    {
        $this->documentoService = $documentoService;
        $this->criticidadService = $criticidadService;
    }

    #[On('criticidad-actualizada')]
    public function refrescarPorCambioDeCriticidad()
    {
        Log::info('Oyente activado: Criticidad actualizada. Refrescando el componente de trabajadores.');
    }

    protected function messages()
    {
        return [
            '*.required' => 'Este campo es obligatorio.',
            'rut_trabajador.unique' => 'El RUT del trabajador ya existe.',
            'email_trabajador.email' => 'El formato del email no es válido.',
            'email_trabajador.unique' => 'El email del trabajador ya existe.',
            'v_fecha_desactivacion.required_if' => 'La fecha de desactivación es obligatoria si la vinculación no está activa.',
            'v_motivo_desactivacion.required_if' => 'El motivo de desactivación es obligatorio si la vinculación no está activa.',
            'cargo_mandante_id_nuevo.required' => 'Debe seleccionar un cargo para el nuevo trabajador.',
            'v_dependencia_id.required_without_all' => 'Debe seleccionar un Lugar de Trabajo.',
            'v_unidad_organizacional_mandante_id.required' => 'Debe seleccionar una Unidad Organizacional.',
        ];
    }

    public function mount(?int $mandanteId = null, ?int $unidadOrganizacionalId = null, $lugarDeTrabajoId = null, array $documentosMaestros = [], $numeroContrato = null)
    {
        $this->mandanteId = $mandanteId;
        $this->unidadOrganizacionalId = $unidadOrganizacionalId;
        $this->lugarDeTrabajoId = $lugarDeTrabajoId;
        $this->documentosMaestros = $documentosMaestros;

        if ($numeroContrato) {
            $this->filtroNumeroContrato = $numeroContrato;
            $this->numeroContratoContexto = $numeroContrato; // Guardar para usar en vinculaciones nuevas
        }

        if ($this->contratistaIdForzado) {
            $this->contratistaId = $this->contratistaIdForzado;
        } else {
            $user = Auth::user();
            $this->contratistaId = $user->contratista_id;
        }

        if (!$this->contratistaId) {
            session()->flash('error', 'Usuario no asociado a un contratista válido.');
            return;
        }

        if ($this->unidadOrganizacionalId) {
            $uoContexto = UnidadOrganizacionalMandante::with('mandante:id,razon_social')->find($this->unidadOrganizacionalId);
            if ($uoContexto && $uoContexto->mandante) {
                $this->nombreVinculacionSeleccionada = ($uoContexto->mandante->razon_social ?? 'N/A') . ' - ' . $uoContexto->nombre_unidad;
            }
        }

        $this->nacionalidades = Nacionalidad::orderBy('nombre')->get();
        $this->tiposPermanencias = TipoPermanencia::where('is_active', true)->orderBy('nombre')->get();
        $this->sexos = Sexo::orderBy('nombre')->get();
        $this->estadosCiviles = EstadoCivil::orderBy('nombre')->get();
        $this->nivelesEducacionales = NivelEducacional::orderBy('nombre')->get();
        $this->etnias = Etnia::orderBy('nombre')->get();
        $this->regiones = Region::orderBy('nombre')->get();
        $this->tiposContratoDisponibles = \App\Models\TipoContrato::where('is_active', true)->orderBy('nombre')->get();
        
        // Condiciones personales cargadas según el mandante actual
        $this->_cargarCondicionesPersonalesPorMandante($this->mandanteId);
        $this->mandantesDisponibles = Mandante::whereHas('unidadesOrganizacionales', function ($query) {
            $query->whereHas('contratistasHabilitados', function ($subQuery) {
                $subQuery->where('contratistas.id', $this->contratistaId);
            });
        })->orderBy('razon_social')->get();

        if ($this->abrirModalParaId) {
            $this->abrirModalDocumentosTrabajador($this->abrirModalParaId);
        }

        // SANEAMIENTO DE ANARQUÍA: Limpiar campos de lugar/contrato de vinculaciones inactivas existentes.
        // PROTECCIÓN: No tocar vinculaciones que estén en estado FINIQUITADO/CESACION/RECONOCIMIENTO_ANTIGUEDAD
        // en una carpeta de nómina NO EMITIDA (período aún no enviado). Esas serán limpiadas por
        // consolidarReserva() al momento de enviar el período. Si se tocan antes, se impide la reversión.
        \App\Models\TrabajadorVinculacion::where('is_active', false)
            ->whereNotExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('carpetas_verificacion_trabajadores AS cvt')
                    ->join('carpetas_verificacion AS cv', 'cv.id', '=', 'cvt.carpeta_verificacion_id')
                    ->whereColumn('cvt.trabajador_vinculacion_id', 'trabajador_vinculaciones.id')
                    ->whereIn('cvt.estado_revision', ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD'])
                    ->where(function($q2) {
                        $q2->whereNull('cv.estado_revision')
                           ->orWhere('cv.estado_revision', '!=', 'EMITIDO');
                    });
            })
            ->where(function($q) {
                $q->whereNotNull('unidad_organizacional_mandante_id')
                  ->orWhereNotNull('dependencia_id')
                  ->orWhereNotNull('numero_contrato');
            })
            ->update([
                'unidad_organizacional_mandante_id' => null,
                'dependencia_id' => null,
                'numero_contrato' => null
            ]);
    }

    public function eliminarTrabajador($id)
    {
        $trabajador = Trabajador::withTrashed()->where('id', $id)->where('contratista_id', $this->contratistaId)->first();
        if ($trabajador) {
            try {
                $trabajador->forceDelete();
                session()->flash('message_trabajador', 'Trabajador y todas sus vinculaciones han sido eliminados PERMANENTEMENTE.');
                $this->cerrarModalFichaTrabajador();
                $this->irAListadoTrabajadores();
                $this->dispatch('recursosActualizados');
            } catch (\Exception $e) {
                Log::error("Error al eliminar permanentemente trabajador ID {$id}: " . $e->getMessage());
                session()->flash('error_trabajador', 'Ocurrió un error al eliminar el trabajador.');
            }
        } else {
            session()->flash('error_trabajador', 'No se pudo eliminar el trabajador. No fue encontrado o no pertenece a su empresa.');
        }
    }

    public function eliminarVinculacion($vinculacionId)
    {
        $vinculacion = TrabajadorVinculacion::with('trabajador')->find($vinculacionId);

        if ($vinculacion && $vinculacion->trabajador->contratista_id == $this->contratistaId) {
            
            if ($vinculacion->trabajador->vinculaciones()->count() <= 1) {
                session()->flash('error_vinculacion', 'Acción no permitida. No se puede eliminar la última vinculación de un trabajador. Para desvincularlo, edite la vinculación para moverlo a "Reserva" o desactive al trabajador desde su ficha.');
                return;
            }

            // BLOQUEO ANTI-FRAUDE INN: Evitar que borren la vinculación si ha tocado CUALQUIER nómina (incluso En Curso)
            // Trabajador ingresado a nómina = trabajador que debe ser auditado/verificado. No hay borrado "fácil".
            $haSidoProcesada = \App\Models\CarpetaVerificacionTrabajador::where('trabajador_vinculacion_id', $vinculacionId)->exists();

            if ($haSidoProcesada) {
                session()->flash('error_vinculacion', 'Bloqueo INN Anti-Fraude: Esta vinculación ya se encuentra registrada en una nómina de auditoría. Todo trabajador ingresado debe ser justificado documentariamente ante el auditor. Si fue un error, gestione su estado ("Finiquitado", etc.) adjuntando la declaración notarial correspondiente.');
                return;
            }

            try {
                $trabajadorAfectado = $vinculacion->trabajador;
                $vinculacion->delete();
                
                ActualizarEstadoRecursoIndividual::dispatch($trabajadorAfectado);

                session()->flash('message_vinculacion', 'La vinculación ha sido eliminada correctamente.');
                $this->dispatch('recursosActualizados');
            } catch (\Exception $e) {
                Log::error("Error al eliminar vinculación ID {$vinculacionId}: " . $e->getMessage());
                session()->flash('error_vinculacion', 'Ocurrió un error al eliminar la vinculación.');
            }
        } else {
            session()->flash('error_vinculacion', 'No se pudo eliminar la vinculación. No fue encontrada o no pertenece a su empresa.');
        }
    }

    public function seleccionarTrabajadorParaVinculaciones($trabajadorId)
    {
        $this->trabajadorSeleccionado = Trabajador::find($trabajadorId);
        if ($this->trabajadorSeleccionado && $this->trabajadorSeleccionado->contratista_id == $this->contratistaId) {
            $this->vistaActual = 'listado_vinculaciones';
            $this->resetPage('vinculacionesPage');
        } else {
            session()->flash('error_trabajador', 'Trabajador no encontrado o no pertenece a su empresa.');
            $this->trabajadorSeleccionado = null;
        }
    }

    public function irAListadoTrabajadores()
    {
        $this->vistaActual = 'listado_trabajadores';
        $this->trabajadorSeleccionado = null;
        $this->resetPage('trabajadoresPage');
    }

    public function rulesFichaTrabajador()
    {
        $rules = [
            'nombres' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'nullable|string|max:100',
            'rut_trabajador' => [
                'required', 
                'string', 
                new ValidarRutRule(), 
                // Regla Unique Inteligente: Ignora si es el mismo trabajador en edición O si es una re-contratación del mismo contratista
                function($attribute, $value, $fail) {
                    $rutNormalizado = strtoupper(str_replace(['.', '-', ' '], '', $value));
                    $exist = \App\Models\Trabajador::withTrashed()
                        ->where('contratista_id', $this->contratistaId)
                        ->whereRaw("UPPER(REPLACE(REPLACE(rut, '.', ''), '-', '')) = ?", [$rutNormalizado])
                        ->where('id', '!=', $this->trabajadorId)
                        ->exists();
                    if ($exist) {
                        $fail('El RUT ingresado ya está registrado con otro trabajador diferente en su empresa.');
                    }
                }
            ],
            'nacionalidad_id' => 'required|exists:nacionalidades,id',
            'tipo_permanencia_id' => 'required|exists:tipos_permanencias,id',
            'fecha_nacimiento' => 'nullable|date|before_or_equal:today',
            'sexo_id' => 'nullable|exists:sexos,id',
            'email_trabajador' => ['nullable', 'email', 'max:255', Rule::unique('trabajadores', 'email')->ignore($this->trabajadorId)],
            'celular_trabajador' => 'nullable|string|max:20',
            'estado_civil_id' => 'nullable|exists:estados_civiles,id',
            'nivel_educacional_id' => 'nullable|exists:niveles_educacionales,id',
            'etnia_id' => 'nullable|exists:etnias,id',
            'direccion_calle' => 'nullable|string|max:255',
            'direccion_numero' => 'nullable|string|max:50',
            'direccion_departamento' => 'nullable|string|max:50',
            'trabajador_region_id' => 'nullable|exists:regiones,id',
            'trabajador_comuna_id' => 'nullable|exists:comunas,id',
            'fecha_ingreso_empresa' => 'nullable|date',
            'trabajador_is_active' => 'boolean',
        ];
        
        // Función auxiliar para obtener familia
        $getFamilyIds = function($cId, $mId) {
            $currentId = $cId;
            while(true) {
                $sol = \App\Models\SolicitudVinculacion::where('contratista_id', $currentId)
                    ->where('mandante_id', $mId)->where('estado', 'APROBADA')->first();
                if (!$sol || !$sol->contratista_padre_id) break;
                $currentId = $sol->contratista_padre_id;
            }
            $rootId = $currentId;
            $familyIds = [$rootId];
            $lvl1 = \App\Models\SolicitudVinculacion::where('contratista_padre_id', $rootId)->where('mandante_id', $mId)->where('estado', 'APROBADA')->pluck('contratista_id')->toArray();
            $familyIds = array_merge($familyIds, $lvl1);
            if (!empty($lvl1)) {
                $lvl2 = \App\Models\SolicitudVinculacion::whereIn('contratista_padre_id', $lvl1)->where('mandante_id', $mId)->where('estado', 'APROBADA')->pluck('contratista_id')->toArray();
                $familyIds = array_merge($familyIds, $lvl2);
                if (!empty($lvl2)) {
                    $lvl3 = \App\Models\SolicitudVinculacion::whereIn('contratista_padre_id', $lvl2)->where('mandante_id', $mId)->where('estado', 'APROBADA')->pluck('contratista_id')->toArray();
                    $familyIds = array_merge($familyIds, $lvl3);
                }
            }
            return array_unique($familyIds);
        };
        
        if (!$this->trabajadorId) {
            $rules['cargo_mandante_id_nuevo'] = 'required|exists:cargos_mandante,id';
            $rules['v_dependencia_id'] = 'required|exists:dependencias,id';
            $rules['v_condiciones_personales_ids'] = 'array';
            $rules['v_condiciones_personales_ids.*'] = 'exists:tipos_condicion_personal,id';
            $rules['v_unidad_organizacional_mandante_id'] = [
                'required',
                'exists:unidades_organizacionales_mandante,id',
                function ($attribute, $value, $fail) use ($getFamilyIds) {
                    if (!$this->trabajador_is_active) return;
                    
                    $numeroContrato = $this->v_numero_contrato ?: $this->numeroContratoContexto;
                    if ($numeroContrato === 'sin_contrato') { $numeroContrato = null; }
                    
                    $depId = $this->v_dependencia_id;
                    
                    $queryCuo = \App\Models\ContratistaUnidadOrganizacional::where('contratista_id', $this->contratistaId)
                        ->where('unidad_organizacional_mandante_id', $value)
                        ->where('dependencia_id', $depId);
                    
                    if ($numeroContrato) {
                        $queryCuo->where('numero_contrato', $numeroContrato);
                    } else {
                        $queryCuo->whereNull('numero_contrato');
                    }
                    
                    $cuo = $queryCuo->first();
                    if ($cuo && !is_null($cuo->trabajadores_cuota)) {
                        $familyIds = $getFamilyIds($this->contratistaId, $this->mandanteId);

                        $countVinculaciones = \App\Models\TrabajadorVinculacion::whereHas('trabajador', function($q) use ($familyIds) {
                                $q->whereIn('contratista_id', $familyIds);
                            })
                            ->where('unidad_organizacional_mandante_id', $value)
                            ->where('dependencia_id', $depId)
                            ->where('is_active', true);
                            
                        if ($numeroContrato) {
                            $countVinculaciones->where('numero_contrato', $numeroContrato);
                        } else {
                            $countVinculaciones->whereNull('numero_contrato');
                        }
                        
                        $activeCount = $countVinculaciones->count();
                        if ($activeCount >= $cuo->trabajadores_cuota) {
                            $fail("Límite superado: La cuota máxima permitida es de {$cuo->trabajadores_cuota} trabajadores en conjunto (incluye subcontratistas).");
                        }
                    }

                    // --- NUEVA VALIDACIÓN: Cuota por Cargo ---
                    $cargoId = $this->cargo_mandante_id_nuevo;
                    if ($cuo && $cargoId) {
                        $cuoCargo = \App\Models\ContratistaUoCargo::where('contratista_uo_id', $cuo->id)
                            ->where('cargo_mandante_id', $cargoId)
                            ->first();
                            
                        if ($cuoCargo && !is_null($cuoCargo->cuota)) {
                            $familyIds = $getFamilyIds($this->contratistaId, $this->mandanteId);
                            $countCargo = \App\Models\TrabajadorVinculacion::whereHas('trabajador', function($q) use ($familyIds) {
                                    $q->whereIn('contratista_id', $familyIds);
                                })
                                ->where('unidad_organizacional_mandante_id', $value)
                                ->where('dependencia_id', $depId)
                                ->where('cargo_mandante_id', $cargoId)
                                ->where('is_active', true);

                            // Ajuste para número de contrato
                            if ($numeroContrato) {
                                $countCargo->where('numero_contrato', $numeroContrato);
                            } else {
                                $countCargo->whereNull('numero_contrato');
                            }
                                
                            if ($countCargo->count() >= $cuoCargo->cuota) {
                                $fail("Límite superado para el cargo '{$cuoCargo->cargoMandante->nombre_cargo}': La cuota máxima es de {$cuoCargo->cuota} trabajadores.");
                            }
                        }
                    }
                }
            ];
        }
        return $rules;
    }

    public function updatedTrabajadorRegionId($value)
    {
        if ($value) {
            $this->comunasDisponiblesTrabajador = Comuna::where('region_id', $value)->orderBy('nombre')->get();
        } else {
            $this->comunasDisponiblesTrabajador = [];
        }
        $this->trabajador_comuna_id = null;
    }

    public function updatedRutTrabajador($value)
    {
        $this->mostrarAvisoHistorial = false;
        if (empty($value)) return;

        // Normalizar RUT para búsqueda (sin puntos ni guiones, mayúscula)
        $rutNormalizado = strtoupper(str_replace(['.', '-', ' '], '', $value));
        
        // BUSQUEDA SEGURA (AISLADA POR CONTRATISTA)
        $trabajadorExistente = \App\Models\Trabajador::withTrashed()
            ->where('contratista_id', $this->contratistaId)
            ->where(function($q) use ($rutNormalizado) {
                // El campo RUT en BD puede estar guardado de varias formas, usamos una búsqueda flexible o normalizada si existiera columna
                $q->whereRaw("UPPER(REPLACE(REPLACE(rut, '.', ''), '-', '')) = ?", [$rutNormalizado]);
            })
            ->first();

        if ($trabajadorExistente) {
            $this->mostrarAvisoHistorial = true;
            $this->trabajadorId = $trabajadorExistente->id;
            $this->nombres = $trabajadorExistente->nombres;
            $this->apellido_paterno = $trabajadorExistente->apellido_paterno;
            $this->apellido_materno = $trabajadorExistente->apellido_materno;
            $this->nacionalidad_id = $trabajadorExistente->nacionalidad_id;
            $this->tipo_permanencia_id = $trabajadorExistente->tipo_permanencia_id;
            $this->fecha_nacimiento = $trabajadorExistente->fecha_nacimiento ? $trabajadorExistente->fecha_nacimiento->format('Y-m-d') : null;
            $this->sexo_id = $trabajadorExistente->sexo_id;
            $this->email_trabajador = $trabajadorExistente->email;
            $this->celular_trabajador = $trabajadorExistente->celular;
            $this->estado_civil_id = $trabajadorExistente->estado_civil_id;
            $this->nivel_educacional_id = $trabajadorExistente->nivel_educacional_id;
            $this->etnia_id = $trabajadorExistente->etnia_id;
            $this->direccion_calle = $trabajadorExistente->direccion_calle;
            $this->direccion_numero = $trabajadorExistente->direccion_numero;
            $this->direccion_departamento = $trabajadorExistente->direccion_departamento;
            $this->trabajador_region_id = $trabajadorExistente->comuna?->region_id;
            if ($this->trabajador_region_id) {
                $this->comunasDisponiblesTrabajador = \App\Models\Comuna::where('region_id', $this->trabajador_region_id)->orderBy('nombre')->get();
            }
            $this->trabajador_comuna_id = $trabajadorExistente->comuna_id;
            $this->fecha_ingreso_empresa = $trabajadorExistente->fecha_ingreso_empresa ? $trabajadorExistente->fecha_ingreso_empresa->format('Y-m-d') : null;
            $this->trabajador_is_active = true; // Por defecto lo reactivamos
        }
    }

    public function updatedSearchTrabajador()
    {
        $this->resetPage('trabajadoresPage');
    }

    public function updatedFiltroEstado()
    {
        $this->resetPage('trabajadoresPage');
    }

    private function resetFichaTrabajadorFields()
    {
        $this->trabajadorId = null;
        $this->nombres = '';
        $this->apellido_paterno = '';
        $this->apellido_materno = null;
        $this->rut_trabajador = '';
        $this->fecha_nacimiento = null;
        $this->email_trabajador = null;
        $this->celular_trabajador = null;
        $this->fecha_ingreso_empresa = null;
        $this->nacionalidad_id = null;
        $this->tipo_permanencia_id = null;
        $this->sexo_id = null;
        $this->estado_civil_id = null;
        $this->nivel_educacional_id = null;
        $this->etnia_id = null;
        $this->direccion_calle = null;
        $this->direccion_numero = null;
        $this->direccion_departamento = null;
        $this->trabajador_region_id = null;
        $this->trabajador_comuna_id = null;
        $this->trabajador_is_active = true;
        $this->comunasDisponiblesTrabajador = [];
        $this->cargo_mandante_id_nuevo = null;
        $this->v_unidad_organizacional_mandante_id = null;
        $this->v_dependencia_id = null;
        $this->v_condiciones_personales_ids = [];
        $this->v_numero_contrato = null;
        $this->mostrarAvisoHistorial = false;
        $this->resetValidation();
    }

    public function abrirModalNuevoTrabajador()
    {
        if (!$this->mandanteId) {
            session()->flash('error', 'Error: El contexto del Mandante no está definido.');
            return;
        }
        
        $this->resetFichaTrabajadorFields();
        
        $this->cargosMandanteDisponibles = CargoMandante::where('mandante_id', $this->mandanteId)
            ->where('is_active', true)->orderBy('nombre_cargo')->get();
        
        $this->unidadesOrganizacionalesDisponibles = UnidadOrganizacionalMandante::where('mandante_id', $this->mandanteId)
            ->where('is_active', true)
            ->whereHas('contratistasHabilitados', function ($query) { 
                $query->where('contratista_id', $this->contratistaId); 
            })->orderBy('nombre_unidad')->get();

        $contratista = Contratista::find($this->contratistaId);
        if ($contratista) {
            $this->dependenciasDisponibles = $contratista->dependencias()
                ->with('parent')
                ->where('mandante_id', $this->mandanteId)
                ->where('estado', true)
                ->get()
                ->sortBy('nombre_jerarquico');
        }
        
        $this->v_dependencia_id = (!in_array($this->lugarDeTrabajoId, ['orphaned', 'in_reserve', 'null', null, ''])) ? $this->lugarDeTrabajoId : null;

        $this->actualizarCargosDisponibles($this->mandanteId, $this->unidadOrganizacionalId, $this->v_dependencia_id, $this->numeroContratoContexto);

        $this->showModalFichaTrabajador = true;
    }

    public function abrirModalEditarTrabajador($id)
    {
        $trabajador = Trabajador::withTrashed()->with('comuna.region')->find($id);
        if ($trabajador && $trabajador->contratista_id == $this->contratistaId) {
            $this->trabajadorId = $trabajador->id;
            if ($this->vistaActual === 'listado_vinculaciones' && $this->trabajadorSeleccionado && $this->trabajadorSeleccionado->id === $trabajador->id) {}
            else {
                $this->trabajadorSeleccionado = $trabajador;
            }
            $this->nombres = $trabajador->nombres;
            $this->apellido_paterno = $trabajador->apellido_paterno;
            $this->apellido_materno = $trabajador->apellido_materno;
            $this->rut_trabajador = $trabajador->rut;
            $this->nacionalidad_id = $trabajador->nacionalidad_id;
            $this->tipo_permanencia_id = $trabajador->tipo_permanencia_id;
            $this->fecha_nacimiento = $trabajador->fecha_nacimiento ? $trabajador->fecha_nacimiento->format('Y-m-d') : null;
            $this->sexo_id = $trabajador->sexo_id;
            $this->email_trabajador = $trabajador->email;
            $this->celular_trabajador = $trabajador->celular;
            $this->estado_civil_id = $trabajador->estado_civil_id;
            $this->nivel_educacional_id = $trabajador->nivel_educacional_id;
            $this->etnia_id = $trabajador->etnia_id;
            $this->direccion_calle = $trabajador->direccion_calle;
            $this->direccion_numero = $trabajador->direccion_numero;
            $this->direccion_departamento = $trabajador->direccion_departamento;
            $this->trabajador_region_id = $trabajador->comuna?->region_id;
            if ($this->trabajador_region_id) {
                $this->comunasDisponiblesTrabajador = Comuna::where('region_id', $this->trabajador_region_id)->orderBy('nombre')->get();
            }
            $this->trabajador_comuna_id = $trabajador->comuna_id;
            $this->fecha_ingreso_empresa = $trabajador->fecha_ingreso_empresa ? $trabajador->fecha_ingreso_empresa->format('Y-m-d') : null;
            $this->trabajador_is_active = !$trabajador->trashed();
            $this->showModalFichaTrabajador = true;
        }
    }

    public function guardarTrabajador()
    {
        // Validación de lugar para NUEVOS O RE-CONTRATADOS
        if (!$this->trabajadorId || $this->mostrarAvisoHistorial) {
            if (!$this->mandanteId) {
                session()->flash('error_trabajador', 'Error: El contexto del Mandante no está definido.');
                $this->cerrarModalFichaTrabajador();
                return;
            }
        }

        $validatedData = $this->validate($this->rulesFichaTrabajador());

        $datosParaGuardar = [
            'contratista_id' => $this->contratistaId, 'nombres' => $validatedData['nombres'],
            'apellido_paterno' => $validatedData['apellido_paterno'], 'apellido_materno' => $validatedData['apellido_materno'],
            'rut' => $validatedData['rut_trabajador'], 'nacionalidad_id' => $validatedData['nacionalidad_id'],
            'tipo_permanencia_id' => $validatedData['tipo_permanencia_id'],
            'fecha_nacimiento' => $validatedData['fecha_nacimiento'], 'sexo_id' => $validatedData['sexo_id'],
            'email' => $validatedData['email_trabajador'], 'celular' => $validatedData['celular_trabajador'],
            'estado_civil_id' => $validatedData['estado_civil_id'], 'nivel_educacional_id' => $validatedData['nivel_educacional_id'],
            'etnia_id' => $validatedData['etnia_id'], 'direccion_calle' => $validatedData['direccion_calle'],
            'direccion_numero' => $validatedData['direccion_numero'], 'direccion_departamento' => $validatedData['direccion_departamento'],
            'comuna_id' => $validatedData['trabajador_comuna_id'], 'fecha_ingreso_empresa' => $validatedData['fecha_ingreso_empresa'],
            'is_active' => $validatedData['trabajador_is_active'],
        ];

        DB::beginTransaction();
        try {
            $trabajador = Trabajador::withTrashed()->updateOrCreate(['id' => $this->trabajadorId], $datosParaGuardar);
            
            if ($validatedData['trabajador_is_active'] && $trabajador->trashed()) {
                $trabajador->restore();
            } elseif (!$validatedData['trabajador_is_active'] && !$trabajador->trashed()) {
                $trabajador->delete();
            }

            // SANEAMIENTO GLOBAL DE ANARQUÍA: 
            // Eliminamos rastro de "Reserva" o registros inconsistentes (sin lugar asignado)
            // Esto asegura que Jose Peruano (y cualquier otro) siempre tenga una base de datos limpia al guardar.
            TrabajadorVinculacion::where('trabajador_id', $trabajador->id)
                ->where(function($q) {
                    $q->whereNull('dependencia_id')
                      ->orWhere('dependencia_id', 0)
                      ->orWhere('is_active', false);
                })
                ->delete();

            // Es un nuevo registro O una re-contratación de alguien en reserva
            $esRecontratacion = !$this->trabajadorId || $this->mostrarAvisoHistorial;
            
            // Si es re-contratación y hay datos de vinculación (Lugar + UO), creamos la nueva vinculación activa
            if ($esRecontratacion && $this->v_dependencia_id && $this->v_unidad_organizacional_mandante_id) {
                // Determinar número de contrato: usar seleccionado o contexto heredado
                $numeroContratoParaVinculacion = $this->v_numero_contrato ?: $this->numeroContratoContexto;
                if ($numeroContratoParaVinculacion === 'sin_contrato') {
                    $numeroContratoParaVinculacion = null;
                }
                $tipoContratoParaVinculacion = $this->v_tipo_contrato_id;
                
                // Si usamos el contrato del contexto, buscar su tipo
                if (!$tipoContratoParaVinculacion && $numeroContratoParaVinculacion) {
                    $vinculacionContratista = \App\Models\ContratistaUnidadOrganizacional::where('contratista_id', $this->contratistaId)
                        ->where('unidad_organizacional_mandante_id', $this->v_unidad_organizacional_mandante_id)
                        ->where('numero_contrato', $numeroContratoParaVinculacion)
                        ->first();
                    if ($vinculacionContratista) {
                        $tipoContratoParaVinculacion = $vinculacionContratista->tipo_contrato_id;
                    }
                }
                
                if ($this->mostrarAvisoHistorial) {
                    // La limpieza ya se realizó arriba de forma global para mayor seguridad.
                }
                
                $nuevaVinculacion = TrabajadorVinculacion::create([
                    'trabajador_id' => $trabajador->id,
                    'unidad_organizacional_mandante_id' => $this->v_unidad_organizacional_mandante_id,
                    'dependencia_id' => $this->v_dependencia_id,
                    'cargo_mandante_id' => $this->cargo_mandante_id_nuevo,
                    'numero_contrato' => $numeroContratoParaVinculacion,
                    'tipo_contrato_id' => $tipoContratoParaVinculacion,
                    'fecha_ingreso_vinculacion' => now(),
                    'is_active' => true,
                ]);
                
                if (!empty($this->v_condiciones_personales_ids)) {
                    $nuevaVinculacion->condicionesPersonales()->sync($this->v_condiciones_personales_ids);
                }
                
                session()->flash('message_trabajador', 'Trabajador agregado y vinculado correctamente.');
            } else {
                session()->flash('message_trabajador', 'Ficha del trabajador actualizada correctamente.');
            }
            DB::commit();

            ActualizarEstadoRecursoIndividual::dispatch($trabajador);

            $this->cerrarModalFichaTrabajador();
            if ($this->trabajadorSeleccionado && $this->trabajadorSeleccionado->id == ($this->trabajadorId ?? $trabajador->id)) {
                $this->trabajadorSeleccionado->refresh();
            }
            $this->dispatch('recursosActualizados');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al guardar trabajador: " . $e->getMessage());
            session()->flash('error_trabajador', 'Ocurrió un error al guardar la ficha del trabajador.');
        }
    }

    public function cerrarModalFichaTrabajador()
    {
        $this->showModalFichaTrabajador = false;
        $this->resetFichaTrabajadorFields();
    }


    public function rulesVinculacion()
    {
        $rules = [
            'v_mandante_id' => 'required|exists:mandantes,id',
            'v_unidad_organizacional_mandante_id' => [
                ($this->v_dependencia_id === null || $this->v_dependencia_id === 'null') && $this->puedeEstarEnReserva ? 'nullable' : 'required',
                'exists:unidades_organizacionales_mandante,id'
            ],
            'v_dependencia_id' => 'nullable|exists:dependencias,id',
            'v_cargo_mandante_id' => 'required|exists:cargos_mandante,id',
            'v_condiciones_personales_ids'   => 'array',
            'v_condiciones_personales_ids.*' => 'exists:tipos_condicion_personal,id',
            'v_fecha_ingreso_vinculacion' => 'required|date',
            'v_fecha_contrato' => 'nullable|date|after_or_equal:v_fecha_ingreso_vinculacion',
            'v_is_active' => 'required|boolean',
            'v_fecha_desactivacion' => 'nullable|required_if:v_is_active,false|date|after_or_equal:v_fecha_ingreso_vinculacion',
            'v_motivo_desactivacion' => 'nullable|required_if:v_is_active,false|string|max:500',
        ];

        if ($this->v_dependencia_id === null && $this->puedeEstarEnReserva) {
        } else {
            $rules['v_dependencia_id'] = 'required|exists:dependencias,id';
        }

        $rules['v_unidad_organizacional_mandante_id'][] = function ($attribute, $value, $fail) {
            
            $getFamilyIds = function($cId, $mId) {
                $currentId = $cId;
                while(true) {
                    $sol = \App\Models\SolicitudVinculacion::where('contratista_id', $currentId)
                        ->where('mandante_id', $mId)->where('estado', 'APROBADA')->first();
                    if (!$sol || !$sol->contratista_padre_id) break;
                    $currentId = $sol->contratista_padre_id;
                }
                $rootId = $currentId;
                $familyIds = [$rootId];
                $lvl1 = \App\Models\SolicitudVinculacion::where('contratista_padre_id', $rootId)->where('mandante_id', $mId)->where('estado', 'APROBADA')->pluck('contratista_id')->toArray();
                $familyIds = array_merge($familyIds, $lvl1);
                if (!empty($lvl1)) {
                    $lvl2 = \App\Models\SolicitudVinculacion::whereIn('contratista_padre_id', $lvl1)->where('mandante_id', $mId)->where('estado', 'APROBADA')->pluck('contratista_id')->toArray();
                    $familyIds = array_merge($familyIds, $lvl2);
                    if (!empty($lvl2)) {
                        $lvl3 = \App\Models\SolicitudVinculacion::whereIn('contratista_padre_id', $lvl2)->where('mandante_id', $mId)->where('estado', 'APROBADA')->pluck('contratista_id')->toArray();
                        $familyIds = array_merge($familyIds, $lvl3);
                    }
                }
                return array_unique($familyIds);
            };

            if ($this->v_is_active) {
                if (!$this->trabajadorSeleccionado) {
                    $fail('No hay un trabajador seleccionado para esta validación.'); return;
                }
                $query = TrabajadorVinculacion::where('trabajador_id', $this->trabajadorSeleccionado->id)
                    ->where('unidad_organizacional_mandante_id', $value)
                    ->where('dependencia_id', $this->v_dependencia_id)
                    ->where('is_active', true);
                
                // Considerar numero_contrato: solo bloquear si el contrato también es el mismo
                if ($this->v_numero_contrato) {
                    $query->where('numero_contrato', $this->v_numero_contrato);
                } else {
                    // Si no hay contrato seleccionado, verificar que no exista otra sin contrato
                    $query->whereNull('numero_contrato');
                }
                    
                if ($this->vinculacionId) { 
                    $query->where('id', '!=', $this->vinculacionId); 
                }
                
                if ($query->exists()) { 
                    $msj = $this->v_dependencia_id ? 'UO, Lugar de Trabajo y Contrato.' : 'UO (en Reserva) y Contrato.';
                    $fail("El trabajador ya tiene una vinculación activa para esta {$msj}"); 
                    return;
                }
                
                // --- Validar Límite de Cuota ---
                $queryCuo = \App\Models\ContratistaUnidadOrganizacional::where('contratista_id', $this->contratistaId)
                    ->where('unidad_organizacional_mandante_id', $value)
                    ->where('dependencia_id', $this->v_dependencia_id);
                
                if ($this->v_numero_contrato) {
                    $queryCuo->where('numero_contrato', $this->v_numero_contrato);
                } else {
                    $queryCuo->whereNull('numero_contrato');
                }
                
                $cuo = $queryCuo->first();
                if ($cuo && !is_null($cuo->trabajadores_cuota)) {
                    $familyIds = $getFamilyIds($this->contratistaId, $this->v_mandante_id);

                    $countVinculaciones = \App\Models\TrabajadorVinculacion::whereHas('trabajador', function($q) use ($familyIds) {
                            $q->whereIn('contratista_id', $familyIds);
                        })
                        ->where('unidad_organizacional_mandante_id', $value)
                        ->where('dependencia_id', $this->v_dependencia_id)
                        ->where('is_active', true);
                    
                    if ($this->v_numero_contrato) {
                        $countVinculaciones->where('numero_contrato', $this->v_numero_contrato);
                    } else {
                        $countVinculaciones->whereNull('numero_contrato');
                    }
                    
                    if ($this->vinculacionId) {
                        $countVinculaciones->where('id', '!=', $this->vinculacionId);
                    }
                    
                    $activeCount = $countVinculaciones->count();
                    if ($activeCount >= $cuo->trabajadores_cuota) {
                        $fail("Límite superado: La cuota máxima permitida es de {$cuo->trabajadores_cuota} trabajadores en conjunto (incluye subcontratistas).");
                    }
                }

                // --- NUEVA VALIDACIÓN: Cuota por Cargo ---
                $cargoId = $this->v_cargo_mandante_id;
                if ($cuo && $cargoId) {
                    $cuoCargo = \App\Models\ContratistaUoCargo::where('contratista_uo_id', $cuo->id)
                        ->where('cargo_mandante_id', $cargoId)
                        ->first();
                        
                    if ($cuoCargo && !is_null($cuoCargo->cuota)) {
                        $familyIds = $getFamilyIds($this->contratistaId, $this->v_mandante_id);
                        $countCargo = \App\Models\TrabajadorVinculacion::whereHas('trabajador', function($q) use ($familyIds) {
                                $q->whereIn('contratista_id', $familyIds);
                            })
                            ->where('unidad_organizacional_mandante_id', $value)
                            ->where('dependencia_id', $this->v_dependencia_id)
                            ->where('cargo_mandante_id', $cargoId)
                            ->where('is_active', true);

                        if ($this->v_numero_contrato) {
                            $countCargo->where('numero_contrato', $this->v_numero_contrato);
                        } else {
                            $countCargo->whereNull('numero_contrato');
                        }
                        
                        if ($this->vinculacionId) {
                            $countCargo->where('id', '!=', $this->vinculacionId);
                        }
                            
                        if ($countCargo->count() >= $cuoCargo->cuota) {
                            $fail("Límite superado para el cargo '{$cuoCargo->cargoMandante->nombre_cargo}': La cuota máxima es de {$cuoCargo->cuota} trabajadores.");
                        }
                    }
                }
            }
        };
        return $rules;
    }

    public function updatedVMandanteId($mandanteId)
    {
        $this->unidadesOrganizacionalesDisponibles = [];
        $this->cargosMandanteDisponibles = [];
        $this->dependenciasDisponibles = [];
        $this->contratosDisponibles = [];
        $this->v_unidad_organizacional_mandante_id = null;
        $this->v_cargo_mandante_id = null;
        $this->v_dependencia_id = null;
        $this->v_numero_contrato = null;
        $this->v_tipo_contrato_id = null;
        
        $this->_cargarCondicionesPersonalesPorMandante($mandanteId);
        if ($mandanteId) {
            $this->unidadesOrganizacionalesDisponibles = UnidadOrganizacionalMandante::with('parent')->where('mandante_id', $mandanteId)->where('is_active', true)->whereHas('contratistasHabilitados', function ($query) { $query->where('contratista_id', $this->contratistaId); })->get()->sortBy('nombre_jerarquico');
            $this->cargosMandanteDisponibles = CargoMandante::where('mandante_id', $mandanteId)->where('is_active', true)->orderBy('nombre_cargo')->get();
            
            $contratista = Contratista::find($this->contratistaId);
            if ($contratista) {
                $this->dependenciasDisponibles = $contratista->dependencias()
                    ->with('parent')
                    ->where('mandante_id', $mandanteId)
                    ->where('estado', true)
                    ->get()
                    ->sortBy('nombre_jerarquico');
            }
            // Al cambiar mandante, resetear cargos filtrados (se filtrarán de nuevo al elegir UO)
            $this->cargosMandanteDisponibles = CargoMandante::where('mandante_id', $mandanteId)->where('is_active', true)->orderBy('nombre_cargo')->get();
        }
    }

    private function _cargarCondicionesPersonalesPorMandante(?int $mandanteId): void
    {
        if (!$mandanteId) {
            $this->tiposCondicionPersonal = [];
            return;
        }

        // Carga SOLO las condiciones del mandante seleccionado (filtrado estricto)
        $this->tiposCondicionPersonal = TipoCondicionPersonal::where('is_active', true)
            ->where('mandante_id', $mandanteId)
            ->orderBy('nombre')
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre])
            ->values()
            ->toArray();
    }

    public function updatedVDependenciaId($value)
    {
        $depId = ($value === 'null' || $value === '') ? null : (int)$value;
        
        // Al cambiar de lugar de trabajo, re-filtrar contratos disponibles
        if ($this->v_unidad_organizacional_mandante_id && $this->contratistaId) {
            $this->actualizarContratosDisponibles($this->v_unidad_organizacional_mandante_id, $depId);
        }

        // Al cambiar de lugar de trabajo, re-filtrar cargos si ya hay UO seleccionada
        $mandanteContexto = $this->v_mandante_id ?? $this->mandanteId;
        if ($mandanteContexto && $this->v_unidad_organizacional_mandante_id) {
            $this->actualizarCargosDisponibles($mandanteContexto, $this->v_unidad_organizacional_mandante_id, $depId, $this->v_numero_contrato);
        }
    }

    /**
     * Cuando se selecciona una UO, cargar los contratos disponibles de la contratista para esa UO y el Lugar de Trabajo (si está seleccionado)
     */
    public function updatedVUnidadOrganizacionalMandanteId($uoId)
    {
        $this->actualizarContratosDisponibles($uoId, $this->v_dependencia_id);
    }

    private function actualizarContratosDisponibles($uoId, $dependenciaId)
    {
        $this->contratosDisponibles = [];
        // No resetear v_numero_contrato si ya tenemos uno (ej: al cargar el modal de edición)
        // Pero sí resetearlo si estamos cambiando la UO activamente
        
        if ($uoId && $this->contratistaId) {
            $depId = ($dependenciaId === 'null' || $dependenciaId === '') ? null : (int)$dependenciaId;

            $query = \App\Models\ContratistaUnidadOrganizacional::where('contratista_id', $this->contratistaId)
                ->where('unidad_organizacional_mandante_id', $uoId)
                ->whereNotNull('numero_contrato')
                ->where(function ($q) use ($depId) {
                    $q->whereNull('dependencia_id');
                    if ($depId) {
                        $q->orWhere('dependencia_id', $depId);
                    }
                });
                
            $vinculacionesContratista = $query->with('tipoContrato')->get();
            
            $this->contratosDisponibles = $vinculacionesContratista->map(function ($v) {
                return [
                    'numero_contrato' => $v->numero_contrato,
                    'tipo_contrato_id' => $v->tipo_contrato_id,
                    'tipo_contrato_nombre' => $v->tipoContrato?->nombre ?? 'N/A',
                ];
            })->unique('numero_contrato')->values()->toArray();

            // Refrescar cargos filtrados para esta UO/Lugar
            $mandanteContexto = $this->v_mandante_id ?? $this->mandanteId;
            $this->actualizarCargosDisponibles($mandanteContexto, $uoId, $depId, $this->v_numero_contrato);
        }
    }

    /**
     * Cuando se selecciona un número de contrato, auto-llenar el tipo de contrato
     */
    public function updatedVNumeroContrato($numeroContrato)
    {
        $this->v_tipo_contrato_id = null;
        
        if ($numeroContrato && !empty($this->contratosDisponibles)) {
            foreach ($this->contratosDisponibles as $contrato) {
                if ($contrato['numero_contrato'] === $numeroContrato) {
                    $this->v_tipo_contrato_id = $contrato['tipo_contrato_id'];
                    break;
                }
            }
            // Re-filtrar si el contrato cambia (podría haber cuotas por contrato/cargo en el futuro, 
            // aunque actualmente la configuración de cargos es por Vinculacion (UO+Lugar+Contrato))
            $mandanteContexto = $this->v_mandante_id ?? $this->mandanteId;
            $this->actualizarCargosDisponibles($mandanteContexto, $this->v_unidad_organizacional_mandante_id, $this->v_dependencia_id, $numeroContrato);
        }
    }

    /**
     * Filtra los cargos disponibles basados en la configuración de la vinculación (contratista_uo_cargos).
     * Si no hay configuración, muestra todos los cargos del mandante.
     */
    private function actualizarCargosDisponibles($mandanteId, $uoId, $dependenciaId, $numeroContrato = null)
    {
        if (!$mandanteId) {
            $this->cargosMandanteDisponibles = [];
            return;
        }

        // Si es Reserva (UO y Lugar nulos), mostrar todos los cargos del mandante 
        // (o podríamos restringir también, pero usualmente Reserva permite todo)
        if (!$uoId && (!$dependenciaId || $dependenciaId === 'null')) {
            $this->cargosMandanteDisponibles = CargoMandante::where('mandante_id', $mandanteId)
                ->where('is_active', true)->orderBy('nombre_cargo')->get();
            return;
        }

        $depId = ($dependenciaId === 'null' || $dependenciaId === '') ? null : (int)$dependenciaId;
        $numContrato = ($numeroContrato === 'sin_contrato') ? null : $numeroContrato;

        $queryCuo = \App\Models\ContratistaUnidadOrganizacional::where('contratista_id', $this->contratistaId)
            ->where('unidad_organizacional_mandante_id', $uoId)
            ->where('dependencia_id', $depId);
            
        if ($numContrato) {
            $queryCuo->where('numero_contrato', $numContrato);
        } else {
            $queryCuo->whereNull('numero_contrato');
        }
        
        $cuo = $queryCuo->first();
        
        if ($cuo && $cuo->cargosConfigurados()->exists()) {
            $cargoIds = $cuo->cargosConfigurados->pluck('cargo_mandante_id');
            $this->cargosMandanteDisponibles = CargoMandante::whereIn('id', $cargoIds)
                ->where('is_active', true)
                ->orderBy('nombre_cargo')
                ->get();
        } else {
            // Default: todos los cargos del mandante
            $this->cargosMandanteDisponibles = CargoMandante::where('mandante_id', $mandanteId)
                ->where('is_active', true)
                ->orderBy('nombre_cargo')
                ->get();
        }
    }

    private function resetVinculacionFields()
    {
        $this->vinculacionId = null;
        $this->v_mandante_id = null;
        $this->v_unidad_organizacional_mandante_id = null;
        $this->v_cargo_mandante_id = null;
        $this->v_dependencia_id = null;
        $this->v_numero_contrato = null;
        $this->v_tipo_contrato_id = null;
        $this->v_condiciones_personales_ids = [];
        $this->v_fecha_ingreso_vinculacion = null;
        $this->v_fecha_contrato = null;
        $this->v_is_active = true;
        $this->v_fecha_desactivacion = null;
        $this->v_motivo_desactivacion = null;
        $this->unidadesOrganizacionalesDisponibles = [];
        $this->cargosMandanteDisponibles = [];
        $this->dependenciasDisponibles = [];
        $this->contratosDisponibles = [];
        $this->resetValidation();
    }

    public function abrirModalNuevaVinculacion()
    {
        if (!$this->trabajadorSeleccionado) { session()->flash('error_vinculacion', 'Debe seleccionar un trabajador para agregar una vinculación.'); return; }
        $this->resetVinculacionFields();
        if ($this->mandanteId) {
            $this->v_mandante_id = $this->mandanteId;
            $this->updatedVMandanteId($this->v_mandante_id);
        } else {
            $this->v_mandante_id = null;
        }
        
        $this->v_fecha_ingreso_vinculacion = now()->format('Y-m-d');
        $this->showModalNuevaVinculacion = true;
    }

    public function abrirModalEditarVinculacion($id)
    {
        $vinculacion = TrabajadorVinculacion::with('unidadOrganizacionalMandante.mandante')->find($id);
        if ($vinculacion && $vinculacion->trabajador_id == $this->trabajadorSeleccionado?->id) {
            // BLOQUEO: NO EDITAR VINCULACIONES FINIQUITADAS
            if (!$vinculacion->is_active && in_array($vinculacion->motivo_desactivacion, ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD'])) {
                $this->showErrorFiniquitoModal = true;
                return;
            }
            $this->vinculacionId = $vinculacion->id;
            $this->v_mandante_id = $vinculacion->unidadOrganizacionalMandante?->mandante_id;
            $this->updatedVMandanteId($this->v_mandante_id);
            $this->v_unidad_organizacional_mandante_id = $vinculacion->unidad_organizacional_mandante_id;
            
            // Cargar contratos disponibles para esta UO (sin resetear los valores actuales)
            if ($this->v_unidad_organizacional_mandante_id && $this->contratistaId) {
                $vinculacionesContratista = \App\Models\ContratistaUnidadOrganizacional::where('contratista_id', $this->contratistaId)
                    ->where('unidad_organizacional_mandante_id', $this->v_unidad_organizacional_mandante_id)
                    ->whereNotNull('numero_contrato')
                    ->with('tipoContrato')
                    ->get();
                
                $this->contratosDisponibles = $vinculacionesContratista->map(function ($v) {
                    return [
                        'numero_contrato' => $v->numero_contrato,
                        'tipo_contrato_id' => $v->tipo_contrato_id,
                        'tipo_contrato_nombre' => $v->tipoContrato?->nombre ?? 'N/A',
                    ];
                })->unique('numero_contrato')->values()->toArray();
            }
            
            $this->v_dependencia_id = $vinculacion->dependencia_id;
            $this->v_numero_contrato = $vinculacion->numero_contrato;
            $this->v_tipo_contrato_id = $vinculacion->tipo_contrato_id;
            $this->v_cargo_mandante_id = $vinculacion->cargo_mandante_id;
            $this->v_condiciones_personales_ids = $vinculacion->condicionesPersonales()->pluck('tipo_condicion_personal_id')->map(fn($i)=>(int)$i)->toArray();
            $this->v_fecha_ingreso_vinculacion = $vinculacion->fecha_ingreso_vinculacion->format('Y-m-d');
            $this->v_fecha_contrato = $vinculacion->fecha_contrato ? $vinculacion->fecha_contrato->format('Y-m-d') : null;
            $this->v_is_active = $vinculacion->is_active;
            $this->v_fecha_desactivacion = $vinculacion->fecha_desactivacion ? $vinculacion->fecha_desactivacion->format('Y-m-d') : null;
            $this->v_motivo_desactivacion = $vinculacion->motivo_desactivacion;
            
            $this->puedeEstarEnReserva = $this->trabajadorSeleccionado->vinculaciones()->count() === 1;

            $this->showModalNuevaVinculacion = true;
        } else {
            session()->flash('error_vinculacion', 'Vinculación no encontrada o no pertenece al trabajador seleccionado.');
        }
    }

    public function guardarVinculacion()
    {
        if (!$this->trabajadorSeleccionado) { session()->flash('error_vinculacion', 'No se ha seleccionado un trabajador.'); return; }
        
        if (!$this->v_mandante_id) {
            session()->flash('error_vinculacion', 'El Mandante no está definido en la operación actual.');
            return;
        }
        
        if ($this->v_dependencia_id === 'null' || $this->v_dependencia_id === null) {
            $this->v_dependencia_id = null;
            $this->v_unidad_organizacional_mandante_id = null;
            $this->v_numero_contrato = null;
            $this->v_tipo_contrato_id = null;
        }

        $validatedData = $this->validate($this->rulesVinculacion());

        // ── BLOQUEO: NO REACTIVAR VINCULACIONES FINIQUITADAS ──
        if ($this->vinculacionId && $validatedData['v_is_active']) {
            $vincExitente = TrabajadorVinculacion::find($this->vinculacionId);
            if ($vincExitente && !$vincExitente->is_active && in_array($vincExitente->motivo_desactivacion, ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD'])) {
                session()->flash('error_vinculacion', 'No puede activar una vinculación que está FINIQUITADA. Para corregir un error, revierta el estado en el periodo de envío respectivo. Para un reingreso, cree una NUEVA vinculación.');
                return;
            }
        }

        if ($validatedData['v_is_active']) { $validatedData['v_fecha_desactivacion'] = null; $validatedData['v_motivo_desactivacion'] = null; }
        $dataToSave = [
            'trabajador_id' => $this->trabajadorSeleccionado->id,
            'unidad_organizacional_mandante_id' => $this->v_unidad_organizacional_mandante_id,
            'dependencia_id' => $this->v_dependencia_id,
            'numero_contrato' => ($this->v_numero_contrato === 'sin_contrato' || empty($this->v_numero_contrato)) ? null : $this->v_numero_contrato,
            'tipo_contrato_id' => $this->v_tipo_contrato_id ?: null,
            'cargo_mandante_id' => $validatedData['v_cargo_mandante_id'],
            'fecha_ingreso_vinculacion' => $validatedData['v_fecha_ingreso_vinculacion'],
            'fecha_contrato' => $validatedData['v_fecha_contrato'],
            'is_active' => $validatedData['v_is_active'],
            'fecha_desactivacion' => $validatedData['v_fecha_desactivacion'],
            'motivo_desactivacion' => $validatedData['v_motivo_desactivacion'],
        ];
        try {
            $vinculacionGuardada = TrabajadorVinculacion::updateOrCreate(['id' => $this->vinculacionId], $dataToSave);

            // Sincronizar condiciones personales (multi-condición]
            $condIds = array_filter(array_map('intval', $this->v_condiciones_personales_ids ?? []));
            $vinculacionGuardada->condicionesPersonales()->sync($condIds);

            ActualizarEstadoRecursoIndividual::dispatch($this->trabajadorSeleccionado);

            session()->flash('message_vinculacion', $this->vinculacionId ? 'Vinculación actualizada correctamente.' : 'Vinculación creada correctamente.');
            $this->cerrarModalVinculacion();
            $this->dispatch('recursosActualizados');
        } catch (\Exception $e) {
            Log::error("Error al guardar vinculación: " . $e->getMessage());
            session()->flash('error_vinculacion', 'Ocurrió un error al guardar la vinculación.');
        }
    }

    public function cerrarModalVinculacion()
    {
        $this->showModalNuevaVinculacion = false;
        $this->resetVinculacionFields();
    }

    public function toggleActivoVinculacion(TrabajadorVinculacion $vinculacion)
    {
        if ($vinculacion && $vinculacion->trabajador_id == $this->trabajadorSeleccionado?->id) {
            if ($vinculacion->is_active) {
                // Now we open the modal instead of toggling directly for deactivation
                $this->abrirModalDesactivacion($vinculacion->id, 'baja');
            } else {
                if (in_array($vinculacion->motivo_desactivacion, ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD'])) {
                    $this->showErrorFiniquitoModal = true;
                    return;
                }

                $existeOtraActivaEnMismaUO = TrabajadorVinculacion::where('trabajador_id', $vinculacion->trabajador_id)
                    ->where('unidad_organizacional_mandante_id', $vinculacion->unidad_organizacional_mandante_id)
                    ->where('dependencia_id', $vinculacion->dependencia_id)
                    ->where('is_active', true)
                    ->where('id', '!=', $vinculacion->id)->exists();
                if ($existeOtraActivaEnMismaUO) { session()->flash('error_vinculacion', 'No se puede activar. El trabajador ya tiene otra vinculación activa en esta UO y Lugar de Trabajo.'); return; }
                $vinculacion->is_active = true;
                $vinculacion->fecha_desactivacion = null;
                $vinculacion->fecha_finiquito = null;
                $vinculacion->motivo_desactivacion = null;
                $vinculacion->save();
                
                ActualizarEstadoRecursoIndividual::dispatch($vinculacion->trabajador);

                session()->flash('message_vinculacion', 'Estado de la vinculación reactivado.');
                $this->dispatch('recursosActualizados');
            }
        }
    }

    public function reactivarVinculacion($id)
    {
        $vinculacion = TrabajadorVinculacion::find($id);
        if ($vinculacion && ($vinculacion->trabajador_id == $this->trabajadorSeleccionado?->id || !$this->trabajadorSeleccionado)) {
            
            if (!$this->trabajadorSeleccionado) {
                $this->trabajadorSeleccionado = $vinculacion->trabajador;
            }

            // --- PROTECCIÓN CONTRA ANARQUÍA EN RESERVA CONSOLIDADA ---
            if (is_null($vinculacion->dependencia_id)) {
                $this->vistaActual = 'listado_vinculaciones';
                $this->resetPage('vinculacionesPage');
                session()->flash('message_vinculacion', '⚠️ Este trabajador está en Reserva Consolidada. Para reactivarlo, debe crear una nueva asignación completa.');
                return;
            }

            $motivoOriginal = $vinculacion->motivo_desactivacion;
            $esGlobal = in_array($motivoOriginal, ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD']);

            // Si es un motivo GLOBAL, reactivamos TODAS las vinculaciones que tengan ese motivo
            if ($esGlobal) {
                $vinculacionesAReactivar = TrabajadorVinculacion::where('trabajador_id', $vinculacion->trabajador_id)
                    ->where('is_active', false)
                    ->whereIn('motivo_desactivacion', ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD'])
                    ->get();
            } else {
                // Si es LOCAL (Presente en otra), solo reactivamos la seleccionada
                $vinculacionesAReactivar = collect([$vinculacion]);
            }

            $count = 0;
            foreach ($vinculacionesAReactivar as $v) {
                // Validar que no exista otra vinculación ACTIVA exactamente igual (UO + Lugar)
                $existeOtraActivaEnMismaUO = TrabajadorVinculacion::where('trabajador_id', $v->trabajador_id)
                    ->where('unidad_organizacional_mandante_id', $v->unidad_organizacional_mandante_id)
                    ->where('dependencia_id', $v->dependencia_id)
                    ->where('is_active', true)
                    ->where('id', '!=', $v->id)->exists();

                if ($existeOtraActivaEnMismaUO) continue; // Saltar si hay conflicto, pero seguir con las otras

                $v->is_active = true;
                $v->fecha_desactivacion = null;
                $v->fecha_finiquito = null;
                $v->motivo_desactivacion = null;
                $v->save();
                
                $this->sincronizarEstadoEnNominasAbiertas($v);
                $count++;
            }
            
            ActualizarEstadoRecursoIndividual::dispatch($this->trabajadorSeleccionado);
            $this->dispatch('recursosActualizados');
            
            $msg = $esGlobal 
                ? "Reactivación global exitosa ($count contratos). El trabajador vuelve a estar ACTIVO en la empresa." 
                : "Vinculación reactivada con éxito.";
                
            session()->flash('message_vinculacion', $msg);
        }
    }

    public function abrirModalDesactivacion($id, $context = 'FINIQUITADO')
    {
        $this->vinculacionADesactivar = $id;
        $vinc = TrabajadorVinculacion::with('trabajador')->find($id);
        if ($vinc) {
            $this->trabajadorSeleccionado = $vinc->trabajador;
        }

        $this->desactivacionContext = $context;
        $this->vinculacion_correcta_id = '';
        $this->v_fecha_finiquito = now()->format('Y-m-d');
        $this->showModalDesactivacion = true;
    }

    public function procesarDesactivacion()
    {
        $vinculacion = TrabajadorVinculacion::find($this->vinculacionADesactivar);
        if (!$vinculacion || $vinculacion->trabajador_id != $this->trabajadorSeleccionado?->id) return;

        if ($this->desactivacionContext === 'PRESENTE_EN_OTRA_VINCULACION') {
            $this->validate(['vinculacion_correcta_id' => 'required']);
            
            $vinculacion->is_active = false;
            $vinculacion->fecha_desactivacion = now();
            // Desactiva sin fecha_finiquito ya que es por duplicidad.
            $vinculacion->motivo_desactivacion = 'PRESENTE EN OTRA VINCULACIÓN: ID ' . $this->vinculacion_correcta_id;
            
            // DESVINCULACIÓN TOTAL: Limpiamos Lugar, UO y Contrato para que quede en RESERVA real
            $vinculacion->unidad_organizacional_mandante_id = null;
            $vinculacion->dependencia_id = null;
            $vinculacion->numero_contrato = null;
            $vinculacion->is_active = false;
            
            $vinculacion->save();
            session()->flash('message_vinculacion', 'Vinculación anulada por estar presente en otra activa.');
            
        } else {
            // FINIQUITADO, CESACION_PRINCIPAL, RECONOCIMIENTO_ANTIGUEDAD
            $this->validate(['v_fecha_finiquito' => 'required|date']);
            $fechaFiniquito = $this->v_fecha_finiquito;

            // Al ser Finiquito, se deben desactivar/actualizar TODAS las vinculaciones del trabajador
            $todasLasVinculaciones = TrabajadorVinculacion::where('trabajador_id', $vinculacion->trabajador_id)->get();

            foreach ($todasLasVinculaciones as $v) {
                // REGLA ANTI-ANARQUÍA: Solo limpiar UO/Lugar/Contrato si NO hay un período de nómina
                // abierto (no EMITIDO) con este trabajador. Si hay nómina abierta, consolidarReserva()
                // hará la limpieza al enviar. Si limpiamos ahora, impedimos la reversión.
                $tieneNominaAbierta = \App\Models\CarpetaVerificacionTrabajador::where('trabajador_vinculacion_id', $v->id)
                    ->whereHas('carpeta', function ($q) {
                        $q->where(function ($sq) {
                            $sq->whereNull('estado_revision')
                               ->orWhere('estado_revision', '!=', 'EMITIDO');
                        });
                    })
                    ->exists();

                $updateData = [
                    'is_active'            => false,
                    'fecha_desactivacion'  => now(),
                    'fecha_finiquito'      => $fechaFiniquito,
                    'motivo_desactivacion' => $this->desactivacionContext,
                ];

                // Solo purgar ubicación si NO hay nómina abierta pendiente de envío
                if (!$tieneNominaAbierta) {
                    $updateData['unidad_organizacional_mandante_id'] = null;
                    $updateData['dependencia_id']                    = null;
                    $updateData['numero_contrato']                   = null;
                }

                $v->update($updateData);
                $this->sincronizarEstadoEnNominasAbiertas($v);
            }

            session()->flash('message_vinculacion', 'Finiquito procesado globalmente. Se ha consolidado la ficha del trabajador en Reserva.');
        }

        $this->sincronizarEstadoEnNominasAbiertas($vinculacion);

        $this->showModalDesactivacion = false;
        ActualizarEstadoRecursoIndividual::dispatch($vinculacion->trabajador);
        $this->dispatch('recursosActualizados');
    }

    /**
     * Sincroniza el estado del maestro hacia las nóminas (carpetas) ya inicializadas
     * que aún no han sido certificadas/auditadas (no EMITIDO).
     */
    private function sincronizarEstadoEnNominasAbiertas(TrabajadorVinculacion $vinculacion)
    {
        // Buscar todos los registros de este trabajador en carpetas existentes
        $registrosEnNominas = \App\Models\CarpetaVerificacionTrabajador::where('trabajador_vinculacion_id', $vinculacion->id)
            ->whereHas('carpeta', function($q) {
                // Solo carpetas que no estén finalizadas/emitidas
                $q->where(function($sq) {
                    $sq->whereNull('estado_revision')
                       ->orWhere('estado_revision', '!=', 'EMITIDO');
                });
            })
            ->get();

        foreach ($registrosEnNominas as $reg) {
            $carpetaDate = \Carbon\Carbon::create($reg->carpeta->anio, $reg->carpeta->mes, 1)->startOfMonth();
            
            // Si estamos REACTIVANDO (is_active = true), no aplicamos escudo temporal 
            // para permitir limpiar estados de desvinculación antiguos en cualquier carpeta abierta.
            if (!$vinculacion->is_active) {
                $referenciaDate = $vinculacion->fecha_finiquito ?: $vinculacion->fecha_desactivacion ?: now();
                $refCarbon = \Carbon\Carbon::parse($referenciaDate);
                
                $refId = ($refCarbon->year * 100) + $refCarbon->month;
                $folderId = ($reg->carpeta->anio * 100) + $reg->carpeta->mes;

                // Si la carpeta es de un periodo ANTERIOR al movimiento, protegemos.
                if ($folderId < $refId) {
                    continue;
                }
            }

            $nuevoEstado = 'PENDIENTE';
            
            if (!$vinculacion->is_active) {
                if (in_array($vinculacion->motivo_desactivacion, ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD'])) {
                    $nuevoEstado = $vinculacion->motivo_desactivacion;
                } elseif (str_contains($vinculacion->motivo_desactivacion ?? '', 'PRESENTE EN OTRA')) {
                    $nuevoEstado = 'PRESENTE_OTRA_VINCULACION';
                    
                    // Si el motivo del maestro tiene un ID de destino, intentar extraerlo y guardarlo
                    if (preg_match('/ID (\d+)/', $vinculacion->motivo_desactivacion, $matches)) {
                        $reg->destino_trabajador_vinculacion_id = $matches[1];
                    }
                }
            }

            $reg->estado_revision = $nuevoEstado;
            $reg->save();
        }
    }

    public function cerrarModalDesactivacion()
    {
        $this->showModalDesactivacion = false;
        $this->v_fecha_finiquito = null;
    }

    /**
     * Revertir finiquito desde el Maestro, ANTES de enviar el período.
     * Solo es posible si el trabajador tiene un CarpetaVerificacionTrabajador
     * con estado FINIQUITADO/CESACION/RECONOCIMIENTO en una carpeta NO EMITIDA.
     * Restaura el trabajador a su estado anterior (Activo, con UO/Lugar/Contrato).
     */
    public function revertirFiniquitoMaestro($vinculacionId)
    {
        $vinculacion = TrabajadorVinculacion::with('trabajador')->find($vinculacionId);
        if (!$vinculacion) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Vinculación no encontrada.']);
            return;
        }

        // Verificar que pertenece al contratista actual
        if ($vinculacion->trabajador->contratista_id !== $this->contratistaId) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Acción no permitida.']);
            return;
        }

        // ── CORRECCIÓN CRÍTICA ─────────────────────────────────────────────────────────
        // El botón actúa sobre la vinculación de menor ID (consolidada en Reserva),
        // pero el registro FINIQUITADO en CarpetaVerificacionTrabajador puede estar
        // ligado a CUALQUIER vinculación del mismo trabajador (ej: la de contrato 4000
        // cuando se muestra la de contrato 3000). Por eso buscamos en TODAS las vinculaciones.
        // ──────────────────────────────────────────────────────────────────────────────────
        $trabajadorId   = $vinculacion->trabajador_id;
        $vinculacionIds = TrabajadorVinculacion::where('trabajador_id', $trabajadorId)->pluck('id');

        // ── VERIFICAR SI YA FUE ENVIADO EN ALGUNA CARPETA ──────────────────────────────
        $bloqueadoPorEnvio = \App\Models\CarpetaVerificacionTrabajador::whereIn('trabajador_vinculacion_id', $vinculacionIds)
            ->whereIn('estado_revision', ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD'])
            ->whereHas('carpeta', function ($q) {
                $q->where('estado', 'ENVIADO'); // Si está enviado, no se puede revertir
            })
            ->exists();

        if ($bloqueadoPorEnvio) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'El finiquito de este trabajador ya fue enviado en un período cerrado y no puede ser revertido. Comuníquese con ASEM si requiere modificaciones.',
            ]);
            return;
        }

        $registroNomina = \App\Models\CarpetaVerificacionTrabajador::whereIn('trabajador_vinculacion_id', $vinculacionIds)
            ->whereIn('estado_revision', ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD'])
            ->whereHas('carpeta', function ($q) {
                $q->where(function ($sq) {
                    $sq->whereNull('estado_revision')
                       ->orWhere('estado_revision', '!=', 'EMITIDO');
                });
            })
            ->first();

        if (!$registroNomina) {
            $this->dispatch('notify', [
                'type'    => 'error',
                'message' => 'No hay período pendiente de envío que permita revertir este finiquito.',
            ]);
            return;
        }

        // Reactivar TODAS las vinculaciones del trabajador con motivo de finiquito
        TrabajadorVinculacion::where('trabajador_id', $trabajadorId)
            ->where('is_active', false)
            ->whereIn('motivo_desactivacion', ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD'])
            ->update([
                'is_active'            => true,
                'fecha_desactivacion'  => null,
                'fecha_finiquito'      => null,
                'motivo_desactivacion' => null,
            ]);

        // Revertir el estado → PENDIENTE en TODAS las nóminas abiertas del mismo período
        $carpetasSamePeriod = \App\Models\CarpetaVerificacion::where('anio', $registroNomina->carpeta->anio)
            ->where('mes', $registroNomina->carpeta->mes)
            ->where(function ($q) {
                $q->whereNull('estado_revision')
                  ->orWhere('estado_revision', '!=', 'EMITIDO');
            })
            ->pluck('id');

        \App\Models\CarpetaVerificacionTrabajador::whereIn('trabajador_vinculacion_id', $vinculacionIds)
            ->whereIn('carpeta_verificacion_id', $carpetasSamePeriod)
            ->whereIn('estado_revision', ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD'])
            ->update(['estado_revision' => 'PENDIENTE']);

        // ── LIMPIEZA ANTI-FANTASMA ─────────────────────────────────────────────────
        // Después de reactivar, pueden quedar registros "ancla" (dependencia_id=null,
        // unidad_organizacional_mandante_id=null) creados por consolidarReserva en
        // procesos anteriores. Si el trabajador ya tiene vinculaciones activas CON
        // ubicación real, estos anclas son redundantes y deben eliminarse.
        // ────────────────────────────────────────────────────────────────────────────
        $tieneVinculacionesRealesActivas = TrabajadorVinculacion::where('trabajador_id', $trabajadorId)
            ->where('is_active', true)
            ->whereNotNull('dependencia_id')
            ->exists();

        if ($tieneVinculacionesRealesActivas) {
            TrabajadorVinculacion::where('trabajador_id', $trabajadorId)
                ->whereNull('dependencia_id')
                ->whereNull('unidad_organizacional_mandante_id')
                ->delete();
        }

        if (!$this->trabajadorSeleccionado) {
            $this->trabajadorSeleccionado = $vinculacion->trabajador;
        }

        ActualizarEstadoRecursoIndividual::dispatch($vinculacion->trabajador);
        $this->dispatch('recursosActualizados');
        $this->dispatch('notify', [
            'type'    => 'success',
            'message' => '✅ Finiquito revertido correctamente. El trabajador vuelve a estar ACTIVO.',
        ]);
    }

    public function abrirModalCargaDocumentos(int $trabajadorId, int $mandanteId, int $unidadOrganizacionalId, string $contexto, int $vinculacionId)
    {
        if (!$unidadOrganizacionalId) {
            session()->flash('error_trabajador', 'Por favor, asegúrese de que el contexto de la Unidad Organizacional esté seleccionado para operar.');
            return;
        }

        $this->dispatch('abrirModalDocumentos', 
            recursoId: $trabajadorId, 
            recursoType: Trabajador::class,
            mandanteId: $mandanteId,
            unidadOrganizacionalId: $unidadOrganizacionalId,
            contexto: $contexto,
            vinculacionId: $vinculacionId
        );
    }

    public function render()
    {
        $vinculacionesPaginadas = collect();
        $totalTrabajadoresUnicos = 0;
        $totalAsignaciones = 0;

        if ($this->vistaActual === 'listado_trabajadores' && $this->contratistaId) {
            
            $baseQuery = TrabajadorVinculacion::query()
                ->whereHas('trabajador', function ($query) {
                    $query->withTrashed();
                    $query->where('contratista_id', $this->contratistaId);

                    $query->when($this->filtroEstado === 'activos', fn($q) => $q->whereNull('deleted_at'));

                    $query->when($this->searchTrabajador, function ($q) {
                        $q->where(function ($subQ) {
                            $subQ->where('rut', 'like', '%' . $this->searchTrabajador . '%')
                                 ->orWhere('nombres', 'like', '%' . $this->searchTrabajador . '%')
                                 ->orWhere('apellido_paterno', 'like', '%' . $this->searchTrabajador . '%')
                                 ->orWhere('apellido_materno', 'like', '%' . $this->searchTrabajador . '%');
                        });
                    });
                });

            // Filtro EN RESERVA (FINIQUITADO): mostrar solo trabajadores con vinculaciones finiquitadas
            $esCualquierReserva = ($this->filtroEstado === 'en_reserva' || $this->lugarDeTrabajoId === 'in_reserve');

            if ($this->filtroEstado === 'en_reserva') {
                $baseQuery->whereIn('motivo_desactivacion', ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD']);
            }

            if ($esCualquierReserva) {
                // CONSOLIDACIÓN VISUAL TOTAL EN VISTA DE RESERVA
                // Solo mostramos un registro por trabajador, y solo de aquellos que no tengan NADA activo.
                $baseQuery->whereIn('trabajador_vinculaciones.id', function($q) {
                    $q->selectRaw('MIN(id)')
                      ->from('trabajador_vinculaciones')
                      ->where('is_active', false)
                      ->whereNotExists(function($notEx) {
                          $notEx->selectRaw(1)
                                ->from('trabajador_vinculaciones as tv_active')
                                ->whereColumn('tv_active.trabajador_id', 'trabajador_vinculaciones.trabajador_id')
                                ->where('tv_active.is_active', true);
                      })
                      ->groupBy('trabajador_id');
                });
            } elseif ($this->filtroEstado === 'todos' || $this->filtroEstado === 'inactivos') {
                // DASHBOARD GLOBAL DINÁMICO:
                // 1. Mostrar TODAS las vinculaciones activas.
                // 2. Mostrar la RESERVA solo si el trabajador no tiene vinculaciones activas.
                $baseQuery->where(function($q) {
                    $q->where('trabajador_vinculaciones.is_active', true)
                      ->orWhere(function($subq) {
                          $subq->where('trabajador_vinculaciones.is_active', false)
                               ->whereIn('trabajador_vinculaciones.id', function($minQ) {
                                    $minQ->selectRaw('MIN(id)')
                                         ->from('trabajador_vinculaciones')
                                         ->where('is_active', false)
                                         ->groupBy('trabajador_id');
                               })
                               ->whereNotExists(function($notEx) {
                                    $notEx->selectRaw(1)
                                          ->from('trabajador_vinculaciones as tv_active_todos')
                                          ->whereColumn('tv_active_todos.trabajador_id', 'trabajador_vinculaciones.trabajador_id')
                                          ->where('tv_active_todos.is_active', true);
                               });
                      });
                });
            }

            // En la vista ACTIVOS, mostrar solo los que tienen is_active = true
            if ($this->filtroEstado === 'activos') {
                $baseQuery->where('trabajador_vinculaciones.is_active', true);
            }


            if ($this->lugarDeTrabajoId === 'orphaned') {
                $contratista = Contratista::find($this->contratistaId);
                $idsDependenciasAsignadas = $contratista->dependencias()->pluck('dependencias.id')->toArray();
                $idsUOsAsignadas = $contratista->unidadesOrganizacionalesMandante()->pluck('unidades_organizacionales_mandante.id')->toArray();
                
                $baseQuery->where(function ($q) use ($idsDependenciasAsignadas, $idsUOsAsignadas) {
                    $q->where(function ($sq) use ($idsDependenciasAsignadas) {
                        $sq->whereNotNull('trabajador_vinculaciones.dependencia_id')
                           ->whereNotIn('trabajador_vinculaciones.dependencia_id', $idsDependenciasAsignadas);
                    })->orWhereNotIn('trabajador_vinculaciones.unidad_organizacional_mandante_id', $idsUOsAsignadas);
                });
                
                
                if ($this->mandanteId) {
                    $baseQuery->where(function($q) {
                        $q->whereHas('unidadOrganizacionalMandante', fn($sq) => $sq->where('mandante_id', $this->mandanteId))
                          ->orWhereHas('cargoMandante', fn($sq) => $sq->where('mandante_id', $this->mandanteId));
                    });
                }
            } elseif ($this->lugarDeTrabajoId === 'in_reserve') {
                $baseQuery->whereNull('dependencia_id');
                if ($this->mandanteId) {
                    $baseQuery->where(function($q) {
                        $q->whereHas('unidadOrganizacionalMandante', fn($sq) => $sq->where('mandante_id', $this->mandanteId))
                          ->orWhereHas('cargoMandante', fn($sq) => $sq->where('mandante_id', $this->mandanteId));
                    });
                }
            } else {
                if ($this->lugarDeTrabajoId) {
                    $baseQuery->where('dependencia_id', $this->lugarDeTrabajoId);
                } else {
                    $contratista = Contratista::find($this->contratistaId);
                    $idsDependenciasValidas = $contratista->dependencias()->pluck('dependencias.id')->toArray();

                    // SI el usuario filtra por ESTADO = TODOS o RESERVA, incluimos los consolidados (dependencia null)
                    if ($this->filtroEstado === 'todos' || $this->filtroEstado === 'en_reserva') {
                        $baseQuery->where(function($q) use ($idsDependenciasValidas) {
                            $q->whereIn('dependencia_id', $idsDependenciasValidas)
                              ->orWhereNull('dependencia_id');
                        });

                        // Para los consolidados (null), bajamos el escudo del Mandante
                        // pero para los asignados, mantenemos el filtro si existe.
                        if ($this->mandanteId) {
                            $baseQuery->where(function($q) {
                                $q->whereHas('unidadOrganizacionalMandante', fn($sq) => $sq->where('mandante_id', $this->mandanteId))
                                  ->orWhereNull('unidad_organizacional_mandante_id'); // Reserva
                            });
                        }
                    } else {
                        // Restricción estándar (Activos/Inactivos locales)
                        $baseQuery->whereIn('dependencia_id', $idsDependenciasValidas);
                        if ($this->mandanteId) {
                             $baseQuery->whereHas('unidadOrganizacionalMandante', fn($q) => $q->where('mandante_id', $this->mandanteId));
                        }
                    }
                }
            }

            if ($this->unidadOrganizacionalId) {
                $baseQuery->where('unidad_organizacional_mandante_id', $this->unidadOrganizacionalId);
            }

            // Filtros por número de contrato y tipo de contrato
            if (!empty($this->filtroNumeroContrato)) {
                if ($this->filtroNumeroContrato === 'sin_contrato') {
                    $baseQuery->whereNull('numero_contrato');
                } else {
                    $baseQuery->where('numero_contrato', 'like', '%' . $this->filtroNumeroContrato . '%');
                }
            }
            
            if ($this->filtroTipoContratoId) {
                $baseQuery->where('tipo_contrato_id', $this->filtroTipoContratoId);
            }

            $totalAsignaciones = (clone $baseQuery)->count();
            $totalTrabajadoresUnicos = (clone $baseQuery)->distinct('trabajador_id')->count();

            $vinculacionesPaginadas = (clone $baseQuery)
                ->with([
                    'trabajador' => fn($q) => $q->withTrashed()->withCount('vinculaciones'),
                    'dependencia.parent',
                    'cargoMandante:id,nombre_cargo',
                    'unidadOrganizacionalMandante' => fn($q) => $q->with(['parent', 'mandante:id,razon_social'])->select('id', 'nombre_unidad', 'mandante_id', 'parent_id'),
                ])
                ->join('trabajadores', 'trabajador_vinculaciones.trabajador_id', '=', 'trabajadores.id')
                ->select('trabajador_vinculaciones.*')
                ->orderBy($this->sortByTrabajador, $this->sortDirectionTrabajador)
                ->orderBy('trabajadores.nombres', 'asc')
                ->paginate(15, ['*'], 'trabajadoresPage');

            // ================== INICIO AISLAMIENTO DEFENSIVO ==================
            /**
             * PASO 1: EXTRAER Y ALMACENAR VALORES PRE-CALCULADOS
             * 
             * Antes de cualquier procesamiento adicional (como calcular estados de documentos),
             * extraemos y almacenamos los valores de estado_acceso y porcentaje_cumplimiento
             * directamente desde la base de datos. Estos valores son INMUTABLES durante el render.
             */
            $this->estadosPreCalculados = [];
            foreach ($vinculacionesPaginadas as $vinculacion) {
                $this->estadosPreCalculados[$vinculacion->id] = [
                    'estado_acceso' => $vinculacion->estado_acceso ?? ['habilitado' => false, 'motivo' => 'Estado no calculado'],
                    'porcentaje_cumplimiento' => $vinculacion->porcentaje_cumplimiento ?? 0,
                ];
            }
            // ================== FIN PASO 1 ==================

            // ================== INICIO PASO 2: CALCULAR ESTADOS DE DOCUMENTOS INDIVIDUALES ==================
            /**
             * PASO 2: CALCULAR ESTADOS DE DOCUMENTOS INDIVIDUALES (AISLADO)
             * 
             * Este cálculo es para las columnas individuales de documentos y está completamente
             * AISLADO de los valores de estado_acceso y porcentaje_cumplimiento que ya extrajimos.
             * No puede ni debe afectar esos valores.
             */
            $this->estadosDocumentosPorVinculacion = [];
            if (!empty($this->documentosMaestros)) {
                foreach ($vinculacionesPaginadas as $vinculacion) {
                    if ($vinculacion->trabajador && $vinculacion->unidadOrganizacionalMandante) {
                        $mandanteIdParaCalculo = $vinculacion->unidadOrganizacionalMandante->mandante_id;
                        $uoIdParaCalculo = $vinculacion->unidad_organizacional_mandante_id;
                        
                        // Llamamos al servicio pasando el ID de vinculación específico
                        $estados = $this->documentoService->obtenerEstadoDocumentosParaEntidad(
                            $vinculacion->trabajador, 
                            $mandanteIdParaCalculo, 
                            $uoIdParaCalculo,
                            $vinculacion->id  // ← CRÍTICO: Pasar el ID de vinculación
                        );
                        
                        // Solo guardamos los estados de documentos individuales
                        $this->estadosDocumentosPorVinculacion[$vinculacion->id] = collect($estados)
                            ->mapWithKeys(fn($item) => [$item['nombre_documento_id'] => $item['estado_actual_documento']]);
                    } else {
                        $this->estadosDocumentosPorVinculacion[$vinculacion->id] = collect();
                    }
                }
            }
            // ================== FIN PASO 2 ==================

            // ================== CALCULAR BLOQUEOS DE REVERSIÓN ==================
            $vinculacionesBloqueadasReversion = [];
            $vincIdsInactivas = $vinculacionesPaginadas->where('is_active', false)->pluck('id')->toArray();
            
            if (!empty($vincIdsInactivas)) {
                $trabajadoresIds = $vinculacionesPaginadas->where('is_active', false)->pluck('trabajador_id')->unique()->toArray();
                // Buscar si alguna vinculación de estos trabajadores está en un periodo enviado
                $todasVincIds = \App\Models\TrabajadorVinculacion::whereIn('trabajador_id', $trabajadoresIds)->pluck('id')->toArray();
                
                $bloqueados = \App\Models\CarpetaVerificacionTrabajador::whereIn('trabajador_vinculacion_id', $todasVincIds)
                    ->whereIn('estado_revision', ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD'])
                    ->whereHas('carpeta', function ($q) {
                        $q->where('estado', 'ENVIADO');
                    })
                    ->pluck('trabajador_vinculacion_id')
                    ->toArray();

                if (!empty($bloqueados)) {
                    $trabajadoresBloqueadosIds = \App\Models\TrabajadorVinculacion::whereIn('id', $bloqueados)->pluck('trabajador_id')->unique()->toArray();
                    foreach ($vinculacionesPaginadas as $v) {
                        if (in_array($v->trabajador_id, $trabajadoresBloqueadosIds)) {
                            $vinculacionesBloqueadasReversion[] = $v->id;
                        }
                    }
                }
            }

        } elseif ($this->vistaActual === 'listado_vinculaciones' && $this->trabajadorSeleccionado) {
            // NOTA: Se eliminó el filtro whereIn(unidadesHabilitadasGlobal) para mostrar vinculaciones
            // en UOs/ubicaciones que ya no están asignadas al contratista (para poder reasignarlas).
            // PERO se mantiene el filtro por mandante si está seleccionado.
            $query = TrabajadorVinculacion::where('trabajador_id', $this->trabajadorSeleccionado->id)
                ->with(['unidadOrganizacionalMandante.mandante', 'unidadOrganizacionalMandante.parent', 'cargoMandante', 'tipoCondicionPersonal', 'dependencia.parent']);
            
            // Si hay un mandante seleccionado, filtrar solo las vinculaciones de ese mandante
            if ($this->mandanteId) {
                $query->whereHas('unidadOrganizacionalMandante', fn($q) => $q->where('mandante_id', $this->mandanteId));
            }
            
            $vinculacionesPaginadas = $query
                ->orderBy('is_active', 'desc')->orderBy('fecha_ingreso_vinculacion', 'desc')
                ->paginate(10, ['*'], 'vinculacionesPage');
                
            // CALCULAR BLOQUEOS DE REVERSIÓN PARA VISTA DE DETALLE
            $vinculacionesBloqueadasReversion = [];
            $vincIds = $vinculacionesPaginadas->pluck('id')->toArray();
            
            $bloqueado = \App\Models\CarpetaVerificacionTrabajador::whereIn('trabajador_vinculacion_id', $vincIds)
                ->whereIn('estado_revision', ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD'])
                ->whereHas('carpeta', function ($q) {
                    $q->where('estado', 'ENVIADO');
                })
                ->exists();
                
            if ($bloqueado) {
                $vinculacionesBloqueadasReversion = $vincIds;
            }
        }

        return view('livewire.contratista.gestion-trabajadores-contratista', [
            'vinculacionesPaginadas' => $vinculacionesPaginadas,
            'totalTrabajadoresUnicos' => $totalTrabajadoresUnicos,
            'totalAsignaciones' => $totalAsignaciones,
            'vinculacionesBloqueadasReversion' => $vinculacionesBloqueadasReversion ?? [],
        ]);
    }

    // ================== MÉTODOS PARA MODAL DE EXCEPCIONES (ANULACIÓN MANUAL) ==================

    /**
     * Abre el modal para crear una excepción de acceso (habilitar o restringir manualmente)
     */
    public function abrirModalAnulacion($trabajadorId, $vinculacionId, $accion)
    {
        $this->recursoSeleccionado = Trabajador::find($trabajadorId);
        $this->recursoType = Trabajador::class;
        $this->accionAnulacion = $accion; // 'HABILITAR' o 'RESTRINGIR'
        $this->justificacion = '';
        $this->valido_hasta = null;
        $this->resetErrorBag();
        $this->showAnulacionModal = true;
    }

    /**
     * Cierra el modal de excepciones
     */
    public function cerrarModalAnulacion()
    {
        $this->showAnulacionModal = false;
        $this->recursoSeleccionado = null;
        $this->recursoType = null;
        $this->accionAnulacion = null;
        $this->justificacion = '';
        $this->valido_hasta = null;
    }

    /**
     * Guarda la excepción de acceso
     */
    public function guardarAnulacionAcceso()
    {
        $this->validate([
            'justificacion' => 'required|string|min:20',
            'valido_hasta' => 'nullable|date|after_or_equal:today',
        ], [
            'justificacion.required' => 'La justificación es obligatoria.',
            'justificacion.min' => 'La justificación debe tener al menos 20 caracteres.',
            'valido_hasta.after_or_equal' => 'La fecha de vencimiento debe ser hoy o una fecha futura.',
        ]);

        try {
            $placeholderDocumentoId = 99999;

            DocumentoExcepcionCriticidad::updateOrCreate(
                [
                    'mandante_id' => $this->mandanteId,
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

            // Buscar la vinculación y actualizar su estado
            $vinculacion = TrabajadorVinculacion::where('trabajador_id', $this->recursoSeleccionado->id)
                ->where('unidad_organizacional_mandante_id', $this->unidadOrganizacionalId)
                ->where('dependencia_id', $this->lugarDeTrabajoId)
                ->first();

            if ($vinculacion) {
                $estadoService = app(EstadoCumplimientoService::class);
                $estadoService->actualizarEstadoParaVinculacion($vinculacion);
            }

            session()->flash('success', 'La excepción de acceso ha sido registrada correctamente.');
            $this->cerrarModalAnulacion();
        } catch (\Exception $e) {
            Log::error("Error al guardar excepción de acceso: " . $e->getMessage());
            session()->flash('error', 'Ocurrió un error al registrar la excepción.');
        }
    }

    /**
     * Revierte una excepción de acceso manual
     */
    public function revertirAnulacionManual($trabajadorId, $vinculacionId)
    {
        try {
            $placeholderDocumentoId = 99999;

            $anulacion = DocumentoExcepcionCriticidad::where('mandante_id', $this->mandanteId)
                ->where('excepcionable_type', Trabajador::class)
                ->where('excepcionable_id', $trabajadorId)
                ->where('nombre_documento_id', $placeholderDocumentoId)
                ->first();

            if (!$anulacion) {
                session()->flash('error', 'No se encontró una excepción manual para revertir.');
                return;
            }

            $anulacion->delete();

            // Buscar la vinculación y forzar recálculo
            $vinculacion = TrabajadorVinculacion::where('trabajador_id', $trabajadorId)
                ->where('unidad_organizacional_mandante_id', $this->unidadOrganizacionalId)
                ->where('dependencia_id', $this->lugarDeTrabajoId)
                ->first();

            if ($vinculacion) {
                $estadoService = app(EstadoCumplimientoService::class);
                $estadoService->recalcularEstadoForzado($vinculacion);
            }

            session()->flash('success', 'La excepción manual ha sido revertida. El estado ahora es calculado por el sistema.');
        } catch (\Exception $e) {
            Log::error("Error al revertir excepción de acceso: " . $e->getMessage());
            session()->flash('error', 'Ocurrió un error al revertir la excepción: ' . $e->getMessage());
        }
    }
    // ================== FIN MÉTODOS EXCEPCIONES ==================
}