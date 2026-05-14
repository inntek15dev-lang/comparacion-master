<?php

namespace App\Livewire\Oval;

use Livewire\Component;
use App\Models\Trabajador;
use App\Models\Vehiculo;
use App\Models\TrabajadorVinculacion;
use App\Models\VehiculoAsignacion;
use App\Models\Mandante;
use App\Models\Dependencia;
use App\Services\DocumentoRequeridoService;
use App\Services\CriticidadDocumentoService;
use Illuminate\Support\Str;

class ControlAcceso extends Component
{
    // Contexto de ubicación
    public $mandanteSeleccionadoId = '';
    public $dependenciaSeleccionadaId = '';
    public $mandantesDisponibles = [];
    public $dependenciasDisponibles = [];

    // Búsqueda
    public string $searchTerm = '';
    public $entidadEncontrada = null;
    public $tipoEntidad = null; // 'trabajador' o 'vehiculo'
    public array $vinculacionesResultado = [];
    public bool $busquedaRealizada = false;
    public string $mensajeError = '';

    protected $queryString = [
        'searchTerm' => ['except' => ''],
        'mandanteSeleccionadoId' => ['except' => ''],
        'dependenciaSeleccionadaId' => ['except' => ''],
    ];

    public function mount()
    {
        $this->mandantesDisponibles = Mandante::orderBy('razon_social')->get();
        if ($this->mandanteSeleccionadoId) {
            $this->dependenciasDisponibles = Dependencia::where('mandante_id', $this->mandanteSeleccionadoId)
                ->where('estado', true)
                ->orderBy('nombre')
                ->get();
        }
    }

    public function updatedMandanteSeleccionadoId($value)
    {
        $this->dependenciaSeleccionadaId = '';
        $this->dependenciasDisponibles = [];
        $this->reset(['entidadEncontrada', 'tipoEntidad', 'vinculacionesResultado', 'busquedaRealizada', 'mensajeError']);

        if ($value) {
            $this->dependenciasDisponibles = Dependencia::where('mandante_id', $value)
                ->where('estado', true)
                ->orderBy('nombre')
                ->get();
        }
    }

    public function updatedDependenciaSeleccionadaId()
    {
        $this->reset(['entidadEncontrada', 'tipoEntidad', 'vinculacionesResultado', 'busquedaRealizada', 'mensajeError', 'searchTerm']);
    }

    public function updatedSearchTerm()
    {
        $this->reset(['entidadEncontrada', 'tipoEntidad', 'vinculacionesResultado', 'busquedaRealizada', 'mensajeError']);
    }

    public function buscar()
    {
        $this->reset(['entidadEncontrada', 'tipoEntidad', 'vinculacionesResultado', 'mensajeError']);
        $this->busquedaRealizada = true;

        if (empty($this->mandanteSeleccionadoId)) {
            $this->mensajeError = 'Debe seleccionar un Mandante (Principal) antes de buscar.';
            return;
        }

        if (empty(trim($this->searchTerm))) {
            $this->mensajeError = 'Ingrese un RUT o Patente para buscar.';
            return;
        }

        $term = strtoupper(trim($this->searchTerm));
        // Limpiar guiones y espacios para la patente y rut
        $termClean = str_replace(['-', ' ', '.'], '', $term);

        // 1. Intentar buscar como Trabajador (RUT exacto)
        $trabajador = Trabajador::where('rut', $term)->orWhere('rut', $termClean)->first();
        if ($trabajador) {
            $this->entidadEncontrada = $trabajador;
            $this->tipoEntidad = 'trabajador';
            $this->procesarVinculacionesTrabajador($trabajador);
            return;
        }

        // 2. Intentar buscar como Vehículo (Patente exacta sin espacios)
        $vehiculo = Vehiculo::whereRaw("REPLACE(CONCAT(patente_letras, patente_numeros), ' ', '') = ?", [$termClean])->first();
        if ($vehiculo) {
            $this->entidadEncontrada = $vehiculo;
            $this->tipoEntidad = 'vehiculo';
            $this->procesarVinculacionesVehiculo($vehiculo);
            return;
        }

        $this->mensajeError = 'No se encontró ningún trabajador o vehículo con ese identificador.';
    }

