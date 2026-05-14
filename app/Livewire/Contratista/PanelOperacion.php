<?php

namespace App\Livewire\Contratista;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Contratista;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Livewire\WithFileUploads;
use App\Models\DocumentoCargado;
use App\Models\ReglaDocumental;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\DocumentoRequeridoService; 
use App\Services\CriticidadDocumentoService; 
use App\Models\Dependencia;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\Mandante;
use App\Models\TrabajadorVinculacion;
use App\Models\VehiculoAsignacion;
use App\Models\MaquinariaAsignacion;
use App\Models\EmbarcacionAsignacion;
use App\Models\TipoEntidadControlable;

class PanelOperacion extends Component
{
    use WithFileUploads;

    public ?int $contratistaIdForzado = null;
    public ?int $mandanteIdForzado = null;

    // Parámetros de preselección (vienen del componente padre cuando se navega desde Supervisión)
    public $preselectedLugar = null;
    public $preselectedUo = null;
    public $preselectedContrato = null;

    public ?Contratista $contratistaActual = null;
    public bool $esSoloLectura = false;

    public $vinculacionesDisponibles;
    public $todasLasVinculaciones;
    
    #[Url(as: 'uo')]
    public $vinculacionSeleccionada = null;

    public $mandantesDisponibles = [];
    #[Url(as: 'mandante')]
    public $mandanteSeleccionadoId = null;

    // === NUEVAS PROPIEDADES PARA MANDANTE ===
    public $contratistasDisponibles = [];
    #[Url(as: 'contratista')]
    public $contratistaSeleccionadoId = null;
    
    // === NUEVAS PROPIEDADES PARA SUBCONTRATISTA (Recursivo) ===
    public $subContratistasDisponibles = [];
    #[Url(as: 'sub_contratista')]
    public $selectedSubContratistaId = null;
    // ========================================

    public bool $existenTrabajadoresHuerfanos = false;
    public bool $existenTrabajadoresEnReserva = false;
    public bool $existenVehiculosHuerfanos = false;
    public bool $existenVehiculosEnReserva = false;
    public bool $existenMaquinariasHuerfanas = false;
    public bool $existenMaquinariasEnReserva = false;
    public bool $existenEmbarcacionesHuerfanas = false;
    public bool $existenEmbarcacionesEnReserva = false;

    public $lugaresDeTrabajoDisponibles = [];
    #[Url(as: 'lugar')]
    public $lugarDeTrabajoSeleccionado = null;

    public $mandanteContextoId = null;
    public $unidadOrganizacionalContextoId = null;
    public $nombreMandanteContexto = '';
    public $nombreUnidadContexto = '';
    public $numeroContratoContexto = ''; // Contexto actual de contrato
    public $contratosDisponibles = []; // Lista de contratos disponibles para el selector
    #[Url(as: 'contrato')]
    public $filtroNumeroContrato = null; // Filtro seleccionado de contrato
    public $tiposEntidadPermitidosContextoActual = [];

    #[Url(as: 'pestaña')]
    public $pestañaActiva = null;
    
    public $force_remount_key;

    public array $documentosRequeridosEmpresa = [];

    private ?DocumentoRequeridoService $documentoService;
    private ?CriticidadDocumentoService $criticidadService; 

    public array $documentosParaCargar = [];
    public array $uploadErrors = [];
    public array $uploadSuccess = [];

    public bool $showModalInfoCargaEmpresa = false;
    public array $infoCargaSeleccionadaEmpresa = [];

    public array $glosarioDocumentos = [];
    public array $documentosMaestros = [];
    
    public bool $showGlosario = false;

    // ================================================================
    // CAMBIO 5: ACREDITACIÓN DEL CUO
    // Determina si el CUO seleccionado tiene acreditación vigente.
    // Si no acredita: se ocultan Carga Flash, % Cumplimiento y Acceso.
    // ================================================================
    public bool $cuoAcredita = true;

    public function boot(DocumentoRequeridoService $documentoService, CriticidadDocumentoService $criticidadService)
    {
        $this->documentoService = $documentoService;
        $this->criticidadService = $criticidadService;
    }

