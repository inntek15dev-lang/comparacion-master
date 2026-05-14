<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Models\Mandante;
use App\Exports\Sheets\ReporteImcResumenSheet;
use App\Exports\Sheets\ReporteImcPrincipalSheet;
use App\Exports\Sheets\ReporteImcTopSheet;

class ReporteImcExport implements WithMultipleSheets
{
    use Exportable;

    protected $mandantesIds;
    protected $soloActivas;

    public function __construct(array $mandantesIds, bool $soloActivas = true)
    {
        $this->mandantesIds = $mandantesIds;
        $this->soloActivas = $soloActivas;
    }

    public function sheets(): array
    {
        $sheets = [];

        // 1. Hoja de Resumen (Dashboard)
        $sheets[] = new ReporteImcResumenSheet($this->mandantesIds, $this->soloActivas);

        // 2. Una hoja por cada Principal seleccionada
        $mandantes = Mandante::whereIn('id', $this->mandantesIds)->orderBy('razon_social')->get();
        foreach ($mandantes as $mandante) {
            $sheets[] = new ReporteImcPrincipalSheet($mandante, $this->soloActivas);
        }

        // 3. Hoja de Top Mayor Riesgo/Carga
        $sheets[] = new ReporteImcTopSheet($this->mandantesIds, $this->soloActivas);

        return $sheets;
    }
}
