<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Dependencia;
use App\Models\Mandante;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

#[Layout('layouts.app')]
#[Title('Gestión de Dependencias')]
class GestionDependencias extends Component
{
    use WithPagination;

    public bool $mostrarModal = false;
    public ?Dependencia $dependenciaActual;
    
    // Campos del formulario
    public $mandante_id = '';
    public string $nombre = '';
    public $dependencia_padre_id = null;
    public bool $estado = true;

    // Para los selects
    public $mandantes = [];
    public $dependenciasPadreDisponibles = [];

    // Filtros
    public string $filtroNombre = '';
    public string $filtroMandanteId = '';
    public string $filtroEstado = 'todos'; // todos, activos, inactivos

    protected function rules()
    {
        return [
            'mandante_id' => 'required|exists:mandantes,id',
            'nombre' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('dependencias', 'nombre')
                    ->where(fn ($query) => $query->where('mandante_id', $this->mandante_id)
                                                ->where('dependencia_padre_id', $this->dependencia_padre_id === '' ? null : $this->dependencia_padre_id))
                    ->ignore($this->dependenciaActual?->id),
            ],
            'dependencia_padre_id' => [
                'nullable',
                'exists:dependencias,id',
                Rule::notIn([$this->dependenciaActual?->id]),
                Rule::exists('dependencias', 'id')->where(function ($query) {
                    $query->where('mandante_id', $this->mandante_id);
                }),
            ],
            'estado' => 'required|boolean',
        ];
    }

    protected $messages = [
        'mandante_id.required' => 'Debe seleccionar un mandante.',
        'mandante_id.exists' => 'El mandante seleccionado no es válido.',
        'nombre.required' => 'El nombre de la dependencia es obligatorio.',
        'nombre.unique' => 'Ya existe una dependencia con este nombre para el mandante y dependencia padre seleccionados.',
        'dependencia_padre_id.exists' => 'La dependencia padre seleccionada no es válida.',
        'dependencia_padre_id.not_in' => 'Una dependencia no puede ser su propio padre.',
    ];

    public function mount()
    {
        if (!Auth::user() || !Auth::user()->hasRole('ASEM_Admin')) {
            abort(403, 'No tiene permisos para acceder a esta sección.');
        }
        $this->dependenciaActual = new Dependencia();
        $this->mandantes = Mandante::where('is_active', true)->orderBy('razon_social')->get();
    }
    
    public function updatedFiltroNombre() { $this->resetPage(); }
    public function updatedFiltroMandanteId() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function updatedMandanteId($value)
    {
        if (!empty($value)) {
            $query = Dependencia::where('mandante_id', $value)
                                ->where('estado', true)
                                ->orderBy('nombre');
            
            if ($this->dependenciaActual && $this->dependenciaActual->id) {
                $query->where('id', '!=', $this->dependenciaActual->id);
            }
            $this->dependenciasPadreDisponibles = $query->get();
        } else {
            $this->dependenciasPadreDisponibles = [];
        }
        
        if ($this->dependencia_padre_id) {
            $parentExistsInNewMandante = collect($this->dependenciasPadreDisponibles)->contains('id', $this->dependencia_padre_id);
            if (!$parentExistsInNewMandante) {
                $this->dependencia_padre_id = null;
            }
        }
    }

    public function render()
    {
        $query = Dependencia::with(['mandante', 'parent'])
                    ->orderBy('mandante_id', 'asc')
                    ->orderBy('nombre', 'asc');

        if (!empty($this->filtroNombre)) {
            $query->where('nombre', 'like', '%' . $this->filtroNombre . '%');
        }
        if (!empty($this->filtroMandanteId)) {
            $query->where('mandante_id', $this->filtroMandanteId);
        }
        if ($this->filtroEstado === 'activos') {
            $query->where('estado', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('estado', false);
        }

        $dependencias = $query->paginate(10);

        return view('livewire.gestion-dependencias', [
            'dependencias' => $dependencias,
            'todosLosMandantes' => Mandante::orderBy('razon_social')->get(),
        ]);
    }

    private function resetInputFields()
    {
        $this->mandante_id = '';
        $this->nombre = '';
        $this->dependencia_padre_id = null;
        $this->estado = true;
        $this->dependenciaActual = new Dependencia();
        $this->dependenciasPadreDisponibles = [];
        $this->resetValidation();
    }

    public function abrirModalParaCrear()
    {
        $this->resetInputFields();
        $this->mandantes = Mandante::where('is_active', true)->orderBy('razon_social')->get();
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(Dependencia $dependencia)
    {
        $this->resetValidation();
        $this->dependenciaActual = $dependencia;
        $this->mandante_id = $dependencia->mandante_id;
        $this->nombre = $dependencia->nombre;
        $this->dependencia_padre_id = $dependencia->dependencia_padre_id ?? null;
        $this->estado = $dependencia->estado;
        
        $this->mandantes = Mandante::where('is_active', true)->orderBy('razon_social')->get();

        $this->updatedMandanteId($this->mandante_id); 

        $this->mostrarModal = true;
    }

    public function guardarDependencia()
    {
        if ($this->dependencia_padre_id === '') {
            $this->dependencia_padre_id = null;
        }
        
        $validatedData = $this->validate();

        try {
            $this->dependenciaActual->fill($validatedData);
            $this->dependenciaActual->dependencia_padre_id = $validatedData['dependencia_padre_id'];
            $this->dependenciaActual->save();

            session()->flash('success', $this->dependenciaActual->wasRecentlyCreated ? 'Dependencia creada exitosamente.' : 'Dependencia actualizada exitosamente.');
            $this->cerrarModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->resetInputFields();
    }

    public function confirmarAlternarEstado(Dependencia $dependencia)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $nuevoEstado = !$dependencia->estado;
        $dependencia->update(['estado' => $nuevoEstado]);
        session()->flash('success', 'Estado de la Dependencia actualizado exitosamente.');
    }
}