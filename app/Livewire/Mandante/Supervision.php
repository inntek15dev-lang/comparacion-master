<?php

namespace App\Livewire\Mandante;

use App\Livewire\Asem\SupervisionGlobal as AsemSupervisionGlobal;
use Illuminate\Support\Facades\Auth;
use App\Models\Mandante;
use Livewire\Attributes\Layout;
use Illuminate\Support\Str;

#[Layout('layouts.app')]
class Supervision extends AsemSupervisionGlobal
{
    // Propiedad para controlar la visibilidad de columnas
    public array $entidadesHabilitadas = [
        'trabajadores' => true,
        'vehiculos' => true,
        'maquinarias' => true,
        'embarcaciones' => true,
    ];

    // Nuevos filtros para N° Contrato y Tipo Contrato
    public string $filtroNumeroContrato = '';
    public $filtroTipoContratoId = 'todos';
    public $tiposContratoDisponibles = [];

    public function mount()
    {
        $user = Auth::user();
        
        if ($user && $user->mandante_id) {
            $this->filtroMandanteId = $user->mandante_id;
            
            // Cargamos el mandante con sus tipos de entidad controlable
            $mandante = Mandante::with('tiposEntidadControlable')->find($user->mandante_id);
            $this->mandantesDisponibles = collect([$mandante]);

            // Lógica para determinar qué columnas mostrar según configuración del Mandante
            if ($mandante && $mandante->tiposEntidadControlable->isNotEmpty()) {
                // Normalizamos los nombres a minúsculas y sin tildes para comparar
                $tipos = $mandante->tiposEntidadControlable->map(function($tipo) {
                    return Str::slug($tipo->nombre_entidad); // ej: "vehiculo", "maquinaria", "persona"
                })->toArray();

                $this->entidadesHabilitadas = [
                    // 'persona' es el nombre técnico en BD para Trabajadores
                    'trabajadores' => in_array('persona', $tipos) || in_array('trabajador', $tipos),
                    'vehiculos' => in_array('vehiculo', $tipos),
                    'maquinarias' => in_array('maquinaria', $tipos),
                    'embarcaciones' => in_array('embarcacion', $tipos),
                ];
            }
        } else {
            $this->filtroMandanteId = null;
            $this->mandantesDisponibles = collect();
        }

        $this->actualizarLugaresTrabajo();
        $this->contratistasConPromedios = [];
        $this->fechaCache = 'No disponible (se requiere cálculo inicial)';
        $this->inicializarTotales();

        // Cargar tipos de contrato disponibles
        $this->tiposContratoDisponibles = \App\Models\TipoContrato::where('is_active', true)->orderBy('nombre')->get();
    }

    public function render()
    {
        $datosFiltrados = collect($this->contratistasConPromedios)->filter(function ($item) {
            // Filtro de búsqueda por razón social o RUT
            if (!empty($this->search)) {
                $matchSearch = str_contains(strtolower($item['razon_social']), strtolower($this->search)) ||
                       str_contains(str_replace(['.', '-'], '', $item['rut']), str_replace(['.', '-'], '', $this->search));
                if (!$matchSearch) return false;
            }
            
            // Filtro por número de contrato
            if (!empty($this->filtroNumeroContrato)) {
                $numContrato = $item['numero_contrato'] ?? '';
                if (!str_contains(strtolower($numContrato), strtolower($this->filtroNumeroContrato))) {
                    return false;
                }
            }

            // Filtro por tipo de contrato
            if ($this->filtroTipoContratoId !== 'todos') {
                $tipoContratoId = $item['tipo_contrato_id'] ?? null;
                if ($tipoContratoId != $this->filtroTipoContratoId) {
                    return false;
                }
            }

            return true;
        });

        $datosOrdenados = $datosFiltrados->sortBy('razon_social');
        $contratistasAgrupados = $datosOrdenados->groupBy('contratista_id');

        return view('livewire.mandante.supervision', [
            'contratistasAgrupados' => $contratistasAgrupados
        ]);
    }
}