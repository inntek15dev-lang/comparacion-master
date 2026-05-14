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
use Carbon\Carbon;

class SincronizacionDocumentosImport implements WithMultipleSheets
{
    use Importable;

    public $successes   = 0;
    public $vivos       = 0;
    public $archivados  = 0; // docs VIEJOS archivados (el entrante los superó)
    public $descartados = 0; // docs ENTRANTES archivados (el existente era mejor)
    public $failures    = [];

    public function sheets(): array
    {
        return [
            'Sincronización de Documentos' => new SincronizacionDocumentosDataSheetImport($this),
        ];
    }
}

class SincronizacionDocumentosDataSheetImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, SkipsOnFailure, SkipsEmptyRows
{
    use RemembersRowNumber;

    private $parent;
    private $usuarioCargaId;
    private $usuarioMigracionId = 54;
    private static $cache = [];

    public function __construct(SincronizacionDocumentosImport $parent)
    {
        $this->parent         = $parent;
        $this->usuarioCargaId = auth()->id();
        $this->initializeCache();
    }

    private function initializeCache()
    {
        if (empty(self::$cache)) {
            self::$cache['mandantes']    = Mandante::pluck('id', 'razon_social');
            self::$cache['contratistas'] = Contratista::pluck('id', 'razon_social');
        }
    }

    private static function cleanCompositeName(?string $value): string
    {
        if (!$value) return '';
        $value = Str::of($value)->replace([' — ', ' – ', ' -- ', ' - '], ' @SEP@ ')->trim();
        $parts = explode(' @SEP@ ', $value);
        return trim(end($parts));
    }

    // ─── UTILIDAD: Determina si un documento es "bueno" (Aprobado + Vigente hoy) ───
    // Aplica igual para el doc entrante y el existente.
    // Un doc con fecha de vencimiento ya expirada es "malo", aunque venga Aprobado.
    private function esDocumentoBueno(?string $resultado, $fechaVencimiento): bool
    {
        if ($resultado !== 'Aprobado') {
            return false;
        }
        // Sin vencimiento → indefinido → vigente
        if (is_null($fechaVencimiento)) {
            return true;
        }
        try {
            $fecha = $fechaVencimiento instanceof Carbon
                ? $fechaVencimiento
                : Carbon::parse($fechaVencimiento);
            return $fecha->gte(Carbon::today());
        } catch (\Exception $e) {
            return false;
        }
    }

    public function model(array $row)
    {
        $cleanedRow = $row;
        foreach ($cleanedRow as $key => $value) {
            if (is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed === "SIN DATOS/MIGRACION" || $trimmed === "") {
                    $cleanedRow[$key] = null;
                } else {
                    $cleanedRow[$key] = $trimmed;
                }
            }
        }

        // ─── NORMALIZACIÓN DE CLAVES ───
        // Maatwebsite transforma los headers a snake_case eliminando caracteres especiales.
        // Limpiamos asteriscos residuales que pueden quedar en las claves generadas.
        $normalizedRow = [];
        foreach ($cleanedRow as $k => $v) {
            $cleanKey = str_replace('*', '', $k);
            $cleanKey = preg_replace('/\s+/', '_', trim($cleanKey));
            $normalizedRow[$cleanKey] = $v;
        }
        // Merge: las claves normalizadas tienen prioridad, pero mantenemos las originales como fallback
        $cleanedRow = array_merge($cleanedRow, $normalizedRow);

        // ─── MAPEO DE HEADINGS ───
        $mandanteName    = $cleanedRow['mandante']              ?? null;
        $contratistaName = $cleanedRow['contratista']           ?? null;
        $uoFullName      = $cleanedRow['unidad_organizacional'] ?? null;
        $entidadType     = $cleanedRow['tipo_de_entidad']       ?? null;
        $reglaFullName   = $cleanedRow['regla_documental']      ?? null;

