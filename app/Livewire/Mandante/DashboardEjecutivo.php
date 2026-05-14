<?php

namespace App\Livewire\Mandante;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Mandante;
use App\Models\Contratista;
use App\Models\Trabajador;
use App\Models\Vehiculo;
use App\Models\Maquinaria;
use App\Models\Embarcacion;
use App\Models\CarpetaTrabajadorContingencia;
use App\Models\DocumentoCargado;
use App\Models\TrabajadorVinculacion;

#[Layout('layouts.app')]
class DashboardEjecutivo extends Component
{
    public int $mandanteId;
    public string $mandanteNombre = '';

    // Filtros
    public string $anioFiltro = '';
    public string $ordenRankingContingencia = 'desc';
    public string $ordenRankingCumplimiento = 'asc'; // asc = peor primero

    public function mount(): void
    {
        $user = Auth::user();
        if (!$user || !$user->mandante_id) {
            abort(403);
        }
        $this->mandanteId = $user->mandante_id;
        $mandante = Mandante::find($this->mandanteId);
        $this->mandanteNombre = $mandante?->razon_social ?? 'Principal';
        $this->anioFiltro = (string) date('Y');
    }

    // ─────────────────────────────────────────────
    // Helpers: IDs de contratistas del mandante
    // ─────────────────────────────────────────────
    private function getContratistaIds(): array
    {
        $mandante = Mandante::find($this->mandanteId);
        $idsSolicitudes = $mandante?->contratistasPrincipalesAprobados()->pluck('contratistas.id')->toArray() ?? [];

        $idsUOs = DB::table('contratista_unidad_organizacional')
            ->join('unidades_organizacionales_mandante', 'contratista_unidad_organizacional.unidad_organizacional_mandante_id', '=', 'unidades_organizacionales_mandante.id')
            ->where('unidades_organizacionales_mandante.mandante_id', $this->mandanteId)
            ->pluck('contratista_unidad_organizacional.contratista_id')
            ->toArray();

        $idsDeps = DB::table('contratista_dependencia')
            ->join('dependencias', 'contratista_dependencia.dependencia_id', '=', 'dependencias.id')
            ->where('dependencias.mandante_id', $this->mandanteId)
            ->pluck('contratista_dependencia.contratista_id')
            ->toArray();

        return array_unique(array_merge($idsSolicitudes, $idsUOs, $idsDeps));
    }

    // ─────────────────────────────────────────────
    // KPIs Universo Controlado
    // ─────────────────────────────────────────────
    public function getKpisUniversoProperty(): array
    {
        $ids = $this->getContratistaIds();
        $totalEmpresas = count($ids);

        $totalTrabajadores = TrabajadorVinculacion::whereHas('trabajador', fn($q) => $q->whereIn('contratista_id', $ids))
            ->where('is_active', true)
            ->count();

        $totalVehiculos = Vehiculo::whereIn('contratista_id', $ids)->count();
        $totalMaquinarias = Maquinaria::whereIn('contratista_id', $ids)->count();
        $totalEmbarcaciones = Embarcacion::whereIn('contratista_id', $ids)->count();

        return [
            'empresas'     => $totalEmpresas,
            'trabajadores' => $totalTrabajadores,
            'vehiculos'    => $totalVehiculos,
            'maquinarias'  => $totalMaquinarias,
            'embarcaciones' => $totalEmbarcaciones,
        ];
    }

    // ─────────────────────────────────────────────
    // Contingencias: Saldo total y por clasificación
    // ─────────────────────────────────────────────
    public function getContingenciasResumenProperty(): array
    {
        $ids = $this->getContratistaIds();

        $query = CarpetaTrabajadorContingencia::query()
            ->where('tipo', 'contingencia')
            ->whereNotNull('monto')
            ->whereHas('carpetaVerificacion', function ($q) use ($ids) {
                $q->whereHas('vinculacion', fn($q2) => $q2->whereIn('contratista_id', $ids));
                if ($this->anioFiltro) {
                    $q->where('anio', $this->anioFiltro);
                }
            });

        $totalSaldo = (float) $query->sum('monto');

        $porClasificacion = (clone $query)
            ->select('clasificacion', 'subtipo', DB::raw('SUM(monto) as total'), DB::raw('COUNT(*) as cantidad'))
            ->groupBy('clasificacion', 'subtipo')
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => [
                'clasificacion' => $r->clasificacion,
                'subtipo'       => $r->subtipo,
                'total'         => (float) $r->total,
                'cantidad'      => $r->cantidad,
            ]);

