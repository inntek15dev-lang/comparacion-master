<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Contratista;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\TipoEmpresaLegal;
use App\Models\Rubro;
use App\Models\Region;
use App\Models\Comuna;
use App\Models\RangoCantidadTrabajadores;
use App\Models\Mutualidad;
use App\Models\Mandante;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\TipoCondicion;
use App\Models\ContratistaUnidadOrganizacional;
use App\Models\Dependencia;
use App\Models\SolicitudVinculacion;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Rules\ValidarRutRule;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Illuminate\Pagination\LengthAwarePaginator;

#[Layout('layouts.app')]
class GestionContratistas extends Component
{
    use WithPagination;

    public $contratistaId, $razon_social, $nombre_fantasia, $rut_contratista, $direccion_calle, $direccion_numero, $comuna_id;
    public $selected_region_id_contratista;
    public $telefono_empresa, $email_empresa, $tipo_empresa_legal_id, $rubro_id, $is_active = true, $tipo_inscripcion = 'Contratista';
    public $rango_cantidad_trabajadores_id, $mutualidad_id;
    public $rep_legal_nombres, $rep_legal_apellido_paterno, $rep_legal_apellido_materno, $rep_legal_rut, $rep_legal_telefono, $rep_legal_email;
    public $admin_user_id;
    public ?string $admin_name = '';
    public ?string $admin_rut_usuario = '';
    public ?string $admin_email = '';
    public ?string $admin_email_confirmation = ''; // NUEVA PROPIEDAD
    public $admin_password, $admin_password_confirmation;
    public $admin_is_active = true;
    public bool $crear_nuevo_admin = true;
    public bool $generar_password_auto = true;
    public $tiposEmpresaLegal, $rubros, $regiones, $comunasDisponiblesContratista = [], $rangosCantidad, $mutualidades;
    public $search = '';
    public $sortField = 'contratistas.razon_social';
    public $sortDirection = 'asc';
    public $isOpen = false;
    public bool $showModalVinculaciones = false;
    public ?int $contratistaVinculacionesId = null;
    public ?int $selectedPivotId = null; // ID de la vinculación específica a editar
    public string $nombreContratistaVinculaciones = '';
    public array $vinculacionesTemp = [];

    public $filtroTipo = 'todos';
    public $filtroEstado = 'activos';  // Cambiado a 'activos' por defecto
    public $filtroMandante = 'todos';
    public $filtroAcredita = 'todos';
    public $filtroVerifica = 'todos';
    public $filtroContrato = '';  // Nuevo filtro por número de contrato
    public $filtroTipoContrato = 'todos';  // Filtro por tipo de contrato
    public $mandantesDisponibles = [];
    public $tiposCondicionDisponibles = [];
    public $mandante_id_vinculacion;

    // Columnas que el usuario puede excluir de la vista
    public array $columnasExcluidas = ['id_bd'];

    public bool $showModalAsignarMandante = false;
    public ?int $contratistaParaAsignar_id = null;
    public string $nombreContratistaParaAsignar = '';
    public $mandantesParaAsignar = [];
    public ?int $nuevoMandanteId = null;

    // Propiedades para Gestión de Cargos por Vinculación
    public bool $showModalGestionCargos = false;
    public $vinculacionIndexActual = null;
    public $cargosDisponiblesModal = [];
    public $cargosSeleccionadosTemp = [];
    
    // Arrays para guardar opciones permitidas del contratista padre cuando es subcontratista
    public $dependenciasPadresPermitidas = [];
    public $uosPadresPermitidas = [];
    public $contratosPadresPermitidos = []; // [cargo_id => ['selected' => bool, 'cuota' => int/null]]

    #[Url]
    public bool $readOnly = false;

    public bool $isSubcontractorMode = false; // Nuevo modo para subs
    public function abrirModalVinculaciones($contratistaId, $pivotId = null)
    {
        $contratista = Contratista::with(['solicitudesVinculacion', 'contratistaPadreAprobado'])->find($contratistaId);
        if (!$contratista) return;

        $this->contratistaVinculacionesId = $contratista->id;
        $this->nombreContratistaVinculaciones = $contratista->razon_social;
        $this->selectedPivotId = $pivotId;
        
        // Detectar si es subcontratista (tiene padre aprobado)
        $padre = $contratista->contratistaPadreAprobado->first();
        $this->isSubcontractorMode = (bool)$padre;

        // Si es subcontratista, cargar SIEMPRE las opciones permitidas del Padre (independiente de si es edición o nueva)
        if ($this->isSubcontractorMode && $padre) {
             $vinculosPadre = ContratistaUnidadOrganizacional::where('contratista_id', $padre->id)->get();
             $this->dependenciasPadresPermitidas = $vinculosPadre->pluck('dependencia_id')->filter()->unique()->values()->toArray();
             $this->uosPadresPermitidas = $vinculosPadre->pluck('unidad_organizacional_mandante_id')->filter()->unique()->values()->toArray();
             $this->contratosPadresPermitidos = $vinculosPadre->pluck('numero_contrato')->filter()->unique()->values()->toArray();
        }

        // Cargar vinculaciones
        $this->vinculacionesTemp = [];

        if ($pivotId) {
            // MODO EDICIÓN INDIVIDUAL
            $v = ContratistaUnidadOrganizacional::with('cargosConfigurados')->find($pivotId);
            if ($v) {
                $mandanteId = null;
                if ($v->unidad_organizacional_mandante_id) {
                    $uo = UnidadOrganizacionalMandante::find($v->unidad_organizacional_mandante_id);
                    if ($uo) $mandanteId = $uo->mandante_id;
                } elseif ($v->dependencia_id) {
                    $dep = Dependencia::find($v->dependencia_id);
                    if ($dep) $mandanteId = $dep->mandante_id;
                }

                if (!$mandanteId) {
                    $mandanteId = $contratista->solicitudesVinculacion->where('estado', 'APROBADA')->pluck('mandante_id')->first();
                }

                $this->vinculacionesTemp[] = [
                    'id' => $v->id,
                    'mandante_id' => $mandanteId,
                    'dependencia_id' => $v->dependencia_id,
                    'unidad_organizacional_mandante_id' => $v->unidad_organizacional_mandante_id,
                    'condiciones_ids' => $v->condiciones()->pluck('tipo_condicion_id')->map(fn($i)=>(int)$i)->toArray(),
                    'acredita' => (bool)$v->acredita,
                    'fecha_inicio_acredita' => $v->fecha_inicio_acredita ? $v->fecha_inicio_acredita->format('Y-m-d') : null,
                    'fecha_fin_acredita' => $v->fecha_fin_acredita ? $v->fecha_fin_acredita->format('Y-m-d') : null,
                    'verifica' => (bool)$v->verifica,
                    'fecha_inicio_verifica' => $v->fecha_inicio_verifica ? $v->fecha_inicio_verifica->format('Y-m-d') : null,
                    'fecha_fin_verifica' => $v->fecha_fin_verifica ? $v->fecha_fin_verifica->format('Y-m-d') : null,
                    'numero_contrato' => $v->numero_contrato,
                    'tipo_contrato_id' => $v->tipo_contrato_id,
                    'trabajadores_cuota' => $v->trabajadores_cuota,
                    'id_registro' => $v->id_registro,
                    'sap' => $v->sap,
                    'generar_id_registro_auto' => false,
                    'id_registro_readonly' => !empty($v->id_registro),
                    'cargos_config' => $v->cargosConfigurados->map(function($c) {
                        return [
                            'cargo_id' => $c->cargo_mandante_id,
                            'selected' => true, 
                            'cuota' => $c->cuota
                        ];
                    })->toArray(),
                ];
            }
        } elseif ($this->isSubcontractorMode && $padre) {
             // MODO SUBCONTRATISTA 
             $this->agregarFilaVinculacion($padre);
        } else {
            // MODO CREACIÓN (Standalone)
            $this->agregarFilaVinculacion();
        }

        // Pre-cargar condiciones si ya conocemos el mandante (modo edición o subcontratista)
        $mandanteIdInicial = $this->vinculacionesTemp[0]['mandante_id'] ?? null;
        $this->_cargarCondicionesPorMandante($mandanteIdInicial ? (int)$mandanteIdInicial : null);

        $this->showModalVinculaciones = true;
    }

    public function agregarFilaVinculacion($padre = null)
    {
        $mandanteFijoId = null;
        if ($this->isSubcontractorMode) {
            if (!$padre && $this->contratistaVinculacionesId) {
                $c = \App\Models\Contratista::find($this->contratistaVinculacionesId);
                if ($c) $padre = $c->contratistaPadreAprobado->first();
            }
            if ($padre) {
                $mandanteFijoId = $padre->solicitudesVinculacion->where('estado', 'APROBADA')->pluck('mandante_id')->first();
            }
        }

        $this->vinculacionesTemp[] = [
            'id' => null,
            'mandante_id' => $mandanteFijoId,
            'dependencia_id' => null,
            'unidad_organizacional_mandante_id' => null,
            'condiciones_ids' => [],
            'acredita' => true,
            'fecha_inicio_acredita' => null,
            'fecha_fin_acredita' => null,
            'verifica' => false,
            'fecha_inicio_verifica' => null,
            'fecha_fin_verifica' => null,
            'numero_contrato' => null,
            'tipo_contrato_id' => null,
            'trabajadores_cuota' => null,
            'id_registro' => null,
            'sap' => null,
            'generar_id_registro_auto' => true,
            'id_registro_readonly' => false,
            'cargos_config' => [],
        ];

        // Precargar identidad (SAP e ID Registro) si ya conocemos el mandante fijo
        if ($mandanteFijoId) {
            $this->_precargarIdentidad($this->vinculacionesTemp[count($this->vinculacionesTemp) - 1], $this->contratistaVinculacionesId, $mandanteFijoId);
        }
    }

