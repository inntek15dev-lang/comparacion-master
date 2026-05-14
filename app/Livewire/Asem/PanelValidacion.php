<?php

namespace App\Livewire\Asem;

use Livewire\Component;
use App\Models\DocumentoCargado;
use App\Models\Mandante;
use App\Models\Contratista;
use App\Models\Trabajador;
use App\Models\Vehiculo;
use App\Models\Maquinaria;
use App\Models\Embarcacion;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PanelValidacion extends Component
{
    use WithPagination;

    public $filtroContratista = '';
    public $filtroMandante = '';
    public $filtroEntidad = '';
    public $filtroDocumento = '';
    public $filтроIdEntidad = ''; 

    public $sortField = 'created_at';
    public $sortDirection = 'asc';

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['filtroContratista', 'filtroMandante', 'filtroEntidad', 'filtroDocumento', 'filtroIdEntidad'])) {
            $this->resetPage();
        }
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
        // =====================================================================
        // INICIO DE MODIFICACIÓN: Consulta Core Actualizada
        // =====================================================================
        $query = DocumentoCargado::query()
            ->where('asem_validador_id', Auth::id()) 
            // 1. AHORA BUSCA AMBOS ESTADOS DE ASIGNACIÓN:
            //    - 'Asignado' (flujo normal)
            //    - 'Asignado-Revalidar' (flujo de revalidación)
            ->whereIn('estado_validacion', ['Asignado', 'Asignado-Revalidar'])
            // 2. ELIMINADA la condición `where('archivado', false)` ya que la columna no existe.
            ->whereNull('resultado_validacion')
            ->with(['contratista', 'mandante', 'entidad']);
        // =====================================================================
        // FIN DE MODIFICACIÓN
        // =====================================================================

        // Aplicar filtros existentes (sin cambios)
        if (!empty($this->filtroContratista)) {
            $query->whereHas('contratista', function ($q) {
                $q->where('razon_social', 'like', '%' . $this->filtroContratista . '%')
                  ->orWhere('rut', 'like', '%' . $this->filtroContratista . '%');
            });
        }
        if (!empty($this->filtroMandante)) {
            $query->where('mandante_id', $this->filtroMandante);
        }
        if (!empty($this->filtroDocumento)) {
            $query->where('nombre_documento_snapshot', 'like', '%' . $this->filtroDocumento . '%');
        }
        if (!empty($this->filtroEntidad)) {
            $query->where('entidad_type', $this->filtroEntidad);
        }

        if (!empty($this->filtroIdEntidad)) {
            $matchingDocIds = [];
            $searchTerm = str_replace(['-', '.', ' '], '', $this->filtroIdEntidad);
            $originalSearchTerm = $this->filtroIdEntidad;
            $vehiculoIds = Vehiculo::where(DB::raw("REPLACE(CONCAT(patente_letras, patente_numeros), ' ', '')"), 'like', "%{$searchTerm}%")->pluck('id');
            if ($vehiculoIds->isNotEmpty()) {
                $matchingDocIds = array_merge($matchingDocIds, DocumentoCargado::where('entidad_type', Vehiculo::class)->whereIn('entidad_id', $vehiculoIds)->pluck('id')->toArray());
            }
            $trabajadorIds = Trabajador::where('rut', 'like', "%{$originalSearchTerm}%")->pluck('id');
            if ($trabajadorIds->isNotEmpty()) {
                $matchingDocIds = array_merge($matchingDocIds, DocumentoCargado::where('entidad_type', Trabajador::class)->whereIn('entidad_id', $trabajadorIds)->pluck('id')->toArray());
            }
            $maquinariaIds = Maquinaria::where(DB::raw("REPLACE(CONCAT(IFNULL(identificador_letras, ''), IFNULL(identificador_numeros, '')), ' ', '')"), 'like', "%{$searchTerm}%")->pluck('id');
            if ($maquinariaIds->isNotEmpty()) {
                $matchingDocIds = array_merge($matchingDocIds, DocumentoCargado::where('entidad_type', Maquinaria::class)->whereIn('entidad_id', $maquinariaIds)->pluck('id')->toArray());
            }
            $embarcacionIds = Embarcacion::where(DB::raw("REPLACE(CONCAT(IFNULL(matricula_letras, ''), IFNULL(matricula_numeros, '')), ' ', '')"), 'like', "%{$searchTerm}%")->pluck('id');
            if ($embarcacionIds->isNotEmpty()) {
                $matchingDocIds = array_merge($matchingDocIds, DocumentoCargado::where('entidad_type', Embarcacion::class)->whereIn('entidad_id', $embarcacionIds)->pluck('id')->toArray());
            }
            $contratistaIds = Contratista::where('rut', 'like', "%{$originalSearchTerm}%")->pluck('id');
            if ($contratistaIds->isNotEmpty()) {
                $matchingDocIds = array_merge($matchingDocIds, DocumentoCargado::where('entidad_type', Contratista::class)->whereIn('entidad_id', $contratistaIds)->pluck('id')->toArray());
            }
            if (!empty($matchingDocIds)) {
                $query->whereIn('id', array_unique($matchingDocIds));
            } else {
                $query->whereRaw('0 = 1');
            }
        }

        $query->orderBy($this->sortField, $this->sortDirection);
        return $query;
    }

    public function render()
    {
        $documentosAsignados = $this->buildQuery()->paginate(15);
        $mandantes = Mandante::orderBy('razon_social')->get();

        return view('livewire.asem.panel-validacion', [
            'documentosAsignados' => $documentosAsignados,
            'mandantes' => $mandantes,
        ])->layout('layouts.app');
    }
}