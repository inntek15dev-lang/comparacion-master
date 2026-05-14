<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ReporteSupervisionService
{
    protected Collection $data;
    protected array $entidadesControlables;
    protected array $promediosGenerales;

    public function __construct(Collection $data, array $entidadesControlables)
    {
        $this->data = $data;
        $this->entidadesControlables = $entidadesControlables;
        $this->promediosGenerales = $this->calcularPromediosGenerales();
    }

    public function generarDatosParaVista(): array
    {
        return [
            'promediosGenerales' => $this->promediosGenerales,
            'datosTabla' => $this->prepararDatosTabla(),
            'datosGraficos' => $this->prepararDatosGraficos(),
            'insights' => $this->generarInsights(),
        ];
    }

    private function calcularPromediosGenerales(): array
    {
        $promedios = [];
        $mapeo = [
            'EMPRESA' => 'cumplimiento_empresa',
            'PERSONA' => 'promedio_trabajadores',
            'VEHICULO' => 'promedio_vehiculos',
            'MAQUINARIA' => 'promedio_maquinarias',
            'EMBARCACION' => 'promedio_embarcaciones',
        ];

        $totalAcumulado = 0;
        $totalPesos = 0;

        foreach ($mapeo as $entidad => $clave) {
            if (in_array($entidad, $this->entidadesControlables) && $this->data->isNotEmpty() && isset($this->data->first()[$clave])) {
                if (str_starts_with($clave, 'promedio_')) {
                    $promedios[$clave] = (int) round($this->data->avg(fn($item) => $item[$clave]['promedio']));
                } else {
                    $promedios[$clave] = (int) round($this->data->avg($clave));
                }
            }
        }

        // ================== INICIO DE LA MODIFICACIÓN DOCTRINAL ==================
        // Cálculo del promedio total ponderado, operando sobre CONTEXTOS
        foreach ($this->data as $contexto) {
            $cumplimientoContexto = 0;
            $pesosContexto = 0;
            if (isset($contexto['cumplimiento_empresa'])) {
                $cumplimientoContexto += $contexto['cumplimiento_empresa'];
                $pesosContexto++;
            }
            foreach (['promedio_trabajadores', 'promedio_vehiculos', 'promedio_maquinarias', 'promedio_embarcaciones'] as $clavePromedio) {
                if (isset($contexto[$clavePromedio]) && $contexto[$clavePromedio]['total'] > 0) {
                    $cumplimientoContexto += $contexto[$clavePromedio]['promedio'];
                    $pesosContexto++;
                }
            }
            $contexto['cumplimiento_total'] = ($pesosContexto > 0) ? (int) round($cumplimientoContexto / $pesosContexto) : 100;
            $totalAcumulado += $contexto['cumplimiento_total'];
            $totalPesos++;
        }
        // ================== FIN DE LA MODIFICACIÓN DOCTRINAL ====================
        
        $promedios['cumplimiento_total'] = ($totalPesos > 0) ? (int) round($totalAcumulado / $totalPesos) : 100;

        return $promedios;
    }

    private function prepararDatosTabla(): Collection
    {
        // ================== INICIO DE LA MODIFICACIÓN DOCTRINAL ==================
        return $this->data->map(function ($contexto) {
            $cumplimientoContexto = 0;
            $pesosContexto = 0;
            if (isset($contexto['cumplimiento_empresa'])) {
                $cumplimientoContexto += $contexto['cumplimiento_empresa'];
                $pesosContexto++;
            }
            foreach (['promedio_trabajadores', 'promedio_vehiculos', 'promedio_maquinarias', 'promedio_embarcaciones'] as $clavePromedio) {
                if (isset($contexto[$clavePromedio]) && $contexto[$clavePromedio]['total'] > 0) {
                    $cumplimientoContexto += $contexto[$clavePromedio]['promedio'];
                    $pesosContexto++;
                }
            }
            $contexto['cumplimiento_total'] = ($pesosContexto > 0) ? (int) round($cumplimientoContexto / $pesosContexto) : 100;
            
            // Se crea una etiqueta contextual para la tabla
            $contexto['etiqueta_contextual'] = $contexto['razon_social'] . ' (' . $contexto['lugar_trabajo_nombre'] . ' / ' . $contexto['uo_nombre'] . ')';

            return $contexto;
        });
        // ================== FIN DE LA MODIFICACIÓN DOCTRINAL ====================
    }

    private function prepararDatosGraficos(): array
    {
        $datosTabla = $this->prepararDatosTabla();
        // ================== INICIO DE LA MODIFICACIÓN DOCTRINAL ==================
        // Las etiquetas ahora son contextuales para evitar duplicados y dar claridad
        $labels = $datosTabla->pluck('etiqueta_contextual');
        // ================== FIN DE LA MODIFICACIÓN DOCTRINAL ====================
        
        $graficoEmpresa = null;
        if (in_array('EMPRESA', $this->entidadesControlables)) {
            $graficoEmpresa = [
                'labels' => $labels,
                'data' => $datosTabla->pluck('cumplimiento_empresa'),
            ];
        }

        $graficoTrabajador = null;
        if (in_array('PERSONA', $this->entidadesControlables)) {
            $graficoTrabajador = [
                'labels' => $labels,
                'data' => $datosTabla->pluck('promedio_trabajadores.promedio'),
            ];
        }

        $graficoTotal = [
            'labels' => $labels,
            'data' => $datosTabla->pluck('cumplimiento_total'),
        ];

        return [
            'empresa' => $graficoEmpresa,
            'trabajador' => $graficoTrabajador,
            'total' => $graficoTotal,
        ];
    }

    private function generarInsights(): array
    {
        $insights = [];
        $datosTabla = $this->prepararDatosTabla();
        if ($datosTabla->isEmpty()) return [];

        $promedioTotal = $this->promediosGenerales['cumplimiento_total'];
        // ================== INICIO DE LA MODIFICACIÓN DOCTRINAL ==================
        $mejorContexto = $datosTabla->sortByDesc('cumplimiento_total')->first();
        $peorContexto = $datosTabla->sortBy('cumplimiento_total')->first();

        if ($mejorContexto) {
            // El insight ahora apunta a un contexto específico
            $insights[] = "Existe un % de cumplimiento total cercano al {$promedioTotal}% influenciado positivamente por el contexto operativo {$mejorContexto['etiqueta_contextual']} con un {$mejorContexto['cumplimiento_total']}% de cumplimiento.";
        }

        if (in_array('PERSONA', $this->entidadesControlables)) {
            $promedioTrabajador = $this->promediosGenerales['promedio_trabajadores'];
            if ($promedioTrabajador < 70) {
                $peoresContextosTrabajadores = $datosTabla->sortBy('promedio_trabajadores.promedio')->take(3)->pluck('etiqueta_contextual')->implode(', ');
                $insights[] = "Un punto a reforzar es el cumplimiento por parte del trabajador (promedio {$promedioTrabajador}%), especialmente en los contextos: {$peoresContextosTrabajadores}.";
            }
        }
        // ================== FIN DE LA MODIFICACIÓN DOCTRINAL ====================
        
        $insights[] = "Existe un permanente acompañamiento de capacitación, apoyo y soporte de la empresa OVAL.";

        return $insights;
    }
}