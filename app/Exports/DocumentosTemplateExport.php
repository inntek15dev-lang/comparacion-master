<?php

namespace App\Exports;

use App\Exports\Sheets\DocumentosTemplateSheet;
use App\Exports\Sheets\DocumentosListadosSheet;
use App\Exports\Sheets\DocumentosInstruccionesSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DocumentosTemplateExport implements WithMultipleSheets
{
    use Exportable;

    protected $mandanteId;
    protected $contratistaId;
    protected $reglaDocumentalId;

    public function __construct($mandanteId = null, $contratistaId = null, $reglaDocumentalId = null)
    {
        $this->mandanteId = $mandanteId;
        $this->contratistaId = $contratistaId;
        $this->reglaDocumentalId = $reglaDocumentalId;
    }

    public function sheets(): array
    {
        return [
            new DocumentosInstruccionesSheet(),
            new DocumentosTemplateSheet($this->mandanteId, $this->contratistaId, $this->reglaDocumentalId),
            new DocumentosListadosSheet($this->mandanteId, $this->contratistaId, $this->reglaDocumentalId),
        ];
    }
}