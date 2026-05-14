<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ProduccionValidadoresExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    protected Collection $data;
    protected array $filtros;

    public function __construct(Collection $data, array $filtros)
    {
        $this->data = $data;
        $this->filtros = $filtros;
    }

    public function title(): string
    {
        return 'Producción de Validadores';
    }

    public function collection()
    {
        return $this->data->map(function ($item) {
            return [
                'VALIDADOR' => $item->validador_nombre,
                'ROL' => $item->rol,
                'TOTAL REVISADOS' => $item->total_revisados,
                'APROBADOS' => $item->aprobados,
                'RECHAZADOS' => $item->rechazados,
                'ERRORES (*)' => $item->errores,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Validador',
            'Rol',
            'Total Revisados',
            'Aprobados',
            'Rechazados',
            'Errores (*)',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            6 => ['font' => ['bold' => true]], // Fila de encabezados
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Insertar filas para el encabezado del informe
                $sheet->insertNewRowBefore(1, 5);

                // Combinar celdas para el título
                $sheet->mergeCells('A1:F1');
                $sheet->setCellValue('A1', 'Informe de Producción de Validadores');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Añadir información de filtros
                $sheet->setCellValue('A3', 'Fecha de Generación:');
                $sheet->setCellValue('B3', now()->format('d-m-Y H:i:s'));
                
                if ($this->filtros['fecha_desde'] && $this->filtros['fecha_hasta']) {
                    $sheet->setCellValue('A4', 'Periodo de Validación:');
                    $sheet->setCellValue('B4', \Carbon\Carbon::parse($this->filtros['fecha_desde'])->format('d-m-Y') . ' al ' . \Carbon\Carbon::parse($this->filtros['fecha_hasta'])->format('d-m-Y'));
                }
                if ($this->filtros['documento']) {
                    $sheet->setCellValue('A5', 'Filtro de Documento:');
                    $sheet->setCellValue('B5', $this->filtros['documento']);
                }

                // Añadir fila de totales al final
                $lastRow = $sheet->getHighestRow();
                $sheet->setCellValue("A" . ($lastRow + 1), 'TOTALES GENERALES');
                $sheet->getStyle("A" . ($lastRow + 1))->getFont()->setBold(true);
                $sheet->setCellValue("C" . ($lastRow + 1), "=SUM(C7:C{$lastRow})");
                $sheet->setCellValue("D" . ($lastRow + 1), "=SUM(D7:D{$lastRow})");
                $sheet->setCellValue("E" . ($lastRow + 1), "=SUM(E7:E{$lastRow})");
                $sheet->setCellValue("F" . ($lastRow + 1), "=SUM(F7:F{$lastRow})");
                $sheet->getStyle("A" . ($lastRow + 1) . ":F" . ($lastRow + 1))->getFont()->setBold(true);
            },
        ];
    }
}