<?php

namespace App\Livewire\Contratista;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\ContratistaUnidadOrganizacional;
use App\Models\User;

class MisVinculaciones extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'bootstrap';
    
    // Filtros
    public $busqueda = '';
    public $filtroMandante = '';
    public $filtroLugarTrabajo = '';
    
    // Modal para asignar usuarios
    public $showModalAsignar = false;
    public $vinculacionIdSeleccionada = null;
    public $vinculacionNombre = '';
    public $usuariosAsignados = [];
    
    public function mount()
    {
        $user = Auth::user();
        
        if (!$user->hasAnyRole(['Contratista_Admin', 'Subcontratista', 'Contratista_User'])) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }
    }
    
    public function render()
    {
        $user = Auth::user();
        $contratistaId = $user->contratista_id;
        
        $query = ContratistaUnidadOrganizacional::where('contratista_id', $contratistaId)
            ->with(['unidadOrganizacionalMandante.mandante', 'dependencia.parent', 'tipoContrato']);
        
        // Filtros
        if (!empty($this->busqueda)) {
            $busqueda = $this->busqueda;
            $query->where(function($q) use ($busqueda) {
                $q->whereHas('unidadOrganizacionalMandante.mandante', function($mq) use ($busqueda) {
                    $mq->where('razon_social', 'like', "%{$busqueda}%");
                })
                ->orWhereHas('dependencia', function($dq) use ($busqueda) {
                    $dq->where('nombre', 'like', "%{$busqueda}%");
                })
                ->orWhere('numero_contrato', 'like', "%{$busqueda}%");
            });
        }
        
        $vinculaciones = $query->orderBy('id', 'desc')->paginate(10);
        
        // Obtener usuarios Contratista_User del contratista
        $usuariosDisponibles = User::where('contratista_id', $contratistaId)
            ->whereHas('roles', function($q) {
                $q->where('name', 'Contratista_User');
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('livewire.contratista.mis-vinculaciones', [
            'vinculaciones' => $vinculaciones,
            'usuariosDisponibles' => $usuariosDisponibles,
        ])->layout('layouts.app');
    }
    
    public function updated($propertyName)
    {
        if (in_array($propertyName, ['busqueda', 'filtroMandante', 'filtroLugarTrabajo'])) {
            $this->resetPage();
        }
    }
    
    /**
     * Abre el modal para asignar usuarios a una vinculación
     */
    public function abrirModalAsignar($vinculacionId)
    {
        $vinculacion = ContratistaUnidadOrganizacional::with(['mandante', 'dependencia', 'unidadOrganizacionalMandante'])
            ->find($vinculacionId);
        
        if (!$vinculacion) {
            session()->flash('error', 'Vinculación no encontrada.');
            return;
        }
        
        // Verificar que pertenece al contratista del usuario
        if ($vinculacion->contratista_id !== Auth::user()->contratista_id) {
            session()->flash('error', 'No tienes permiso para gestionar esta vinculación.');
            return;
        }
        
        $this->vinculacionIdSeleccionada = $vinculacionId;
        
        // Construir nombre descriptivo (el mandante se accede por UO->mandante)
        $nombreMandante = $vinculacion->unidadOrganizacionalMandante?->mandante?->razon_social ?? 'Sin Principal';
        $nombre = $nombreMandante;
        if ($vinculacion->dependencia) {
            $nombre .= ' / ' . $vinculacion->dependencia->nombre;
        }
        if ($vinculacion->unidadOrganizacionalMandante) {
            $nombre .= ' / ' . $vinculacion->unidadOrganizacionalMandante->nombre_unidad;
        }
        if ($vinculacion->numero_contrato) {
            $nombre .= ' (Cto: ' . $vinculacion->numero_contrato . ')';
        }
        $this->vinculacionNombre = $nombre;
        
        // Obtener usuarios actualmente asignados
        $this->usuariosAsignados = User::whereHas('vinculacionesAsignadas', function($q) use ($vinculacionId) {
            $q->where('contratista_unidad_organizacional_id', $vinculacionId);
        })->pluck('id')->toArray();
        
        $this->showModalAsignar = true;
    }
    
    /**
     * Cierra el modal de asignación
     */
    public function cerrarModal()
    {
        $this->showModalAsignar = false;
        $this->vinculacionIdSeleccionada = null;
        $this->vinculacionNombre = '';
        $this->usuariosAsignados = [];
    }
    
    /**
     * Guarda los usuarios asignados a la vinculación
     */
    public function guardarAsignaciones()
    {
        if (!$this->vinculacionIdSeleccionada) {
            session()->flash('error', 'No hay vinculación seleccionada.');
            return;
        }
        
        $vinculacion = ContratistaUnidadOrganizacional::find($this->vinculacionIdSeleccionada);
        
        if (!$vinculacion || $vinculacion->contratista_id !== Auth::user()->contratista_id) {
            session()->flash('error', 'No tienes permiso para modificar esta vinculación.');
            return;
        }
        
        // Obtener todos los usuarios Contratista_User del contratista
        $contratistaId = Auth::user()->contratista_id;
        $usuariosContratista = User::where('contratista_id', $contratistaId)
            ->whereHas('roles', function($q) {
                $q->where('name', 'Contratista_User');
            })
            ->get();
        
        // Para cada usuario, actualizar su relación con esta vinculación
        foreach ($usuariosContratista as $usuario) {
            if (in_array($usuario->id, $this->usuariosAsignados)) {
                // Asignar si no está asignado
                if (!$usuario->vinculacionesAsignadas()->where('contratista_unidad_organizacional_id', $this->vinculacionIdSeleccionada)->exists()) {
                    $usuario->vinculacionesAsignadas()->attach($this->vinculacionIdSeleccionada);
                }
            } else {
                // Desasignar si está asignado
                $usuario->vinculacionesAsignadas()->detach($this->vinculacionIdSeleccionada);
            }
        }
        
        session()->flash('message', 'Usuarios asignados correctamente a la vinculación.');
        $this->cerrarModal();
    }
    
    /**
     * Obtiene los nombres de usuarios asignados a una vinculación
     */
    public function getUsuariosAsignadosNombres($vinculacionId)
    {
        $usuarios = User::whereHas('vinculacionesAsignadas', function($q) use ($vinculacionId) {
            $q->where('contratista_unidad_organizacional_id', $vinculacionId);
        })->get(['id', 'name']);
        
        return $usuarios;
    }
}
