<?php

namespace App\Exports\Sheets;

use App\Models\MarcaVehiculo;
use App\Models\TipoVehiculo;
use App\Models\ColorVehiculo;
use App\Models\TenenciaVehiculo;
use App\Models\SubTipoVehiculoMandante;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\Dependencia;
use App\Models\Mandante;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VehiculosListadosSheet implements FromCollection, WithTitle, WithHeadings, WithEvents
{
    public function collection()
    {
        $mandantes = Mandante::where('is_active', true)->orderBy('razon_social')->pluck('razon_social')->toArray();
        $marcas = MarcaVehiculo::where('is_active', true)->orderBy('nombre')->pluck('nombre')->toArray();
        $tipos = TipoVehiculo::where('is_active', true)->orderBy('nombre')->pluck('nombre')->toArray();
        $colores = ColorVehiculo::where('is_active', true)->orderBy('nombre')->pluck('nombre')->toArray();
        $tenencias = TenenciaVehiculo::where('is_active', true)->orderBy('nombre')->pluck('nombre')->toArray();

        // Construir listas compuestas: "MANDANTE - RECURSO"
        $uos = UnidadOrganizacionalMandante::with('mandante')->where('is_active', true)->get()->map(function ($uo) {
            return ($uo->mandante->razon_social ?? 'SIN MANDANTE') . ' — ' . $uo->nombre_jerarquico;
        })->sort()->values()->toArray();

        // Lugares (dependencias) no tienen is_active en esta DB
        $lugares = Dependencia::with('mandante')->get()->map(function ($dep) {
            return ($dep->mandante->razon_social ?? 'SIN MANDANTE') . ' — ' . $dep->nombre_jerarquico;
        })->sort()->values()->toArray();

        // Sub-tipos compuestos: "MANDANTE — SUBTIPO"
        $subtipos = SubTipoVehiculoMandante::with('mandante')
            ->where('is_active', true)
            ->orderBy('nombre')
            ->get()
            ->map(function ($st) {
                return ($st->mandante->razon_social ?? 'SIN MANDANTE') . ' — ' . $st->nombre;
            })->sort()->values()->toArray();

        // Encontrar la lista más larga para iterar
        $maxRows = max(
            count($mandantes), count($marcas), count($tipos), count($colores),
            count($tenencias), count($uos), count($lugares), count($subtipos)
        );

        $data = [];
        for ($i = 0; $i < $maxRows; $i++) {
            $data[] = [
                $mandantes[$i] ?? null,
                $marcas[$i] ?? null,
                $tipos[$i] ?? null,
                $colores[$i] ?? null,
                $tenencias[$i] ?? null,
                $uos[$i] ?? null,
                $lugares[$i] ?? null,
                $subtipos[$i] ?? null,
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Mandantes',
            'Marcas de Vehículos',
            'Tipos de Vehículos',
            'Colores',
            'Tenencias',
            'Unidades Organizativas (UO)',
            'Lugares de Trabajo',
            'Sub-Tipos de Vehículo (por Principal)'
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
