<?php

namespace App\Exports\Sheets;

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

class DotacionAnteriorInstruccionesSheet implements WithTitle, FromArray, WithStyles, WithEvents
{
    public function title(): string
    {
        return 'INSTRUCCIONES';
    }

    public function array(): array
    {
        return [
            ['GUÍA DE CARGA MASIVA — NÓMINA DE DOTACIÓN ANTERIOR', '', '', ''],
            [''],
            ['I. OBJETIVO', '', '', ''],
            ['¿Qué es este módulo?', 'Permite cargar la lista exacta de trabajadores presentes en el período anterior para poblar la primera carpeta de verificación del nuevo sistema.', '', ''],
            [''],
            ['II. COLUMNAS Y VALORES', '', '', ''],
            ['#', 'COLUMNA EN EXCEL', '¿OBLIGATORIO?', 'DETALLES'],
            ['A', 'ID_REGISTRO *', 'SÍ ✅', 'Código único del contratista en el Mandante.'],
            ['B', 'RUT_CONTRATISTA *', 'SÍ ✅', 'RUT sin puntos y con guion. Ej: 76123456-K'],
            ['C', 'MANDANTE *', 'SÍ ✅', 'Razón social exacta del Mandante.'],
            ['D', 'RUT_TRABAJADOR *', 'SÍ ✅', 'RUT sin puntos y con guion. Ej: 12345678-9'],
            ['E', 'NOMBRES *', 'SÍ ✅', 'Nombres del trabajador.'],
            ['F', 'APELLIDO_PATERNO *', 'SÍ ✅', 'Apellido paterno del trabajador.'],
            ['G', 'APELLIDO_MATERNO', 'NO ⭕', 'Apellido materno del trabajador.'],
            ['H', 'CARGO *', 'SÍ ✅', 'Nombre del cargo en el Mandante. Seleccionable desde el desplegable.'],
            ['I', 'LUGAR *', 'SÍ ✅', 'Lugar de Trabajo. Se incluyen opciones en un desplegable si descargó con Mandante seleccionado.'],
            ['J', 'CONTRATO', 'NO ⭕', 'Número de Contrato (Opcional). Se incluyen opciones en un desplegable.'],
            ['K', 'ESTADO *', 'SÍ ✅', 'Valores: Activo, Nuevo, Finiquitado o Movido.'],
            ['L', 'FECHA_INGRESO', 'NO ⭕', 'Formato: DD-MM-YYYY.'],
            ['M', 'FECHA_FINIQUITO', 'SÍ (si es Finiquitado)', 'Obligatorio si estado es Finiquitado. Formato: DD-MM-YYYY.'],
            ['N', 'PERIODO *', 'SÍ ✅', 'Periodo al que corresponde la carga. Formato: YYYY-MM (ej: 2024-11).'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setSize(14)->setBold(true)->setColor(new Color('FF1D4ED8'));
        
        $sectionStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1D4ED8']],
        ];
        foreach ([3, 6] as $row) {
            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($sectionStyle);
        }

        $sheet->getStyle('A7:D7')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A8A']],
        ]);

        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(50);
        $sheet->getStyle('A1:D25')->getAlignment()->setWrapText(true);

        return [];
    }

    public function registerEvents(): array
    {
        return [ AfterSheet::class => function(AfterSheet $event) { $event->sheet->getDelegate()->freezePane('A3'); } ];
    }
}
