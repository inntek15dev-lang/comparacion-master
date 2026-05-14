<?php

namespace App\Services;

use App\Models\Contratista;
use App\Models\DocumentoConfiguracionCriticidad;
use App\Models\DocumentoExcepcionCriticidad;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\Trabajador;
use App\Models\Vehiculo;
use App\Models\Maquinaria;
use App\Models\Embarcacion;
class CriticidadDocumentoService
{
    public function calcularPorcentajeCumplimientoParaEntidad(Model $entidad, int $mandanteId, int $unidadOrganizacionalId): int
    {
        $documentosConEstado = app(DocumentoRequeridoService::class)->obtenerEstadoDocumentosParaEntidad($entidad, $mandanteId, $unidadOrganizacionalId);

        $docsQueAfectan = array_filter($documentosConEstado, function ($doc) {
            return $doc['afecta_cumplimiento'] === true;
        });

        $docsValidos = array_filter($docsQueAfectan, fn($doc) => 
            in_array($doc['estado_actual_documento'], ['Aprobado', 'Aprobado-Modificado']) ||
            ($doc['dentro_de_gracia'] ?? false) ||
            ($doc['tiene_reemplazo_vigente'] ?? false)
        );

        if (count($docsQueAfectan) === 0) {
            return 100;
        }

        return (int)round((count($docsValidos) / count($docsQueAfectan)) * 100);
    }

    public function determinarAccesoFinalRecurso(Model $entidad, int $mandanteId, int $unidadOrganizacionalId): array
    {
        $hoy = Carbon::today();
        $placeholderDocumentoId = 99999;


        $anulacionManualRecurso = DocumentoExcepcionCriticidad::where('mandante_id', $mandanteId)
            ->where('excepcionable_type', get_class($entidad))
            ->where('excepcionable_id', $entidad->id)
            ->where('nombre_documento_id', $placeholderDocumentoId)
            ->whereNotNull('accion_override')
            ->where(function ($query) use ($hoy) {
            $query->where('valido_hasta', '>=', $hoy)
                ->orWhereNull('valido_hasta');
        })
            ->orderBy('created_at', 'desc')
            ->first();

        if ($anulacionManualRecurso) {
            if ($anulacionManualRecurso->accion_override === 'HABILITAR') {
                return [
                    'habilitado' => true,
                    'motivo' => 'HABILITADO (MANUALMENTE)',
                    'es_excepcion' => true,
                    'valido_hasta' => $anulacionManualRecurso->valido_hasta,
                    'justificacion' => $anulacionManualRecurso->justificacion,
                ];
            }
            if ($anulacionManualRecurso->accion_override === 'RESTRINGIR') {
                return [
                    'habilitado' => false,
                    'motivo' => 'RESTRINGIDO (MANUALMENTE)',
                    'es_excepcion' => true,
                    'valido_hasta' => $anulacionManualRecurso->valido_hasta,
                    'justificacion' => $anulacionManualRecurso->justificacion,
                ];
            }
        }

        if (!$entidad instanceof Contratista) {
            $contratista = $entidad->contratista;
            if ($contratista) {
                $estadoContratista = $this->determinarAccesoFinalRecurso($contratista, $mandanteId, $unidadOrganizacionalId);

                if (!$estadoContratista['habilitado']) {
                    $motivo = $estadoContratista['es_excepcion'] ? 'RESTRINGIDO (MANUALMENTE POR EMPRESA)' : 'Restringido (DOC. EMPRESA)';
                    return ['habilitado' => false, 'motivo' => $motivo, 'es_excepcion' => false];
                }
            }
        }

        $documentosConEstado = app(DocumentoRequeridoService::class)->obtenerEstadoDocumentosParaEntidad($entidad, $mandanteId, $unidadOrganizacionalId);

        foreach ($documentosConEstado as $doc) {
            if (($doc['restringe_acceso'] === true || $doc['restringe_acceso'] == 1) && !in_array($doc['estado_actual_documento'], ['Aprobado', 'Aprobado-Modificado'])) {
                if (($doc['dentro_de_gracia'] ?? false) || ($doc['tiene_reemplazo_vigente'] ?? false)) {
                    continue;
                }
                return ['habilitado' => false, 'motivo' => 'Restringido', 'es_excepcion' => false];
            }
        }

        return ['habilitado' => true, 'motivo' => 'Habilitado', 'es_excepcion' => false];
    }

    public function getParaEntidad(Model $entidad, int $nombreDocumentoId, int $mandanteId): array
    {
        $hoy = Carbon::today()->toDateString();
        $entidadType = get_class($entidad);
        $entidadId = $entidad->id;

        $configuracionGeneral = DocumentoConfiguracionCriticidad::where('mandante_id', $mandanteId)
            ->where('nombre_documento_id', $nombreDocumentoId)
            ->first();

        $criticidadFinal = [
            'afecta_cumplimiento' => $configuracionGeneral->afecta_cumplimiento ?? false,
            'restringe_acceso' => $configuracionGeneral->restringe_acceso ?? false,
            'es_perseguidor' => $configuracionGeneral->es_perseguidor ?? false,
        ];

        $contratistaId = ($entidad instanceof Contratista) ? $entidad->id : $entidad->contratista_id;
        if ($contratistaId) {
            $excepcionContratista = DocumentoExcepcionCriticidad::where('mandante_id', $mandanteId)
                ->where('nombre_documento_id', $nombreDocumentoId)
                ->where('excepcionable_type', Contratista::class)
                ->where('excepcionable_id', $contratistaId)
                ->where(function ($q) use ($hoy) {
                $q->where('valido_hasta', '>=', $hoy)->orWhereNull('valido_hasta');
            })
                ->whereNull('accion_override')
                ->first();

            if ($excepcionContratista) {
                $criticidadFinal['afecta_cumplimiento'] = $excepcionContratista->afecta_cumplimiento_override ?? $criticidadFinal['afecta_cumplimiento'];
                $criticidadFinal['restringe_acceso'] = $excepcionContratista->restringe_acceso_override ?? $criticidadFinal['restringe_acceso'];
                $criticidadFinal['es_perseguidor'] = $excepcionContratista->es_perseguidor_override ?? $criticidadFinal['es_perseguidor'];
            }
        }

        if (!$entidad instanceof Contratista) {
            $excepcionEntidad = DocumentoExcepcionCriticidad::where('mandante_id', $mandanteId)
                ->where('nombre_documento_id', $nombreDocumentoId)
                ->where('excepcionable_type', $entidadType)
                ->where('excepcionable_id', $entidadId)
                ->where(function ($q) use ($hoy) {
                $q->where('valido_hasta', '>=', $hoy)->orWhereNull('valido_hasta');
            })
                ->whereNull('accion_override')
                ->first();

            if ($excepcionEntidad) {
                $criticidadFinal['afecta_cumplimiento'] = $excepcionEntidad->afecta_cumplimiento_override ?? $criticidadFinal['afecta_cumplimiento'];
                $criticidadFinal['restringe_acceso'] = $excepcionEntidad->restringe_acceso_override ?? $criticidadFinal['restringe_acceso'];
                $criticidadFinal['es_perseguidor'] = $excepcionEntidad->es_perseguidor_override ?? $criticidadFinal['es_perseguidor'];
            }
        }

        return $criticidadFinal;
    }
}