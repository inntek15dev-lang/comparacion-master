<?php

namespace App\Livewire\Mandante;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use App\Models\DocumentoCargado;
use App\Models\Contratista;
use App\Models\User;
use App\Models\Trabajador;
use App\Models\Vehiculo;
use App\Models\Maquinaria;
use App\Models\Embarcacion;
use App\Services\DocumentoRequeridoService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use stdClass;

class GestionGeneralMandante extends Component
{
    use WithPagination;

    // --- PROPIEDADES DE FILTRADO ---
    public $filtroContratista = '';
    public $filtroEntidad = '';
    public $filtroDocumento = '';
    public $filtroIdEntidad = '';
    public $filtroEstado = '';
    public $filtroResultado = '';
    public $filtroVigencia = '';
    public $filtroMostrar = '';
    public $filtroFechaDesde = '';
    public $filtroFechaHasta = '';
    public $filtroFechaCargaDesde = ''; // <-- NUEVA PROPIEDAD
    public $filtroFechaCargaHasta = ''; // <-- NUEVA PROPIEDAD
    
    // --- PROPIEDADES DE GESTIÓN Y ASIGNACIÓN ---
    public $documentosSeleccionados = [];
    public $validadorSeleccionado = null;
    public $seleccionarTodos = false;

    // --- PROPIEDADES DE ORDENACIÓN ---
    public $sortField = 'created_at';
    public $sortDirection = 'asc';

    // --- SERVICIO INYECTADO ---
    protected $documentoRequeridoService;

    public function boot(DocumentoRequeridoService $documentoRequeridoService)
    {
        $this->documentoRequeridoService = $documentoRequeridoService;
    }
    
    public function mount()
    {
        // No se necesita lógica de montaje especial
    }
    
    public function updated($propertyName) {
        if(in_array($propertyName, ['filtroContratista', 'filtroEntidad', 'filtroDocumento', 'filtroIdEntidad', 'filtroEstado', 'filtroResultado', 'filtroVigencia', 'filtroMostrar', 'filtroFechaDesde', 'filtroFechaHasta', 'filtroFechaCargaDesde', 'filtroFechaCargaHasta'])) {
            $this->resetPage();
            if ($propertyName === 'filtroMostrar' && $this->filtroMostrar !== 'cargados') {
                $this->resetSeleccion();
            }
        }
    }
    
    public function updatedSeleccionarTodos($value) {
        if ($value && $this->filtroMostrar === 'cargados') {
            $this->documentosSeleccionados = $this->buildQuery()
                ->where('estado_validacion', 'Pendiente Validación Mandante')
                ->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->documentosSeleccionados = [];
        }
    }
    
    private function resetSeleccion() { 
        $this->documentosSeleccionados = []; 
        $this->validadorSeleccionado = null; 
        $this->seleccionarTodos = false; 
    }

    public function sortBy($field) {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }

