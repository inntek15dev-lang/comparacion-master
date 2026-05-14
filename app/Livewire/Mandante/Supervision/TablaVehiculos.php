<?php

namespace App\Livewire\Mandante\Supervision;

use Livewire\Component;
use App\Models\Vehiculo;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class TablaVehiculos extends Component
{
    use WithPagination;

    public $contratistaId;
    public $mandanteId;
    public $search = '';

    public function render()
    {
        $vehiculos = Vehiculo::where('contratista_id', $this->contratistaId)
            ->where(function ($query) {
                $searchTerm = str_replace(['-', ' ', '•'], '', $this->search);
                $query->where(DB::raw("REPLACE(CONCAT(patente_letras, patente_numeros), ' ', '')"), 'like', '%' . $searchTerm . '%');
            })
            ->with([
                'contratista.unidadesOrganizacionalesMandante',
                'anulacionManualActiva' => function ($q) {
                    $q->where('mandante_id', $this->mandanteId);
                }
            ])
            ->paginate(10, ['*'], 'vehiculosPage');

        return view('livewire.mandante.supervision.tabla-vehiculos', [
            'vehiculos' => $vehiculos,
        ]);
    }
}