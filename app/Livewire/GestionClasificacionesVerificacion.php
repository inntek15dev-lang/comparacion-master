<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ClasificacionVerificacion;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Clasificaciones de Verificación')]
class GestionClasificacionesVerificacion extends Component
{
    use WithPagination;

    public bool $mostrarModal = false;
    public ?ClasificacionVerificacion $clasificacionActual;
    public string $nombre = '';
    public string $descripcion = '';
    public bool $is_active = true;

    public string $filtroNombre = '';
    public string $filtroEstado = 'todos';

    protected function rules()
    {
        $id = $this->clasificacionActual?->id ?? 'NULL';
        return [
            'nombre' => "required|string|min:3|max:240|unique:clasificaciones_verificacion,nombre,{$id},id",
            'descripcion' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre es obligatorio.',
        'nombre.unique' => 'Esta clasificación ya existe.',
    ];

    public function updatedFiltroNombre() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function mount()
    {
        $this->clasificacionActual = new ClasificacionVerificacion();
    }

    public function render()
    {
        $query = ClasificacionVerificacion::query();

        if (!empty($this->filtroNombre)) {
            $query->where('nombre', 'like', '%' . $this->filtroNombre . '%');
        }

        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }

        $clasificaciones = $query->orderBy('nombre', 'asc')->paginate(10);

        return view('livewire.gestion-clasificaciones-verificacion', [
            'clasificaciones' => $clasificaciones,
        ]);
    }

    public function abrirModalParaCrear()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos.');
            return;
        }
        $this->resetValidation();
        $this->clasificacionActual = new ClasificacionVerificacion();
        $this->nombre = '';
        $this->descripcion = '';
        $this->is_active = true;
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(ClasificacionVerificacion $clasificacion)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos.');
            return;
        }
        $this->resetValidation();
        $this->clasificacionActual = $clasificacion;
        $this->nombre = $clasificacion->nombre;
        $this->descripcion = $clasificacion->descripcion ?? '';
        $this->is_active = $clasificacion->is_active;
        $this->mostrarModal = true;
    }

    public function guardar()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos.');
            return;
        }
        $validatedData = $this->validate();

        if (empty($this->clasificacionActual->id)) {
            ClasificacionVerificacion::create($validatedData);
            session()->flash('success', 'Clasificación creada exitosamente.');
        } else {
            $this->clasificacionActual->update($validatedData);
            session()->flash('success', 'Clasificación actualizada exitosamente.');
        }
        $this->cerrarModal();
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->resetValidation();
        $this->nombre = '';
        $this->descripcion = '';
        $this->is_active = true;
        $this->clasificacionActual = new ClasificacionVerificacion();
    }

    public function confirmarAlternarEstado(ClasificacionVerificacion $clasificacion)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos.');
            return;
        }
        $clasificacion->update(['is_active' => !$clasificacion->is_active]);
        session()->flash('success', 'Estado actualizado exitosamente.');
    }
}
