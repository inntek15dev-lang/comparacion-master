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

class TrabajadoresTemplateSheet implements \Maatwebsite\Excel\Concerns\FromCollection, WithHeadings, WithStyles, WithEvents, WithColumnFormatting, WithTitle
{
    protected $mandante_id;
    protected $contratista_id;

    public function __construct($mandante_id = null, $contratista_id = null)
    {
        $this->mandante_id = $mandante_id;
        $this->contratista_id = $contratista_id;
    }
    protected $requiredColumns = [
        'RUT Contratista*',
        'Razon Social Principal*',
        'RUT Trabajador*',
        'Nombres*',
        'Apellido Paterno*',
        'Fecha de Nacimiento*',
        'Nacionalidad*',
        'Tipo Permanencia*',
        'Nombre UO Inicial*',
        'Nombre Lugar de Trabajo Inicial*',
        'Nombre Cargo Inicial*',
    ];

    /**
     * Pre-rellena los trabajadores existentes de la contratista seleccionada
     * para permitir actualizaciones masivas (upsert) al re-importar.
     */
    public function collection()
    {
        if (!$this->mandante_id) {
            return collect([]);
        }

        $trabQuery = \App\Models\Trabajador::with([
            'contratista',
            'nacionalidad',
            'tipoPermanencia',
            'sexo',
            'estadoCivil',
            'etnia',
            'nivelEducacional',
        ])->whereHas('vinculaciones', fn($q) =>
            $q->where('is_active', true)
              ->whereHas('unidadOrganizacionalMandante', fn($sq) => $sq->where('mandante_id', $this->mandante_id))
        );

        if ($this->contratista_id) {
            $trabQuery->where('contratista_id', $this->contratista_id);
        }

        $rows = collect();

        foreach ($trabQuery->orderBy('apellido_paterno')->get() as $t) {
            // Obtener TODAS las vinculaciones activas de este trabajador para el mandante
            $vinculaciones = \App\Models\TrabajadorVinculacion::with([
                    'unidadOrganizacionalMandante.mandante',
                    'dependencia',
                    'cargoMandante',
                ])
                ->where('trabajador_id', $t->id)
                ->where('is_active', true)
                ->whereHas('unidadOrganizacionalMandante', fn($q) => $q->where('mandante_id', $this->mandante_id))
                ->get();

            if ($vinculaciones->isEmpty()) {
                continue; // Trabajador sin vinculación activa en este mandante → omitir
            }

            // UNA FILA POR VINCULACIÓN (trabajador con 2 contratos = 2 filas en el Excel)
            foreach ($vinculaciones as $vinc) {
                $uo       = $vinc->unidadOrganizacionalMandante;
                $dep      = $vinc->dependencia;
                $cargo    = $vinc->cargoMandante;
                $mandante = $uo?->mandante;

                $mandanteNombre = $mandante?->razon_social ?? '';

                $rows->push([
                    $t->contratista?->rut                                                                              ?? '',  // A - RUT Contratista
                    $mandanteNombre,                                                                                           // B - Principal
                    $t->rut,                                                                                                   // C - RUT Trabajador
                    $t->nombres,                                                                                               // D
                    $t->apellido_paterno,                                                                                      // E
                    $t->apellido_materno                                                                               ?? '',  // F
                    $t->fecha_nacimiento ? \Carbon\Carbon::parse($t->fecha_nacimiento)->format('d-m-Y') : '',                  // G
                    $t->email                                                                                          ?? '',  // H
                    $t->celular                                                                                        ?? '',  // I
                    $t->direccion_calle                                                                                ?? '',  // J
                    $t->nacionalidad?->nombre                                                                          ?? '',  // K
                    $t->tipoPermanencia?->nombre                                                                       ?? '',  // L
                    $t->sexo?->nombre                                                                                  ?? '',  // M
                    $t->estadoCivil?->nombre                                                                           ?? '',  // N
                    $t->etnia?->nombre                                                                                 ?? '',  // O
                    $t->nivelEducacional?->nombre                                                                      ?? '',  // P
                    $uo    ? ($mandanteNombre . ' — ' . $uo->nombre_jerarquico)   : '',                                        // Q - UO
                    $dep   ? ($mandanteNombre . ' — ' . $dep->nombre_jerarquico)  : '',                                        // R - Lugar
                    $cargo ? ($mandanteNombre . ' — ' . $cargo->nombre_cargo)     : '',                                        // S - Cargo
                    $vinc->numero_contrato                                                                             ?? '',  // T - N° Contrato (identifica la vinculación en upsert)
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'RUT Contratista*',           // A
            'Razon Social Principal*',     // B  ← sin tilde para key limpio
            'RUT Trabajador*',             // C
            'Nombres*',                    // D
            'Apellido Paterno*',           // E
            'Apellido Materno',            // F
            'Fecha de Nacimiento*',        // G
            'Email',                       // H
            'Telefono',                    // I
            'Direccion Calle y Numero',    // J
            'Nacionalidad*',               // K
            'Tipo Permanencia*',           // L
            'Sexo',                        // M
            'Estado Civil',                // N
            'Etnia',                       // O
            'Nivel Educacional',           // P
            'Nombre UO Inicial*',          // Q
            'Nombre Lugar de Trabajo Inicial*', // R
            'Nombre Cargo Inicial*',       // S
            'Numero Contrato',             // T  ← clave limpia → numero_contrato
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT, // RUT Contratista
            'C' => NumberFormat::FORMAT_TEXT, // RUT Trabajador
            'G' => NumberFormat::FORMAT_TEXT, // Fecha Nacimiento
            'I' => NumberFormat::FORMAT_TEXT, // Teléfono
            'T' => NumberFormat::FORMAT_TEXT, // N° Contrato
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
                    'startColor' => ['argb' => $isRequired ? 'FFDC2626' : 'FF1D4ED8'], // Rojo / Azul
                ],
            ]);
        }

        return [];
    }

