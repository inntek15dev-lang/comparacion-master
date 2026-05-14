<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Contratista;
use App\Models\Mandante;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;

class GestionUsuarios extends Component
{
    use WithPagination;

    // Propiedades para el modal de edición/creación
    public $isModalOpen = false;
    public $userId;
    public $name, $email, $password, $password_confirmation;
    public $selectedRole, $contratista_id, $mandante_id;
    
    // Vinculaciones seleccionadas para Contratista_User
    public $vinculacionesSeleccionadas = [];

    // NUEVA PROPIEDAD PARA CONFIRMAR ELIMINACIÓN
    public $confirmingUserDeletionId;
    
    // Propiedades para los filtros
    public $search = ''; 
    public $filtroEmail = '';
    public $filtroEmpresa = '';
    public $filtroRol = '';
    public $filtroEstado = '';

    protected $paginationTheme = 'bootstrap';

    /**
     * Obtiene los roles que el usuario actual puede asignar a otros usuarios.
     */
    public function getRolesDisponiblesProperty()
    {
        $user = Auth::user();
        
        if ($user->hasRole('ASEM_Admin')) {
            return Role::orderBy('name')->get();
        }
        
        if ($user->hasRole('Mandante_Admin')) {
            // Mandante_Admin puede crear usuarios Mandante y Contratista
            return Role::whereIn('name', [
                'Mandante_Admin', 
                'Mandante_Ver', 
                'Mandante_Validator',
                'Contratista_Admin',
                'Contratista_User',
                'Subcontratista'
            ])->orderBy('name')->get();
        }
        
        if ($user->hasRole('Contratista_Admin')) {
            // Contratista_Admin solo puede crear usuarios internos para SU propia empresa
            return Role::whereIn('name', ['Contratista_Admin', 'Contratista_User'])->orderBy('name')->get();
        }
        
        return collect(); // Sin permisos para crear usuarios
    }

    /**
     * Obtiene los contratistas que el usuario actual puede gestionar.
     */
    public function getContratistasDisponiblesProperty()
    {
        $user = Auth::user();
        
        if ($user->hasRole('ASEM_Admin')) {
            return Contratista::orderBy('razon_social')->get();
        }
        
        if ($user->hasRole('Mandante_Admin')) {
            // TODO: Filtrar solo contratistas vinculados al mandante del usuario
            // Por ahora, permitir todos los contratistas activos
            return Contratista::where('is_active', true)->orderBy('razon_social')->get();
        }
        
        if ($user->hasRole('Contratista_Admin')) {
            // Su propia empresa + sub-contratistas aprobados (todos los niveles)
            $contratistaId = $user->contratista_id;
            $contratistaPrincipal = Contratista::find($contratistaId);
            
            if (!$contratistaPrincipal) {
                return collect();
            }
            
            // Recopilar IDs de sub-contratistas en todos los niveles
            $idsContratistas = collect([$contratistaId]);
            $this->recopilarSubContratistasIds($contratistaPrincipal, $idsContratistas);
            
            return Contratista::whereIn('id', $idsContratistas->toArray())
                ->where('is_active', true)
                ->orderBy('tipo_inscripcion') // Contratista primero, luego Subcontratista
                ->orderBy('razon_social')
                ->get();
        }
        
        return collect();
    }

    /**
     * Función auxiliar para recopilar IDs de sub-contratistas recursivamente.
     */
    private function recopilarSubContratistasIds(Contratista $contratista, &$ids)
    {
        $subContratistas = $contratista->subContratistasAprobados;
        foreach ($subContratistas as $sub) {
            $ids->push($sub->id);
            $this->recopilarSubContratistasIds($sub, $ids);
        }
    }

    /**
     * Obtiene los mandantes que el usuario actual puede gestionar.
     */
    public function getMandantesDisponiblesProperty()
    {
        $user = Auth::user();
        
        if ($user->hasRole('ASEM_Admin')) {
            return Mandante::orderBy('razon_social')->get();
        }
        
        if ($user->hasRole('Mandante_Admin')) {
            // Solo su propio mandante
            return Mandante::where('id', $user->mandante_id)->get();
        }
        
        return collect();
    }

    /**
     * Obtiene las vinculaciones disponibles según el contratista seleccionado.
     */
    public function getVinculacionesDisponiblesProperty()
    {
        if (!$this->contratista_id) {
            return collect();
        }
        
        return \App\Models\ContratistaUnidadOrganizacional::where('contratista_id', $this->contratista_id)
            ->with(['mandante', 'dependencia', 'unidadOrganizacionalMandante'])
            ->get()
            ->map(function ($vinc) {
                $label = $vinc->mandante->razon_social ?? 'Sin Principal';
                if ($vinc->dependencia) {
                    $label .= ' / ' . $vinc->dependencia->nombre;
                }
                if ($vinc->unidadOrganizacionalMandante) {
                    $label .= ' / ' . $vinc->unidadOrganizacionalMandante->nombre_unidad;
                }
                if ($vinc->numero_contrato) {
                    $label .= ' (Cto: ' . $vinc->numero_contrato . ')';
                }
                return [
                    'id' => $vinc->id,
                    'label' => $label,
                ];
            });
    }

