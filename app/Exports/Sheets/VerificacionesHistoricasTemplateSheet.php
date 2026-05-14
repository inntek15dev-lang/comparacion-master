<?php

namespace App\Exports\Sheets;

use App\Models\Mandante;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class VerificacionesHistoricasTemplateSheet implements WithTitle, FromArray, WithStyles, WithEvents
{
    public function title(): string
    {
        return 'Plantilla';
    }

    public function array(): array
    {
        // Cabecera
        $headers = [
            'ID_REGISTRO *',
            'MANDANTE *',
            'LUGAR *',
            'CONTRATO *',
            'PERIODO_ANIO *',
            'PERIODO_MES *',
            'RESULTADO *',
            'MONTO_RETENIBLE',
            'MONTO_NO_RETENIBLE',
            'RUT_CONTRATISTA *',
        ];

        // Fila de ejemplo
        $example = [
            'REG-2024-001',
            'NOMBRE MANDANTE S.A.',
            'PLANTA NORTE',
            '2024-CONT-001',
            2024,
            12,
            'Limpio',
            '',
            '',
            '76.xxx.xxx-K',
        ];

        // Fila 2 con Contingencia (ejemplo con montos)
        $example2 = [
            'REG-2024-002',
            'NOMBRE MANDANTE S.A.',
            'PUERTO SUR',
            '2024-CONT-002',
            2024,
            11,
            'Contingencia',
            1500000,
            2000000,
            '77.xxx.xxx-K',
        ];

        return [$headers, $example, $example2];
    }

    public function styles(Worksheet $sheet)
    {
        // ── Cabecera ──────────────────────────────────────────────────────────
        $redStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFB91C1C']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFDC2626']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ];
        $blueStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1D4ED8']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF1D4ED8']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ];

        // Columnas obligatorias (rojo): A-G, J
        $sheet->getStyle('A1:G1')->applyFromArray($redStyle);
        $sheet->getStyle('J1')->applyFromArray($redStyle);

        // Columnas opcionales (azul): H, I
        $sheet->getStyle('H1:I1')->applyFromArray($blueStyle);

        // ── Filas de ejemplo ─────────────────────────────────────────────────
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]],
        ];
        $sheet->getStyle('A2:J3')->applyFromArray($dataStyle);

        // Fondo gris claro para filas ejemplo
        $sheet->getStyle('A2:J2')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF9FAFB']],
        ]);
        $sheet->getStyle('A3:J3')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEFF6FF']],
        ]);

        // ── Anchos de columna ─────────────────────────────────────────────────
        $widths = ['A' => 18, 'B' => 30, 'C' => 25, 'D' => 22, 'E' => 15, 'F' => 14, 'G' => 18, 'H' => 20, 'I' => 20, 'J' => 18];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Fila de cabecera: altura
        $sheet->getRowDimension(1)->setRowHeight(38);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $ws = $event->sheet->getDelegate();

                // Congelar cabecera
                $ws->freezePane('A2');

                // Validación desplegable para RESULTADO (columna G)
                $validation = $ws->getCell('G2')->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setFormula1('"Limpio,Obs,Contingencia,Ambos"');
                $validation->setShowDropDown(false);
                $validation->setAllowBlank(false);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Resultado inválido');
                $validation->setError('Use: Limpio, Obs, Contingencia o Ambos');

                // Copiar validación a filas 2-500
                for ($row = 3; $row <= 500; $row++) {
                    $cloned = clone $validation;
                    $ws->getCell("G{$row}")->setDataValidation($cloned);
                }

                // Formato numérico para montos (H, I) filas 2-500
                $ws->getStyle('H2:I500')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);

                // Formato texto para período año/mes
                $ws->getStyle('E2:F500')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);

                // Nota: columnas rojas = obligatorias, azules = opcionales (ver hoja INSTRUCCIONES)
            },
        ];
    }
}
