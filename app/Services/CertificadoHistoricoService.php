<?php

namespace App\Services;

use App\Models\CarpetaTrabajadorContingencia;
use App\Models\CarpetaVerificacion;
use App\Models\CarpetaVerificacionTrabajador;
use App\Models\Contratista;
use App\Models\ContratistaUnidadOrganizacional;
use App\Models\Dependencia;
use App\Models\Mandante;
use App\Models\SolicitudComplementaria;
use App\Models\SolicitudComplementariaItem;
use App\Models\SolicitudVinculacion;
use App\Models\UnidadOrganizacionalMandante;
use Illuminate\Support\Facades\DB;
use Exception;

class CertificadoHistoricoService
{
    /**
     * Procesa una lista de certificados en JSON.
     * Retorna un array con 'resultados' (detalles de cada uno) y un resumen de 'exitosos' y 'fallidos'.
     */
    public function procesarImportacionMasiva(array $certificados, bool $isDryRun = false, bool $forzar = false): array
    {
        $resultados = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($certificados as $index => $json) {
            $num = $index + 1;
            $resultadoIndividual = [
                'index' => $num,
                'periodo' => ($json['periodo']['mes'] ?? '?') . '/' . ($json['periodo']['anio'] ?? '?'),
                'contratista' => $json['contratista']['razon_social'] ?? 'Desconocido',
                'exito' => false,
                'mensajes' => [],
                'carpeta_id' => null,
            ];

            if (!isset($json['contratista'])) {
                $resultadoIndividual['mensajes'][] = "❌ Estructura de JSON inválida.";
                $failCount++;
                $resultados[] = $resultadoIndividual;
                continue;
            }

            try {
                // 1. Mandante
                $mandante = Mandante::where('rut', $json['empresa_principal']['rut'])->first();
                if (!$mandante) {
                    throw new Exception("Mandante RUT {$json['empresa_principal']['rut']} NO existe en el sistema.");
                }
                $resultadoIndividual['mensajes'][] = "✅ Mandante encontrado: {$mandante->razon_social}";

                // 2. Contratista
                $contratista = Contratista::where('rut', $json['contratista']['rut'])->first();
                if (!$contratista) {
                    if (!$isDryRun) {
                        $contratista = Contratista::create([
                            'razon_social'       => $json['contratista']['razon_social'],
                            'rut'                => $json['contratista']['rut'],
                            'direccion_calle'    => $json['contratista']['direccion'] ?? 'SIN DIRECCION',
                            'email_empresa'      => $json['contratista']['email'] ?? 'sin@email.com',
                            'telefono_empresa'   => $json['contratista']['telefono'] ?? '000000000',
                            'rep_legal_nombres'  => $json['contratista']['rep_legal_nombre'] ?? 'SIN NOMBRE',
                            'rep_legal_apellido_paterno' => 'SIN APELLIDO',
                            'rep_legal_apellido_materno' => 'SIN APELLIDO',
                            'rep_legal_rut'      => $json['contratista']['rep_legal_rut'] ?? '11.111.111-1',
                            'is_active'          => true,
                            'estado_plataforma'  => 'ACTIVO',
                            'tipo_inscripcion'   => 'MANUAL_HISTORICO',
                        ]);
                    } else {
                        $contratista = new Contratista(['id' => 0, 'razon_social' => $json['contratista']['razon_social']]);
                    }
                    $resultadoIndividual['mensajes'][] = "⚠ Contratista creado automáticamente.";
                }

                // 3. UO
                $nombreUo = $json['sistema']['unidad_organizacional'] ?? 'IMPORTACIÓN HISTÓRICA';
                $uo = UnidadOrganizacionalMandante::where('mandante_id', $mandante->id)
                    ->where('nombre_unidad', $nombreUo)
                    ->first();
                if (!$uo) {
                    if (!$isDryRun) {
                        $uo = UnidadOrganizacionalMandante::create([
                            'mandante_id'    => $mandante->id,
                            'nombre_unidad'  => $nombreUo,
                            'codigo_unidad'  => 'HIST-' . strtoupper(substr(preg_replace('/\s+/', '-', $nombreUo), 0, 20)),
                            'descripcion'    => 'Creada automáticamente en importación histórica.',
                            'is_active'      => true,
                        ]);
                    } else {
                        $uo = new UnidadOrganizacionalMandante(['id' => 0, 'nombre_unidad' => $nombreUo]);
                    }
                    $resultadoIndividual['mensajes'][] = "⚠ UO creada automáticamente.";
                }

                // 4. Dependencia
                $nombreDep = $json['sistema']['lugar_trabajo'] ?? 'FAENA HISTÓRICA';
                $dependencia = Dependencia::where('mandante_id', $mandante->id)
                    ->where('nombre', $nombreDep)
                    ->first();
                if (!$dependencia) {
                    if (!$isDryRun) {
                        $dependencia = Dependencia::create([
                            'mandante_id' => $mandante->id,
                            'nombre'      => $nombreDep,
                            'estado'      => true,
                        ]);
                    } else {
                        $dependencia = new Dependencia(['id' => 0, 'nombre' => $nombreDep]);
                    }
                    $resultadoIndividual['mensajes'][] = "⚠ Dependencia creada automáticamente.";
                }

                // 5. Vinculación CUO
                $vinculacion = null;
                if ($contratista->id && $uo->id) {
                    $vinculacion = ContratistaUnidadOrganizacional::where('contratista_id', $contratista->id)
                        ->where('unidad_organizacional_mandante_id', $uo->id)
                        ->where('numero_contrato', $json['sistema']['numero_contrato'] ?? null)
                        ->first();
                }

                if (!$vinculacion) {
                    $tipoSolicitud = strtoupper($json['contratista']['tipo_solicitud'] ?? 'CONTRATISTA');
                    if (!$isDryRun) {
                        $vinculacion = ContratistaUnidadOrganizacional::create([
                            'contratista_id'                  => $contratista->id,
                            'unidad_organizacional_mandante_id'=> $uo->id,
                            'dependencia_id'                  => $dependencia->id,
                            'numero_contrato'                 => $json['sistema']['numero_contrato'] ?? 'HIST-' . $json['periodo']['anio'],
                            'acredita'                        => false,
                            'verifica'                        => true,
                            'fecha_inicio_verifica'           => $json['periodo']['anio'] . '-' . str_pad($json['periodo']['mes'], 2, '0', STR_PAD_LEFT) . '-01',
                        ]);

                        SolicitudVinculacion::firstOrCreate(
                            [
                                'contratista_id' => $contratista->id,
                                'mandante_id'    => $mandante->id,
                                'tipo_solicitud' => $tipoSolicitud,
                            ],
                            [
                                'estado'             => 'APROBADA',
                                'unidad_organizacional_mandante_id' => $uo->id,
                                'numero_contrato'    => $json['sistema']['numero_contrato'] ?? 'HIST-' . $json['periodo']['anio'],
                                'dependencia_id'     => $dependencia->id,
                            ]
                        );
                    } else {
                        $vinculacion = new ContratistaUnidadOrganizacional(['id' => 0]);
                    }
                    $resultadoIndividual['mensajes'][] = "⚠ Vinculación Contratista↔UO creada automáticamente.";
                }

                // 6. Verificar Duplicados
                if ($vinculacion->id) {
                    $carpetaExistente = CarpetaVerificacion::where('contratista_unidad_organizacional_id', $vinculacion->id)
                        ->where('anio', $json['periodo']['anio'])
                        ->where('mes', $json['periodo']['mes'])
                        ->first();

                    if ($carpetaExistente && !$forzar) {
                        throw new Exception("Ya existe una carpeta para este período (ID: {$carpetaExistente->id}).");
                    }
                    if ($carpetaExistente && $forzar) {
                        if (!$isDryRun) $carpetaExistente->delete();
                        $resultadoIndividual['mensajes'][] = "⚠ Carpeta existente eliminada (--forzar).";
                    }
                }

                // 7. DRY-RUN
                if ($isDryRun) {
                    $resultadoIndividual['mensajes'][] = "🧪 [DRY-RUN] CarpetaVerificacion simulada.";
                    $resultadoIndividual['exito'] = true;
                    $successCount++;
                    $resultados[] = $resultadoIndividual;
                    continue;
                }

                // 8. IMPORTACIÓN REAL
                \Illuminate\Support\Facades\Log::info("Iniciando importación real para vinculación {$vinculacion->id}");
                DB::beginTransaction();
                try {
                    $carpeta = $this->crearCarpeta($json, $vinculacion);
                    \Illuminate\Support\Facades\Log::info("Carpeta creada ID: {$carpeta->id}");
                    
                    $nominaMap = $this->importarNomina($json, $carpeta);
                    \Illuminate\Support\Facades\Log::info("Nómina importada: " . count($nominaMap) . " registros.");
                    
                    $stats = $this->importarContingencias($json, $carpeta, $nominaMap, $vinculacion);
                    \Illuminate\Support\Facades\Log::info("Contingencias importadas: " . $stats['contingencias']);

                    DB::commit();

                    $resultadoIndividual['exito'] = true;
                    $resultadoIndividual['carpeta_id'] = $carpeta->id;
                    $resultadoIndividual['mensajes'][] = "✅ Carpeta creada exitosamente.";
                    $resultadoIndividual['mensajes'][] = "✅ Nómina importada: " . count($nominaMap) . " trabajadores.";
                    $resultadoIndividual['mensajes'][] = "✅ Contingencias: {$stats['contingencias']}, Complementarios: {$stats['complementarios']}";
                    
                    $successCount++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }

            } catch (\Exception $e) {
                $resultadoIndividual['exito'] = false;
                $resultadoIndividual['mensajes'][] = "❌ Error: " . $e->getMessage();
                $failCount++;
            }

            $resultados[] = $resultadoIndividual;
        }

        return [
            'exitosos' => $successCount,
            'fallidos' => $failCount,
            'detalles' => $resultados
        ];
    }

