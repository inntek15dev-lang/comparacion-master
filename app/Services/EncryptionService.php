<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * EncryptionService — Escudo Criptográfico de Documentos
 *
 * Responsable de encriptar los documentos PDF en reposo (at-rest)
 * usando AES-256-CBC via OpenSSL nativo de PHP.
 *
 * Formato del archivo .enc en disco:
 *   [16 bytes IV aleatorio][contenido AES-256-CBC cifrado]
 *
 * Los archivos se guardan SIEMPRE en disk:local (fuera del webroot),
 * bajo la carpeta encrypted/{directorio}/{uuid}.enc
 *
 * La clave de encriptación es APP_DOCUMENTS_KEY del .env,
 * codificada en Base64 (debe ser exactamente 32 bytes decodificados).
 */
class EncryptionService
{
    private const CIPHER    = 'AES-256-CBC';
    private const IV_LENGTH = 16;
    private const DISK      = 'local';

    /**
     * Encripta un archivo subido y lo guarda en storage/app/encrypted/{directory}/
     *
     * @param  UploadedFile  $file       Archivo validado de Livewire
     * @param  string        $directory  Directorio relativo (ej: "contratistas/1")
     * @return string        Ruta relativa en disk:local (ej: "encrypted/contratistas/1/uuid.enc")
     */
    public function encryptAndStore(UploadedFile $file, string $directory): string
    {
        $key     = $this->getKey();
        $iv      = random_bytes(self::IV_LENGTH);
        $content = file_get_contents($file->getRealPath());

        $encrypted = openssl_encrypt(
            $content,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Error al encriptar el documento. Verifique la configuración de OpenSSL.');
        }

        // Payload: IV + contenido cifrado
        $payload  = $iv . $encrypted;
        $filename = Str::uuid() . '.enc';
        $path     = "encrypted/{$directory}/{$filename}";

        Storage::disk(self::DISK)->put($path, $payload);

        return $path;
    }

    /**
     * Encripta contenido binario ya leído (string) y lo guarda en disk:local.
     * Útil para reencriptar archivos leídos desde otro disco (ej: disk:public en importaciones masivas).
     *
     * @param  string  $content    Contenido binario del archivo
     * @param  string  $directory  Directorio relativo (ej: "trabajadors/1")
     * @return string  Ruta relativa en disk:local (ej: "encrypted/trabajadors/1/uuid.enc")
     */
    public function encryptFromContent(string $content, string $directory): string
    {
        $key     = $this->getKey();
        $iv      = random_bytes(self::IV_LENGTH);

        $encrypted = openssl_encrypt(
            $content,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Error al encriptar el documento. Verifique la configuración de OpenSSL.');
        }

        $payload  = $iv . $encrypted;
        $filename = Str::uuid() . '.enc';
        $path     = "encrypted/{$directory}/{$filename}";

        Storage::disk(self::DISK)->put($path, $payload);

        return $path;
    }

    /**
     * Desencripta un archivo .enc desde disk:local y retorna el contenido binario en memoria.
     * NUNCA escribe el contenido descifrado al disco.
     *
     * @param  string  $path  Ruta relativa en disk:local
     * @return string  Contenido binario del PDF original
     */
    public function decryptToMemory(string $path): string
    {
        if (!Storage::disk(self::DISK)->exists($path)) {
            throw new \RuntimeException("Archivo encriptado no encontrado: {$path}");
        }

        $key     = $this->getKey();
        $payload = Storage::disk(self::DISK)->get($path);

        $iv        = substr($payload, 0, self::IV_LENGTH);
        $encrypted = substr($payload, self::IV_LENGTH);

        $decrypted = openssl_decrypt(
            $encrypted,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Error al desencriptar el documento. La clave o el archivo pueden estar corruptos.');
        }

        return $decrypted;
    }

    /**
     * Elimina un archivo encriptado del disco local.
     */
    public function deleteEncrypted(string $path): bool
    {
        if (Storage::disk(self::DISK)->exists($path)) {
            return Storage::disk(self::DISK)->delete($path);
        }
        return false;
    }

    /**
     * Obtiene la clave de encriptación desde la configuración.
     * La clave debe ser exactamente 32 bytes (256 bits) decodificada.
     */
    private function getKey(): string
    {
        $encoded = config('app.documents_key');

        if (empty($encoded)) {
            throw new \RuntimeException(
                'APP_DOCUMENTS_KEY no está definida en el archivo .env. ' .
                'Genere una con: php artisan oval:generate-docs-key'
            );
        }

        $key = base64_decode($encoded, true);

        if ($key === false || strlen($key) !== 32) {
            throw new \RuntimeException(
                'APP_DOCUMENTS_KEY inválida. Debe ser exactamente 32 bytes codificados en Base64.'
            );
        }

        return $key;
    }
}
