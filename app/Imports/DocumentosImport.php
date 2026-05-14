<?php

namespace App\Imports;

use App\Models\DocumentoCargado;
use App\Models\Mandante;
use App\Models\Contratista;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\ReglaDocumental;
use App\Services\EncryptionService;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Illuminate\Support\Str;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class DocumentosImport implements WithMultipleSheets
{
    use Importable;

    public $successes = 0;
    public $failures = [];

    public function sheets(): array
    {
        return [
            'Migración de Documentos' => new DocumentosDataSheetImport($this),
        ];
    }
}

class DocumentosDataSheetImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, SkipsOnFailure, SkipsEmptyRows
{
    use RemembersRowNumber;

    private $parent;
    private $usuarioCargaId;
    private $usuarioMigracionId = 54;
    private static $cache = [];

    public function __construct(DocumentosImport $parent)
    {
        $this->parent = $parent;
        $this->usuarioCargaId = auth()->id();
        $this->initializeCache();
    }

    private function initializeCache()
    {
        if (empty(self::$cache)) {
            self::$cache['mandantes'] = Mandante::pluck('id', 'razon_social');
            self::$cache['contratistas'] = Contratista::pluck('id', 'razon_social');
        }
    }

    private static function cleanCompositeName(?string $value): string
    {
        if (!$value) return '';
        // Soportar EM DASH (—), EN DASH (–) y guion doble (--) o simple (-) con espacios
        $value = Str::of($value)->replace([' — ', ' – ', ' -- ', ' - '], ' @SEP@ ')->trim();
        $parts = explode(' @SEP@ ', $value);
        return trim(end($parts));
    }

