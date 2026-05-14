<?php

namespace App\Livewire\Mandante;

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
use App\Models\TrabajadorVinculacion;
use App\Services\DocumentoRequeridoService;
use App\Jobs\NotificarDocumentosContratista;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use App\Jobs\ActualizarEstadoRecursoIndividual;

class GestionGeneralDocumentos extends Component
{
    use WithPagination, WithFileUploads, \App\Traits\ValidatesFileUpload;

    // --- PROPIEDADES DE FILTRADO ---
    #[Url]
    public $filtroContratista = '';
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
    public $filtroTipoContratista = 'todos';
    #[Url]
    public $filtroValidador = '';
    #[Url]
    public $filtroResponsabilidad = 'todos';

    public bool $busquedaRealizada = false;
    
    // --- PROPIEDADES DE FILTRADO POR EXCLUSIÓN ---
    public $listaDeEstados = [];
    public $listaDeColumnas = [];
    
    // --- PROPIEDADES DE GESTIÓN Y ASIGNACIÓN ---
    public $documentosSeleccionados = [];
    public $validadorSeleccionado = null;
    public $seleccionarTodos = false;

    // --- PROPIEDADES PARA REVALIDACIÓN MASIVA ---
    public $seleccionParaRevalidar = [];
    public $motivoRevalidacionMasiva = '';
    public $seleccionarTodosRevalidar = false;
    
    // --- PROPIEDADES PARA MODIFICAR VENCIMIENTO ---
    public $seleccionParaModificar = [];
    public $showModificarVencimientoModal = false;
    public $tipoModificacion = 'fecha_fija';
    public $fechaFija;
    public $diasASumar;
    public $motivoModificacion = '';
    public $justificativoModificacion;
    public $seleccionarTodosModificar = false; // NUEVO

    // --- PROPIEDADES PARA AUDITORÍA (SOLO LECTURA) ---
    public $showAuditoriaModal = false;
    public $documentoAuditoria = null;
    public $esAuditoriaSoloLectura = true; // Siempre será solo lectura para el mandante
    public ?string $cargoAuditoria = null;
    
    // --- PROPIEDADES PARA NOTIFICACIONES ---
    public $showNotificacionModal = false;
    public $conteoNotificacion = ['total' => 0, 'contratistas' => 0];
    public $mensajeNotificacion = '';

    // --- PROPIEDAD PARA CONTROL DE SOLO LECTURA (Mandante_Ver) ---
    public bool $esSoloLectura = false;

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
        // Determinar si el usuario es de solo lectura (Mandante_Ver)
        $this->esSoloLectura = Auth::user()->hasRole('Mandante_Ver');

        $this->listaDeEstados = [
            'Sin Asignar' => 'Sin Asignar',
            'Asignado' => 'Asignado',
            'Devuelto' => 'Devuelto',
            'Pendiente Validación Mandante' => 'Pendiente Validación Principal',
            'Revisado' => 'Revisado (Finalizado)',
            'Archivado' => 'Archivado',
            'Archivado-Revalidado' => 'Archivado (Por Revalidación)',
        ];

        $this->listaDeColumnas = [
            'contratista' => 'Contratista',
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
    }
    
    public function updated($propertyName) { if(in_array($propertyName, ['filtroContratista', 'filtroEntidad', 'filtroDocumento', 'filtroIdEntidad', 'filtroEstado', 'filtroResultado', 'filtroVigencia', 'filtroFechaDesde', 'filtroFechaHasta', 'filtroFechaCargaDesde', 'filtroFechaCargaHasta', 'filtroTipoContratista', 'filtroValidador'])) { $this->resetPage(); } }
    
    public function updatedSeleccionarTodos($value) {
        if ($value) {
            $this->documentosSeleccionados = $this->buildQuery()
                ->whereNull('resultado_validacion')
                ->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->documentosSeleccionados = [];
        }
    }

