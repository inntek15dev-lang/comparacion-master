<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ColorVehiculo; // Modelo para Colores de Vehículo
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.app')]
#[Title('Gestión de Colores de Vehículo')]
class GestionColoresVehiculo extends Component
{
    use WithPagination;

    public $nombre, $descripcion, $color_vehiculo_id, $is_active = true;
    public $isOpen = false;
    public $searchTerm = '';
    public $filterByStatus = '';

    protected function rules()
    {
        return [
            'nombre' => 'required|string|max:255|unique:colores_vehiculo,nombre' . ($this->color_vehiculo_id ? ',' . $this->color_vehiculo_id : ''),
            'descripcion' => 'nullable|string|max:65535',
            'is_active' => 'boolean'
        ];
    }

    protected $validationAttributes = [
        'nombre' => 'Nombre del Color de Vehículo',
        'descripcion' => 'Descripción',
        'is_active' => 'Estado'
    ];

    public function render()
    {
        $query = ColorVehiculo::query();

        if ($this->searchTerm) {
            $query->where('nombre', 'like', '%' . $this->searchTerm . '%');
        }

        if ($this->filterByStatus !== '') {
            $query->where('is_active', $this->filterByStatus === '1');
        }

        $coloresVehiculo = $query->orderBy('nombre', 'asc')->paginate(10);
        return view('livewire.gestion-colores-vehiculo', ['coloresVehiculo' => $coloresVehiculo]);
    }
    
    public function updatingSearchTerm()
    {
        $this->resetPage();
    }

    public function updatingFilterByStatus()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->resetErrorBag(); 
    }

    private function resetInputFields()
    {
        $this->nombre = '';
        $this->descripcion = '';
        $this->color_vehiculo_id = null;
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function store()
    {
        $this->validate($this->rules(), [], $this->validationAttributes);

        ColorVehiculo::updateOrCreate(['id' => $this->color_vehiculo_id], [
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'is_active' => $this->is_active,
        ]);

        session()->flash(
            'success',
            $this->color_vehiculo_id ? 'Color de Vehículo actualizado exitosamente.' : 'Color de Vehículo creado exitosamente.'
        );

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $colorVehiculo = ColorVehiculo::findOrFail($id);
        $this->color_vehiculo_id = $id;
        $this->nombre = $colorVehiculo->nombre;
        $this->descripcion = $colorVehiculo->descripcion;
        $this->is_active = $colorVehiculo->is_active;
        $this->openModal();
    }

    public function toggleStatus($id)
    {
        $colorVehiculo = ColorVehiculo::findOrFail($id);
        $colorVehiculo->is_active = !$colorVehiculo->is_active;
        $colorVehiculo->save();

        $status = $colorVehiculo->is_active ? 'activado' : 'desactivado';
        session()->flash('success', "Color de Vehículo {$status} exitosamente.");
    }
}