    public function mount()
    {
        $user = Auth::user();

        // Lógica Específica para MANDANTE
        if ($user->hasAnyRole(['Mandante_Admin', 'Mandante_Ver'])) {
            $this->esSoloLectura = $user->hasRole('Mandante_Ver') || $this->esSoloLectura;
            $this->mandanteIdForzado = $user->mandante->id;
            $this->mandanteSeleccionadoId = $this->mandanteIdForzado;
            
            // Cargar Contratistas vinculados a este Mandante
            $this->contratistasDisponibles = Contratista::whereHas('solicitudesVinculacion', function ($query) {
                $query->where('mandante_id', $this->mandanteIdForzado)
                      ->where('estado', 'APROBADA');
            })->orderBy('razon_social')->get();

            // PRIORIDAD: Si viene forzado desde un componente padre (ej: Mandante\Operaciones), usarlo.
            if ($this->contratistaIdForzado) {
                $this->contratistaActual = Contratista::find($this->contratistaIdForzado);
                $this->contratistaSeleccionadoId = $this->contratistaIdForzado;
            } 
            // Si no, usar el parámetro de URL (comportamiento legacy/standalone)
            elseif ($this->contratistaSeleccionadoId) {
                $this->contratistaActual = Contratista::find($this->contratistaSeleccionadoId);
            }
            
            // IMPORTANTE: No retornamos error si no hay contratistaActual, porque el Mandante debe seleccionarlo.
        } 
        // Lógica Original (Backup) para Contratista y Admin
        else {
            if ($this->contratistaIdForzado) {
                $this->contratistaActual = Contratista::find($this->contratistaIdForzado);
            } else {
                $this->contratistaActual = Auth::user()->contratista;
            }

            // CORRECCIÓN: Si es un Contratista (tiene contratistaActual y no es Admin), forzamos el ID
            // Esto asegura que la vista oculte el selector de "Seleccione Contratista"
            if ($this->contratistaActual && !$user->hasRole('ASEM_Admin') && !$user->hasRole('ASEM_Validator')) {
                $this->contratistaIdForzado = $this->contratistaActual->id;
            }

            if (!$this->contratistaActual) {
                // Mantenemos la validación original, pero excluimos a los Admins que pueden entrar sin contratista inicial
                if (!$user->hasRole('ASEM_Admin') && !$user->hasRole('ASEM_Validator')) {
                    session()->flash('error_general_panel', 'No se pudo determinar el contratista para operar.');
                    return;
                }
            }
        }

        if ($this->mandanteIdForzado) {
            $this->mandanteSeleccionadoId = $this->mandanteIdForzado;
        }

        // Solo cargar datos iniciales si tenemos un contratista definido
        if ($this->contratistaActual) {
            $this->cargarDatosIniciales();
            
            $this->filtrarVinculacionesDisponibles();
            
            // Preservar la vinculación (UO) si viene desde la URL, ya que updatedLugarDeTrabajoSeleccionado la resetea
            $vinculacionPreservada = $this->vinculacionSeleccionada;

            if ($this->lugarDeTrabajoSeleccionado) {
                $this->updatedLugarDeTrabajoSeleccionado($this->lugarDeTrabajoSeleccionado);
            } else {
                $this->updatedLugarDeTrabajoSeleccionado(null);
            }

            // Restaurar y aplicar la vinculación si existía
            if ($vinculacionPreservada) {
                $this->vinculacionSeleccionada = $vinculacionPreservada;
                $this->updatedVinculacionSeleccionada($this->vinculacionSeleccionada);
            }

            if ($this->mandanteContextoId && $this->pestañaActiva) {
                $this->cargarDocumentosMaestrosParaContexto();
            }
        }

        // NUEVO: Aplicar preselección si vienen parámetros desde Supervisión
        if ($this->preselectedLugar && !$this->lugarDeTrabajoSeleccionado) {
            $this->lugarDeTrabajoSeleccionado = $this->preselectedLugar;
            $this->updatedLugarDeTrabajoSeleccionado($this->preselectedLugar);
        }
        if ($this->preselectedUo && !$this->vinculacionSeleccionada) {
            $this->vinculacionSeleccionada = $this->preselectedUo;
            $this->updatedVinculacionSeleccionada($this->preselectedUo);
        }
        // Aplicar preselección de contrato al filtro Y al contexto
        if ($this->preselectedContrato) {
            $this->filtroNumeroContrato = $this->preselectedContrato;
            $this->numeroContratoContexto = $this->preselectedContrato;
        }

        // CARGAR SUBCONTRATISTAS SI CORRESPONDE
        if ($this->contratistaActual) {
            // Caso especial: Si estoy "impersonando" a un subcontratista (ej: seleccionado desde dropdown),
            // necesito saber quién es el "padre" original para cargar la lista de hermanos/subs.
            // Pero en este diseño simplificado, asumimos que si el contratistaActual tiene subs, los mostramos.
            $this->cargarSubs($this->contratistaActual);
        }
        
        $this->force_remount_key = rand();
    }

