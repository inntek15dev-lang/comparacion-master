<?php

namespace App\Exports\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Mandante;
use App\Models\ReglaDocumental;
use App\Models\TipoEntidadControlable;

class ReporteImcResumenSheet implements FromView, WithTitle, ShouldAutoSize, WithStyles
{
    protected $mandantesIds;
    protected $soloActivas;

    public function __construct(array $mandantesIds, bool $soloActivas)
    {
        $this->mandantesIds = $mandantesIds;
        $this->soloActivas = $soloActivas;
    }

    public function view(): View
    {
        $data = [];
        $mandantes = Mandante::whereIn('id', $this->mandantesIds)->orderBy('razon_social')->get();
        $tiposEntidad = TipoEntidadControlable::orderBy('nombre_entidad')->get();

        $totalReglasGlobal = 0;
        $reglasActivasGlobal = 0;
        $imcTotalGlobal = 0;

        foreach ($mandantes as $mandante) {
            $totalReglasMandante = 0;
            $activasMandante = 0;
            $imcMandante = 0;

            foreach ($tiposEntidad as $tipo) {
                $query = ReglaDocumental::where('mandante_id', $mandante->id)
                    ->where('tipo_entidad_controlada_id', $tipo->id);

                if ($this->soloActivas) {
                    $query->where('is_active', true);
                }

                $reglas = $query->get();
                if ($reglas->isNotEmpty()) {
                    $total = $reglas->count();
                    $activas = $reglas->where('is_active', true)->count();
                    $imc = $reglas->sum('imc');

                    $totalReglasMandante += $total;
                    $activasMandante += $activas;
                    $imcMandante += $imc;

                    $cargasAnio = $imc * 12;

                    $data[] = [
                        'Principal' => $mandante->razon_social,
                        'Entidad' => $tipo->nombre_entidad,
                        'Total Reglas' => $total,
                        'Reglas Activas' => $activas,
                        'IMC Total' => $imc,
                        'Cargas Est' => $cargasAnio,
                        'is_total' => false
                    ];
                }
            }

            if ($totalReglasMandante > 0) {
                $data[] = [
                    'Principal' => 'TOTAL',
                    'Entidad' => $mandante->razon_social,
                    'Total Reglas' => $totalReglasMandante,
                    'Reglas Activas' => $activasMandante,
                    'IMC Total' => $imcMandante,
                    'Cargas Est' => $imcMandante * 12,
                    'is_total' => true
                ];
                
                $totalReglasGlobal += $totalReglasMandante;
                $reglasActivasGlobal += $activasMandante;
                $imcTotalGlobal += $imcMandante;
            }
        }

        return view('reports.excel.imc-resumen', [
            'data' => $data,
            'totalMandantes' => count($mandantes),
            'totalReglas' => $totalReglasGlobal,
            'reglasActivas' => $reglasActivasGlobal,
            'imcTotal' => $imcTotalGlobal
        ]);
    }

    public function title(): string
    {
        return 'Dashboard Resumen IMC';
    }

    public function styles(Worksheet $sheet)
    {
        // No explicit styles here since we use HTML inline CSS, which Laravel Excel parses beautifully.
        return [];
    }
}
