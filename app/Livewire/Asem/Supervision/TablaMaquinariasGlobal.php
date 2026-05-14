<?php

namespace App\Livewire\Asem\Supervision;

use Livewire\Component;
use App\Models\Maquinaria;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class TablaMaquinariasGlobal extends Component
{
    use WithPagination;

    public $contratistaId;
    public $mandanteId;
    // ================== INICIO DE LA MODIFICACIÓN DOCTRINAL ==================
    public $lugarDeTrabajoId;
    public $uoId;
    // ================== FIN DE LA MODIFICACIÓN DOCTRINAL ====================
    public $search = '';
    public bool $esSoloLectura = false;

    public function render()
    {
        // ================== INICIO DE LA MODIFICACIÓN DOCTRINAL: CONSULTA CONTEXTUAL ==================
        $maquinarias = Maquinaria::where('contratista_id', $this->contratistaId)
            ->whereHas('vinculaciones', function ($query) {
                $query->where('dependencia_id', $this->lugarDeTrabajoId)
                      ->where('unidad_organizacional_mandante_id', $this->uoId);
            })
            ->where(function ($query) {
                $searchTerm = str_replace(['-', ' ', '•'], '', $this->search);
                $query->where(DB::raw("REPLACE(CONCAT(identificador_letras, identificador_numeros), ' ', '')"), 'like', '%' . $searchTerm . '%');
            })
            ->with([
                'anulacionManualActiva' => function ($q) {
                    $q->where('mandante_id', $this->mandanteId);
                }
            ])
            ->paginate(10, ['*'], 'maquinariasPage');
        // ================== FIN DE LA MODIFICACIÓN DOCTRINAL: CONSULTA CONTEXTUAL ====================

        return view('livewire.asem.supervision.tabla-maquinarias-global', [
            'maquinarias' => $maquinarias,
        ]);
    }
}
