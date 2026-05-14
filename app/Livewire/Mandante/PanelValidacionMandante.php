<?php

namespace App\Livewire\Mandante;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\DocumentoCargado;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // Asegurarse de que el modelo User está importado.

class PanelValidacionMandante extends Component
{
    use WithPagination;

    public $filtroContratista = '';
    public $filtroEntidad = '';
    public $filtroDocumento = '';
    public $filtroIdEntidad = ''; 

    public $sortField = 'created_at';
    public $sortDirection = 'asc';

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['filtroContratista', 'filtroEntidad', 'filtroDocumento', 'filtroIdEntidad'])) {
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
        $user = Auth::user();
        
        $query = \App\Models\DocumentoCargado::query()
            ->where('mandante_id', $user->mandante_id)
            ->where('mandante_validador_id', $user->id)
            ->where('estado_validacion', 'Pendiente Validación Mandante')
            ->whereNull('resultado_validacion')
            ->with(['contratista', 'entidad']);

        // Filtros
        if (!empty($this->filtroContratista)) {
            $query->whereHas('contratista', function ($q) {
                $q->where('razon_social', 'like', '%' . $this->filtroContratista . '%')
                  ->orWhere('rut', 'like', '%' . $this->filtroContratista . '%');
            });
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
            
            $vehiculoIds = \App\Models\Vehiculo::where(\Illuminate\Support\Facades\DB::raw("REPLACE(CONCAT(patente_letras, patente_numeros), ' ', '')"), 'like', "%{$searchTerm}%")->pluck('id');
            if ($vehiculoIds->isNotEmpty()) {
                $matchingDocIds = array_merge($matchingDocIds, \App\Models\DocumentoCargado::where('entidad_type', \App\Models\Vehiculo::class)->whereIn('entidad_id', $vehiculoIds)->pluck('id')->toArray());
            }
            $trabajadorIds = \App\Models\Trabajador::where('rut', 'like', "%{$originalSearchTerm}%")->pluck('id');
            if ($trabajadorIds->isNotEmpty()) {
                $matchingDocIds = array_merge($matchingDocIds, \App\Models\DocumentoCargado::where('entidad_type', \App\Models\Trabajador::class)->whereIn('entidad_id', $trabajadorIds)->pluck('id')->toArray());
            }
            $maquinariaIds = \App\Models\Maquinaria::where(\Illuminate\Support\Facades\DB::raw("REPLACE(CONCAT(IFNULL(identificador_letras, ''), IFNULL(identificador_numeros, '')), ' ', '')"), 'like', "%{$searchTerm}%")->pluck('id');
            if ($maquinariaIds->isNotEmpty()) {
                $matchingDocIds = array_merge($matchingDocIds, \App\Models\DocumentoCargado::where('entidad_type', \App\Models\Maquinaria::class)->whereIn('entidad_id', $maquinariaIds)->pluck('id')->toArray());
            }
            $embarcacionIds = \App\Models\Embarcacion::where(\Illuminate\Support\Facades\DB::raw("REPLACE(CONCAT(IFNULL(matricula_letras, ''), IFNULL(matricula_numeros, '')), ' ', '')"), 'like', "%{$searchTerm}%")->pluck('id');
            if ($embarcacionIds->isNotEmpty()) {
                $matchingDocIds = array_merge($matchingDocIds, \App\Models\DocumentoCargado::where('entidad_type', \App\Models\Embarcacion::class)->whereIn('entidad_id', $embarcacionIds)->pluck('id')->toArray());
            }
            $contratistaIds = \App\Models\Contratista::where('rut', 'like', "%{$originalSearchTerm}%")->pluck('id');
            if ($contratistaIds->isNotEmpty()) {
                $matchingDocIds = array_merge($matchingDocIds, \App\Models\DocumentoCargado::where('entidad_type', \App\Models\Contratista::class)->whereIn('entidad_id', $contratistaIds)->pluck('id')->toArray());
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
        $user = Auth::user();

        if (!$user->isMandante() || !$user->mandante_id) {
            abort(403, 'Acceso no autorizado a este panel.');
        }

        $documentos = $this->buildQuery()->paginate(15);

        return view('livewire.mandante.panel-validacion-mandante', [
            'documentos' => $documentos,
        ])->layout('layouts.app');
    }
}