    private function procesarVinculacionesTrabajador(Trabajador $trabajador)
    {
        // ================================================================
        // CAMBIO 5: GUARDIA DE ACREDITACIÓN
        // Si el contratista del trabajador no acredita (o está fuera del
        // período vigente de acreditación), bloquear la consulta completa.
        // ================================================================
        if ($trabajador->contratista_id) {
            $cuo = \App\Models\ContratistaUnidadOrganizacional::where('contratista_id', $trabajador->contratista_id)
                ->whereHas('unidadOrganizacionalMandante', function ($q) {
                    $q->where('mandante_id', $this->mandanteSeleccionadoId);
                })->first();

            if ($cuo) {
                if (!$cuo->acredita) {
                    $this->mensajeError = 'CONTRATISTA NO ACREDITA para este Principal. El acceso al predio no está habilitado.';
                    return;
                }
                $hoy = \Carbon\Carbon::today();
                $fueraRango = ($cuo->fecha_inicio_acredita && \Carbon\Carbon::parse($cuo->fecha_inicio_acredita)->gt($hoy))
                           || ($cuo->fecha_fin_acredita   && \Carbon\Carbon::parse($cuo->fecha_fin_acredita)->lt($hoy));
                if ($fueraRango) {
                    $this->mensajeError = 'CONTRATISTA FUERA DE VIGENCIA DE ACREDITACIÓN. El período activo no corresponde a la fecha actual.';
                    return;
                }
            }
        }
        // ================================================================
        $query = TrabajadorVinculacion::with(['unidadOrganizacionalMandante.mandante', 'dependencia', 'cargoMandante', 'tipoContrato'])
            ->where('trabajador_id', $trabajador->id)
            ->where('is_active', true)
            ->whereHas('unidadOrganizacionalMandante', function($q) {
                $q->where('mandante_id', $this->mandanteSeleccionadoId);
            });

        if (!empty($this->dependenciaSeleccionadaId)) {
            $query->where('dependencia_id', $this->dependenciaSeleccionadaId);
        }

        $vinculacionesActivas = $query->get();

        if ($vinculacionesActivas->isEmpty()) {
            $this->mensajeError = 'El trabajador no tiene acceso habilitado para este Mandante o Lugar de Trabajo específico (no hay contrato activo en esta ubicación).';
            return;
        }

        $this->generarResultados($trabajador, $vinculacionesActivas, true);
    }

    private function procesarVinculacionesVehiculo(Vehiculo $vehiculo)
    {
        $query = VehiculoAsignacion::with(['unidadOrganizacionalMandante.mandante', 'dependencia'])
            ->where('vehiculo_id', $vehiculo->id)
            ->where('is_active', true)
            ->whereHas('unidadOrganizacionalMandante', function($q) {
                $q->where('mandante_id', $this->mandanteSeleccionadoId);
            });

        if (!empty($this->dependenciaSeleccionadaId)) {
            $query->where('dependencia_id', $this->dependenciaSeleccionadaId);
        }

        $vinculacionesActivas = $query->get();

        if ($vinculacionesActivas->isEmpty()) {
            $this->mensajeError = 'El vehículo no tiene acceso habilitado para este Mandante o Lugar de Trabajo específico (no está asignado a esta ubicación).';
            return;
        }

        $this->generarResultados($vehiculo, $vinculacionesActivas, false);
    }

    private function generarResultados($entidad, $vinculaciones, $esTrabajador)
    {
        $documentoService = app(DocumentoRequeridoService::class);

        foreach ($vinculaciones as $vinc) {
            $mandanteId = $vinc->unidadOrganizacionalMandante->mandante_id ?? null;
            $uoId = $vinc->unidad_organizacional_mandante_id;

            if (!$mandanteId || !$uoId) continue;

            $documentosConEstado = $documentoService->obtenerEstadoDocumentosParaEntidad($entidad, $mandanteId, $uoId, $vinc->id);
            
            $estadoAcceso = $vinc->estado_acceso ?? ['habilitado' => false, 'motivo' => 'Restringido (Calculando)'];
            
            $documentosProblematicos = [];
            foreach ($documentosConEstado as $doc) {
                $restringe = $doc['restringe_acceso'] === true || $doc['restringe_acceso'] == 1;
                $aprobado = in_array($doc['estado_actual_documento'], ['Aprobado', 'Aprobado-Modificado']);
                $enGracia = ($doc['dentro_de_gracia'] ?? false) || ($doc['tiene_reemplazo_vigente'] ?? false);
                
                if ($restringe && !$aprobado && !$enGracia) {
                    $documentosProblematicos[] = [
                        'nombre' => $doc['nombre_documento_texto'],
                        'estado' => $doc['estado_actual_documento']
                    ];
                }
            }

            $this->vinculacionesResultado[] = [
                'mandante' => $vinc->unidadOrganizacionalMandante->mandante->razon_social ?? 'N/A',
                'unidad_organizacional' => $vinc->unidadOrganizacionalMandante->nombre_unidad ?? 'N/A',
                'lugar_trabajo' => $vinc->dependencia->nombre_jerarquico ?? $vinc->dependencia->nombre ?? 'Sin Dependencia Específica',
                'contrato' => $esTrabajador ? ($vinc->numero_contrato ?? 'S/N') : 'N/A',
                'cargo' => $esTrabajador ? ($vinc->cargoMandante->nombre_cargo ?? 'S/N') : 'N/A',
                'estado_acceso' => $estadoAcceso,
                'documentos_problematicos' => $documentosProblematicos,
            ];
        }
    }

    public function render()
    {
        return view('livewire.oval.control-acceso')->layout('layouts.app');
    }
}
