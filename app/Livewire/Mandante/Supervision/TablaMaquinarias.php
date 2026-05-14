<?php

namespace App\Livewire\Mandante\Supervision;

use Livewire\Component;
use App\Models\Maquinaria;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class TablaMaquinarias extends Component
{
    use WithPagination;

    public $contratistaId;
    public $mandanteId;
    public $search = '';

    public function render()
    {
        $maquinarias = Maquinaria::where('contratista_id', $this->contratistaId)
            ->where(function ($query) {
                $searchTerm = str_replace(['-', ' ', '•'], '', $this->search);
                $query->where(DB::raw("REPLACE(CONCAT(identificador_letras, identificador_numeros), ' ', '')"), 'like', '%' . $searchTerm . '%');
            })
            ->with([
                'contratista.unidadesOrganizacionalesMandante',
                'anulacionManualActiva' => function ($q) {
                    $q->where('mandante_id', $this->mandanteId);
                }
            ])
            ->paginate(10, ['*'], 'maquinariasPage');

        return view('livewire.mandante.supervision.tabla-maquinarias', [
            'maquinarias' => $maquinarias,
        ]);
    }
}