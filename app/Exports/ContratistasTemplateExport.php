<?php

namespace App\Exports;

use App\Exports\Sheets\ContratistasInstruccionesSheet;
use App\Exports\Sheets\ContratistasTemplateSheet;
use App\Exports\Sheets\ContratistasListadosSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ContratistasTemplateExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private ?int $mandanteId = null, private ?int $contratistaId = null) {}

    public function sheets(): array
    {
        return [
            new ContratistasInstruccionesSheet(),
            new ContratistasTemplateSheet($this->mandanteId, $this->contratistaId),
            new ContratistasListadosSheet(),
        ];
    }
}