    #[On('recursosActualizados')]
    public function cargarDatosIniciales()
    {
        if (!$this->contratistaActual) return;

        if (!$this->mandanteIdForzado) {
            $this->mandantesDisponibles = $this->contratistaActual->mandantesAprobados()->orderBy('razon_social')->get();
        } else {
            $this->mandantesDisponibles = Mandante::where('id', $this->mandanteIdForzado)->get();
        }

        $queryLugares = $this->contratistaActual->dependencias()->with('parent')->where('estado', true);
        if ($this->mandanteSeleccionadoId) {
            $queryLugares->where('mandante_id', $this->mandanteSeleccionadoId);
        }
        $this->lugaresDeTrabajoDisponibles = $queryLugares->get()->sortBy('nombre_jerarquico');

        $this->detectarTrabajadoresHuerfanos();
        $this->detectarTrabajadoresEnReserva();
        $this->detectarVehiculosHuerfanos();
        $this->detectarVehiculosEnReserva();
        $this->detectarMaquinariasHuerfanas();
        $this->detectarMaquinariasEnReserva();
        $this->detectarEmbarcacionesHuerfanas();
        $this->detectarEmbarcacionesEnReserva();

        $unidadesAsignadas = $this->contratistaActual->unidadesOrganizacionalesMandante()
            ->with(['mandante.tiposEntidadControlable', 'mandante:id,razon_social', 'parent'])
            ->get()
            ->unique('id'); // Eliminar duplicados cuando hay múltiples contratos en la misma UO
        
        $vinculacionesFormateadas = collect();
        if ($unidadesAsignadas->isNotEmpty()) {
            foreach ($unidadesAsignadas as $unidadOrg) {
                if ($mandante = $unidadOrg->mandante) {
                    $vinculacionesFormateadas->push([
                        'id_seleccion' => $unidadOrg->id,
                        'texto_visible' => $mandante->razon_social . ' - ' . $unidadOrg->nombre_jerarquico,
                        'mandante_id' => $mandante->id,
                        'unidad_organizacional_mandante_id' => $unidadOrg->id,
                        'mandante_razon_social' => $mandante->razon_social,
                        'unidad_organizacional_nombre' => $unidadOrg->nombre_unidad,
                        'tipos_entidad_permitidos' => $mandante->tiposEntidadControlable
                            ->pluck('nombre_entidad')->map(fn($nombre) => strtoupper($nombre))
                            ->unique()->values()->toArray(),
                        'numero_contrato' => $unidadOrg->pivot->numero_contrato, // Añadir contrato
                    ]);
                }
            }
        }
        $this->todasLasVinculaciones = $vinculacionesFormateadas->sortBy('texto_visible')->values();
        
        // ===== FILTRADO PARA CONTRATISTA_USER =====
        // Si el usuario es Contratista_User, solo mostrar las vinculaciones asignadas
        $user = Auth::user();
        if ($user->hasRole('Contratista_User')) {
            $vinculacionesAsignadasIds = $user->vinculacionesAsignadas()->pluck('contratista_unidad_organizacional.id')->toArray();
            
            if (!empty($vinculacionesAsignadasIds)) {
                // Filtrar basándose en las vinculaciones CUO asignadas
                $vinculacionesCuo = \App\Models\ContratistaUnidadOrganizacional::whereIn('id', $vinculacionesAsignadasIds)
                    ->get();
                
                $uoIdsAsignadas = $vinculacionesCuo->pluck('unidad_organizacional_mandante_id')->toArray();
                
                $this->todasLasVinculaciones = $this->todasLasVinculaciones->filter(function ($vinc) use ($uoIdsAsignadas) {
                    return in_array($vinc['unidad_organizacional_mandante_id'], $uoIdsAsignadas);
                })->values();
            } else {
                // Si no tiene vinculaciones asignadas, no puede ver nada
                $this->todasLasVinculaciones = collect();
            }
        }
        // ===== FIN FILTRADO =====
        
        $this->vinculacionesDisponibles = $this->todasLasVinculaciones;
        
        // Cargar contratos disponibles
        $this->cargarContratosDisponibles();
    }

    /**
     * Carga la lista de contratos disponibles para el selector
     */
    private function cargarContratosDisponibles()
    {
        $this->contratosDisponibles = [];
        
        if (!$this->contratistaActual) {
            return;
        }

        // Obtener TODOS los números de contrato únicos del contratista desde la BD
        // No solo los de las vinculaciones filtradas, sino todos sus contratos
        $contratos = DB::table('contratista_unidad_organizacional')
            ->where('contratista_id', $this->contratistaActual->id)
            ->whereNotNull('numero_contrato')
            ->distinct()
            ->pluck('numero_contrato')
            ->sort()
            ->values()
            ->toArray();
        
        $this->contratosDisponibles = $contratos;
    }

