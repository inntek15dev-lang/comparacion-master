<?php

namespace App\Livewire\Asem;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\DocumentoCargado;
use App\Models\Contratista;
use App\Models\Mandante;
use App\Models\User;
use App\Models\Trabajador;
use App\Models\Vehiculo;
use App\Models\Maquinaria;
use App\Models\Embarcacion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AsignacionDocumentos extends Component
{
    use WithPagination;

    public $filtroContratista = '';
    public $filtroMandante = '';
    public $filtroEntidad = '';
    public $filtroDocumento = '';
    public $filtroIdEntidad = '';
    public $filtroEstado = 'Sin Asignar';

    public $documentosSeleccionados = [];
    public $validadorSeleccionado = null;
    public $totalValorNominal = 0;
    public $seleccionarTodos = false;
    
    public $sortField = 'created_at';
    public $sortDirection = 'asc';

    public function updatedDocumentosSeleccionados()
    {
        if (empty($this->documentosSeleccionados)) {
            $this->totalValorNominal = 0;
            return;
        }
        $this->totalValorNominal = DocumentoCargado::whereIn('id', $this->documentosSeleccionados)
                                      ->sum('valor_nominal_snapshot');
    }

    public function updatedSeleccionarTodos($value)
    {
        if ($value) {
            $this->documentosSeleccionados = $this->buildQuery()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->documentosSeleccionados = [];
        }
        $this->updatedDocumentosSeleccionados();
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }

    private function buildQuery()
    {
        $query = DocumentoCargado::query()
            ->with(['contratista', 'mandante', 'entidad', 'validador'])
            ->where('archivado', false);
            
        // CAMBIO: Se ajusta la lógica de filtrado para ser más robusta y capturar el estado 'fantasma'.
        if (!empty($this->filtroEstado)) {
             // Si se filtra por 'Sin Asignar', también incluimos el estado erróneo 'Pendiente' para que aparezcan.
             if ($this->filtroEstado === 'Sin Asignar') {
                 $query->whereIn('estado_validacion', ['Sin Asignar', 'Pendiente']);
             } else {
                 $query->where('estado_validacion', $this->filtroEstado);
             }
        } else {
            $query->where(function ($q) {
                $q->whereIn('estado_validacion', ['Sin Asignar', 'Asignado', 'Devuelto', 'Pendiente'])
                  ->whereNull('resultado_validacion'); 
            });
        }
        
        if ($this->filtroEstado === 'Revalidar') {
             $query->reapplyBindings()->where('requiere_revalidacion', true);
        } else {
            $query->where('requiere_revalidacion', false);
        }

        if (!empty($this->filtroContratista)) { $query->whereHas('contratista', function ($q) { $q->where('razon_social', 'like', '%' . $this->filtroContratista . '%')->orWhere('rut', 'like', '%' . $this->filtroContratista . '%'); }); }
        if (!empty($this->filtroMandante)) { $query->where('mandante_id', $this->filtroMandante); }
        if (!empty($this->filtroEntidad)) { $query->where('entidad_type', $this->filtroEntidad); }
        if (!empty($this->filtroDocumento)) { $query->where('nombre_documento_snapshot', 'like', '%' . $this->filtroDocumento . '%'); }
        if (!empty($this->filtroIdEntidad)) { $matchingDocIds = []; $searchTerm = str_replace(['-', '.', ' '], '', $this->filtroIdEntidad); $originalSearchTerm = $this->filtroIdEntidad; $vehiculoIds = Vehiculo::where(DB::raw("REPLACE(CONCAT(patente_letras, patente_numeros), ' ', '')"), 'like', "%{$searchTerm}%")->pluck('id'); if ($vehiculoIds->isNotEmpty()) { $matchingDocIds = array_merge($matchingDocIds, DocumentoCargado::where('entidad_type', Vehiculo::class)->whereIn('entidad_id', $vehiculoIds)->pluck('id')->toArray()); } $trabajadorIds = Trabajador::where('rut', 'like', "%{$originalSearchTerm}%")->pluck('id'); if ($trabajadorIds->isNotEmpty()) { $matchingDocIds = array_merge($matchingDocIds, DocumentoCargado::where('entidad_type', Trabajador::class)->whereIn('entidad_id', $trabajadorIds)->pluck('id')->toArray()); } $maquinariaIds = Maquinaria::where(DB::raw("REPLACE(CONCAT(IFNULL(identificador_letras, ''), IFNULL(identificador_numeros, '')), ' ', '')"), 'like', "%{$searchTerm}%")->pluck('id'); if ($maquinariaIds->isNotEmpty()) { $matchingDocIds = array_merge($matchingDocIds, DocumentoCargado::where('entidad_type', Maquinaria::class)->whereIn('entidad_id', $maquinariaIds)->pluck('id')->toArray()); } $embarcacionIds = Embarcacion::where(DB::raw("REPLACE(CONCAT(IFNULL(matricula_letras, ''), IFNULL(matricula_numeros, '')), ' ', '')"), 'like', "%{$searchTerm}%")->pluck('id'); if ($embarcacionIds->isNotEmpty()) { $matchingDocIds = array_merge($matchingDocIds, DocumentoCargado::where('entidad_type', Embarcacion::class)->whereIn('entidad_id', $embarcacionIds)->pluck('id')->toArray()); } $contratistaIds = Contratista::where('rut', 'like', "%{$originalSearchTerm}%")->pluck('id'); if ($contratistaIds->isNotEmpty()) { $matchingDocIds = array_merge($matchingDocIds, DocumentoCargado::where('entidad_type', Contratista::class)->whereIn('entidad_id', $contratistaIds)->pluck('id')->toArray()); } if (!empty($matchingDocIds)) { $query->whereIn('id', array_unique($matchingDocIds)); } else { $query->whereRaw('0 = 1'); } }
        
        if ($this->sortField === 'tiempo_en_cola') {
            $sqlCase = "CASE WHEN fecha_validacion IS NOT NULL THEN TIMESTAMPDIFF(HOUR, created_at, fecha_validacion) ELSE TIMESTAMPDIFF(HOUR, created_at, NOW()) END";
            $query->orderByRaw("$sqlCase {$this->sortDirection}");
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }
        
        return $query;
    }

    public function render()
    {
        $documentosPendientes = $this->buildQuery()->paginate(10);
        $mandantes = Mandante::orderBy('razon_social')->get();
        $validadores = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['ASEM_Validator', 'ASEM_Admin']);
        })->orderBy('name')->get();
        return view('livewire.asem.asignacion-documentos', [
            'documentosPendientes' => $documentosPendientes,
            'mandantes' => $mandantes,
            'validadores' => $validadores,
        ])->layout('layouts.app');
    }
    
    public function updated($propertyName)
    {
        if(in_array($propertyName, ['filtroContratista', 'filtroMandante', 'filtroEntidad', 'filtroDocumento', 'filtroIdEntidad', 'filtroEstado'])) {
            $this->seleccionarTodos = false;
            $this->documentosSeleccionados = [];
            $this->totalValorNominal = 0;
            $this->resetPage();
        }
    }
    
    public function asignarSeleccionados()
    {
        $this->validate([
            'validadorSeleccionado' => 'required|exists:users,id',
            'documentosSeleccionados' => 'required|array|min:1',
        ]);
        try {
            DocumentoCargado::whereIn('id', $this->documentosSeleccionados)
                ->update([
                    'asem_validador_id' => $this->validadorSeleccionado,
                    'estado_validacion' => 'Asignado', 
                    'requiere_revalidacion' => false, 
                    'observacion_rechazo' => null,    
                    'observacion_interna_asem' => null, 
                    'motivo_revalidacion' => null,    
                ]);
            session()->flash('message', 'Documentos asignados correctamente.');
            Log::info('Documentos ' . implode(', ', $this->documentosSeleccionados) . ' asignados al validador ID: ' . $this->validadorSeleccionado);
            $this->documentosSeleccionados = [];
            $this->validadorSeleccionado = null;
            $this->totalValorNominal = 0;
            $this->seleccionarTodos = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error al asignar los documentos.');
            Log::error('Error asignando documentos: ' . $e->getMessage());
        }
    }
}