<?php

namespace App\Console\Commands;

use App\Models\CarpetaVerificacion;
use App\Models\ContratistaUnidadOrganizacional;
use App\Models\DocumentoVerificacion;
use App\Models\RequisitoVerificacion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Importador masivo de documentos PDF históricos.
 *
 * CONVENCIÓN DE NOMBRE DE ARCHIVO OBLIGATORIA:
 *   {PRINCIPAL}_{ID_REGISTRO}_{AAAAMM}_{LUGAR}_{NUM_CONTRATO}_{COD_DOCUMENTO}.pdf
 *
 * Campos:
 *   PRINCIPAL      → Nombre/código del Mandante. Puede ser "X" si no aplica.
 *   ID_REGISTRO    → id_registro del registro en contratista_unidad_organizacional (OBLIGATORIO).
 *   AAAAMM         → Período en formato año+mes, ej: 202410 = Octubre 2024 (OBLIGATORIO).
 *   LUGAR          → Nombre del lugar de trabajo, sin espacios (usar guión). Ej: FAENA-NORTE.
 *   NUM_CONTRATO   → Número de contrato. Puede ser "X" si no aplica.
 *   COD_DOCUMENTO  → Código del RequisitoVerificacion en la base de datos. Ej: F30, LIQ, COT.
 *
 * Ejemplos:
 *   LANDES_2188_202410_FAENANORTE_C2024001_F30.pdf
 *   X_2188_202411_PLANTA_X_LIQ.pdf
 */
class ImportarDocumentosHistoricos extends Command
{
    protected $signature = 'documentos:importar-historicos
                            {directorio : Ruta absoluta a la carpeta con los PDFs}
                            {--dry-run : Simula la importación sin guardar nada}
                            {--forzar  : Sobreescribe si el documento ya existe en la carpeta}';

    protected $description = 'Importa documentos PDF históricos desde un directorio usando la convención de nombre estándar';

    // Patrón: PRINCIPAL_IDREGISTRO_AAAAMM_LUGAR_NUMCONTRATO_CODDOCUMENTO.pdf
    private const PATRON = '/^(.+?)_(\d+)_(\d{4})(\d{2})_(.+?)_(.+?)_(.+?)\.pdf$/i';