    /**
     * Se ejecuta cuando cambia el filtro de número de contrato
     */
    public function updatedFiltroNumeroContrato($value)
    {
        $this->numeroContratoContexto = $value ?: '';
        $this->force_remount_key = rand();
    }

    // === NUEVO MÉTODO PARA ACTUALIZAR CONTRATISTA (MANDANTE) ===
    public function updatedContratistaSeleccionadoId($value)
    {
        $this->resetContexto();
        $this->contratistaActual = null;
        $this->selectedSubContratistaId = null; // Reset sub
        
        if ($value) {
            $this->contratistaActual = Contratista::find($value);
            if ($this->contratistaActual) {
                $this->cargarDatosIniciales();
                $this->updatedLugarDeTrabajoSeleccionado(null);
                $this->cargarSubs($this->contratistaActual); // Cargar subs del nuevo contratista seleccionado
            }
        }
        $this->force_remount_key = rand();
    }
    
    // === NUEVO MÉTODO PARA SELECCIONAR SUBCONTRATISTA ===
    public function updatedSelectedSubContratistaId($value)
    {
        if ($value) {
            $nuevoContratista = Contratista::find($value);
            if ($nuevoContratista) {
                // ALERTA: Estamos navegando hacia abajo.
                $this->contratistaActual = $nuevoContratista;
                $this->resetContexto();
                $this->cargarDatosIniciales();
                $this->updatedLugarDeTrabajoSeleccionado(null);
                
                // Mantenemos la lógica drill-down
                $this->cargarSubs($this->contratistaActual); 
                
                // IMPORTANTE: Mantenemos el ID seleccionado en la propiedad para que el select refleje "Gestión Propia" 
                // pero como el select se repobla con los hijos DEL NUEVO, el value del select ya no coincidirá con el ID del nuevo (que ahora es el padre)
                // EL select de Subs muestra los HIJOS de $contratistaActual.
                // Si $contratistaActual es el SUB, el select mostrará los SUBS del SUB.
                // Por lo tanto, el select debe resetearse a "vacío" (Gestión Propia).
                $this->selectedSubContratistaId = null; 
            }
        }
        $this->force_remount_key = rand();
    }

    public function volverAlContratistaOriginal()
    {
        // Reseteamos al "Usuario Logueado" o al "Contratista Seleccionado por Mandante"
        $user = Auth::user();
        $idOriginal = null;

        if ($this->contratistaSeleccionadoId) {
            // Caso Mandante o Admin que seleccionó un contratista root
            $idOriginal = $this->contratistaSeleccionadoId;
        } elseif ($user->contratista_id) {
            // Caso Contratista logueado
            $idOriginal = $user->contratista_id;
        }

        if ($idOriginal) {
            $this->contratistaActual = Contratista::find($idOriginal);
            $this->resetContexto();
            $this->cargarDatosIniciales();
            $this->updatedLugarDeTrabajoSeleccionado(null);
            $this->cargarSubs($this->contratistaActual);
            $this->selectedSubContratistaId = null;
        }
        $this->force_remount_key = rand();
    }

    public function cargarSubs($contratista)
    {
        $this->subContratistasDisponibles = [];
        if ($contratista) {
            $this->subContratistasDisponibles = $this->obtenerDescendientesPlanos($contratista);
        }
    }

    private function obtenerDescendientesPlanos($contratista, $nivel = 0)
    {
        $items = collect();
        
        $idsHijos = \Illuminate\Support\Facades\DB::table('solicitudes_vinculacion')
            ->where('contratista_padre_id', $contratista->id)
            ->where('estado', 'APROBADA')
            ->distinct()
            ->pluck('contratista_id');
            
        if ($idsHijos->isEmpty()) {
            return $items;
        }

        $hijos = Contratista::whereIn('id', $idsHijos)
            ->orderBy('razon_social')
            ->get();
        
        foreach ($hijos as $hijo) {
            $prefix = str_repeat('↳ ', $nivel + 1); 
            
            $items->push([
                'id' => $hijo->id,
                'razon_social' => $prefix . $hijo->razon_social,
                'rut' => $hijo->rut
            ]);
            
            // Recursividad para obtener nietos, bisnietos, etc.
            // Esto poblará el dropdown con TODA la jerarquía plana hacia abajo.
            $descendientes = $this->obtenerDescendientesPlanos($hijo, $nivel + 1);
            $items = $items->merge($descendientes);
        }
        
        return $items;
    }
    // ===========================================================

