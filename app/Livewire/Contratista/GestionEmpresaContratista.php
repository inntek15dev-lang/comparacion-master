<?php

namespace App\Livewire\Contratista;

use Livewire\Component;
use App\Models\Contratista;
use App\Models\UnidadOrganizacionalMandante;
use App\Services\DocumentoRequeridoService;
use Livewire\Attributes\On;
use Illuminate\Support\Collection;

class GestionEmpresaContratista extends Component
{
    public ?int $contratistaIdForzado = null;
    public ?int $mandanteId = null;
    public ?int $unidadOrganizacionalId = null;
    public $lugarDeTrabajoId = null;

    // CAMBIO 5: Cuando el CUO no acredita, ocultar columnas de cumplimiento y acceso.
    public bool $sinAcreditacion = false;

    public ?Contratista $contratista = null;
    public Collection $asignaciones;

    public array $documentosMaestros = [];
    private DocumentoRequeridoService $documentoService;

    public function boot(DocumentoRequeridoService $documentoService)
    {
        $this->documentoService = $documentoService;
    }

    public function mount(array $documentosMaestros = [])
    {
        $this->contratista = Contratista::find($this->contratistaIdForzado);
        $this->asignaciones = collect();
        $this->documentosMaestros = $documentosMaestros;
        $this->calcularAsignacionesYEstados();
    }

    #[On('documentosActualizados')]
    public function recalcularEstados()
    {
        $this->calcularAsignacionesYEstados();
    }

    public function calcularAsignacionesYEstados()
    {
        if (!$this->contratista || !$this->mandanteId) {
            $this->asignaciones = collect();
            return;
        }

        // ================== INICIO DE LA MODIFICACIÓN: ITERAR POR VINCULACIONES ==================
        // En lugar de iterar solo por UOs, iteramos por las vinculaciones específicas del contratista
        // Esto nos da acceso a la condición empresa (tipo_condicion_id) de cada vinculación
        // Nota: pivot es un atributo automático de belongsToMany, no una relación que se carga con with()
        $vinculaciones = $this->contratista->unidadesOrganizacionalesMandante()
            ->where('mandante_id', $this->mandanteId)
            ->with('parent')
            ->get();

        // Si hay filtro de UO, filtramos
        if ($this->unidadOrganizacionalId) {
            $vinculaciones = $vinculaciones->filter(fn($uo) => $uo->id === $this->unidadOrganizacionalId);
        }

        $asignacionesCalculadas = collect();

        foreach ($vinculaciones as $uo) {
            // Obtener el pivot que contiene tipo_condicion_id
            $pivotVinculacion = $uo->pivot;
            $vinculacionContratistaId = $pivotVinculacion ? $pivotVinculacion->id : null;
            
            $cumplimiento = $this->contratista->calcularPorcentajeCumplimiento($this->mandanteId, $uo->id);
            $acceso = $this->contratista->determinarAccesoHabilitado($this->mandanteId, $uo->id);

            $estadosDocumentos = collect();
            if ($this->documentoService) {
                // Pasar vinculacionContratistaId para que el servicio use la condición empresa correcta
                $estados = $this->documentoService->obtenerEstadoDocumentosParaEntidad(
                    $this->contratista, 
                    $this->mandanteId, 
                    $uo->id,
                    null,  // vinculacionId (para trabajadores)
                    $vinculacionContratistaId  // vinculacionContratistaId (para empresas)
                );
                $estadosDocumentos = collect($estados)->mapWithKeys(fn($item) => [$item['nombre_documento_id'] => $item['estado_actual_documento']]);
            }

            $asignacionesCalculadas->push([
                'id' => $uo->id,
                'vinculacion_contratista_id' => $vinculacionContratistaId,  // Agregamos para referencia futura
                'unidad_organizacional_nombre' => $uo->nombre_jerarquico,
                'unidad_organizacional_id' => $uo->id,
                'mandante_id' => $this->mandanteId,
                'porcentaje_cumplimiento' => $cumplimiento,
                'acceso_habilitado' => $acceso['habilitado'],
                'acceso_motivo' => $acceso['motivo'],
                'estados_documentos' => $estadosDocumentos,
            ]);
        }
        // ================== FIN DE LA MODIFICACIÓN ==================
        
        $this->asignaciones = $asignacionesCalculadas->sortBy('unidad_organizacional_nombre');
    }

    // ================== INICIO DE LA MODIFICACIÓN: PASAR VINCULACIÓN ==================
    public function abrirModalCargaDocumentos(int $mandanteId, int $unidadOrganizacionalId, ?int $vinculacionContratistaId = null)
    {
        if (!$this->contratista) return;

        $uo = UnidadOrganizacionalMandante::with('mandante:id,razon_social')->find($unidadOrganizacionalId);
        $contexto = 'N/A';
        if ($uo) {
            $contexto = ($uo->mandante->razon_social ?? 'N/A') . ' - ' . ($uo->nombre_jerarquico ?? 'N/A');
        }

        $this->dispatch('abrirModalDocumentos', 
            recursoId: $this->contratista->id, 
            recursoType: Contratista::class,
            mandanteId: $mandanteId,
            unidadOrganizacionalId: $unidadOrganizacionalId,
            contexto: $contexto,
            vinculacionId: $vinculacionContratistaId  // Pasar la vinculación específica
        );
    }
    // ================== FIN DE LA MODIFICACIÓN ==================

    public function render()
    {
        return view('livewire.contratista.gestion-empresa-contratista');
    }
}