<?php

namespace App\Livewire\Asem;

use App\Models\DocumentoCargado;
use App\Models\DatoExtraidoIa;
use App\Models\IaCampoConfiguracion;
use App\Services\IaExtraccionService;
use App\Services\IaMatchService;
use App\Imports\DatosIaExcelImport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

/**
 * RevisionIaAcreditacion — Panel principal del módulo IA para Acreditación.
 *
 * Acceso: ASEM_Admin (prefix: gestion, route: gestion.ia-acreditacion)
 * NO interviene en el flujo normal de acreditación.
 */
class RevisionIaAcreditacion extends Component
{
    use WithFileUploads, WithPagination;

    // ─── Pestañas ─────────────────────────────────────────────────────────────
    public string $pestana = 'documentos'; // 'documentos' | 'configuracion'

    // ─── Filtros ──────────────────────────────────────────────────────────────
    public string $busqueda         = '';
    public string $filtroEstadoIa   = '';
    public string $filtroMatchResult= '';
    public string $filtroMandante   = '';
    public string $filtroFuenteIa   = '';

    // ─── Configuración de campos por regla ───────────────────────────────────
    public ?int  $configMandanteId    = null;  // Filtro por Principal
    public ?int  $configReglaId        = null;
    public array $configCamposActivos  = []; // campo_clave seleccionados (columnas)
    public array $configRequeridos     = []; // campo_clave marcados como requeridos
    public array $configCriteriosActivos = []; // IDs de criterio_evaluacion seleccionados
    public array $configCriteriosRequeridos = []; // IDs de criterio_evaluacion marcados requeridos
    public array $configCriteriosDescripciones = []; // Instrucción manual para la IA por cada criterio activo
    public array $configCriteriosFormatos   = []; // array [id_criterio => formato_muestra_id]
    public string $configMensaje       = '';
    public string $configMensajeError  = '';
    public bool  $configGuardado       = false;

    // ─── Selección masiva ────────────────────────────────────────────────────
    public array $seleccionados = [];
    public bool  $seleccionarTodos = false;

    // ─── Modal de detalle / confirmación ────────────────────────────────────
    public ?int  $detalleDocId  = null;
    public ?int  $detalleIaId   = null;
    public bool  $modalDetalle  = false;

    // ─── Selector de Modelo IA ───────────────────────────────────────────────
    public string $modeloIaSeleccionado = 'google/gemini-2.5-flash';

    // ─── Excel ───────────────────────────────────────────────────────────────
    public $archivoExcel;
    public bool  $modalExcel       = false;
    public array $resultadosExcel  = [];
    public bool  $excelProcesado   = false;

    // ─── Estado del proceso ──────────────────────────────────────────────────
    public string $mensajeExito = '';
    public string $mensajeError = '';
    public array  $procesandoIds = [];

    protected $paginationTheme = 'tailwind';

    public function updatingBusqueda()       { $this->resetPage(); }
    public function updatingFiltroEstadoIa() { $this->resetPage(); }
    public function updatingFiltroMatchResult() { $this->resetPage(); }
    public function updatingFiltroMandante() { $this->resetPage(); }

    // ─── Query principal ─────────────────────────────────────────────────────

    public function getDocumentosProperty()
    {
        $query = DocumentoCargado::query()
            ->with([
                'contratista:id,razon_social,rut',
                'mandante:id,razon_social',
                'datoExtraidoIa',
                'entidad',
            ])
            // Solo docs cuya regla tiene al menos 1 campo IA activo configurado
            ->whereExists(function ($sub) {
                $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('ia_campos_configuracion')
                    ->whereColumn('ia_campos_configuracion.regla_documental_id', 'documentos_cargados.regla_documental_id_origen')
                    ->where('ia_campos_configuracion.is_active', true);
            })
            // Solo docs activos, sin resultado final aún
            ->whereNotIn('estado_validacion', ['Archivado', 'Archivado-Revalidado'])
            ->whereNull('resultado_validacion');

        // Filtros
        if ($this->busqueda) {
            $query->where(function ($q) {
                $q->where('nombre_documento_snapshot', 'like', "%{$this->busqueda}%")
                  ->orWhereHas('contratista', fn($c) => $c->where('razon_social', 'like', "%{$this->busqueda}%")
                                                           ->orWhere('rut', 'like', "%{$this->busqueda}%"));
            });
        }

        if ($this->filtroMandante) {
            $query->where('mandante_id', $this->filtroMandante);
        }

        if ($this->filtroEstadoIa === 'sin_procesar') {
            $query->doesntHave('datoExtraidoIa');
        } elseif ($this->filtroEstadoIa) {
            $query->whereHas('datoExtraidoIa', fn($q) => $q->where('estado', $this->filtroEstadoIa));
        }

        if ($this->filtroMatchResult) {
            $query->whereHas('datoExtraidoIa', fn($q) => $q->where('match_calculado', $this->filtroMatchResult));
        }

        if ($this->filtroFuenteIa) {
            $query->whereHas('datoExtraidoIa', fn($q) => $q->where('fuente', $this->filtroFuenteIa));
        }

        return $query->orderBy('created_at', 'desc')->paginate(25);
    }

