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

class TrabajadoresInstruccionesSheet implements WithTitle, FromArray, WithStyles, WithEvents
{
    public function title(): string
    {
        return 'INSTRUCCIONES';
    }

    public function array(): array
    {
        return [
            // Fila 1: Título principal
            ['GUÍA MAESTRA DE MIGRACIÓN Y CARGA MASIVA DE TRABAJADORES', '', '', ''],
            // Fila 2: espacio
            [''],
            // Fila 3: Sección I
            ['I. EL PROCESO DE CARGA MASIVA (PASO A PASO)', '', '', ''],
            // Filas 4–8: Pasos
            ['PASO 1: PREPARACIÓN DE DATOS',  'Complete la hoja "Trabajadores" con los datos de los colaboradores. Valide los catálogos en la pestaña "Listados".', '', ''],
            ['',                               'Las columnas en ROJO con (*) son obligatorias. Las columnas en AZUL son opcionales.', '', ''],
            ['PASO 2: LISTAS DESPLEGABLES',   'Seleccione U.O., Lugar de Trabajo, Cargo, Mandante, Nacionalidad, Sexo, etc. SÓLO desde las listas desplegables. No escriba texto libre en esos campos.', '', ''],
            ['PASO 3: N° CONTRATO',           'Si el trabajador opera bajo un contrato específico, ingréselo en la columna S. Si no tiene contrato, déjela VACÍA: el trabajador quedará sin contrato y podrá asignárselo después.', '', ''],
            ['PASO 4: CARGA DEL EXCEL',       'Suba este archivo Excel al módulo "Importación Masiva → Trabajadores" y haga clic en Importar.', '', ''],
            ['PASO 5: RESULTADOS',            'El sistema informará cuántos trabajadores se crearon, cuántos van a RESERVA por cuota excedida, y cuáles filas fallaron con su respectivo motivo.', '', ''],
            // Fila 10: espacio
            [''],
            // Fila 11: Sección II
            ['II. MAPA COMPLETO DE CAMPOS (19 COLUMNAS)', '', '', ''],
            // Fila 12: cabecera de tabla
            ['#', 'COLUMNA EN EXCEL', '¿OBLIGATORIO?', 'DETALLES ADICIONALES'],
            // Filas 13–31: columnas
            ['A', 'RUT Contratista*',                       'SI ✅', 'RUT del contratista dueño del trabajador. Formato chileno. Ej: 76.xxx.xxx-K.'],
            ['B', 'Razón Social Principal*',                 'SI ✅', 'Nombre exacto del Mandante (empresa principal). Debe coincidir con la lista desplegable.'],
            ['C', 'RUT Trabajador*',                         'SI ✅', 'RUT/NUIP/DNI del trabajador. Debe ser único por contratista.'],
            ['D', 'Nombres*',                                'SI ✅', 'Nombres del trabajador.'],
            ['E', 'Apellido Paterno*',                       'SI ✅', 'Primer apellido.'],
            ['F', 'Apellido Materno',                        'NO ⭕', 'Segundo apellido (puede quedar vacío).'],
            ['G', 'Fecha de Nacimiento*',                    'SI ✅', 'Formato DD-MM-AAAA. Ej: 14-05-1990. La columna está configurada como Texto para evitar conversiones.'],
            ['H', 'Email',                                   'NO ⭕', 'Correo electrónico del trabajador.'],
            ['I', 'Teléfono',                                'NO ⭕', 'Número de contacto. La columna está en Texto para preservar el formato.'],
            ['J', 'Dirección (Calle y Número)',               'NO ⭕', 'Dirección domiciliaria del trabajador.'],
            ['K', 'Nacionalidad*',                           'SI ✅', 'Seleccionar de la Lista Desplegable. Debe existir en el catálogo del sistema.'],
            ['L', 'Sexo',                                    'NO ⭕', 'Seleccionar de la Lista Desplegable.'],
            ['M', 'Estado Civil',                            'NO ⭕', 'Seleccionar de la Lista Desplegable.'],
            ['N', 'Etnia',                                   'NO ⭕', 'Seleccionar de la Lista Desplegable.'],
            ['O', 'Nivel Educacional',                       'NO ⭕', 'Seleccionar de la Lista Desplegable.'],
            ['P', 'Nombre U.O. Inicial*',                    'SI ✅', 'Unidad Organizacional del Mandante. Seleccionar de la Lista Desplegable.'],
            ['Q', 'Nombre Lugar de Trabajo Inicial*',         'SI ✅', 'Lugar/Departamento donde se asigna. Seleccionar de la Lista Desplegable.'],
            ['R', 'Nombre Cargo Inicial*',                   'SI ✅', 'Cargo del Mandante al que pertenece. Seleccionar de la Lista Desplegable.'],
            ['S', 'N° Contrato (Opcional)',                  'NO ⭕', 'Número de contrato. Si se deja vacío, el trabajador queda "Sin Contrato" y puede asignarse después desde la plataforma.'],
            // Fila 32: espacio
            [''],
            // Fila 33: Sección III
            ['III. COMPORTAMIENTO DEL SISTEMA (CUOTAS)', '', '', ''],
            // Filas 34–39: notas
            ['TOTAL OBLIGATORIOS:', '10 campos (marcados con * en ROJO en la cabecera de la hoja Trabajadores)', '', ''],
            ['TOTAL OPCIONALES:',   '9 campos (marcados en AZUL en la cabecera)', '', ''],
            ['', '', '', ''],
            ['NOTA 1 — CUOTA EXCEDIDA:',  'Si la combinación Lugar+U.O. tiene una cuota máxima de trabajadores y ya está llena, el trabajador IGUAL se crea pero queda en estado RESERVA (sin U.O., sin Lugar, sin Cargo). Aparecerá en el filtro "VER TRABAJADORES EN RESERVA".', '', ''],
            ['NOTA 2 — SIN CUOTA:',       'Si la combinación Lugar+U.O. no tiene cuota definida, se asignan todos los trabajadores sin límite.', '', ''],
            ['NOTA 3 — LISTAS:',          'No modifique la hoja "Listados". Es generada automáticamente y alimenta las listas desplegables.', '', ''],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Fila 1: Título
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true)->setColor(new Color('FF1D4ED8'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Estilo secciones (I, II, III)
        $sectionStyle = [
            'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1D4ED8'], // Azul
            ],
        ];
        foreach ([3, 11, 33] as $sectionRow) {
            $sheet->mergeCells("A{$sectionRow}:D{$sectionRow}");
            $sheet->getStyle("A{$sectionRow}:D{$sectionRow}")->applyFromArray($sectionStyle);
        }

        // Cabecera de la tabla de campos
        $tableHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E3A8A'], // Azul oscuro
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];
        $sheet->getStyle('A12:D12')->applyFromArray($tableHeaderStyle);

