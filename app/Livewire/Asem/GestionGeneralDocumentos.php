<?php

namespace App\Livewire\Asem;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use App\Models\DocumentoCargado;
use App\Models\Contratista;
use App\Models\Mandante;
use App\Models\User;
use App\Models\Trabajador;
use App\Models\Vehiculo;
use App\Models\Maquinaria;
use App\Models\Embarcacion;
use App\Models\ReglaDocumental;
use App\Models\MandanteColorConfiguracion;
use App\Models\TrabajadorVinculacion;
use App\Services\DocumentoRequeridoService;
use App\Jobs\NotificarDocumentosContratista;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use stdClass;
use App\Exports\ProduccionValidadoresExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;
use Livewire\Attributes\Url;
use App\Jobs\ActualizarEstadoRecursoIndividual; // NUEVO

class GestionGeneralDocumentos extends Component
{
    use WithPagination, WithFileUploads, \App\Traits\ValidatesFileUpload;

    // --- PROPIEDADES DE FILTRADO ---
    #[Url]
    public $filtroContratista = '';
    #[Url]
    public $filtroMandante = '';
    #[Url]
    public $filtroEntidad = '';
    #[Url]
    public $filtroDocumento = '';
    #[Url]
    public $filtroIdEntidad = '';
    #[Url]
    public $filtroEstado = '';
    #[Url]
    public $filtroResultado = '';
    #[Url]
    public $filtroVigencia = '';
    #[Url]
    public $filtroFechaDesde = '';
    #[Url]
    public $filtroFechaHasta = '';
    #[Url]
    public $filtroFechaCargaDesde = '';
    #[Url]
    public $filtroFechaCargaHasta = '';
    #[Url]
    public $filtroValidador = '';
    #[Url]
    public $filtroTipoContratista = 'todos';
    #[Url]
    public $filtroErrorValidador = false;

    public bool $busquedaRealizada = false;

    // --- PROPIEDADES PARA FILTRADO POR EXCLUSIÓN ---
    #[Url(except: [])]
    public $estadosAExcluir = [];
    public $listaDeEstados = [];

    // NUEVO: Propiedades para la exclusión de columnas
    #[Url(except: [])]
    public $columnasAExcluir = [];
    public $listaDeColumnas = [];
    
    // --- PROPIEDADES DE GESTIÓN Y ASIGNACIÓN ---
    public $documentosSeleccionados = [];
    public $validadorSeleccionado = null;
    public $totalValorNominal = 0;
    public $seleccionarTodos = false;

    // --- PROPIEDADES PARA REVALIDACIÓN MASIVA ---
    public $seleccionParaRevalidar = [];
    public $motivoRevalidacionMasiva = '';
    public $seleccionarTodosRevalidar = false; // NUEVO

    // --- PROPIEDADES PARA MODIFICAR VENCIMIENTO ---
    public $seleccionParaModificar = [];
    public $showModificarVencimientoModal = false;
    public $tipoModificacion = 'fecha_fija';
    public $fechaFija;
    public $diasASumar;
    public $motivoModificacion = '';
    public $justificativoModificacion;
    public $seleccionarTodosModificar = false; // NUEVO

    // --- PROPIEDADES PARA AUDITORÍA Y REVALIDACIÓN INDIVIDUAL ---
    public $showAuditoriaModal = false;
    public $documentoAuditoria = null;
    public $motivoRevalidacionIndividual = '';
    public $esAuditoriaSoloLectura = false;
    public $marcarComoErrorValidador = false;
    public ?string $cargoAuditoria = null;
    
    // --- PROPIEDADES PARA NOTIFICACIONES ---
    public $showNotificacionModal = false;
    public $conteoNotificacion = ['total' => 0, 'contratistas' => 0];
    public $mensajeNotificacion = '';

    // --- PROPIEDADES PARA INFORMES ---
    public $showInformeProduccionModal = false;
    public $datosInformeProduccion = [];
    public $formatosExportacion = [];
    public $validadoresParaExportar = [];
    public $seleccionarTodosValidadores = true;

    // --- PROPIEDADES PARA CONFIGURACIÓN DE COLORES ---
    public $showColorConfigModal = false;
    public $selectedMandanteForColors = null;
    public $colorConfigs = [];
    public $newRuleHorasInicio;
    public $newRuleHorasFin;
    public $newRuleColorSeleccionado = 'yellow';
    public $opcionesDeColor = [];

    // --- PROPIEDADES PARA MAPA DE CALOR ---
    public $showMapaCalorModal = false;
    public $mapaCalorData = [];

    // --- PROPIEDADES DE ORDENACIÓN ---
    #[Url]
    public $sortField = 'created_at';
    #[Url]
    public $sortDirection = 'asc';

    // --- SERVICIO INYECTADO ---
    protected $documentoRequeridoService;

    public function boot(DocumentoRequeridoService $documentoRequeridoService)
    {
        $this->documentoRequeridoService = $documentoRequeridoService;
    }
    
    public function mount()
    {
        $this->listaDeEstados = [
            'Sin Asignar' => 'Sin Asignar',
            'Asignado' => 'Asignado',
            'Devuelto' => 'Devuelto',
            'Pendiente Validación Mandante' => 'Pendiente Validación Principal',
            'Revisado' => 'Revisado (Finalizado)',
            'Asignar-Revalidar' => 'Revalidar (Sin Asignar)',
            'Asignado-Revalidar' => 'Revalidar (Asignado)',
            'Revisado-Revalidado' => 'Revisado (Por Revalidación)',
            'Archivado' => 'Archivado',
            'Archivado-Revalidado' => 'Archivado (Por Revalidación)',
        ];

        $this->listaDeColumnas = [
            'principal' => 'Principal',
            'contratista' => 'Contratista',
            'valor_nominal' => 'Valor Nominal',
            'entidad' => 'Entidad',
            'estado_validacion' => 'Estado Validación',
            'resultado' => 'Resultado',
            'fecha_validacion' => 'F. Validación',
            'fecha_vencimiento' => 'F. Vencimiento',
            'vigencia' => 'Vigencia',
            'validador' => 'Validador',
            'fecha_carga' => 'F. Carga',
            'revalidar' => 'Revalidar',
            'mod_venc' => 'Mod. Venc.',
        ];

        $this->opcionesDeColor = [
            'yellow' => [
                'nombre' => 'Amarillo (Alerta Media)',
                'fondo' => 'bg-yellow-200',
                'texto' => 'text-yellow-800',
            ],
            'orange' => [
                'nombre' => 'Naranjo (Alerta Alta)',
                'fondo' => 'bg-orange-400',
                'texto' => 'text-white',
            ],
            'red' => [
                'nombre' => 'Rojo (Alerta Crítica)',
                'fondo' => 'bg-red-600',
                'texto' => 'text-white',
            ],
            'black' => [
                'nombre' => 'Negro (Alerta Máxima)',
                'fondo' => 'bg-black',
                'texto' => 'text-white',
            ],
        ];
    }
    