    public function getMandantesProperty()
    {
        return \App\Models\Mandante::orderBy('razon_social')->get(['id', 'razon_social']);
    }

    public function getCamposDisponiblesProperty(): array
    {
        return \App\Services\IaCamposDisponibles::todos();
    }

    public function getFormatosMuestraProperty()
    {
        return \App\Models\FormatoDocumentoMuestra::where('is_active', true)->orderBy('nombre')->get();
    }

    public function getReglasProperty()
    {
        $q = \App\Models\ReglaDocumental::query()
            ->join('nombre_documentos', 'reglas_documentales.nombre_documento_id', '=', 'nombre_documentos.id')
            ->where('reglas_documentales.is_active', true)
            ->orderBy('nombre_documentos.nombre')
            ->select(
                'reglas_documentales.id',
                'nombre_documentos.nombre as nombre_documento',
                'reglas_documentales.mandante_id',
            );

        // Filtrar por Principal si está seleccionado
        if ($this->configMandanteId) {
            $q->where('reglas_documentales.mandante_id', $this->configMandanteId);
        }

        return $q->get();
    }

    /**
     * Carga los criterios de la regla seleccionada para configurar extracción IA.
     * Cada criterio tiene: id, nombre_criterio, aclaracion (hint), sub_criterios (valor esperado).
     */
    public function getCriteriosReglaProperty(): \Illuminate\Support\Collection
    {
        if (!$this->configReglaId) return collect();

        return \App\Models\ReglaDocumentalCriterio::with([
            'criterioEvaluacion',
            'subCriterios',
            'aclaracionCriterio',
        ])
        ->where('regla_documental_id', $this->configReglaId)
        ->get();
    }

    public function updatedConfigMandanteId(): void
    {
        // Al cambiar el Mandante, limpiar la regla seleccionada
        $this->configReglaId             = null;
        $this->configCamposActivos       = [];
        $this->configRequeridos          = [];
        $this->configCriteriosActivos    = [];
        $this->configCriteriosRequeridos = [];
        $this->configCriteriosDescripciones = [];
        $this->configCriteriosFormatos   = [];
    }

    public function updatedConfigReglaId(): void
    {
        $this->cargarConfigRegla();
    }

    public function cargarConfigRegla(): void
    {
        if (!$this->configReglaId) {
            $this->configCamposActivos       = [];
            $this->configRequeridos          = [];
            $this->configCriteriosActivos    = [];
            $this->configCriteriosRequeridos = [];
            $this->configCriteriosDescripciones = [];
            $this->configCriteriosFormatos   = [];
            return;
        }

        $campos = IaCampoConfiguracion::where('regla_documental_id', $this->configReglaId)
            ->where('is_active', true)->get();

        // Separar campos de documentos_cargados vs criterios
        $columnas  = $campos->filter(fn($c) => !str_starts_with($c->campo_clave, 'criterio_'));
        $criterios = $campos->filter(fn($c) => str_starts_with($c->campo_clave, 'criterio_'));

        $this->configCamposActivos       = $columnas->pluck('campo_clave')->values()->toArray();
        $this->configRequeridos          = $columnas->where('es_requerido', true)->pluck('campo_clave')->values()->toArray();
        $this->configCriteriosActivos    = $criterios->pluck('criterio_evaluacion_id')->map(fn($id) => (string)$id)->values()->toArray();
        $this->configCriteriosRequeridos = $criterios->where('es_requerido', true)->pluck('criterio_evaluacion_id')->map(fn($id) => (string)$id)->values()->toArray();
        
        $this->configCriteriosDescripciones = [];
        $this->configCriteriosFormatos   = [];
        foreach ($criterios as $c) {
            $key = (string) $c->criterio_evaluacion_id;
            $this->configCriteriosDescripciones[$key] = $c->descripcion_ia ?? '';
            $this->configCriteriosFormatos[$key] = $c->formato_muestra_id ?? '';
        }
    }