        // ─── RESOLVER MANDANTE ───
        $mandanteId = self::$cache['mandantes'][$mandanteName] ?? null;
        if (!$mandanteId) {
            $mandanteId = Mandante::where('razon_social', 'LIKE', "%{$mandanteName}%")->value('id');
        }

        // ─── RESOLVER CONTRATISTA ───
        if ($contratistaName) {
            $contratistaName = str_replace(' (Sin ID_REGISTRO)', '', $contratistaName);
            $contratistaName = trim($contratistaName);
        }

        $contratistaId = null;
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
        if (!$contratistaId) {
            $contratistaId = self::$cache['contratistas'][$contratistaName] ?? null;
            if (!$contratistaId) {
                $contratistaId = Contratista::where('razon_social', 'LIKE', "%{$contratistaName}%")->value('id');
            }
        }

        // ─── RESOLVER UO (OPCIONAL) ───
        $uoName = self::cleanCompositeName($uoFullName);
        $uo     = null;
        if ($mandanteId && !empty($uoName)) {
            $uo = UnidadOrganizacionalMandante::where('mandante_id', $mandanteId)
                ->get()
                ->filter(fn($u) => $u->nombre_jerarquico === $uoName || $u->nombre_unidad === $uoName)
                ->first();
        }

        // ─── RESOLVER REGLA ───
        $reglaName = self::cleanCompositeName($reglaFullName);
        $regla     = null;
        if ($mandanteId) {
            $regla = ReglaDocumental::where('mandante_id', $mandanteId)
                ->with('nombreDocumento')
                ->get()
                ->filter(fn($r) => ($r->nombreDocumento->nombre ?? '') === $reglaName)
                ->first();
        }

        // ─── VALIDACIÓN DE CAMPOS OBLIGATORIOS ───
        $validator = Validator::make($cleanedRow, [
            'mandante'                    => 'required',
            'contratista'                 => 'required',
            'tipo_de_entidad'             => 'required',
            'idrutpatente_entidad'        => 'required',
            'regla_documental'            => 'required',
            'nombre_documento_snapshot'   => 'required|string|max:255',
            'nombre_archivo_fisico'       => 'required',
            'resultado_validacion_origen' => 'required|in:Aprobado,Rechazado',
        ]);

        if ($validator->fails() || !$mandanteId || !$contratistaId || !$regla) {
            $errors = $validator->errors()->all();
            if (!$mandanteId)    $errors[] = "Mandante '{$mandanteName}' no encontrado.";
            if (!$contratistaId) $errors[] = "Contratista '{$contratistaName}' no encontrado.";
            if (!empty($uoName) && $mandanteId && !$uo) $errors[] = "U.O. '{$uoName}' no encontrada para el Mandante.";
            if ($mandanteId && !$regla) $errors[] = "Regla Documental '{$reglaName}' no encontrada para el Mandante.";

            $this->parent->failures[] = [
                'row'       => $this->getRowNumber(),
                'attribute' => 'Validación',
                'errors'    => implode(' | ', $errors),
                'values'    => $row,
            ];
            return null;
        }

        // ─── VALIDAR ARCHIVO FÍSICO ───
        $carpetaIngesta      = 'importar_documentos_sincronizacion/';
        $nombreArchivoFisico = $cleanedRow['nombre_archivo_fisico'];
        $rutaOrigen          = $carpetaIngesta . $nombreArchivoFisico;

        if (!Storage::disk('public')->exists($rutaOrigen)) {
            $this->parent->failures[] = [
                'row'       => $this->getRowNumber(),
                'attribute' => 'nombre_archivo_fisico',
                'errors'    => "El archivo físico '{$nombreArchivoFisico}' no existe en 'storage/app/public/{$carpetaIngesta}'.",
                'values'    => $row,
            ];
            return null;
        }

