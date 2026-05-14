<?php

namespace App\Livewire\Contratista;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\DocumentoRequeridoService;
use App\Services\AsignacionAutomaticaService;
use App\Models\DocumentoCargado;
use App\Models\ReglaDocumental;
use App\Models\Contratista;
use App\Models\Trabajador;
use App\Models\Vehiculo;
use App\Models\Maquinaria;
use App\Models\Embarcacion;
use App\Models\TrabajadorVinculacion;
use App\Livewire\Traits\FormatsDocumentTooltips;
use Illuminate\Support\Facades\DB;
use App\Traits\ValidatesFileUpload;

class ModalGestionDocumentosRecurso extends Component
{
    use WithFileUploads;
    use FormatsDocumentTooltips;
    use ValidatesFileUpload;

    public ?int $contratistaIdForzado = null;
    public $contratistaId;

    public bool $showModal = false;
    public $recurso;
    public ?string $recursoType = null;
    public ?int $recursoId = null;
    public ?int $mandanteId = null;
    public ?int $unidadOrganizacionalId = null;
    // ================== INICIO DE LA MODIFICACIÓN (NUEVA PROPIEDAD) ==================
    public ?int $vinculacionId = null;
    // ================== FIN DE LA MODIFICACIÓN ========================================

    public string $nombreRecursoParaModal = '';
    public string $identificadorRecursoParaModal = '';
    public string $contextoParaModal = '';
    public array $infoExtraParaModal = [];

    public array $documentosRequeridos = [];
    public array $documentosParaCargar = [];
    public array $uploadErrors = [];
    public array $uploadSuccess = [];
    
    public bool $showModalInfoCarga = false;
    public array $infoCargaSeleccionada = [];

    private DocumentoRequeridoService $documentoService;
    private AsignacionAutomaticaService $asignacionService;

    public function boot(DocumentoRequeridoService $documentoService, AsignacionAutomaticaService $asignacionService)
    {
        $this->documentoService = $documentoService;
        $this->asignacionService = $asignacionService;
    }

    public function mount()
    {
        $this->contratistaId = $this->contratistaIdForzado ?? Auth::user()->contratista_id;
    }

    // ================== INICIO DE LA MODIFICACIÓN (NUEVO PARÁMETRO) ==================
    #[On('abrirModalDocumentos')]
    public function prepararModal(int $recursoId, string $recursoType, int $mandanteId, int $unidadOrganizacionalId, string $contexto, ?int $vinculacionId = null)
    {
        $this->recursoId = $recursoId;
        $this->recursoType = $recursoType;
        $this->mandanteId = $mandanteId;
        $this->unidadOrganizacionalId = $unidadOrganizacionalId;
        $this->contextoParaModal = $contexto;
        $this->vinculacionId = $vinculacionId; // <-- Almacenar el ID de la vinculación
    // ================== FIN DE LA MODIFICACIÓN ========================================

        $this->recurso = $this->recursoType::find($this->recursoId);

        if (!$this->recurso) {
            return;
        }

        if ($this->recursoType !== Contratista::class && $this->recurso->contratista_id != $this->contratistaId) {
            return;
        }

        if ($this->recursoType === Contratista::class && $this->recurso->id != $this->contratistaId) {
            return;
        }

        $this->establecerInfoRecurso();
        $this->determinarDocumentosRequeridos();
        $this->inicializarCamposCarga();
        
        $this->reset(['uploadErrors', 'uploadSuccess']);
        $this->resetErrorBag();
        $this->showModal = true;
    }