    public function guardarConfiguracion(): void
    {
        $this->configMensaje      = '';
        $this->configMensajeError = '';
        $this->configGuardado     = false;

        if (!$this->configReglaId) {
            $this->configMensajeError = 'Debes seleccionar una regla documental.';
            return;
        }

        $clavesValidas = \App\Services\IaCamposDisponibles::claves();

        try {
            $totalGuardados = \Illuminate\Support\Facades\DB::transaction(function () use ($clavesValidas) {
                // Eliminar configuración anterior para esta regla
                IaCampoConfiguracion::where('regla_documental_id', $this->configReglaId)->delete();

                $totalGuardados = 0;

                // ── 1. Campos de documentos_cargados ────────────────────────────────
                foreach ($this->configCamposActivos as $clave) {
                    if (!in_array($clave, $clavesValidas)) continue;

                    $def = \App\Services\IaCamposDisponibles::definicion($clave);

                    IaCampoConfiguracion::create([
                        'regla_documental_id'  => $this->configReglaId,
                        'campo_clave'          => $clave,
                        'etiqueta'             => $def['etiqueta'],
                        'tipo_dato'            => $def['tipo_dato'],
                        'es_requerido'         => in_array($clave, $this->configRequeridos),
                        'mapea_a_columna'      => $def['mapea_columna'],
                        'descripcion_ia'       => $def['descripcion'],
                        'valor_esperado'       => null,
                        'criterio_evaluacion_id' => null,
                        'orden'                => array_search($clave, $clavesValidas),
                        'is_active'            => true,
                    ]);
                    $totalGuardados++;
                }

                // ── 2. Criterios de la regla ─────────────────────────────────────────
                foreach ($this->configCriteriosActivos as $criterioIdStr) {
                    $criterioId = (int) $criterioIdStr;

                    // Cargar el criterio con sus relaciones
                    $rdCriterio = \App\Models\ReglaDocumentalCriterio::with([
                        'criterioEvaluacion',
                        'subCriterios',
                        'aclaracionCriterio',
                    ])->where('regla_documental_id', $this->configReglaId)
                      ->where('criterio_evaluacion_id', $criterioId)
                      ->first();

                    if (!$rdCriterio || !$rdCriterio->criterioEvaluacion) continue;

                    $criterio   = $rdCriterio->criterioEvaluacion;
                    $aclaracion = $rdCriterio->aclaracionCriterio;

                    // El nuevo paradigma booleano dicta que el valor esperado de un Criterio es siempre "SI".
                    $valoresEsperados = 'SI';

                    // Hint para el prompt: texto manual (Asem Admin) o un default sugerido
                    $hintPrompt = $this->configCriteriosDescripciones[$criterioIdStr] ?? 
                                  ($aclaracion?->titulo ? "{$criterio->nombre_criterio}. Aclaración: {$aclaracion->titulo}" : $criterio->nombre_criterio);

                    IaCampoConfiguracion::create([
                        'regla_documental_id'    => $this->configReglaId,
                        'campo_clave'            => 'criterio_' . $criterioId,
                        'etiqueta'               => $criterio->nombre_criterio,
                        'tipo_dato'              => 'texto',
                        'es_requerido'           => in_array($criterioIdStr, $this->configCriteriosRequeridos),
                        'mapea_a_columna'        => null, // Los criterios NO escriben en documentos_cargados
                        'descripcion_ia'         => $hintPrompt,
                        'valor_esperado'         => $valoresEsperados ?: null,
                        'criterio_evaluacion_id' => $criterioId,
                        'formato_muestra_id'     => empty($this->configCriteriosFormatos[$criterioIdStr]) ? null : $this->configCriteriosFormatos[$criterioIdStr],
                        'orden'                  => 100 + $criterioId,
                        'is_active'              => true,
                    ]);
                    $totalGuardados++;
                }

                return $totalGuardados;
            });

            $this->configMensaje  = "✓ Configuración guardada: {$totalGuardados} campos (columnas + criterios).";
            $this->configGuardado = true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error guardando configuración IA: ' . $e->getMessage());
            $this->configMensajeError = 'Ocurrió un error al guardar la configuración: ' . $e->getMessage();
        }
    }

