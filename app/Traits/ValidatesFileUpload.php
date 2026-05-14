<?php

namespace App\Traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use App\Services\AuditService;
use App\Services\EncryptionService;

trait ValidatesFileUpload
{
    /**
     * Obtiene la regla de validación estándar para un contexto.
     */
    protected function getFileValidationRule(string $context = 'acreditacion'): string
    {
        $config = Config::get("file-upload.contexts.{$context}");
        if (!$config) return "required|file|mimes:pdf|max:30720";

        $mimes = implode(',', $config['allowed_mimes'] ?? ['pdf']);
        $types = implode(',', $config['allowed_mimetypes'] ?? ['application/pdf']);
        $max = $config['max_size'] ?? 30720;
        
        return "required|file|mimes:{$mimes}|mimetypes:{$types}|max:{$max}";
    }

    /**
     * Valida el archivo manualmente contra la blacklist y el contexto, 
     * y registra alertas de seguridad si detecta anomalías.
     */
    protected function validateSecureFile($file, string $context = 'acreditacion', string $module = 'GENERAL')
    {
        if (!$file) return;

        $ext = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();
        $blacklist = Config::get('file-upload.blacklist', []);
        $config = Config::get("file-upload.contexts.{$context}");

        // 1. Check Blacklist
        if (in_array($ext, $blacklist)) {
            AuditService::log('seguridad-alerta', "ATAQUE DETECTADO ({$module}): Intento de subir archivo prohibido '{$file->getClientOriginalName()}' (Ext: {$ext}). Bloqueado por Blacklist.");
            throw ValidationException::withMessages([
                'archivo' => 'El tipo de archivo no está permitido por razones de seguridad.'
            ]);
        }

        // 2. Check Content vs Extension (Anti-Camuflaje)
        if ($config) {
            $allowedMimes = $config['allowed_mimes'] ?? [];
            $allowedTypes = $config['allowed_mimetypes'] ?? [];

            if (in_array($ext, $allowedMimes) && !in_array($mime, $allowedTypes)) {
                AuditService::log('seguridad-alerta', "INTENTO DE CAMUFLAJE DETECTADO ({$module}): Archivo '{$file->getClientOriginalName()}' dice ser {$ext} pero su contenido es '{$mime}'. Bloqueado.");
                throw ValidationException::withMessages([
                    'archivo' => 'El contenido del archivo no coincide con su extensión o el formato es inválido.'
                ]);
            }
        }

        return true;
    }

    /**
     * Valida, encripta y almacena un PDF en disk:local usando AES-256-CBC.
     *
     * Reemplaza el `$archivo->storeAs(...)` directo en todos los componentes
     * que manejan documentos de acreditación y verificación.
     *
     * @param  mixed   $file       Archivo Livewire (UploadedFile)
     * @param  string  $directory  Directorio de destino (ej: "contratistas/1")
     * @param  string  $context    Contexto para logs de auditoría
     * @return array{ruta_archivo: string, is_encrypted: bool}
     */
    protected function encryptAndStoreFile($file, string $directory, string $context = 'ACREDITACION'): array
    {
        if (!$file) {
            throw new \InvalidArgumentException('No se proporcionó un archivo para encriptar.');
        }

        /** @var EncryptionService $service */
        $service  = app(EncryptionService::class);
        $rutaEnc  = $service->encryptAndStore($file, $directory);

        return [
            'ruta_archivo' => $rutaEnc,
            'is_encrypted' => true,
        ];
    }

    /**
     * Elimina un archivo del disco correcto según si está encriptado o no.
     * Úsalo en lugar de Storage::disk('public')->delete() directamente.
     */
    protected function deleteDocumentFile(string $rutaArchivo, bool $isEncrypted): void
    {
        if ($isEncrypted) {
            app(EncryptionService::class)->deleteEncrypted($rutaArchivo);
        } else {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($rutaArchivo);
        }
    }
}