    public function render()
    {
        $user = Auth::user();
        $query = User::query()->with(['roles', 'contratista', 'mandante', 'createdBy']);

        // ====== FILTRADO DE USUARIOS SEGÚN ROL DEL USUARIO ACTUAL ======
        if ($user->hasRole('Mandante_Admin')) {
            // Mandante_Admin ve: 
            // 1. Usuarios de su propio mandante
            // 2. Usuarios que él creó
            // 3. Usuarios de contratistas que tienen VINCULACIÓN APROBADA con este mandante
            
            $mandanteId = $user->mandante_id;
            
            // Obtener IDs de contratistas vinculados activamente a este mandante
            $contratistasIds = \App\Models\SolicitudVinculacion::where('mandante_id', $mandanteId)
                ->where('estado', 'APROBADA')
                ->pluck('contratista_id')
                ->toArray();

            $query->where(function ($q) use ($user, $contratistasIds) {
                $q->where('mandante_id', $user->mandante_id)
                  ->orWhere('created_by_user_id', $user->id)
                  ->orWhereIn('contratista_id', $contratistasIds);
            });
        } elseif ($user->hasRole('Contratista_Admin')) {
            // Contratista_Admin ve: SOLO usuarios de su propia empresa (NO subcontratistas)
            $query->where('contratista_id', $user->contratista_id);
            
            // Opcional: Excluir explícitamente rol 'Subcontratista' si existiera alguno asignado erróneamente a esta empresa
            $query->whereHas('roles', function($r) {
                $r->where('name', '!=', 'Subcontratista');
            });
        }
        // ASEM_Admin ve todos los usuarios (sin filtro adicional)

        // Filtros de búsqueda
        if (!empty($this->search)) $query->where('name', 'like', '%' . $this->search . '%');
        if (!empty($this->filtroEmail)) $query->where('email', 'like', '%' . $this->filtroEmail . '%');
        if (!empty($this->filtroRol)) $query->whereHas('roles', function ($q) { $q->where('name', $this->filtroRol); });
        if ($this->filtroEstado !== '') $query->where('is_active', $this->filtroEstado);
        
        // Filtro por empresa (busca en mandante o contratista)
        if (!empty($this->filtroEmpresa)) {
            $filtroEmpresa = $this->filtroEmpresa;
            $query->where(function ($q) use ($filtroEmpresa) {
                $q->whereHas('mandante', function ($mq) use ($filtroEmpresa) {
                    $mq->where('razon_social', 'like', '%' . $filtroEmpresa . '%');
                })
                ->orWhereHas('contratista', function ($cq) use ($filtroEmpresa) {
                    $cq->where('razon_social', 'like', '%' . $filtroEmpresa . '%');
                });
                
                // Si busca "asem", incluir usuarios tipo asem
                if (stripos('asem', $filtroEmpresa) !== false || stripos($filtroEmpresa, 'asem') !== false) {
                    $q->orWhere('user_type', 'asem');
                }
            });
        }

        $users = $query->orderBy('name')->paginate(10);

        return view('livewire.gestion-usuarios', [
            'users' => $users,
            'roles' => $this->rolesDisponibles,
            'contratistas' => $this->contratistasDisponibles,
            'mandantes' => $this->mandantesDisponibles,
            'vinculacionesDisponibles' => $this->vinculacionesDisponibles,
            'esAsemAdmin' => $user->hasRole('ASEM_Admin'),
            'esMandanteAdmin' => $user->hasRole('Mandante_Admin'),
            'esContratistaAdmin' => $user->hasRole('Contratista_Admin'),
        ])->layout('layouts.app');
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['search', 'filtroEmail', 'filtroEmpresa', 'filtroRol', 'filtroEstado'])) {
            $this->resetPage();
        }
    }

    private function resetInputFields()
    {
        $this->userId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->selectedRole = '';
        $this->contratista_id = null;
        $this->mandante_id = null;
        $this->vinculacionesSeleccionadas = [];
        $this->confirmingUserDeletionId = null;
    }

    public function openModal() { $this->isModalOpen = true; }
    public function closeModal() { $this->isModalOpen = false; $this->resetInputFields(); }

    public function create()
    {
        $user = Auth::user();
        
        // Pre-seleccionar valores según el rol del usuario actual
        if ($user->hasRole('Mandante_Admin')) {
            $this->mandante_id = $user->mandante_id;
        } elseif ($user->hasRole('Contratista_Admin')) {
            $this->contratista_id = $user->contratista_id;
            $this->selectedRole = 'Contratista_User'; // Pre-seleccionar el único rol disponible
        }
        
        $this->resetInputFields();
        
        // Restaurar pre-selecciones después del reset
        if ($user->hasRole('Mandante_Admin')) {
            $this->mandante_id = $user->mandante_id;
        } elseif ($user->hasRole('Contratista_Admin')) {
            $this->contratista_id = $user->contratista_id;
            $this->selectedRole = 'Contratista_User';
        }
        
        $this->openModal();
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        
        // Verificar que el usuario actual puede editar este usuario
        if (!$this->puedeGestionarUsuario($user)) {
            session()->flash('error', 'No tienes permisos para editar este usuario.');
            return;
        }
        
        $this->userId = $id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->selectedRole = $user->roles->first()->name ?? '';
        $this->contratista_id = $user->contratista_id;
        $this->mandante_id = $user->mandante_id;
        $this->password = '';
        $this->password_confirmation = '';
        // Cargar vinculaciones asignadas al editar
        $this->vinculacionesSeleccionadas = $user->vinculacionesAsignadas->pluck('id')->toArray();
        $this->openModal();
    }

    public function save()
    {
        $currentUser = Auth::user();
        
        // Validar que el rol seleccionado está permitido
        $rolesPermitidos = $this->rolesDisponibles->pluck('name')->toArray();
        if (!in_array($this->selectedRole, $rolesPermitidos)) {
            session()->flash('error', 'No tienes permisos para asignar este rol.');
            return;
        }
        
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $this->userId,
            'selectedRole' => 'required|exists:roles,name',
        ];

        if (!$this->userId || !empty($this->password)) {
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        }

        // Roles de Contratista que requieren contratista_id
        if (in_array($this->selectedRole, ['Contratista_Admin', 'Contratista_User'])) {
            $rules['contratista_id'] = 'required|exists:contratistas,id';
        }
        // Roles de Mandante que requieren mandante_id
        if (in_array($this->selectedRole, ['Mandante_Admin', 'Mandante_Ver', 'Mandante_Validator'])) {
            $rules['mandante_id'] = 'required|exists:mandantes,id';
        }
        
        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'user_type' => str_contains(strtolower($this->selectedRole), 'contratista') ? 'contratista' : (str_contains(strtolower($this->selectedRole), 'mandante') ? 'mandante' : 'asem'),
            'contratista_id' => in_array($this->selectedRole, ['Contratista_Admin', 'Contratista_User', 'Subcontratista']) ? $this->contratista_id : null,
            'mandante_id' => in_array($this->selectedRole, ['Mandante_Admin', 'Mandante_Ver', 'Mandante_Validator']) ? $this->mandante_id : null,
        ];

        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }
        
        // Asignar created_by_user_id solo al crear
        if (!$this->userId) {
            $data['created_by_user_id'] = $currentUser->id;
        }

        $user = User::updateOrCreate(['id' => $this->userId], $data);
        $user->syncRoles([$this->selectedRole]);
        
        // Sincronizar vinculaciones solo para Contratista_User
        if ($this->selectedRole === 'Contratista_User') {
            $user->vinculacionesAsignadas()->sync($this->vinculacionesSeleccionadas);
        } else {
            // Si cambia de rol, limpiar vinculaciones
            $user->vinculacionesAsignadas()->detach();
        }
        
        session()->flash('message', $this->userId ? 'Usuario actualizado exitosamente.' : 'Usuario creado exitosamente.');
        $this->closeModal();
    }
    
    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            session()->flash('error', 'No puedes desactivar tu propia cuenta.');
            return;
        }
        
        if (!$this->puedeGestionarUsuario($user)) {
            session()->flash('error', 'No tienes permisos para cambiar el estado de este usuario.');
            return;
        }
        
        $user->is_active = !$user->is_active;
        $user->save();
        session()->flash('message', 'Estado del usuario actualizado exitosamente.');
    }

    // ============= FUNCIONES PARA ELIMINAR USUARIO =============
    public function confirmUserDeletion($id)
    {
        $user = User::find($id);
        
        // No permitir que el usuario se elimine a sí mismo
        if ($id === auth()->id()) {
            session()->flash('error', 'No puedes eliminar tu propia cuenta.');
            return;
        }
        
        if (!$this->puedeGestionarUsuario($user)) {
            session()->flash('error', 'No tienes permisos para eliminar este usuario.');
            return;
        }
        
        $this->confirmingUserDeletionId = $id;
    }

    public function deleteUser()
    {
        $user = User::find($this->confirmingUserDeletionId);
        if ($user) {
            $user->delete();
            session()->flash('message', 'Usuario eliminado exitosamente.');
        } else {
            session()->flash('error', 'No se pudo encontrar el usuario para eliminar.');
        }
        $this->confirmingUserDeletionId = null;
    }
    
    /**
     * Verifica si el usuario actual puede gestionar (editar/eliminar) al usuario dado.
     */
    private function puedeGestionarUsuario(?User $targetUser): bool
    {
        if (!$targetUser) return false;
        
        $currentUser = Auth::user();
        
        // ASEM_Admin puede gestionar a todos
        if ($currentUser->hasRole('ASEM_Admin')) {
            return true;
        }
        
        // Solo puede gestionar usuarios que él creó
        return $targetUser->created_by_user_id === $currentUser->id;
    }
}