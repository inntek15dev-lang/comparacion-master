<?php

namespace App\Livewire\Mandante;

use App\Livewire\Asem\SupervisionDetalleGlobal as AsemSupervisionDetalleGlobal;
use Illuminate\Support\Facades\Auth;

class SupervisionDetalle extends AsemSupervisionDetalleGlobal
{
    // Propiedad para controlar acceso de solo lectura (Mandante_Ver)
    public bool $esSoloLectura = false;

    /**
     * Sobrescribimos el método mount para validar que el Mandante_Admin
     * tenga permiso para ver esta combinación de contratista/mandante.
     */
    public function mount($contratistaId, $mandanteId, $lugarDeTrabajoId, $uoId)
    {
        $user = Auth::user();
        // Verificación de seguridad: el mandante en la URL debe ser el mismo que el del usuario.
        if (!$user || !$user->mandante_id || $user->mandante_id != $mandanteId) {
            abort(403, 'Acción no autorizada.');
        }

        // Determinar si es acceso de solo lectura (Mandante_Ver)
        $this->esSoloLectura = $user->hasRole('Mandante_Ver');

        // Si la verificación es exitosa, se ejecuta la lógica original del padre.
        parent::mount($contratistaId, $mandanteId, $lugarDeTrabajoId, $uoId);
    }

    /**
     * Sobrescribimos el render para apuntar a la vista del mandante.
     */
    public function render()
    {
        return view('livewire.mandante.supervision-detalle', [
            'esSoloLectura' => $this->esSoloLectura,
        ])->layout('layouts.app');
    }
}