    private function crearCarpeta(array $json, ContratistaUnidadOrganizacional $vinculacion): CarpetaVerificacion
    {
        $r = $json['resumen'];
        return CarpetaVerificacion::create([
            'contratista_unidad_organizacional_id' => $vinculacion->id,
            'anio'                          => $json['periodo']['anio'],
            'mes'                           => $json['periodo']['mes'],
            'estado'                        => 'ENVIADO',
            'estado_revision'               => 'EMITIDO',
            'tipo_envio'                    => 'NORMAL',
            'fecha_envio'                   => $json['fecha_emision_certificado'] ?? now(),
            'fecha_emision'                 => $json['fecha_emision_certificado'] ?? now(),
            'fecha_auditoria'               => $json['fecha_emision_certificado'] ?? now(),
            'fin_doy_finalizado'            => true,
            'ia_datos_extraidos'            => true,
            'observaciones_auditor'         => $json['observaciones_auditor'] ?? null,
            'fin_contratados_periodo'       => $r['contratados_periodo'] ?? 0,
            'fin_desvinculados_periodo'     => $r['desvinculados_periodo'] ?? 0,
            'fin_total_vigentes'            => $r['total_vigentes'] ?? 0,
            'fin_trabajadores_revisados'    => $r['trabajadores_revisados'] ?? 0,
            'fin_remuneraciones_pagadas'    => $r['remuneraciones_pagadas'] ?? 0,
            'fin_cotizaciones_pagadas'      => $r['cotizaciones_pagadas'] ?? 0,
            'fin_aviso_previo_trabajadores' => $r['aviso_previo_trabajadores'] ?? 0,
            'fin_aviso_previo_total'        => $r['aviso_previo_total'] ?? 0,
            'fin_anio_servicio_trabajadores'=> $r['anio_servicio_trabajadores'] ?? 0,
            'fin_anio_servicio_total'       => $r['anio_servicio_total'] ?? 0,
            'fin_feriado_trabajadores'      => $r['feriado_trabajadores'] ?? 0,
            'fin_feriado_total'             => $r['feriado_total'] ?? 0,
        ]);
    }