    public function updated($propertyName) { if(in_array($propertyName, ['filtroContratista', 'filtroMandante', 'filtroEntidad', 'filtroDocumento', 'filtroIdEntidad', 'filtroEstado', 'filtroResultado', 'filtroVigencia', 'filtroFechaDesde', 'filtroFechaHasta', 'filtroFechaCargaDesde', 'filtroFechaCargaHasta', 'filtroValidador', 'estadosAExcluir', 'filtroTipoContratista', 'filtroErrorValidador', 'columnasAExcluir'])) { $this->resetPage(); } }
    
    public function updatedDocumentosSeleccionados() { if (empty($this->documentosSeleccionados)) { $this->totalValorNominal = 0; return; } $this->totalValorNominal = DocumentoCargado::whereIn('id', $this->documentosSeleccionados)->sum('valor_nominal_snapshot'); }
    
    public function updatedSeleccionarTodos($value) {
        if ($value) {
            $this->documentosSeleccionados = $this->buildQuery()
                ->whereNull('resultado_validacion')
                ->where(function($q) {
                    $q->whereNull('valida_solo_mandante_snapshot')
                      ->orWhere('valida_solo_mandante_snapshot', false);
                })
                ->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->documentosSeleccionados = [];
        }
        $this->updatedDocumentosSeleccionados();
    }

    public function updatedSeleccionarTodosRevalidar($value)
    {
        $documentos = $this->buildQuery()->paginate(100);
        if ($value) {
            $this->seleccionParaRevalidar = $documentos->filter(function ($doc) {
                return $doc->resultado_validacion && !in_array($doc->estado_validacion, ['Archivado', 'Archivado-Revalidado']);
            })->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->seleccionParaRevalidar = [];
        }
    }

    public function updatedSeleccionarTodosModificar($value)
    {
        $documentos = $this->buildQuery()->paginate(100);
        if ($value) {
            $this->seleccionParaModificar = $documentos->filter(function ($doc) {
                return $doc->resultado_validacion && !in_array($doc->estado_validacion, ['Archivado', 'Archivado-Revalidado']);
            })->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->seleccionParaModificar = [];
        }
    }
    
    private function resetSeleccion() { 
        $this->documentosSeleccionados = []; 
        $this->validadorSeleccionado = null; 
        $this->totalValorNominal = 0; 
        $this->seleccionarTodos = false; 
        $this->seleccionParaRevalidar = [];
        $this->motivoRevalidacionMasiva = '';
        $this->seleccionParaModificar = [];
        $this->seleccionarTodosRevalidar = false;
        $this->seleccionarTodosModificar = false;
        $this->cerrarModalModificarVencimiento();
        $this->cerrarModalAuditoria();
        $this->cerrarModalNotificacion();
        $this->cerrarModalInformeProduccion();
        $this->cerrarModalColores();
        $this->cerrarModalMapaCalor();
    }

