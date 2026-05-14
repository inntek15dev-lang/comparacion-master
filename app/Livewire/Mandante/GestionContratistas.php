<?php

namespace App\Livewire\Mandante;

use App\Livewire\GestionContratistas as BaseGestionContratistas;
use Illuminate\Support\Facades\Auth;
use App\Models\SolicitudVinculacion;
use App\Models\Contratista;
use App\Models\Mandante;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use App\Models\ContratistaUnidadOrganizacional;
use Illuminate\Validation\Rule;
use App\Rules\ValidarRutRule;

class GestionContratistas extends BaseGestionContratistas
{
    // Propiedad para la confirmación del email (necesaria para la validación 'confirmed')
    public ?string $admin_email_confirmation = '';

    // Filtro por tipo de contrato (se define aquí para que sea accesible en la vista)
    public $filtroTipoContrato = 'todos';

    // Propiedad para controlar acceso de solo lectura (Mandante_Ver)
    public bool $esSoloLectura = false;

    /**
     * Sobrescribimos el método mount para ajustar las propiedades heredadas
     * al contexto del Mandante_Admin.
     */
    public function mount($contratistaId = null)
    {
        // Ejecutamos el mount del componente padre para inicializar todo.
        parent::mount($contratistaId);

        $user = Auth::user();

        // Determinar si es acceso de solo lectura (Mandante_Ver)
        $this->esSoloLectura = $user->hasRole('Mandante_Ver');

        if ($user && $user->mandante_id) {
            // Sobrescribimos la lista de mandantes disponibles.
            // Ahora solo contendrá el mandante del usuario actual.
            $this->mandantesDisponibles = Mandante::where('id', $user->mandante_id)->get();

            // Forzamos el valor del filtro para que coincida con el mandante del usuario.
            $this->filtroMandante = $user->mandante_id;
        }
        else {
            // Si no hay mandante, la lista estará vacía.
            $this->mandantesDisponibles = collect();
        }
    }

    /**
     * Sobrescribimos las reglas de validación para incluir la confirmación de email.
     */
    protected function rules()
    {
        $rules = parent::rules();

        // Actualizamos la regla para admin_email agregando 'confirmed'
        $rules['admin_email'] = [
            'required',
            'email',
            'max:255',
            'confirmed', // Regla de confirmación
            Rule::unique('users', 'email')->ignore($this->admin_user_id)
        ];

        return $rules;
    }

    /**
     * Sobrescribimos el método edit para pre-llenar la confirmación de email.
     */
    public function edit($id)
    {
        parent::edit($id);

        // Si hay un usuario administrador cargado, pre-llenamos la confirmación
        if ($this->admin_user_id) {
            $this->admin_email_confirmation = $this->admin_email;
        }
    }

    /**
     * Sobrescribimos el método para limpiar campos, incluyendo la confirmación.
     */
    public function create()
    {
        parent::create();
        $this->admin_email_confirmation = '';
    }

    // ================== INICIO DE LA CORRECCIÓN DE SEGURIDAD ==================

    /**
     * Sobrescribe el método del padre para restringir la gestión de vinculaciones
     * únicamente al mandante del usuario actual.
     */
    public function abrirModalVinculaciones($contratistaId, $pivotId = null)
    {
        $user = Auth::user();
        // Permitir acceso a Mandante_Admin y Mandante_Ver
        if (!$user->hasAnyRole(['Mandante_Admin', 'Mandante_Ver']) || !$user->mandante_id) {
            session()->flash('error', 'Acción no autorizada.');
            return;
        }

        $contratista = Contratista::with(['solicitudesVinculacion', 'contratistaPadreAprobado'])->find($contratistaId);
        if (!$contratista) {
            session()->flash('error', 'Contratista no encontrado.');
            return;
        }

        $this->contratistaVinculacionesId = $contratista->id;
        $this->nombreContratistaVinculaciones = $contratista->razon_social;
        $this->selectedPivotId = $pivotId; // Guardar el ID seleccionado

        // Detectar si es subcontratista (tiene padre aprobado)
        $padre = $contratista->contratistaPadreAprobado->first();
        $this->isSubcontractorMode = (bool)$padre;

        // Cargar opciones del padre si aplica
        if ($this->isSubcontractorMode && $padre) {
             $vinculosPadre = ContratistaUnidadOrganizacional::where('contratista_id', $padre->id)->get();
             $this->dependenciasPadresPermitidas = $vinculosPadre->pluck('dependencia_id')->filter()->unique()->values()->toArray();
             $this->uosPadresPermitidas = $vinculosPadre->pluck('unidad_organizacional_mandante_id')->filter()->unique()->values()->toArray();
             $this->contratosPadresPermitidos = $vinculosPadre->pluck('numero_contrato')->filter()->unique()->values()->toArray();
        }

        $this->vinculacionesTemp = [];

        if ($pivotId) {
            // Cargar vinculación específica (Validando que pertenezca al mandante)
            $v = ContratistaUnidadOrganizacional::where('id', $pivotId)
                ->where('contratista_id', $contratistaId)
                ->where(function ($q) use ($user) {
                    $q->whereHas('unidadOrganizacional', function ($uq) use ($user) {
                        $uq->where('mandante_id', $user->mandante_id);
                    })
                    ->orWhereHas('dependencia', function ($dq) use ($user) {
                        $dq->where('mandante_id', $user->mandante_id);
                    });
                })->first();

            if ($v) {
                $this->vinculacionesTemp[] = [
                    'id' => $v->id,
                    'mandante_id' => $user->mandante_id,
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
                    'cargos_config' => $v->cargosConfigurados->map(function($c) {
                        return [
                            'cargo_id' => $c->cargo_mandante_id,
                            'selected' => true, 
                            'cuota' => $c->cuota
                        ];
                    })->toArray(),
                ];
            }
        }

        if (empty($this->vinculacionesTemp)) {
            $this->agregarFilaVinculacion();
        }

        // Cargar las condiciones de empresa correspondientes a este mandante
        $this->_cargarCondicionesPorMandante($user->mandante_id);

        $this->showModalVinculaciones = true;
    }