    public function updatedMandanteSeleccionadoId($value)
    {
        $this->lugarDeTrabajoSeleccionado = null;
        $this->vinculacionSeleccionada = null;
        
        $queryLugares = $this->contratistaActual->dependencias()->with('parent')->where('estado', true);
        if ($value) {
            $queryLugares->where('mandante_id', $value);
        }
        $this->lugaresDeTrabajoDisponibles = $queryLugares->get()->sortBy('nombre_jerarquico');

        $this->filtrarVinculacionesDisponibles();
        $this->updatedLugarDeTrabajoSeleccionado(null);

        // Refrescar detecciones de reserva y huérfanos al cambiar mandante
        $this->detectarTrabajadoresHuerfanos();
        $this->detectarTrabajadoresEnReserva();
        $this->detectarVehiculosHuerfanos();
        $this->detectarVehiculosEnReserva();
        $this->detectarMaquinariasHuerfanas();
        $this->detectarMaquinariasEnReserva();
        $this->detectarEmbarcacionesHuerfanas();
        $this->detectarEmbarcacionesEnReserva();
    }

    private function filtrarVinculacionesDisponibles()
    {
        if ($this->mandanteSeleccionadoId) {
            $this->vinculacionesDisponibles = $this->todasLasVinculaciones
                ->where('mandante_id', $this->mandanteSeleccionadoId)
                ->values();
        } else {
            $this->vinculacionesDisponibles = $this->todasLasVinculaciones;
        }
    }

