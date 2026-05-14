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

class SincronizacionTemplateSheet implements WithHeadings, WithStyles, WithEvents, WithColumnFormatting, WithTitle
{
    protected $mandanteId;

    public function __construct($mandanteId = null)
    {
        $this->mandanteId = $mandanteId;
    }

    /**
     * Columnas de la plantilla de sincronización.
     * NOTA: resultado_validacion_origen es OBLIGATORIO (a diferencia del importador normal)
     * ya que la lógica de calidad necesita saber si el doc del sistema viejo era Aprobado o Rechazado.
     */
    protected $headers = [
        // OBLIGATORIOS (marcados con *)
        'Mandante*',
        'Contratista*',
        'Tipo de Entidad*',
        'ID/RUT/Patente Entidad*',
        'Regla Documental*',
        'Nombre Documento (Snapshot)*',
        'Nombre Archivo Fisico*',
        'Resultado Validacion Origen*',   // → clave: resultado_validacion_origen (Aprobado/Rechazado)
        'Fecha Vencimiento*',              // → clave: fecha_vencimiento
        // OPCIONALES
        'Unidad Organizacional',
        'Fecha Emision',
        'Periodo',
        'ID Validador ASEM',
        'Observacion Validador',

    ];

    protected $requiredColumns = [
        'Mandante*',
        'Contratista*',
        'Tipo de Entidad*',
        'ID/RUT/Patente Entidad*',
        'Regla Documental*',
        'Nombre Documento (Snapshot)*',
        'Nombre Archivo Fisico*',
        'Resultado Validacion Origen*',
        'Fecha Vencimiento*',
    ];


    public function headings(): array
    {
        return $this->headers;
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_TEXT,  // ID/RUT/Patente
            'I' => NumberFormat::FORMAT_TEXT,  // Fecha Vencimiento
            'K' => NumberFormat::FORMAT_TEXT,  // Fecha Emisión
            'L' => NumberFormat::FORMAT_TEXT,  // Periodo
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header general — violeta oscuro (para distinguir del importador)
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF6D28D9'], // Violet-700
            ],
        ]);

        // Columnas obligatorias — rojo oscuro
        foreach ($this->headers as $index => $heading) {
            if (in_array($heading, $this->requiredColumns)) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                $sheet->getStyle($columnLetter . '1')->applyFromArray([
                    'fill' => [
                        'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFB91C1C'],
                    ],
                ]);
            }
        }

        return [];
    }

    public function title(): string
    {
        return 'Sincronización de Documentos';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = 1001;

                // Auto-size todas las columnas
                foreach (range(1, count($this->headers)) as $col) {
                    $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
                }

                // Comentarios explicativos
                $sheet->getComment('A1')->getText()->createTextRun("Seleccione el Mandante/Principal.");
                $sheet->getComment('B1')->getText()->createTextRun("ID_REGISTRO del contratista o razón social.");
                $sheet->getComment('C1')->getText()->createTextRun("App\\Models\\Trabajador, App\\Models\\Vehiculo, App\\Models\\Contratista, etc.");
                $sheet->getComment('D1')->getText()->createTextRun("RUT (trabajador/empresa), Patente (vehículo) o N° Serie (maquinaria).");
                $sheet->getComment('G1')->getText()->createTextRun("Nombre EXACTO del PDF en la carpeta de sincronización.");
                $sheet->getComment('H1')->getText()->createTextRun("¿Cómo estaba el documento en el sistema viejo? Aprobado o Rechazado.");
                $sheet->getComment('I1')->getText()->createTextRun("Si el documento no vence, deje en blanco. Esto afecta la lógica de calidad.");
                $sheet->getComment('J1')->getText()->createTextRun("UO del mandante (opcional para documentos sin clasificación jerárquica).");

                // ── VALIDACIONES DESDE HOJA LISTADOS ──
                // Las validaciones empiezan desde fila 2 (justo después del header)
                $this->applyDropdown($sheet, 'A', $lastRow, 'Listados!$A$2:$A$1000'); // Mandantes
                $this->applyDropdown($sheet, 'B', $lastRow, 'Listados!$B$2:$B$1000'); // Contratistas
                $this->applyDropdown($sheet, 'C', $lastRow, 'Listados!$D$2:$D$100');  // Tipos Entidad
                $this->applyDropdown($sheet, 'E', $lastRow, 'Listados!$E$2:$E$10000', false); // Reglas
                $this->applyDropdown($sheet, 'H', $lastRow, 'Listados!$F$2:$F$10');   // Resultado Origen
                $this->applyDropdown($sheet, 'J', $lastRow, 'Listados!$C$2:$C$10000', false); // UOs

                // NO se agrega fila de ejemplo para evitar que se importe accidentalmente.
                // Los comentarios en los headers (arriba) orientan al operador.
            },
        ];
    }

    private function applyDropdown(Worksheet $sheet, string $column, int $lastRow, string $formula, bool $allowBlank = true)
    {
        $validation = $sheet->getCell($column . '2')->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank($allowBlank);
        $validation->setShowDropDown(true);
        $validation->setFormula1($formula);

        for ($i = 2; $i <= $lastRow; $i++) {
            $sheet->getCell($column . $i)->setDataValidation(clone $validation);
        }
    }
}
