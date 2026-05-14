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

class VerificacionesHistoricasInstruccionesSheet implements WithTitle, FromArray, WithStyles, WithEvents
{
    public function title(): string
    {
        return 'INSTRUCCIONES';
    }

    public function array(): array
    {
        return [
            // Fila 1
            ['GUÍA DE CARGA MASIVA — VERIFICACIONES HISTÓRICAS (DOTACIÓN ANTERIOR)', '', '', ''],
            [''],
            // Sección I
            ['I. OBJETIVO Y CONTEXTO', '', '', ''],
            ['¿Qué es este módulo?', 'Permite cargar los resultados de verificación del ÚLTIMO período antes de la transición al nuevo sistema. Estos datos forman la "Dotación Anterior" que sirve como base para el primer período.', '', ''],
            ['Regla clave', 'Las retenciones son período-específicas. No se arrastran automáticamente. Este archivo registra el HISTORIAL, no genera retenciones automáticas en el nuevo período.', '', ''],
            [''],
            // Sección II
            ['II. PASOS DE CARGA', '', '', ''],
            ['PASO 1', 'Complete la hoja "Plantilla" con los datos. Use exactamente los valores indicados en la columna RESULTADO.', '', ''],
            ['PASO 2', 'Suba el archivo en el módulo "Importar → Verificaciones Históricas".', '', ''],
            ['PASO 3', 'El sistema validará cada fila y mostrará un resumen: creados / actualizados / errores.', '', ''],
            ['PASO 4', 'Al finalizar, el sistema creará automáticamente el SNAPSHOT de la primera carpeta de verificación activa para cada ID_REGISTRO encontrado.', '', ''],
            [''],
            // Sección III — Tabla de campos
            ['III. MAPA DE COLUMNAS (10 COLUMNAS)', '', '', ''],
            ['#', 'COLUMNA EN EXCEL', '¿OBLIGATORIO?', 'DETALLES Y VALORES VÁLIDOS'],
            ['A', 'ID_REGISTRO *', 'SÍ ✅', 'Código único del contratista dentro del Mandante. Es el mismo ID_REGISTRO asignado en la ficha del contrato en el sistema.'],
            ['B', 'MANDANTE *', 'SÍ ✅', 'Razón social exacta del Principal/Mandante. Debe existir en el sistema.'],
            ['C', 'LUGAR *', 'SÍ ✅', 'Nombre del Lugar de Trabajo / Dependencia donde se verificó la dotación.'],
            ['D', 'CONTRATO *', 'SÍ ✅', 'Número de contrato al que pertenece la verificación.'],
            ['E', 'PERIODO_ANIO *', 'SÍ ✅', 'Año del período verificado. Ej: 2024'],
            ['F', 'PERIODO_MES *', 'SÍ ✅', 'Mes del período verificado (número). Valores: 1 al 12.'],
            ['G', 'RESULTADO *', 'SÍ ✅', "Resultado de la verificación. Valores EXACTOS permitidos:\n  • Limpio         → sin obs ni retención\n  • Obs            → con observaciones\n  • Contingencia   → con retención económica\n  • Ambos          → observaciones + retención"],
            ['H', 'MONTO_RETENIBLE', 'NO ⭕', 'Monto en CLP sujeto a retención. Solo para resultados Contingencia o Ambos. Número entero sin puntos ni comas. Ej: 1500000'],
            ['I', 'MONTO_NO_RETENIBLE', 'NO ⭕', 'Monto en CLP liberado de retención. Número entero. Ej: 2000000'],
            ['J', 'RUT_CONTRATISTA *', 'SÍ ✅', 'RUT del Contratista (para trazabilidad). Ej: 76.xxx.xxx-K'],
            [''],
            // Sección IV — Notas
            ['IV. NOTAS IMPORTANTES', '', '', ''],
            ['IDEMPOTENCIA', 'Si carga el mismo archivo dos veces, el sistema actualiza los registros existentes (no duplica). La clave única es ID_REGISTRO + MANDANTE + AÑO + MES.', '', ''],
            ['RESULTADO obligatorio', 'No se aceptan filas sin RESULTADO. Si la dotación fue verificada, debe tener un resultado asignado.', '', ''],
            ['SNAPSHOT AUTOMÁTICO', 'Al terminar la importación, el sistema detecta la primera carpeta de verificación activa para cada ID_REGISTRO y crea los registros de dotación anterior (tipo DOTACION_ANTERIOR). Esto NO genera retenciones.', '', ''],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Título principal
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setSize(15)->setBold(true)->setColor(new Color('FF1D4ED8'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Estilo secciones
        $sectionStyle = [
            'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1D4ED8']],
        ];
        foreach ([3, 7, 13, 27] as $row) {
            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($sectionStyle);
        }

        // Cabecera de tabla de campos
        $sheet->getStyle('A14:D14')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A8A']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Bordes filas de campos
        $sheet->getStyle('A15:D25')->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]],
        ]);

        // Obligatorios → verde claro
        $obligatoriasFilas = [15, 16, 17, 18, 19, 20, 21, 25];
        foreach ($obligatoriasFilas as $f) {
            $sheet->getStyle("A{$f}:D{$f}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDCFCE7']],
            ]);
        }
        // Opcionales → celeste claro
        foreach ([22, 23] as $f) {
            $sheet->getStyle("A{$f}:D{$f}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0F9FF']],
            ]);
        }

        // RESULTADO: fila con color amarillo para destacar
        $sheet->getStyle('A21:D21')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF9C3']],
            'font' => ['bold' => true],
        ]);

        // Notas
        foreach ([28, 29, 30, 31] as $f) {
            $sheet->getStyle("A{$f}")->applyFromArray(['font' => ['bold' => true, 'color' => ['argb' => 'FFB91C1C']]]);
        }

        // Anchos
        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(70);

        $sheet->getStyle('B1:D35')->getAlignment()->setWrapText(true);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->freezePane('A3');
            },
        ];
    }
}
