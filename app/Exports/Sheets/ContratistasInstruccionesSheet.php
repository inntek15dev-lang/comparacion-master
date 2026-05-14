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

class ContratistasInstruccionesSheet implements WithTitle, FromArray, WithStyles, WithEvents
{
    public function title(): string
    {
        return 'INSTRUCCIONES';
    }

    public function array(): array
    {
        return [
            ['GUÍA MAESTRA DE MIGRACIÓN Y CARGA MASIVA DE CONTRATISTAS', '', '', ''],
            [''],
            ['I. EL PROCESO DE CARGA MASIVA (PASO A PASO)', '', '', ''],
            ['PASO 1: PREPARACIÓN DE DATOS', 'Complete la hoja "Contratistas" validando la información con la pestaña "Listados".', '', ''],
            ['', 'Todas las columnas en rojo con (*) son obligatorias.', '', ''],
            ['PASO 2: LISTAS DESPLEGABLES', 'Es imperativo seleccionar las opciones predefinidas de las listas desplegables. El sistema rebotará la carga si ingresa texto libre en esos campos.', '', ''],
            ['PASO 3: CARGA DEL EXCEL', 'Suba este archivo Excel al módulo de Importación en la plataforma y haga clic para iniciar el procesamiento.', '', ''],
            ['PASO 4: RESULTADOS', 'El sistema le indicará inmediatamente cuántas empresas se crearon, vinculaciones, e informará aquellas filas que fallaron con su respectivo motivo.', '', ''],
            [''],
            ['II. MAPA COMPLETO DE CAMPOS (30 COLUMNAS)', '', '', ''],
            ['#', 'COLUMNA EN EXCEL', '¿OBLIGATORIO?', 'DETALLES ADICIONALES'],
            
            // Fila 12 a 37 - campos
            ['A', 'Razón Social*', 'SI ✅', 'Nombre completo legal de la empresa o persona natural.'],
            ['B', 'Nombre Comercial', 'NO ⭕', 'Nombre de fantasía con el que operan.'],
            ['C', 'RUT Empresa*', 'SI ✅', 'Formato chileno: Ej. 76.xxx.xxx-K.'],
            ['D', 'Email Empresa', 'NO ⭕', 'Debe ser único en el sistema.'],
            ['E', 'Teléfono Empresa', 'NO ⭕', 'Número de contacto general.'],
            ['F', 'Dirección Calle', 'NO ⭕', 'Dirección física principal.'],
            ['G', 'Dirección Número', 'NO ⭕', 'Numeración de la dirección.'],
            ['H', 'Región', 'NO ⭕', 'Elegir de la Lista Desplegable obligatoriamente.'],
            ['I', 'Comuna', 'NO ⭕', 'Elegir de la Lista Desplegable obligatoriamente.'],
            ['J', 'Tipo Empresa Legal', 'NO ⭕', 'Elegir de la Lista Desplegable obligatoriamente.'],
            ['K', 'Actividad Económica (Rubro)', 'NO ⭕', 'Elegir de la Lista Desplegable obligatoriamente.'],
            ['L', 'Rango Empleados', 'NO ⭕', 'Elegir de la Lista Desplegable obligatoriamente.'],
            ['M', 'ARL (Mutualidad)', 'NO ⭕', 'Elegir de la Lista Desplegable obligatoriamente.'],
            ['N', 'Rep. Legal Nombres*', 'SI ✅', 'Nombres del R.L.'],
            ['O', 'Rep. Legal Primer Apellido*', 'SI ✅', 'Primer Apellido del R.L.'],
            ['P', 'Rep. Legal Segundo Apellido', 'NO ⭕', 'Segundo Apellido del R.L.'],
            ['Q', 'Rep. Legal RUT*', 'SI ✅', 'RUT Chileno del R.L.'],
            ['R', 'Rep. Legal Teléfono', 'NO ⭕', 'Contacto del R.L.'],
            ['S', 'Rep. Legal Email*', 'SI ✅', 'Correo corporativo del R.L.'],
            ['T', 'Admin. Nombre Completo*', 'SI ✅', 'Nombre del administrador inicial de la plataforma del contratista.'],
            ['U', 'Admin. RUT', 'NO ⭕', 'RUT del administrador inicial.'],
            ['V', 'Admin. Email*', 'SI ✅', 'Correo de acceso para el administrador. Se generará contraseña.'],
            ['W', 'Mandante a Vincular (Razón Social)*', 'SI ✅', 'Elegir de la Lista Desplegable. Define para quién prestará servicio.'],
            ['X', 'Nombre U.O. Inicial', 'NO ⭕', 'Elegir de la Lista Desplegable dependiente del Mandante.'],
            ['Y', 'Nombre Lugar de Trabajo Inicial', 'NO ⭕', 'Elegir de la Lista Desplegable dependiente del Mandante.'],
            ['Z', 'ID Registro (Opcional)', 'NO ⭕', 'ID numérico. Si va vacío, el sistema lo genera secuencialmente (desde 40001).'],
            ['AA','SAP (Opcional)', 'NO ⭕', 'Código SAP asociado a la vinculación operativa del contratista.'],
            ['AB','Número de Contrato Inicial', 'NO ⭕', 'Número del contrato principal para esta vinculación.'],
            ['AC','Nivel Jerarquía', 'NO ⭕', '0 = Principal, 1 = Subcontratista, 2 = Sub-Subcontratista, etc. (vacío asume 0).'],
            ['AD','RUT Contratista Padre', 'CONDICIONAL', 'Requerido solo si Nivel Jerarquía > 0. El RUT del padre directo en la jerarquía.'],
            
            [''],
            ['III. RESUMEN Y NOTAS TÉCNICAS', '', '', ''],
            ['TOTAL OBLIGATORIOS:', '9 campos (marcados con * en ROJO en la cabecera)', '', ''],
            ['TOTAL OPCIONALES:', '21 campos (incluye opcionales y condicionales)', '', ''],
            ['', '', '', ''],
            ['NOTA 1:', 'Si una columna NO obligatoria se deja vacía pero el sistema requiere un dato por defecto, se guardará como "S/D MIGRADO".', '', ''],
            ['NOTA 2:', 'Las plantillas descargadas ya cuentan con reglas de Listas Desplegables. No pegue texto directamente si hay una lista disponible.', '', ''],
            ['NOTA 3:', 'ACTUALIZACIÓN (UPSERT): Si carga un archivo con un "RUT Empresa" que ya existe en el sistema, los datos se actualizarán automáticamente en lugar de duplicarse.', '', ''],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true)->setColor(new Color('FF4338CA'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sectionStyle = [
            'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4338CA'],
            ],
        ];

        foreach ([3, 10, 41] as $sectionRow) {
            $sheet->mergeCells("A{$sectionRow}:D{$sectionRow}");
            $sheet->getStyle("A{$sectionRow}:D{$sectionRow}")->applyFromArray($sectionStyle);
        }

        $tableHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E3A8A'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];
        $sheet->getStyle('A11:D11')->applyFromArray($tableHeaderStyle);

        $tableBorderStyle = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']],
            ],
        ];
        $sheet->getStyle('A12:D41')->applyFromArray($tableBorderStyle);

        $obligatorioStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFDCFCE7'],
            ],
        ];
        $opcionalStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF9FAFB'],
            ],
        ];

        // Obligatorios: A(12), C(14), N(25), O(26), Q(28), S(30), T(31), V(33), W(34)
        $filasObligatorias = [12, 14, 25, 26, 28, 30, 31, 33, 34];
        $filasOpcionales = [13, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 27, 29, 32, 35, 36, 37, 38, 39, 40, 41];

        foreach ($filasObligatorias as $fila) {
            $sheet->getStyle("A{$fila}:D{$fila}")->applyFromArray($obligatorioStyle);
        }
        foreach ($filasOpcionales as $fila) {
            $sheet->getStyle("A{$fila}:D{$fila}")->applyFromArray($opcionalStyle);
        }

        $stepStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF0F9FF'],
            ],
        ];
        foreach ([4, 6, 7, 8] as $stepRow) {
            $sheet->getStyle("A{$stepRow}")->applyFromArray($stepStyle);
        }

        $notaStyle = ['font' => ['bold' => true, 'color' => ['argb' => 'FFB91C1C']]];
        foreach ([42, 43, 45, 46, 47] as $notaRow) {
            $sheet->getStyle("A{$notaRow}")->applyFromArray($notaStyle);
        }

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(55);

        $sheet->getStyle('B1:D45')->getAlignment()->setWrapText(true);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getDelegate()->freezePane('A3');
            },
        ];
    }
}
