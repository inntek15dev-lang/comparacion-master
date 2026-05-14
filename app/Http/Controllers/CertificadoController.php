<?php

namespace App\Http\Controllers;

use App\Models\CarpetaVerificacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CertificadoController extends Controller
{
    public function generarCertificado($carpeta_id)
    {
        $carpeta = CarpetaVerificacion::with([
            'vinculacion.contratista',
            'vinculacion.unidadOrganizacional.mandante',
            'vinculacion.dependencia',
            'analista',
            'supervisor',
            'auditor',
            'trabajadoresVerificados.vinculacion.trabajador',
            'trabajadoresVerificados.contingencias.catalogoItem',
            'trabajadoresVerificados.contingencias.itemsComplementarios.solicitud'
        ])->findOrFail($carpeta_id);

        // AUDITORÍA: Registrar visualización de certificado
        \App\Services\AuditService::log(
            'VIEW_CERTIFICATE',
            "Visualizó certificado de auditoría para " . ($carpeta->vinculacion->contratista->razon_social ?? 'Contratista ID: ' . $carpeta->vinculacion->contratista_id),
            [
                'carpeta_id' => $carpeta_id,
                'contratista_id' => $carpeta->vinculacion->contratista_id,
                'periodo' => "{$carpeta->mes}-{$carpeta->anio}",
            ]
        );

        $user = auth()->user();
        $esAdminOSuper = $user->hasAnyRole(['OVAL_Admin', 'ASEM_Admin', 'Verifica_Supervisor', 'Verifica_Emisor']);
        $esAuditor = $user->hasRole('Verifica_Auditor');
        $esContratista = $user->hasAnyRole(['Contratista_Admin', 'Contratista_User', 'Subcontratista']);

        // 1. Estados permitidos (PARA_EMITIR es borrador interno, EMITIDO es final)
        $estadosPermitidos = ['PARA_EMITIR', 'EMITIDO'];
        if (!in_array($carpeta->estado_revision, $estadosPermitidos)) {
            abort(403, 'El certificado no está disponible para previsualización o descarga en su estado actual.');
        }

        // 2. Control de Acceso por Rol
        if ($esContratista) {
            // El contratista SOLO puede ver certificados ya EMITIDOS
            if ($carpeta->estado_revision !== 'EMITIDO') {
                abort(403, 'El certificado solicitado aún se encuentra en proceso de firma o revisión interna.');
            }
            // Seguridad: Solo puede ver certificados de SU propia empresa
            if ($carpeta->vinculacion->contratista_id !== $user->contratista_id) {
                abort(403, 'Acceso denegado. No tiene permisos para visualizar este documento.');
            }
        } elseif ($esAuditor || $esAdminOSuper) {
            // Auditores y Supervisores pueden ver ambos estados (Previsualización y Final)
            // No aplicamos restricciones de contratista_id aquí
        } else {
            // Otros roles (Analistas, etc.) - Por ahora restringimos si no tienen rol explícito en la ruta
            // (La ruta ya tiene middleware de roles, así que esto es una capa extra)
        }

        // =====================================================================
        // AGRUPACIÓN: Solo contingencias retenibles van al certificado.
        // Las observaciones y contingencias no retenibles se guardan en BD
        // pero NO se incluyen en el PDF del certificado.
        // =====================================================================
        $trabajadores = $carpeta->trabajadoresVerificados;

        $contingenciasAgrupadas = []; // Solo tipo='contingencia' subtipo='retenible'

        foreach ($trabajadores as $ctv) {
            foreach ($ctv->contingencias as $contingencia) {

                // Solo procesar contingencias retenibles
                if ($contingencia->tipo !== 'contingencia' || $contingencia->subtipo !== 'retenible') {
                    continue;
                }

                // Texto a mostrar: usar texto_plural del catálogo si existe, sino el causal directo
                $textoAGrupar = $contingencia->catalogo_item_id
                    ? ($contingencia->catalogoItem->texto_plural ?? $contingencia->causal)
                    : $contingencia->causal;

                $clasif = $contingencia->clasificacion ?? 'Sin Clasificación';

                // Clave de agrupamiento por texto + clasificación
                $claveAgrupamiento = $textoAGrupar . '|' . $clasif;

                if (!isset($contingenciasAgrupadas[$claveAgrupamiento])) {
                    $contingenciasAgrupadas[$claveAgrupamiento] = [
                        'texto_plural'   => $textoAGrupar,
                        'texto_singular' => $contingencia->causal,
                        'clasificacion'  => $clasif,
                        'codigo'         => $contingencia->codigo, // Código real de BD (ej. 100001)
                        'afectados'      => [],
                    ];
                }

                // Determinar si hay solución (TOTAL o PARCIAL) iterando sobre sus ítems históricos
                $montoAcumuladoSolucionado = 0;
                $ultimaFechaSolucion = null;
                $ultimaObs = null;

                foreach ($contingencia->itemsComplementarios as $itemSc) {
                    if (in_array($itemSc->estado_auditor, ['TOTAL', 'PARCIAL']) && $itemSc->solicitud && $itemSc->solicitud->estado === 'EMITIDO') {
                        // Si es TOTAL, cubrió todo lo que quedaba, pero sumamos el monto resuelto oficial o el remanente
                        if ($itemSc->estado_auditor === 'TOTAL') {
                            $montoAcumuladoSolucionado = $contingencia->monto; 
                        } else {
                            $montoAcumuladoSolucionado += $itemSc->monto_solucionado;
                        }

                        $sol = $itemSc->solicitud;
                        if ($sol) {
                            if (!$ultimaFechaSolucion || $sol->fecha_revision > $ultimaFechaSolucion) {
                                $ultimaFechaSolucion = $sol->fecha_revision;
                                $ultimaObs = $sol->observaciones_auditor;
                            }
                        }
                    }
                }

                // Cada afectado lleva sus propios datos de solución (línea verde individual)
                // Soporte para registros históricos (sin vinculación real)
                if ($ctv->vinculacion) {
                    $trabajadorData = $ctv->vinculacion->trabajador;
                } else {
                    // Si es histórico, creamos un objeto genérico con los snapshots
                    $trabajadorData = (object) [
                        'rut'             => $ctv->snapshot_rut,
                        'nombre_completo' => $ctv->snapshot_nombres,
                    ];
                }

                $contingenciasAgrupadas[$claveAgrupamiento]['afectados'][] = [
                    'trabajador'             => $trabajadorData,
                    'monto'                  => $contingencia->monto,
                    'codigo'                 => $contingencia->codigo, // Código individual por trabajador
                    'estado_subsanacion'     => $contingencia->estado_subsanacion,
                    'monto_solucionado'      => $montoAcumuladoSolucionado,
                    'fecha_solucion'         => $ultimaFechaSolucion,
                    'observaciones_solucion' => $ultimaObs,
                ];
            }
        }

        $pdf = Pdf::loadView('certificados.certificado-auditoria', [
            'carpeta'                => $carpeta,
            'contingenciasAgrupadas' => $contingenciasAgrupadas,
            'trabajadores'           => $trabajadores,
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Certificado_Periodo_{$carpeta->mes}_{$carpeta->anio}_{$carpeta->vinculacion->contratista->rut}.pdf");
    }
}
