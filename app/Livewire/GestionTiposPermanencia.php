<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TipoPermanencia; 
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Gestión de Tipos de Permanencia')]
class GestionTiposPermanencia extends Component
{
    use WithPagination;

    public bool $mostrarModal = false;
    public ?TipoPermanencia $tipoPermanenciaActual;
    public string $nombre = '';
    public bool $is_active = true;

    public string $filtroNombre = '';
    public string $filtroEstado = 'todos';

    protected function rules()
    {
        $tipoId = $this->tipoPermanenciaActual?->id ?? 'NULL';
        return [
            'nombre' => "required|string|min:3|max:100|unique:tipos_permanencias,nombre,{$tipoId},id",
            'is_active' => 'required|boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre del tipo de permanencia es obligatorio.',
        'nombre.unique' => 'Este tipo de permanencia ya existe.',
    ];

    public function updatedFiltroNombre() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function mount()
    {
        $this->tipoPermanenciaActual = new TipoPermanencia();
    }

    public function render()
    {
        $query = TipoPermanencia::query();

        if (!empty($this->filtroNombre)) {
            $query->where('nombre', 'like', '%' . $this->filtroNombre . '%');
        }

        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }

        $tiposPermanencia = $query->orderBy('nombre', 'asc')->paginate(10);

        return view('livewire.gestion-tipos-permanencia', [
            'tiposPermanencia' => $tiposPermanencia,
        ]);
    }

    public function abrirModalParaCrear()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->tipoPermanenciaActual = new TipoPermanencia();
        $this->nombre = '';
        $this->is_active = true;
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(TipoPermanencia $tipoPermanencia)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->tipoPermanenciaActual = $tipoPermanencia;
        $this->nombre = $tipoPermanencia->nombre;
        $this->is_active = $tipoPermanencia->is_active;
        $this->mostrarModal = true;
    }

    public function guardarTipoPermanencia()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $validatedData = $this->validate();

        if (empty($this->tipoPermanenciaActual->id)) {
            TipoPermanencia::create($validatedData);
            session()->flash('success', 'Tipo de Permanencia creado exitosamente.');
        } else {
            $this->tipoPermanenciaActual->update($validatedData);
            session()->flash('success', 'Tipo de Permanencia actualizado exitosamente.');
        }
        $this->cerrarModal();
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->resetValidation();
        $this->nombre = '';
        $this->is_active = true;
        $this->tipoPermanenciaActual = new TipoPermanencia();
    }

    public function confirmarAlternarEstado(TipoPermanencia $tipoPermanencia)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $nuevoEstado = !$tipoPermanencia->is_active;
        $tipoPermanencia->update(['is_active' => $nuevoEstado]);
        session()->flash('success', 'Estado del tipo de permanencia actualizado exitosamente.');
    }
}