    // ─── Acciones individuales ────────────────────────────────────────────────

    public function enviarAIa(int $docId): void
    {
        $this->limpiarMensajes();
        $this->procesandoIds[] = $docId;

        $documento = DocumentoCargado::find($docId);
        if (!$documento) {
            $this->mensajeError = "Documento ID {$docId} no encontrado.";
            return;
        }

        try {
            $ia = app(IaExtraccionService::class);
            $ia->procesarDocumento($documento, $this->modeloIaSeleccionado);
            $this->mensajeExito = "✓ Documento \"{$documento->nombre_documento_snapshot}\" procesado por IA correctamente usando {$this->modeloIaSeleccionado}.";
        } catch (\Exception $e) {
            Log::error("[RevisionIaAcreditacion] Error enviarAIa doc {$docId}: " . $e->getMessage());
            $this->mensajeError = "Error al procesar el documento: " . $e->getMessage();
        }

        $this->procesandoIds = array_filter($this->procesandoIds, fn($id) => $id !== $docId);
    }

    public function calcularMatch(int $docId): void
    {
        $this->limpiarMensajes();
        try {
            $doc = DocumentoCargado::with('datoExtraidoIa')->findOrFail($docId);
            
            if (!$doc->datoExtraidoIa) {
                $this->mensajeError = "El documento no tiene extracción previa.";
                return;
            }

            $matchService = app(\App\Services\IaMatchService::class);
            $matchService->calcularMatch($doc->datoExtraidoIa);

            $this->mensajeExito = "✓ Match calculado correctamente para el documento \"{$doc->nombre_documento_snapshot}\".";
        } catch (\Exception $e) {
            Log::error("[RevisionIaAcreditacion] Error calcularMatch doc {$docId}: " . $e->getMessage());
            $this->mensajeError = 'Error al calcular match: ' . $e->getMessage();
        }
    }

    public function revertirIa(int $docId): void
    {
        $this->limpiarMensajes();
        try {
            $doc = DocumentoCargado::with('datoExtraidoIa')->findOrFail($docId);
            if ($doc->datoExtraidoIa) {
                // Prevenir revertir si ya está confirmado (por seguridad)
                if ($doc->datoExtraidoIa->estado === 'CONFIRMADO') {
                    $this->mensajeError = "No se puede revertir un documento que ya fue confirmado y escrito en la base de datos.";
                    return;
                }
                
                $doc->datoExtraidoIa->delete();
                $this->mensajeExito = "✓ Extracción IA revertida. Ya puede volver a procesar el documento.";
            }
        } catch (\Exception $e) {
            Log::error("[RevisionIaAcreditacion] Error al revertir IA doc {$docId}: " . $e->getMessage());
            $this->mensajeError = 'Error al revertir: ' . $e->getMessage();
        }
    }

    public function verDetalle(int $docId): void
    {
        $this->limpiarMensajes();
        $this->detalleDocId = $docId;
        $doc = DocumentoCargado::with('datoExtraidoIa')->find($docId);
        $this->detalleIaId  = $doc?->datoExtraidoIa?->id;
        $this->modalDetalle = true;
    }

    public function confirmarMatchIndividual(int $datoIaId): void
    {
        $this->limpiarMensajes();
        $datoIa = DatoExtraidoIa::find($datoIaId);

        if (!$datoIa || $datoIa->estado !== 'MATCH_CALCULADO') {
            $this->mensajeError = 'El dato IA no está listo para confirmar.';
            return;
        }

        try {
            $matchService = app(IaMatchService::class);
            $matchService->confirmarMatch($datoIa, Auth::id());
            $this->mensajeExito = '✓ Resultado confirmado y escrito en el documento correctamente.';
            $this->modalDetalle = false;
        } catch (\Exception $e) {
            Log::error("[RevisionIaAcreditacion] Error confirmar match datoIaId={$datoIaId}: " . $e->getMessage());
            $this->mensajeError = 'Error al confirmar: ' . $e->getMessage();
        }
    }

    public function rechazarResultadoIa(int $datoIaId): void
    {
        $this->limpiarMensajes();
        $datoIa = DatoExtraidoIa::find($datoIaId);

        if (!$datoIa) return;

        $datoIa->update(['estado' => 'RECHAZADO_OPERADOR']);
        $this->mensajeExito = 'Resultado IA marcado como rechazado por el operador. El documento queda sin procesar.';
        $this->modalDetalle = false;
    }

