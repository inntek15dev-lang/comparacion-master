<?php

namespace App\Livewire\Mandante\Supervision;

use Livewire\Component;
use App\Models\Trabajador;
use Livewire\WithPagination;

class TablaTrabajadores extends Component
{
    use WithPagination;

    public $contratistaId;
    public $mandanteId;
    public $search = '';

    public function render()
    {
        $trabajadores = Trabajador::where('contratista_id', $this->contratistaId)
            ->where(function ($query) {
                $query->where('rut', 'like', '%'.$this->search.'%')
                      ->orWhere('nombres', 'like', '%'.$this->search.'%')
                      ->orWhere('apellido_paterno', 'like', '%'.$this->search.'%');
            })
            ->with([
                'vinculaciones' => function ($q) {
                    $q->whereHas('unidadOrganizacionalMandante', fn($sub) => $sub->where('mandante_id', $this->mandanteId))
                      ->with('cargoMandante');
                },
                'anulacionManualActiva' => function ($q) {
                    $q->where('mandante_id', $this->mandanteId);
                }
            ])
            ->paginate(10);

        return view('livewire.mandante.supervision.tabla-trabajadores', [
            'trabajadores' => $trabajadores,
        ]);
    }
}