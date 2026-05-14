<?php

namespace App\Livewire\Layout;

use Livewire\Component;

class OperacionNavigation extends Component
{
    /**
     * NOTA: Este componente se genera a través de la sintaxis "Volt" en la vista.
     * Esta clase existe para cumplir con la estructura formal si es necesario,
     * pero la lógica principal (logout) ya está definida en el propio archivo Blade.
     *
     * Si no se usa Volt, la lógica del archivo Blade se movería aquí.
     */
     
    public function render()
    {
        return view('livewire.layout.operacion-navigation');
    }
}