    private function importarNomina(array $json, CarpetaVerificacion $carpeta): array
    {
        $nominaMap = [];
        foreach ($json['nomina_trabajadores'] as $trab) {
            $cvt = CarpetaVerificacionTrabajador::create([
                'carpeta_verificacion_id'   => $carpeta->id,
                'trabajador_vinculacion_id' => null,   // Histórico: sin vinculación real
                'snapshot_rut'             => $trab['rut'],
                'snapshot_nombres'         => $trab['nombre'],
                'snapshot_cargo'           => $trab['cargo'] ?? null,
                'snapshot_fecha_contrato'  => $trab['fecha_contrato'] ?? null,
                'snapshot_fecha_ingreso'   => $trab['fecha_contrato'] ?? null,
                'tipo_registro'            => 'HISTORICO',
                'estado_revision'          => 'VERIFICADO',
            ]);
            $nominaMap[$trab['rut']] = $cvt;
        }
        return $nominaMap;
    }

    private function importarContingencias(array $json, CarpetaVerificacion $carpeta, array $nominaMap, ContratistaUnidadOrganizacional $vinculacion): array
    {
        $totalConting = 0;
        $totalCompl   = 0;

        foreach ($json['contingencias'] as $grupo) {
            $clasificacion = $grupo['clasificacion'];
            $causal        = $grupo['causal'];
            $solucionados  = [];

            foreach ($grupo['trabajadores_afectados'] as $trab) {
                $cvt    = $nominaMap[$trab['rut']] ?? null;
                $codigo = $carpeta->generarCodigoIncidencia();

                $contingencia = CarpetaTrabajadorContingencia::create([
                    'carpeta_verificacion_id'            => $carpeta->id,
                    'carpeta_verificacion_trabajador_id' => $cvt?->id,
                    'tipo'           => 'contingencia',
                    'subtipo'        => 'retenible',
                    'clasificacion'  => $clasificacion,
                    'aplica_empresa' => false,
                    'codigo'         => $codigo,
                    'causal'         => $causal,
                    'monto'          => $trab['monto_contingencia'],
                    'es_retenible'   => true,
                    'estado_subsanacion' => $trab['solucionado']['fue_solucionado']
                        ? 'SUBSANADO'
                        : 'PENDIENTE',
                ]);
                $totalConting++;

                if (!empty($trab['solucionado']['fue_solucionado'])) {
                    $solucionados[] = [
                        'contingencia'   => $contingencia,
                        'monto_sol'      => $trab['solucionado']['monto_solucionado'],
                        'estado'         => $trab['solucionado']['estado'],
                        'fecha_solucion' => $trab['solucionado']['fecha_solucion'],
                    ];
                }
            }

            // Un SC consolidado por grupo de soluciones
            if (!empty($solucionados)) {
                $fechaSol = !empty($solucionados[0]['fecha_solucion'])
                    ? \Carbon\Carbon::parse($solucionados[0]['fecha_solucion'])
                    : now();

                $sc = SolicitudComplementaria::create([
                    'carpeta_verificacion_id'             => $carpeta->id,
                    'contratista_unidad_organizacional_id'=> $vinculacion->id,
                    'estado'           => 'EMITIDO',
                    'fecha_envio'      => $fechaSol,
                    'fecha_revision'   => $fechaSol,
                    'observaciones_auditor' => $json['observaciones_auditor'] ?? null,
                ]);
                $totalCompl++;

                foreach ($solucionados as $sol) {
                    SolicitudComplementariaItem::create([
                        'solicitud_complementaria_id'        => $sc->id,
                        'carpeta_trabajador_contingencia_id' => $sol['contingencia']->id,
                        'estado_auditor'                     => $sol['estado'] === 'TOTAL' ? 'TOTAL' : 'PARCIAL',
                        'monto_solucionado'                  => $sol['monto_sol'],
                        'observaciones_auditor'              => null,
                    ]);
                }
            }
        }

        return ['contingencias' => $totalConting, 'complementarios' => $totalCompl];
    }
}
