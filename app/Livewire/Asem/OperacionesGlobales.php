<?php

namespace App\Livewire\Asem;

use Livewire\Component;
use App\Models\Contratista;
use App\Models\Mandante;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

#[Layout('layouts.app')]
class OperacionesGlobales extends Component
{
    // ================== INICIO DE LA MODIFICACIÓN ==================
    public $mandantesDisponibles = [];
    public $contratistasDisponibles = [];
    
    #[Url] // Permite recibir el ID desde la URL (desde Supervisión)
    public $selectedMandanteId = null;
    #[Url] // Permite recibir el ID desde la URL (desde Supervisión)
    public $selectedContratistaId = null;
    
    // Parámetros de preselección para filtros internos (vienen desde Supervisión)
    #[Url(as: 'lugar')]
    public $preselectedLugar = null;
    
    #[Url(as: 'uo')]
    public $preselectedUo = null;
    
    #[Url(as: 'contrato')]
    public $preselectedContrato = null;

    public function mount()
    {
        // La nueva doctrina carga primero a los Mandantes.
        $this->mandantesDisponibles = Mandante::where('is_active', true)
            ->orderBy('razon_social')
            ->get(['id', 'razon_social']);

        // Si viene un mandante pre-seleccionado desde la URL (ej: desde Supervisión), cargamos sus contratistas
        if ($this->selectedMandanteId) {
            $mandante = Mandante::find($this->selectedMandanteId);
            if ($mandante) {
                // FILTRO ROBUSTO EN MOUNT TAMBIÉN
                $this->contratistasDisponibles = Contratista::whereHas('solicitudesVinculacion', function ($q) {
                    $q->where('mandante_id', $this->selectedMandanteId)
                      ->where('estado', 'APROBADA');
                })
                ->whereDoesntHave('solicitudesVinculacion', function ($q) {
                    $q->where('mandante_id', $this->selectedMandanteId)
                      ->whereNotNull('contratista_padre_id')
                      ->where('estado', 'APROBADA');
                })
                ->orderBy('razon_social')
                ->get(['id', 'razon_social', 'rut']);
                
                // Si viene un contratistaId desde la URL, verificar si es principal o subcontratista
                if ($this->selectedContratistaId) {
                    // Verificar si el contratista está en la lista de principales
                    $esContratistaPrincipal = $this->contratistasDisponibles->contains('id', (int)$this->selectedContratistaId);
                    
                    if ($esContratistaPrincipal) {
                        // Es principal, cargar sus subcontratistas
                        $this->cargarSubs();
                    } else {
                        // Es un subcontratista, encontrar su contratista padre
                        $solicitud = \App\Models\SolicitudVinculacion::where('contratista_id', $this->selectedContratistaId)
                            ->where('mandante_id', $this->selectedMandanteId)
                            ->where('estado', 'APROBADA')
                            ->whereNotNull('contratista_padre_id')
                            ->first();
                        
                        if ($solicitud && $solicitud->contratista_padre_id) {
                            // Guardar el ID del subcontratista
                            $subContratistaId = $this->selectedContratistaId;
                            
                            // Establecer el padre como contratista principal seleccionado
                            $this->selectedContratistaId = $solicitud->contratista_padre_id;
                            
                            // Cargar los subcontratistas del padre (sin resetear selectedSubContratistaId)
                            $contratistaId = $this->selectedContratistaId;
                            $this->subContratistasDisponibles = [];
                            $contratista = Contratista::find($contratistaId);
                            if ($contratista) {
                                $this->debugPadreId = $contratista->id;
                                $this->subContratistasDisponibles = $this->obtenerDescendientesPlanos($contratista);
                            }
                            
                            // Preseleccionar el subcontratista
                            $this->selectedSubContratistaId = $subContratistaId;
                        }
                    }
                }
            }
        }
    }

    /**
     * Se activa cuando se selecciona un Mandante.
     * Carga los contratistas aprobados para ese Mandante.
     */
    public function updatedSelectedMandanteId($mandanteId)
    {
        // Resetea la selección de contratista para forzar una nueva elección.
        $this->selectedContratistaId = null;
        $this->selectedSubContratistaId = null; // Reset sub
        $this->contratistasDisponibles = [];
        $this->subContratistasDisponibles = []; // Reset sub list

        if ($mandanteId) {
            $mandante = Mandante::find($mandanteId);
            if ($mandante) {
                // FILTRO ROBUSTO: Traer contratistas vinculados que NO sean subcontratistas (sin padre)
                $this->contratistasDisponibles = Contratista::whereHas('solicitudesVinculacion', function ($q) use ($mandanteId) {
                    $q->where('mandante_id', $mandanteId)
                      ->where('estado', 'APROBADA');
                })
                ->whereDoesntHave('solicitudesVinculacion', function ($q) use ($mandanteId) {
                    $q->where('mandante_id', $mandanteId)
                      ->whereNotNull('contratista_padre_id')
                      ->where('estado', 'APROBADA');
                })
                ->orderBy('razon_social')
                ->get(['id', 'razon_social', 'rut']);
            }
        }
    }

    // ================== NUEVA LÓGICA DE SUBCONTRATISTAS ==================
    public $subContratistasDisponibles = [];
    public $selectedSubContratistaId = null;

    public $debugPadreId = null; 

    public function updatedSelectedContratistaId($contratistaId)
    {
        $this->cargarSubs();
    }

    public function cargarSubs()
    {
        $this->selectedSubContratistaId = null;
        $this->subContratistasDisponibles = [];
        // debugPadreId set null or msg
        
        $contratistaId = $this->selectedContratistaId;

        if ($contratistaId) {
            $contratista = Contratista::find($contratistaId);
            if ($contratista) {
                $this->debugPadreId = $contratista->id;
                $this->subContratistasDisponibles = $this->obtenerDescendientesPlanos($contratista);
            }
        }
    }

    private function obtenerDescendientesPlanos($contratista, $nivel = 0)
    {
        $items = collect();
        
        // QUERY MANUAL ROBUSTA PARA EVITAR PROBLEMAS DE RELACIONES/GLOBAL SCOPES
        $idsHijos = \Illuminate\Support\Facades\DB::table('solicitudes_vinculacion')
            ->where('contratista_padre_id', $contratista->id)
            ->where('estado', 'APROBADA')
            ->distinct()
            ->pluck('contratista_id');
            
        if ($idsHijos->isEmpty()) {
            return $items;
        }

        $hijos = Contratista::whereIn('id', $idsHijos)
            ->orderBy('razon_social')
            ->get();
        
        foreach ($hijos as $hijo) {
            $prefix = str_repeat('↳ ', $nivel + 1); 
            
            $items->push([
                'id' => $hijo->id,
                'razon_social' => $prefix . $hijo->razon_social,
                'rut' => $hijo->rut
            ]);
            
            $descendientes = $this->obtenerDescendientesPlanos($hijo, $nivel + 1);
            $items = $items->merge($descendientes);
        }
        
        return $items;
    }
    // ================== FIN NUEVA LÓGICA ==================

    // ================== FIN DE LA MODIFICACIÓN ====================

    public function render()
    {
        return view('livewire.asem.operaciones-globales');
    }
}