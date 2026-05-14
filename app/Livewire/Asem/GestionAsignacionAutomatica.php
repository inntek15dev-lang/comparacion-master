<?php

namespace App\Livewire\Asem;

use Livewire\Component;
use App\Models\Mandante;
use App\Models\User;
use App\Models\ConfiguracionAsignacionAutomatica;
use Illuminate\Support\Facades\DB;

class GestionAsignacionAutomatica extends Component
{
    public bool $showModal = false;
    public ?Mandante $selectedMandante = null;
    public array $selectedValidators = [];

    public function render()
    {
        $mandantes = Mandante::where('is_active', true)
            ->with(['configuracionAsignacion.validadores' => function ($query) {
                $query->select('users.id', 'users.name');
            }])
            ->orderBy('razon_social')
            ->get();

        return view('livewire.asem.gestion-asignacion-automatica', [
            'mandantes' => $mandantes,
        ]);
    }

    public function abrirModal(int $mandanteId)
    {
        $this->selectedMandante = Mandante::find($mandanteId);
        if (!$this->selectedMandante) return;

        $config = $this->selectedMandante->configuracionAsignacion()->first();
        $this->selectedValidators = $config ? $config->validadores()->pluck('users.id')->toArray() : [];

        $this->showModal = true;
    }

    public function guardarConfiguracion()
    {
        if (!$this->selectedMandante) return;

        $this->validate([
            'selectedValidators' => 'array|max:5',
            'selectedValidators.*' => 'exists:users,id',
        ], [
            'selectedValidators.max' => 'No se pueden asignar más de 5 validadores.'
        ]);

        DB::transaction(function () {
            $config = ConfiguracionAsignacionAutomatica::firstOrCreate(
                ['mandante_id' => $this->selectedMandante->id]
            );

            $syncData = [];
            foreach ($this->selectedValidators as $index => $validatorId) {
                $syncData[$validatorId] = ['orden' => $index + 1];
            }

            $config->validadores()->sync($syncData);
        });

        $this->showModal = false;
        session()->flash('status', 'Configuración de asignación guardada exitosamente.');
    }

    public function toggleActivo(int $configId)
    {
        $config = ConfiguracionAsignacionAutomatica::find($configId);
        if ($config) {
            $config->update(['is_active' => !$config->is_active]);
        }
    }

    public function getValidadoresDisponiblesProperty()
    {
        return User::role('ASEM_Validator')->where('is_active', true)->orderBy('name')->get();
    }
}