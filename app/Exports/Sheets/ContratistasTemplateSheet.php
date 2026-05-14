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
use Maatwebsite\Excel\Concerns\FromArray;
use App\Models\Contratista;

class ContratistasTemplateSheet implements WithHeadings, WithStyles, WithEvents, WithColumnFormatting, WithTitle, FromArray
{
    protected $requiredColumns = [
        'Razón Social*', 'RUT Empresa*', 'Rep. Legal Nombres*',
        'Rep. Legal Primer Apellido*', 'Rep. Legal RUT*',
        'Rep. Legal Email*', 'Admin. Nombre Completo*', 'Admin. Email*', 'Mandante a Vincular (Razón Social)*'
    ];

    public function __construct(private ?int $mandanteId = null, private ?int $contratistaId = null) {}

    public function headings(): array
    {
        return [
            'Razón Social*', 'Nombre Comercial', 'RUT Empresa*', 'Email Empresa', 'Teléfono Empresa',
            'Dirección Calle', 'Dirección Número', 'Region', 'Comuna', 'Tipo Empresa Legal',
            'Actividad Económica (Rubro)', 'Rango Empleados', 'ARL (Mutualidad)', 'Rep. Legal Nombres*',
            'Rep. Legal Primer Apellido*', 'Rep. Legal Segundo Apellido', 'Rep. Legal RUT*', 'Rep. Legal Teléfono',
            'Rep. Legal Email*', 'Admin. Nombre Completo*', 'Admin. RUT', 'Admin. Email*', 'Mandante a Vincular (Razón Social)*',
             'Nombre U.O. Inicial', 'Nombre Lugar de Trabajo Inicial', 'ID Registro (Opcional)', 'SAP (Opcional)', 'Número de Contrato Inicial',
             'Nivel Jerarquía', 'RUT Contratista Padre'
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_TEXT, // RUT Empresa
            'E' => NumberFormat::FORMAT_TEXT, // Teléfono Empresa
            'G' => NumberFormat::FORMAT_TEXT, // Dirección Número
            'Q' => NumberFormat::FORMAT_TEXT, // Rep. Legal RUT
            'R' => NumberFormat::FORMAT_TEXT, // Rep. Legal Teléfono
            'U' => NumberFormat::FORMAT_TEXT, // Admin. RUT
            'AC' => NumberFormat::FORMAT_TEXT, // RUT Padre
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo base para toda la cabecera (Azul/Indigo)
        $sheet->getStyle('A1:AD1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4338CA'], // Indigo-700
            ],
        ]);

        // Aplicar estilo rojo a las columnas obligatorias
        $headings = $this->headings();
        foreach ($headings as $index => $heading) {
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
        return 'Contratistas';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = 1001;

                // Autoajustar el tamaño de las columnas para mejor legibilidad
                $columns = array_merge(range('A', 'Z'), ['AA', 'AB', 'AC', 'AD']);
                foreach ($columns as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }

                // Aplicar validaciones de datos referenciando a la hoja oculta 'Listados'
                $this->applyDataValidationFromSheet($sheet, 'H', $lastRow, 'Listados!$B$2:$B$1000'); // Regiones
                $this->applyDataValidationFromSheet($sheet, 'I', $lastRow, 'Listados!$C$2:$C$1000'); // Comunas
                $this->applyDataValidationFromSheet($sheet, 'J', $lastRow, 'Listados!$D$2:$D$1000'); // Tipos Empresa
                $this->applyDataValidationFromSheet($sheet, 'K', $lastRow, 'Listados!$E$2:$E$1000'); // Rubros
                $this->applyDataValidationFromSheet($sheet, 'L', $lastRow, 'Listados!$F$2:$F$1000'); // Rangos
                $this->applyDataValidationFromSheet($sheet, 'M', $lastRow, 'Listados!$G$2:$G$1000'); // Mutualidades
                $this->applyDataValidationFromSheet($sheet, 'W', $lastRow, 'Listados!$A$2:$A$1000'); // Mandantes
                $this->applyDataValidationFromSheet($sheet, 'X', $lastRow, 'Listados!$H$2:$H$10000', false); // UO
                $this->applyDataValidationFromSheet($sheet, 'Y', $lastRow, 'Listados!$I$2:$I$10000', false); // Lugar

                // Ajustamos los comentarios
                $sheet->getComment('I1')->getText()->createTextRun('Seleccione una Comuna de la lista. (Las opciones que aparecen dependen de que estén correctamente mapeadas en su sistema)');
                $sheet->getComment('I1')->setVisible(false);
                $sheet->getComment('X1')->getText()->createTextRun('Seleccione la U.O. de la lista. Las opciones muestran primero el Principal al que pertenecen.');
                $sheet->getComment('Y1')->getText()->createTextRun('Seleccione el Lugar de Trabajo de la lista. Las opciones muestran primero el Principal al que pertenecen.');
                $sheet->getComment('Z1')->getText()->createTextRun('ID de registro para el contratista bajo este mandante. Si se deja en blanco, el sistema generará uno automáticamente (desde 40001).');
                $sheet->getComment('AA1')->getText()->createTextRun('Código SAP único para este contratista que lo identifica en sistemas externos.');
                $sheet->getComment('AB1')->getText()->createTextRun('Nivel jerárquico. Dejar vacío o 0 para contratistas principales. 1 para subcontratistas, 2 para sub-subcontratistas, etc.');
                $sheet->getComment('AC1')->getText()->createTextRun('RUT del contratista padre. Obligatorio si el nivel jerárquico es mayor a 0.');
            },
        ];
    }

    private function applyDataValidationFromSheet(Worksheet $sheet, string $column, int $lastRow, string $formula)
    {
        $validation = $sheet->getCell($column.'2')->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Entrada no válida');
        $validation->setError('El valor no está en la lista desplegable.');
        $validation->setPromptTitle('Seleccione un valor');
        $validation->setPrompt('Por favor, elija un valor de la lista.');
        
        // La fórmula ahora apunta a un rango en otra hoja
        $validation->setFormula1($formula);

        for ($i = 2; $i <= $lastRow; $i++) {
            $sheet->getCell($column.$i)->setDataValidation(clone $validation);
        }
    }

    public function array(): array
    {
        $data = [];
        
        $query = Contratista::with([
            'subContratistasAprobados', 
            'comuna.region', 
            'tipoEmpresaLegal', 
            'rubro', 
            'rangoCantidadTrabajadores', 
            'mutualidad', 
            'adminUser', 
            'vinculaciones.unidadOrganizacional.mandante',
            'unidadesOrganizacionalesMandante'
        ]);

        if ($this->contratistaId) {
            $contratistas = $query->where('id', $this->contratistaId)->get();
        } elseif ($this->mandanteId) {
            // Obtenemos todos los principales para este mandante
            $contratistas = $query->whereHas('solicitudesVinculacion', function ($q) {
                $q->where('mandante_id', $this->mandanteId)
                  ->where('tipo_solicitud', 'CONTRATISTA')
                  ->where('estado', 'APROBADA');
            })->get();
        } else {
            // Obtenemos solo los principales
            $contratistas = $query->whereHas('solicitudesVinculacion', function ($q) {
                $q->where('tipo_solicitud', 'CONTRATISTA')->where('estado', 'APROBADA');
            })->get();
        }

        foreach ($contratistas as $contratista) {
            $data = array_merge($data, $this->flattenJerarquia($contratista, 0, null));
        }

        return $data;
    }

    private function flattenJerarquia(Contratista $contratista, int $nivel = 0, ?string $rutPadre = null): array
    {
        $filas = [];
        $filas[] = $this->mapearFila($contratista, $nivel, $rutPadre);
        
        foreach ($contratista->subContratistasAprobados as $sub) {
            $filas = array_merge($filas, $this->flattenJerarquia($sub, $nivel + 1, $contratista->rut));
        }
        
        return $filas;
    }

    private function mapearFila(Contratista $contratista, int $nivel, ?string $rutPadre): array
    {
        $uo = $contratista->unidadesOrganizacionalesMandante->first();
        $mandanteName = $uo ? ($uo->mandante->razon_social ?? '') : '';
        $uoName = $uo ? ($mandanteName . ' — ' . $uo->nombre_jerarquico) : '';
        
        $dep = $contratista->dependencias->first();
        $lugarName = $dep ? ($mandanteName . ' — ' . $dep->nombre_jerarquico) : '';
        
        return [
            $contratista->razon_social,
            $contratista->nombre_fantasia === 'S/D MIGRADO' ? '' : $contratista->nombre_fantasia,
            $contratista->rut,
            $contratista->email_empresa,
            $contratista->telefono_empresa === 'S/D MIGRADO' ? '' : $contratista->telefono_empresa,
            $contratista->direccion_calle === 'S/D MIGRADO' ? '' : $contratista->direccion_calle,
            $contratista->direccion_numero === 'S/D MIGRADO' ? '' : $contratista->direccion_numero,
            $contratista->comuna ? ($contratista->comuna->region->nombre ?? '') : '',
            $contratista->comuna->nombre ?? '',
            $contratista->tipoEmpresaLegal->nombre ?? '',
            $contratista->rubro->nombre ?? '',
            $contratista->rangoCantidadTrabajadores->nombre ?? '',
            $contratista->mutualidad->nombre ?? '',
            $contratista->rep_legal_nombres,
            $contratista->rep_legal_apellido_paterno,
            $contratista->rep_legal_apellido_materno === 'S/D MIGRADO' ? '' : $contratista->rep_legal_apellido_materno,
            $contratista->rep_legal_rut,
            $contratista->rep_legal_telefono === 'S/D MIGRADO' ? '' : $contratista->rep_legal_telefono,
            $contratista->rep_legal_email,
            $contratista->adminUser ? $contratista->adminUser->name : '',
            $contratista->adminUser ? $contratista->adminUser->rut : '',
            $contratista->adminUser ? $contratista->adminUser->email : '',
            $mandanteName,
            $uoName,
            $lugarName,
            $uo ? $uo->pivot->id_registro : '',
            $uo ? $uo->pivot->sap : '',
            $uo ? $uo->pivot->numero_contrato : '',
            $nivel,
            $rutPadre
        ];
    }
}
