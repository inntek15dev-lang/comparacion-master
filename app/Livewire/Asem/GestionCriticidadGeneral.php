<?php

namespace App\Livewire\Asem;

use Livewire\Component;
use App\Models\Mandante;
use App\Models\NombreDocumento;
use App\Models\TipoEntidadControlable;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\DocumentoConfiguracionCriticidad;
use Illuminate\Support\Facades\Log;
use App\Jobs\ActualizarEstadoCumplimientoEnMasa; // NUEVO

class GestionCriticidadGeneral extends Component
{
    public array $mandantes = [];
    public ?int $mandante_id = null;

    public array $unidadesOrganizacionales = [];
    public ?string $unidadOrganizacionalIdFiltro = ''; // Usar string para el valor ""

    public array $tiposEntidad = [];
    public ?string $tipoEntidadIdFiltro = ''; // Usar string para el valor ""

    public array $documentosAgrupados = [];
    public array $configuraciones = [];

    public function mount()
    {
        $this->mandantes = Mandante::where('is_active', true)->orderBy('razon_social')->get()->toArray();
        if (count($this->mandantes) > 0) {
            $this->mandante_id = $this->mandantes[0]['id'] ?? null;
            $this->cargarFiltrosYConfiguraciones();
        }
    }

    public function cargarFiltrosYConfiguraciones()
    {
        $this->unidadesOrganizacionales = [];
        $this->tiposEntidad = [];
        $this->unidadOrganizacionalIdFiltro = '';
        $this->tipoEntidadIdFiltro = '';

        if ($this->mandante_id) {
            $this->unidadesOrganizacionales = UnidadOrganizacionalMandante::where('mandante_id', $this->mandante_id)
                ->where('is_active', true)->orderBy('nombre_unidad')->get(['id', 'nombre_unidad'])->toArray();
                
            $this->tiposEntidad = TipoEntidadControlable::where('is_active', true)
                ->whereHas('reglasDocumentales', function ($query) {
                    $query->where('mandante_id', $this->mandante_id);
                })
                ->distinct()->get(['id', 'nombre_entidad'])->toArray();
        }
        
        $this->cargarConfiguraciones();
    }
    
    public function updatedMandanteId() { $this->cargarFiltrosYConfiguraciones(); }
    public function updatedUnidadOrganizacionalIdFiltro() { $this->cargarConfiguraciones(); }
    public function updatedTipoEntidadIdFiltro() { $this->cargarConfiguraciones(); }

    public function cargarConfiguraciones()
    {
        if (!$this->mandante_id) {
            $this->documentosAgrupados = [];
            $this->configuraciones = [];
            return;
        }

        $reglasQuery = \App\Models\ReglaDocumental::query()
            ->where('mandante_id', $this->mandante_id);

        if ($this->unidadOrganizacionalIdFiltro) {
            $reglasQuery->whereHas('unidadesOrganizacionales', function ($query) {
                $query->where('unidades_organizacionales_mandante.id', $this->unidadOrganizacionalIdFiltro);
            });
        }

        if ($this->tipoEntidadIdFiltro) {
            $reglasQuery->where('tipo_entidad_controlada_id', $this->tipoEntidadIdFiltro);
        }

        $documentoIds = $reglasQuery->select('nombre_documento_id')->distinct()->pluck('nombre_documento_id');
        
        $documentos = NombreDocumento::whereIn('id', $documentoIds)
            ->with('tipoEntidadControlable:id,nombre_entidad')
            ->orderBy('aplica_a')
            ->orderBy('nombre')
            ->get();
            
        $this->documentosAgrupados = $documentos
            ->groupBy('tipoEntidadControlable.nombre_entidad')
            ->map(fn ($items) => $items->toArray())
            ->toArray();
        
        $configs = DocumentoConfiguracionCriticidad::where('mandante_id', $this->mandante_id)
            ->whereIn('nombre_documento_id', $documentos->pluck('id'))
            ->get()
            ->keyBy('nombre_documento_id');
            
        $this->configuraciones = [];
        foreach($documentos as $doc) {
            $config = $configs->get($doc->id);
            $this->configuraciones[$doc->id] = [
                'afecta_cumplimiento' => $config->afecta_cumplimiento ?? false,
                'restringe_acceso' => $config->restringe_acceso ?? false,
                'es_perseguidor' => $config->es_perseguidor ?? false,
            ];
        }
    }

    public function actualizarCriticidad($nombreDocumentoId, $campo)
    {
        if (!$this->mandante_id || !isset($this->configuraciones[$nombreDocumentoId])) {
            return;
        }
        $this->configuraciones[$nombreDocumentoId][$campo] = !$this->configuraciones[$nombreDocumentoId][$campo];
        try {
            DocumentoConfiguracionCriticidad::updateOrCreate(
                ['mandante_id' => $this->mandante_id, 'nombre_documento_id' => $nombreDocumentoId,],
                [$campo => $this->configuraciones[$nombreDocumentoId][$campo]]
            );
            
            // ================== INICIO DEL DETONANTE ==================
            ActualizarEstadoCumplimientoEnMasa::dispatch($this->mandante_id);
            // ================== FIN DEL DETONANTE ====================

            $this->dispatch('notificacion-exito', 'Configuración actualizada. La re-evaluación de los recursos se está procesando en segundo plano.');
            $this->dispatch('criticidad-actualizada');

        } catch (\Exception $e) {
            Log::error("Error al actualizar criticidad: " . $e->getMessage());
            $this->dispatch('notificacion-error', 'Error al actualizar.');
            $this->cargarFiltrosYConfiguraciones();
        }
    }

    public function render()
    {
        return view('livewire.asem.gestion-criticidad-general')->layout('layouts.app');
    }
}