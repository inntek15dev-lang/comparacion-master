<?php

namespace App\Livewire\Contratista;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\DocumentoCargado;
use App\Models\ReglaDocumental;
use App\Models\Contratista;
use App\Models\Trabajador;
use App\Models\Vehiculo;
use App\Models\Maquinaria;
use App\Models\Embarcacion;
use App\Services\DocumentoRequeridoService;
use App\Services\AsignacionAutomaticaService;
use Illuminate\Support\Str;
use App\Livewire\Traits\FormatsDocumentTooltips;
use Livewire\Attributes\On;

use App\Traits\ValidatesFileUpload;

class CargaFlashContratista extends Component
{
    use WithFileUploads;
    use WithPagination;
    use FormatsDocumentTooltips;
    use ValidatesFileUpload;

    public ?int $contratistaId = null;
    public ?int $mandanteId = null;
    public ?int $unidadOrganizacionalId = null;
    public $lugarDeTrabajoId = null;

    public array $documentosParaCargar = [];
    public array $uploadErrors = [];
    public array $uploadSuccess = [];

    public string $filtroRecurso = '';
    public string $filtroDocumento = '';

    protected $paginationTheme = 'tailwind';

    private DocumentoRequeridoService $documentoService;
    private AsignacionAutomaticaService $asignacionService;

    public bool $showModalInfoCarga = false;
    public array $infoCargaSeleccionada = [];

    public function boot(DocumentoRequeridoService $documentoService, AsignacionAutomaticaService $asignacionService)
    {
        $this->documentoService = $documentoService;
        $this->asignacionService = $asignacionService;
    }

