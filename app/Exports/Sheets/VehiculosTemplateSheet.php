<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithTitle;

class VehiculosTemplateSheet implements WithHeadings, WithStyles, WithEvents, WithColumnFormatting, WithTitle
{
    protected $requiredColumns = [
        'RUT Contratista*',
        'Patente*',
        'Año Fabricación*',
        'Marca*',
        'Tipo*',
        'Color*',
    ];

    public function headings(): array
    {
        return [
            'RUT Contratista*',           // A
            'Patente*',                   // B
            'Año Fabricación*',           // C
            'Marca*',                     // D
            'Tipo*',                      // E
            'Color*',                     // F
            'Tenencia',                   // G
            'Nombre U.O. Inicial',        // H
            'Nombre Lugar de Trabajo Inicial', // I
            'Sub-Tipo Vehículo (Opcional)', // J
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT, // RUT Contratista
            'B' => NumberFormat::FORMAT_TEXT, // Patente
            'C' => NumberFormat::FORMAT_TEXT, // Año Fabricación
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $headings = $this->headings();
        foreach ($headings as $index => $heading) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $isRequired = in_array($heading, $this->requiredColumns);
            $sheet->getStyle($columnLetter . '1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => $isRequired ? 'FFDC2626' : 'FF1D4ED8'],
                ],
            ]);
        }
        return [];
    }

    public function title(): string
    {
        return 'Vehículos';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = 1001;

                foreach (range('A', 'J') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }

                $sheet->getComment('B1')->getText()->createTextRun('Ingrese la patente completa, sin guiones ni puntos. Ej: ABCD12');
                $sheet->getComment('H1')->getText()->createTextRun('Opcional si se indica Sub-Tipo. Seleccione la U.O. de la lista. Las opciones muestran primero el Principal al que pertenecen.');
                $sheet->getComment('I1')->getText()->createTextRun('Opcional si se indica Sub-Tipo. Seleccione el Lugar de Trabajo de la lista. Las opciones muestran primero el Principal al que pertenecen.');
                $sheet->getComment('J1')->getText()->createTextRun('Opcional, pero REQUERIDO si no se indica UO/Lugar (para cargar en RESERVA). Seleccione el sub-tipo según el Principal.');

                // Aplicar validaciones de datos referenciando a la hoja oculta 'Listados'
                // A=Mandantes, B=Marcas, C=Tipos, D=Colores, E=Tenencias, F=UOs, G=Lugares, H=SubTipos
                $this->applyDataValidationFromSheet($sheet, 'D', $lastRow, 'Listados!$B$2:$B$1000', false); // Marcas
                $this->applyDataValidationFromSheet($sheet, 'E', $lastRow, 'Listados!$C$2:$C$1000', false); // Tipos
                $this->applyDataValidationFromSheet($sheet, 'F', $lastRow, 'Listados!$D$2:$D$1000', false); // Colores
                $this->applyDataValidationFromSheet($sheet, 'G', $lastRow, 'Listados!$E$2:$E$1000', true);  // Tenencias

                // Estos utilizan nombres compuestos largos y pueden tener muchos registros
                $this->applyDataValidationFromSheet($sheet, 'H', $lastRow, 'Listados!$F$2:$F$10000', false); // U.Os
                $this->applyDataValidationFromSheet($sheet, 'I', $lastRow, 'Listados!$G$2:$G$10000', false); // Lugares
                $this->applyDataValidationFromSheet($sheet, 'J', $lastRow, 'Listados!$H$2:$H$10000', true);  // Sub-Tipos
            },
        ];
    }

    private function applyDataValidationFromSheet(Worksheet $sheet, string $column, int $lastRow, string $formula, bool $allowBlank = true)
    {
        $validation = $sheet->getCell($column.'2')->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank($allowBlank);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Entrada no válida');
        $validation->setError('El valor no está en la lista desplegable.');
        $validation->setPromptTitle('Seleccione un valor');
        $validation->setPrompt('Por favor, elija un valor de la lista.');
        
        // La fórmula apunta a un rango en otra hoja
        $validation->setFormula1($formula);

        for ($i = 2; $i <= $lastRow; $i++) {
            $sheet->getCell($column.$i)->setDataValidation(clone $validation);
        }
    }
}