    public function model(array $row)
    {
        $cleanedRow = $row;
        foreach ($cleanedRow as $key => $value) {
            if (is_string($value)) {
                $trimmed = trim($value);
                // Si es un campo opcional con el tag de migración o vacío, lo tratamos como null
                if ($trimmed === "SIN DATOS/MIGRACION" || $trimmed === "") {
                    $cleanedRow[$key] = null;
                } else {
                    $cleanedRow[$key] = $trimmed;
                }
            }
        }

        // --- MAPEO DE HEADINGS A LLAVES INTERNAS ---
        $mandanteName = $cleanedRow['mandante'] ?? null;
        $contratistaName = $cleanedRow['contratista'] ?? null;
        $uoFullName = $cleanedRow['unidad_organizacional'] ?? null;
        $entidadType = $cleanedRow['tipo_de_entidad'] ?? null;
        $entidadIdStr = $cleanedRow['idrutpatente_entidad'] ?? null;
        $reglaFullName = $cleanedRow['regla_documental'] ?? null;

        // Intentar encontrar el Mandante (búsqueda exacta o parcial si viene con basura)
        $mandanteId = self::$cache['mandantes'][$mandanteName] ?? null;
        if (!$mandanteId) {
            // Intento de rescate si el usuario puso el nombre corto o el que aparece en la UI
            $mandanteId = Mandante::where('razon_social', 'LIKE', "%{$mandanteName}%")->value('id');
        }

        // Limpiar el nombre del contratista por si viene con el sufijo " (Sin ID_REGISTRO)"
        if ($contratistaName) {
            $contratistaName = str_replace(' (Sin ID_REGISTRO)', '', $contratistaName);
            $contratistaName = trim($contratistaName);
        }

        $contratistaId = null;
        // 1. Intentar buscar por id_registro (ya que ahora el Excel manda directamente el ID_REGISTRO)
        if ($mandanteId && is_numeric($contratistaName)) {
            $cuo = \App\Models\ContratistaUnidadOrganizacional::select('contratista_unidad_organizacional.contratista_id')
                ->join('unidades_organizacionales_mandante as uo', 'uo.id', '=', 'contratista_unidad_organizacional.unidad_organizacional_mandante_id')
                ->where('uo.mandante_id', $mandanteId)
                ->where('contratista_unidad_organizacional.id_registro', $contratistaName)
                ->first();
            if ($cuo) {
                $contratistaId = $cuo->contratista_id;
            }
        }

        // 2. Fallback a búsqueda por razón social (por si escribió a mano o usó uno sin ID_REGISTRO)
        if (!$contratistaId) {
            $contratistaId = self::$cache['contratistas'][$contratistaName] ?? null;
            if (!$contratistaId) {
                $contratistaId = Contratista::where('razon_social', 'LIKE', "%{$contratistaName}%")->value('id');
            }
        }

        // --- RESOLUCIÓN DE UO EN MEMORIA (OPCIONAL para migración histórica) ---
        $uoName = self::cleanCompositeName($uoFullName);
        $uo = null;
        if ($mandanteId && !empty($uoName)) {
            $uo = UnidadOrganizacionalMandante::where('mandante_id', $mandanteId)
                ->get()
                ->filter(function ($u) use ($uoName) {
                    return $u->nombre_jerarquico === $uoName || $u->nombre_unidad === $uoName;
                })
                ->first();
        }

        // --- RESOLUCIÓN DE REGLA EN MEMORIA ---
        $reglaName = self::cleanCompositeName($reglaFullName);
        $regla = null;
        if ($mandanteId) {
            $regla = ReglaDocumental::where('mandante_id', $mandanteId)
                ->with('nombreDocumento')
                ->get()
                ->filter(function ($r) use ($reglaName) {
                    return ($r->nombreDocumento->nombre ?? '') === $reglaName;
                })
                ->first();
        }

        $validator = Validator::make($cleanedRow, [
            'mandante' => 'required',
            'contratista' => 'required',
            'unidad_organizacional' => 'nullable',
            'tipo_de_entidad' => 'required',
            'idrutpatente_entidad' => 'required',
            'regla_documental' => 'required',
            'nombre_documento_snapshot' => 'required|string|max:255',
            'nombre_archivo_fisico' => 'required',
            'fecha_vencimiento' => 'nullable',
            'estado_validacion' => 'required',
            'resultado_validacion' => 'nullable|in:Aprobado,Rechazado',
        ]);

        if ($validator->fails() || !$mandanteId || !$contratistaId || !$regla) {
            $errors = $validator->errors()->all();
            if (!$mandanteId) $errors[] = "Mandante '{$mandanteName}' no encontrado.";
            if (!$contratistaId) $errors[] = "Contratista '{$contratistaName}' no encontrado.";
            if (!empty($uoName) && $mandanteId && !$uo) $errors[] = "U.O. '{$uoName}' no encontrada para el Mandante seleccionado.";
            if ($mandanteId && !$regla) $errors[] = "Regla Documental '{$reglaName}' no encontrada para el Mandante seleccionado.";

            $this->parent->failures[] = [
                'row' => $this->getRowNumber(),
                'attribute' => 'Validación',
                'errors' => implode(' | ', $errors),
                'values' => $row,
            ];
            return null;
        }

        // --- VALIDACIÓN DE ARCHIVO FÍSICO ---
        $carpetaIngesta = 'importar_documentos_fisicos/';
        $nombreArchivoFisico = $cleanedRow['nombre_archivo_fisico'];
        $rutaOrigen = $carpetaIngesta . $nombreArchivoFisico;

        if (!Storage::disk('public')->exists($rutaOrigen)) {
            $this->parent->failures[] = [
                'row' => $this->getRowNumber(),
                'attribute' => 'nombre_archivo_fisico',
                'errors' => "El archivo físico '{$nombreArchivoFisico}' no existe en 'storage/app/public/{$carpetaIngesta}'.",
                'values' => $row,
            ];
            return null;
        }

        DB::beginTransaction();
        try {
            $entidadType = $cleanedRow['tipo_de_entidad'];
            $entidadIdStr = $cleanedRow['idrutpatente_entidad'];
            $entidadId = $this->resolverEntidadId($entidadType, $entidadIdStr, $contratistaId);

            if (!$entidadId) {
                throw new \Exception("No se encontró la entidad de tipo '{$entidadType}' con identificador '{$entidadIdStr}' para el contratista.");
            }

            // --- RESOLUCIÓN DE VINCULACIÓN TRABAJADOR (para trabajador_vinculacion_id) ---
            $trabajadorVinculacionId = null;
            if ($entidadType === 'App\\Models\\Trabajador' && $uo) {
                $vinculacion = \App\Models\TrabajadorVinculacion::where('trabajador_id', $entidadId)
                    ->where('unidad_organizacional_mandante_id', $uo->id)
                    ->where('is_active', true)
                    ->first();
                if ($vinculacion) {
                    $trabajadorVinculacionId = $vinculacion->id;
                }
            }

            // --- ENCRIPTADO Y ALMACENAMIENTO SEGURO (igual que importador histórico) ---
            // Los archivos se guardan en disk:local (fuera del webroot) encriptados con AES-256-CBC.
            $baseDir = strtolower(class_basename($entidadType)) . "s";
            if (str_ends_with($baseDir, 'rs')) $baseDir = str_replace('rs', 'res', $baseDir);
            $directory = $baseDir . '/' . $entidadId;

            // Obtener metadata antes de encriptar
            $mimeType      = Storage::disk('public')->mimeType($rutaOrigen);
            $tamanoArchivo = Storage::disk('public')->size($rutaOrigen);
            $contenidoPdf  = Storage::disk('public')->get($rutaOrigen);

            // Encriptar y guardar en disk:local
            /** @var EncryptionService $encryptionService */
            $encryptionService = app(EncryptionService::class);
            $rutaArchivoFinal  = $encryptionService->encryptFromContent($contenidoPdf, $directory);

            // --- VALIDACIÓN DE DUPLICADOS PENDIENTES ---
            // Regla: No se puede cargar un documento con resultado NULL si ya existe uno NULL para la misma regla y entidad.
            $resultadoValidacion = $cleanedRow['resultado_validacion'];
            if (empty($resultadoValidacion)) {
                $existePendiente = DocumentoCargado::where('entidad_id', $entidadId)
                    ->where('entidad_type', $entidadType)
                    ->where('regla_documental_id_origen', $regla->id)
                    ->whereNull('resultado_validacion')
                    ->exists();

                if ($existePendiente) {
                    throw new \Exception("Ya existe un documento pendiente de validación para esta entidad y regla documental. Debe procesar el existente antes de cargar uno nuevo.");
                }
            }

            $resultadoValidacion = $cleanedRow['resultado_validacion'];
            $estadoValidacion = $cleanedRow['estado_validacion'];
            $asemValidadorId = $cleanedRow['id_validador_asem'] ?? $this->usuarioMigracionId;

            // --- COHERENCIA DE ESTADOS MASIVOS ---
            // Si el documento importado no tiene un resultado final (no está evaluado),
            // lo forzamos a entrar limpio a la cola de validación.
            if (empty($resultadoValidacion)) {
                $estadoValidacion = 'Sin Asignar';
                $asemValidadorId = null;
            }

            // --- SNAPSHOT DE CRITERIOS DE REVISIÓN ---
            $criteriosSnapshot = empty($cleanedRow['snapshot_criterios_json']) ? null : json_decode($cleanedRow['snapshot_criterios_json'], true);

            // Si no hay snapshot en el Excel y el documento entra "Sin Asignar" para revisión,
            // capturamos el checklist frescamente desde la Regla Documental.
            if (empty($criteriosSnapshot) && empty($resultadoValidacion)) {
                $reglaConCriterios = ReglaDocumental::with([
                    'criterios.criterioEvaluacion', 
                    'criterios.subCriterio', 
                    'criterios.textoRechazo', 
                    'criterios.aclaracionCriterio'
                ])->find($regla->id);

                if ($reglaConCriterios && $reglaConCriterios->criterios) {
                    $criteriosSnapshot = $reglaConCriterios->criterios->map(function ($criterioPivote) {
                        return [
                            'criterio' => $criterioPivote->criterioEvaluacion->nombre_criterio ?? 'Criterio no encontrado',
                            'texto_rechazo' => $criterioPivote->textoRechazo->texto_rechazo ?? null,
                            'sub_criterio' => $criterioPivote->subCriterio->nombre ?? null,
                            'aclaracion' => $criterioPivote->aclaracionCriterio->texto_aclaracion ?? null,
                        ];
                    })->toArray();
                }
            }

            $documento = DocumentoCargado::create([
                'contratista_id' => $contratistaId,
                'mandante_id' => $mandanteId,
                'unidad_organizacional_id' => $uo?->id,
                // ── FIX: guardar vinculación del trabajador para docs no-perseguidores ──
                'trabajador_vinculacion_id' => $trabajadorVinculacionId,
                'entidad_id' => $entidadId,
                'entidad_type' => $entidadType,
                'regla_documental_id_origen' => $regla->id,
                'usuario_carga_id' => $this->usuarioCargaId,
                'ruta_archivo' => $rutaArchivoFinal,
                // ── FIX: marcar como encriptado porque encryptFromContent sí lo encripta ──
                'is_encrypted' => true,
                'nombre_original_archivo' => $nombreArchivoFisico,
                'mime_type' => $mimeType,
                'tamano_archivo' => $tamanoArchivo,
                'fecha_emision' => $this->formatDate($cleanedRow['fecha_emision'] ?? null),
                'fecha_vencimiento' => $this->formatDate($cleanedRow['fecha_vencimiento']),
                'periodo' => $cleanedRow['periodo'] ?? null,
                'estado_validacion' => $estadoValidacion,
                'resultado_validacion' => $resultadoValidacion,
                'asem_validador_id' => $asemValidadorId,
                'mandante_validador_id' => $cleanedRow['id_validador_mandante'] ?? null,
                'fecha_validacion' => $this->formatDate($cleanedRow['fecha_validacion_general'] ?? null, true),
                'fecha_validacion_asem' => $this->formatDate($cleanedRow['fecha_validacion_asem'] ?? null, true),
                'fecha_validacion_mandante' => $this->formatDate($cleanedRow['fecha_validacion_mandante'] ?? null, true),
                'observacion_validador' => $cleanedRow['observacion_validador'],
                'observacion_rechazo' => $cleanedRow['observacion_rechazo'],
                'observacion_interna_asem' => $cleanedRow['observacion_interna_asem'],
                'motivo_revalidacion' => $cleanedRow['motivo_revalidacion'],
                'nombre_documento_snapshot' => $cleanedRow['nombre_documento_snapshot'],
                'tipo_vencimiento_snapshot' => $cleanedRow['tipo_vencimiento_snapshot'] ?? 'MIGRADO',
                'valida_emision_snapshot' => false,
                'valida_vencimiento_snapshot' => false,
                'valor_nominal_snapshot' => null,
                'motivo_modificacion_vencimiento' => $cleanedRow['motivo_modif_vencimiento'],
                'criterios_snapshot' => $criteriosSnapshot,
                'observacion_documento_snapshot' => $cleanedRow['observacion_doc_snapshot'] ?? 'MIGRACION HISTORICA',
            ]);

            DB::commit();
            $this->parent->successes++;
            return $documento;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->parent->failures[] = [
                'row' => $this->getRowNumber(),
                'attribute' => 'General',
                'errors' => $e->getMessage(),
                'values' => $row,
            ];
            return null;
        }
    }

