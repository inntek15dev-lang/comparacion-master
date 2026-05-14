<?php

namespace App\Http\Controllers;

use App\Imports\CertificadosHistoricosImport;
use App\Models\CarpetaVerificacion;
use App\Models\ContratistaUnidadOrganizacional;
use App\Models\DocumentoVerificacion;
use App\Models\RequisitoVerificacion;
use App\Services\CertificadoHistoricoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportarCertificadoHistoricoController extends Controller
{
    protected $service;
    protected $encryptionService;

    // Patrón de nombre: MANDANTE_ID_IDREGISTRO_AAAAMM_LUGAR_UO_NUMCONTRATO_CODDOCUMENTO[_SUFIJO].pdf
    // SUFIJO es opcional: permite múltiples PDFs del mismo código (ej: D2_1.pdf, D2_2.pdf, D2_A.pdf)
    // MANDANTE_ID puede ser numérico (ej: 100004) o 'X' si se desconoce.
    // NUM_CONTRATO puede ser 'X' si no aplica.
    private const PDF_PATRON = '/^(.+?)_(\d+)_(\d{4})(\d{2})_(.+?)_(.+?)_(.+?)_([^_.]+)(?:_([^.]+))?\.pdf$/i';

    public function __construct(CertificadoHistoricoService $service, \App\Services\EncryptionService $encryptionService)
    {
        $this->service = $service;
        $this->encryptionService = $encryptionService;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VISTAS
    // ─────────────────────────────────────────────────────────────────────────

    public function index()
    {
        return view('admin.importador-historico');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // IMPORTACIÓN DE EXCEL/CSV (NÓMINA + CONTINGENCIAS)
    // ─────────────────────────────────────────────────────────────────────────

    public function procesar(Request $request)
    {
        Log::info('Iniciando procesamiento de importación histórica.');
        $request->validate([
            'archivo_excel' => 'required|file',
            'is_dry_run'    => 'nullable',
            'forzar'        => 'nullable',
        ]);

        try {
            $import = new CertificadosHistoricosImport();
            Excel::import($import, $request->file('archivo_excel'));
            $certificados = $import->getCertificados();

            if (empty($certificados)) {
                return back()->with('error', 'El archivo no contiene registros válidos o no tiene las cabeceras requeridas.');
            }

            $isDryRun = $request->boolean('is_dry_run', false);
            $forzar   = $request->boolean('forzar', false);
            $resultado = $this->service->procesarImportacionMasiva($certificados, $isDryRun, $forzar);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'resultado' => $resultado]);
            }

            return back()->with('resultado', $resultado)->with('success', 'Importación procesada.');

        } catch (\Exception $e) {
            Log::error('Error en Importador Histórico (Excel): ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // IMPORTACIÓN MASIVA DE PDFs HISTÓRICOS (desde UI del módulo)
    // Convención: PRINCIPAL_IDREGISTRO_AAAAMM_LUGAR_NUMCONTRATO_COD.pdf
    // ─────────────────────────────────────────────────────────────────────────

    public function procesarPdfs(Request $request)
    {
        $request->validate([
            'pdfs'    => 'required|array|min:1',
            'pdfs.*'  => 'required|file|mimes:pdf|max:102400', // max 100MB por archivo
            'dry_run' => 'nullable|boolean',
            'forzar'  => 'nullable|boolean',
        ]);

        $isDry  = $request->boolean('dry_run', false);
        $forzar = $request->boolean('forzar', false);
        $resultados = ['ok' => [], 'skip' => [], 'error' => []];

        foreach ($request->file('pdfs') as $archivo) {
            $nombre = $archivo->getClientOriginalName();
            $res    = $this->procesarUnPdf($nombre, $archivo, $isDry, $forzar);
            $resultados[$res['status']][] = ['archivo' => $nombre, 'msg' => $res['msg']];
        }

        $ok    = count($resultados['ok']);
        $skip  = count($resultados['skip']);
        $error = count($resultados['error']);

        Log::info("Importación PDFs históricos: {$ok} ok, {$skip} omitidos, {$error} errores.");

        return back()
            ->with('resultado_pdfs', $resultados)
            ->with('success_pdfs', "PDFs procesados: {$ok} importados, {$skip} omitidos, {$error} errores.");
    }

    private function procesarUnPdf(string $nombre, $archivo, bool $isDry, bool $forzar): array
    {
        // 1. Parsear nombre de archivo
        if (!preg_match(self::PDF_PATRON, $nombre, $m)) {
            return [
                'status' => 'error',
                'msg'    => 'Nombre inválido. Debe ser: PRINCIPAL_IDREGISTRO_AAAAMM_LUGAR_NUMCONTRATO_COD.pdf',
            ];
        }

        [, $principalRaw, $idRegistro, $anio, $mes, $lugar, $uo, $numContrato, $codDocumento, $sufijo] = $m + [9 => null];
        $anio            = (int) $anio;
        $mes             = (int) $mes;
        $sinContrato     = strtoupper($numContrato) === 'X';
        $sinPrincipal    = strtoupper($principalRaw) === 'X';
        $codDocumento    = strtoupper($codDocumento);
        $sufijoLabel     = $sufijo ? " (#{$sufijo})" : ''; // para mensajes UI
        // Si PRINCIPAL es numérico, lo interpretamos como mandante_id para validación cruzada
        $mandanteIdNombre = is_numeric($principalRaw) ? (int) $principalRaw : null;

        // 2. Buscar ContratistaUnidadOrganizacional
        $query = ContratistaUnidadOrganizacional::where('id_registro', $idRegistro);
        if (!$sinContrato) {
            $query->where(function ($q) use ($numContrato) {
                $q->where('numero_contrato', $numContrato)
                  ->orWhere('numero_contrato', str_replace('-', '', $numContrato));
            });
        }
        $cuo = $query->first();

        if (!$cuo) {
            return [
                'status' => 'error',
                'msg'    => "No existe CUO con id_registro={$idRegistro}"
                          . ($sinContrato ? '' : " y contrato={$numContrato}"),
            ];
        }

        // 3. Buscar CarpetaVerificacion
        $carpeta = CarpetaVerificacion::where('contratista_unidad_organizacional_id', $cuo->id)
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->first();

        if (!$carpeta) {
            return [
                'status' => 'error',
                'msg'    => "No hay carpeta para id_registro={$idRegistro} en {$anio}/{$mes}. Importe primero el JSON de nómina.",
            ];
        }

        // 3.5 Validación cruzada: mandante_id del filename vs mandante real del CUO
        $mandanteId     = $cuo->unidadOrganizacionalMandante?->mandante_id;
        $mandanteNombre = $cuo->unidadOrganizacionalMandante?->mandante?->razon_social ?? 'ID:' . $mandanteId;

        if (!$sinPrincipal && $mandanteIdNombre !== null && $mandanteId !== null) {
            if ($mandanteIdNombre !== $mandanteId) {
                return [
                    'status' => 'error',
                    'msg'    => "MANDANTE no coincide: archivo dice ID={$mandanteIdNombre}, carpeta pertenece a '{$mandanteNombre}' (ID={$mandanteId}). Revisa el nombre del archivo.",
                ];
            }
        }

        // 4. Buscar RequisitoVerificacion por código
        // Códigos UNIVERSALES: D36 = Cotizaciones siempre, sin importar el mandante.
        // Búsqueda 1: Requisito del mandante específico
        $requisito = $mandanteId
            ? RequisitoVerificacion::where('codigo', $codDocumento)
                ->where('mandante_id', $mandanteId)
                ->first()
            : null;

        // Búsqueda 2 (fallback): Cualquier mandante con ese código
        if (!$requisito) {
            $requisito = RequisitoVerificacion::where('codigo', $codDocumento)->first();
        }

        if (!$requisito) {
            return [
                'status' => 'error',
                'msg'    => "Código '{$codDocumento}' no configurado. Ve a Verif. Config → {$mandanteNombre} y agrégalo.",
            ];
        }

        // Aviso si el requisito vino del fallback (otro mandante)
        if ($mandanteId && $requisito->mandante_id !== $mandanteId) {
            Log::warning("PDF {$nombre}: código {$codDocumento} encontrado en mandante ID {$requisito->mandante_id}, no en {$mandanteId}.");
        }

        // 5. Verificar duplicado
        $existente = DocumentoVerificacion::where('carpeta_verificacion_id', $carpeta->id)
            ->where('requisito_verificacion_id', $requisito->id)
            ->where('nombre_original', $nombre)
            ->first();

        if ($existente && !$forzar) {
            return ['status' => 'skip', 'msg' => 'Ya existe (marca "Forzar" para sobreescribir)'];
        }

        if ($isDry) {
            return ['status' => 'ok', 'msg' => "[DRY-RUN] → Carpeta #{$carpeta->id} | {$requisito->nombre}{$sufijoLabel}"];
        }

        // 6. Guardar archivo en storage (CON ENCRIPTACIÓN)
        $directory = "verificacion/historico/{$carpeta->id}";
        $storagePath = $this->encryptionService->encryptAndStore($archivo, $directory);

        // 7. Crear o actualizar registro DocumentoVerificacion
        if ($existente && $forzar) {
            // Eliminar archivo anterior si existe (opcional, pero recomendado)
            if ($existente->is_encrypted) {
                $this->encryptionService->deleteEncrypted($existente->path);
            } else {
                Storage::disk('public')->delete($existente->path);
            }

            $existente->update([
                'path' => $storagePath, 
                'nombre_original' => $nombre,
                'is_encrypted' => true
            ]);
        } else {
            DocumentoVerificacion::create([
                'carpeta_verificacion_id'   => $carpeta->id,
                'requisito_verificacion_id' => $requisito->id,
                'path'                      => $storagePath,
                'nombre_original'           => $nombre,
                'is_encrypted'              => true
            ]);
        }

        return ['status' => 'ok', 'msg' => "✅ Carpeta #{$carpeta->id} | {$requisito->nombre}{$sufijoLabel}"];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DESCARGA DE PLANTILLA CSV
    // ─────────────────────────────────────────────────────────────────────────

    public function descargarPlantilla()
    {
        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=plantilla_historicos.csv',
            'Expires'             => '0',
            'Pragma'              => 'public',
        ];

        $columnas = [
            'mes_periodo', 'anio_periodo', 'rut_contratista', 'razon_social_contratista',
            'rut_mandante', 'unidad_organizacional', 'lugar_trabajo', 'numero_contrato',
            'rut_trabajador', 'nombre_trabajador',
            'contingencia_clasificacion', 'contingencia_causal', 'monto_adeudado',
            'solucionado', 'monto_solucionado', 'fecha_solucion',
        ];

        $callback = function () use ($columnas) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columnas, ';');
            fputcsv($file, [
                '11', '2025', '10.703.728-4', 'ANA PAMELA RIVERA LIRA',
                '92.387.000-8', 'LANDES U.O.', 'LANDES LUGAR', '',
                '15.807.850-3', 'JONY TARDON',
                'Feriado', 'No presenta finiquito...', '1044102',
                'SI', '1044102', '2026-01-20',
            ], ';');
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
