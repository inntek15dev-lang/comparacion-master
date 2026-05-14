<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TenenciaVehiculo; // Modelo actualizado
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.app')]
#[Title('Gestión de Tenencias de Vehículo')] // Título actualizado
class GestionTenenciasVehiculo extends Component // Nombre de clase actualizado
{
    use WithPagination;

    public $nombre, $descripcion, $tenencia_vehiculo_id, $is_active = true; // Variable ID actualizada
    public $isOpen = false;
    public $searchTerm = '';
    public $filterByStatus = '';

    protected function rules()
    {
        // Regla unique actualizada a la nueva tabla
        return [
            'nombre' => 'required|string|max:255|unique:tenencias_vehiculo,nombre' . ($this->tenencia_vehiculo_id ? ',' . $this->tenencia_vehiculo_id : ''),
            'descripcion' => 'nullable|string|max:65535',
            'is_active' => 'boolean'
        ];
    }

    protected $validationAttributes = [
        'nombre' => 'Nombre de la Tenencia de Vehículo', // Atributo de validación actualizado
        'descripcion' => 'Descripción',
        'is_active' => 'Estado'
    ];

    public function render()
    {
        $query = TenenciaVehiculo::query(); // Modelo actualizado

        if ($this->searchTerm) {
            $query->where('nombre', 'like', '%' . $this->searchTerm . '%');
        }

        if ($this->filterByStatus !== '') {
            $query->where('is_active', $this->filterByStatus === '1');
        }

        $tenenciasVehiculo = $query->orderBy('nombre', 'asc')->paginate(10); // Variable actualizada
        // Vista blade actualizada
        return view('livewire.gestion-tenencias-vehiculo', ['tenenciasVehiculo' => $tenenciasVehiculo]);
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
        $this->tenencia_vehiculo_id = null; // Variable ID actualizada
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function store()
    {
        $this->validate($this->rules(), [], $this->validationAttributes);

        TenenciaVehiculo::updateOrCreate(['id' => $this->tenencia_vehiculo_id], [ // Modelo y variable ID actualizados
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'is_active' => $this->is_active,
        ]);

        session()->flash(
            'success',
            // Mensaje actualizado
            $this->tenencia_vehiculo_id ? 'Tenencia de Vehículo actualizada exitosamente.' : 'Tenencia de Vehículo creada exitosamente.'
        );

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $tenenciaVehiculo = TenenciaVehiculo::findOrFail($id); // Modelo actualizado
        $this->tenencia_vehiculo_id = $id; // Variable ID actualizada
        $this->nombre = $tenenciaVehiculo->nombre;
        $this->descripcion = $tenenciaVehiculo->descripcion;
        $this->is_active = $tenenciaVehiculo->is_active;
        $this->openModal();
    }

    public function toggleStatus($id)
    {
        $tenenciaVehiculo = TenenciaVehiculo::findOrFail($id); // Modelo actualizado
        $tenenciaVehiculo->is_active = !$tenenciaVehiculo->is_active;
        $tenenciaVehiculo->save();

        $status = $tenenciaVehiculo->is_active ? 'activada' : 'desactivada';
        // Mensaje actualizado
        session()->flash('success', "Tenencia de Vehículo {$status} exitosamente.");
    }
}