    public function updatedSeleccionarTodosRevalidar($value)
    {
        $documentos = $this->buildQuery()->paginate(100);
        if ($value) {
            $this->seleccionParaRevalidar = $documentos->filter(function ($doc) {
                return $doc->resultado_validacion 
                    && !in_array($doc->estado_validacion, ['Archivado', 'Archivado-Revalidado'])
                    && (bool)$doc->valida_solo_mandante_snapshot;
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
        $this->seleccionarTodos = false;
        $this->seleccionParaRevalidar = [];
        $this->seleccionarTodosRevalidar = false;
        $this->motivoRevalidacionMasiva = '';
        $this->seleccionParaModificar = [];
        $this->seleccionarTodosModificar = false;
        $this->cerrarModalModificarVencimiento();
        $this->cerrarModalAuditoria();
        $this->cerrarModalNotificacion();
    }

    public function sortBy($field) { if ($this->sortField === $field) { $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc'; } else { $this->sortDirection = 'asc'; } $this->sortField = $field; }

    public function resetearFiltros()
    {
        $this->reset([
            'filtroContratista', 'filtroEntidad',
            'filtroDocumento', 'filtroIdEntidad', 'filtroEstado',
            'filtroResultado', 'filtroVigencia',
            'filtroFechaDesde', 'filtroFechaHasta', 'filtroFechaCargaDesde', 'filtroFechaCargaHasta',
            'filtroTipoContratista', 'filtroResponsabilidad'
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

    protected function buildQuery()
    {
        $mandanteId = Auth::user()->mandante_id;
        if (!$mandanteId) {
            return DocumentoCargado::whereRaw('0 = 1'); // No retornar nada si el usuario no tiene un mandante asociado
        }

        $query = DocumentoCargado::query()
            ->where('mandante_id', $mandanteId) // Filtro automático y obligatorio por mandante
            ->with([
                'contratista.contratistaPadreAprobado', 
                'mandante', 
                'entidad', 
                'validadorAsem', 
                'validadorMandante'
            ]);

        // Si es Validador (y no Admin), filtrar solo lo que tiene asignado
        if (Auth::user()->hasRole('Mandante_Validator') && !Auth::user()->hasRole('Mandante_Admin')) {
            $query->where('mandante_validador_id', Auth::id());
        }

        if (!empty($this->filtroContratista)) { $query->whereHas('contratista', function ($q) { $q->where('razon_social', 'like', '%' . $this->filtroContratista . '%')->orWhere('rut', 'like', '%' . $this->filtroContratista . '%'); }); }
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
        if (!empty($this->filtroValidador)) { $query->where('mandante_validador_id', $this->filtroValidador); }
        if (!empty($this->filtroFechaDesde)) { $query->whereDate('fecha_validacion', '>=', $this->filtroFechaDesde); }
        if (!empty($this->filtroFechaHasta)) { $query->whereDate('fecha_validacion', '<=', $this->filtroFechaHasta); }
        
        if ($this->filtroResponsabilidad !== 'todos') {
            switch ($this->filtroResponsabilidad) {
                case 'pendientes':
                    $query->where('estado_validacion', 'Pendiente Validación Mandante');
                    break;
                case 'exclusivos':
                    $query->where('valida_solo_mandante_snapshot', true);
                    break;
                case 'gestionados':
                    $query->whereNotNull('mandante_validador_id');
                    break;
            }
        }
        
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
        $validadores = User::where('mandante_id', Auth::user()->mandante_id)
            ->whereHas('roles', function ($q) { 
                $q->whereIn('name', ['Mandante_Admin', 'Mandante_Validator']);
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

        return view('livewire.mandante.gestion-general-documentos', [
            'documentos' => $documentos,
            'validadores' => $validadores
        ])->layout('layouts.app');
    }

    public function asignarSeleccionados()
    {
        if ($this->esSoloLectura) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }

        $this->validate([
            'documentosSeleccionados' => 'required|array|min:1',
            'validadorSeleccionado' => 'required|exists:users,id'
        ], [
            'documentosSeleccionados.required' => 'Debe seleccionar al menos un documento.',
            'validadorSeleccionado.required' => 'Debe seleccionar un validador.',
        ]);

        try {
            $filasAfectadas = DocumentoCargado::whereIn('id', $this->documentosSeleccionados)
                ->where('mandante_id', Auth::user()->mandante_id)
                ->whereNull('resultado_validacion')
                ->update([
                    'mandante_validador_id' => $this->validadorSeleccionado,
                    'estado_validacion' => 'Pendiente Validación Mandante'
                ]);

            session()->flash('message', $filasAfectadas . ' documento(s) asignado(s) correctamente.');
            $this->resetSeleccion();

        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error al asignar los documentos.');
            Log::error('Error asignando documentos Mandante: ' . $e->getMessage());
        }
    }

    public function desasignarSeleccionados()
    {
        if ($this->esSoloLectura) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }

        $this->validate(['documentosSeleccionados' => 'required|array|min:1']);

        try {
            $filasAfectadas = DocumentoCargado::whereIn('id', $this->documentosSeleccionados)
                ->where('mandante_id', Auth::user()->mandante_id)
                ->whereNull('resultado_validacion')
                ->update([
                    'mandante_validador_id' => null
                ]);

            session()->flash('message', $filasAfectadas . ' documento(s) desasignado(s) correctamente.');
            $this->resetSeleccion();

        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error al desasignar los documentos.');
            Log::error('Error desasignando documentos Mandante: ' . $e->getMessage());
        }
    }

    public function revalidarSeleccionados()
    {
        if ($this->esSoloLectura) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }

        $this->validate([
            'seleccionParaRevalidar' => 'required|array|min:1',
            'motivoRevalidacionMasiva' => 'required|string|min:10',
        ], [
            'seleccionParaRevalidar.required' => 'Debe seleccionar al menos un documento para revalidar.',
            'motivoRevalidacionMasiva.required' => 'El motivo de revalidación es obligatorio.',
            'motivoRevalidacionMasiva.min' => 'El motivo debe tener al menos 10 caracteres.',
        ]);

        $docsParaRevalidar = DocumentoCargado::whereIn('id', $this->seleccionParaRevalidar)
            ->where('mandante_id', Auth::user()->mandante_id)
            ->whereNotNull('resultado_validacion')
            ->where('valida_solo_mandante_snapshot', true)
            ->get();

        if ($docsParaRevalidar->count() === 0) {
            session()->flash('error', 'No se encontraron documentos elegibles para revalidación.');
            return;
        }

        try {
            DB::transaction(function () use ($docsParaRevalidar) {
                foreach ($docsParaRevalidar as $originalDoc) {
                    
                    $originalDoc->update(['estado_validacion' => 'Archivado-Revalidado']);

                    $nuevoDoc = $originalDoc->replicate([
                        'resultado_validacion', 'asem_validador_id', 'mandante_validador_id', 
                        'fecha_validacion', 'observacion_interna_asem', 'observacion_rechazo', 
                        'motivo_revalidacion', 'es_error_validador'
                    ]);

                    $nuevoDoc->estado_validacion = 'Pendiente Validación Mandante';
                    $nuevoDoc->motivo_revalidacion = $this->motivoRevalidacionMasiva;
                    $nuevoDoc->created_at = now();
                    $nuevoDoc->updated_at = now();
                    $nuevoDoc->save();
                }
            });

            session()->flash('message', $docsParaRevalidar->count() . ' documento(s) enviados a revalidación.');
            $this->resetSeleccion();

        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error al revalidar los documentos.');
            Log::error('Error revalidando documentos Mandante: ' . $e->getMessage());
        }
    }

    public function abrirModalModificarVencimiento() {
        if ($this->esSoloLectura) {
            session()->flash('error', 'No tiene permisos para realizar esta acción (modo solo lectura).');
            return;
        }
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
        if ($this->esSoloLectura) {
            session()->flash('error', 'No tiene permisos para realizar esta acción (modo solo lectura).');
            return;
        }
        
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
                $this->validateSecureFile($this->justificativoModificacion, 'justificativo', 'MANDANTE_MOD_VENC');
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
    
    public function abrirModalAuditoria($documentoId) {
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

        $this->esAuditoriaSoloLectura = true; // Forzamos a que siempre sea solo lectura
        $this->resetErrorBag();
        $this->showAuditoriaModal = true;
    }
    
    public function cerrarModalAuditoria() {
        $this->showAuditoriaModal = false;
        $this->documentoAuditoria = null;
        $this->cargoAuditoria = null;
    }
    
    public function abrirModalNotificacion() {
        if ($this->esSoloLectura) {
            session()->flash('error', 'No tiene permisos para realizar esta acción (modo solo lectura).');
            return;
        }
        $documentosQuery = $this->buildQuery(); $documentosParaNotificar = $documentosQuery->get(); $totalDocumentos = $documentosParaNotificar->count(); $totalContratistas = $documentosParaNotificar->pluck('contratista_id')->unique()->count();
        $this->conteoNotificacion = [ 'total' => $totalDocumentos, 'contratistas' => $totalContratistas, ];
        $this->mensajeNotificacion = "Le informamos que se ha detectado la siguiente documentación con observaciones en nuestra plataforma, en relación a su prestación de servicios.\n\n" . "Agradecemos proceder a su regularización a la brevedad para mantener el cumplimiento normativo.";
        $this->resetErrorBag(); $this->showNotificacionModal = true;
    }
    
    public function cerrarModalNotificacion() { $this->showNotificacionModal = false; $this->mensajeNotificacion = ''; }
    
    public function despacharNotificaciones() {
        if ($this->esSoloLectura) {
            session()->flash('error', 'No tiene permisos para realizar esta acción (modo solo lectura).');
            return;
        }
        $this->validate(['mensajeNotificacion' => 'required|string|min:20'], [ 'mensajeNotificacion.required' => 'El mensaje de notificación no puede estar vacío.', 'mensajeNotificacion.min' => 'El mensaje debe tener al menos 20 caracteres.' ]);
        $documentoIds = $this->buildQuery()->pluck('id')->toArray();
        if (empty($documentoIds)) { session()->flash('error', 'No hay documentos en la vista actual para notificar.'); $this->cerrarModalNotificacion(); return; }
        NotificarDocumentosContratista::dispatch($documentoIds, $this->mensajeNotificacion, 'Notificación Manual', Auth::id());
        session()->flash('message', 'La tarea de notificación ha sido enviada a la cola. Los correos se enviarán en segundo plano.'); $this->cerrarModalNotificacion();
    }
}