        return [
            'total_saldo'       => $totalSaldo,
            'por_clasificacion' => $porClasificacion->toArray(),
        ];
    }

    // ─────────────────────────────────────────────
    // Ranking Empresas por Saldo de Contingencia
    // ─────────────────────────────────────────────
    public function getRankingContingenciaProperty(): array
    {
        $ids = $this->getContratistaIds();
        if (empty($ids)) return [];

        $dir = $this->ordenRankingContingencia === 'asc' ? 'asc' : 'desc';

        $rows = DB::table('carpeta_trabajador_contingencias as ctc')
            ->join('carpetas_verificacion as cv', 'ctc.carpeta_verificacion_id', '=', 'cv.id')
            ->join('contratista_unidad_organizacional as cuo', 'cv.contratista_unidad_organizacional_id', '=', 'cuo.id')
            ->join('contratistas as c', 'cuo.contratista_id', '=', 'c.id')
            ->whereIn('cuo.contratista_id', $ids)
            ->where('ctc.tipo', 'contingencia')
            ->whereNotNull('ctc.monto')
            ->when($this->anioFiltro, fn($q) => $q->where('cv.anio', $this->anioFiltro))
            ->select('c.id', 'c.razon_social', 'c.rut', DB::raw('SUM(ctc.monto) as saldo'), DB::raw('COUNT(*) as num_incidencias'))
            ->groupBy('c.id', 'c.razon_social', 'c.rut')
            ->orderBy('saldo', $dir)
            ->limit(15)
            ->get();

        return $rows->map(fn($r) => [
            'id'             => $r->id,
            'razon_social'   => $r->razon_social,
            'rut'            => $r->rut,
            'saldo'          => (float) $r->saldo,
            'num_incidencias'=> $r->num_incidencias,
        ])->toArray();
    }

    // ─────────────────────────────────────────────
    // Evolución de Contingencias por Año
    // ─────────────────────────────────────────────
    public function getEvolucionContingenciasProperty(): array
    {
        $ids = $this->getContratistaIds();
        if (empty($ids)) return [];

        $rows = DB::table('carpeta_trabajador_contingencias as ctc')
            ->join('carpetas_verificacion as cv', 'ctc.carpeta_verificacion_id', '=', 'cv.id')
            ->join('contratista_unidad_organizacional as cuo', 'cv.contratista_unidad_organizacional_id', '=', 'cuo.id')
            ->whereIn('cuo.contratista_id', $ids)
            ->where('ctc.tipo', 'contingencia')
            ->whereNotNull('ctc.monto')
            ->select('cv.anio', DB::raw('SUM(ctc.monto) as total'), DB::raw('COUNT(*) as cantidad'))
            ->groupBy('cv.anio')
            ->orderBy('cv.anio')
            ->get();

        return $rows->map(fn($r) => [
            'anio'     => (int) $r->anio,
            'total'    => (float) $r->total,
            'cantidad' => (int) $r->cantidad,
        ])->toArray();
    }

    // ─────────────────────────────────────────────
    // Cumplimiento Acreditación por Empresa
    // ─────────────────────────────────────────────
    public function getRankingCumplimientoProperty(): array
    {
        $ids = $this->getContratistaIds();
        if (empty($ids)) return [];

        $dir = $this->ordenRankingCumplimiento === 'asc' ? 'asc' : 'desc';

        // Calculamos % de documentos aprobados vs total por contratista
        $rows = DB::table('documentos_cargados as dc')
            ->join('contratistas as c', 'dc.contratista_id', '=', 'c.id')
            ->whereIn('dc.contratista_id', $ids)
            ->where('dc.mandante_id', $this->mandanteId)
            ->select(
                'c.id', 'c.razon_social', 'c.rut',
                DB::raw('COUNT(*) as total_docs'),
                DB::raw("SUM(CASE WHEN dc.resultado_validacion = 'Aprobado' THEN 1 ELSE 0 END) as aprobados"),
                DB::raw("SUM(CASE WHEN dc.resultado_validacion = 'Rechazado' THEN 1 ELSE 0 END) as rechazados"),
                DB::raw("SUM(CASE WHEN dc.resultado_validacion IS NULL THEN 1 ELSE 0 END) as pendientes")
            )
            ->groupBy('c.id', 'c.razon_social', 'c.rut')
            ->get();

        $data = $rows->map(function ($r) {
            $pct = $r->total_docs > 0 ? round(($r->aprobados / $r->total_docs) * 100, 1) : 0;
            return [
                'id'           => $r->id,
                'razon_social' => $r->razon_social,
                'rut'          => $r->rut,
                'total_docs'   => $r->total_docs,
                'aprobados'    => $r->aprobados,
                'rechazados'   => $r->rechazados,
                'pendientes'   => $r->pendientes,
                'pct'          => $pct,
            ];
        });

        if ($dir === 'asc') {
            $data = $data->sortBy('pct');
        } else {
            $data = $data->sortByDesc('pct');
        }

        return $data->values()->take(15)->toArray();
    }

    // ─────────────────────────────────────────────
    // Top Documentos Rechazados
    // ─────────────────────────────────────────────
    public function getTopDocumentosRechazadosProperty(): array
    {
        $ids = $this->getContratistaIds();
        if (empty($ids)) return [];

        $rows = DB::table('documentos_cargados')
            ->whereIn('contratista_id', $ids)
            ->where('mandante_id', $this->mandanteId)
            ->where('resultado_validacion', 'Rechazado')
            ->whereNotNull('nombre_documento_snapshot')
            ->select('nombre_documento_snapshot', DB::raw('COUNT(*) as total'))
            ->groupBy('nombre_documento_snapshot')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return $rows->map(fn($r) => [
            'nombre' => $r->nombre_documento_snapshot,
            'total'  => $r->total,
        ])->toArray();
    }

    // ─────────────────────────────────────────────
    // Distribución por tipo de contrato/cargo
    // ─────────────────────────────────────────────
    public function getDistribucionTrabajadoresProperty(): array
    {
        $ids = $this->getContratistaIds();
        if (empty($ids)) return [];

        $rows = DB::table('trabajador_vinculaciones as tv')
            ->join('trabajadores as t', 'tv.trabajador_id', '=', 't.id')
            ->join('tipos_contrato as tc', 'tv.tipo_contrato_id', '=', 'tc.id')
            ->whereIn('t.contratista_id', $ids)
            ->where('tv.is_active', true)
            ->select('tc.nombre', DB::raw('COUNT(*) as cantidad'))
            ->groupBy('tc.nombre')
            ->orderByDesc('cantidad')
            ->limit(8)
            ->get();

        return $rows->map(fn($r) => [
            'nombre'   => $r->nombre,
            'cantidad' => (int) $r->cantidad,
        ])->toArray();
    }

    // ─────────────────────────────────────────────
    // Resumen por Mes (para gráfico de barras mensual)
    // ─────────────────────────────────────────────
    public function getContingenciasMensualesProperty(): array
    {
        $ids = $this->getContratistaIds();
        if (empty($ids) || !$this->anioFiltro) return [];

        $rows = DB::table('carpeta_trabajador_contingencias as ctc')
            ->join('carpetas_verificacion as cv', 'ctc.carpeta_verificacion_id', '=', 'cv.id')
            ->join('contratista_unidad_organizacional as cuo', 'cv.contratista_unidad_organizacional_id', '=', 'cuo.id')
            ->whereIn('cuo.contratista_id', $ids)
            ->where('ctc.tipo', 'contingencia')
            ->whereNotNull('ctc.monto')
            ->where('cv.anio', $this->anioFiltro)
            ->select('cv.mes', DB::raw('SUM(ctc.monto) as total'), DB::raw('COUNT(*) as cantidad'))
            ->groupBy('cv.mes')
            ->orderBy('cv.mes')
            ->get();

        $meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        $result = [];
        foreach ($meses as $i => $m) {
            $found = $rows->firstWhere('mes', $i + 1);
            $result[] = [
                'mes'      => $m,
                'total'    => $found ? (float) $found->total : 0,
                'cantidad' => $found ? (int) $found->cantidad : 0,
            ];
        }

        return $result;
    }

    public function getAniosDisponiblesProperty(): array
    {
        $ids = $this->getContratistaIds();
        if (empty($ids)) return [date('Y')];

        $anios = DB::table('carpetas_verificacion as cv')
            ->join('contratista_unidad_organizacional as cuo', 'cv.contratista_unidad_organizacional_id', '=', 'cuo.id')
            ->whereIn('cuo.contratista_id', $ids)
            ->whereNotNull('cv.anio')
            ->distinct()
            ->orderByDesc('cv.anio')
            ->pluck('cv.anio')
            ->toArray();

        return !empty($anios) ? $anios : [date('Y')];
    }

    public function toggleOrdenContingencia(): void
    {
        $this->ordenRankingContingencia = $this->ordenRankingContingencia === 'desc' ? 'asc' : 'desc';
    }

    public function toggleOrdenCumplimiento(): void
    {
        $this->ordenRankingCumplimiento = $this->ordenRankingCumplimiento === 'asc' ? 'desc' : 'asc';
    }

    public function render()
    {
        return view('livewire.mandante.dashboard-ejecutivo', [
            'kpisUniverso'               => $this->kpisUniverso,
            'contingenciasResumen'       => $this->contingenciasResumen,
            'rankingContingencia'        => $this->rankingContingencia,
            'evolucionContingencias'     => $this->evolucionContingencias,
            'rankingCumplimiento'        => $this->rankingCumplimiento,
            'topDocumentosRechazados'    => $this->topDocumentosRechazados,
            'distribucionTrabajadores'   => $this->distribucionTrabajadores,
            'contingenciasMensuales'     => $this->contingenciasMensuales,
            'aniosDisponibles'           => $this->aniosDisponibles,
        ]);
    }
}
