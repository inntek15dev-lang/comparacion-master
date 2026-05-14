<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TipoContrato;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Gestión de Tipos de Contrato')]
class GestionTiposContrato extends Component
{
    use WithPagination;

    public bool $mostrarModal = false;
    public ?TipoContrato $tipoContratoActual;
    public string $nombre = '';
    public string $descripcion = '';
    public bool $is_active = true;

    public string $filtroNombre = '';
    public string $filtroEstado = 'todos';

    protected function rules()
    {
        $tipoContratoId = $this->tipoContratoActual?->id ?? 'NULL';
        return [
            'nombre' => "required|string|min:3|max:100|unique:tipos_contrato,nombre,{$tipoContratoId},id",
            'descripcion' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre del tipo de contrato es obligatorio.',
        'nombre.unique' => 'Este nombre de tipo de contrato ya existe.',
    ];

    public function updatedFiltroNombre() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function mount()
    {
        $this->tipoContratoActual = new TipoContrato();
    }

    public function render()
    {
        $query = TipoContrato::query();

        if (!empty($this->filtroNombre)) {
            $query->where('nombre', 'like', '%' . $this->filtroNombre . '%');
        }

        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }

        $tiposContrato = $query->orderBy('nombre', 'asc')->paginate(10);

        return view('livewire.gestion-tipos-contrato', [
            'tiposContrato' => $tiposContrato,
        ]);
    }

    public function abrirModalParaCrear()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->tipoContratoActual = new TipoContrato();
        $this->nombre = '';
        $this->descripcion = '';
        $this->is_active = true;
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(TipoContrato $tipoContrato)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->tipoContratoActual = $tipoContrato;
        $this->nombre = $tipoContrato->nombre;
        $this->descripcion = $tipoContrato->descripcion ?? '';
        $this->is_active = $tipoContrato->is_active;
        $this->mostrarModal = true;
    }

    public function guardarTipoContrato()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $validatedData = $this->validate();

        if (empty($this->tipoContratoActual->id)) {
            TipoContrato::create($validatedData);
            session()->flash('success', 'Tipo de contrato creado exitosamente.');
        } else {
            $this->tipoContratoActual->update($validatedData);
            session()->flash('success', 'Tipo de contrato actualizado exitosamente.');
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
        $this->tipoContratoActual = new TipoContrato();
    }

    public function confirmarAlternarEstado(TipoContrato $tipoContrato)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $nuevoEstado = !$tipoContrato->is_active;
        $tipoContrato->update(['is_active' => $nuevoEstado]);
        session()->flash('success', 'Estado del tipo de contrato actualizado exitosamente.');
    }
}
