<?php

namespace App\Livewire\Contratista;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\SolicitudVinculacion;
use App\Models\UnidadOrganizacionalMandante;

class SolicitudesSubcontratistasPage extends Component
{
    public $solicitudesSubcontratistas;
    public $unidadOrganizacionalParaVincular = [];
    public $vinculacionesDisponibles;

    public function mount()
    {
        $this->cargarSolicitudesPendientes();
        $this->cargarVinculaciones();
    }

    public function cargarSolicitudesPendientes()
    {
        $contratistaId = Auth::user()->contratista_id;
        if (!$contratistaId) {
            $this->solicitudesSubcontratistas = collect();
            return;
        }

        $this->solicitudesSubcontratistas = SolicitudVinculacion::with('contratista')
            ->where('contratista_padre_id', $contratistaId)
            ->where('estado', 'PENDIENTE_VINCULACION_CONTRATISTA')
            ->get();
    }

    public function cargarVinculaciones()
    {
        $user = Auth::user();
        $contratista = $user->contratista;
        if (!$contratista) return;

        $unidadesAsignadas = $contratista->unidadesOrganizacionalesMandante()
            ->with('mandante:id,razon_social')
            ->get();
        
        $vinculacionesFormateadas = collect();
        if ($unidadesAsignadas->isNotEmpty()) {
            foreach ($unidadesAsignadas as $unidadOrg) {
                if ($mandante = $unidadOrg->mandante) {
                    $vinculacionesFormateadas->push([
                        'id_seleccion' => $unidadOrg->id,
                        'texto_visible' => $mandante->razon_social . ' - ' . $unidadOrg->nombre_unidad,
                    ]);
                }
            }
        }
        $this->vinculacionesDisponibles = $vinculacionesFormateadas->sortBy('texto_visible')->values();
    }

    public function aprobarYVincularSubcontratista($solicitudId)
    {
        $contratistaPrincipalId = Auth::user()->contratista_id;
        $solicitud = SolicitudVinculacion::where('id', $solicitudId)
            ->where('contratista_padre_id', $contratistaPrincipalId)
            ->where('estado', 'PENDIENTE_VINCULACION_CONTRATISTA')
            ->first();

        if (!$solicitud) {
            session()->flash('error_sub', 'La solicitud no es válida o ya fue procesada.');
            return;
        }

        $uoIdSeleccionada = $this->unidadOrganizacionalParaVincular[$solicitudId] ?? null;
        if (!$uoIdSeleccionada) {
            session()->flash('error_sub', 'Debe seleccionar una Unidad Organizacional para vincular al sub-contratista.');
            return;
        }

        $unidadOrganizacional = UnidadOrganizacionalMandante::find($uoIdSeleccionada);
        if (!$unidadOrganizacional) {
            session()->flash('error_sub', 'La Unidad Organizacional seleccionada no es válida.');
            return;
        }

        DB::beginTransaction();
        try {
            $solicitud->update([
                'estado' => 'PENDIENTE',
                'mandante_id' => $unidadOrganizacional->mandante_id,
            ]);

            $subContratista = $solicitud->contratista;
            $subContratista->unidadesOrganizacionalesMandante()->attach($uoIdSeleccionada);

            DB::commit();
            session()->flash('message_sub', 'Sub-contratista vinculado exitosamente. La solicitud ha sido enviada para aprobación final.');
            
            $this->cargarSolicitudesPendientes();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al vincular subcontratista: " . $e->getMessage());
            session()->flash('error_sub', 'Ocurrió un error inesperado al procesar la vinculación.');
        }
    }

    public function render()
    {
        return view('livewire.contratista.solicitudes-subcontratistas-page');
    }
}