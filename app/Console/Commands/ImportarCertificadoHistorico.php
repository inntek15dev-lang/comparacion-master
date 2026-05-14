<?php

namespace App\Console\Commands;

use App\Services\CertificadoHistoricoService;
use Illuminate\Console\Command;

class ImportarCertificadoHistorico extends Command
{
    protected $signature = 'certificado:importar
                            {archivo : Ruta al archivo JSON generado desde Claude.ai}
                            {--dry-run : Simula la importación sin guardar nada en BD}
                            {--forzar : Sobreescribe si ya existe un período igual para este contratista}';

    protected $description = 'Importa un período histórico desde un JSON (extraído con Claude.ai de un certificado PDF OVAL)';

    public function handle(CertificadoHistoricoService $service): int
    {
        $archivo  = $this->argument('archivo');
        $isDryRun = $this->option('dry-run');
        $forzar   = $this->option('forzar');

        if (!file_exists($archivo)) {
            $this->error("Archivo no encontrado: {$archivo}");
            return self::FAILURE;
        }

        $jsonContent = json_decode(file_get_contents($archivo), true);
        if (!$jsonContent) {
            $this->error("El archivo no es un JSON válido.");
            return self::FAILURE;
        }

        // Si el JSON es un objeto único, lo convertimos en un array de 1 elemento
        // Si es un array de objetos (múltiples PDFs procesados por Claude), lo procesamos completo
        $certificados = isset($jsonContent['periodo']) ? [$jsonContent] : $jsonContent;

        if (!is_array($certificados) || empty($certificados)) {
            $this->error("Estructura JSON no reconocida. Debe ser un objeto de certificado o un array de certificados.");
            return self::FAILURE;
        }

        $this->line('');
        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║   IMPORTADOR DE CERTIFICADOS HISTÓRICOS OVAL         ║');
        $this->info('╚══════════════════════════════════════════════════════╝');

        if ($isDryRun) {
            $this->warn('  ⚠  MODO DRY-RUN: No se guardará nada en la base de datos.');
        }
        $this->line("  📦 Procesando " . count($certificados) . " certificados...");

        $resultado = $service->procesarImportacionMasiva($certificados, $isDryRun, $forzar);

        foreach ($resultado['detalles'] as $detalle) {
            $this->line('');
            $this->info("────────── CERTIFICADO {$detalle['index']} DE " . count($certificados) . " ──────────");
            $this->info("📄 {$detalle['periodo']} | 🏢 {$detalle['contratista']}");
            
            foreach ($detalle['mensajes'] as $msg) {
                if (str_starts_with($msg, '❌')) {
                    $this->error("  " . $msg);
                } elseif (str_starts_with($msg, '⚠')) {
                    $this->warn("  " . $msg);
                } else {
                    $this->line("  " . $msg);
                }
            }
        }

        $this->line('');
        $this->info('══════════════════════════════════════════════════════');
        $this->info("  📊 RESULTADO GLOBAL DE LA IMPORTACIÓN");
        $this->info("     Exitosos : {$resultado['exitosos']}");
        $this->info("     Fallidos : {$resultado['fallidos']}");
        $this->info('══════════════════════════════════════════════════════');
        $this->line('');

        return $resultado['fallidos'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