    private function detectarTrabajadoresHuerfanos() { $idsDependenciasAsignadas = $this->contratistaActual->dependencias()->pluck('dependencias.id')->toArray(); $idsUOsAsignadas = $this->contratistaActual->unidadesOrganizacionalesMandante()->pluck('unidades_organizacionales_mandante.id')->toArray(); $this->existenTrabajadoresHuerfanos = TrabajadorVinculacion::whereHas('trabajador', fn($q) => $q->where('contratista_id', $this->contratistaActual->id))->where(function($q) use ($idsDependenciasAsignadas, $idsUOsAsignadas) { $q->where(function($sq) use ($idsDependenciasAsignadas) { $sq->whereNotNull('dependencia_id')->whereNotIn('dependencia_id', $idsDependenciasAsignadas); })->orWhereNotIn('unidad_organizacional_mandante_id', $idsUOsAsignadas); })->when($this->mandanteSeleccionadoId, function($q) { $q->where(fn($sq) => $sq->whereHas('unidadOrganizacionalMandante', fn($ssq) => $ssq->where('mandante_id', $this->mandanteSeleccionadoId))->orWhereHas('cargoMandante', fn($ssq) => $ssq->where('mandante_id', $this->mandanteSeleccionadoId))); })->exists(); }
    private function detectarTrabajadoresEnReserva() { $this->existenTrabajadoresEnReserva = TrabajadorVinculacion::whereHas('trabajador', fn($q) => $q->where('contratista_id', $this->contratistaActual->id))->whereNull('dependencia_id')->when($this->mandanteSeleccionadoId, function($q) { $q->where(fn($sq) => $sq->whereHas('unidadOrganizacionalMandante', fn($ssq) => $ssq->where('mandante_id', $this->mandanteSeleccionadoId))->orWhereHas('cargoMandante', fn($ssq) => $ssq->where('mandante_id', $this->mandanteSeleccionadoId))); })->exists(); }
    private function detectarVehiculosHuerfanos() { $idsDependenciasAsignadas = $this->contratistaActual->dependencias()->pluck('dependencias.id')->toArray(); $idsUOsAsignadas = $this->contratistaActual->unidadesOrganizacionalesMandante()->pluck('unidades_organizacionales_mandante.id')->toArray(); $this->existenVehiculosHuerfanos = VehiculoAsignacion::whereHas('vehiculo', fn($q) => $q->where('contratista_id', $this->contratistaActual->id))->where(function($q) use ($idsDependenciasAsignadas, $idsUOsAsignadas) { $q->where(function($sq) use ($idsDependenciasAsignadas) { $sq->whereNotNull('dependencia_id')->whereNotIn('dependencia_id', $idsDependenciasAsignadas); })->orWhereNotIn('unidad_organizacional_mandante_id', $idsUOsAsignadas); })->when($this->mandanteSeleccionadoId, function($q) { $q->where(fn($sq) => $sq->whereHas('unidadOrganizacionalMandante', fn($ssq) => $ssq->where('mandante_id', $this->mandanteSeleccionadoId))->orWhereHas('subTipoVehiculo', fn($ssq) => $ssq->where('mandante_id', $this->mandanteSeleccionadoId))); })->exists(); }
    private function detectarVehiculosEnReserva() { $this->existenVehiculosEnReserva = VehiculoAsignacion::whereHas('vehiculo', fn($q) => $q->where('contratista_id', $this->contratistaActual->id))->whereNull('dependencia_id')->when($this->mandanteSeleccionadoId, function($q) { $q->where(fn($sq) => $sq->whereHas('unidadOrganizacionalMandante', fn($ssq) => $ssq->where('mandante_id', $this->mandanteSeleccionadoId))->orWhereHas('subTipoVehiculo', fn($ssq) => $ssq->where('mandante_id', $this->mandanteSeleccionadoId))); })->exists(); }
    private function detectarMaquinariasHuerfanas() { $idsDependenciasAsignadas = $this->contratistaActual->dependencias()->pluck('dependencias.id')->toArray(); $idsUOsAsignadas = $this->contratistaActual->unidadesOrganizacionalesMandante()->pluck('unidades_organizacionales_mandante.id')->toArray(); $this->existenMaquinariasHuerfanas = MaquinariaAsignacion::whereHas('maquinaria', fn($q) => $q->where('contratista_id', $this->contratistaActual->id))->where(function($q) use ($idsDependenciasAsignadas, $idsUOsAsignadas) { $q->where(function($sq) use ($idsDependenciasAsignadas) { $sq->whereNotNull('dependencia_id')->whereNotIn('dependencia_id', $idsDependenciasAsignadas); })->orWhereNotIn('unidad_organizacional_mandante_id', $idsUOsAsignadas); })->when($this->mandanteSeleccionadoId, function($q) { $q->whereHas('unidadOrganizacionalMandante', fn($sq) => $sq->where('mandante_id', $this->mandanteSeleccionadoId)); })->exists(); }
    private function detectarMaquinariasEnReserva() { $this->existenMaquinariasEnReserva = MaquinariaAsignacion::whereHas('maquinaria', fn($q) => $q->where('contratista_id', $this->contratistaActual->id))->whereNull('dependencia_id')->when($this->mandanteSeleccionadoId, function($q) { $q->whereHas('unidadOrganizacionalMandante', fn($sq) => $sq->where('mandante_id', $this->mandanteSeleccionadoId)); })->exists(); }
    private function detectarEmbarcacionesHuerfanas() { $idsDependenciasAsignadas = $this->contratistaActual->dependencias()->pluck('dependencias.id')->toArray(); $idsUOsAsignadas = $this->contratistaActual->unidadesOrganizacionalesMandante()->pluck('unidades_organizacionales_mandante.id')->toArray(); $this->existenEmbarcacionesHuerfanas = EmbarcacionAsignacion::whereHas('embarcacion', fn($q) => $q->where('contratista_id', $this->contratistaActual->id))->where(function($q) use ($idsDependenciasAsignadas, $idsUOsAsignadas) { $q->where(function($sq) use ($idsDependenciasAsignadas) { $sq->whereNotNull('dependencia_id')->whereNotIn('dependencia_id', $idsDependenciasAsignadas); })->orWhereNotIn('unidad_organizacional_mandante_id', $idsUOsAsignadas); })->when($this->mandanteSeleccionadoId, function($q) { $q->whereHas('unidadOrganizacionalMandante', fn($sq) => $sq->where('mandante_id', $this->mandanteSeleccionadoId)); })->exists(); }
    private function detectarEmbarcacionesEnReserva() { $this->existenEmbarcacionesEnReserva = EmbarcacionAsignacion::whereHas('embarcacion', fn($q) => $q->where('contratista_id', $this->contratistaActual->id))->whereNull('dependencia_id')->when($this->mandanteSeleccionadoId, function($q) { $q->whereHas('unidadOrganizacionalMandante', fn($sq) => $sq->where('mandante_id', $this->mandanteSeleccionadoId)); })->exists(); }


