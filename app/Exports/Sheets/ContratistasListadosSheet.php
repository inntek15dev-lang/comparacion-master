<?php

namespace App\Exports\Sheets;

use App\Models\Mandante;
use App\Models\Region;
use App\Models\Comuna;
use App\Models\TipoEmpresaLegal;
use App\Models\Rubro;
use App\Models\RangoCantidadTrabajadores;
use App\Models\Mutualidad;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\Dependencia;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ContratistasListadosSheet implements FromCollection, WithTitle, WithHeadings, WithEvents
{
    public function collection()
    {
        $mandantes = Mandante::where('is_active', true)->orderBy('razon_social')->pluck('razon_social')->toArray();
        $regiones = Region::where('is_active', true)->orderBy('nombre')->pluck('nombre')->toArray();
        $comunas = Comuna::where('is_active', true)->orderBy('nombre')->pluck('nombre')->toArray();
        $tiposEmpresa = TipoEmpresaLegal::where('is_active', true)->orderBy('nombre')->pluck('nombre')->toArray();
        $rubros = Rubro::where('is_active', true)->orderBy('nombre')->pluck('nombre')->toArray();
        $rangos = RangoCantidadTrabajadores::where('is_active', true)->orderBy('id')->pluck('nombre')->toArray();
        $mutualidades = Mutualidad::where('is_active', true)->orderBy('nombre')->pluck('nombre')->toArray();

        // Construir listas compuestas: "MANDANTE - RECURSO"
        $uos = UnidadOrganizacionalMandante::with('mandante')->where('is_active', true)->get()->map(function ($uo) {
            return ($uo->mandante->razon_social ?? 'SIN MANDANTE') . ' — ' . $uo->nombre_jerarquico;
        })->sort()->values()->toArray();

        // Lugares (dependencias) no tienen is_active en esta DB
        $lugares = Dependencia::with('mandante')->get()->map(function ($dep) {
            return ($dep->mandante->razon_social ?? 'SIN MANDANTE') . ' — ' . $dep->nombre_jerarquico;
        })->sort()->values()->toArray();

        // Encontrar la lista más larga para iterar
        $maxRows = max(
            count($mandantes), count($regiones), count($comunas), count($tiposEmpresa),
            count($rubros), count($rangos), count($mutualidades), count($uos), count($lugares)
        );

        $data = [];
        for ($i = 0; $i < $maxRows; $i++) {
            $data[] = [
                $mandantes[$i] ?? null,
                $regiones[$i] ?? null,
                $comunas[$i] ?? null,
                $tiposEmpresa[$i] ?? null,
                $rubros[$i] ?? null,
                $rangos[$i] ?? null,
                $mutualidades[$i] ?? null,
                $uos[$i] ?? null,
                $lugares[$i] ?? null,
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Mandantes',
            'Regiones',
            'Comunas',
            'Tipos Empresa Legal',
            'Rubros',
            'Rangos Empleados',
            'Mutualidades',
            'Unidades Organizativas (UO)',
            'Lugares de Trabajo'
        ];
    }

    public function title(): string
    {
        return 'Listados';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Ocultar esta hoja para que el usuario no la vea, pero sirva para las validaciones
                $event->sheet->getDelegate()->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
            },
        ];
    }
}