    private function _precargarIdentidad(&$fila, $contratistaId, $mandanteId)
    {
        if (!$mandanteId || !$contratistaId) return;

        $existingSap = null;
        $existingId = null;

        // 1. Buscar si en el modal ya había un SAP o ID cargado para este mandante
        foreach ($this->vinculacionesTemp as $row) {
            if (!empty($row['mandante_id']) && $row['mandante_id'] == $mandanteId) {
                if (!empty($row['sap']) && !$existingSap) $existingSap = $row['sap'];
                if (!empty($row['id_registro']) && !$existingId) $existingId = $row['id_registro'];
            }
        }

        // 2. Si no está en el modal, buscar en la Base de Datos
        if (!$existingSap || !$existingId) {
            $existing = DB::table('contratista_unidad_organizacional as cuo')
                ->leftJoin('unidades_organizacionales_mandante as uo', 'uo.id', '=', 'cuo.unidad_organizacional_mandante_id')
                ->leftJoin('dependencias as dep', 'dep.id', '=', 'cuo.dependencia_id')
                ->where('cuo.contratista_id', $contratistaId)
                ->where(function($q) use ($mandanteId) {
                    $q->where('uo.mandante_id', $mandanteId)
                      ->orWhere('dep.mandante_id', $mandanteId);
                })
                ->where(function($q) {
                    $q->whereNotNull('cuo.sap')->where('cuo.sap', '!=', '')
                      ->orWhereNotNull('cuo.id_registro');
                })
                ->select('cuo.sap', 'cuo.id_registro')
                ->first();

            if ($existing) {
                if (!$existingSap && $existing->sap) $existingSap = $existing->sap;
                if (!$existingId && $existing->id_registro) $existingId = $existing->id_registro;
            }
        }

        if ($existingSap) $fila['sap'] = $existingSap;
        if ($existingId) {
            $fila['id_registro'] = $existingId;
            $fila['generar_id_registro_auto'] = false;
            $fila['id_registro_readonly'] = true;
        }
    }

    public function eliminarFilaVinculacion($index)
    {
        unset($this->vinculacionesTemp[$index]);
        $this->vinculacionesTemp = array_values($this->vinculacionesTemp);
        
        if (empty($this->vinculacionesTemp)) {
            $this->agregarFilaVinculacion();
        }
    }

