<?php

namespace App\Livewire\Mandante;

use Livewire\Component;
use App\Models\Contratista;
use App\Models\DocumentoExcepcionCriticidad;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Livewire\Attributes\Url;

class SupervisionDetalleContratista extends Component
{
    public Contratista $contratista;
    public $mandante;

    #[Url(as: 'pestaña')]
    public $pestañaActiva = 'trabajadores'; // Por defecto

    // Propiedades para el modal de anulación
    public bool $showAnulacionModal = false;
    public $recursoSeleccionado = null;
    public ?string $recursoType = null;
    public ?string $accionAnulacion = null;
    public string $justificacion = '';
    public ?string $valido_hasta = null;

    public function mount($contratistaId)
    {
        $this->mandante = Auth::user()->mandante;
        $contratista = Contratista::where('id', $contratistaId)
            ->whereHas('unidadesOrganizacionalesMandante', fn($q) => $q->where('mandante_id', $this->mandante->id))
            ->firstOrFail();
        $this->contratista = $contratista;
    }

    public function seleccionarPestaña($pestaña)
    {
        $this->pestañaActiva = $pestaña;
    }

    public function abrirModalAnulacion($recursoId, $recursoType, $accion)
    {
        $this->recursoSeleccionado = $recursoType::find($recursoId);
        $this->recursoType = $recursoType;
        $this->accionAnulacion = $accion;
        $this->justificacion = '';
        $this->valido_hasta = null;
        $this->resetErrorBag();
        $this->showAnulacionModal = true;
    }

    public function cerrarModalAnulacion()
    {
        $this->showAnulacionModal = false;
    }

    public function guardarAnulacionAcceso()
    {
        $this->validate([
            'justificacion' => 'required|string|min:20',
            'valido_hasta' => 'nullable|date|after_or_equal:today',
        ]);

        try {
            $placeholderDocumentoId = 99999;

            DocumentoExcepcionCriticidad::updateOrCreate(
                [
                    'mandante_id' => $this->mandante->id,
                    'excepcionable_type' => $this->recursoType,
                    'excepcionable_id' => $this->recursoSeleccionado->id,
                    'nombre_documento_id' => $placeholderDocumentoId,
                ],
                [
                    'accion_override' => $this->accionAnulacion,
                    'justificacion' => $this->justificacion,
                    'valido_hasta' => $this->valido_hasta,
                    'created_by_user_id' => Auth::id(),
                    'afecta_cumplimiento_override' => null,
                    'restringe_acceso_override' => null,
                    'es_perseguidor_override' => null,
                ]
            );

            $this->dispatch('notificacion-exito', 'La anulación de acceso ha sido registrada correctamente.');
            $this->cerrarModalAnulacion();
        } catch (\Exception $e) {
            Log::error("Error al guardar anulación de acceso: " . $e->getMessage());
            $this->dispatch('notificacion-error', 'Ocurrió un error al registrar la anulación.');
        }
    }

    public function render()
    {
        return view('livewire.mandante.supervision-detalle-contratista')->layout('layouts.app');
    }
}