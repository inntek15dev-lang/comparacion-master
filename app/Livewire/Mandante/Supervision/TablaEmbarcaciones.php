<?php

namespace App\Livewire\Mandante\Supervision;

use Livewire\Component;
use App\Models\Embarcacion;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class TablaEmbarcaciones extends Component
{
    use WithPagination;

    public $contratistaId;
    public $mandanteId;
    public $search = '';

    public function render()
    {
        $embarcaciones = Embarcacion::where('contratista_id', $this->contratistaId)
            ->where(function ($query) {
                $searchTerm = str_replace(['-', ' ', '•'], '', $this->search);
                $query->where(DB::raw("REPLACE(CONCAT(matricula_letras, matricula_numeros), ' ', '')"), 'like', '%' . $searchTerm . '%');
            })
            ->with([
                'contratista.unidadesOrganizacionalesMandante',
                'anulacionManualActiva' => function ($q) {
                    $q->where('mandante_id', $this->mandanteId);
                }
            ])
            ->paginate(10, ['*'], 'embarcacionesPage');

        return view('livewire.mandante.supervision.tabla-embarcaciones', [
            'embarcaciones' => $embarcaciones,
        ]);
    }
}