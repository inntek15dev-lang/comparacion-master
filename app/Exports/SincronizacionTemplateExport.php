<?php

namespace App\Exports;

use App\Exports\Sheets\SincronizacionTemplateSheet;
use App\Exports\Sheets\SincronizacionListadosSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SincronizacionTemplateExport implements WithMultipleSheets
{
    use Exportable;

    protected $mandanteId;

    public function __construct($mandanteId = null)
    {
        $this->mandanteId = $mandanteId;
    }

    public function sheets(): array
    {
        return [
            new SincronizacionTemplateSheet($this->mandanteId),
            new SincronizacionListadosSheet($this->mandanteId),
        ];
    }
}
