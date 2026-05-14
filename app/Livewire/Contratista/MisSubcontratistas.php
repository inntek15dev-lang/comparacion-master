<?php

namespace App\Livewire\Contratista;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Contratista;
use Illuminate\Support\Facades\Auth;
use App\Models\ContratistaUnidadOrganizacional;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class MisSubcontratistas extends Component
{
    use WithPagination;

    public $search = '';
    public $contratistaActual;
    public $showModalVinculaciones = false;
    public $subContratistaSeleccionado;
    public $vinculacionesSub = [];

    public function mount()
    {
        $user = Auth::user();
        if (!$user->hasRole('Contratista_Admin')) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }
        $this->contratistaActual = $user->contratista;
    }

    public function abrirModalVinculaciones($subId, $pivotId = null)
    {
        $this->subContratistaSeleccionado = Contratista::find($subId);
        
        if (!$this->subContratistaSeleccionado) return;

        // Cargar vinculaciones del sub contratista
        $this->vinculacionesSub = ContratistaUnidadOrganizacional::where('contratista_id', $subId)
            ->with(['unidadOrganizacional.mandante', 'dependencia.mandante'])
            ->get();
            
        $this->showModalVinculaciones = true;
    }
    
    public function cerrarModalVinculaciones()
    {
        $this->showModalVinculaciones = false;
        $this->subContratistaSeleccionado = null;
        $this->vinculacionesSub = [];
    }

    public function render()
    {
        // Obtener TODOS los subcontratistas recursivamente (hijos, nietos, etc.)
        $allDescendantIds = $this->getAllDescendantIds($this->contratistaActual->id);
        
        $query = Contratista::whereIn('id', $allDescendantIds)
            ->with(['users', 'contratistaPadreAprobado']);
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('razon_social', 'like', '%'.$this->search.'%')
                  ->orWhere('rut', 'like', '%'.$this->search.'%');
            });
        }
        
        $subcontratistas = $query->paginate(10);

        return view('livewire.contratista.mis-subcontratistas', [
            'subcontratistas' => $subcontratistas
        ]);
    }

    /**
     * Obtiene recursivamente todos los IDs de descendientes (hijos, nietos, bisnietos, etc.)
     */
    private function getAllDescendantIds($contratistaId, $depth = 0)
    {
        // Limitar profundidad para evitar loops infinitos
        if ($depth > 5) return [];
        
        $allIds = [];
        
        // Obtener hijos directos aprobados consultando la tabla de vinculaciones
        $children = \App\Models\SolicitudVinculacion::where('contratista_padre_id', $contratistaId)
            ->where('estado', 'APROBADA')
            ->pluck('contratista_id')
            ->toArray();
        
        $allIds = array_merge($allIds, $children);
        
        // Para cada hijo, obtener sus descendientes
        foreach ($children as $childId) {
            $grandchildren = $this->getAllDescendantIds($childId, $depth + 1);
            $allIds = array_merge($allIds, $grandchildren);
        }
        
        return array_unique($allIds);
    }

    // =========================================================================
    // LÓGICA DE GESTIÓN DE USUARIOS
    // =========================================================================
    public $showModalUsuarios = false;
    public $usuariosSubcontratista = [];
    public $showFormUsuario = false;
    public $userForm = [
        'id' => null,
        'name' => '',
        'email' => '',
        'password' => '',
    ];

    public function gestionarUsuarios($subId)
    {
        $this->subContratistaSeleccionado = Contratista::find($subId);
        if (!$this->subContratistaSeleccionado) return;
        
        $this->cargarUsuariosSubcontratista();
        $this->showFormUsuario = false;
        $this->resetUserForm();
        $this->showModalUsuarios = true;
    }

    public function cargarUsuariosSubcontratista()
    {
        if ($this->subContratistaSeleccionado) {
            $this->usuariosSubcontratista = \App\Models\User::where('contratista_id', $this->subContratistaSeleccionado->id)->get();
        }
    }

    public function cerrarModalUsuarios()
    {
        $this->showModalUsuarios = false;
        $this->subContratistaSeleccionado = null;
        $this->usuariosSubcontratista = [];
        $this->resetUserForm();
    }

    public function formCrearUsuario()
    {
        $this->resetUserForm();
        $this->showFormUsuario = true;
    }

    public function editarUsuario($userId)
    {
        $user = \App\Models\User::find($userId);
        if (!$user) return;

        $this->userForm = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
        ];
        $this->showFormUsuario = true;
    }

    public function cancelarEdicionUsuario()
    {
        $this->showFormUsuario = false;
        $this->resetUserForm();
    }

    private function resetUserForm()
    {
        $this->userForm = [
            'id' => null,
            'name' => '',
            'email' => '',
            'password' => '',
        ];
        $this->resetValidation();
    }

    public function guardarUsuario()
    {
        $rules = [
            'userForm.name' => 'required|string|max:255',
            'userForm.email' => 'required|email|max:255|unique:users,email,' . ($this->userForm['id'] ?? 'NULL'),
        ];

        if (!$this->userForm['id'] || !empty($this->userForm['password'])) {
            $rules['userForm.password'] = 'required|min:8';
        }

        $this->validate($rules);

        $currentUser = Auth::user();
        
        $data = [
            'name' => $this->userForm['name'],
            'email' => $this->userForm['email'],
            'contratista_id' => $this->subContratistaSeleccionado->id,
            'user_type' => 'contratista', // Tipo genérico
        ];

        if (!empty($this->userForm['password'])) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($this->userForm['password']);
        }

        if (!$this->userForm['id']) {
            $data['created_by_user_id'] = $currentUser->id;
        }

        $user = \App\Models\User::updateOrCreate(['id' => $this->userForm['id']], $data);
        
        // Asignar rol Subcontratista siempre
        if (!$user->hasRole('Subcontratista')) {
            $user->syncRoles(['Subcontratista']);
        }

        $this->showFormUsuario = false;
        $this->cargarUsuariosSubcontratista();
    }    

    public function eliminarUsuario($userId)
    {
        $user = \App\Models\User::find($userId);
        if ($user && $user->contratista_id == $this->subContratistaSeleccionado->id) {
            $user->delete();
            $this->cargarUsuariosSubcontratista();
        }
    }
}