        DB::beginTransaction();
        try {
            $entidadIdStr = $cleanedRow['idrutpatente_entidad'];
            $entidadId    = $this->resolverEntidadId($entidadType, $entidadIdStr, $contratistaId);

            if (!$entidadId) {
                throw new \Exception("No se encontró la entidad de tipo '{$entidadType}' con identificador '{$entidadIdStr}' para el contratista.");
            }

            // ─── RESOLVER VINCULACIÓN TRABAJADOR ───
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

            // ─── ENCRIPTADO Y ALMACENAMIENTO ───
            $baseDir   = strtolower(class_basename($entidadType)) . "s";
            if (str_ends_with($baseDir, 'rs')) $baseDir = str_replace('rs', 'res', $baseDir);
            $directory = $baseDir . '/' . $entidadId;

            $mimeType      = Storage::disk('public')->mimeType($rutaOrigen);
            $tamanoArchivo = Storage::disk('public')->size($rutaOrigen);
            $contenidoPdf  = Storage::disk('public')->get($rutaOrigen);

            /** @var EncryptionService $encryptionService */
            $encryptionService = app(EncryptionService::class);
            $rutaArchivoFinal  = $encryptionService->encryptFromContent($contenidoPdf, $directory);

            // ─── PASO 1: CALIDAD DEL DOC ENTRANTE ───
            $resultadoEntrante = $cleanedRow['resultado_validacion_origen']; // Aprobado / Rechazado
            $fechaVencEntrante = $this->formatDate($cleanedRow['fecha_vencimiento'] ?? null);
            $entranteEsBueno   = $this->esDocumentoBueno($resultadoEntrante, $fechaVencEntrante);

            // ─── PASO 2: BUSCAR DOC VIVO EXISTENTE ───
            $docVivoExistente = DocumentoCargado::where('entidad_id', $entidadId)
                ->where('entidad_type', $entidadType)
                ->where('regla_documental_id_origen', $regla->id)
                ->whereNotIn('estado_validacion', ['Archivado', 'Archivado-Revalidado'])
                ->latest('created_at')
                ->first();

            // ─── PASO 3: CALIDAD DEL EXISTENTE ───
            $existenteEsBueno = false;
            if ($docVivoExistente) {
                $existenteEsBueno = $this->esDocumentoBueno(
                    $docVivoExistente->resultado_validacion,
                    $docVivoExistente->fecha_vencimiento
                );
            }

            // ─── PASO 4: MATRIZ DE DECISIÓN ───
            // Entrante gana si: es bueno (Aprobado + vigente), O si el existente también es malo.
            // Existente gana solo si: el existente es bueno Y el entrante es malo.
            $entranteGana = $entranteEsBueno || !$existenteEsBueno;

            $reemplazaAId     = null;
            $estadoFinalNuevo = 'Revisado';

            if ($entranteGana) {
                if ($docVivoExistente) {
                    $docVivoExistente->update(['estado_validacion' => 'Archivado']);
                    $reemplazaAId = $docVivoExistente->id;
                    $this->parent->archivados++;
                }
                $estadoFinalNuevo = 'Revisado';
                $this->parent->vivos++;
            } else {
                // El existente gana → el entrante se archiva (se guarda por trazabilidad)
                $estadoFinalNuevo = 'Archivado';
                $this->parent->descartados++;
            }

            $asemValidadorId = $cleanedRow['id_validador_asem'] ?? $this->usuarioMigracionId;

            // ─── SNAPSHOT DE CRITERIOS (solo si el doc queda vivo) ───
            $criteriosSnapshot = null;
            if ($estadoFinalNuevo === 'Revisado') {
                $reglaConCriterios = ReglaDocumental::with([
                    'criterios.criterioEvaluacion',
                    'criterios.subCriterio',
                    'criterios.textoRechazo',
                    'criterios.aclaracionCriterio',
                ])->find($regla->id);

                if ($reglaConCriterios && $reglaConCriterios->criterios) {
                    $criteriosSnapshot = $reglaConCriterios->criterios->map(fn($cp) => [
                        'criterio'      => $cp->criterioEvaluacion->nombre_criterio ?? 'Criterio no encontrado',
                        'texto_rechazo' => $cp->textoRechazo->texto_rechazo         ?? null,
                        'sub_criterio'  => $cp->subCriterio->nombre                 ?? null,
                        'aclaracion'    => $cp->aclaracionCriterio->texto_aclaracion ?? null,
                    ])->toArray();
                }
            }

            // ─── CREAR REGISTRO (siempre, para trazabilidad completa) ───
            $documento = DocumentoCargado::create([
                'contratista_id'                 => $contratistaId,
                'mandante_id'                    => $mandanteId,
                'unidad_organizacional_id'       => $uo?->id,
                'trabajador_vinculacion_id'      => $trabajadorVinculacionId,
                'entidad_id'                     => $entidadId,
                'entidad_type'                   => $entidadType,
                'regla_documental_id_origen'     => $regla->id,
                'usuario_carga_id'               => $this->usuarioCargaId,
                'ruta_archivo'                   => $rutaArchivoFinal,
                'is_encrypted'                   => true,
                'nombre_original_archivo'        => $nombreArchivoFisico,
                'mime_type'                      => $mimeType,
                'tamano_archivo'                 => $tamanoArchivo,
                'fecha_emision'                  => $this->formatDate($cleanedRow['fecha_emision'] ?? null),
                'fecha_vencimiento'              => $fechaVencEntrante,
                'periodo'                        => $cleanedRow['periodo'] ?? null,
                'estado_validacion'              => $estadoFinalNuevo,
                'resultado_validacion'           => $resultadoEntrante,
                'reemplaza_a_id'                 => $reemplazaAId,
                'fecha_validacion'               => now(),
                'asem_validador_id'              => $asemValidadorId,
                'observacion_validador'          => $cleanedRow['observacion_validador'] ?? null,
                'nombre_documento_snapshot'      => $cleanedRow['nombre_documento_snapshot'],
                'tipo_vencimiento_snapshot'      => 'SINCRONIZACION',
                'valida_emision_snapshot'        => false,
                'valida_vencimiento_snapshot'    => false,
                'valor_nominal_snapshot'         => null,
                'observacion_documento_snapshot' => 'SINCRONIZACION DESDE SISTEMA OBSOLETO',
                'criterios_snapshot'             => $criteriosSnapshot,
            ]);

            DB::commit();
            $this->parent->successes++;
            return $documento;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->parent->failures[] = [
                'row'       => $this->getRowNumber(),
                'attribute' => 'General',
                'errors'    => $e->getMessage(),
                'values'    => $row,
            ];
            return null;
        }
    }

    private function resolverEntidadId(string $type, string $identifier, int $contratistaId): ?int
    {
        $identifier = trim($identifier);
        return match ($type) {
            'App\Models\Contratista' => \App\Models\Contratista::where('rut', $identifier)->value('id'),
            'App\Models\Trabajador'  => \App\Models\Trabajador::where('rut', $identifier)->where('contratista_id', $contratistaId)->value('id'),
            'App\Models\Vehiculo'    => \App\Models\Vehiculo::where('patente', $identifier)->where('contratista_id', $contratistaId)->value('id'),
            'App\Models\Maquinaria'  => \App\Models\Maquinaria::where('numero_serie', $identifier)->where('contratista_id', $contratistaId)->value('id'),
            'App\Models\Embarcacion' => \App\Models\Embarcacion::where('matricula', $identifier)->where('contratista_id', $contratistaId)->value('id'),
            default                  => null,
        };
    }

    private function formatDate($value, bool $dateTime = false): ?string
    {
        if (!$value) return null;
        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                    ->format($dateTime ? 'Y-m-d H:i:s' : 'Y-m-d');
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
                'row'       => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors'    => implode(', ', $failure->errors()),
                'values'    => $failure->values(),
            ];
        }
    }

    public function chunkSize(): int { return 100; }
}
