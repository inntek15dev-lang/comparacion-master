<?php

namespace App\Http\Controllers;

use App\Models\DocumentoCargado;
use App\Services\EncryptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * DocumentoSeguroController — Punto Único de Descarga de Documentos
 *
 * TODA descarga de documentos pasa obligatoriamente por este controller.
 * Verifica autenticación + autorización antes de servir cualquier archivo.
 *
 * Soporta dos modos de forma transparente:
 *   - is_encrypted = true  → desencripta en memoria y hace streaming
 *   - is_encrypted = false → sirve el archivo plano (compatibilidad legado)
 *
 * Rutas:
 *   GET /documento-seguro/{id}            → visualizar (inline)
 *   GET /documento-seguro/{id}?download=1 → descargar
 */
class DocumentoSeguroController extends Controller
{
    public function __construct(private readonly EncryptionService $encryptionService) {}

    /**
     * Descarga o visualiza un DocumentoCargado de acreditación.
     */
    public function descargar(Request $request, int $id): StreamedResponse
    {
        $documento = DocumentoCargado::findOrFail($id);

        // ── AUTORIZACIÓN ─────────────────────────────────────────────────────
        $this->autorizarAcceso($documento);

        // ── NOMBRE PARA CONTENT-DISPOSITION ──────────────────────────────────
        $nombreArchivo = $documento->nombre_original_archivo ?? 'documento.pdf';
        $disposition   = $request->boolean('download') ? 'attachment' : 'inline';

        // ── MODO ENCRIPTADO ───────────────────────────────────────────────────
        if ($documento->is_encrypted) {
            $contenido = $this->encryptionService->decryptToMemory($documento->ruta_archivo);
            return response()->stream(function () use ($contenido) {
                echo $contenido;
            }, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => "{$disposition}; filename=\"{$nombreArchivo}\"",
                'Content-Length'      => strlen($contenido),
                'Cache-Control'       => 'private, no-store, no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        // ── MODO LEGADO (archivo plano en disk:public) ────────────────────────
        if (!Storage::disk('public')->exists($documento->ruta_archivo)) {
            abort(404, 'El archivo solicitado no fue encontrado en el servidor.');
        }

        return Storage::disk('public')->response(
            $documento->ruta_archivo,
            $nombreArchivo,
            ['Content-Disposition' => "{$disposition}; filename=\"{$nombreArchivo}\""]
        );
    }

    /**
     * Descarga o visualiza un documento de verificación complementaria.
     */
    public function descargarComplementario(Request $request, int $id): StreamedResponse
    {
        $documento = \App\Models\DocumentoSolicitudComplementaria::findOrFail($id);

        // Verificar que el usuario autenticado tenga relación con la solicitud
        $this->autorizarAccesoComplementario($documento);

        $nombreArchivo = $documento->nombre_original ?? 'verificacion.pdf';
        $disposition   = $request->boolean('download') ? 'attachment' : 'inline';

        if ($documento->is_encrypted) {
            $contenido = $this->encryptionService->decryptToMemory($documento->path);
            return response()->stream(function () use ($contenido) {
                echo $contenido;
            }, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => "{$disposition}; filename=\"{$nombreArchivo}\"",
                'Content-Length'      => strlen($contenido),
                'Cache-Control'       => 'private, no-store, no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        if (!Storage::disk('public')->exists($documento->path)) {
            abort(404, 'El archivo de verificación no fue encontrado.');
        }

        return Storage::disk('public')->response(
            $documento->path,
            $nombreArchivo,
            ['Content-Disposition' => "{$disposition}; filename=\"{$nombreArchivo}\""]
        );
    }

    /**
     * Descarga o visualiza un documento de verificación legacy.
     */
    public function descargarVerificacion(Request $request, int $id): StreamedResponse
    {
        $documento = \App\Models\DocumentoVerificacion::findOrFail($id);

        // Autorización
        $this->autorizarAccesoVerificacion($documento);

        $nombreArchivo = $documento->nombre_original ?? 'documento.pdf';
        $disposition   = $request->boolean('download') ? 'attachment' : 'inline';

        if ($documento->is_encrypted) {
            $contenido = $this->encryptionService->decryptToMemory($documento->path);
            return response()->stream(function () use ($contenido) {
                echo $contenido;
            }, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => "{$disposition}; filename=\"{$nombreArchivo}\"",
                'Content-Length'      => strlen($contenido),
                'Cache-Control'       => 'private, no-store, no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        if (!Storage::disk('public')->exists($documento->path)) {
            abort(404, 'El archivo solicitado no fue encontrado.');
        }

        return Storage::disk('public')->response(
            $documento->path,
            $nombreArchivo,
            ['Content-Disposition' => "{$disposition}; filename=\"{$nombreArchivo}\""]
        );
    }

    /**
     * Verifica que el usuario autenticado tiene permiso para ver el documento.
     *
     * Reglas:
     * - ASEM_Admin, OVAL_Admin, ASEM_Validator → acceso total
     * - Mandante_Admin, Mandante_Validator, Mandante_Ver → solo su mandante
     * - Contratista_Admin, Contratista_User → solo su contratista
     * - Cualquier otro rol autenticado → acceso denegado
     */
    private function autorizarAcceso(DocumentoCargado $documento): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'No autenticado.');
        }

        // ── Administradores y validadores ASEM/OVAL: acceso total ─────────────
        if ($user->hasRole(['ASEM_Admin', 'OVAL_Admin', 'ASEM_Validator'])) {
            return;
        }

        // ── Mandante: solo documentos de su propio mandante ───────────────────
        if ($user->hasRole(['Mandante_Admin', 'Mandante_Validator', 'Mandante_Ver'])) {
            if ($documento->mandante_id && $user->mandante_id === $documento->mandante_id) {
                return;
            }
            abort(403, 'No tiene permiso para acceder a este documento.');
        }

        // ── Contratista: solo documentos de su propio contratista ─────────────
        if ($user->hasRole(['Contratista_Admin', 'Contratista_User', 'Subcontratista'])) {
            if ($documento->contratista_id && $user->contratista_id === $documento->contratista_id) {
                return;
            }
            abort(403, 'No tiene permiso para acceder a este documento.');
        }

        // ── Roles de verificación ─────────────────────────────────────────────
        if ($user->hasRole(['Verifica_Auditor', 'Verifica_Supervisor', 'Verifica_Emisor', 'Verifica_Analista'])) {
            return;
        }

        abort(403, 'Rol sin permiso para descargar documentos.');
    }

    /**
     * Autorización para documentos de solicitudes complementarias.
     */
    private function autorizarAccesoComplementario(\App\Models\DocumentoSolicitudComplementaria $documento): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'No autenticado.');
        }

        // ── Admins y roles de verificación: acceso total ──────────────────────
        if ($user->hasRole(['ASEM_Admin', 'OVAL_Admin', 'ASEM_Validator',
                            'Verifica_Auditor', 'Verifica_Supervisor', 'Verifica_Emisor', 'Verifica_Analista'])) {
            return;
        }

        // ── Contratista: verificar que la solicitud pertenece a su contratista ─
        if ($user->hasRole(['Contratista_Admin', 'Contratista_User', 'Subcontratista'])) {
            $solicitud = $documento->solicitudComplementaria;
            if ($solicitud) {
                $vinculacion = \App\Models\ContratistaUnidadOrganizacional::find(
                    $solicitud->contratista_unidad_organizacional_id
                );
                if ($vinculacion && $vinculacion->contratista_id === $user->contratista_id) {
                    return;
                }
            }
            abort(403, 'No tiene permiso para acceder a este documento de verificación.');
        }

        abort(403, 'Rol sin permiso para descargar documentos de verificación.');
    }

    /**
     * Autorización para documentos de verificación legacy.
     */
    private function autorizarAccesoVerificacion(\App\Models\DocumentoVerificacion $documento): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'No autenticado.');
        }

        // ── Admins y roles de verificación: acceso total ──────────────────────
        if ($user->hasRole(['ASEM_Admin', 'OVAL_Admin', 'ASEM_Validator',
                            'Verifica_Auditor', 'Verifica_Supervisor', 'Verifica_Emisor', 'Verifica_Analista'])) {
            return;
        }

        // ── Contratista: verificar que el documento pertenece a su carpeta ─────
        if ($user->hasRole(['Contratista_Admin', 'Contratista_User', 'Subcontratista'])) {
            $carpeta = $documento->carpeta;
            if ($carpeta) {
                $vinculacion = $carpeta->vinculacion;
                if ($vinculacion && $vinculacion->contratista_id === $user->contratista_id) {
                    return;
                }
            }
            abort(403, 'No tiene permiso para acceder a este documento de verificación.');
        }

        abort(403, 'Rol sin permiso para descargar documentos de verificación.');
    }
}
