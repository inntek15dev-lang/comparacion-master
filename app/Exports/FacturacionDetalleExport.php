<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class FacturacionDetalleExport implements FromView, WithTitle, ShouldAutoSize
{
    protected $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function view(): View
    {
        return view('exports.facturacion-mensual-detalle-individual', [
            'datos' => $this->datos
        ]);
    }

    public function title(): string
    {
        return 'Detalle Facturacion';
    }
}