    public function title(): string
    {
        return 'Trabajadores';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = 1001;

                foreach (range('A', 'T') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }

                $sheet->getComment('G1')->getText()->createTextRun('Por favor, ingrese la fecha en formato DD-MM-AAAA. Ejemplo: 14-05-2000');
                $sheet->getComment('L1')->getText()->createTextRun('Seleccione el Tipo de Permanencia. Ej: CHILENO, TEMPORAL, DEFINITIVA, ESTUDIANTE, SUJETA A CONTRATO.');
                $sheet->getComment('T1')->getText()->createTextRun('Ingrese el N° de contrato si corresponde. Dejar vacío para trabajadores sin contrato.');

                // Actualizamos los comentarios para que el usuario entienda el formato compuesto
                $sheet->getComment('Q1')->getText()->createTextRun('Seleccione la U.O. de la lista. Las opciones muestran primero el Principal al que pertenecen.');
                $sheet->getComment('R1')->getText()->createTextRun('Seleccione el Lugar de Trabajo de la lista. Las opciones muestran primero el Principal al que pertenecen.');
                $sheet->getComment('S1')->getText()->createTextRun('Seleccione el Cargo de la lista. Las opciones muestran primero el Principal al que pertenecen.');

                // Aplicar validaciones de datos referenciando a la hoja oculta 'Listados'
                // Columnas en Listados: A=Mandantes, B=Nacionalidades, C=TiposPermanencia, D=Sexos, E=EstadosCiviles, F=Etnias, G=NivelesEd, H=UOs, I=Lugares, J=Cargos
                $this->applyDataValidationFromSheet($sheet, 'B', $lastRow, 'Listados!$A$2:$A$1000', false); // Mandantes
                $this->applyDataValidationFromSheet($sheet, 'K', $lastRow, 'Listados!$B$2:$B$1000', false); // Nacionalidades
                $this->applyDataValidationFromSheet($sheet, 'L', $lastRow, 'Listados!$C$2:$C$1000', false); // Tipos Permanencia
                $this->applyDataValidationFromSheet($sheet, 'M', $lastRow, 'Listados!$D$2:$D$1000');        // Sexos
                $this->applyDataValidationFromSheet($sheet, 'N', $lastRow, 'Listados!$E$2:$E$1000');        // Estados Civiles
                $this->applyDataValidationFromSheet($sheet, 'O', $lastRow, 'Listados!$F$2:$F$1000');        // Etnias
                $this->applyDataValidationFromSheet($sheet, 'P', $lastRow, 'Listados!$G$2:$G$1000');        // Niveles Educacionales

                // Estos tres pueden ser muchos más, abrimos el rango hasta 10000
                $this->applyDataValidationFromSheet($sheet, 'Q', $lastRow, 'Listados!$H$2:$H$10000', false); // U.Os
                $this->applyDataValidationFromSheet($sheet, 'R', $lastRow, 'Listados!$I$2:$I$10000', false); // Lugares
                $this->applyDataValidationFromSheet($sheet, 'S', $lastRow, 'Listados!$J$2:$J$10000', false); // Cargos
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