    public function mount(?int $contratistaIdForzado = null, ?int $mandanteId = null, ?int $unidadOrganizacionalId = null, $lugarDeTrabajoId = null)
    {
        $this->contratistaId = $contratistaIdForzado;
        $this->mandanteId = $mandanteId;
        $this->unidadOrganizacionalId = $unidadOrganizacionalId;
        $this->lugarDeTrabajoId = $lugarDeTrabajoId;
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['filtroRecurso', 'filtroDocumento'])) {
            $this->resetPage('cargaFlashPage');
        }
    }

    #[On('documentosActualizados')]
    public function refrescarLista()
    {
        // Forzar la re-renderización para actualizar la lista
        $this->render();
    }

    public function cargarDocumentos()
    {
        if (!$this->contratistaId || !$this->mandanteId) {
            session()->flash('error_carga_flash', 'Error de contexto. No se puede procesar la carga.');
            return;
        }

        $this->uploadErrors = [];
        $this->uploadSuccess = [];
        $this->resetErrorBag();

        $validationRules = [];
        foreach ($this->documentosParaCargar as $uniqueKey => $data) {
            if (!empty($data['archivo_input'])) {
                $validationRules["documentosParaCargar.{$uniqueKey}.archivo_input"] = $this->getFileValidationRule('acreditacion');
            }
        }

        $mensajeErrorUnificado = 'El archivo debe ser de tipo PDF y no debe exceder los 30MB de tamaño.';

        try {
            if (empty($validationRules)) {
                session()->flash('error_carga_flash', 'Debe seleccionar al menos un archivo para cargar.');
                return;
            }

            $this->validate(
                $validationRules,
                [
                    'documentosParaCargar.*.archivo_input.mimes' => $mensajeErrorUnificado,
                    'documentosParaCargar.*.archivo_input.mimetypes' => $mensajeErrorUnificado,
                    'documentosParaCargar.*.archivo_input.max' => $mensajeErrorUnificado,
                ]
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($this->documentosParaCargar as $data) {
                if (!empty($data['archivo_input'])) {
                    $this->validateSecureFile($data['archivo_input'], 'acreditacion', 'CARGA_FLASH');
                }
            }
            throw $e;
        }

        $usuarioCargaId = Auth::id();
        $huboArchivosParaProcesar = false;

        foreach ($this->documentosParaCargar as $uniqueKey => $data) {
            if (empty($data['archivo_input'])) continue;

            $huboArchivosParaProcesar = true;
            
            $keyParts = explode('|', $uniqueKey);
            $type = $keyParts[0];
            $id = $keyParts[1];
            $entidadType = str_replace('\\\\', '\\', $keyParts[2]);
            $entidadId = $keyParts[3];
            $unidadOrganizacionalContextoId = $keyParts[4];
            $vinculacionIdStr = (isset($keyParts[5]) && $keyParts[5] !== '0') ? $keyParts[5] : null;

            $entidad = app($entidadType)->find($entidadId);
            if (!$entidad) {
                Log::error("Carga Flash: No se encontró la entidad {$entidadType} con ID {$entidadId}");
                continue;
            }

            // SEGURIDAD: Solo los trabajadores usan trabajador_vinculacion_id
            if ($entidadType !== \App\Models\Trabajador::class) {
                $vinculacionIdStr = null;
            }

            $reglaId = null;
            $criteriosSnapshot = null;

            if ($type === 'doc') {
                $docOriginal = DocumentoCargado::find($id);
                if (!$docOriginal) continue;
                $reglaId = $docOriginal->regla_documental_id_origen;
                $criteriosSnapshot = $docOriginal->criterios_snapshot;
            } elseif ($type === 'regla') {
                $reglaId = $id;
                $reglaTemp = ReglaDocumental::with('criterios.criterioEvaluacion', 'criterios.subCriterio', 'criterios.aclaracionCriterio', 'criterios.textoRechazo')->find($reglaId);
                if ($reglaTemp) {
                    $criteriosSnapshot = $reglaTemp->criterios->map(function ($criterioPivote) {
                        return [
                            'criterio' => $criterioPivote->criterioEvaluacion->nombre_criterio ?? 'Criterio no encontrado',
                            'texto_rechazo' => $criterioPivote->textoRechazo->texto_rechazo ?? null,
                            'sub_criterio' => $criterioPivote->subCriterio->nombre ?? null,
                            'aclaracion' => $criterioPivote->aclaracionCriterio->texto_aclaracion ?? null,
                        ];
                    })->toArray();
                }
            }

            if (!$reglaId) continue;

            $reglaOriginal = ReglaDocumental::with(['nombreDocumento', 'tipoVencimiento'])->findOrFail($reglaId);

            $errorValidacion = null;
            if (($reglaOriginal->valida_emision || $reglaOriginal->tipoVencimiento?->nombre === 'DESDE EMISION') && empty($data['fecha_emision_input'])) {
                $errorValidacion = 'Se requiere Fecha de Emisión.';
            }
            if (($reglaOriginal->valida_vencimiento || $reglaOriginal->tipoVencimiento?->nombre === 'SEGUN DOCUMENTO') && empty($data['fecha_vencimiento_input'])) {
                $errorValidacion = 'Se requiere Fecha de Vencimiento.';
            }
            if ($reglaOriginal->tipoVencimiento?->nombre === 'POR PERIODO' && empty($data['periodo_input'])) {
                $errorValidacion = 'El Período a cargar no pudo ser determinado.';
            }

            if ($errorValidacion) {
                $this->addError('documentosParaCargar.' . $uniqueKey . '.archivo_input', $errorValidacion);
                continue;
            }

            DB::transaction(function () use ($entidad, $entidadId, $entidadType, $reglaId, $data, $reglaOriginal, $criteriosSnapshot, $usuarioCargaId, $uniqueKey, $unidadOrganizacionalContextoId, $vinculacionIdStr) {
                
                $queryActivo = DocumentoCargado::where('entidad_id', $entidadId)
                    ->where('entidad_type', $entidadType)
                    ->where('mandante_id', $this->mandanteId)
                    ->where('unidad_organizacional_id', $unidadOrganizacionalContextoId)
                    ->where('regla_documental_id_origen', $reglaId)
                    ->whereNotIn('estado_validacion', ['Archivado', 'Archivado-Revalidado']);

                if ($vinculacionIdStr) {
                    $queryActivo->where('trabajador_vinculacion_id', $vinculacionIdStr);
                } else {
                    $queryActivo->whereNull('trabajador_vinculacion_id');
                }

                $documentoActivo = $queryActivo->first();
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

                $archivo      = $data['archivo_input'];
                $rutaDirectorio = match($entidadType) {
                    \App\Models\Contratista::class  => "contratistas/{$entidadId}",
                    \App\Models\Trabajador::class   => "trabajadores/{$entidadId}",
                    \App\Models\Vehiculo::class     => "vehiculos/{$entidadId}",
                    \App\Models\Maquinaria::class   => "maquinarias/{$entidadId}",
                    \App\Models\Embarcacion::class  => "embarcaciones/{$entidadId}",
                    default                         => strtolower(class_basename($entidadType)) . "s/{$entidadId}"
                };
                $stored      = $this->encryptAndStoreFile($archivo, $rutaDirectorio, 'CARGA_FLASH');
                $rutaArchivo = $stored['ruta_archivo'];
                $isEncrypted = $stored['is_encrypted'];

                $fechaVencimientoCalculada = $data['fecha_vencimiento_input'] ?? null;
                if ($reglaOriginal->tipoVencimiento?->nombre === 'DESDE EMISION' && !empty($data['fecha_emision_input'])) {
                    $diasValidez = $reglaOriginal->dias_validez_documento ?? 0;
                    $fechaVencimientoCalculada = Carbon::parse($data['fecha_emision_input'])->addDays($diasValidez)->format('Y-m-d');
                }

                $nuevoDocumento = DocumentoCargado::create([
                    'contratista_id' => $this->contratistaId,
                    'mandante_id' => $this->mandanteId,
                    'unidad_organizacional_id' => $unidadOrganizacionalContextoId,
                    'trabajador_vinculacion_id' => $vinculacionIdStr,
                    'entidad_id' => $entidadId,
                    'entidad_type' => $entidadType,
                    'regla_documental_id_origen' => $reglaId,
                    'usuario_carga_id' => $usuarioCargaId,
                    'ruta_archivo' => $rutaArchivo,
                    'is_encrypted' => $isEncrypted,
                    'nombre_original_archivo' => $archivo->getClientOriginalName(),
                    'mime_type' => $archivo->getMimeType(),
                    'tamano_archivo' => $archivo->getSize(),
                    'fecha_emision' => $data['fecha_emision_input'] ?? null,
                    'fecha_vencimiento' => $fechaVencimientoCalculada,
                    'periodo' => $data['periodo_input'] ?? null,
                    'estado_validacion' => $reglaOriginal->valida_solo_mandante ? 'Pendiente Validación Mandante' : 'Sin Asignar',
                    'reemplaza_a_id' => $reemplazaId,
                    'nombre_documento_snapshot' => $reglaOriginal->nombreDocumento?->nombre,
                    'tipo_vencimiento_snapshot' => $reglaOriginal->tipoVencimiento?->nombre ?? 'NO APLICA',
                    'valida_emision_snapshot' => (bool)$reglaOriginal->valida_emision,
                    'valida_vencimiento_snapshot' => (bool)$reglaOriginal->valida_vencimiento,
                    'valida_solo_mandante_snapshot' => (bool)$reglaOriginal->valida_solo_mandante,
                    'valor_nominal_snapshot' => $reglaOriginal->valor_nominal_documento,
                    'criterios_snapshot' => $criteriosSnapshot,
                ]);

                // AUDITORÍA DETALLADA DE CARGA
                $nombreRecurso = match($entidadType) {
                    \App\Models\Contratista::class => $entidad->razon_social ?? 'N/A',
                    \App\Models\Trabajador::class => trim(($entidad->nombres ?? '') . ' ' . ($entidad->apellido_paterno ?? '')),
                    \App\Models\Vehiculo::class => ($entidad->patente_letras ?? '') . ($entidad->patente_numeros ?? ''),
                    default => 'N/A'
                };

                \App\Services\AuditService::log(
                    'carga-documento',
                    "Carga Flash: [" . ($reglaOriginal->nombreDocumento?->nombre ?? 'N/A') . "] para [" . $nombreRecurso . "]. Archivo: " . $archivo->getClientOriginalName(),
                    [
                        'documento_id' => $nuevoDocumento->id,
                        'regla_id' => $reglaId,
                        'recurso_id' => $entidadId,
                        'recurso_type' => $entidadType,
                        'archivo_original' => $archivo->getClientOriginalName(),
                        'modo' => 'CARGA_FLASH'
                    ]
                );

                if (!$reglaOriginal->valida_solo_mandante) {
                    $this->asignacionService->intentarAsignar($nuevoDocumento);
                }
                $this->uploadSuccess[$uniqueKey] = 'Archivo cargado exitosamente.';

            }, 5);
        }

        if (!$huboArchivosParaProcesar) {
            session()->flash('info_carga_flash', 'No se seleccionó ningún archivo nuevo para cargar.');
        } else {
            session()->flash('message_carga_flash', 'Proceso de carga finalizado. La lista se actualizará.');
        }
        $this->reset('documentosParaCargar');
        $this->dispatch('documentosActualizados');
    }

    public function abrirModalInfoCarga($key)
    {
        if (empty($key)) {
            session()->flash('error_carga_flash', 'Error: La clave del documento es inválida.');
            return;
        }

        $keyParts = explode('|', $key);
        $type = $keyParts[0];
        $id = $keyParts[1];
        $entidadType = str_replace('\\\\', '\\', $keyParts[2]);
        $entidadId = $keyParts[3];
        $unidadOrganizacionalContextoId = $keyParts[4];
        $vinculacionId = (isset($keyParts[5]) && $keyParts[5] !== '0') ? $keyParts[5] : null;

        $entidad = app($entidadType)->find($entidadId);
        if (!$entidad) {
            session()->flash('error_carga_flash', 'No se pudo encontrar la entidad asociada al documento.');
            return;
        }

        $documentosRequeridos = $this->documentoService->obtenerEstadoDocumentosParaEntidad($entidad, $this->mandanteId, $unidadOrganizacionalContextoId);

        $documentoEncontrado = collect($documentosRequeridos)->first(function ($doc) use ($type, $id) {
            if ($type === 'doc' && $doc['archivo_cargado'] && $doc['archivo_cargado']->id == $id) {
                return true;
            }
            if ($type === 'regla' && $doc['regla_documental_id_origen'] == $id) {
                return true;
            }
            return false;
        });

        if ($documentoEncontrado) {
            $this->infoCargaSeleccionada = $documentoEncontrado;
            $this->showModalInfoCarga = true;
        } else {
            session()->flash('error_carga_flash', 'No se pudo encontrar la información para el documento seleccionado.');
        }
    }

    public function cerrarModalInfoCarga()
    {
        $this->showModalInfoCarga = false;
        $this->infoCargaSeleccionada = [];
    }

    // ================== INICIO DE LA REINGENIERÍA DE RENDIMIENTO ==================
    private function getDocumentosUrgentesPaginados()
    {
        if (!$this->contratistaId || !$this->mandanteId) {
            return new LengthAwarePaginator([], 0, 50, 1, ['pageName' => 'cargaFlashPage']);
        }

        // 1. Obtener todas las entidades y sus contextos de una manera más eficiente
        $entidadesConContexto = $this->getEntidadesYContextosOptimizados();
        if (empty($entidadesConContexto)) {
            return new LengthAwarePaginator([], 0, 50, 1, ['pageName' => 'cargaFlashPage']);
        }

        // 2. Obtener todos los documentos requeridos en una sola pasada si es posible
        $documentosUrgentes = [];
        $fechaLimite = Carbon::now()->addDays(15);

        foreach ($entidadesConContexto as $item) {
            $entidad = $item['entidad'];
            $uoId = $item['unidad_organizacional_id'];
            $vinculacionAsignacionId = isset($item['vinculacion_id']) ? $item['vinculacion_id']->id : null;
            
            // Esta sigue siendo la parte costosa, pero ahora la aplicamos a un conjunto pre-filtrado de entidades
            $documentosRequeridos = $this->documentoService->obtenerEstadoDocumentosParaEntidad($entidad, $this->mandanteId, $uoId, $vinculacionAsignacionId);

            foreach ($documentosRequeridos as $doc) {
                $esUrgente = false;
                $estadoFlash = '';
                $fechaVencimiento = $doc['archivo_cargado'] ? $doc['archivo_cargado']->fecha_vencimiento : null;

                if ($doc['estado_actual_documento'] === 'No Cargado') { $esUrgente = true; $estadoFlash = 'No Cargado'; } 
                elseif ($doc['estado_actual_documento'] === 'Rechazado') { $esUrgente = true; $estadoFlash = 'Rechazado'; } 
                elseif (in_array($doc['estado_actual_documento'], ['Vencido', 'Vencido-Modificado'])) { $esUrgente = true; $estadoFlash = 'Vencido'; } 
                elseif ($fechaVencimiento && Carbon::parse($fechaVencimiento)->isBetween(Carbon::now(), $fechaLimite)) { $esUrgente = true; $estadoFlash = 'Por Vencer'; }

                if ($esUrgente) {
                    $entidadIdentificador = match(get_class($entidad)) {
                        Contratista::class => $entidad->rut, Trabajador::class => $entidad->rut,
                        Vehiculo::class => $entidad->patente_completa, Maquinaria::class => $entidad->identificador_completo,
                        Embarcacion::class => $entidad->matricula_completa, default => 'N/A',
                    };
                    $entidadNombre = (get_class($entidad) === Contratista::class) ? $entidad->razon_social : ($entidad->nombre_completo ?? $entidadIdentificador);
                    $entidadTypeEscapado = str_replace('\\', '\\\\', get_class($entidad));
                    $uniqueKey = $doc['archivo_cargado']
                        ? 'doc|' . $doc['archivo_cargado']->id . '|' . $entidadTypeEscapado . '|' . $entidad->id . '|' . $uoId . '|' . ($vinculacionAsignacionId ?? '0')
                        : 'regla|' . $doc['regla_documental_id_origen'] . '|' . $entidadTypeEscapado . '|' . $entidad->id . '|' . $uoId . '|' . ($vinculacionAsignacionId ?? '0');

                    $doc['estado_flash'] = $estadoFlash;
                    $doc['entidad_identificador'] = $entidadIdentificador;
                    $doc['entidad_nombre'] = $entidadNombre;
                    $doc['unique_key'] = $uniqueKey;
                    $doc['entidad_id'] = $entidad->id;
                    $doc['entidad_type'] = get_class($entidad);
                    
                    $documentosUrgentes[$uniqueKey] = $doc; // Usar uniqueKey para evitar duplicados
                }
            }
        }

        $items = collect(array_values($documentosUrgentes));

        // 3. Aplicar filtros y ordenamiento en la colección
        if (trim($this->filtroRecurso)) {
            $items = $items->filter(fn($doc) => stripos($doc['entidad_nombre'], $this->filtroRecurso) !== false || stripos($doc['entidad_identificador'], $this->filtroRecurso) !== false);
        }
        if (trim($this->filtroDocumento)) {
            $items = $items->filter(fn($doc) => stripos($doc['nombre_documento_texto'], $this->filtroDocumento) !== false);
        }

        $items = $items->sortBy(function ($doc) {
            $order = ['No Cargado' => 1, 'Rechazado' => 2, 'Vencido' => 3, 'Por Vencer' => 4];
            return ($order[$doc['estado_flash']] ?? 99) . ($doc['archivo_cargado'] ? $doc['archivo_cargado']->fecha_vencimiento : '9999-12-31');
        });

        // 4. Paginar la colección resultante (AHORA 50 ITEMS)
        $page = $this->getPage('cargaFlashPage');
        $perPage = 50; // MODIFICADO: 50 items por página
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, ['path' => request()->url(), 'pageName' => 'cargaFlashPage']);
    }

    private function getEntidadesYContextosOptimizados()
    {
        $entidadesConContexto = [];
        $contratista = Contratista::find($this->contratistaId);
        if (!$contratista) return [];

        $queryUOs = $contratista->unidadesOrganizacionalesMandante()->where('mandante_id', $this->mandanteId);
        if ($this->unidadOrganizacionalId) {
            $queryUOs->where('unidades_organizacionales_mandante.id', $this->unidadOrganizacionalId);
        }
        $uosRelevantes = $queryUOs->pluck('unidades_organizacionales_mandante.id');
        if ($uosRelevantes->isEmpty()) return [];

        // Añadir la propia empresa como entidad
        foreach ($uosRelevantes as $uoId) {
            $entidadesConContexto[] = ['entidad' => $contratista, 'unidad_organizacional_id' => $uoId];
        }

        $modelos = [
            'trabajadores' => Trabajador::class, 'vehiculos' => Vehiculo::class,
            'maquinarias' => Maquinaria::class, 'embarcaciones' => Embarcacion::class,
        ];

        foreach ($modelos as $tabla => $modelo) {
            $query = $modelo::query()
                ->where('contratista_id', $this->contratistaId)
                ->whereHas('vinculaciones', function ($q) use ($uosRelevantes) {
                    $q->whereIn('unidad_organizacional_mandante_id', $uosRelevantes)
                      ->where('is_active', true)
                      ->when($this->lugarDeTrabajoId && !in_array($this->lugarDeTrabajoId, ['in_reserve', 'orphaned']), function ($subQ) {
                          $subQ->where('dependencia_id', $this->lugarDeTrabajoId);
                      });
                });
            
            // Aplicar filtro de recurso a nivel de base de datos
            if (trim($this->filtroRecurso)) {
                $query->where(function($q) use ($tabla) {
                    if ($tabla === 'trabajadores') {
                        $q->where('nombres', 'like', '%'.$this->filtroRecurso.'%')
                          ->orWhere('apellido_paterno', 'like', '%'.$this->filtroRecurso.'%')
                          ->orWhere('rut', 'like', '%'.$this->filtroRecurso.'%');
                    } // Añadir lógicas similares para otros modelos si es necesario
                });
            }

            $recursos = $query->with(['vinculaciones' => function ($q) use ($uosRelevantes) {
                $q->whereIn('unidad_organizacional_mandante_id', $uosRelevantes)
                  ->where('is_active', true)
                  ->when($this->lugarDeTrabajoId && !in_array($this->lugarDeTrabajoId, ['in_reserve', 'orphaned']), function ($subQ) {
                      $subQ->where('dependencia_id', $this->lugarDeTrabajoId);
                  });
            }])->get();

            foreach ($recursos as $recurso) {
                foreach ($recurso->vinculaciones as $vinculacion) {
                    $entidadesConContexto[] = [
                        'entidad' => $recurso, 
                        'unidad_organizacional_id' => $vinculacion->unidad_organizacional_mandante_id,
                        'vinculacion_id' => clone $vinculacion // Guardamos el objeto completo o el ID
                    ];
                }
            }
        }
        return $entidadesConContexto;
    }
    // ================== FIN DE LA REINGENIERÍA DE RENDIMIENTO ====================

    public function render()
    {
        $paginator = $this->getDocumentosUrgentesPaginados();

        if ($paginator->isNotEmpty()) {
            $tempDocsParaCargar = [];
            foreach ($paginator as $doc) {
                if (!isset($this->documentosParaCargar[$doc['unique_key']])) {
                     $tempDocsParaCargar[$doc['unique_key']] = [
                        'archivo_input' => null,
                        'fecha_emision_input' => null,
                        'fecha_vencimiento_input' => null,
                        'periodo_input' => $doc['siguiente_periodo_requerido'] ?? null,
                    ];
                }
            }
            $this->documentosParaCargar = array_merge($tempDocsParaCargar, $this->documentosParaCargar);
        }

        return view('livewire.contratista.carga-flash-contratista', [
            'documentosUrgentes' => $paginator
        ]);
    }
}