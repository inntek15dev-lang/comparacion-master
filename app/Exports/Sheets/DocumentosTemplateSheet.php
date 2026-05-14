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

class DocumentosTemplateSheet implements WithHeadings, WithStyles, WithEvents, WithColumnFormatting, WithTitle
{
    protected $mandanteId;
    protected $contratistaId;
    protected $reglaDocumentalId;

    public function __construct($mandanteId = null, $contratistaId = null, $reglaDocumentalId = null)
    {
        $this->mandanteId = $mandanteId;
        $this->contratistaId = $contratistaId;
        $this->reglaDocumentalId = $reglaDocumentalId;
    }

    // Usamos headers técnicos que coincidan con los atributos del modelo pero más amigables
    protected $headers = [
        'Mandante*', 'Contratista*', 'Unidad Organizacional', 'Tipo de Entidad*', 'ID/RUT/Patente Entidad*',
        'Regla Documental*', 'Nombre Documento (Snapshot)*', 'Nombre Archivo Físico*', 'Ruta Destino (Opcional)',
        'Fecha Emisión', 'Fecha Vencimiento', 'Periodo', 'Estado Validación*', 'Resultado Validación',
        'ID Validador ASEM', 'ID Validador Mandante', 'Fecha Validación General', 'Fecha Validación ASEM', 'Fecha Validación Mandante',
        'Observación Validador', 'Observación Rechazo', 'Observación Interna ASEM', 'Motivo Revalidación',
        'Tipo Vencimiento (Snapshot)', 'Motivo Modif Vencimiento', 'Snapshot Criterios (JSON)', 'Observación Doc (Snapshot)'
    ];

    protected $requiredColumns = [
        'Mandante*', 'Contratista*', 'Unidad Organizacional*', 'Tipo de Entidad*', 'ID/RUT/Patente Entidad*',
        'Regla Documental*', 'Nombre Documento (Snapshot)*', 'Nombre Archivo Físico*',
        'Estado Validación*'
    ];

    public function headings(): array
    {
        return $this->headers;
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_TEXT, // Identificador Entidad
            'J' => NumberFormat::FORMAT_TEXT, // Fecha Emisión
            'K' => NumberFormat::FORMAT_TEXT, // Fecha Vencimiento
            'L' => NumberFormat::FORMAT_TEXT, // Periodo
            'Q' => NumberFormat::FORMAT_TEXT, // Fecha Validación Gral
            'R' => NumberFormat::FORMAT_TEXT, // Fecha Validación ASEM
            'S' => NumberFormat::FORMAT_TEXT, // Fecha Validación Mandante
            'Z' => NumberFormat::FORMAT_TEXT, // JSON Criterios
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:AA1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4338CA'], // Indigo-700
            ],
        ]);

        foreach ($this->headers as $index => $heading) {
            if (in_array($heading, $this->requiredColumns)) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                $sheet->getStyle($columnLetter.'1')->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFB91C1C'], // Red-700
                    ],
                ]);
            }
        }
        return [];
    }

    public function title(): string
    {
        return 'Migración de Documentos';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = 1001;

                foreach (range(1, 27) as $col) {
                    $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
                }

                // Comentarios
                $sheet->getComment('A1')->getText()->createTextRun("Seleccione el Mandante.");
                $sheet->getComment('E1')->getText()->createTextRun("Escriba el RUT (Persona), Patente (Vehículo) o N° Serie.");
                $sheet->getComment('G1')->getText()->createTextRun("Nombre descriptivo manual del documento en el sistema anterior.");
                $sheet->getComment('H1')->getText()->createTextRun("Nombre del PDF subido previamente a la carpeta temporal.");
                $sheet->getComment('I1')->getText()->createTextRun("OPCIONAL. Deje vacío para que el sistema organice el archivo automáticamente en la carpeta del recurso.");
                $sheet->getComment('M1')->getText()->createTextRun("Estado del documento en el flujo operacional.");
                $sheet->getComment('N1')->getText()->createTextRun("Aprobado o Rechazado.");
                $sheet->getComment('Z1')->getText()->createTextRun("Si tiene los criterios del sistema anterior, péguelos aquí en formato JSON.");

                // Validaciones de datos
                $this->applyDataValidationFromSheet($sheet, 'A', $lastRow, 'Listados!$A$2:$A$1000'); // Mandantes
                $this->applyDataValidationFromSheet($sheet, 'B', $lastRow, 'Listados!$B$2:$B$1000'); // Contratistas
                $this->applyDataValidationFromSheet($sheet, 'C', $lastRow, 'Listados!$C$2:$C$10000', false); // UOs
                $this->applyDataValidationFromSheet($sheet, 'D', $lastRow, 'Listados!$D$2:$D$100'); // Tipos Entidad
                $this->applyDataValidationFromSheet($sheet, 'F', $lastRow, 'Listados!$E$2:$E$10000', false); // Reglas
                $this->applyDataValidationFromSheet($sheet, 'M', $lastRow, 'Listados!$G$2:$G$100'); // Estados
                $this->applyDataValidationFromSheet($sheet, 'N', $lastRow, 'Listados!$F$2:$F$100'); // Resultados
                $this->applyDataValidationFromSheet($sheet, 'X', $lastRow, 'Listados!$H$2:$H$100'); // Tipos Venc Snapshot
            },
        ];
    }

    private function applyDataValidationFromSheet(Worksheet $sheet, string $column, int $lastRow, string $formula, bool $allowBlank = true)
    {
        $validation = $sheet->getCell($column.'2')->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank($allowBlank);
        $validation->setShowDropDown(true);
        $validation->setFormula1($formula);

        for ($i = 2; $i <= $lastRow; $i++) {
            $sheet->getCell($column.$i)->setDataValidation(clone $validation);
        }
    }
}
