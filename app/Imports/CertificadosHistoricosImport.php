<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class CertificadosHistoricosImport implements ToCollection, WithHeadingRow, WithCustomCsvSettings
{
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';'
        ];
    }

    private $certificadosEstructurados = [];

    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        $agrupados = [];

        foreach ($rows as $row) {
            // Ignorar filas completamente vacías
            if (!isset($row['mes_periodo']) || !isset($row['anio_periodo']) || !isset($row['rut_contratista'])) {
                continue;
            }

            // Generar clave única para el certificado
            $key = $row['mes_periodo'] . '-' . $row['anio_periodo'] . '-' . $row['rut_contratista'] . '-' . $row['rut_mandante'];

            if (!isset($agrupados[$key])) {
                $agrupados[$key] = [
                    'periodo' => [
                        'mes' => (int) $row['mes_periodo'],
                        'anio' => (int) $row['anio_periodo'],
                        'folio' => 'EXCEL-' . time(),
                    ],
                    'contratista' => [
                        'rut' => $row['rut_contratista'],
                        'razon_social' => $row['razon_social_contratista'] ?? 'SIN RAZON SOCIAL',
                        'direccion' => null,
                        'tipo_solicitud' => 'CONTRATISTA',
                        'email' => null,
                        'telefono' => null,
                        'rep_legal_nombre' => null,
                        'rep_legal_rut' => null,
                    ],
                    'empresa_principal' => [
                        'rut' => $row['rut_mandante'],
                        'razon_social' => 'MANDANTE IMPORTADO' // No es crítico, se busca por RUT
                    ],
                    'sistema' => [
                        'unidad_organizacional' => $row['unidad_organizacional'] ?? null,
                        'lugar_trabajo' => $row['lugar_trabajo'] ?? null,
                        'numero_contrato' => $row['numero_contrato'] ?? null,
                    ],
                    'resumen' => [
                        'contratados_periodo' => 0,
                        'desvinculados_periodo' => 0,
                        'total_vigentes' => 0,
                        'trabajadores_revisados' => 0,
                        'remuneraciones_pagadas' => 0,
                        'cotizaciones_pagadas' => 0,
                        'aviso_previo_trabajadores' => 0,
                        'aviso_previo_total' => 0,
                        'anio_servicio_trabajadores' => 0,
                        'anio_servicio_total' => 0,
                        'feriado_trabajadores' => 0,
                        'feriado_total' => 0,
                    ],
                    'contingencias' => [],
                    'nomina_trabajadores' => [],
                    'fecha_emision_certificado' => now()->format('Y-m-d'),
                    'observaciones_auditor' => null,
                    
                    // Arrays auxiliares para evitar duplicados al procesar filas
                    '_ruts_nomina' => [],
                    '_contingencias_agrupadas' => []
                ];
            }

            // 1. Agregar a nómina si no existe aún
            $rutTrabajador = $row['rut_trabajador'] ?? null;
            if ($rutTrabajador && !in_array($rutTrabajador, $agrupados[$key]['_ruts_nomina'])) {
                $agrupados[$key]['nomina_trabajadores'][] = [
                    'rut' => $rutTrabajador,
                    'nombre' => $row['nombre_trabajador'] ?? 'SIN NOMBRE',
                    'fecha_contrato' => null,
                    'fecha_fin_contrato' => null,
                ];
                $agrupados[$key]['_ruts_nomina'][] = $rutTrabajador;
                $agrupados[$key]['resumen']['total_vigentes']++;
                $agrupados[$key]['resumen']['trabajadores_revisados']++;
            }

            // 2. Procesar contingencia si tiene
            $clasificacion = $row['contingencia_clasificacion'] ?? null;
            if ($clasificacion && $rutTrabajador) {
                if (!isset($agrupados[$key]['_contingencias_agrupadas'][$clasificacion])) {
                    $agrupados[$key]['_contingencias_agrupadas'][$clasificacion] = [
                        'clasificacion' => $clasificacion,
                        'causal' => $row['contingencia_causal'] ?? 'Causal genérica',
                        'trabajadores_afectados' => []
                    ];
                }

                $montoContingencia = (int) ($row['monto_adeudado'] ?? 0);
                $montoSolucionado = (int) ($row['monto_solucionado'] ?? 0);
                $fueSolucionado = $montoSolucionado > 0 || strtolower(trim((string)$row['solucionado'] ?? 'no')) === 'si';

                $agrupados[$key]['_contingencias_agrupadas'][$clasificacion]['trabajadores_afectados'][] = [
                    'rut' => $rutTrabajador,
                    'nombre' => $row['nombre_trabajador'] ?? 'SIN NOMBRE',
                    'codigo' => rand(100000, 999999), // Código dummy
                    'monto_contingencia' => $montoContingencia,
                    'solucionado' => [
                        'fue_solucionado' => $fueSolucionado,
                        'monto_solucionado' => $montoSolucionado,
                        'estado' => ($montoSolucionado >= $montoContingencia && $fueSolucionado) ? 'TOTAL' : ($fueSolucionado ? 'PARCIAL' : null),
                        'fecha_solucion' => $row['fecha_solucion'] ?? null,
                    ]
                ];
            }
        }

        // Limpiar arrays auxiliares y armar el array final
        foreach ($agrupados as $key => $certificado) {
            $certificado['contingencias'] = array_values($certificado['_contingencias_agrupadas']);
            unset($certificado['_ruts_nomina']);
            unset($certificado['_contingencias_agrupadas']);
            
            $this->certificadosEstructurados[] = $certificado;
        }
    }

    public function getCertificados(): array
    {
        return $this->certificadosEstructurados;
    }
}
