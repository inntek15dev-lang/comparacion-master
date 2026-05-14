<?php

namespace App\Livewire\Publico;

use Livewire\Component;
use App\Models\Mandante;
use Livewire\Attributes\On;

class InscripcionModal extends Component
{
    public bool $showModal = false;
    public string $mandanteId = '';
    public $mandantes;

    public function mount()
    {
        $this->mandantes = Mandante::where('is_active', true)->orderBy('razon_social')->get();
    }

    #[On('abrir-modal-inscripcion')]
    public function abrirModal()
    {
        $this->resetValidation();
        $this->reset('mandanteId');
        $this->showModal = true;
    }

    public function validarYContinuar()
    {
        $this->validate(['mandanteId' => 'required|exists:mandantes,id']);
        
        // Siempre es tipo CONTRATISTA (ya no existe opción de sub-contratista en página pública)
        $url = route('public.registro', ['tipo' => 'CONTRATISTA', 'mandante_id' => $this->mandanteId]);
        
        return $this->redirect($url);
    }

    public function render()
    {
        return view('livewire.publico.inscripcion-modal');
    }
}