<?php

namespace App\Exports;

use App\Exports\Sheets\TrabajadoresInstruccionesSheet;
use App\Exports\Sheets\TrabajadoresTemplateSheet;
use App\Exports\Sheets\TrabajadoresListadosSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TrabajadoresTemplateExport implements WithMultipleSheets
{
    use Exportable;

    protected $mandante_id;
    protected $contratista_id;

    public function __construct($mandante_id = null, $contratista_id = null)
    {
        $this->mandante_id = $mandante_id;
        $this->contratista_id = $contratista_id;
    }

    public function sheets(): array
    {
        return [
            new TrabajadoresInstruccionesSheet(),
            new TrabajadoresTemplateSheet($this->mandante_id, $this->contratista_id),
            new TrabajadoresListadosSheet($this->mandante_id, $this->contratista_id),
        ];
    }
}