    private function establecerInfoRecurso()
    {
        $this->nombreRecursoParaModal = match($this->recursoType) {
            Contratista::class => $this->recurso->razon_social,
            Trabajador::class => $this->recurso->nombre_completo,
            Vehiculo::class => $this->recurso->patente_completa,
            Maquinaria::class => $this->recurso->identificador_completo,
            Embarcacion::class => $this->recurso->matricula_completa,
            default => 'Recurso Desconocido'
        };

        $this->identificadorRecursoParaModal = match($this->recursoType) {
            Contratista::class => 'NIT: ' . $this->recurso->rut,
            Trabajador::class => 'ID: ' . $this->recurso->rut,
            default => ''
        };

        $this->infoExtraParaModal = [];
        if ($this->recursoType === Trabajador::class) {
            // ================== INICIO DE LA MODIFICACIÓN (USAR VINCULACION ID) ==================
            // Usar el ID de la vinculación si está disponible para obtener el cargo correcto
            $vinculacion = $this->vinculacionId 
                ? TrabajadorVinculacion::with('cargoMandante:id,nombre_cargo')->find($this->vinculacionId)
                : TrabajadorVinculacion::with('cargoMandante:id,nombre_cargo')
                    ->where('trabajador_id', $this->recursoId)
                    ->where('unidad_organizacional_mandante_id', $this->unidadOrganizacionalId)
                    ->where('is_active', true)->first();
            // ================== FIN DE LA MODIFICACIÓN ========================================
            $this->infoExtraParaModal['cargo'] = $vinculacion?->cargoMandante?->nombre_cargo ?? 'N/A';
        }
    }

    private function determinarDocumentosRequeridos()
    {
        if (!$this->recurso || !$this->unidadOrganizacionalId || !$this->mandanteId) {
            $this->documentosRequeridos = [];
            return;
        }
        // ================== INICIO DE LA MODIFICACIÓN (PASAR VINCULACION ID) ==================
        // Para trabajadores: vinculacionId
        // Para empresas: vinculacionContratistaId (usamos el mismo vinculacionId que viene del dispatch)
        if ($this->recursoType === Contratista::class) {
            $this->documentosRequeridos = $this->documentoService->obtenerEstadoDocumentosParaEntidad(
                $this->recurso, $this->mandanteId, $this->unidadOrganizacionalId, null, $this->vinculacionId
            );
        } else {
            $this->documentosRequeridos = $this->documentoService->obtenerEstadoDocumentosParaEntidad(
                $this->recurso, $this->mandanteId, $this->unidadOrganizacionalId, $this->vinculacionId
            );
        }
        // ================== FIN DE LA MODIFICACIÓN ========================================

        foreach ($this->documentosRequeridos as &$doc) {
            if (isset($doc['archivo_cargado']) && $doc['archivo_cargado']->resultado_validacion === 'Aprobado' && is_null($doc['archivo_cargado']->fecha_vencimiento) && $doc['tipo_vencimiento_nombre'] !== 'POR PERIODO') {
                $doc['estado_actual_documento'] = 'Aprobado';
            }
        }
    }

    private function inicializarCamposCarga()
    {
        $temp = [];
        foreach ($this->documentosRequeridos as $doc) {
            $key = $doc['archivo_cargado'] ? $doc['archivo_cargado']->id : 'regla_' . $doc['regla_documental_id_origen'];
            
            $periodoInicial = ($doc['tipo_vencimiento_nombre'] === 'POR PERIODO')
                ? $doc['siguiente_periodo_requerido'] : null;

            $temp[$key] = ['archivo_input' => null, 'fecha_emision_input' => null, 'fecha_vencimiento_input' => null, 'periodo_input' => $periodoInicial];
        }
        $this->documentosParaCargar = $temp;
    }