    // ─── Acciones masivas ─────────────────────────────────────────────────────

    public function enviarSeleccionadosAIa(): void
    {
        $this->limpiarMensajes();

        if (empty($this->seleccionados)) {
            $this->mensajeError = 'No hay documentos seleccionados.';
            return;
        }

        $ia        = app(IaExtraccionService::class);
        $ok        = 0;
        $errores   = 0;

        foreach ($this->seleccionados as $docId) {
            $documento = DocumentoCargado::find($docId);
            if (!$documento) continue;
            try {
                $ia->procesarDocumento($documento, $this->modeloIaSeleccionado);
                $ok++;
            } catch (\Exception $e) {
                Log::error("[RevisionIaAcreditacion] Error masivo doc {$docId}: " . $e->getMessage());
                $errores++;
            }
        }

        $this->seleccionados   = [];
        $this->seleccionarTodos = false;
        $this->mensajeExito    = "✓ Procesados: {$ok} documentos." . ($errores > 0 ? " Errores: {$errores}." : '');
    }

    public function confirmarTodosAprobados(): void
    {
        $this->limpiarMensajes();
        $matchService = app(IaMatchService::class);
        $ok = 0;

        $datos = DatoExtraidoIa::where('estado', 'MATCH_CALCULADO')
            ->where('match_calculado', 'APROBADO')
            ->get();

        foreach ($datos as $datoIa) {
            try {
                $matchService->confirmarMatch($datoIa, Auth::id());
                $ok++;
            } catch (\Exception $e) {
                Log::error("[RevisionIaAcreditacion] Error confirmar masivo datoIaId={$datoIa->id}: " . $e->getMessage());
            }
        }

        $this->mensajeExito = "✓ Confirmados {$ok} documentos APROBADOS por IA.";
    }

    // ─── Excel ────────────────────────────────────────────────────────────────

    public function abrirModalExcel(): void
    {
        $this->reset(['archivoExcel', 'resultadosExcel', 'excelProcesado']);
        $this->modalExcel = true;
    }

    public function subirExcel(): void
    {
        $this->validate([
            'archivoExcel' => 'required|file|mimes:xlsx,xls|max:10240',
        ], [
            'archivoExcel.required' => 'Debe seleccionar un archivo Excel.',
            'archivoExcel.mimes'    => 'Solo se aceptan archivos .xlsx o .xls.',
        ]);

        $this->limpiarMensajes();

        try {
            $importer = new DatosIaExcelImport(app(IaMatchService::class));
            Excel::import($importer, $this->archivoExcel->getRealPath());

            $this->resultadosExcel = $importer->resultados;
            $this->excelProcesado  = true;
            $this->mensajeExito    = "Excel procesado: {$importer->procesados} correctos, {$importer->errores} errores, {$importer->omitidos} omitidos.";
        } catch (\Exception $e) {
            Log::error('[RevisionIaAcreditacion] Error importar Excel: ' . $e->getMessage());
            $this->mensajeError = 'Error al procesar el Excel: ' . $e->getMessage();
        }
    }

    public function descargarPlantillaExcel(int $reglaDocumentalId): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $campos = IaCampoConfiguracion::where('regla_documental_id', $reglaDocumentalId)
            ->where('is_active', true)
            ->orderBy('orden')
            ->get();

        $headers = ['documento_cargado_id', ...$campos->pluck('campo_clave')->toArray()];
        $etiquetas = ['ID del Documento (obligatorio)', ...$campos->pluck('etiqueta')->toArray()];

        return response()->streamDownload(function () use ($headers, $etiquetas) {
            $fp = fopen('php://output', 'w');
            fputcsv($fp, $etiquetas);  // Fila de etiquetas legibles
            fputcsv($fp, $headers);     // Fila de claves técnicas (esta es la que se importa)
            fclose($fp);
        }, 'plantilla_ia_' . $reglaDocumentalId . '.csv');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function limpiarMensajes(): void
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';
    }

    public function render()
    {
        return view('livewire.asem.revision-ia-acreditacion', [
            'documentos'       => $this->documentos,
            'mandantes'        => $this->mandantes,
            'camposDisponibles'=> $this->camposDisponibles,
            'reglas'           => $this->reglas,
        ])->layout('layouts.app');
    }
}