        // Bordes tabla de campos (filas 13–31)
        $tableBorderStyle = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']],
            ],
        ];
        $sheet->getStyle('A13:D31')->applyFromArray($tableBorderStyle);

        // Filas obligatorias → verde claro
        $obligatorioStyle = [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDCFCE7']],
        ];
        // Filas opcionales → gris muy claro
        $opcionalStyle = [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0F9FF']],
        ];

        // A=13,B=14,C=15,D=16,E=17,G=19,K=23,P=28,Q=29,R=30 (10 obligatorias)
        $filasObligatorias = [13, 14, 15, 16, 17, 19, 23, 28, 29, 30];
        // F=18, H=20, I=21, J=22, L=24, M=25, N=26, O=27, S=31 (9 opcionales)
        $filasOpcionales   = [18, 20, 21, 22, 24, 25, 26, 27, 31];

        foreach ($filasObligatorias as $fila) {
            $sheet->getStyle("A{$fila}:D{$fila}")->applyFromArray($obligatorioStyle);
        }
        foreach ($filasOpcionales as $fila) {
            $sheet->getStyle("A{$fila}:D{$fila}")->applyFromArray($opcionalStyle);
        }

        // Estilo pasos (PASO 1…5)
        $stepStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0F9FF']],
        ];
        foreach ([4, 6, 7, 8, 9] as $stepRow) {
            $sheet->getStyle("A{$stepRow}")->applyFromArray($stepStyle);
        }

        // Notas en rojo
        $notaStyle = ['font' => ['bold' => true, 'color' => ['argb' => 'FFB91C1C']]];
        foreach ([34, 35, 37, 38, 39] as $notaRow) {
            $sheet->getStyle("A{$notaRow}")->applyFromArray($notaStyle);
        }

        // Anchos de columna
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(65);

        // Wrap text
        $sheet->getStyle('B1:D45')->getAlignment()->setWrapText(true);

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
