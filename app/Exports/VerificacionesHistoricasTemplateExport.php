<?php

namespace App\Exports;

use App\Exports\Sheets\VerificacionesHistoricasInstruccionesSheet;
use App\Exports\Sheets\VerificacionesHistoricasTemplateSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class VerificacionesHistoricasTemplateExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        return [
            new VerificacionesHistoricasInstruccionesSheet(),
            new VerificacionesHistoricasTemplateSheet(),
        ];
    }
}