    private function resolverEntidadId($type, $identifier, $contratistaId)
    {
        $identifier = trim($identifier);
        
        switch ($type) {
            case 'App\Models\Contratista':
                // Para EMPRESA, el identificador es el RUT del contratista
                return \App\Models\Contratista::where('rut', $identifier)->value('id');
            case 'App\Models\Trabajador':
                return \App\Models\Trabajador::where('rut', $identifier)->where('contratista_id', $contratistaId)->value('id');
            case 'App\Models\Vehiculo':
                return \App\Models\Vehiculo::where('patente', $identifier)->where('contratista_id', $contratistaId)->value('id');
            case 'App\Models\Maquinaria':
                return \App\Models\Maquinaria::where('numero_serie', $identifier)->where('contratista_id', $contratistaId)->value('id');
            case 'App\Models\Embarcacion':
                return \App\Models\Embarcacion::where('matricula', $identifier)->where('contratista_id', $contratistaId)->value('id');
            default:
                return null;
        }
    }

    private function formatDate($value, $dateTime = false)
    {
        if (!$value) return null;
        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format($dateTime ? 'Y-m-d H:i:s' : 'Y-m-d');
            }
            return date($dateTime ? 'Y-m-d H:i:s' : 'Y-m-d', strtotime($value));
        } catch (\Exception $e) {
            return $value;
        }
    }

    public function rules(): array { return []; }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->parent->failures[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => implode(', ', $failure->errors()),
                'values' => $failure->values(),
            ];
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }
}