<?php

namespace App\Livewire\Asem\Supervision;

use Livewire\Component;
use App\Models\Trabajador;
use Livewire\WithPagination;

class TablaTrabajadoresGlobal extends Component
{
    use WithPagination;

    public $contratistaId;
    public $mandanteId;
    public $lugarDeTrabajoId;
    public $uoId;
    public $search = '';
    public bool $esSoloLectura = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $trabajadoresQuery = Trabajador::where('contratista_id', $this->contratistaId)
            ->whereHas('vinculaciones', function ($query) {
                $query->where('dependencia_id', $this->lugarDeTrabajoId)
                      ->where('unidad_organizacional_mandante_id', $this->uoId);
            })
            ->where(function ($query) {
                $query->where('rut', 'like', '%'.$this->search.'%')
                      ->orWhere('nombres', 'like', '%'.$this->search.'%')
                      ->orWhere('apellido_paterno', 'like', '%'.$this->search.'%');
            })
            ->with([
                'anulacionManualActiva' => function ($q) {
                    $q->where('mandante_id', $this->mandanteId);
                }
            ]);
        
        $trabajadores = $trabajadoresQuery->paginate(100);

        return view('livewire.asem.supervision.tabla-trabajadores-global', [
            'trabajadores' => $trabajadores,
        ]);
    }
}