    public function resetearFiltros()
    {
        $this->reset([
            'filtroContratista', 'filtroEntidad', 'filtroDocumento', 'filtroIdEntidad', 
            'filtroEstado', 'filtroResultado', 'filtroVigencia', 'filtroMostrar',
            'filtroFechaDesde', 'filtroFechaHasta', 'filtroFechaCargaDesde', 'filtroFechaCargaHasta'
        ]);
        $this->resetPage();
        $this->resetSeleccion();
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

    private function buildQuery()
    {
        $user = Auth::user();
        $query = DocumentoCargado::query()
            ->where('mandante_id', $user->mandante_id)
            ->with(['contratista', 'mandante', 'entidad', 'validadorAsem', 'validadorMandante']);

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
        if (!empty($this->filtroFechaDesde)) { $query->whereDate('fecha_validacion', '>=', $this->filtroFechaDesde); }
        if (!empty($this->filtroFechaHasta)) { $query->whereDate('fecha_validacion', '<=', $this->filtroFechaHasta); }
        
        // --- INICIO DE LA LÓGICA DE FILTRADO POR FECHA DE CARGA ---
        if (!empty($this->filtroFechaCargaDesde)) { $query->whereDate('created_at', '>=', $this->filtroFechaCargaDesde); }
        if (!empty($this->filtroFechaCargaHasta)) { $query->whereDate('created_at', '<=', $this->filtroFechaCargaHasta); }
        // --- FIN DE LA LÓGICA DE FILTRADO POR FECHA DE CARGA ---

        if ($this->sortField === 'tiempo_en_cola') { $sqlCase = "CASE WHEN fecha_validacion IS NOT NULL THEN TIMESTAMPDIFF(HOUR, created_at, fecha_validacion) ELSE TIMESTAMPDIFF(HOUR, created_at, NOW()) END"; $query->orderByRaw("$sqlCase {$this->sortDirection}"); } else { $query->orderBy($this->sortField, $this->sortDirection); }
        return $query;
    }

    private function getNoCargados()
    {
        $user = Auth::user();
        $documentosFaltantes = new Collection();
        $contratistasQuery = Contratista::query()
            ->whereHas('unidadesOrganizacionalesMandante', function($q) use ($user) {
                $q->where('mandante_id', $user->mandante_id);
            })->with('unidadesOrganizacionalesMandante.mandante');

        if ($this->filtroContratista) { $contratistasQuery->where(function($q) { $q->where('razon_social', 'like', '%' . $this->filtroContratista . '%')->orWhere('rut', 'like', '%' . $this->filtroContratista . '%'); }); }
        
        $contratistas = $contratistasQuery->get();
        $mapaNombresEntidad = [ 'Contratista' => 'EMPRESA', 'Trabajador' => 'PERSONA', 'Vehiculo' => 'VEHICULO', 'Maquinaria' => 'MAQUINARIA', 'Embarcacion' => 'EMBARCACION', ];
        
        foreach ($contratistas as $contratista) {
            $tiposEntidadARevisar = !empty($this->filtroEntidad) ? [$this->filtroEntidad] : [ Contratista::class, Trabajador::class, Vehiculo::class, Maquinaria::class, Embarcacion::class ];
            foreach ($tiposEntidadARevisar as $tipoEntidad) {
                $entidades = new Collection();
                if ($tipoEntidad === Contratista::class) { $entidades->push($contratista); } else {
                    $relationName = null;
                    $baseName = class_basename($tipoEntidad);
                    switch($baseName) { case 'Trabajador': $relationName = 'trabajadores'; break; case 'Vehiculo': $relationName = 'vehiculos'; break; case 'Maquinaria': $relationName = 'maquinarias'; break; case 'Embarcacion': $relationName = 'embarcaciones'; break; }
                    if ($relationName) { $entidades = $contratista->$relationName()->get(); }
                }
                foreach ($entidades as $entidad) {
                    if (!empty($this->filtroIdEntidad)) {
                        $matches = false;
                        $searchTermNumerico = str_replace(['-', '.', ' '], '', strtolower($this->filtroIdEntidad));
                        $searchTermTexto = strtolower($this->filtroIdEntidad);
                        if ($entidad instanceof Trabajador) { $nombreCompleto = strtolower(implode(' ', array_filter([ $entidad->nombres, $entidad->apellido_paterno, $entidad->apellido_materno ]))); if (str_contains($entidad->rut, $searchTermTexto) || str_contains($nombreCompleto, $searchTermTexto)) { $matches = true; } }
                        elseif ($entidad instanceof Contratista) { if (str_contains($entidad->rut, $searchTermTexto)) { $matches = true; } }
                        elseif ($entidad instanceof Vehiculo) { $patente = strtolower(str_replace(' ', '', $entidad->patente_letras . $entidad->patente_numeros)); if (str_contains($patente, $searchTermNumerico)) { $matches = true; } }
                        elseif ($entidad instanceof Maquinaria) { $identificador = strtolower(str_replace(' ', '', ($entidad->identificador_letras ?? '') . ($entidad->identificador_numeros ?? ''))); if (str_contains($identificador, $searchTermNumerico)) { $matches = true; } }
                        elseif ($entidad instanceof Embarcacion) { $matricula = strtolower(str_replace(' ', '', ($entidad->matricula_letras ?? '') . ($entidad->matricula_numeros ?? ''))); if (str_contains($matricula, $searchTermNumerico)) { $matches = true; } }
                        if (!$matches) { continue; }
                    }
                    $documentosRequeridosGlobal = new Collection();
                    foreach($contratista->unidadesOrganizacionalesMandante as $uo) {
                        if ($uo->mandante_id != $user->mandante_id) continue;
                        $mandante = $uo->mandante;
                        $nombreEntidadNormalizado = $mapaNombresEntidad[class_basename($tipoEntidad)] ?? null;
                        if ($nombreEntidadNormalizado) { $reglasParaUO = $this->documentoRequeridoService->getReglasParaEntidadEnUO($mandante->id, $uo->id, $nombreEntidadNormalizado); $documentosRequeridosGlobal = $documentosRequeridosGlobal->merge($reglasParaUO); }
                    }
                    $nombresDocumentosRequeridos = $documentosRequeridosGlobal->pluck('nombreDocumento.nombre')->unique()->values();
                    $nombresDocumentosCargados = DocumentoCargado::where('entidad_type', get_class($entidad))->where('entidad_id', $entidad->id)->where('mandante_id', $user->mandante_id)->where(function ($query) { $query->where('resultado_validacion', '!=', 'Rechazado')->orWhereNull('resultado_validacion'); })->pluck('nombre_documento_snapshot')->toArray();
                    $documentosFaltantesNombres = $nombresDocumentosRequeridos->diff($nombresDocumentosCargados);
                    if (!empty($this->filtroDocumento)) { $documentosFaltantesNombres = $documentosFaltantesNombres->filter(function($nombre) { return stripos($nombre, $this->filtroDocumento) !== false; }); }
                    foreach ($documentosFaltantesNombres as $nombreFaltante) {
                        $docVirtual = new stdClass();
                        $docVirtual->id = "NC-" . $entidad->id . "-" . md5($nombreFaltante);
                        $docVirtual->contratista = $contratista;
                        $docVirtual->nombre_documento_snapshot = $nombreFaltante;
                        $docVirtual->entidad_type = get_class($entidad);
                        $docVirtual->entidad = $entidad;
                        $docVirtual->estado_validacion = 'No Cargado';
                        $docVirtual->resultado_validacion = '---';
                        $docVirtual->fecha_vencimiento = null;
                        $docVirtual->estado_vigencia = '---';
                        $docVirtual->created_at = null;
                        $docVirtual->fecha_validacion = null;
                        $docVirtual->es_vencimiento_modificado = false;
                        $docVirtual->motivo_modificacion_vencimiento = null;
                        $docVirtual->ruta_justificativo_modificacion = null;
                        $docVirtual->justificativo_url = null; 
                        $documentosFaltantes->push($docVirtual);
                    }
                }
            }
        }
        $pagina = Paginator::resolveCurrentPage('page');
        $porPagina = 10;
        $items = $documentosFaltantes->slice(($pagina - 1) * $porPagina, $porPagina);
        return new LengthAwarePaginator($items, $documentosFaltantes->count(), $porPagina, $pagina, [ 'path' => Paginator::resolveCurrentPath(), 'pageName' => 'page', ]);
    }

    public function render()
    {
        $user = Auth::user();
        $validadores = User::where('mandante_id', $user->mandante_id)
            ->whereHas('roles', function ($q) { 
                $q->where('name', 'Mandante_Validator');
            })->orderBy('name')->get();
        
        $documentos = null;
        if ($this->filtroMostrar === 'cargados') { $documentos = $this->buildQuery()->paginate(10); } elseif ($this->filtroMostrar === 'no_cargados') { $documentos = $this->getNoCargados(); } else { $documentos = new LengthAwarePaginator(collect(), 0, 10, 1, [ 'path' => Paginator::resolveCurrentPath(), 'pageName' => 'page', ]); }
        
        return view('livewire.mandante.gestion-general-mandante', [
            'documentos' => $documentos, 
            'validadores' => $validadores
        ])->layout('layouts.app');
    }

    public function asignarSeleccionados()
    {
        $this->validate([
            'validadorSeleccionado' => 'required|exists:users,id', 
            'documentosSeleccionados' => 'required|array|min:1'
        ]);

        $idsParaAsignar = DocumentoCargado::whereIn('id', $this->documentosSeleccionados)
            ->where('estado_validacion', 'Pendiente Validación Mandante')
            ->pluck('id')->toArray();

        $conteoOriginal = count($this->documentosSeleccionados);
        $conteoIgnorados = $conteoOriginal - count($idsParaAsignar);

        try {
            if (!empty($idsParaAsignar)) {
                DocumentoCargado::whereIn('id', $idsParaAsignar)->update([
                    'mandante_validador_id' => $this->validadorSeleccionado
                ]);
                session()->flash('message', count($idsParaAsignar) . ' documento(s) asignado(s) correctamente al validador de su empresa.');
                Log::info('Documentos ' . implode(', ', $idsParaAsignar) . ' asignados al validador de mandante ID: ' . $this->validadorSeleccionado . ' por el Mandante_Admin ID: ' . Auth::id());
            }
            if ($conteoIgnorados > 0) { session()->flash('warning', $conteoIgnorados . ' documento(s) no fueron modificados porque no estaban en estado "Pendiente Validación Mandante".'); }
            if (empty($idsParaAsignar) && $conteoOriginal > 0) {  session()->flash('error', 'Ningún documento seleccionado era elegible para la asignación.'); }
            $this->resetSeleccion();
        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error al asignar los documentos.');
            Log::error('Error asignando documentos (Mandante): ' . $e->getMessage());
        }
    }

    public function desasignarSeleccionados()
    {
        $this->validate(['documentosSeleccionados' => 'required|array|min:1']);
        
        $idsParaDesasignar = DocumentoCargado::whereIn('id', $this->documentosSeleccionados)
            ->where('estado_validacion', 'Pendiente Validación Mandante')
            ->pluck('id')->toArray();

        $conteoOriginal = count($this->documentosSeleccionados);
        $conteoIgnorados = $conteoOriginal - count($idsParaDesasignar);

        try {
            if (!empty($idsParaDesasignar)) {
                DocumentoCargado::whereIn('id', $idsParaDesasignar)->update(['mandante_validador_id' => null]);
                session()->flash('message', count($idsParaDesasignar) . ' documento(s) desasignado(s). Ahora están disponibles para cualquier validador de su empresa.');
                Log::info('Documentos ' . implode(', ', $idsParaDesasignar) . ' fueron desasignados por Mandante_Admin ID: ' . Auth::id());
            }
            if ($conteoIgnorados > 0) { session()->flash('warning', $conteoIgnorados . ' documento(s) no fueron modificados porque no eran elegibles.'); }
            $this->resetSeleccion();
        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error al desasignar los documentos.');
            Log::error('Error desasignando documentos (Mandante): ' . $e->getMessage());
        }
    }
}