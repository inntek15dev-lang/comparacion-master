<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\MarcaVehiculo; // Modelo para Marcas de Vehículo
use Livewire\WithPagination;
use Livewire\Attributes\Layout; // Para especificar el layout
use Livewire\Attributes\Title; // Para el título de la página

#[Layout('layouts.app')] // Usando el layout principal de Breeze
#[Title('Gestión de Marcas de Vehículo')] // Título de la página
class GestionMarcasVehiculo extends Component
{
    use WithPagination;

    public $nombre, $descripcion, $marca_vehiculo_id, $is_active = true;
    public $isOpen = false;
    public $searchTerm = '';
    public $filterByStatus = ''; // Todos, '1' para Activos, '0' para Inactivos

    protected function rules()
    {
        return [
            'nombre' => 'required|string|max:255|unique:marcas_vehiculo,nombre' . ($this->marca_vehiculo_id ? ',' . $this->marca_vehiculo_id : ''),
            'descripcion' => 'nullable|string|max:65535',
            'is_active' => 'boolean'
        ];
    }

    protected $validationAttributes = [
        'nombre' => 'Nombre de la Marca de Vehículo',
        'descripcion' => 'Descripción',
        'is_active' => 'Estado'
    ];

    public function render()
    {
        $query = MarcaVehiculo::query();

        if ($this->searchTerm) {
            $query->where('nombre', 'like', '%' . $this->searchTerm . '%');
        }

        if ($this->filterByStatus !== '') {
            $query->where('is_active', $this->filterByStatus === '1');
        }

        $marcasVehiculo = $query->orderBy('nombre', 'asc')->paginate(10);
        return view('livewire.gestion-marcas-vehiculo', ['marcasVehiculo' => $marcasVehiculo]);
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
        $this->marca_vehiculo_id = null;
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function store()
    {
        $this->validate($this->rules(), [], $this->validationAttributes);

        MarcaVehiculo::updateOrCreate(['id' => $this->marca_vehiculo_id], [
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'is_active' => $this->is_active,
        ]);

        session()->flash(
            'success',
            $this->marca_vehiculo_id ? 'Marca de Vehículo actualizada exitosamente.' : 'Marca de Vehículo creada exitosamente.'
        );

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $marcaVehiculo = MarcaVehiculo::findOrFail($id);
        $this->marca_vehiculo_id = $id;
        $this->nombre = $marcaVehiculo->nombre;
        $this->descripcion = $marcaVehiculo->descripcion;
        $this->is_active = $marcaVehiculo->is_active;
        $this->openModal();
    }

    public function toggleStatus($id)
    {
        $marcaVehiculo = MarcaVehiculo::findOrFail($id);
        $marcaVehiculo->is_active = !$marcaVehiculo->is_active;
        $marcaVehiculo->save();

        $status = $marcaVehiculo->is_active ? 'activada' : 'desactivada';
        session()->flash('success', "Marca de Vehículo {$status} exitosamente.");
    }
}