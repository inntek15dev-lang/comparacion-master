<?php

namespace App\Exports;

use App\Exports\Sheets\DotacionAnteriorInstruccionesSheet;
use App\Exports\Sheets\DotacionAnteriorTemplateSheet;
use App\Exports\Sheets\DotacionAnteriorDataSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DotacionAnteriorTemplateExport implements WithMultipleSheets
{
    use Exportable;

    public $mandanteId;
    public $contratistaId;
    public $periodo;

    public function __construct($mandanteId, $contratistaId = null, $periodo = null)
    {
        $this->mandanteId    = $mandanteId;
        $this->contratistaId = $contratistaId;
        $this->periodo       = $periodo;
    }

    public function sheets(): array
    {
        return [
            new DotacionAnteriorTemplateSheet($this->mandanteId, $this->contratistaId, $this->periodo),
            new DotacionAnteriorInstruccionesSheet(),
            new DotacionAnteriorDataSheet($this->mandanteId),
        ];
    }
}
