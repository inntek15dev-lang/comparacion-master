<?php

namespace App\Exports;

use App\Exports\Sheets\VehiculosTemplateSheet;
use App\Exports\Sheets\VehiculosListadosSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class VehiculosTemplateExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        return [
            new VehiculosTemplateSheet(),
            new VehiculosListadosSheet(),
        ];
    }
}