    public function updatedLugarDeTrabajoSeleccionado($value)
    {
        $this->resetContextoParcial();

        if (empty($value)) {
            $this->mandanteContextoId = $this->mandanteSeleccionadoId; 
            $this->filtrarVinculacionesDisponibles();
            if ($this->mandanteContextoId) {
                $mandante = Mandante::with('tiposEntidadControlable')->find($this->mandanteContextoId);
                if ($mandante) {
                    $this->tiposEntidadPermitidosContextoActual = $mandante->tiposEntidadControlable->pluck('nombre_entidad')->map(fn($nombre) => strtoupper($nombre))->unique()->values()->toArray();
                }
            } else {
                $this->tiposEntidadPermitidosContextoActual = ['EMPRESA', 'PERSONA', 'VEHICULO', 'MAQUINARIA', 'EMBARCACION'];
            }
        } elseif ($value === 'orphaned' || $value === 'in_reserve') {
            $this->mandanteContextoId = $this->mandanteSeleccionadoId;
            $this->filtrarVinculacionesDisponibles();
        } else {
            $lugar = Dependencia::find($value);
            if ($lugar) {
                $this->mandanteContextoId = $lugar->mandante_id;
                $this->filtrarVinculacionesDisponibles();
                $mandante = Mandante::with('tiposEntidadControlable')->find($this->mandanteContextoId);
                if ($mandante) {
                    $this->tiposEntidadPermitidosContextoActual = $mandante->tiposEntidadControlable->pluck('nombre_entidad')->map(fn($nombre) => strtoupper($nombre))->unique()->values()->toArray();
                }
            }
        }

        // ============================================================
        // CAMBIO 5: Recalcular acreditación al cambiar de lugar
        // Cuando no hay UO específica, verifica si el contratista tiene
        // AL MENOS UN CUO con acredita=true y fechas vigentes para el mandante.
        // ============================================================
        if ($this->contratistaIdForzado && $this->contratistaActual && $this->mandanteContextoId) {
            $hoy = Carbon::today();
            $this->cuoAcredita = \App\Models\ContratistaUnidadOrganizacional::where('contratista_id', $this->contratistaActual->id)
                ->where('acredita', true)
                ->whereHas('unidadOrganizacionalMandante', fn($q) => $q->where('mandante_id', $this->mandanteContextoId))
                ->where(fn($q) => $q->whereNull('fecha_inicio_acredita')->orWhere('fecha_inicio_acredita', '<=', $hoy))
                ->where(fn($q) => $q->whereNull('fecha_fin_acredita')->orWhere('fecha_fin_acredita', '>=', $hoy))
                ->exists();
        } elseif (!$this->contratistaIdForzado) {
            $this->cuoAcredita = true; // Admin/Mandante siempre permisivo
        }
        // ============================================================

        $this->cargarDocumentosMaestrosParaContexto();
        $this->force_remount_key = rand();
    }
    
    public function updatedVinculacionSeleccionada($value) 
    { 
        if (empty($value)) { 
            $this->unidadOrganizacionalContextoId = null; 
            $this->nombreUnidadContexto = ''; 
            $this->cuoAcredita = true; // permisivo cuando no hay filtro
        } else { 
            $vinculacion = $this->todasLasVinculaciones->firstWhere('id_seleccion', (int) $value); 
            if ($vinculacion) { 
                $this->unidadOrganizacionalContextoId = $vinculacion['unidad_organizacional_mandante_id']; 
                $this->nombreUnidadContexto = $vinculacion['unidad_organizacional_nombre']; 
                $this->numeroContratoContexto = $vinculacion['numero_contrato']; // Asignar contrato 
                $this->mandanteContextoId = $vinculacion['mandante_id']; 
                $this->nombreMandanteContexto = $vinculacion['mandante_razon_social']; 
                $this->tiposEntidadPermitidosContextoActual = $vinculacion['tipos_entidad_permitidos'];

                // ============================================================
                // CAMBIO 5: Calcular si el CUO acredita con fechas vigentes
                // ============================================================
                if ($this->contratistaActual) {
                    $cuo = \App\Models\ContratistaUnidadOrganizacional::where('contratista_id', $this->contratistaActual->id)
                        ->where('unidad_organizacional_mandante_id', $vinculacion['unidad_organizacional_mandante_id'])
                        ->first();
                    if ($cuo) {
                        $hoy = Carbon::today();
                        $inicioOk = !$cuo->fecha_inicio_acredita || Carbon::parse($cuo->fecha_inicio_acredita)->lte($hoy);
                        $finOk    = !$cuo->fecha_fin_acredita   || Carbon::parse($cuo->fecha_fin_acredita)->gte($hoy);
                        $this->cuoAcredita = (bool) $cuo->acredita && $inicioOk && $finOk;
                    } else {
                        $this->cuoAcredita = true; // sin CUO, permisivo
                    }
                }
                // ============================================================
            } 
        } 
        $this->cargarDocumentosMaestrosParaContexto();
        $this->force_remount_key = rand(); 
    }

