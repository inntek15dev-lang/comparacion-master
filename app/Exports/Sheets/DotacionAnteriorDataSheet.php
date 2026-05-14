<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Models\ContratistaUnidadOrganizacional;
use App\Models\CargoMandante;
use PhpOffice\PhpSpreadsheet\NamedRange;

class DotacionAnteriorDataSheet implements WithTitle, FromArray, WithEvents
{
    public $mandanteId;

    public function __construct($mandanteId)
    {
        $this->mandanteId = $mandanteId;
    }

    public function title(): string
    {
        return 'Datos';
    }

    public function array(): array
    {
        $cuos = ContratistaUnidadOrganizacional::with('dependencia')
            ->whereHas('unidadOrganizacionalMandante', function($q) {
                $q->where('mandante_id', $this->mandanteId);
            })
            ->get();
            
        $lugares = $cuos->pluck('dependencia.nombre')->filter()->unique()->values()->toArray();
        $contratos = $cuos->pluck('numero_contrato')->filter()->unique()->values()->toArray();
        $cargos = CargoMandante::where('mandante_id', $this->mandanteId)->pluck('nombre_cargo')->filter()->unique()->values()->toArray();

        // Agregamos una primera fila de encabezados
        $data = [['LUGARES', 'CONTRATOS', 'CARGOS']];
        $maxRows = max(count($lugares), count($contratos), count($cargos), 1);

        for ($i = 0; $i < $maxRows; $i++) {
            $data[] = [
                $lugares[$i] ?? '',
                $contratos[$i] ?? '',
                $cargos[$i] ?? '',
            ];
        }

        return $data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $ws = $event->sheet->getDelegate();
                $wb = $ws->getParent();

                // Crear los rangos con nombre para validaciones.
                // Estimamos hasta 1000 filas para que cubra todo.
                $wb->addNamedRange(new NamedRange('ListLugares', $ws, '$A$2:$A$1000'));
                $wb->addNamedRange(new NamedRange('ListContratos', $ws, '$B$2:$B$1000'));
                $wb->addNamedRange(new NamedRange('ListCargos', $ws, '$C$2:$C$1000'));

                // Ocultar la hoja para que el usuario no la vea por defecto
                $ws->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
            }
        ];
    }
}