    public function iniciarGestionCargos($index)
    {
        $this->vinculacionIndexActual = $index;
        $row = $this->vinculacionesTemp[$index];
        
        if (empty($row['mandante_id'])) {
            session()->flash('error_vinculaciones', 'Debe seleccionar un Mandante antes de configurar cargos.');
            return;
        }

        $cargosRaw = $row['cargos_config'] ?? [];
        $cargosSeleccionadosTemp = [];
        foreach ($cargosRaw as $c) {
            if (isset($c['cargo_id'])) {
                $cargosSeleccionadosTemp[$c['cargo_id']] = $c;
            }
        }

        if ($this->isSubcontractorMode) {
            $contratista = Contratista::find($this->contratistaVinculacionesId);
            $padreCuo = null;
            if ($contratista) {
                $padre = $contratista->contratistaPadreAprobado->first();
                if ($padre) {
                    $padreCuoQuery = ContratistaUnidadOrganizacional::where('contratista_id', $padre->id);
                    if (!empty($row['unidad_organizacional_mandante_id'])) {
                        $padreCuoQuery->where('unidad_organizacional_mandante_id', $row['unidad_organizacional_mandante_id']);
                    } else {
                        $padreCuoQuery->whereNull('unidad_organizacional_mandante_id');
                    }
                    if (!empty($row['dependencia_id'])) {
                        $padreCuoQuery->where('dependencia_id', $row['dependencia_id']);
                    } else {
                        $padreCuoQuery->whereNull('dependencia_id');
                    }
                    if (!empty($row['numero_contrato'])) {
                        $padreCuoQuery->where('numero_contrato', $row['numero_contrato']);
                    }
                    $padreCuo = $padreCuoQuery->first();
                }
            }

            if ($padreCuo) {
                $cargosPadre = \App\Models\ContratistaUoCargo::where('contratista_uo_id', $padreCuo->id)->get();
                $cargosIdsPadre = $cargosPadre->pluck('cargo_mandante_id')->toArray();

                if (empty($cargosIdsPadre)) {
                    // El padre no tiene restricciones, así que tiene todos disponibles
                    $this->cargosDisponiblesModal = \App\Models\CargoMandante::where('mandante_id', $row['mandante_id'])
                        ->where('is_active', true)
                        ->orderBy('nombre_cargo')
                        ->get();
                    foreach ($this->cargosDisponiblesModal as $cargo) {
                        $cargosSeleccionadosTemp[$cargo->id] = [
                            'cargo_id' => $cargo->id,
                            'selected' => true,
                            'cuota' => null
                        ];
                    }
                } else {
                    $this->cargosDisponiblesModal = \App\Models\CargoMandante::whereIn('id', $cargosIdsPadre)
                        ->orderBy('nombre_cargo')
                        ->get();
                    foreach ($this->cargosDisponiblesModal as $cargo) {
                        $ccPadre = $cargosPadre->firstWhere('cargo_mandante_id', $cargo->id);
                        $cargosSeleccionadosTemp[$cargo->id] = [
                            'cargo_id' => $cargo->id,
                            'selected' => true,
                            'cuota' => $ccPadre ? $ccPadre->cuota : null
                        ];
                    }
                }
            } else {
                $this->cargosDisponiblesModal = collect(); // Sin vinculación equivalente en el padre
            }
        } else {
            // Modo Normal (Contratista Principal)
            $this->cargosDisponiblesModal = \App\Models\CargoMandante::where('mandante_id', $row['mandante_id'])
                ->where('is_active', true)
                ->orderBy('nombre_cargo')
                ->get();

            foreach ($this->cargosDisponiblesModal as $cargo) {
                if (!isset($cargosSeleccionadosTemp[$cargo->id])) {
                    $cargosSeleccionadosTemp[$cargo->id] = [
                        'cargo_id' => $cargo->id,
                        'selected' => false,
                        'cuota' => null
                    ];
                } else {
                    $cargosSeleccionadosTemp[$cargo->id]['cargo_id'] = $cargo->id;
                    $cargosSeleccionadosTemp[$cargo->id]['selected'] = filter_var($cargosSeleccionadosTemp[$cargo->id]['selected'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $cargosSeleccionadosTemp[$cargo->id]['cuota'] = $cargosSeleccionadosTemp[$cargo->id]['cuota'] ?? null;
                }
            }
        }

        $this->cargosSeleccionadosTemp = $cargosSeleccionadosTemp;
        $this->showModalGestionCargos = true;
    }

    public function guardarCargosTemp()
    {
        // Limpiamos la configuración forzando sus tipos y normalizamos a array secuencial
        $configToSave = [];
        foreach ($this->cargosSeleccionadosTemp as $key => $c) {
            $isSelected = filter_var($c['selected'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($isSelected || $this->isSubcontractorMode) {
                $c['selected'] = $isSelected;
                $c['cargo_id'] = $c['cargo_id'] ?? $key;
                $configToSave[] = $c; // Array secuencial puro para evitar crash de Livewire 3 en Javascript
            }
        }
        
        \Illuminate\Support\Facades\Log::info('== guardarCargosTemp ==');
        \Illuminate\Support\Facades\Log::info(json_encode($configToSave));

        $this->vinculacionesTemp[$this->vinculacionIndexActual]['cargos_config'] = $configToSave;
        $this->showModalGestionCargos = false;
    }

    public function updatedVinculacionesTemp($value, $key)
    {
        $parts = explode('.', $key);
        // Detectar si el usuario modificó el mandante_id de una fila
        if (count($parts) === 2 && $parts[1] === 'mandante_id' && !empty($value)) {
            $index = $parts[0];
            $mandanteId = $value;
            
            // Recargamos las condiciones disponibles filtradas por este mandante
            $this->_cargarCondicionesPorMandante((int)$mandanteId);

            // Usamos el helper centralizado para intentar traer SAP e ID_Registro
            $this->_precargarIdentidad($this->vinculacionesTemp[(int)$index], $this->contratistaVinculacionesId, $mandanteId);
        }
    }

    /**
     * Carga las condiciones de empresa filtradas por un mandante específico.
     * Si no hay mandante, deja el listado vacío.
     */
    protected function _cargarCondicionesPorMandante(?int $mandanteId): void
    {
        if (!$mandanteId) {
            $this->tiposCondicionDisponibles = [];
            return;
        }

        // Carga SOLO las condiciones del mandante seleccionado (filtrado estricto)
        $this->tiposCondicionDisponibles = TipoCondicion::where('is_active', true)
            ->where('mandante_id', $mandanteId)
            ->orderBy('nombre')
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre])
            ->values()
            ->toArray();
    }

    public function guardarVinculaciones()
    {
        \Illuminate\Support\Facades\Log::info('== INICIO guardarVinculaciones ==', [
            'contratistaId' => $this->contratistaVinculacionesId,
            'vinculacionesTemp' => $this->vinculacionesTemp,
            'isSubcontractorMode' => $this->isSubcontractorMode
        ]);
        
        $contratista = Contratista::find($this->contratistaVinculacionesId);
        if (!$contratista) {
            \Illuminate\Support\Facades\Log::warning('Guardado falló: contratista no encontrado');
            return;
        }

        // ========== VALIDACIÓN DE DUPLICADOS ==========
        // Construir array de combinaciones únicas para detectar duplicados
        $combinaciones = [];
        foreach ($this->vinculacionesTemp as $index => $row) {
            if (!$row['mandante_id'] || (!$row['dependencia_id'] && !$row['unidad_organizacional_mandante_id'])) {
                continue; // Ignorar filas incompletas
            }

            // Crear clave única: UO + Dependencia + N° Contrato
            $clave = implode('|', [
                $row['unidad_organizacional_mandante_id'] ?? 'null',
                $row['dependencia_id'] ?? 'null',
                $row['numero_contrato'] ?? 'null',
            ]);

            if (isset($combinaciones[$clave])) {
                // Preparar mensaje descriptivo
                $uo = $row['unidad_organizacional_mandante_id'] 
                    ? UnidadOrganizacionalMandante::find($row['unidad_organizacional_mandante_id'])?->nombre_unidad ?? 'N/A'
                    : 'N/A';
                $lugar = $row['dependencia_id']
                    ? Dependencia::find($row['dependencia_id'])?->nombre ?? 'N/A'
                    : 'N/A';
                $nContrato = $row['numero_contrato'] ?? 'Sin N° Contrato';

                session()->flash('error_vinculaciones', 
                    "⚠️ Error: Ya existe una vinculación con la misma combinación.\n\n" .
                    "📍 Lugar de Trabajo: {$lugar}\n" .
                    "🏢 U. Operativa: {$uo}\n" .
                    "📋 N° Contrato: {$nContrato}\n\n" .
                    "Por favor, use un N° de Contrato diferente para cada vinculación en la misma UO."
                );
                return;
            }

            $combinaciones[$clave] = $index;
        }
        // ========== FIN SUPER VALIDACIÓN DE DUPLICADOS ==========

        // ========== VALIDACIÓN DE RESTRICCIONES DE SUBCONTRATISTA ==========
        if ($this->isSubcontractorMode) {
            foreach ($this->vinculacionesTemp as $index => $row) {
                if ($row['dependencia_id'] && !in_array($row['dependencia_id'], $this->dependenciasPadresPermitidas)) {
                    session()->flash('error_vinculaciones', "El Lugar seleccionado no está autorizado para el contratista principal.");
                    return;
                }
                if ($row['unidad_organizacional_mandante_id'] && !in_array($row['unidad_organizacional_mandante_id'], $this->uosPadresPermitidas)) {
                    session()->flash('error_vinculaciones', "La Unidad Operativa seleccionada no está autorizada para el contratista principal.");
                    return;
                }
                if ($row['numero_contrato'] && !in_array($row['numero_contrato'], $this->contratosPadresPermitidos)) {
                    session()->flash('error_vinculaciones', "El N° Contrato no pertenece a las vinculaciones del contratista principal.");
                    return;
                }
            }
        }
        // ========== FIN DE RESTRICCIONES DE SUBCONTRATISTA ==========
        // ========== SINCRONIZACIÓN Y SEGURIDAD DEL SAP POR MANDANTE ==========
        $isAdmin = auth()->user() && auth()->user()->hasRole('ASEM_Admin');
        $sapPorMandante = [];
        
        if ($isAdmin) {
            // El administrador puede definir el SAP. Extraemos el ingresado.
            foreach ($this->vinculacionesTemp as $row) {
                $mId = (string)($row['mandante_id'] ?? '');
                if (!empty($mId) && !empty($row['sap']) && !isset($sapPorMandante[$mId])) {
                    $sapPorMandante[$mId] = $row['sap'];
                }
            }
        } else {
            // Usuarios normales no pueden editar SAP. Recuperamos el valor real de BD para evitar inyecciones.
            $vinculacionesDB = ContratistaUnidadOrganizacional::where('contratista_id', $contratista->id)->get();
            foreach ($vinculacionesDB as $v) {
                $mId = null;
                if ($v->unidad_organizacional_mandante_id) {
                    $uo = UnidadOrganizacionalMandante::find($v->unidad_organizacional_mandante_id);
                    if ($uo) $mId = $uo->mandante_id;
                } elseif ($v->dependencia_id) {
                    $dep = Dependencia::find($v->dependencia_id);
                    if ($dep) $mId = $dep->mandante_id;
                }
                
                if ($mId && !empty($v->sap) && !isset($sapPorMandante[(string)$mId])) {
                    $sapPorMandante[(string)$mId] = $v->sap;
                }
            }
        }

        // Unificar y aplicar el SAP correcto a todas las filas del mismo mandante (Sincronización)
        foreach ($this->vinculacionesTemp as $index => $row) {
            $mId = (string)($row['mandante_id'] ?? '');
            if (!empty($mId)) {
                $this->vinculacionesTemp[$index]['sap'] = $sapPorMandante[$mId] ?? null;
            }
        }
        
        // ========== AUTO-GENERACIÓN Y PROPAGACIÓN DE ID REGISTRO ==========
        $idGeneradoPorMandante = [];
        
        foreach ($this->vinculacionesTemp as $index => $row) {
            $mId = (string)($row['mandante_id'] ?? '');
            
            // Si tiene id_registro existente (ya sea por edición o propagación), lo forzamos a ser el mismo para todas las filas de este mandante?
            // En realidad, si una fila requiere auto-generación y no tiene ID, le generamos uno único por mandante.
            if (!empty($row['generar_id_registro_auto']) && empty($row['id_registro']) && !empty($mId)) {
                if (!isset($idGeneradoPorMandante[$mId])) {
                    $idGeneradoPorMandante[$mId] = $this->generarIdRegistroAutomatico();
                }
                $this->vinculacionesTemp[$index]['id_registro'] = $idGeneradoPorMandante[$mId];
                $this->vinculacionesTemp[$index]['generar_id_registro_auto'] = false; // Ya fue generado
            }
        }
        // =====================================================================

        DB::beginTransaction();
        try {
            foreach ($this->vinculacionesTemp as $row) {
                // Sólo guardar si tiene al menos mandante y (lugar o uo)
                if ($row['mandante_id'] && ($row['dependencia_id'] || $row['unidad_organizacional_mandante_id'])) {
                    \Illuminate\Support\Facades\Log::info('Row valid to save! row_id=' . ($row['id'] ?? 'nuevo'));
                    $data = [
                        'contratista_id' => $contratista->id,
                        'dependencia_id' => $row['dependencia_id'] ?: null,
                        'unidad_organizacional_mandante_id' => $row['unidad_organizacional_mandante_id'] ?: null,
                        'acredita' => $row['acredita'],
                        'fecha_inicio_acredita' => $row['fecha_inicio_acredita'] ?? null,
                        'fecha_fin_acredita' => $row['fecha_fin_acredita'] ?? null,
                        'verifica' => $row['verifica'],
                        'fecha_inicio_verifica' => $row['fecha_inicio_verifica'] ?? null,
                        'fecha_fin_verifica' => $row['fecha_fin_verifica'] ?? null,
                        'numero_contrato' => $row['numero_contrato'] ?? null,
                        'tipo_contrato_id' => $row['tipo_contrato_id'] ?? null,
                        'trabajadores_cuota' => isset($row['trabajadores_cuota']) && $row['trabajadores_cuota'] !== '' ? (int)$row['trabajadores_cuota'] : null,
                        'id_registro' => $row['id_registro'] ?? null,
                        'sap' => $row['sap'] ?? null,
                    ];

                    if (!empty($row['id'])) {
                        // ACTUALIZAR EXISTENTE
                        $cuo = ContratistaUnidadOrganizacional::find($row['id']);
                        if ($cuo) {
                            // Capturar "Firma Vieja" antes de actualizar
                            $viejaFirma = [
                                'dependencia_id' => $cuo->dependencia_id,
                                'unidad_organizacional_mandante_id' => $cuo->unidad_organizacional_mandante_id,
                                'numero_contrato' => $cuo->numero_contrato,
                            ];

                            $cuo->update($data);

                            // Sincronizar condiciones (multi)
                            $condicionesIds = array_filter(array_map('intval', $row['condiciones_ids'] ?? []));
                            $cuo->condiciones()->sync($condicionesIds);

                            // Detectar cambio de Firma y Propagar a Subs
                            if (!$this->isSubcontractorMode) {
                                $nuevaFirma = [
                                    'dependencia_id' => $cuo->dependencia_id,
                                    'unidad_organizacional_mandante_id' => $cuo->unidad_organizacional_mandante_id,
                                    'numero_contrato' => $cuo->numero_contrato,
                                ];
                                if ($viejaFirma !== $nuevaFirma) {
                                    $this->propagarCambiosEstructurales($contratista, $viejaFirma, $nuevaFirma);
                                }
                            }

                            // Limpiar cargos previos
                            \App\Models\ContratistaUoCargo::where('contratista_uo_id', $cuo->id)->delete();
                        } else {
                            $cuo = ContratistaUnidadOrganizacional::create($data);
                            $condicionesIds = array_filter(array_map('intval', $row['condiciones_ids'] ?? []));
                            $cuo->condiciones()->sync($condicionesIds);
                        }
                    } else {
                        // CREAR NUEVO
                        $cuo = ContratistaUnidadOrganizacional::create($data);
                        $condicionesIds = array_filter(array_map('intval', $row['condiciones_ids'] ?? []));
                        $cuo->condiciones()->sync($condicionesIds);
                    }

                    // Guardar configuración de cargos
                    if ($this->isSubcontractorMode) {
                        $padre = $contratista->contratistaPadreAprobado->first();
                        if ($padre) {
                            $padreCuoQuery = ContratistaUnidadOrganizacional::where('contratista_id', $padre->id);
                            if (!empty($row['unidad_organizacional_mandante_id'])) {
                                $padreCuoQuery->where('unidad_organizacional_mandante_id', $row['unidad_organizacional_mandante_id']);
                            } else {
                                $padreCuoQuery->whereNull('unidad_organizacional_mandante_id');
                            }
                            if (!empty($row['dependencia_id'])) {
                                $padreCuoQuery->where('dependencia_id', $row['dependencia_id']);
                            } else {
                                $padreCuoQuery->whereNull('dependencia_id');
                            }
                            if (!empty($row['numero_contrato'])) {
                                $padreCuoQuery->where('numero_contrato', $row['numero_contrato']);
                            }
                            
                            $padreCuo = $padreCuoQuery->first();
                            if ($padreCuo) {
                                $cargosPadre = \App\Models\ContratistaUoCargo::where('contratista_uo_id', $padreCuo->id)->get();
                                foreach ($cargosPadre as $cp) {
                                    \App\Models\ContratistaUoCargo::create([
                                        'contratista_uo_id' => $cuo->id,
                                        'cargo_mandante_id' => $cp->cargo_mandante_id,
                                        'cuota' => null, // El subcontratista comparte la restricción pero no define su propia cuota
                                    ]);
                                }
                            }
                        }
                    } else {
                        // Modo Normal
                        \App\Models\ContratistaUoCargo::where('contratista_uo_id', $cuo->id)->delete(); // Limpiamos SIEMPRE por seguridad
                        
                        \Illuminate\Support\Facades\Log::info('== guardarVinculaciones Cargos Config == ' . $cuo->id);
                        \Illuminate\Support\Facades\Log::info(json_encode($row['cargos_config'] ?? []));
                        
                        if (!empty($row['cargos_config'])) {
                            foreach ($row['cargos_config'] as $key => $config) {
                                $isSelected = filter_var($config['selected'] ?? false, FILTER_VALIDATE_BOOLEAN);
                                $cargoId = $config['cargo_id'] ?? $key;
                                if ($isSelected && is_numeric($cargoId) && $cargoId > 0) {
                                    \App\Models\ContratistaUoCargo::create([
                                        'contratista_uo_id' => $cuo->id,
                                        'cargo_mandante_id' => $cargoId,
                                        'cuota' => (isset($config['cuota']) && $config['cuota'] !== '') ? (int)$config['cuota'] : null,
                                    ]);
                                }
                            }
                        }
                        
                        // Propagar inmediatamente estos cargos a las vinculaciones de cualquier subcontratista dependiente
                        $this->propagarCargosASubcontratistas($cuo->id);
                    }
                }
            }

            // Nota: El sync de dependencias legacy se mantiene pero podría ser redundante
            $todasLasVinculaciones = ContratistaUnidadOrganizacional::where('contratista_id', $contratista->id)->get();
            $depsIds = $todasLasVinculaciones->pluck('dependencia_id')->filter()->unique()->toArray();
            $contratista->dependencias()->sync($depsIds);

            // SINCRONIZACIÓN GLOBAL DE SAP E ID_REGISTRO AL CONTRATISTA (Para todas sus UOs bajo este Mandante)
            if (count($this->vinculacionesTemp) > 0) {
                // Tomar de la primera fila validada
                $primerMId = $this->vinculacionesTemp[0]['mandante_id'] ?? null;
                $globalSap = $this->vinculacionesTemp[0]['sap'] ?? null;
                $globalRegistro = $this->vinculacionesTemp[0]['id_registro'] ?? null;
                
                if ($primerMId) {
                    // Dado que mandante_id no existe directamente en la tabla pívot,
                    // filtramos por las UOs o Dependencias que pertenezcan a ese mandante.
                    $querySincro = ContratistaUnidadOrganizacional::where('contratista_id', $contratista->id)
                        ->where(function($q) use ($primerMId) {
                            $q->whereHas('unidadOrganizacionalMandante', function($sq) use ($primerMId) {
                                $sq->where('mandante_id', $primerMId);
                            })->orWhereHas('dependencia', function($sq) use ($primerMId) {
                                $sq->where('mandante_id', $primerMId);
                            });
                        });

                    $querySincro->update([
                        'sap' => $globalSap,
                        'id_registro' => $globalRegistro,
                    ]);
                }
            }

            DB::commit();
            session()->flash('message', 'Vinculación guardada correctamente.');
            $this->cerrarModalVinculaciones();
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('guardarVinculaciones QueryException: ' . $e->getMessage());
            // Capturar error de constraint único de BD (por si acaso)
            if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'UNIQUE constraint')) {
                session()->flash('error_vinculaciones', 
                    '⚠️ Error: No se puede guardar porque hay vinculaciones duplicadas. ' .
                    'Cada combinación de Lugar + U. Operativa + N° Contrato debe ser única.'
                );
            } else {
                session()->flash('error_vinculaciones', 'Error al guardar: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('guardarVinculaciones Exception: ' . $e->getMessage());
            session()->flash('error_vinculaciones', 'Error al guardar: ' . $e->getMessage());
        }
    }


    public function cerrarModalVinculaciones()
    {
        $this->showModalVinculaciones = false;
        $this->vinculacionesTemp = [];
        $this->contratistaVinculacionesId = null;
    }

    /**
     * Propaga (cascada) los cambios del Lugar, UO y Contrato a todos los subcontratistas recursivos.
     */
    private function propagarCambiosEstructurales($padreContratista, $viejaFirma, $nuevaFirma)
    {
        $subs = $padreContratista->subContratistasAprobados()->get();
        if ($subs->isEmpty()) {
            return;
        }

        foreach ($subs as $sub) {
            // Buscamos si este sub tiene una vinculación atada a la Firma Vieja
            $cuoVieja = ContratistaUnidadOrganizacional::where('contratista_id', $sub->id)
                ->where('dependencia_id', $viejaFirma['dependencia_id'])
                ->where('unidad_organizacional_mandante_id', $viejaFirma['unidad_organizacional_mandante_id'])
                ->where('numero_contrato', $viejaFirma['numero_contrato'])
                ->first();

            if ($cuoVieja) {
                // Checamos si el hijo ya tiene una fila con la Nueva Firma (para evitar DUPLICADO BD)
                $cuoNuevaExiste = ContratistaUnidadOrganizacional::where('contratista_id', $sub->id)
                    ->where('dependencia_id', $nuevaFirma['dependencia_id'])
                    ->where('unidad_organizacional_mandante_id', $nuevaFirma['unidad_organizacional_mandante_id'])
                    ->where('numero_contrato', $nuevaFirma['numero_contrato'])
                    ->exists();

                if ($cuoNuevaExiste) {
                    // Si ya existía, choca. Destruimos la vinculación vieja redundante.
                    $cuoVieja->delete();
                } else {
                    // Actualizamos seguro
                    $cuoVieja->update([
                        'dependencia_id' => $nuevaFirma['dependencia_id'],
                        'unidad_organizacional_mandante_id' => $nuevaFirma['unidad_organizacional_mandante_id'],
                        'numero_contrato' => $nuevaFirma['numero_contrato'],
                    ]);
                }
            }

            // Llamada recursiva (Sub-Sub -> Sub-Sub-Sub)
            $this->propagarCambiosEstructurales($sub, $viejaFirma, $nuevaFirma);
        }
    }

    private function gestionarRecursosFantasmasAlEliminar($contratistaId, $uoId, $dependenciaId, $numeroContrato)
    {
        // 1. Trabajadores
        $this->_limpiarAsignacionesEnReserva(
            \App\Models\TrabajadorVinculacion::whereHas('trabajador', function ($q) use ($contratistaId) {
                $q->where('contratista_id', $contratistaId);
            }),
            $uoId, $dependenciaId, $numeroContrato, 'trabajador_id'
        );

        // 2. Vehículos
        $this->_limpiarAsignacionesEnReserva(
            \App\Models\VehiculoAsignacion::whereHas('vehiculo', function ($q) use ($contratistaId) {
                $q->where('contratista_id', $contratistaId);
            }),
            $uoId, $dependenciaId, null, 'vehiculo_id'
        );

        // 3. Maquinarias
        $this->_limpiarAsignacionesEnReserva(
            \App\Models\MaquinariaAsignacion::whereHas('maquinaria', function ($q) use ($contratistaId) {
                $q->where('contratista_id', $contratistaId);
            }),
            $uoId, $dependenciaId, null, 'maquinaria_id'
        );

        // 4. Embarcaciones
        $this->_limpiarAsignacionesEnReserva(
            \App\Models\EmbarcacionAsignacion::whereHas('embarcacion', function ($q) use ($contratistaId) {
                $q->where('contratista_id', $contratistaId);
            }),
            $uoId, $dependenciaId, null, 'embarcacion_id'
        );
    }

    private function _limpiarAsignacionesEnReserva($queryBuilder, $uoId, $dependenciaId, $numeroContrato, $fkName)
    {
        if ($uoId) {
            $queryBuilder->where('unidad_organizacional_mandante_id', $uoId);
        } else {
            $queryBuilder->whereNull('unidad_organizacional_mandante_id');
        }

        if ($dependenciaId) {
            $queryBuilder->where('dependencia_id', $dependenciaId);
        } else {
            $queryBuilder->whereNull('dependencia_id');
        }

        // Solo trabajadores (y tablas homólogas con contrato futuro) soportan numero_contrato actualmente
        if ($fkName === 'trabajador_id') {
            if ($numeroContrato) {
                $queryBuilder->where('numero_contrato', $numeroContrato);
            } else {
                $queryBuilder->whereNull('numero_contrato');
            }
        }

        $viejasAsignaciones = $queryBuilder->get();

        foreach ($viejasAsignaciones as $asignacion) {
            // Contar cuantas vinculaciones totales tiene este recurso
            $modelClass = get_class($asignacion);
            $totalCount = $modelClass::where($fkName, $asignacion->{$fkName})->count();

            if ($totalCount > 1) {
                // Hay otras vinculaciones, por tanto, se destruye esta
                $asignacion->delete();
            } else {
                // Es la única vinculación que tiene el recurso. Pasa a estado "Reserva".
                $updateData = [
                    'unidad_organizacional_mandante_id' => null,
                    'dependencia_id' => null,
                ];
                if ($fkName === 'trabajador_id') {
                    $updateData['numero_contrato'] = null;
                }
                $asignacion->update($updateData);
            }
        }
    }

    /**
     * Elimina una vinculación de forma recursiva (cascada hacia hijos)
     */
    public function eliminarVinculacion($id)
    {
        $v = ContratistaUnidadOrganizacional::find($id);
        if (!$v) return;

        DB::beginTransaction();
        try {
            $contratista = Contratista::find($v->contratista_id);
            $signature = [
                'dependencia_id' => $v->dependencia_id,
                'unidad_organizacional_mandante_id' => $v->unidad_organizacional_mandante_id,
                'numero_contrato' => $v->numero_contrato,
            ];

            // LLAMADA RECURSIVA PARA HIJOS
            if ($contratista) {
                $this->eliminarVinculacionesRecursivamente($contratista, $signature);
            }

            // LIMPIEZA DE EVENTUALES RECURSOS FANTASMA DEL CONTRATISTA PADRE
            $this->gestionarRecursosFantasmasAlEliminar(
                $v->contratista_id, 
                $v->unidad_organizacional_mandante_id, 
                $v->dependencia_id, 
                $v->numero_contrato
            );

            // LIMPIEZA DE CARGOS Y ELIMINACIÓN FINAL DEL REGISTRO ACTUAL
            \App\Models\ContratistaUoCargo::where('contratista_uo_id', $v->id)->delete();
            $v->delete();

            DB::commit();
            session()->flash('message', 'Vinculación eliminada correctamente en cascada.');
            $this->cerrarModalVinculaciones();
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error al eliminar vinculación: ' . $e->getMessage());
            session()->flash('error_vinculaciones', 'Error al eliminar: ' . $e->getMessage());
        }
    }

    private function eliminarVinculacionesRecursivamente($padre, $signature)
    {
        $subs = $padre->subContratistasAprobados()->get();
        foreach ($subs as $sub) {
            $vinculoHijo = ContratistaUnidadOrganizacional::where('contratista_id', $sub->id)
                ->where('dependencia_id', $signature['dependencia_id'])
                ->where('unidad_organizacional_mandante_id', $signature['unidad_organizacional_mandante_id'])
                ->where('numero_contrato', $signature['numero_contrato'])
                ->first();

            if ($vinculoHijo) {
                // Eliminar cargos y recursos fantasmas antes de borrar el vínculo del hijo
                \App\Models\ContratistaUoCargo::where('contratista_uo_id', $vinculoHijo->id)->delete();
                $this->gestionarRecursosFantasmasAlEliminar(
                    $vinculoHijo->contratista_id, 
                    $vinculoHijo->unidad_organizacional_mandante_id, 
                    $vinculoHijo->dependencia_id, 
                    $vinculoHijo->numero_contrato
                );
                $this->eliminarVinculacionesRecursivamente($sub, $signature);
                $vinculoHijo->delete();
            }
        }
    }

    /**
     * Propaga cascada abajo de los cargos autorizados hacia las mallas de subcontratistas dependientes
     * de la misma vinculación (mandante, lugar, uo).
     */
    private function propagarCargosASubcontratistas($cuoPadreId)
    {
        $cuoPadre = \App\Models\ContratistaUnidadOrganizacional::with('cargosConfigurados')->find($cuoPadreId);
        if (!$cuoPadre) return;

        // Buscar a los subcontratistas aprobados de este contratista
        $hijos = \App\Models\SolicitudVinculacion::where('contratista_padre_id', $cuoPadre->contratista_id)
            ->where('estado', 'APROBADA')
            ->get();

        foreach ($hijos as $solicitudHijo) {
            // Buscar la misma vinculación en el subcontratista
            $cuoHijoQuery = \App\Models\ContratistaUnidadOrganizacional::where('contratista_id', $solicitudHijo->contratista_id);
            if ($cuoPadre->unidad_organizacional_mandante_id) {
                $cuoHijoQuery->where('unidad_organizacional_mandante_id', $cuoPadre->unidad_organizacional_mandante_id);
            } else {
                $cuoHijoQuery->whereNull('unidad_organizacional_mandante_id');
            }
            if ($cuoPadre->dependencia_id) {
                $cuoHijoQuery->where('dependencia_id', $cuoPadre->dependencia_id);
            } else {
                $cuoHijoQuery->whereNull('dependencia_id');
            }
            if ($cuoPadre->numero_contrato) {
                $cuoHijoQuery->where('numero_contrato', $cuoPadre->numero_contrato);
            }
            
            $cuoHijo = $cuoHijoQuery->first();
            
            if ($cuoHijo) {
                // Limpiar configuración del hijo e insertar una copia del padre
                \App\Models\ContratistaUoCargo::where('contratista_uo_id', $cuoHijo->id)->delete();
                
                foreach ($cuoPadre->cargosConfigurados as $cargoPadre) {
                    \App\Models\ContratistaUoCargo::create([
                        'contratista_uo_id' => $cuoHijo->id,
                        'cargo_mandante_id' => $cargoPadre->cargo_mandante_id,
                        'cuota' => null, // La cuota siempre es compartida hacia abajo
                    ]);
                }
                
                // Recursión para sub-sub, sub-sub-sub, etc.
                $this->propagarCargosASubcontratistas($cuoHijo->id);
            }
        }
    }

    public function mount($contratistaId = null)
    {
        $this->tiposEmpresaLegal = TipoEmpresaLegal::where('is_active', true)->orderBy('nombre')->get();
        $this->rubros = Rubro::where('is_active', true)->orderBy('nombre')->get();
        $this->regiones = Region::where('is_active', true)->orderBy('nombre')->get();
        $this->rangosCantidad = RangoCantidadTrabajadores::where('is_active', true)->orderBy('id')->get();
        $this->mutualidades = Mutualidad::where('is_active', true)->orderBy('nombre')->get();
        $this->tiposCondicionDisponibles = []; // Se carga dinámicamente al seleccionar la Principal en el modal
        $this->mandantesDisponibles = Mandante::where('is_active', true)->orderBy('razon_social')->get();

        if ($contratistaId) {
            $this->edit($contratistaId);
            if ($this->readOnly) {
                $this->isOpen = true;
            }
        }
    }

    protected function rules()
    {
        $rules = [
            'razon_social' => 'required|string|min:3|max:255',
            'nombre_fantasia' => 'required|string|max:255',
            'rut_contratista' => ['required', 'string', 'max:12', Rule::unique('contratistas', 'rut')->ignore($this->contratistaId), new ValidarRutRule()],
            'direccion_calle' => 'required|string|max:255',
            'direccion_numero' => 'required|string|max:50',
            'selected_region_id_contratista' => 'required|exists:regiones,id',
            'comuna_id' => 'required|exists:comunas,id',
            'telefono_empresa' => 'required|string|max:20',
            'email_empresa' => ['required', 'email', 'max:255', Rule::unique('contratistas', 'email_empresa')->ignore($this->contratistaId)],
            'tipo_empresa_legal_id' => 'required|exists:tipos_empresa_legal,id',
            'rubro_id' => 'required|exists:rubros,id',
            'tipo_inscripcion' => 'required|in:Contratista,Subcontratista',
            'rango_cantidad_trabajadores_id' => 'required|exists:rangos_cantidad_trabajadores,id',
            'mutualidad_id' => 'required|exists:mutualidades,id',
            'is_active' => 'boolean',
            'rep_legal_nombres' => 'required|string|max:100',
            'rep_legal_apellido_paterno' => 'required|string|max:100',
            'rep_legal_apellido_materno' => 'required|string|max:100',
            'rep_legal_rut' => ['required', 'string', 'max:12', new ValidarRutRule()],
            'rep_legal_telefono' => 'required|string|max:20',
            'rep_legal_email' => 'required|email|max:255',
            'admin_is_active' => 'boolean',
            'admin_name' => 'required|string|max:255',
            'admin_rut_usuario' => ['required', 'string', 'max:12', Rule::unique('users', 'rut')->ignore($this->admin_user_id), new ValidarRutRule()],
            
            // REGLA ACTUALIZADA: Se agregó 'confirmed'
            'admin_email' => ['required', 'email', 'max:255', 'confirmed', Rule::unique('users', 'email')->ignore($this->admin_user_id)],
            
        ];

        if (!$this->contratistaId) {
            $rules['mandante_id_vinculacion'] = 'required|exists:mandantes,id';
        }

        if ($this->crear_nuevo_admin || !$this->admin_user_id || ($this->admin_user_id && !$this->generar_password_auto && !empty($this->admin_password)) || ($this->admin_user_id && $this->generar_password_auto)) {
            if (!$this->generar_password_auto) {
                $rules['admin_password'] = 'required|string|min:8|confirmed';
                $rules['admin_password_confirmation'] = 'required';
            }
        }
        return $rules;
    }

    public function validationAttributes()
    {
        return [
            'razon_social' => 'Razón Social',
            'nombre_fantasia' => 'Nombre Comercial',
            'rut_contratista' => 'NIT Empresa',
            'direccion_calle' => 'Calle',
            'direccion_numero' => 'Número',
            'selected_region_id_contratista' => 'Departamento',
            'comuna_id' => 'Municipio',
            'telefono_empresa' => 'Teléfono Empresa',
            'email_empresa' => 'Email Empresa',
            'tipo_empresa_legal_id' => 'Tipo Empresa Legal',
            'rubro_id' => 'Actividad Económica',
            'rango_cantidad_trabajadores_id' => 'Rango Empleados',
            'mutualidad_id' => 'ARL',
            'rep_legal_nombres' => 'Nombres del Representante Legal',
            'rep_legal_apellido_paterno' => 'Primer Apellido del Representante Legal',
            'rep_legal_apellido_materno' => 'Segundo Apellido del Representante Legal',
            'rep_legal_rut' => 'NIT del Representante Legal',
            'rep_legal_telefono' => 'Teléfono del Representante Legal',
            'rep_legal_email' => 'Email del Representante Legal',
            'admin_name' => 'Nombre Completo del Administrador',
            'admin_rut_usuario' => 'NIT del Administrador',
            'admin_email' => 'Email del Administrador',
            'admin_password' => 'Contraseña del Administrador',
            'mandante_id_vinculacion' => 'Mandante a Vincular',
            'nuevoMandanteId' => 'Nuevo Mandante',
        ];
    }

    public function updated($propertyName)
    {
        if ($propertyName !== 'selectedUnidadesConCondicion' && !Str::startsWith($propertyName, 'selectedUnidadesConCondicion.')) {
            $this->validateOnly($propertyName);
        }
        if (in_array($propertyName, ['search', 'filtroTipo', 'filtroEstado', 'filtroMandante', 'filtroAcredita', 'filtroVerifica', 'filtroTipoContrato'])) {
            $this->resetPage();
        }
    }

    public function updatedSelectedRegionIdContratista($region_id)
    {
        if (!empty($region_id)) {
            $this->comunasDisponiblesContratista = Comuna::where('region_id', $region_id)->where('is_active', true)->orderBy('nombre')->get();
        } else {
            $this->comunasDisponiblesContratista = [];
        }
        $this->comuna_id = null;
    }

    public function create()
    {
        $this->resetInputFields();
        $this->admin_is_active = true;
        $this->is_active = true;
        $this->crear_nuevo_admin = true;
        $this->generar_password_auto = true;
        $this->openModal();
    }

    public function edit($id)
    {
        $contratista = Contratista::with('adminUser', 'comuna.region')->findOrFail($id);
        $this->contratistaId = $id;
        $this->razon_social = $contratista->razon_social;
        $this->nombre_fantasia = $contratista->nombre_fantasia;
        $this->rut_contratista = $contratista->rut;
        $this->direccion_calle = $contratista->direccion_calle;
        $this->direccion_numero = $contratista->direccion_numero;
        $this->selected_region_id_contratista = $contratista->comuna?->region_id;
        $this->updatedSelectedRegionIdContratista($this->selected_region_id_contratista);
        $this->comuna_id = $contratista->comuna_id;
        $this->telefono_empresa = $contratista->telefono_empresa;
        $this->email_empresa = $contratista->email_empresa;
        $this->tipo_empresa_legal_id = $contratista->tipo_empresa_legal_id;
        $this->rubro_id = $contratista->rubro_id;
        $this->is_active = $contratista->is_active;
        $this->tipo_inscripcion = $contratista->tipo_inscripcion;
        $this->rango_cantidad_trabajadores_id = $contratista->rango_cantidad_trabajadores_id;
        $this->mutualidad_id = $contratista->mutualidad_id;
        $this->rep_legal_nombres = $contratista->rep_legal_nombres;
        $this->rep_legal_apellido_paterno = $contratista->rep_legal_apellido_paterno;
        $this->rep_legal_apellido_materno = $contratista->rep_legal_apellido_materno;
        $this->rep_legal_rut = $contratista->rep_legal_rut;
        $this->rep_legal_telefono = $contratista->rep_legal_telefono;
        $this->rep_legal_email = $contratista->rep_legal_email;
        if ($contratista->adminUser) {
            $this->admin_user_id = $contratista->adminUser->id;
            $this->admin_name = $contratista->adminUser->name;
            $this->admin_rut_usuario = $contratista->adminUser->rut;
            $this->admin_email = $contratista->adminUser->email;
            // Pre-llenamos la confirmación para evitar errores si no se cambia el email
            $this->admin_email_confirmation = $contratista->adminUser->email;
            $this->admin_is_active = $contratista->adminUser->is_active;
            $this->crear_nuevo_admin = false;
        } else {
            $this->resetAdminUserFields();
            $this->crear_nuevo_admin = true;
        }
        $this->generar_password_auto = true;
        $this->admin_password = '';
        $this->admin_password_confirmation = '';
        $this->openModal();
    }

    public function store()
    {
        if ($this->readOnly) return;
        if (is_null($this->tipo_inscripcion)) {
            $this->tipo_inscripcion = 'Contratista';
        }
        $rulesForContratista = $this->rules();
        unset($rulesForContratista['selectedUnidadesConCondicion']);
        unset($rulesForContratista['selectedUnidadesConCondicion.*']);
        unset($rulesForContratista['selectedDependencias']);
        $validatedData = $this->validate($rulesForContratista);
        DB::beginTransaction();
        try {
            $user = null;
            $generatedPassword = null;
            $userPassword = '';
            if ($this->crear_nuevo_admin || !$this->admin_user_id) {
                if ($this->generar_password_auto) {
                    $generatedPassword = Str::random(10);
                    $userPassword = $generatedPassword;
                } else {
                    $userPassword = $validatedData['admin_password'];
                }
                $user = User::create(['name' => $validatedData['admin_name'], 'rut' => $validatedData['admin_rut_usuario'], 'email' => $validatedData['admin_email'], 'password' => Hash::make($userPassword), 'is_active' => $validatedData['admin_is_active'], 'user_type' => 'Contratista',]);
                $contratistaAdminRole = Role::where('name', 'Contratista_Admin')->firstOrFail();
                $user->roles()->attach($contratistaAdminRole);
                $this->admin_user_id = $user->id;
            } else {
                $user = User::findOrFail($this->admin_user_id);
                $userDataToUpdate = ['name' => $validatedData['admin_name'], 'rut' => $validatedData['admin_rut_usuario'], 'email' => $validatedData['admin_email'], 'is_active' => $validatedData['admin_is_active'],];
                if ($this->generar_password_auto) {
                    $generatedPassword = Str::random(10);
                    $userDataToUpdate['password'] = Hash::make($generatedPassword);
                } elseif (!empty($validatedData['admin_password'])) {
                    $userDataToUpdate['password'] = Hash::make($validatedData['admin_password']);
                }
                $user->update($userDataToUpdate);
            }
            $dataContratista = [
                'razon_social' => $validatedData['razon_social'], 
                'nombre_fantasia' => $validatedData['nombre_fantasia'], 
                'rut' => $validatedData['rut_contratista'], 
                'direccion_calle' => $validatedData['direccion_calle'], 
                'direccion_numero' => $validatedData['direccion_numero'], 
                'comuna_id' => $validatedData['comuna_id'], 
                'telefono_empresa' => $validatedData['telefono_empresa'], 
                'email_empresa' => $validatedData['email_empresa'], 
                'tipo_empresa_legal_id' => $validatedData['tipo_empresa_legal_id'], 
                'rubro_id' => $validatedData['rubro_id'], 
                'tipo_inscripcion' => $validatedData['tipo_inscripcion'], 
                'rango_cantidad_trabajadores_id' => $validatedData['rango_cantidad_trabajadores_id'] ?? null, 
                'mutualidad_id' => $validatedData['mutualidad_id'] ?? null, 
                'rep_legal_nombres' => $validatedData['rep_legal_nombres'], 
                'rep_legal_apellido_paterno' => $validatedData['rep_legal_apellido_paterno'], 
                'rep_legal_apellido_materno' => $validatedData['rep_legal_apellido_materno'], 
                'rep_legal_rut' => $validatedData['rep_legal_rut'], 
                'rep_legal_telefono' => $validatedData['rep_legal_telefono'], 
                'rep_legal_email' => $validatedData['rep_legal_email'], 
                'is_active' => $validatedData['is_active'], 
                'admin_user_id' => $this->admin_user_id,
            ];
            $contratista = Contratista::updateOrCreate(['id' => $this->contratistaId], $dataContratista);

            if (!$this->contratistaId) {
                SolicitudVinculacion::create([
                    'contratista_id' => $contratista->id,
                    'mandante_id' => $validatedData['mandante_id_vinculacion'],
                    'tipo_solicitud' => 'CONTRATISTA',
                    'estado' => 'APROBADA',
                    'aprobado_por_user_id' => auth()->id(),
                    'fecha_aprobacion' => now(),
                    'contratista_padre_id' => null,
                    'motivo_rechazo' => null,
                ]);
            }

            if ($user && ($user->contratista_id !== $contratista->id)) {
                $user->contratista_id = $contratista->id;
                $user->save();
            }
            DB::commit();
            session()->flash('message', $this->contratistaId ? 'Empresa Contratista Actualizada.' : 'Empresa Contratista Creada y Vinculada.');
            if ($generatedPassword) {
                session()->flash('admin_password_generated', "Se generó una nueva contraseña para el administrador {$user->email}: {$generatedPassword}. Por favor, guárdela en un lugar seguro y entréguela al usuario.");
            }
            $this->closeModal();
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            $errorMessages = [];
            foreach ($e->errors() as $key => $messages) {
                $errorMessages[] = $key . ': ' . implode(', ', $messages);
            }
            if (!empty($errorMessages)) {
                session()->flash('error', 'Error de validación. Por favor revise los campos. (' . implode('; ', $errorMessages) . ')');
            } else {
                session()->flash('error', 'Error de validación. Por favor revise los campos.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Ocurrió un error al guardar la empresa contratista: ' . $e->getMessage());
        }
    }

    public function toggleActive($id)
    {
        $contratista = Contratista::find($id);
        if ($contratista) {
            $nuevoEstado = !$contratista->is_active;
            
            $contratista->is_active = $nuevoEstado;
            $contratista->save();
            
            if ($contratista->adminUser) {
                $contratista->adminUser->is_active = $nuevoEstado;
                $contratista->adminUser->save();
            }

            // Si se desactiva, desactivar recursivamente a todos los descendientes
            if ($nuevoEstado === false) {
                $this->desactivarDescendientes($contratista->id);
            }

            session()->flash('message', 'Estado de la empresa contratista cambiado' . ($nuevoEstado === false ? ' y subcontratistas desactivados en cascada.' : '.'));
        }
    }

    private function desactivarDescendientes($padreId)
    {
        $padre = Contratista::find($padreId);
        if (!$padre) return;

        // Obtener hijos directos aprobados
        $hijos = $padre->subContratistasAprobados()->get();

        foreach ($hijos as $hijo) {
            // Solo desactivar si está activo (para evitar redundancia, aunque save() detecta cambios)
            if ($hijo->is_active) {
                $hijo->is_active = false;
                $hijo->save();

                if ($hijo->adminUser) {
                    $hijo->adminUser->is_active = false;
                    $hijo->adminUser->save();
                }

                // Recursión: Desactivar los hijos de este hijo
                $this->desactivarDescendientes($hijo->id);
            }
        }
    }

    public function toggleAcreditaUo($uoId)
    {
        $vinculacion = ContratistaUnidadOrganizacional::where('id', $uoId)->first();
        if ($vinculacion) {
            $vinculacion->acredita = !$vinculacion->acredita;
            $vinculacion->save();
            session()->flash('message', 'Servicio de Acreditación actualizado para la unidad.');
        }
    }

    public function toggleVerificaUo($uoId)
    {
        $vinculacion = ContratistaUnidadOrganizacional::where('id', $uoId)->first();
        if ($vinculacion) {
            $vinculacion->verifica = !$vinculacion->verifica;
            $vinculacion->save();
            session()->flash('message', 'Servicio de Verificación actualizado para la unidad.');
        }
    }

    public function sortBy($field)
    {
        $allowedSorts = ['contratistas.is_active'];
        if (!in_array($field, $allowedSorts)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }

    public function openModal()
    {
        $this->isOpen = true;
        $this->resetValidation();
    }
    public function closeModal()
    {
        $this->isOpen = false;
        $this->resetInputFields();
        $this->readOnly = false;
    }

    private function resetInputFields()
    {
        $this->contratistaId = null;
        $this->razon_social = '';
        $this->nombre_fantasia = '';
        $this->rut_contratista = '';
        $this->direccion_calle = '';
        $this->direccion_numero = '';
        $this->selected_region_id_contratista = null;
        $this->comuna_id = null;
        $this->comunasDisponiblesContratista = [];
        $this->telefono_empresa = '';
        $this->email_empresa = '';
        $this->tipo_empresa_legal_id = null;
        $this->rubro_id = null;
        $this->is_active = true;
        $this->tipo_inscripcion = 'Contratista';
        $this->rango_cantidad_trabajadores_id = null;
        $this->mutualidad_id = null;
        $this->rep_legal_nombres = '';
        $this->rep_legal_apellido_paterno = '';
        $this->rep_legal_apellido_materno = '';
        $this->rep_legal_rut = '';
        $this->rep_legal_telefono = '';
        $this->rep_legal_email = '';
        $this->mandante_id_vinculacion = null;
        $this->resetAdminUserFields();
        $this->resetValidation();
    }

    private function resetAdminUserFields()
    {
        $this->admin_user_id = null;
        $this->admin_name = '';
        $this->admin_rut_usuario = '';
        $this->admin_email = '';
        $this->admin_email_confirmation = ''; // LIMPIEZA DEL CAMPO
        $this->admin_password = '';
        $this->admin_password_confirmation = '';
        $this->admin_is_active = true;
        $this->crear_nuevo_admin = true;
        $this->generar_password_auto = true;
    }


    public function render()
    {
        $query = $this->buildBaseQuery();
        $query->where('solicitudes_vinculacion.estado', 'APROBADA');
        
        $filtersActive = false;

        if ($this->search) {
            $filtersActive = true;
            $query->where(function ($q) {
                $q->where('contratistas.razon_social', 'like', '%' . $this->search . '%')
                    ->orWhere('contratistas.rut', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filtroMandante !== 'todos') {
            $filtersActive = true;
            $query->where('solicitudes_vinculacion.mandante_id', $this->filtroMandante);
        }

        if ($this->filtroAcredita !== 'todos') {
            $filtersActive = true;
            $query->where('cuo.acredita', $this->filtroAcredita === '1');
        }

        if ($this->filtroVerifica !== 'todos') {
            $filtersActive = true;
            $query->where('cuo.verifica', $this->filtroVerifica === '1');
        }

        // Filtro por número de contrato
        if (!empty($this->filtroContrato)) {
            $filtersActive = true;
            $query->where('cuo.numero_contrato', 'like', '%' . $this->filtroContrato . '%');
        }

        // Filtro por tipo de contrato
        if ($this->filtroTipoContrato !== 'todos') {
            $filtersActive = true;
            $query->where('cuo.tipo_contrato_id', $this->filtroTipoContrato);
        }

        $solicitudes = $query->paginate(50);
        
        // Logica de Expansion: Si hay filtros activos, buscar y agregar descendientes 
        // de los items visibles en la página actual.
        // Logica de Expansion: Si hay filtros activos, buscar y agregar descendientes y PADRES
        // de los items visibles en la página actual.
        if ($filtersActive && $solicitudes->isNotEmpty()) {
             $visibleRows = $solicitudes->getCollection();
             $allExtraRows = collect();

             // A) Fetch Descendants (Para mostrar hijos de los resultados)
             $parentContratistaIds = $visibleRows->pluck('contratista_id')->unique()->filter()->toArray();
             if (!empty($parentContratistaIds)) {
                 $descendantIds = $this->getRecursivelyDescendants($parentContratistaIds);
                 $currentIds = $visibleRows->pluck('id')->toArray();
                 $idsFetch = array_diff($descendantIds, $currentIds);

                 if (!empty($idsFetch)) {
                    $descendants = $this->buildBaseQuery()
                        ->where('solicitudes_vinculacion.estado', 'APROBADA')
                        ->whereIn('solicitudes_vinculacion.id', $idsFetch)
                        ->get();
                    if ($descendants->isNotEmpty()) {
                        $allExtraRows = $allExtraRows->merge($descendants);
                    }
                 }
             }


             // B) Fetch Descendants that matched the filter but parent didn't
             // Solo agregar descendants si los hay, no modificar los padres
             // Los padres ya deberían estar en visibleRows si coinciden con el filtro

             if ($allExtraRows->isNotEmpty()) {
                 // IMPORTANTE: usar concat() en lugar de merge() porque merge() sobrescribe por key numérica
                 $merged = $visibleRows->concat($allExtraRows)->unique(function ($item) {
                     return $item->id . '-' . ($item->pivot_id ?? 'root');
                 });
                 $solicitudes->setCollection($merged);
             }
        }
        
        // Procesar resultados para agregar información jerárquica
        $solicitudes->getCollection()->transform(function ($item) {
            $item->nivel_jerarquia = $this->calcularNivelJerarquia($item->contratista_id);
            $item->cadena_ancestros = $this->obtenerCadenaAncestros($item->contratista_id);
            return $item;
        });
        
        // Ordenar jerárquicamente (padres primero, luego hijos)
        $ordenados = $this->ordenarJerarquicamente($solicitudes->getCollection());

        // --- AGREGAR CONTEO DE TRABAJADORES ---
        $ordenados->transform(function ($item) {
            $mandanteId = $item->mandante_id;
            
            // 1. Trabajadores Propios
            $qPropios = \App\Models\TrabajadorVinculacion::whereHas('trabajador', function($q) use ($item) {
                $q->where('contratista_id', $item->contratista_id);
            })->where('is_active', true);
            
            if ($item->uo_row_id) { $qPropios->where('unidad_organizacional_mandante_id', $item->uo_row_id); } else { $qPropios->whereNull('unidad_organizacional_mandante_id'); }
            if ($item->dep_row_id) { $qPropios->where('dependencia_id', $item->dep_row_id); } else { $qPropios->whereNull('dependencia_id'); }
            if ($item->pivot_numero_contrato) { $qPropios->where('numero_contrato', $item->pivot_numero_contrato); } else { $qPropios->whereNull('numero_contrato'); }
            
            $item->count_trabajadores_propios = $qPropios->count();

            // 2. Si es Principal, Trabajadores Familia
            if ($item->nivel_jerarquia == 0) {
                $familyIds = [$item->contratista_id];
                $lvl1 = \App\Models\SolicitudVinculacion::where('contratista_padre_id', $item->contratista_id)->where('mandante_id', $mandanteId)->where('estado', 'APROBADA')->pluck('contratista_id')->toArray();
                $familyIds = array_merge($familyIds, $lvl1);
                if (!empty($lvl1)) {
                    $lvl2 = \App\Models\SolicitudVinculacion::whereIn('contratista_padre_id', $lvl1)->where('mandante_id', $mandanteId)->where('estado', 'APROBADA')->pluck('contratista_id')->toArray();
                    $familyIds = array_merge($familyIds, $lvl2);
                    if (!empty($lvl2)) {
                        $lvl3 = \App\Models\SolicitudVinculacion::whereIn('contratista_padre_id', $lvl2)->where('mandante_id', $mandanteId)->where('estado', 'APROBADA')->pluck('contratista_id')->toArray();
                        $familyIds = array_merge($familyIds, $lvl3);
                    }
                }
                
                $qFamilia = \App\Models\TrabajadorVinculacion::whereHas('trabajador', function($q) use ($familyIds) {
                    $q->whereIn('contratista_id', $familyIds);
                })->where('is_active', true);
                
                if ($item->uo_row_id) { $qFamilia->where('unidad_organizacional_mandante_id', $item->uo_row_id); } else { $qFamilia->whereNull('unidad_organizacional_mandante_id'); }
                if ($item->dep_row_id) { $qFamilia->where('dependencia_id', $item->dep_row_id); } else { $qFamilia->whereNull('dependencia_id'); }
                if ($item->pivot_numero_contrato) { $qFamilia->where('numero_contrato', $item->pivot_numero_contrato); } else { $qFamilia->whereNull('numero_contrato'); }
                
                $item->count_trabajadores_familia = $qFamilia->count();
            }

            return $item;
        });

        $solicitudes->setCollection($ordenados);

        return view('livewire.gestion-contratistas', [
            'solicitudes' => $solicitudes,
            'tiposCondicion' => $this->tiposCondicionDisponibles,
            'mandantesAprobados' => $this->getMandantesAprobadosParaModal(),
            'tiposContrato' => \App\Models\TipoContrato::where('is_active', true)->orderBy('nombre')->get(),
        ]);
    }

    public function limpiarFiltros()
    {
        $this->search = '';
        $this->filtroTipo = 'todos';
        $this->filtroEstado = 'activos';
        $this->filtroMandante = 'todos';
        $this->filtroAcredita = 'todos';
        $this->filtroVerifica = 'todos';
        $this->filtroContrato = '';
        $this->filtroTipoContrato = 'todos';
        $this->columnasExcluidas = ['id_bd'];
        $this->resetPage();
    }

    /**
     * Genera un ID de registro automático secuencial.
     */
    protected function generarIdRegistroAutomatico(): string
    {
        $ultimoId = ContratistaUnidadOrganizacional::whereNotNull('id_registro')
            ->where('id_registro', 'regexp', '^[0-9]+$')
            ->orderByRaw('CAST(id_registro AS UNSIGNED) DESC')
            ->value('id_registro');

        $siguiente = $ultimoId ? (intval($ultimoId) + 1) : 1;
        return (string)$siguiente;
    }

    protected function getMandantesAprobadosParaModal()
    {
        if (!$this->contratistaVinculacionesId) return [];
        $contratista = Contratista::find($this->contratistaVinculacionesId);
        if (!$contratista) return [];

        return $contratista->mandantesAprobados()->with(['unidadesOrganizacionales', 'dependencias'])->get();
    }



    public function abrirModalAsignarMandante($contratistaId)
    {
        $contratista = Contratista::with('solicitudesVinculacion')->find($contratistaId);
        if (!$contratista) {
            session()->flash('error', 'Contratista no encontrado.');
            return;
        }

        $this->contratistaParaAsignar_id = $contratista->id;
        $this->nombreContratistaParaAsignar = $contratista->razon_social;
        
        $mandantesVinculadosIds = $contratista->solicitudesVinculacion->pluck('mandante_id')->unique()->toArray();
        
        $this->mandantesParaAsignar = Mandante::where('is_active', true)
                                            ->whereNotIn('id', $mandantesVinculadosIds)
                                            ->orderBy('razon_social')
                                            ->get();

        $this->showModalAsignarMandante = true;
        $this->resetValidation();
    }

    public function cerrarModalAsignarMandante()
    {
        $this->showModalAsignarMandante = false;
        $this->contratistaParaAsignar_id = null;
        $this->nombreContratistaParaAsignar = '';
        $this->mandantesParaAsignar = [];
        $this->nuevoMandanteId = null;
        $this->resetValidation();
    }

    public function guardarAsignacionMandante()
    {
        $this->validate(['nuevoMandanteId' => 'required|exists:mandantes,id']);

        SolicitudVinculacion::create([
            'contratista_id' => $this->contratistaParaAsignar_id,
            'mandante_id' => $this->nuevoMandanteId,
            'tipo_solicitud' => 'CONTRATISTA',
            'estado' => 'APROBADA',
            'aprobado_por_user_id' => auth()->id(),
            'fecha_aprobacion' => now(),
        ]);

        session()->flash('message', 'Nueva vinculación con mandante asignada correctamente.');
        $this->cerrarModalAsignarMandante();
    }

    /**
     * Calcula el nivel jerárquico de un contratista (0 = principal, 1 = sub, 2 = sub-sub, etc.)
     */
    protected function calcularNivelJerarquia($contratistaId): int
    {
        $nivel = 0;
        $solicitud = SolicitudVinculacion::where('contratista_id', $contratistaId)
            ->where('estado', 'APROBADA')
            ->first();
        
        while ($solicitud && $solicitud->contratista_padre_id) {
            $nivel++;
            $solicitud = SolicitudVinculacion::where('contratista_id', $solicitud->contratista_padre_id)
                ->where('estado', 'APROBADA')
                ->first();
        }
        
        return $nivel;
    }

    /**
     * Obtiene la cadena de ancestros para mostrar en la columna Tipo
     * Ejemplo: "Sub → MADESUN SA" o "Sub-Sub → SUB_MADE → MADESUN SA"
     */
    protected function obtenerCadenaAncestros($contratistaId): string
    {
        $ancestros = [];
        $solicitud = SolicitudVinculacion::where('contratista_id', $contratistaId)
            ->where('estado', 'APROBADA')
            ->first();
        
        if (!$solicitud || !$solicitud->contratista_padre_id) {
            return 'Contratista';
        }
        
        // Recorrer hacia arriba
        $padreId = $solicitud->contratista_padre_id;
        while ($padreId) {
            $padre = Contratista::find($padreId);
            if ($padre) {
                $ancestros[] = $padre->razon_social;
            }
            $solPadre = SolicitudVinculacion::where('contratista_id', $padreId)
                ->where('estado', 'APROBADA')
                ->first();
            $padreId = $solPadre ? $solPadre->contratista_padre_id : null;
        }
        
        $nivel = count($ancestros);
        $prefijo = match($nivel) {
            1 => 'Sub',
            2 => 'Sub-Sub',
            3 => 'Sub-Sub-Sub',
            default => 'Sub (Nivel ' . $nivel . ')'
        };
        
        return $prefijo . ' → ' . implode(' → ', $ancestros);
    }

    /**
     * Ordena la colección para que los hijos aparezcan debajo de sus padres
     * y asigna correlativos jerárquicos (1, 1.1, 1.1.1, etc.)
     */
    protected function ordenarJerarquicamente($collection)
    {
        // 1. Indexar todos los items por su contratista_id (para búsqueda rápida de padres candidatos)
        // Un contratista_id puede tener múltiples filas (diferentes contratos/pivots)
        $itemsPorContratistaId = $collection->groupBy('contratista_id');
        
        // Inicializar propiedad de hijos temporales en todos los items
        foreach ($collection as $item) {
            $item->temporal_children = collect();
            $item->is_attached_to_parent = false;
        }

        // 2. Asignar cada hijo a su mejor padre candidato
        foreach ($collection as $child) {
            // Si no tiene padre, es raíz natural, saltar
            if (empty($child->contratista_padre_id)) {
                continue;
            }

            // Buscar candidatos a padre
            $candidatos = $itemsPorContratistaId->get($child->contratista_padre_id);

            if (!$candidatos || $candidatos->isEmpty()) {
                // El padre no está en la lista (probablemente paginación), se tratará como raíz
                continue;
            }

            // Encontrar el mejor candidato basado en coincidencia de datos
            $mejorPadre = null;
            $mejorPuntaje = -1;

            foreach ($candidatos as $padre) {
                $puntaje = 0;
                
                // Coincidencia de UO (Muy importante)
                if ($padre->uo_row_id == $child->uo_row_id) {
                    $puntaje += 10;
                } elseif ($padre->uo_row_id && $child->uo_row_id) {
                    // Si ambos tienen UO pero son distintas, penalizar fuertemente
                    $puntaje -= 50; 
                }

                // Coincidencia de Lugar/Dependencia
                if ($padre->dep_row_id == $child->dep_row_id) {
                    $puntaje += 10;
                } elseif ($padre->dep_row_id && $child->dep_row_id) {
                    // Si ambos tienen Lugar pero son distintos, penalizar
                    $puntaje -= 20;
                }

                // Coincidencia de N° Contrato (Bonus específico solicitado por usuario)
                // Si el N° de contrato coincide (aunque usualmente difieren), es un link fuerte
                // Coincidencia de N° Contrato
                if ($child->pivot_numero_contrato && $padre->pivot_numero_contrato) {
                     if ($child->pivot_numero_contrato == $padre->pivot_numero_contrato) {
                        $puntaje += 50; // Match fuerte
                     } else {
                        $puntaje -= 100; // Mismatch fuerte: distintos contratos = distinta vinculación
                     }
                }

                if ($puntaje > $mejorPuntaje) {
                    $mejorPuntaje = $puntaje;
                    $mejorPadre = $padre;
                }
            }

            // Asignar al mejor padre encontrado
            if ($mejorPadre && $mejorPuntaje > -50) { // Umbral mínimo para evitar asignaciones absurdas
                $mejorPadre->temporal_children->push($child);
                $child->is_attached_to_parent = true;
            }
        }

        // 3. Construir la lista plana recursivamente
        $resultado = collect();

        // Función recursiva para aplanar
        $aplanarArbol = function($items, $prefijo = '') use (&$aplanarArbol, &$resultado) {
            $contador = 1;
            foreach ($items as $item) {
                // Asignar correlativo
                $item->correlativo_jerarquico = $prefijo === '' ? (string)$contador : "$prefijo.$contador";
                $resultado->push($item);

                // Procesar hijos asignados
                if ($item->temporal_children->isNotEmpty()) {
                    $aplanarArbol($item->temporal_children, $item->correlativo_jerarquico);
                }
                $contador++;
            }
        };

        // Identificar raíces (aquellos que no fueron adjuntados a ningún padre)
        // Esto incluye: 
        // a) Items con padre_id null 
        // b) Items con padre_id que no estaba en la lista
        // c) Items que fueron rechazados por todos los candidatos (orphan por mismatch)
        $raices = $collection->filter(function($item) {
            return !$item->is_attached_to_parent;
        });

        // Ordenar raíces si es necesario? (Ya vienen ordenadas por la query normalmente)
        
        $aplanarArbol($raices);

        return $resultado;
    }

    /**
     * Construye la query base utilizada en render(), con joins y selects pero SIN filtros dinámicos.
     */
    private function buildBaseQuery()
    {
        return \App\Models\SolicitudVinculacion::query()
            ->from('solicitudes_vinculacion')
            ->join('contratistas', 'solicitudes_vinculacion.contratista_id', '=', 'contratistas.id')
            ->leftJoin('contratista_unidad_organizacional as cuo', 'contratistas.id', '=', 'cuo.contratista_id')
            ->leftJoin('unidades_organizacionales_mandante as uo_join', 'cuo.unidad_organizacional_mandante_id', '=', 'uo_join.id')
            ->leftJoin('dependencias as dep_join', 'cuo.dependencia_id', '=', 'dep_join.id')
            ->select(
                'solicitudes_vinculacion.id',
                'solicitudes_vinculacion.contratista_id',
                'solicitudes_vinculacion.mandante_id',
                'solicitudes_vinculacion.estado',
                'contratistas.razon_social',
                'contratistas.rut',
                'cuo.id as pivot_id',
                'cuo.acredita as pivot_acredita',
                'cuo.fecha_inicio_acredita as pivot_fecha_inicio_acredita',
                'cuo.fecha_fin_acredita as pivot_fecha_fin_acredita',
                'cuo.verifica as pivot_verifica',
                'cuo.fecha_inicio_verifica as pivot_fecha_inicio_verifica',
                'cuo.fecha_fin_verifica as pivot_fecha_fin_verifica',
                'cuo.tipo_condicion_id as pivot_condicion_id',
                'cuo.unidad_organizacional_mandante_id as uo_row_id',
                'cuo.dependencia_id as dep_row_id',
                'cuo.numero_contrato as pivot_numero_contrato',
                'cuo.tipo_contrato_id as pivot_tipo_contrato_id',
                'cuo.trabajadores_cuota as pivot_trabajadores_cuota',
                'cuo.id_registro as pivot_id_registro',
                'cuo.sap as pivot_sap',
                'solicitudes_vinculacion.contratista_padre_id'
            )
            ->where(function($q) {
                $q->whereNull('cuo.id')
                  ->orWhere(function($sq) {
                      $sq->whereNotNull('cuo.unidad_organizacional_mandante_id')
                         ->whereColumn('uo_join.mandante_id', 'solicitudes_vinculacion.mandante_id');
                  })
                  ->orWhere(function($sq) {
                      $sq->whereNotNull('cuo.dependencia_id')
                         ->whereColumn('dep_join.mandante_id', 'solicitudes_vinculacion.mandante_id');
                  });
            })
            ->groupBy(
                'solicitudes_vinculacion.id',
                'solicitudes_vinculacion.contratista_id',
                'solicitudes_vinculacion.mandante_id',
                'solicitudes_vinculacion.estado',
                'contratistas.razon_social',
                'contratistas.rut',
                'cuo.id',
                'cuo.acredita',
                'cuo.fecha_inicio_acredita',
                'cuo.fecha_fin_acredita',
                'cuo.verifica',
                'cuo.fecha_inicio_verifica',
                'cuo.fecha_fin_verifica',
                'cuo.tipo_condicion_id',
                'cuo.unidad_organizacional_mandante_id',
                'cuo.dependencia_id',
                'cuo.numero_contrato',
                'cuo.tipo_contrato_id',
                'cuo.trabajadores_cuota',
                'cuo.id_registro',
                'cuo.sap',
                'solicitudes_vinculacion.contratista_padre_id'
            );
    }

    /**
     * Obtiene los IDs de solicitudes de descendientes (hijos, nietos) para un conjunto de contratistas padres.
     */
    private function getRecursivelyDescendants($parentContratistaIds)
    {
        $allDescendantSolicitudIds = [];
        $currentParentIds = collect($parentContratistaIds)->unique()->filter()->toArray();

        // 3 niveles de profundidad arbitrarios
        for ($i = 0; $i < 4; $i++) {
            if (empty($currentParentIds)) break;

            $children = \App\Models\SolicitudVinculacion::whereIn('contratista_padre_id', $currentParentIds)
                ->where('estado', 'APROBADA')
                ->get(['id', 'contratista_id']); 
            
            if ($children->isEmpty()) break;

            $newSolicitudIds = $children->pluck('id')->toArray();
            $newContratistaIds = $children->pluck('contratista_id')->toArray();

            $allDescendantSolicitudIds = array_merge($allDescendantSolicitudIds, $newSolicitudIds);
            
            $currentParentIds = $newContratistaIds;
        }

        return array_unique($allDescendantSolicitudIds);
    }

    /**
     * Query SIN restricción de mandante - para expandir y mostrar todas las vinculaciones de un contratista.
     */
    private function buildUnrestrictedQuery()
    {
        return \App\Models\SolicitudVinculacion::query()
            ->from('solicitudes_vinculacion')
            ->join('contratistas', 'solicitudes_vinculacion.contratista_id', '=', 'contratistas.id')
            ->leftJoin('contratista_unidad_organizacional as cuo', 'contratistas.id', '=', 'cuo.contratista_id')
            ->leftJoin('unidades_organizacionales_mandante as uo_join', 'cuo.unidad_organizacional_mandante_id', '=', 'uo_join.id')
            ->leftJoin('dependencias as dep_join', 'cuo.dependencia_id', '=', 'dep_join.id')
            ->select(
                'solicitudes_vinculacion.id',
                'solicitudes_vinculacion.contratista_id',
                'solicitudes_vinculacion.mandante_id',
                'solicitudes_vinculacion.estado',
                'contratistas.razon_social',
                'contratistas.rut',
                'cuo.id as pivot_id',
                'cuo.acredita as pivot_acredita',
                'cuo.fecha_inicio_acredita as pivot_fecha_inicio_acredita',
                'cuo.fecha_fin_acredita as pivot_fecha_fin_acredita',
                'cuo.verifica as pivot_verifica',
                'cuo.fecha_inicio_verifica as pivot_fecha_inicio_verifica',
                'cuo.fecha_fin_verifica as pivot_fecha_fin_verifica',
                'cuo.tipo_condicion_id as pivot_condicion_id',
                'cuo.unidad_organizacional_mandante_id as uo_row_id',
                'cuo.dependencia_id as dep_row_id',
                'cuo.numero_contrato as pivot_numero_contrato',
                'cuo.tipo_contrato_id as pivot_tipo_contrato_id',
                'cuo.trabajadores_cuota as pivot_trabajadores_cuota',
                'cuo.id_registro as pivot_id_registro',
                'cuo.sap as pivot_sap',
                'solicitudes_vinculacion.contratista_padre_id'
            )
            // SIN restricción de mandante - trae todas las CUOs
            ->groupBy(
                'solicitudes_vinculacion.id',
                'solicitudes_vinculacion.contratista_id',
                'solicitudes_vinculacion.mandante_id',
                'solicitudes_vinculacion.estado',
                'contratistas.razon_social',
                'contratistas.rut',
                'cuo.id',
                'cuo.acredita',
                'cuo.fecha_inicio_acredita',
                'cuo.fecha_fin_acredita',
                'cuo.verifica',
                'cuo.fecha_inicio_verifica',
                'cuo.fecha_fin_verifica',
                'cuo.tipo_condicion_id',
                'cuo.unidad_organizacional_mandante_id',
                'cuo.dependencia_id',
                'cuo.numero_contrato',
                'cuo.tipo_contrato_id',
                'cuo.trabajadores_cuota',
                'cuo.id_registro',
                'cuo.sap',
                'solicitudes_vinculacion.contratista_padre_id'
            );
    }
}

