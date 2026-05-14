<?php

namespace App\Exports\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\ReglaDocumental;

class ReporteImcTopSheet implements FromView, WithTitle, ShouldAutoSize, WithStyles
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
        $query = ReglaDocumental::with(['mandante', 'tipoEntidadControlada', 'nombreDocumento'])
            ->whereIn('mandante_id', $this->mandantesIds);

        if ($this->soloActivas) {
            $query->where('is_active', true);
        }

        $reglas = $query->get()->sortByDesc(function ($regla) {
            return $regla->imc ?? 0;
        })->take(25);

        $data = [];
        $rank = 1;
        foreach ($reglas as $r) {
            $imc = $r->imc;
            if ($imc === null || $imc <= 0) continue;

            $cargasAnio = $imc * 12;

            $data[] = [
                'Ranking' => '#' . $rank++,
                'Principal' => $r->mandante->razon_social ?? 'N/A',
                'Entidad' => $r->tipoEntidadControlada->nombre_entidad ?? 'N/A',
                'Documento' => $r->nombreDocumento->nombre ?? 'N/A',
                'Cargas/Año' => number_format($cargasAnio, 2, ',', '.'),
                'IMC (docs/mes)' => number_format($imc, 4, ',', '.'),
            ];
        }

        return view('reports.excel.imc-top', [
            'data' => $data
        ]);
    }

    public function title(): string
    {
        return 'Top 25 Mayor Carga global';
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }
}