    public function cargarDocumentos()
    {
        if (!$this->recurso) return;
        $this->reset(['uploadErrors', 'uploadSuccess']);

        $mapaDocs = collect($this->documentosRequeridos)->keyBy(function ($doc) {
            return $doc['archivo_cargado'] ? $doc['archivo_cargado']->id : 'regla_' . $doc['regla_documental_id_origen'];
        });

        $validationRules = [];
        $validationMessages = [];
        $huboArchivos = false;

        foreach ($this->documentosParaCargar as $key => $data) {
            if (empty($data['archivo_input'])) continue;
            $huboArchivos = true;

            $infoRegla = $mapaDocs->get($key);
            if (!$infoRegla) continue;

            $baseKey = 'documentosParaCargar.' . $key;

            // Validación de Fechas
            if ($infoRegla['valida_emision'] || $infoRegla['tipo_vencimiento_nombre'] === 'DESDE EMISION') {
                $validationRules[$baseKey . '.fecha_emision_input'] = 'required|date';
                $validationMessages[$baseKey . '.fecha_emision_input.required'] = 'La F. Emisión es obligatoria.';
            }

            if ($infoRegla['valida_vencimiento'] || $infoRegla['tipo_vencimiento_nombre'] === 'SEGUN DOCUMENTO') {
                $validationRules[$baseKey . '.fecha_vencimiento_input'] = 'required|date';
                $validationMessages[$baseKey . '.fecha_vencimiento_input.required'] = 'La F. Vencimiento es obligatoria.';
            }

            // Validación de Archivo (Individual para evitar que pida los vacíos)
            $validationRules[$baseKey . '.archivo_input'] = $this->getFileValidationRule('acreditacion');
        }
        
        $mensajeErrorUnificado = 'El archivo debe ser de tipo PDF y no debe exceder los 30MB de tamaño.';

        try {
            if (empty($validationRules)) {
                session()->flash('error', 'Debe seleccionar al menos un archivo para cargar.');
                return;
            }

            $this->validate(
                $validationRules,
                array_merge($validationMessages, [
                    'documentosParaCargar.*.archivo_input.mimes' => $mensajeErrorUnificado,
                    'documentosParaCargar.*.archivo_input.mimetypes' => $mensajeErrorUnificado,
                    'documentosParaCargar.*.archivo_input.max' => $mensajeErrorUnificado,
                ])
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($this->documentosParaCargar as $data) {
                if (!empty($data['archivo_input'])) {
                    $this->validateSecureFile($data['archivo_input'], 'acreditacion', 'ACREDITACION_INDIVIDUAL');
                }
            }
            throw $e;
        }

        foreach ($this->documentosParaCargar as $key => $data) {
            if (empty($data['archivo_input'])) continue;
            
            $infoRegla = $mapaDocs->get($key);
            if (!$infoRegla) continue;

            $archivo = $data['archivo_input'];
            $reglaId = $infoRegla['regla_documental_id_origen'];
            
            try {
                $reglaOriginal = ReglaDocumental::with(['nombreDocumento', 'tipoVencimiento'])->findOrFail($reglaId);
                
                $directorioBase = match($this->recursoType) {
                    Contratista::class  => "contratistas/{$this->recurso->id}",
                    Trabajador::class   => "trabajadores/{$this->recurso->id}",
                    Vehiculo::class     => "vehiculos/{$this->recurso->id}",
                    Maquinaria::class   => "maquinarias/{$this->recurso->id}",
                    Embarcacion::class  => "embarcaciones/{$this->recurso->id}",
                    default             => Str::lower(class_basename($this->recursoType)) . "s/{$this->recurso->id}"
                };

                $stored      = $this->encryptAndStoreFile($archivo, $directorioBase, 'ACREDITACION');
                $rutaArchivo = $stored['ruta_archivo'];
                $isEncrypted = $stored['is_encrypted'];

                $fechaVencimientoFinal = $data['fecha_vencimiento_input'] ?? null;

                if ($reglaOriginal->tipoVencimiento?->nombre === 'DESDE CARGA' && is_numeric($reglaOriginal->dias_validez_documento)) {
                    $fechaVencimientoFinal = Carbon::now()->addDays($reglaOriginal->dias_validez_documento);
                }

                DB::transaction(function () use ($reglaId, $rutaArchivo, $isEncrypted, $archivo, $data, $fechaVencimientoFinal, $reglaOriginal, $infoRegla) {
                    // ================== PERSEGUIDOR/VINCULACIÓN: Determinar vinculacion_id para este doc ==================
                    // Si el doc NO es perseguidor + entidad es Trabajador + hay vinculación en contexto
                    // → stampeamos el doc con la vinculación específica.
                    $vinculacionIdParaDoc = null;
                    if (
                        !($infoRegla['es_perseguidor'] ?? true)
                        && $this->recursoType === Trabajador::class
                        && $this->vinculacionId
                    ) {
                        $vinculacionIdParaDoc = $this->vinculacionId;
                    }

                    // Buscar documento activo a reemplazar, filtrado por vinculación cuando aplica.
                    $queryActivo = DocumentoCargado::where('entidad_id', $this->recurso->id)
                        ->where('entidad_type', $this->recursoType)
                        ->where('mandante_id', $this->mandanteId)
                        ->where('unidad_organizacional_id', $this->unidadOrganizacionalId)
                        ->where('regla_documental_id_origen', $reglaId)
                        ->whereNotIn('estado_validacion', ['Archivado', 'Archivado-Revalidado']);

                    // Si el doc es NO perseguidor, restringimos la búsqueda a la misma vinculación.
                    if ($vinculacionIdParaDoc) {
                        $queryActivo->where('trabajador_vinculacion_id', $vinculacionIdParaDoc);
                    } else {
                        // Para perseguidores, ignoramos los que SÍ tienen vinculacion_id (que son de otro contexto)
                        $queryActivo->whereNull('trabajador_vinculacion_id');
                    }

                    $documentoActivo = $queryActivo->first();
                    // ==============================================================================================

                    $reemplazaId = null;

                    if ($documentoActivo) {
                        if (is_null($documentoActivo->resultado_validacion)) {
                            $this->deleteDocumentFile($documentoActivo->ruta_archivo, (bool)$documentoActivo->is_encrypted);
                            $documentoActivo->delete();
                        }
                        elseif ($documentoActivo->resultado_validacion === 'Rechazado' || ($documentoActivo->fecha_vencimiento && $documentoActivo->fecha_vencimiento->isPast())) {
                            $documentoActivo->update(['estado_validacion' => 'Archivado']);
                        }
                        else {
                            $reemplazaId = $documentoActivo->id;
                        }
                    }

                    $nuevoDocumento = DocumentoCargado::create([
                        'contratista_id' => $this->contratistaId,
                        'mandante_id' => $this->mandanteId,
                        'unidad_organizacional_id' => $this->unidadOrganizacionalId,
                        // ================== PERSEGUIDOR/VINCULACIÓN ==================
                        'trabajador_vinculacion_id' => $vinculacionIdParaDoc,
                        // ============================================================
                        'entidad_id' => $this->recurso->id,
                        'entidad_type' => $this->recursoType,
                        'regla_documental_id_origen' => $reglaId,
                        'usuario_carga_id' => Auth::id(),
                        'ruta_archivo' => $rutaArchivo,
                        'is_encrypted' => $isEncrypted,
                        'nombre_original_archivo' => $archivo->getClientOriginalName(),
                        'mime_type' => $archivo->getMimeType(), 
                        'tamano_archivo' => $archivo->getSize(),
                        'fecha_emision' => $data['fecha_emision_input'] ?? null, 
                        'fecha_vencimiento' => $fechaVencimientoFinal,
                        'periodo' => $data['periodo_input'] ?? null,
                        'estado_validacion' => $reglaOriginal->valida_solo_mandante ? 'Pendiente Validación Mandante' : 'Sin Asignar',
                        'reemplaza_a_id' => $reemplazaId,
                        'nombre_documento_snapshot' => $reglaOriginal->nombreDocumento?->nombre,
                        'tipo_vencimiento_snapshot' => $reglaOriginal->tipoVencimiento?->nombre ?? 'NO APLICA',
                        'valida_emision_snapshot' => (bool)$reglaOriginal->valida_emision,
                        'valida_vencimiento_snapshot' => (bool)$reglaOriginal->valida_vencimiento,
                        'valida_solo_mandante_snapshot' => (bool)$reglaOriginal->valida_solo_mandante,
                        'valor_nominal_snapshot' => $reglaOriginal->valor_nominal_documento,
                        'criterios_snapshot' => $infoRegla['criterios_evaluacion'],
                    ]);

                    // AUDITORÍA DETALLADA DE CARGA
                    \App\Services\AuditService::log(
                        'carga-documento',
                        "Carga de documento: [" . ($reglaOriginal->nombreDocumento?->nombre ?? 'N/A') . "] para [" . $this->getNombreRecurso($this->recurso) . "]. Archivo: " . $archivo->getClientOriginalName(),
                        [
                            'documento_id' => $nuevoDocumento->id,
                            'regla_id' => $reglaId,
                            'recurso_id' => $this->recurso->id,
                            'recurso_type' => $this->recursoType,
                            'archivo_original' => $archivo->getClientOriginalName()
                        ]
                    );
                    
                    if (!$reglaOriginal->valida_solo_mandante) {
                        $this->asignacionService->intentarAsignar($nuevoDocumento);
                    }
                });
                
                $this->uploadSuccess[$key] = 'Archivo cargado exitosamente.';
            } catch (\Exception $e) {
                Log::error("Error al cargar documento para {$this->recursoType} {$this->recurso->id}: " . $e->getMessage());
                $this->addError('documentosParaCargar.' . $key . '.archivo_input', 'Error inesperado.');
            }
        }

        if ($huboArchivos) {
            $this->determinarDocumentosRequeridos();
            $this->inicializarCamposCarga();
        }
    }

    public function eliminarDocumentoCargado($docId)
    {
        $doc = DocumentoCargado::find($docId);
        if ($doc && $doc->contratista_id == $this->contratistaId && is_null($doc->resultado_validacion)) {
            $this->deleteDocumentFile($doc->ruta_archivo, (bool)$doc->is_encrypted);
            $doc->delete();
            $this->determinarDocumentosRequeridos();
            $this->inicializarCamposCarga();
        }
    }

    public function abrirModalInfoCarga($key)
    {
        $mapaDocs = collect($this->documentosRequeridos)->keyBy(function ($doc) {
            return $doc['archivo_cargado'] ? $doc['archivo_cargado']->id : 'regla_' . $doc['regla_documental_id_origen'];
        });
        $this->infoCargaSeleccionada = $mapaDocs->get($key, []);
        if (!empty($this->infoCargaSeleccionada)) {
            $this->showModalInfoCarga = true;
        }
    }

    public function cerrarModalInfoCarga()
    {
        $this->showModalInfoCarga = false;
        $this->infoCargaSeleccionada = [];
    }

    public function cerrarModal()
    {
        $this->showModal = false;
        $this->reset(['recurso', 'recursoType', 'recursoId', 'mandanteId', 'unidadOrganizacionalId', 'documentosRequeridos', 'documentosParaCargar', 'vinculacionId']);
        $this->dispatch('documentosActualizados');
    }

    private function getNombreRecurso($entidad)
    {
        if (!$entidad) return 'N/A';
        
        return match(get_class($entidad)) {
            \App\Models\Contratista::class => $entidad->razon_social ?? 'N/A',
            \App\Models\Trabajador::class => trim(($entidad->nombres ?? '') . ' ' . ($entidad->apellido_paterno ?? '')),
            \App\Models\Vehiculo::class => ($entidad->patente_letras ?? '') . ($entidad->patente_numeros ?? ''),
            default => 'N/A'
        };
    }

    public function render()
    {
        return view('livewire.contratista.modal-gestion-documentos-recurso');
    }
}