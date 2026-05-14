<?php

namespace App\Livewire\Asem\Supervision;

use Livewire\Component;
use App\Models\Vehiculo;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Services\DocumentoRequeridoService;
use App\Models\ReglaDocumental;
use App\Models\TipoEntidadControlable;
use App\Models\UnidadOrganizacionalMandante;

class TablaVehiculosGlobal extends Component
{
    use WithPagination;

    public $contratistaId;
    public $mandanteId;
    public $lugarDeTrabajoId;
    public $uoId;
    public $search = '';
    public bool $esSoloLectura = false;

    public array $documentosMaestros = [];
    private ?DocumentoRequeridoService $documentoService;

    public function boot(DocumentoRequeridoService $documentoService)
    {
        $this->documentoService = $documentoService;
    }

    public function mount()
    {
        $this->cargarDocumentosMaestros();
    }

    public function updatingSearch()
    {
        $this->resetPage('vehiculosPage');
    }

    private function cargarDocumentosMaestros()
    {
        $this->documentosMaestros = [];
        if (!$this->mandanteId || !$this->uoId) {
            return;
        }

        $tipoEntidad = TipoEntidadControlable::where('nombre_entidad', 'VEHICULO')->first();
        if (!$tipoEntidad) return;

        $uoActual = UnidadOrganizacionalMandante::find($this->uoId);
        $idsUoAplicables = [$this->uoId];
        if ($uoActual) {
            $parentId = $uoActual->parent_id;
            while ($parentId) {
                $idsUoAplicables[] = $parentId;
                $ancestro = UnidadOrganizacionalMandante::find($parentId);
                $parentId = $ancestro ? $ancestro->parent_id : null;
            }
        }

        $reglas = ReglaDocumental::query()
            ->where('is_active', true)
            ->where('mandante_id', $this->mandanteId)
            ->where('tipo_entidad_controlada_id', $tipoEntidad->id)
            ->where(function ($query) use ($idsUoAplicables) {
                $query->whereHas('unidadesOrganizacionales', function ($subQuery) use ($idsUoAplicables) {
                    $subQuery->whereIn('unidad_organizacional_mandante_id', $idsUoAplicables);
                })
                ->orWhereDoesntHave('unidadesOrganizacionales');
            })
            ->with('nombreDocumento')
            ->get();

        $documentosUnicos = $reglas->unique('nombre_documento_id')->sortBy('nombreDocumento.nombre');

        $contador = 1;
        foreach ($documentosUnicos as $regla) {
            if ($regla->nombreDocumento) {
                $this->documentosMaestros[] = [
                    'numero' => $contador,
                    'nombre_documento_id' => $regla->nombre_documento_id,
                ];
                $contador++;
            }
        }
    }

    public function render()
    {
        $vehiculosQuery = Vehiculo::where('contratista_id', $this->contratistaId)
            ->whereHas('vinculaciones', function ($query) {
                $query->where('dependencia_id', $this->lugarDeTrabajoId)
                      ->where('unidad_organizacional_mandante_id', $this->uoId);
            })
            ->where(function ($query) {
                $searchTerm = str_replace(['-', ' ', '•'], '', $this->search);
                $query->where(DB::raw("REPLACE(CONCAT(patente_letras, patente_numeros), ' ', '')"), 'like', '%' . $searchTerm . '%');
            })
            ->with([
                'anulacionManualActiva' => function ($q) {
                    $q->where('mandante_id', $this->mandanteId);
                }
            ]);

        $vehiculos = $vehiculosQuery->paginate(100, ['*'], 'vehiculosPage');

        if (!empty($this->documentosMaestros)) {
            $vehiculos->getCollection()->transform(function ($vehiculo) {
                $estados = $this->documentoService->obtenerEstadoDocumentosParaEntidad(
                    $vehiculo, 
                    $this->mandanteId, 
                    $this->uoId
                );
                $vehiculo->estadosDocumentos = collect($estados)->mapWithKeys(fn($item) => [$item['nombre_documento_id'] => $item['estado_actual_documento']]);
                return $vehiculo;
            });
        }

        return view('livewire.asem.supervision.tabla-vehiculos-global', [
            'vehiculos' => $vehiculos,
        ]);
    }
}