    public function handle(): int
    {
        $dir     = $this->argument('directorio');
        $isDry   = $this->option('dry-run');
        $forzar  = $this->option('forzar');

        if (!is_dir($dir)) {
            $this->error("❌ El directorio no existe: {$dir}");
            return self::FAILURE;
        }

        $archivos = glob(rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.pdf');
        if (empty($archivos)) {
            $this->warn("⚠  No se encontraron archivos PDF en: {$dir}");
            return self::SUCCESS;
        }

        $this->line('');
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║   IMPORTADOR MASIVO DE DOCUMENTOS HISTÓRICOS OVAL        ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        if ($isDry) $this->warn('  ⚠  MODO DRY-RUN: No se guardará nada.');
        $this->line("  📁 Directorio : {$dir}");
        $this->line("  📄 Archivos   : " . count($archivos) . " PDFs encontrados");
        $this->line('');

        $ok = $skip = $err = 0;

        foreach ($archivos as $rutaAbsoluta) {
            $nombreArchivo = basename($rutaAbsoluta);
            $resultado = $this->procesarArchivo($nombreArchivo, $rutaAbsoluta, $isDry, $forzar);

            match ($resultado['status']) {
                'ok'   => ($this->line("  ✅ {$nombreArchivo}") && $ok++),
                'skip' => ($this->warn("  ⏭  {$nombreArchivo} → {$resultado['msg']}") && $skip++),
                'err'  => ($this->error("  ❌ {$nombreArchivo} → {$resultado['msg']}") && $err++),
            };
        }

        $this->line('');
        $this->info('══════════════════════════════════════════════════════════');
        $this->info("  ✅ Importados  : {$ok}");
        $this->warn("  ⏭  Omitidos   : {$skip}");
        $this->error("  ❌ Errores     : {$err}");
        $this->info('══════════════════════════════════════════════════════════');
        $this->line('');

        return $err > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function procesarArchivo(string $nombre, string $ruta, bool $isDry, bool $forzar): array
    {
        // ── 1. PARSEAR NOMBRE ──────────────────────────────────────────────────
        if (!preg_match(self::PATRON, $nombre, $m)) {
            return ['status' => 'err', 'msg' => 'Nombre no sigue la convención: PRINCIPAL_IDREGISTRO_AAAAMM_LUGAR_NUMCONTRATO_COD.pdf'];
        }

        [, $principal, $idRegistro, $anio, $mes, $lugar, $numContrato, $codDocumento] = $m;
        $anio        = (int) $anio;
        $mes         = (int) $mes;
        $sinPrincipal  = strtoupper($principal)   === 'X';
        $sinContrato   = strtoupper($numContrato)  === 'X';
        $codDocumento  = strtoupper($codDocumento);

        // ── 2. BUSCAR LA VINCULACIÓN (CUO) POR ID_REGISTRO ────────────────────
        $query = ContratistaUnidadOrganizacional::where('id_registro', $idRegistro);
        if (!$sinContrato) {
            // Normalizar el número de contrato (quitar guiones y mayúsculas para comparar)
            $query->where(function ($q) use ($numContrato) {
                $q->where('numero_contrato', $numContrato)
                  ->orWhere('numero_contrato', str_replace('-', '', $numContrato));
            });
        }
        $cuo = $query->first();

        if (!$cuo) {
            return [
                'status' => 'err',
                'msg'    => "No se encontró ContratistaUnidadOrganizacional con id_registro={$idRegistro}"
                          . ($sinContrato ? '' : " y num_contrato={$numContrato}"),
            ];
        }

        // ── 3. BUSCAR LA CARPETA DE VERIFICACIÓN ──────────────────────────────
        $carpeta = CarpetaVerificacion::where('contratista_unidad_organizacional_id', $cuo->id)
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->first();

        if (!$carpeta) {
            return [
                'status' => 'err',
                'msg'    => "No existe carpeta para id_registro={$idRegistro}, periodo={$anio}/{$mes}",
            ];
        }

        // ── 4. BUSCAR EL REQUISITO DE VERIFICACIÓN POR CÓDIGO ─────────────────
        $mandanteId = $cuo->unidadOrganizacionalMandante?->mandante_id;
        $requisito  = RequisitoVerificacion::where('codigo', $codDocumento)
            ->when($mandanteId, fn($q) => $q->where('mandante_id', $mandanteId))
            ->first();

        if (!$requisito) {
            // Segundo intento: buscar sin filtro de mandante (por si el código es global)
            $requisito = RequisitoVerificacion::where('codigo', $codDocumento)->first();
        }

        if (!$requisito) {
            return [
                'status' => 'err',
                'msg'    => "No existe un RequisitoVerificacion con código='{$codDocumento}'. Créelo primero en el sistema.",
            ];
        }

        // ── 5. VERIFICAR DUPLICADO ─────────────────────────────────────────────
        $existente = DocumentoVerificacion::where('carpeta_verificacion_id', $carpeta->id)
            ->where('requisito_verificacion_id', $requisito->id)
            ->where('nombre_original', $nombre)
            ->first();

        if ($existente && !$forzar) {
            return ['status' => 'skip', 'msg' => 'Ya existe en la carpeta (usa --forzar para sobreescribir)'];
        }

        if ($isDry) {
            $this->line("     → Carpeta ID: {$carpeta->id} | Requisito: {$requisito->nombre} | {$anio}/{$mes}");
            return ['status' => 'ok', 'msg' => 'dry-run'];
        }

        // ── 6. COPIAR ARCHIVO A STORAGE (CON ENCRIPTACIÓN) ────────────────────────
        $subdir    = "verificacion/historico/{$carpeta->id}";
        $service   = app(\App\Services\EncryptionService::class);
        
        // El servicio de encriptación maneja su propio disco local seguro
        $storagePath = $service->encryptAndStore($ruta, $subdir);

        // ── 7. CREAR O ACTUALIZAR DocumentoVerificacion ───────────────────────
        if ($existente && $forzar) {
            // Eliminar anterior si existía
            if ($existente->is_encrypted) {
                $service->deleteEncrypted($existente->path);
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
                'is_encrypted'              => true,
            ]);
        }

        $this->line("     → Carpeta #{$carpeta->id} | {$requisito->nombre} | Guardado en: {$storagePath}");
        return ['status' => 'ok', 'msg' => 'importado'];
    }
}
