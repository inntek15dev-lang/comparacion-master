<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class FacturacionResumenExport implements FromView, WithTitle, ShouldAutoSize
{
    protected $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function view(): View
    {
        // Reutilizamos la vista de resumen existente, demostrando la eficiencia del diseño.
        return view('exports.facturacion-mensual-resumen', [
            'datos' => $this->datos
        ]);
    }

    public function title(): string
    {
        return 'Resumen de Facturacion';
    }
}