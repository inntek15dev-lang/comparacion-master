<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SubTipoVehiculoMandante;
use App\Models\Mandante;
use App\Models\TipoVehiculo;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

#[Layout('layouts.app')]
#[Title('Gestión de Sub-Tipos de Vehículo por Mandante')]
class GestionSubTiposVehiculoMandante extends Component
{
    use WithPagination;

    public bool $mostrarModal = false;
    public ?SubTipoVehiculoMandante $subTipoActual;

    // Campos del formulario
    public $mandante_id = '';
    public $tipo_vehiculo_id = null;
    public string $nombre = '';
    public ?string $descripcion = null;
    public bool $is_active = true;

    // Para los selects en el modal
    public $mandantesDisponibles = [];
    public $tiposVehiculoDisponibles = [];

    // Filtros
    public string $filtroNombre = '';
    public string $filtroMandanteId = '';
    public string $filtroEstado = 'todos';

    protected function rules()
    {
        return [
            'mandante_id' => 'required|exists:mandantes,id',
            'tipo_vehiculo_id' => 'nullable|exists:tipos_vehiculo,id',
            'nombre' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('sub_tipos_vehiculo_mandante', 'nombre')
                    ->where(fn ($query) => $query->where('mandante_id', $this->mandante_id))
                    ->ignore($this->subTipoActual?->id),
            ],
            'descripcion' => 'nullable|string|max:1000',
            'is_active' => 'required|boolean',
        ];
    }

    protected $messages = [
        'mandante_id.required' => 'Debe seleccionar un mandante.',
        'nombre.required' => 'El nombre del sub-tipo es obligatorio.',
        'nombre.unique' => 'Ya existe un sub-tipo con este nombre para el mandante seleccionado.',
    ];

    public function mount()
    {
        if (!Auth::user() || !Auth::user()->hasRole('ASEM_Admin')) {
            abort(403, 'No tiene permisos para acceder a esta sección.');
        }
        $this->subTipoActual = new SubTipoVehiculoMandante();
        $this->mandantesDisponibles = Mandante::where('is_active', true)->orderBy('razon_social')->get();
        $this->tiposVehiculoDisponibles = TipoVehiculo::where('is_active', true)->orderBy('nombre')->get();
    }

    public function updatedFiltroNombre() { $this->resetPage(); }
    public function updatedFiltroMandanteId() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function render()
    {
        $query = SubTipoVehiculoMandante::with(['mandante', 'tipoVehiculo'])
                    ->orderBy('mandante_id', 'asc')
                    ->orderBy('nombre', 'asc');

        if (!empty($this->filtroNombre)) {
            $query->where('nombre', 'like', '%' . $this->filtroNombre . '%');
        }
        if (!empty($this->filtroMandanteId)) {
            $query->where('mandante_id', $this->filtroMandanteId);
        }
        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }

        $subTipos = $query->paginate(15);
        $todosLosMandantesParaFiltro = Mandante::orderBy('razon_social')->get();

        return view('livewire.gestion-sub-tipos-vehiculo-mandante', [
            'subTipos' => $subTipos,
            'todosLosMandantesParaFiltro' => $todosLosMandantesParaFiltro,
        ]);
    }

    private function resetInputFields()
    {
        $this->mandante_id = '';
        $this->tipo_vehiculo_id = null;
        $this->nombre = '';
        $this->descripcion = null;
        $this->is_active = true;
        $this->subTipoActual = new SubTipoVehiculoMandante();
        $this->resetValidation();
    }

    public function abrirModalParaCrear()
    {
        $this->resetInputFields();
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(SubTipoVehiculoMandante $subTipo)
    {
        $this->resetValidation();
        $this->subTipoActual = $subTipo;
        $this->mandante_id = $subTipo->mandante_id;
        $this->tipo_vehiculo_id = $subTipo->tipo_vehiculo_id;
        $this->nombre = $subTipo->nombre;
        $this->descripcion = $subTipo->descripcion;
        $this->is_active = $subTipo->is_active;
        $this->mostrarModal = true;
    }

    public function guardarSubTipo()
    {
        $validatedData = $this->validate();

        try {
            $this->subTipoActual->fill($validatedData);
            $this->subTipoActual->save();

            session()->flash('success', $this->subTipoActual->wasRecentlyCreated
                ? 'Sub-Tipo de Vehículo creado exitosamente.'
                : 'Sub-Tipo de Vehículo actualizado exitosamente.');
            $this->cerrarModal();
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1062) {
                session()->flash('error', 'Ya existe un sub-tipo con ese nombre para el mandante seleccionado.');
            } else {
                session()->flash('error', 'Error al guardar. Detalles: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->resetInputFields();
    }

    public function confirmarAlternarEstado(SubTipoVehiculoMandante $subTipo)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $subTipo->update(['is_active' => !$subTipo->is_active]);
        session()->flash('success', 'Estado actualizado exitosamente.');
    }
}