    public function sortBy($field) { if ($this->sortField === $field) { $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc'; } else { $this->sortDirection = 'asc'; } $this->sortField = $field; }

    public function resetearFiltros()
    {
        $this->reset([
            'filtroContratista', 'filtroMandante', 'filtroEntidad',
            'filtroDocumento', 'filtroIdEntidad', 'filtroEstado',
            'filtroResultado', 'filtroVigencia',
            'filtroFechaDesde', 'filtroFechaHasta', 'filtroFechaCargaDesde', 'filtroFechaCargaHasta',
            'filtroValidador', 'estadosAExcluir', 'filtroTipoContratista', 'filtroErrorValidador',
            'columnasAExcluir'
        ]);
        $this->busquedaRealizada = false;
        $this->resetPage();
        $this->resetSeleccion();
    }

    public function buscar()
    {
        $this->busquedaRealizada = true;
        $this->resetPage();
    }

    public function borrarFechasValidacion()
    {
        $this->filtroFechaDesde = '';
        $this->filtroFechaHasta = '';
        $this->resetPage();
    }

    public function borrarFechasCarga()
    {
        $this->filtroFechaCargaDesde = '';
        $this->filtroFechaCargaHasta = '';
        $this->resetPage();
    }

    public function updatedFiltroEstado($value)
    {
        if (!empty($value)) {
            $this->estadosAExcluir = [];
        }
        $this->resetPage();
    }

    public function updatedEstadosAExcluir($value)
    {
        if (!empty($value)) {
            $this->filtroEstado = '';
        }
        $this->resetPage();
    }
    
    public function marcarTodosParaExcluir()
    {
        $this->filtroEstado = '';
        $this->estadosAExcluir = array_keys($this->listaDeEstados);
        $this->resetPage();
    }

    public function desmarcarTodosParaExcluir()
    {
        $this->estadosAExcluir = [];
        $this->resetPage();
    }

    public function desmarcarTodasColumnasParaExcluir()
    {
        $this->columnasAExcluir = [];
        $this->resetPage();
    }

    protected function buildQuery()
    {
        $query = DocumentoCargado::query()->with([
            'contratista.contratistaPadreAprobado', 
            'mandante', 
            'entidad', 
            'validadorAsem', 
            'validadorMandante'
        ]);

        if (!empty($this->filtroContratista)) { $query->whereHas('contratista', function ($q) { $q->where('razon_social', 'like', '%' . $this->filtroContratista . '%')->orWhere('rut', 'like', '%' . $this->filtroContratista . '%'); }); }
        if (!empty($this->filtroMandante)) { $query->where('mandante_id', $this->filtroMandante); }
        if (!empty($this->filtroEntidad)) { $query->where('entidad_type', $this->filtroEntidad); }
        if (!empty($this->filtroDocumento)) { $query->where('nombre_documento_snapshot', 'like', '%' . $this->filtroDocumento . '%'); }
        if (!empty($this->filtroIdEntidad)) {
            $matchingDocIds = [];
            $searchTerm = str_replace(['-', '.', ' '], '', $this->filtroIdEntidad);
            $originalSearchTerm = $this->filtroIdEntidad;
            $vehiculoIds = Vehiculo::where(DB::raw("REPLACE(CONCAT(patente_letras, patente_numeros), ' ', '')"), 'like', "%{$searchTerm}%")->pluck('id');
            if ($vehiculoIds->isNotEmpty()) { $matchingDocIds = array_merge($matchingDocIds, DocumentoCargado::where('entidad_type', Vehiculo::class)->whereIn('entidad_id', $vehiculoIds)->pluck('id')->toArray()); }
            $trabajadorIds = Trabajador::where(function ($query) use ($originalSearchTerm) { $query->where('rut', 'like', "%{$originalSearchTerm}%")->orWhere(DB::raw("CONCAT_WS(' ', nombres, apellido_paterno, apellido_materno)"), 'like', "%{$originalSearchTerm}%"); })->pluck('id');
            if ($trabajadorIds->isNotEmpty()) { $matchingDocIds = array_merge($matchingDocIds, DocumentoCargado::where('entidad_type', Trabajador::class)->whereIn('entidad_id', $trabajadorIds)->pluck('id')->toArray()); }
            $maquinariaIds = Maquinaria::where(DB::raw("REPLACE(CONCAT(IFNULL(identificador_letras, ''), IFNULL(identificador_numeros, '')), ' ', '')"), 'like', "%{$searchTerm}%")->pluck('id');
            if ($maquinariaIds->isNotEmpty()) { $matchingDocIds = array_merge($matchingDocIds, DocumentoCargado::where('entidad_type', Maquinaria::class)->whereIn('entidad_id', $maquinariaIds)->pluck('id')->toArray()); }
            $embarcacionIds = Embarcacion::where(DB::raw("REPLACE(CONCAT(IFNULL(matricula_letras, ''), IFNULL(matricula_numeros, '')), ' ', '')"), 'like', "%{$searchTerm}%")->pluck('id');
            if ($embarcacionIds->isNotEmpty()) { $matchingDocIds = array_merge($matchingDocIds, DocumentoCargado::where('entidad_type', Embarcacion::class)->whereIn('entidad_id', $embarcacionIds)->pluck('id')->toArray()); }
            $contratistaIds = Contratista::where('rut', 'like', "%{$originalSearchTerm}%")->pluck('id');
            if ($contratistaIds->isNotEmpty()) { $matchingDocIds = array_merge($matchingDocIds, DocumentoCargado::where('entidad_type', Contratista::class)->whereIn('entidad_id', $contratistaIds)->pluck('id')->toArray()); }
            if (!empty($matchingDocIds)) { $query->whereIn('id', array_unique($matchingDocIds)); } else { $query->whereRaw('0 = 1'); }
        }
        
        if (!empty($this->filtroEstado)) {
            if ($this->filtroEstado === 'Revisado') { 
                $query->whereNotNull('resultado_validacion')->whereNotIn('estado_validacion', ['Archivado', 'Archivado-Revalidado', 'Revisado-Revalidado']);
            } else { 
                $query->where('estado_validacion', $this->filtroEstado); 
            }
        } elseif (!empty($this->estadosAExcluir)) {
            $query->whereNotIn('estado_validacion', $this->estadosAExcluir);
        }

        if (!empty($this->filtroVigencia)) {
            switch ($this->filtroVigencia) {
                case 'Vigente': $query->where('es_vencimiento_modificado', false)->whereNotNull('fecha_vencimiento')->whereDate('fecha_vencimiento', '>=', now()); break;
                case 'Vencido': $query->where('es_vencimiento_modificado', false)->whereNotNull('fecha_vencimiento')->whereDate('fecha_vencimiento', '<', now()); break;
                case 'Vigente-Modificado': $query->where('es_vencimiento_modificado', true)->whereNotNull('fecha_vencimiento')->whereDate('fecha_vencimiento', '>=', now()); break;
                case 'Vencido-Modificado': $query->where('es_vencimiento_modificado', true)->whereNotNull('fecha_vencimiento')->whereDate('fecha_vencimiento', '<', now()); break;
                case 'Por Periodo': $query->whereNull('fecha_vencimiento'); break;
            }
        }
        if (!empty($this->filtroResultado)) { $query->where('resultado_validacion', $this->filtroResultado); }
        if (!empty($this->filtroValidador)) { 
            $query->where(function ($q) {
                $q->where('asem_validador_id', $this->filtroValidador)
                  ->orWhere('mandante_validador_id', $this->filtroValidador);
            });
        }
        if (!empty($this->filtroFechaDesde)) { $query->whereDate('fecha_validacion', '>=', $this->filtroFechaDesde); }
        if (!empty($this->filtroFechaHasta)) { $query->whereDate('fecha_validacion', '<=', $this->filtroFechaHasta); }
        
        if (!empty($this->filtroFechaCargaDesde)) { $query->whereDate('created_at', '>=', $this->filtroFechaCargaDesde); }
        if (!empty($this->filtroFechaCargaHasta)) { $query->whereDate('created_at', '<=', $this->filtroFechaCargaHasta); }

        if ($this->filtroTipoContratista !== 'todos') {
            $query->whereHas('contratista', function ($contratistaQuery) {
                $contratistaQuery->whereHas('solicitudesVinculacion', function ($solicitudQuery) {
                    $solicitudQuery->where('estado', 'APROBADA');
                    if ($this->filtroTipoContratista === 'contratistas') {
                        $solicitudQuery->whereNull('contratista_padre_id');
                    } elseif ($this->filtroTipoContratista === 'subcontratistas') {
                        $solicitudQuery->whereNotNull('contratista_padre_id');
                    }
                });
            });
        }

        if ($this->filtroErrorValidador) {
            $query->where('es_error_validador', true);
        }

        if ($this->sortField === 'tiempo_en_cola') { 
            $sqlCase = "CASE 
                            WHEN fecha_validacion_asem IS NOT NULL AND fecha_validacion_mandante IS NOT NULL THEN 
                                GREATEST(TIMESTAMPDIFF(HOUR, created_at, fecha_validacion_asem), TIMESTAMPDIFF(HOUR, created_at, fecha_validacion_mandante))
                            WHEN fecha_validacion_asem IS NOT NULL THEN 
                                TIMESTAMPDIFF(HOUR, created_at, fecha_validacion_asem)
                            WHEN fecha_validacion_mandante IS NOT NULL THEN 
                                TIMESTAMPDIFF(HOUR, created_at, fecha_validacion_mandante)
                            ELSE 
                                TIMESTAMPDIFF(HOUR, created_at, NOW()) 
                        END"; 
            $query->orderByRaw("$sqlCase {$this->sortDirection}"); 
        } else { 
            $query->orderBy($this->sortField, $this->sortDirection); 
        }
        return $query;
    }

    public function render()
    {
        $mandantes = Mandante::where('is_active', true)->orderBy('razon_social')->get();
        
        $validadores = User::whereHas('roles', function ($q) { 
            $q->whereIn('name', ['ASEM_Validator', 'ASEM_Admin', 'Mandante_Admin', 'Mandante_Validator']);
        })->orderBy('name')->get();
        
        $documentos = null;
        if ($this->busquedaRealizada) {
            $documentos = $this->buildQuery()->paginate(100);
        } else {
            $documentos = new LengthAwarePaginator(collect(), 0, 100, 1, [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]);
        }

        return view('livewire.asem.gestion-general-documentos', [
            'documentos' => $documentos, 
            'mandantes' => $mandantes, 
            'validadores' => $validadores
        ])->layout('layouts.app');
    }

    public function asignarSeleccionados()
    {
        $this->validate([
            'validadorSeleccionado' => 'required|exists:users,id',
            'documentosSeleccionados' => 'required|array|min:1'
        ]);

        $estadosAsignables = [
            'Sin Asignar', 
            'Asignado', 
            'Devuelto', 
            'Asignar-Revalidar', 
            'Asignado-Revalidar'
        ];

        $query = DocumentoCargado::whereIn('id', $this->documentosSeleccionados)
            ->whereIn('estado_validacion', $estadosAsignables)
            ->whereNull('resultado_validacion')
            ->where(function($q) {
                $q->whereNull('valida_solo_mandante_snapshot')
                  ->orWhere('valida_solo_mandante_snapshot', false);
            });

        $idsParaAsignar = $query->pluck('id')->toArray();
        $conteoOriginal = count($this->documentosSeleccionados);
        $conteoIgnorados = $conteoOriginal - count($idsParaAsignar);

        if (empty($idsParaAsignar)) {
            if ($conteoOriginal > 0) {
                session()->flash('error', 'Ningún documento seleccionado era elegible para la asignación (posiblemente ya están revisados o en un estado no asignable).');
            }
            $this->resetSeleccion();
            return;
        }

        try {
            $filasAfectadas = DB::table('documentos_cargados')
                ->whereIn('id', $idsParaAsignar)
                ->update([
                    'asem_validador_id' => $this->validadorSeleccionado,
                    'observacion_rechazo' => null,
                    'observacion_interna_asem' => null,
                    'estado_validacion' => DB::raw("CASE 
                        WHEN estado_validacion IN ('Asignar-Revalidar', 'Asignado-Revalidar') THEN 'Asignado-Revalidar'
                        ELSE 'Asignado'
                    END")
                ]);

            session()->flash('message', $filasAfectadas . ' documento(s) asignado(s) correctamente.');
            Log::info('Documentos ' . implode(', ', $idsParaAsignar) . ' asignados al validador ID: ' . $this->validadorSeleccionado . ' por el usuario ID: ' . Auth::id());

            if ($conteoIgnorados > 0) {
                session()->flash('warning', $conteoIgnorados . ' documento(s) no fueron modificados porque ya estaban revisados o en un estado no asignable.');
            }
            
            $this->resetSeleccion();

        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error al asignar los documentos.');
            Log::error('Error asignando documentos: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
    }

    public function desasignarSeleccionados()
    {
        $this->validate(['documentosSeleccionados' => 'required|array|min:1']);
        $idsParaDesasignar = DocumentoCargado::whereIn('id', $this->documentosSeleccionados) ->whereNull('resultado_validacion') ->pluck('id') ->toArray();
        $conteoOriginal = count($this->documentosSeleccionados);
        $conteoIgnorados = $conteoOriginal - count($idsParaDesasignar);
        try {
            if (!empty($idsParaDesasignar)) {
                $updates = [ 'asem_validador_id' => null, 'observacion_interna_asem' => null, ];
                DocumentoCargado::whereIn('id', $idsParaDesasignar)->where('estado_validacion', 'like', '%-Revalidar%')->update(array_merge($updates, ['estado_validacion' => 'Asignar-Revalidar']));
                DocumentoCargado::whereIn('id', $idsParaDesasignar)->where('estado_validacion', 'not like', '%-Revalidar%')->update(array_merge($updates, ['estado_validacion' => 'Sin Asignar']));
                session()->flash('message', count($idsParaDesasignar) . ' documento(s) desasignado(s) y devuelto(s) a la cola principal.');
                Log::info('Documentos ' . implode(', ', $idsParaDesasignar) . ' fueron desasignados.');
            }
            if ($conteoIgnorados > 0) { session()->flash('warning', $conteoIgnorados . ' documento(s) no fueron modificados porque ya estaban revisados (Aprobados/Rechazados).'); }
            if (empty($idsParaDesasignar) && $conteoOriginal > 0) {  session()->flash('error', 'Ningún documento seleccionado era elegible para la desasignación.'); }
            $this->resetSeleccion();
        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error al desasignar los documentos.');
            Log::error('Error desasignando documentos: ' . $e->getMessage());
        }
    }

    public function revalidarSeleccionados()
    {
        $this->validate([
            'seleccionParaRevalidar' => 'required|array|min:1',
            'motivoRevalidacionMasiva' => 'required|string|min:10',
        ], [
            'seleccionParaRevalidar.required' => 'Debe seleccionar al menos un documento para revalidar.',
            'motivoRevalidacionMasiva.required' => 'El motivo de revalidación es obligatorio.',
            'motivoRevalidacionMasiva.min' => 'El motivo debe tener al menos 10 caracteres.',
        ]);

        $docsParaRevalidar = DocumentoCargado::with('entidad')->whereIn('id', $this->seleccionParaRevalidar)->whereNotNull('resultado_validacion')->get();

        if ($docsParaRevalidar->count() !== count($this->seleccionParaRevalidar)) {
            session()->flash('error', 'Algunos de los documentos seleccionados no son elegibles para revalidación.');
            $this->resetSeleccion();
            return;
        }

        try {
            DB::transaction(function () use ($docsParaRevalidar) {
                foreach ($docsParaRevalidar as $originalDoc) {
                    
                    $reglaActual = ReglaDocumental::with(['nombreDocumento', 'tipoVencimiento', 'observacionDocumento', 'formatoDocumento', 'criterios.criterioEvaluacion', 'criterios.subCriterio', 'criterios.textoRechazo', 'criterios.aclaracionCriterio'])->find($originalDoc->regla_documental_id_origen);
                    
                    $originalDoc->update(['estado_validacion' => 'Archivado-Revalidado']);

                    $nuevoDoc = $originalDoc->replicate([
                        'resultado_validacion', 'asem_validador_id', 'fecha_validacion', 
                        'observacion_interna_asem', 'observacion_rechazo', 'motivo_revalidacion',
                        'es_error_validador'
                    ]);

                    if ($reglaActual) {
                        $nuevoDoc->nombre_documento_snapshot = $reglaActual->nombreDocumento->nombre;
                        $nuevoDoc->tipo_vencimiento_snapshot = $reglaActual->tipoVencimiento->nombre;
                        $nuevoDoc->valida_emision_snapshot = $reglaActual->valida_emision;
                        $nuevoDoc->valida_vencimiento_snapshot = $reglaActual->valida_vencimiento;
                        $nuevoDoc->valor_nominal_snapshot = $reglaActual->valor_nominal_documento;
                        $nuevoDoc->observacion_documento_snapshot = $reglaActual->observacionDocumento->observacion ?? null;
                        $nuevoDoc->formato_documento_snapshot = $reglaActual->formatoDocumento->nombre ?? null;
                        $nuevoDoc->documento_relacionado_id_snapshot = $reglaActual->documento_relacionado_id;
                        
                        $nuevoDoc->criterios_snapshot = $reglaActual->criterios->map(function ($criterioPivote) {
                            return [
                                'criterio' => $criterioPivote->criterioEvaluacion->nombre_criterio ?? 'Criterio no encontrado',
                                'texto_rechazo' => $criterioPivote->textoRechazo->texto_rechazo ?? null,
                                'sub_criterio' => $criterioPivote->subCriterio->nombre ?? null,
                                'aclaracion' => $criterioPivote->aclaracionCriterio->texto_aclaracion ?? null,
                            ];
                        })->toArray();
                    }
                    
                    $nuevoDoc->estado_validacion = 'Asignar-Revalidar';
                    $nuevoDoc->motivo_revalidacion = $this->motivoRevalidacionMasiva;
                    $nuevoDoc->created_at = now();
                    $nuevoDoc->updated_at = now();
                    $nuevoDoc->save();

                    Log::info("Revalidación Masiva para Doc ID: {$originalDoc->id}. Nuevo Doc ID creado: {$nuevoDoc->id}. Regla actualizada aplicada. User ID: " . auth()->id());
                }
            });

            $recursosAfectados = $docsParaRevalidar->pluck('entidad')->unique('id');
            foreach ($recursosAfectados as $recurso) {
                if ($recurso) {
                    ActualizarEstadoRecursoIndividual::dispatch($recurso);
                }
            }

            session()->flash('message', $docsParaRevalidar->count() . ' documento(s) enviados a revalidación con las reglas actuales. Se han creado nuevas solicitudes.');
            $this->resetSeleccion();

        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error al procesar la solicitud de revalidación masiva.');
            Log::error('Error en revalidarSeleccionados: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->resetSeleccion();
        }
    }

    public function abrirModalModificarVencimiento() {
        $this->resetErrorBag();
        $this->tipoModificacion = 'fecha_fija';
        $this->fechaFija = null;
        $this->diasASumar = null;
        $this->motivoModificacion = '';
        $this->justificativoModificacion = null;
        $this->showModificarVencimientoModal = true;
    }
    
    public function cerrarModalModificarVencimiento() {
        $this->showModificarVencimientoModal = false;
    }
    
    public function confirmarModificacionVencimiento()
    {
        try {
            $rules = [ 
                'seleccionParaModificar' => 'required|array|min:1', 
                'motivoModificacion' => 'required|string|min:15', 
                'tipoModificacion' => 'required|in:fecha_fija,sumar_dias', 
                'justificativoModificacion' => 'nullable|' . $this->getFileValidationRule('justificativo'), 
            ];
            if ($this->tipoModificacion === 'fecha_fija') { $rules['fechaFija'] = 'required|date'; } else { $rules['diasASumar'] = 'required|integer'; }
            
            $this->validate($rules, [ 
                'seleccionParaModificar.required' => 'Debe seleccionar al menos un documento.', 
                'motivoModificacion.required' => 'El motivo es obligatorio.', 
                'motivoModificacion.min' => 'El motivo debe tener al menos 15 caracteres.', 
                'fechaFija.required' => 'Debe especificar una fecha fija.', 
                'diasASumar.required' => 'Debe especificar los días a sumar/restar.', 
                'diasASumar.integer' => 'El valor debe ser un número entero (positivo o negativo).', 
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($this->justificativoModificacion) {
                $this->validateSecureFile($this->justificativoModificacion, 'justificativo', 'ASEM_MOD_VENC');
            }
            throw $e;
        }
        $documentos = DocumentoCargado::with('entidad')->whereIn('id', $this->seleccionParaModificar)->whereNotNull('resultado_validacion')->get();
        if ($documentos->isEmpty()) { session()->flash('error', 'No se encontraron documentos válidos para modificar.'); $this->cerrarModalModificarVencimiento(); return; }
        try {
            DB::transaction(function () use ($documentos) {
                $rutaArchivo = null;
                if ($this->justificativoModificacion) { $rutaArchivo = $this->justificativoModificacion->store('justificativos_vencimiento', 'public'); }
                foreach ($documentos as $doc) {
                    $nuevaFechaVencimiento = null;
                    if ($this->tipoModificacion === 'fecha_fija') { $nuevaFechaVencimiento = Carbon::parse($this->fechaFija); } else { if ($doc->fecha_vencimiento) { $dias = (int)$this->diasASumar; $nuevaFechaVencimiento = Carbon::parse($doc->fecha_vencimiento)->addDays($dias); } }
                    if ($nuevaFechaVencimiento) { $doc->update(['fecha_vencimiento' => $nuevaFechaVencimiento, 'es_vencimiento_modificado' => true, 'motivo_modificacion_vencimiento' => $this->motivoModificacion, 'ruta_justificativo_modificacion' => $rutaArchivo,]); Log::info("Vencimiento del Doc ID {$doc->id} modificado por User ID: " . auth()->id()); }
                }
            });

            $recursosAfectados = $documentos->pluck('entidad')->unique('id');
            foreach ($recursosAfectados as $recurso) {
                if ($recurso) {
                    ActualizarEstadoRecursoIndividual::dispatch($recurso);
                }
            }

            session()->flash('message', count($documentos) . ' vencimiento(s) de documento(s) han sido modificados exitosamente.');
            $this->resetSeleccion();
        } catch (\Exception $e) { Log::error("Error modificando vencimientos: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]); session()->flash('error', 'Ocurrió un error inesperado al modificar los vencimientos.'); } finally { $this->cerrarModalModificarVencimiento(); }
    }
    
    public function abrirModalAuditoria($documentoId, $soloLectura = false) {
        $this->documentoAuditoria = DocumentoCargado::with([
            'validadorAsem',
            'validadorMandante', 
            'contratista',
            'mandante',
            'entidad', 
            'reglaDocumental.observacionDocumento',
            'reglaDocumental.formatoDocumento'
        ])->find($documentoId);
        
        $this->cargoAuditoria = null;
        if ($this->documentoAuditoria && $this->documentoAuditoria->entidad_type === Trabajador::class) {
            $vinculacion = TrabajadorVinculacion::with('cargoMandante:id,nombre_cargo')
                ->where('trabajador_id', $this->documentoAuditoria->entidad_id)
                ->where('unidad_organizacional_mandante_id', $this->documentoAuditoria->unidad_organizacional_id)
                ->where('is_active', true)
                ->first();
            
            if ($vinculacion && $vinculacion->cargoMandante) {
                $this->cargoAuditoria = $vinculacion->cargoMandante->nombre_cargo;
            }
        }

        $this->esAuditoriaSoloLectura = $soloLectura;
        $this->motivoRevalidacionIndividual = '';
        $this->marcarComoErrorValidador = false;
        $this->resetErrorBag();
        $this->showAuditoriaModal = true;
    }
    
    public function cerrarModalAuditoria() {
        $this->showAuditoriaModal = false;
        $this->documentoAuditoria = null;
        $this->esAuditoriaSoloLectura = false;
        $this->cargoAuditoria = null;
    }
    
    public function iniciarRevalidacionIndividual() {
        if ($this->esAuditoriaSoloLectura) { return; }
        $this->validate([ 'motivoRevalidacionIndividual' => 'required|string|min:10', ], [ 'motivoRevalidacionIndividual.required' => 'El motivo de revalidación es obligatorio.', 'motivoRevalidacionIndividual.min' => 'El motivo debe tener al menos 10 caracteres.', ]);
        if (!$this->documentoAuditoria) { session()->flash('error', 'No se pudo encontrar el documento a revalidar. Por favor, recargue la página.'); $this->cerrarModalAuditoria(); return; }
        try {
            DB::transaction(function () {
                $originalDoc = $this->documentoAuditoria;
                
                $updateDataOriginal = ['estado_validacion' => 'Archivado-Revalidado'];
                if ($this->marcarComoErrorValidador) {
                    $updateDataOriginal['es_error_validador'] = true;
                }
                $originalDoc->update($updateDataOriginal);

                $reglaActual = ReglaDocumental::with([ 'nombreDocumento', 'tipoVencimiento', 'observacionDocumento', 'formatoDocumento', 'criterios.criterioEvaluacion', 'criterios.subCriterio', 'criterios.textoRechazo', 'criterios.aclaracionCriterio' ])->find($originalDoc->regla_documental_id_origen);
                
                $nuevoDoc = $originalDoc->replicate([
                    'resultado_validacion', 'asem_validador_id', 'fecha_validacion', 
                    'observacion_interna_asem', 'observacion_rechazo', 'motivo_revalidacion',
                    'es_error_validador'
                ]);

                if ($reglaActual) {
                    $nuevoDoc->nombre_documento_snapshot = $reglaActual->nombreDocumento->nombre; $nuevoDoc->tipo_vencimiento_snapshot = $reglaActual->tipoVencimiento->nombre; $nuevoDoc->valida_emision_snapshot = $reglaActual->valida_emision; $nuevoDoc->valida_vencimiento_snapshot = $reglaActual->valida_vencimiento; $nuevoDoc->valor_nominal_snapshot = $reglaActual->valor_nominal_documento; $nuevoDoc->observacion_documento_snapshot = $reglaActual->observacionDocumento->observacion ?? null; $nuevoDoc->formato_documento_snapshot = $reglaActual->formatoDocumento->nombre ?? null; $nuevoDoc->documento_relacionado_id_snapshot = $reglaActual->documento_relacionado_id;
                    
                    $nuevoDoc->criterios_snapshot = $reglaActual->criterios->map(function ($criterioPivote) { 
                        return [ 
                            'criterio' => $criterioPivote->criterioEvaluacion->nombre_criterio ?? 'Criterio no encontrado', 
                            'texto_rechazo' => $criterioPivote->textoRechazo->texto_rechazo ?? null, 
                            'sub_criterio' => $criterioPivote->subCriterio->nombre ?? null, 
                            'aclaracion' => $criterioPivote->aclaracionCriterio->texto_aclaracion ?? null, 
                        ]; 
                    })->toArray();
                }
                $nuevoDoc->estado_validacion = 'Asignar-Revalidar'; $nuevoDoc->motivo_revalidacion = $this->motivoRevalidacionIndividual; $nuevoDoc->created_at = now(); $nuevoDoc->updated_at = now(); $nuevoDoc->save();
                Log::info("Revalidación Individual para Doc ID: {$originalDoc->id}. Nuevo Doc ID creado: {$nuevoDoc->id}. Regla actualizada aplicada. User ID: " . auth()->id());
            });

            if ($this->documentoAuditoria->entidad) {
                ActualizarEstadoRecursoIndividual::dispatch($this->documentoAuditoria->entidad);
            }

            session()->flash('message', 'El documento ha sido enviado a revalidación con las reglas actuales. Se ha creado una nueva solicitud.');
            $this->cerrarModalAuditoria();
        } catch (\Exception $e) { session()->flash('error', 'Ocurrió un error al procesar la solicitud de revalidación.'); Log::error('Error en iniciarRevalidacionIndividual: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]); $this->cerrarModalAuditoria(); }
    }
    
    public function abrirModalNotificacion() {
        $documentosQuery = $this->buildQuery(); $documentosParaNotificar = $documentosQuery->get(); $totalDocumentos = $documentosParaNotificar->count(); $totalContratistas = $documentosParaNotificar->pluck('contratista_id')->unique()->count();
        $this->conteoNotificacion = [ 'total' => $totalDocumentos, 'contratistas' => $totalContratistas, ];
        $this->mensajeNotificacion = "Le informamos que se ha detectado la siguiente documentación con observaciones en nuestra plataforma, en relación a su prestación de servicios.\n\n" . "Agradecemos proceder a su regularización a la brevedad para mantener el cumplimiento normativo.";
        $this->resetErrorBag(); $this->showNotificacionModal = true;
    }
    
    public function cerrarModalNotificacion() { $this->showNotificacionModal = false; $this->mensajeNotificacion = ''; }
    
    public function despacharNotificaciones() {
        $this->validate(['mensajeNotificacion' => 'required|string|min:20'], [ 'mensajeNotificacion.required' => 'El mensaje de notificación no puede estar vacío.', 'mensajeNotificacion.min' => 'El mensaje debe tener al menos 20 caracteres.' ]);
        $documentoIds = $this->buildQuery()->pluck('id')->toArray();
        if (empty($documentoIds)) { session()->flash('error', 'No hay documentos en la vista actual para notificar.'); $this->cerrarModalNotificacion(); return; }
        NotificarDocumentosContratista::dispatch($documentoIds, $this->mensajeNotificacion, 'Notificación Manual', Auth::id());
        session()->flash('message', 'La tarea de notificación ha sido enviada a la cola. Los correos se enviarán en segundo plano.'); $this->cerrarModalNotificacion();
    }

    private function getDatosInformeProduccion()
    {
        $baseQuery = $this->buildQuery()->whereNotNull('resultado_validacion');
        $documentosIds = $baseQuery->pluck('id');

        $todosLosValidadores = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['ASEM_Validator', 'ASEM_Admin', 'Mandante_Admin', 'Mandante_Validator']);
        })->select('id', 'name')->orderBy('name')->get();

        if ($documentosIds->isEmpty()) {
            return $todosLosValidadores->map(function ($validador) {
                return (object) [
                    'validador_id' => $validador->id,
                    'validador_nombre' => $validador->name,
                    'rol' => 'N/A',
                    'total_revisados' => 0, 'aprobados' => 0, 'rechazados' => 0, 'errores' => 0,
                ];
            });
        }

        $subQueryAsem = DB::table('documentos_cargados')
            ->whereIn('id', $documentosIds)
            ->whereNotNull('asem_validador_id')
            ->select(
                'asem_validador_id as validador_id',
                DB::raw('"ASEM" as rol'),
                DB::raw('COUNT(id) as total_revisados'),
                DB::raw("SUM(CASE WHEN resultado_validacion = 'Aprobado' THEN 1 ELSE 0 END) as aprobados"),
                DB::raw("SUM(CASE WHEN resultado_validacion = 'Rechazado' THEN 1 ELSE 0 END) as rechazados"),
                DB::raw("SUM(es_error_validador) as errores")
            )
            ->groupBy('validador_id', 'rol');

        $subQueryMandante = DB::table('documentos_cargados')
            ->whereIn('id', $documentosIds)
            ->whereNotNull('mandante_validador_id')
            ->select(
                'mandante_validador_id as validador_id',
                DB::raw('"Mandante" as rol'),
                DB::raw('COUNT(id) as total_revisados'),
                DB::raw("SUM(CASE WHEN resultado_validacion = 'Aprobado' THEN 1 ELSE 0 END) as aprobados"),
                DB::raw("SUM(CASE WHEN resultado_validacion = 'Rechazado' THEN 1 ELSE 0 END) as rechazados"),
                DB::raw("SUM(es_error_validador) as errores")
            )
            ->groupBy('validador_id', 'rol');
        
        $produccion = $subQueryAsem->unionAll($subQueryMandante)->get()->keyBy('validador_id');

        $resultados = $todosLosValidadores->map(function ($validador) use ($produccion) {
            $datosProduccion = $produccion->get($validador->id);
            return (object) [
                'validador_id' => $validador->id,
                'validador_nombre' => $validador->name,
                'rol' => $datosProduccion->rol ?? 'N/A',
                'total_revisados' => (int) ($datosProduccion->total_revisados ?? 0),
                'aprobados' => (int) ($datosProduccion->aprobados ?? 0),
                'rechazados' => (int) ($datosProduccion->rechazados ?? 0),
                'errores' => (int) ($datosProduccion->errores ?? 0),
            ];
        });

        return $resultados;
    }

    private function getDatosInformeProduccionGranular()
    {
        $baseQuery = $this->buildQuery()->whereNotNull('resultado_validacion');
        $documentosIds = $baseQuery->pluck('id');

        if ($documentosIds->isEmpty()) {
            return collect();
        }

        $subQueryAsem = DB::table('documentos_cargados')
            ->whereIn('id', $documentosIds)
            ->whereNotNull('asem_validador_id')
            ->select(
                'asem_validador_id as validador_id',
                'nombre_documento_snapshot as documento_nombre',
                DB::raw("SUM(CASE WHEN resultado_validacion = 'Aprobado' THEN 1 ELSE 0 END) as aprobados"),
                DB::raw("SUM(CASE WHEN resultado_validacion = 'Rechazado' THEN 1 ELSE 0 END) as rechazados")
            )
            ->groupBy('validador_id', 'documento_nombre');

        $subQueryMandante = DB::table('documentos_cargados')
            ->whereIn('id', $documentosIds)
            ->whereNotNull('mandante_validador_id')
            ->select(
                'mandante_validador_id as validador_id',
                'nombre_documento_snapshot as documento_nombre',
                DB::raw("SUM(CASE WHEN resultado_validacion = 'Aprobado' THEN 1 ELSE 0 END) as aprobados"),
                DB::raw("SUM(CASE WHEN resultado_validacion = 'Rechazado' THEN 1 ELSE 0 END) as rechazados")
            )
            ->groupBy('validador_id', 'documento_nombre');

        return $subQueryAsem->unionAll($subQueryMandante)->get();
    }

    public function abrirModalInformeProduccion()
    {
        $this->datosInformeProduccion = $this->getDatosInformeProduccion();
        $this->validadoresParaExportar = $this->datosInformeProduccion->pluck('validador_id')->toArray();
        $this->seleccionarTodosValidadores = true;
        $this->formatosExportacion = [];
        $this->showInformeProduccionModal = true;
    }

    public function updatedSeleccionarTodosValidadores($value)
    {
        if ($value) {
            $this->validadoresParaExportar = $this->datosInformeProduccion->pluck('validador_id')->toArray();
        } else {
            $this->validadoresParaExportar = [];
        }
    }

    public function cerrarModalInformeProduccion()
    {
        $this->showInformeProduccionModal = false;
        $this->datosInformeProduccion = [];
        $this->validadoresParaExportar = [];
    }

    public function exportarInformeProduccion()
    {
        $this->validate(
            ['formatosExportacion' => 'required|array|min:1'], 
            ['formatosExportacion.required' => 'Debe seleccionar al menos un formato de exportación.']
        );

        $datosConsolidados = $this->getDatosInformeProduccion()->whereIn('validador_id', $this->validadoresParaExportar);
        
        if ($datosConsolidados->isEmpty()) {
            session()->flash('error', 'No hay datos para exportar con los validadores seleccionados.');
            return;
        }

        $filtros = [
            'fecha_desde' => $this->filtroFechaDesde,
            'fecha_hasta' => $this->filtroFechaHasta,
            'documento' => $this->filtroDocumento,
        ];

        $timestamp = now()->format('Y-m-d_His');
        $archivosGenerados = [];

        if (in_array('excel', $this->formatosExportacion)) {
            $nombreArchivo = "informe_produccion_{$timestamp}.xlsx";
            Excel::store(new ProduccionValidadoresExport($datosConsolidados, $filtros), $nombreArchivo, 'local');
            $archivosGenerados['excel'] = ['nombre' => $nombreArchivo, 'ruta' => Storage::disk('local')->path($nombreArchivo)];
        }
        
        if (in_array('pdf', $this->formatosExportacion)) {
            $nombreArchivo = "informe_produccion_{$timestamp}.pdf";
            $pdf = Pdf::loadView('exports.informe-produccion', ['datos' => $datosConsolidados, 'filtros' => $filtros]);
            Storage::disk('local')->put($nombreArchivo, $pdf->output());
            $archivosGenerados['pdf'] = ['nombre' => $nombreArchivo, 'ruta' => Storage::disk('local')->path($nombreArchivo)];
        }
        
        if (in_array('html', $this->formatosExportacion)) {
            $datosGranulares = $this->getDatosInformeProduccionGranular()->whereIn('validador_id', $this->validadoresParaExportar);
            $listaDeDocumentos = $datosGranulares->pluck('documento_nombre')->unique()->sort()->values();

            $nombreArchivo = "dashboard_produccion_{$timestamp}.html";
            $html = view('exports.informe-produccion-interactivo', [
                'datosConsolidados' => $datosConsolidados,
                'datosGranulares' => $datosGranulares,
                'listaDeDocumentos' => $listaDeDocumentos,
                'filtros' => $filtros
            ])->render();
            Storage::disk('local')->put($nombreArchivo, $html);
            $archivosGenerados['html'] = ['nombre' => $nombreArchivo, 'ruta' => Storage::disk('local')->path($nombreArchivo)];
        }

        if (count($archivosGenerados) > 1) {
            $zipFileName = "informes_produccion_{$timestamp}.zip";
            $zipPath = Storage::disk('local')->path($zipFileName);
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                foreach ($archivosGenerados as $file) {
                    $zip->addFile($file['ruta'], $file['nombre']);
                }
                $zip->close();
            }
            foreach ($archivosGenerados as $file) { Storage::disk('local')->delete($file['nombre']); }
            return response()->download($zipPath)->deleteFileAfterSend(true);
        } elseif (count($archivosGenerados) === 1) {
            $file = array_pop($archivosGenerados);
            return response()->download($file['ruta'], $file['nombre'])->deleteFileAfterSend(true);
        }
    }

    public function abrirModalColores()
    {
        $this->resetErrorBag();
        $this->selectedMandanteForColors = null;
        $this->colorConfigs = [];
        $this->reset('newRuleHorasInicio', 'newRuleHorasFin', 'newRuleColorSeleccionado');
        $this->showColorConfigModal = true;
    }

    public function cerrarModalColores()
    {
        $this->showColorConfigModal = false;
    }

    public function updatedSelectedMandanteForColors($mandanteId)
    {
        if ($mandanteId) {
            $this->colorConfigs = Mandante::findOrFail($mandanteId)->colorConfiguraciones;
        } else {
            $this->colorConfigs = [];
        }
    }

    public function guardarNuevaReglaColor()
    {
        $this->validate([
            'selectedMandanteForColors' => 'required|exists:mandantes,id',
            'newRuleHorasInicio' => 'required|integer|min:0',
            'newRuleHorasFin' => 'required|integer|gt:newRuleHorasInicio',
            'newRuleColorSeleccionado' => 'required|in:' . implode(',', array_keys($this->opcionesDeColor)),
        ], [
            'selectedMandanteForColors.required' => 'Debe seleccionar un mandante.',
            'newRuleHorasFin.gt' => 'Las horas de fin deben ser mayores que las de inicio.',
            'newRuleColorSeleccionado.required' => 'Debe seleccionar un nivel de alerta.',
        ]);

        $colorSeleccionado = $this->opcionesDeColor[$this->newRuleColorSeleccionado];

        MandanteColorConfiguracion::create([
            'mandante_id' => $this->selectedMandanteForColors,
            'horas_inicio' => $this->newRuleHorasInicio,
            'horas_fin' => $this->newRuleHorasFin,
            'color_fondo' => $colorSeleccionado['fondo'],
            'color_texto' => $colorSeleccionado['texto'],
        ]);

        $this->colorConfigs = Mandante::findOrFail($this->selectedMandanteForColors)->colorConfiguraciones;
        $this->reset('newRuleHorasInicio', 'newRuleHorasFin', 'newRuleColorSeleccionado');
        Cache::forget('color_config_mandante_' . $this->selectedMandanteForColors);
        session()->flash('message-modal-colores', 'Nueva regla de color guardada.');
    }

    public function eliminarReglaColor($id)
    {
        $regla = MandanteColorConfiguracion::findOrFail($id);
        $mandanteId = $regla->mandante_id;
        $regla->delete();

        $this->colorConfigs = Mandante::findOrFail($mandanteId)->colorConfiguraciones;
        Cache::forget('color_config_mandante_' . $mandanteId);
        session()->flash('message-modal-colores', 'Regla de color eliminada.');
    }

    public function abrirModalMapaCalor()
    {
        $this->mapaCalorData = $this->calcularDatosMapaCalor();
        $this->showMapaCalorModal = true;
    }

    public function cerrarModalMapaCalor()
    {
        $this->showMapaCalorModal = false;
        $this->mapaCalorData = [];
    }

    private function calcularDatosMapaCalor()
    {
        $mandantes = Mandante::with('colorConfiguraciones')->where('is_active', true)->get();
        $documentosEnCola = DocumentoCargado::whereNull('resultado_validacion')
            ->where('estado_validacion', '!=', 'Pendiente Validación Mandante')
            ->get();

        $mapa = [];
        $entidades = ['Empresa', 'Trabajadores', 'Vehiculos', 'Maquinaria', 'Embarcaciones'];
        $colores = ['black', 'red', 'orange', 'yellow', 'safe'];

        foreach ($mandantes as $mandante) {
            $mapa[$mandante->id]['mandante_nombre'] = $mandante->razon_social;
            foreach ($entidades as $entidad) {
                foreach ($colores as $color) {
                    $mapa[$mandante->id]['entidades'][$entidad][$color] = 0;
                }
            }
        }

        foreach ($documentosEnCola as $doc) {
            if (!isset($mapa[$doc->mandante_id])) continue;

            $horas = $doc->horas_en_cola;
            $categoriaColor = 'safe';

            if (!is_null($horas)) {
                $configuraciones = $doc->mandante->colorConfiguraciones;
                if ($configuraciones->isNotEmpty()) {
                    foreach ($configuraciones as $config) {
                        if ($horas >= $config->horas_inicio && $horas <= $config->horas_fin) {
                            if (str_contains($config->color_fondo, 'black')) $categoriaColor = 'black';
                            elseif (str_contains($config->color_fondo, 'red')) $categoriaColor = 'red';
                            elseif (str_contains($config->color_fondo, 'orange')) $categoriaColor = 'orange';
                            elseif (str_contains($config->color_fondo, 'yellow')) $categoriaColor = 'yellow';
                            break;
                        }
                    }
                }
            }

            $tipoEntidad = class_basename($doc->entidad_type);
            $entidadKey = '';
            switch ($tipoEntidad) {
                case 'Contratista': $entidadKey = 'Empresa'; break;
                case 'Trabajador': $entidadKey = 'Trabajadores'; break;
                case 'Vehiculo': $entidadKey = 'Vehiculos'; break;
                case 'Maquinaria': $entidadKey = 'Maquinaria'; break;
                case 'Embarcacion': $entidadKey = 'Embarcaciones'; break;
            }

            if ($entidadKey && isset($mapa[$doc->mandante_id]['entidades'][$entidadKey])) {
                $mapa[$doc->mandante_id]['entidades'][$entidadKey][$categoriaColor]++;
            }
        }

        foreach ($mapa as $mandanteId => &$data) {
            $totalGeneralMandante = 0;
            foreach ($entidades as $entidad) {
                $subtotalEntidad = array_sum($data['entidades'][$entidad]);
                $data['entidades'][$entidad]['subtotal'] = $subtotalEntidad;
                $totalGeneralMandante += $subtotalEntidad;
            }
            $data['total_general'] = $totalGeneralMandante;
        }

        return array_values($mapa);
    }
}