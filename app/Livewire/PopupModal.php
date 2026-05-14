<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Popup;
use App\Models\PopupVisualizacion;
use Illuminate\Support\Facades\Auth;

class PopupModal extends Component
{
    public ?Popup $popupActivo = null;
    public bool $mostrarPopup = false;
    public bool $aceptoCondiciones = false;
    public int $visualizacionActual = 0;
    public int $maxVisualizaciones = 0;

    public function mount()
    {
        $this->cargarPopupPendiente();
    }

    public function cargarPopupPendiente()
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();

        // Buscar el primer popup visible para este usuario
        $popupsVigentes = Popup::vigentes()
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($popupsVigentes as $popup) {
            if ($popup->esVisiblePara($user)) {
                $this->popupActivo = $popup;
                $this->mostrarPopup = true;
                $this->maxVisualizaciones = $popup->max_visualizaciones;
                
                // Obtener o crear registro de visualización
                $visualizacion = PopupVisualizacion::firstOrCreate(
                    ['popup_id' => $popup->id, 'user_id' => $user->id],
                    ['veces_mostrado' => 0, 'acepto_condiciones' => false]
                );
                
                $this->visualizacionActual = $visualizacion->veces_mostrado;
                
                // Solo incrementar visualización si NO requiere aceptación obligatoria
                // Para popups con aceptación obligatoria, el contador no aplica
                if (!$popup->requiere_aceptacion) {
                    $visualizacion->incrementarVisualizacion();
                    $this->visualizacionActual++;
                }
                
                break;
            }
        }
    }

    public function cerrarPopup()
    {
        // Si requiere aceptación y no ha aceptado, no cerrar
        if ($this->popupActivo && $this->popupActivo->requiere_aceptacion && !$this->aceptoCondiciones) {
            return;
        }

        // Registrar aceptación si aplica
        if ($this->popupActivo && $this->aceptoCondiciones) {
            $visualizacion = PopupVisualizacion::where('popup_id', $this->popupActivo->id)
                ->where('user_id', Auth::id())
                ->first();
            
            if ($visualizacion) {
                $visualizacion->aceptarCondiciones();
            }
        }

        // Redirigir si hay URL de destino
        if ($this->popupActivo && $this->popupActivo->url_destino) {
            $this->redirect($this->popupActivo->url_destino);
            return;
        }

        $this->mostrarPopup = false;
        $this->popupActivo = null;
        $this->aceptoCondiciones = false;
    }

    public function accionClick()
    {
        // Verificar aceptación si es requerida
        if ($this->popupActivo && $this->popupActivo->requiere_aceptacion && !$this->aceptoCondiciones) {
            return;
        }

        $this->cerrarPopup();
    }

    public function render()
    {
        return view('livewire.popup-modal');
    }
}