    /**
     * Sobrescribe para forzar el mandante_id del usuario actual en la fila nueva.
     */
    public function agregarFilaVinculacion($padre = null)
    {
        $user = Auth::user();
        $this->vinculacionesTemp[] = [
            'id' => null,
            'mandante_id' => $user->mandante_id ?? null,
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
            'cargos_config' => [],
        ];
    }

    // ================== FIN DE LA CORRECCIÓN DE SEGURIDAD ====================

    /**
     * Sobrescribimos el método render para mostrar una fila por cada vinculación (Lugar/UO),
     * igual que la estructura de ASEM, pero filtrado por el mandante_id del usuario logueado.
     */
    public function render()
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['Mandante_Admin', 'Mandante_Ver']) || !$user->mandante_id) {
            $paginator = new LengthAwarePaginator([], 0, 50);
            return view('livewire.mandante.gestion-contratistas', [
                'solicitudes' => $paginator,
                'tiposCondicion' => $this->tiposCondicionDisponibles,
                'tiposContrato' => \App\Models\TipoContrato::where('is_active', true)->orderBy('nombre')->get(),
            ])->layout('layouts.app');
        }

        $mandanteId = $user->mandante_id;

        $query = SolicitudVinculacion::query()
            ->from('solicitudes_vinculacion')
            ->join('contratistas', 'solicitudes_vinculacion.contratista_id', '=', 'contratistas.id')
            ->leftJoin('contratista_unidad_organizacional as cuo', 'contratistas.id', '=', 'cuo.contratista_id')
            ->leftJoin('unidades_organizacionales_mandante as uo_join', 'cuo.unidad_organizacional_mandante_id', '=', 'uo_join.id')
            ->leftJoin('dependencias as dep_join', 'cuo.dependencia_id', '=', 'dep_join.id')
            ->leftJoin('users', 'contratistas.admin_user_id', '=', 'users.id')
            ->select(
            'solicitudes_vinculacion.id',
            'solicitudes_vinculacion.contratista_id',
            'solicitudes_vinculacion.mandante_id',
            'solicitudes_vinculacion.estado',
            'contratistas.razon_social',
            'contratistas.rut',
            'contratistas.tipo_inscripcion',
            'contratistas.is_active',
            'users.name as admin_name',
            'users.email as admin_email',
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
            'uo_join.nombre_unidad as uo_nombre',
            'dep_join.nombre as dep_nombre',
            'cuo.numero_contrato as pivot_numero_contrato',
            'cuo.tipo_contrato_id as pivot_tipo_contrato_id',
            'cuo.sap as pivot_sap',
            'cuo.trabajadores_cuota as pivot_trabajadores_cuota',
            'cuo.id_registro as pivot_id_registro',
            'solicitudes_vinculacion.contratista_padre_id'
        )
            ->where('solicitudes_vinculacion.estado', 'APROBADA')
            ->where('solicitudes_vinculacion.mandante_id', $mandanteId)
            // Sub-contratistas también deben ser visibles para el Mandante
            ->where(function ($q) use ($mandanteId) {
            $q->whereNull('cuo.id')
                ->orWhere(function ($sq) use ($mandanteId) {
                $sq->whereNotNull('cuo.unidad_organizacional_mandante_id')
                    ->where('uo_join.mandante_id', $mandanteId);
            }
            )
                ->orWhere(function ($sq) use ($mandanteId) {
                $sq->whereNotNull('cuo.dependencia_id')
                    ->where('dep_join.mandante_id', $mandanteId);
            }
            );
        })
            ->groupBy(
            'solicitudes_vinculacion.id',
            'solicitudes_vinculacion.contratista_id',
            'solicitudes_vinculacion.mandante_id',
            'solicitudes_vinculacion.estado',
            'contratistas.razon_social',
            'contratistas.rut',
            'contratistas.tipo_inscripcion',
            'contratistas.is_active',
            'users.name',
            'users.email',
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
            'uo_join.nombre_unidad',
            'dep_join.nombre',
            'cuo.numero_contrato',
            'cuo.tipo_contrato_id',
            'cuo.sap',
            'cuo.trabajadores_cuota',
            'cuo.id_registro',
            'solicitudes_vinculacion.contratista_padre_id'
        );

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('contratistas.razon_social', 'like', '%' . $this->search . '%')
                    ->orWhere('contratistas.rut', 'like', '%' . $this->search . '%')
                    ->orWhere('users.name', 'like', '%' . $this->search . '%')
                    ->orWhere('users.email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filtroTipo !== 'todos') {
            $query->where('contratistas.tipo_inscripcion', $this->filtroTipo);
        }

        if ($this->filtroEstado !== 'todos') {
            $estadoBool = $this->filtroEstado === 'activos';
            $query->where('contratistas.is_active', $estadoBool);
        }

        if ($this->filtroAcredita !== 'todos') {
            $acreditaBool = $this->filtroAcredita === 'si';
            $query->where('cuo.acredita', $acreditaBool);
        }

        if ($this->filtroVerifica !== 'todos') {
            $verificaBool = $this->filtroVerifica === 'si';
            $query->where('cuo.verifica', $verificaBool);
        }

        // Filtro por número de contrato
        if (!empty($this->filtroContrato)) {
            $query->where('cuo.numero_contrato', 'like', '%' . $this->filtroContrato . '%');
        }

        // Filtro por tipo de contrato
        if ($this->filtroTipoContrato !== 'todos') {
            $query->where('cuo.tipo_contrato_id', $this->filtroTipoContrato);
        }

        $solicitudes = $query->paginate(50);

        // Cargar relaciones adicionales para la vista
        $contratistaIds = $solicitudes->pluck('contratista_id')->unique();
        $contratistas = Contratista::whereIn('id', $contratistaIds)
            ->with(['adminUser', 'unidadesOrganizacionalesMandante', 'dependencias'])
            ->get()
            ->keyBy('id');

        // Adjuntar el contratista completo a cada solicitud
        $solicitudes->getCollection()->transform(function ($item) use ($contratistas) {
            $item->contratista = $contratistas->get($item->contratista_id);
            $item->nivel_jerarquia = $this->calcularNivelJerarquia($item->contratista_id);
            $item->cadena_ancestros = $this->obtenerCadenaAncestros($item->contratista_id);
            return $item;
        });

        // Ordenar jerárquicamente (padres primero, luego hijos)
        $ordenados = $this->ordenarJerarquicamente($solicitudes->getCollection());

        // --- AGREGAR CONTEO DE TRABAJADORES ---
        $ordenados->transform(function ($item) {
            $mId = $item->mandante_id;
            
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
                $lvl1 = \App\Models\SolicitudVinculacion::where('contratista_padre_id', $item->contratista_id)->where('mandante_id', $mId)->where('estado', 'APROBADA')->pluck('contratista_id')->toArray();
                $familyIds = array_merge($familyIds, $lvl1);
                if (!empty($lvl1)) {
                    $lvl2 = \App\Models\SolicitudVinculacion::whereIn('contratista_padre_id', $lvl1)->where('mandante_id', $mId)->where('estado', 'APROBADA')->pluck('contratista_id')->toArray();
                    $familyIds = array_merge($familyIds, $lvl2);
                    if (!empty($lvl2)) {
                        $lvl3 = \App\Models\SolicitudVinculacion::whereIn('contratista_padre_id', $lvl2)->where('mandante_id', $mId)->where('estado', 'APROBADA')->pluck('contratista_id')->toArray();
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

        return view('livewire.mandante.gestion-contratistas', [
            'solicitudes' => $solicitudes,
            'tiposCondicion' => $this->tiposCondicionDisponibles,
            'mandantesDisponibles' => $this->mandantesDisponibles,
            'tiposContrato' => \App\Models\TipoContrato::where('is_active', true)->orderBy('nombre')->get(),
        ])->layout('layouts.app');
    }

    /**
     * Sobrescribimos el método store para asegurar que al crear un nuevo contratista,
     * se vincule automáticamente al mandante del usuario actual.
     */
    public function store()
    {
        $user = Auth::user();
        if ($user->hasRole('Mandante_Admin') && $user->mandante_id) {
            $this->mandante_id_vinculacion = $user->mandante_id;
        }

        parent::store();
    }
}