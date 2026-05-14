<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class FacturacionMensualExport implements WithMultipleSheets
{
    protected $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function sheets(): array
    {
        return [
            new ResumenSheet($this->datos),
            new DetalleSheet($this->datos),
        ];
    }
}

class ResumenSheet implements FromView, WithTitle, ShouldAutoSize
{
    private $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function view(): View
    {
        return view('exports.facturacion-mensual-resumen', [
            'datos' => $this->datos
        ]);
    }

    public function title(): string
    {
        return 'Resumen de Facturacion';
    }
}

class DetalleSheet implements FromView, WithTitle, ShouldAutoSize
{
    private $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function view(): View
    {
        return view('exports.facturacion-mensual-detalle', [
            'datos' => $this->datos
        ]);
    }

    public function title(): string
    {
        return 'Detalle de Trabajadores';
    }
}