    private function resetContextoParcial() { $this->vinculacionSeleccionada = null; $this->unidadOrganizacionalContextoId = null; $this->nombreMandanteContexto = ''; $this->nombreUnidadContexto = ''; $this->numeroContratoContexto = ''; $this->pestañaActiva = null; $this->glosarioDocumentos = []; $this->documentosMaestros = []; }
    public function resetContexto() { $this->vinculacionSeleccionada = null; $this->mandanteContextoId = null; $this->unidadOrganizacionalContextoId = null; $this->nombreMandanteContexto = ''; $this->nombreUnidadContexto = ''; $this->numeroContratoContexto = ''; $this->tiposEntidadPermitidosContextoActual = []; $this->pestañaActiva = null; $this->documentosRequeridosEmpresa = []; $this->lugarDeTrabajoSeleccionado = null; $this->vinculacionesDisponibles = $this->todasLasVinculaciones; $this->glosarioDocumentos = []; $this->documentosMaestros = []; $this->cuoAcredita = true; }
    
    public function seleccionarPestaña(string $nombrePestaña, $mantenerMensajes = false) 
    { 
        $this->pestañaActiva = $nombrePestaña; 
        if (!$mantenerMensajes) { 
            $this->uploadErrors = []; 
            $this->uploadSuccess = []; 
        } 
        
        // Refrescar detecciones al cambiar de pestaña
        if ($this->contratistaActual) {
            $this->detectarTrabajadoresHuerfanos();
            $this->detectarTrabajadoresEnReserva();
            $this->detectarVehiculosHuerfanos();
            $this->detectarVehiculosEnReserva();
            $this->detectarMaquinariasHuerfanas();
            $this->detectarMaquinariasEnReserva();
            $this->detectarEmbarcacionesHuerfanas();
            $this->detectarEmbarcacionesEnReserva();
        }

        $this->cargarDocumentosMaestrosParaContexto();
        $this->force_remount_key = rand(); 
    }

    private function cargarDocumentosMaestrosParaContexto()
    {
        $this->glosarioDocumentos = [];
        $this->documentosMaestros = [];

        $nombreEntidad = match($this->pestañaActiva) {
            'empresa' => 'EMPRESA',
            'trabajadores' => 'PERSONA',
            'vehiculos' => 'VEHICULO',
            'maquinaria' => 'MAQUINARIA',
            'embarcaciones' => 'EMBARCACION',
            default => null,
        };

        if (!$this->mandanteContextoId || !$nombreEntidad) {
            return;
        }

        $tipoEntidad = TipoEntidadControlable::where('nombre_entidad', strtoupper($nombreEntidad))->first();
        if (!$tipoEntidad) {
            return;
        }

        $queryReglas = ReglaDocumental::query()
            ->where('is_active', true)
            ->where('mandante_id', $this->mandanteContextoId)
            ->where('tipo_entidad_controlada_id', $tipoEntidad->id);

        if ($this->unidadOrganizacionalContextoId) {
            $uoActual = UnidadOrganizacionalMandante::find($this->unidadOrganizacionalContextoId);
            $idsUoAplicables = [$this->unidadOrganizacionalContextoId];
            if ($uoActual) {
                $parentId = $uoActual->parent_id;
                while ($parentId) {
                    $idsUoAplicables[] = $parentId;
                    $ancestro = UnidadOrganizacionalMandante::find($parentId);
                    $parentId = $ancestro ? $ancestro->parent_id : null;
                }
            }
            $queryReglas->where(function ($query) use ($idsUoAplicables) {
                $query->whereHas('unidadesOrganizacionales', function ($subQuery) use ($idsUoAplicables) {
                    $subQuery->whereIn('unidad_organizacional_mandante_id', $idsUoAplicables);
                })
                ->orWhereDoesntHave('unidadesOrganizacionales');
            });
        }

        $reglas = $queryReglas->with('nombreDocumento')->get();

        $documentosUnicos = $reglas->unique('nombre_documento_id')->sortBy('nombreDocumento.nombre');

        $contador = 1;
        foreach ($documentosUnicos as $regla) {
            if ($regla->nombreDocumento) {
                $this->glosarioDocumentos[] = [
                    'numero' => $contador,
                    'nombre' => $regla->nombreDocumento->nombre,
                ];
                $this->documentosMaestros[] = [
                    'numero' => $contador,
                    'nombre_documento_id' => $regla->nombre_documento_id,
                ];
                $contador++;
            }
        }
    }

    public function toggleGlosario()
    {
        $this->showGlosario = !$this->showGlosario;
    }

    public function render() { return view('livewire.contratista.panel-operacion'); }
}