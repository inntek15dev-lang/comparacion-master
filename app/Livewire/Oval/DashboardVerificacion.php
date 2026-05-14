<?php

namespace App\Livewire\Oval;

use Livewire\Component;
use App\Models\Mandante;
use App\Models\CarpetaVerificacion;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardVerificacion extends Component
{
    public $principalId;
    public $anioSeleccionado;
    public $tabSeleccionado = 'empresas';

    public function mount()
    {
        $this->anioSeleccionado = date('Y');
        $primerMandante = Mandante::first();
        if ($primerMandante) {
            $this->principalId = $primerMandante->id;
        }
    }

    public function setTab($tab)
    {
        $this->tabSeleccionado = $tab;
        // Emitir un evento para repintar gráficos si es necesario
        $this->dispatch('tab-cambiada', tab: $tab);
    }

    public function updatedPrincipalId()
    {
        $this->dispatch('filtros-actualizados');
    }

    public function updatedAnioSeleccionado()
    {
        $this->dispatch('filtros-actualizados');
    }

    public function render()
    {
        $mandantes = Mandante::orderBy('razon_social')->get();
        $datos = [];

        if ($this->principalId) {
            $datos = $this->calcularMetricas();
        }

        return view('livewire.oval.dashboard-verificacion', [
            'mandantes' => $mandantes,
            'datos' => $datos
        ])->layout('layouts.app');
    }

    private function calcularMetricas()
    {
        // Base para los 12 meses (Ene-Dic) del año seleccionado
        $mesesNombres = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        
        for ($i = 1; $i <= 12; $i++) {
            $inicioMes = Carbon::create($this->anioSeleccionado, $i, 1)->startOfMonth()->format('Y-m-d');
            $finMes = Carbon::create($this->anioSeleccionado, $i, 1)->endOfMonth()->format('Y-m-d');

            // EMPRESAS activas en el mes (que tienen el servicio de Verificación activo)
            $countE = DB::table('contratista_unidad_organizacional as cuo')
                ->join('unidades_organizacionales_mandante as uom', 'cuo.unidad_organizacional_mandante_id', '=', 'uom.id')
                ->where('uom.mandante_id', $this->principalId)
                ->where('cuo.verifica', 1)
                ->where(function($q) use ($inicioMes, $finMes) {
                    $q->where(function($q2) use ($finMes) {
                        $q2->whereNull('cuo.fecha_inicio_verifica')
                           ->orWhere('cuo.fecha_inicio_verifica', '<=', $finMes);
                    })
                    ->where(function($q3) use ($inicioMes) {
                        $q3->whereNull('cuo.fecha_fin_verifica')
                           ->orWhere('cuo.fecha_fin_verifica', '>=', $inicioMes);
                    });
                })
                ->count(DB::raw('DISTINCT cuo.contratista_id'));
            
            $empresasHistorico[] = $countE;

            // TRABAJADORES activos en el mes
            $countT = DB::table('trabajador_vinculaciones as tv')
                ->join('unidades_organizacionales_mandante as uom', 'tv.unidad_organizacional_mandante_id', '=', 'uom.id')
                ->where('uom.mandante_id', $this->principalId)
                ->where(function($q) use ($inicioMes, $finMes) {
                    $q->where(function($q2) use ($finMes) {
                        $q2->whereNull('tv.fecha_contrato')
                           ->orWhere('tv.fecha_contrato', '<=', $finMes);
                    })
                    ->where(function($q3) use ($inicioMes) {
                        $q3->whereNull('tv.fecha_finiquito')
                           ->orWhere('tv.fecha_finiquito', '>=', $inicioMes);
                    });
                })
                ->count(DB::raw('DISTINCT tv.trabajador_id'));

            $trabajadoresHistorico[] = $countT;
        }
        
        $promedioAnualEmpresas = round(collect($empresasHistorico)->sum() / 12, 1);
        $promedioAnualTrabajadores = round(collect($trabajadoresHistorico)->sum() / 12);

        // --- 3. CONTINGENCIAS ---
        $contingenciasRetenibles = array_fill(0, 12, 0);
        $contingenciasNoRetenibles = array_fill(0, 12, 0);
        
        // Aquí deberíamos sumar los montos de la tabla incidencias_trabajadores
        // Por ahora lo inicializamos en 0 hasta conectar la tabla real
        
        return [
            'labels' => $mesesNombres,
            'empresas' => [
                'promedio_anual' => $promedioAnualEmpresas,
                'historico' => $empresasHistorico
            ],
            'trabajadores' => [
                'promedio_anual' => $promedioAnualTrabajadores,
                'historico' => $trabajadoresHistorico
            ],
            'contingencias' => [
                'retenibles' => $contingenciasRetenibles,
                'no_retenibles' => $contingenciasNoRetenibles
            ],
            'pareto' => [
                // Top 10 Riesgo
            ]
        ];
    }
}
