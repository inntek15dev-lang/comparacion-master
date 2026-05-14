<?php

namespace App\Livewire\Asem;

use Livewire\Component;
use App\Models\Mandante;
use App\Models\Contratista;
use App\Models\Trabajador;
use App\Models\Vehiculo;
use App\Models\Maquinaria;
use App\Models\Embarcacion;
use App\Models\NombreDocumento;
use App\Models\DocumentoConfiguracionCriticidad;
use App\Models\DocumentoExcepcionCriticidad;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\TipoEntidadControlable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GestionCriticidadExcepciones extends Component
{
    // Filtros
    public array $mandantes = [];
    public ?int $mandanteId = null;

    public array $contratistas = [];
    public ?int $contratistaId = null;

    public array $unidadesOrganizacionales = [];
    public ?int $unidadOrganizacionalId = null;
    
    public array $tiposEntidad = [];
    public ?string $tipoEntidadId = '';
    
    public array $activos = [];
    public ?string $activoId = '';

    // Estado
    public array $documentosConCriticidad = [];

    public function mount()
    {
        $this->mandantes = Mandante::where('is_active', true)->orderBy('razon_social')->get(['id', 'razon_social'])->toArray();
    }
    
    // --- Flujo de actualización de filtros ---
    
    public function updatedMandanteId($value)
    {
        $this->reset(['contratistaId', 'unidadOrganizacionalId', 'tipoEntidadId', 'activoId', 'contratistas', 'unidadesOrganizacionales', 'tiposEntidad', 'activos', 'documentosConCriticidad']);
        if ($value) {
            $this->contratistas = Contratista::whereHas('unidadesOrganizacionalesMandante.mandante', fn($q) => $q->where('id', '!=', 99999)->where('id', $value))
                ->where('is_active', true)->orderBy('razon_social')->get(['id', 'razon_social'])->toArray();
        }
    }
    
    public function updatedContratistaId($value)
    {
        $this->reset(['unidadOrganizacionalId', 'tipoEntidadId', 'activoId', 'unidadesOrganizacionales', 'tiposEntidad', 'activos', 'documentosConCriticidad']);
        if ($value && $this->mandanteId) {
            $this->unidadesOrganizacionales = UnidadOrganizacionalMandante::where('mandante_id', $this->mandanteId)
                ->whereHas('contratistasHabilitados', fn($q) => $q->where('contratistas.id', $value))
                ->where('is_active', true)->orderBy('nombre_unidad')->get(['id', 'nombre_unidad'])->toArray();
        }
    }

    public function updatedUnidadOrganizacionalId($value)
    {
        $this->reset(['tipoEntidadId', 'activoId', 'tiposEntidad', 'activos']);
        $this->cargarFiltrosSecundarios();
        $this->cargarDocumentosYExcepciones();
    }

    public function updatedTipoEntidadId() 
    {
        $this->reset(['activoId', 'activos']);
        $this->cargarFiltrosSecundarios();
        $this->cargarDocumentosYExcepciones(); 
    }
    
    public function updatedActivoId() 
    {
        $this->cargarDocumentosYExcepciones(); 
    }

    public function cargarFiltrosSecundarios()
    {
        if ($this->unidadOrganizacionalId) {
            $query = TipoEntidadControlable::query()->where('is_active', true);
            $query->whereHas('reglasDocumentales', fn($q) => $q->where('mandante_id', $this->mandanteId)->whereHas('unidadesOrganizacionales', fn($sub) => $sub->where('unidad_organizacional_mandante_id', $this->unidadOrganizacionalId)));
            $this->tiposEntidad = $query->distinct()->get(['id', 'nombre_entidad'])->toArray();
            array_unshift($this->tiposEntidad, ['id' => 'empresa', 'nombre_entidad' => 'EMPRESA']);
        }
        
        if ($this->tipoEntidadId && $this->contratistaId) {
            $this->activos = $this->getActivosPorTipo($this->tipoEntidadId, $this->contratistaId);
        }
    }

    protected function getActivosPorTipo($tipoEntidadId, $contratistaId): array
    {
        if (empty($tipoEntidadId) || empty($contratistaId)) return [];
        if ($tipoEntidadId === 'empresa') {
            return [['id' => $contratistaId, 'nombre' => 'Ficha Empresa']];
        }
        $nombreEntidad = TipoEntidadControlable::find((int)$tipoEntidadId)?->nombre_entidad;
        if(!$nombreEntidad) return [];
        switch(strtoupper($nombreEntidad)) {
            case 'PERSONA': return Trabajador::where('contratista_id', $contratistaId)->where('is_active', true)->get()->map(fn($i) => ['id' => $i->id, 'nombre' => $i->nombre_completo])->toArray();
            case 'VEHICULO': return Vehiculo::where('contratista_id', $contratistaId)->where('is_active', true)->get()->map(fn($i) => ['id' => $i->id, 'nombre' => $i->patente_completa])->toArray();
        }
        return [];
    }
    
    protected function getEntidadModel()
    {
        // =========================================================================================
        // INICIO: REFORJA DE LA LÓGICA DE MANDO
        // Se modifica la lógica para que, si no hay un activo específico, la entidad
        // sobre la que se aplica la excepción sea el Contratista.
        // =========================================================================================
        if (empty($this->contratistaId) || empty($this->tipoEntidadId)) {
            return null;
        }

        if (!empty($this->activoId)) {
            if ($this->tipoEntidadId === 'empresa') {
                 return Contratista::find($this->contratistaId);
            }
            $nombreEntidad = TipoEntidadControlable::find((int)$this->tipoEntidadId)?->nombre_entidad;
            if (!$nombreEntidad) return null;
            switch(strtoupper($nombreEntidad)) {
                case 'PERSONA': return Trabajador::find($this->activoId);
                case 'VEHICULO': return Vehiculo::find($this->activoId);
            }
        } else {
            // Si no hay activo específico, la excepción es a nivel de Contratista.
            return Contratista::find($this->contratistaId);
        }

        return null;
        // =========================================================================================
        // FIN: REFORJA DE LA LÓGICA DE MANDO
        // =========================================================================================
    }

    public function cargarDocumentosYExcepciones()
    {
        if (!$this->mandanteId || !$this->contratistaId || !$this->unidadOrganizacionalId) {
            $this->documentosConCriticidad = []; return;
        }
        $reglasQuery = \App\Models\ReglaDocumental::with('nombreDocumento')->where('mandante_id', $this->mandanteId)->whereHas('unidadesOrganizacionales', fn($q) => $q->where('unidad_organizacional_mandante_id', $this->unidadOrganizacionalId));
        if ($this->tipoEntidadId) {
            $idTipo = ($this->tipoEntidadId === 'empresa') ? TipoEntidadControlable::where('nombre_entidad', 'EMPRESA')->value('id') : $this->tipoEntidadId;
            $reglasQuery->where('tipo_entidad_controlada_id', $idTipo);
        }
        $documentosAplicables = $reglasQuery->get()->pluck('nombreDocumento')->unique('id')->filter();
        $entidadModel = $this->getEntidadModel();
        $this->documentosConCriticidad = [];
        foreach ($documentosAplicables as $doc) {
            if(!$doc) continue;
            $configGeneral = DocumentoConfiguracionCriticidad::where('mandante_id', $this->mandanteId)->where('nombre_documento_id', $doc->id)->first();
            $excepcion = null;
            if ($entidadModel) {
                 $excepcion = DocumentoExcepcionCriticidad::where('mandante_id', $this->mandanteId)
                    ->where('nombre_documento_id', $doc->id)->where('excepcionable_type', get_class($entidadModel))->where('excepcionable_id', $entidadModel->id)
                    ->first();
            }

            $afecta_override = is_null($excepcion?->afecta_cumplimiento_override) ? '' : ($excepcion->afecta_cumplimiento_override ? '1' : '0');
            $restringe_override = is_null($excepcion?->restringe_acceso_override) ? '' : ($excepcion->restringe_acceso_override ? '1' : '0');
            $perseguidor_override = is_null($excepcion?->es_perseguidor_override) ? '' : ($excepcion->es_perseguidor_override ? '1' : '0');

            $this->documentosConCriticidad[$doc->id] = [
                'nombre_documento' => $doc->nombre,
                'config_general' => ['afecta_cumplimiento' => $configGeneral?->afecta_cumplimiento ?? false, 'restringe_acceso' => $configGeneral?->restringe_acceso ?? false, 'es_perseguidor' => $configGeneral?->es_perseguidor ?? false],
                'excepcion' => ['afecta_cumplimiento_override' => $afecta_override, 'restringe_acceso_override' => $restringe_override, 'es_perseguidor_override' => $perseguidor_override],
                'valido_hasta' => $excepcion ? Carbon::parse($excepcion->valido_hasta)->format('Y-m-d') : null,
                'tiene_excepcion_activa' => !is_null($excepcion),
            ];
        }
    }
    
    public function guardarExcepcion($nombreDocumentoId) {
        $entidad = $this->getEntidadModel();
        if (!$entidad) { $this->dispatch('notificacion-error', 'Debe seleccionar un Contratista y Tipo de Entidad para crear una excepción.'); return; }
        $datosExcepcion = $this->documentosConCriticidad[$nombreDocumentoId] ?? null;
        if (!$datosExcepcion) return;
        if (empty($datosExcepcion['valido_hasta'])) { $this->addError("documentosConCriticidad.{$nombreDocumentoId}.valido_hasta", 'La fecha es obligatoria.'); return; }
        try { $fechaValidaHasta = Carbon::parse($datosExcepcion['valido_hasta']); } catch (\Exception $e) { $this->addError("documentosConCriticidad.{$nombreDocumentoId}.valido_hasta", 'Formato de fecha no válido.'); return; }
        if ($fechaValidaHasta->isPast() && !$fechaValidaHasta->isToday()) { $this->addError("documentosConCriticidad.{$nombreDocumentoId}.valido_hasta", 'La fecha no puede ser pasada.'); return; }
        $hayOverride = ($datosExcepcion['excepcion']['afecta_cumplimiento_override'] !== '') || ($datosExcepcion['excepcion']['restringe_acceso_override'] !== '') || ($datosExcepcion['excepcion']['es_perseguidor_override'] !== '');
        if (!$hayOverride) { $this->addError("documentosConCriticidad.{$nombreDocumentoId}.valido_hasta", 'Debe definir al menos una excepción (Sí/No).'); return; }
        try {
            $valor_afecta = $datosExcepcion['excepcion']['afecta_cumplimiento_override'] === '' ? null : (bool)$datosExcepcion['excepcion']['afecta_cumplimiento_override'];
            $valor_restringe = $datosExcepcion['excepcion']['restringe_acceso_override'] === '' ? null : (bool)$datosExcepcion['excepcion']['restringe_acceso_override'];
            $valor_perseguidor = $datosExcepcion['excepcion']['es_perseguidor_override'] === '' ? null : (bool)$datosExcepcion['excepcion']['es_perseguidor_override'];
            DocumentoExcepcionCriticidad::updateOrCreate(
                [ 'mandante_id' => $this->mandanteId, 'nombre_documento_id' => $nombreDocumentoId, 'excepcionable_type' => get_class($entidad), 'excepcionable_id' => $entidad->id ],
                [ 'afecta_cumplimiento_override' => $valor_afecta, 'restringe_acceso_override' => $valor_restringe, 'es_perseguidor_override' => $valor_perseguidor, 'valido_hasta' => $fechaValidaHasta->toDateString() ]
            );
            $this->cargarDocumentosYExcepciones();
            $this->dispatch('notificacion-exito', 'Excepción guardada correctamente.');
        } catch (\Exception $e) { Log::error("Error al guardar excepción: " . $e->getMessage(), $e->getTrace()); $this->dispatch('notificacion-error', 'Error al guardar la excepción.'); }
    }
    
    public function eliminarExcepcion($nombreDocumentoId) {
        $entidad = $this->getEntidadModel();
        if (!$entidad) return;
        try {
            DocumentoExcepcionCriticidad::where('mandante_id', $this->mandanteId)->where('nombre_documento_id', $nombreDocumentoId)->where('excepcionable_type', get_class($entidad))->where('excepcionable_id', $entidad->id)->delete();
            
            if (isset($this->documentosConCriticidad[$nombreDocumentoId])) {
                $this->documentosConCriticidad[$nombreDocumentoId]['excepcion'] = [
                    'afecta_cumplimiento_override' => '',
                    'restringe_acceso_override' => '',
                    'es_perseguidor_override' => '',
                ];
                $this->documentosConCriticidad[$nombreDocumentoId]['valido_hasta'] = null;
                $this->documentosConCriticidad[$nombreDocumentoId]['tiene_excepcion_activa'] = false;
            }

            $this->dispatch('notificacion-exito', 'Excepción eliminada correctamente.');
        } catch (\Exception $e) { 
            Log::error("Error al eliminar excepción: " . $e->getMessage()); 
            $this->dispatch('notificacion-error', 'Error al eliminar la excepción.'); 
        }
    }

    public function render()
    {
        return view('livewire.asem.gestion-criticidad-excepciones')->layout('layouts.app');
    }
}