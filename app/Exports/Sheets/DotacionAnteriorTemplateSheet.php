<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class DotacionAnteriorTemplateSheet implements WithTitle, FromArray, WithStyles, WithEvents
{
    public $mandanteId;
    public $contratistaId;
    public $periodo;

    public function __construct($mandanteId, $contratistaId = null, $periodo = null)
    {
        $this->mandanteId    = $mandanteId;
        $this->contratistaId = $contratistaId;
        $this->periodo       = $periodo;
    }

    public function title(): string
    {
        return 'Plantilla';
    }

    public function array(): array
    {
        $headers = [
            'ID_REGISTRO *', 'RUT_CONTRATISTA *', 'MANDANTE *', 'RUT_TRABAJADOR *',
            'NOMBRES *', 'APELLIDO_PATERNO *', 'APELLIDO_MATERNO', 'CARGO *',
            'LUGAR *', 'CONTRATO', 'ESTADO *', 'FECHA_INGRESO', 'FECHA_CONTRATO', 'FECHA_FINIQUITO', 'PERIODO *'
        ];

        $data = [$headers];

        if ($this->periodo) {
            $partes = explode('-', $this->periodo);
            if (count($partes) === 2) {
                $anio = (int) $partes[0];
                $mes = (int) $partes[1];

                $registros = \App\Models\CarpetaVerificacionTrabajador::with([
                    'vinculacion.trabajador.contratista', 
                    'carpeta.vinculacion.unidadOrganizacionalMandante.mandante',
                    'carpeta.vinculacion.dependencia',
                    'vinculacion.cargoMandante'
                ])
                ->whereHas('carpeta', function($q) use ($anio, $mes) {
                    $q->where('anio', $anio)->where('mes', $mes)
                      ->whereHas('vinculacion', function($q2) {
                          $q2->whereHas('unidadOrganizacionalMandante', fn($q3) => $q3->where('mandante_id', $this->mandanteId));
                          if ($this->contratistaId) {
                              $q2->where('contratista_id', $this->contratistaId);
                          }
                      });
                })->get();

                foreach ($registros as $registro) {
                    $vinculacion = $registro->vinculacion;
                    $trabajador = $vinculacion->trabajador;
                    $cuo = $registro->carpeta->vinculacion;
                    
                    $data[] = [
                        $cuo->id_registro ?? '',
                        $trabajador->contratista->rut ?? '',
                        $cuo->unidadOrganizacionalMandante->mandante->razon_social ?? '',
                        $trabajador->rut ?? '',
                        $trabajador->nombres ?? '',
                        $trabajador->apellido_paterno ?? '',
                        $trabajador->apellido_materno ?? '',
                        $vinculacion->cargoMandante->nombre_cargo ?? '',
                        $cuo->dependencia->nombre ?? '',
                        $cuo->numero_contrato ?? '',
                        'Activo', // Por defecto "Activo" para que los arrastren al nuevo periodo, el usuario modifica a Finiquitado si corresponde
                        $vinculacion->fecha_ingreso_vinculacion ? \Carbon\Carbon::parse($vinculacion->fecha_ingreso_vinculacion)->format('d-m-Y') : '',
                        $vinculacion->fecha_contrato ? \Carbon\Carbon::parse($vinculacion->fecha_contrato)->format('d-m-Y') : '',
                        '',
                        $this->periodo
                    ];
                }
            }
        }

        // Si no se proveyó periodo, o si la consulta no arrojó resultados, usar Dummies
        if (count($data) === 1) {
            $data[] = ['REG-001', '76000000-K', 'MANDANTE SA', '11111111-1', 'JUAN', 'PEREZ', 'GOMEZ', 'OPERADOR', 'MINA NORTE', 'CONT-123', 'Activo', '01-01-2023', '01-01-2023', '', '2025-10'];
            $data[] = ['REG-001', '76000000-K', 'MANDANTE SA', '22222222-2', 'PEDRO', 'DIAZ', '', 'CHOFER', 'MINA NORTE', 'CONT-123', 'Finiquitado', '15-06-2024', '15-06-2024', '10-10-2024', '2025-10'];
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $redStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFB91C1C']],
        ];
        $blueStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1D4ED8']],
        ];

        $sheet->getStyle('A1')->applyFromArray($redStyle);
        $sheet->getStyle('B1')->applyFromArray($redStyle);
        $sheet->getStyle('C1')->applyFromArray($redStyle);
        $sheet->getStyle('D1')->applyFromArray($redStyle);
        $sheet->getStyle('E1')->applyFromArray($redStyle);
        $sheet->getStyle('F1')->applyFromArray($redStyle);
        $sheet->getStyle('G1')->applyFromArray($blueStyle);
        $sheet->getStyle('H1')->applyFromArray($redStyle);
        $sheet->getStyle('I1')->applyFromArray($redStyle);
        $sheet->getStyle('J1')->applyFromArray($redStyle);
        $sheet->getStyle('K1')->applyFromArray($redStyle);
        $sheet->getStyle('L1')->applyFromArray($blueStyle);
        $sheet->getStyle('M1')->applyFromArray($blueStyle);
        $sheet->getStyle('N1')->applyFromArray($blueStyle); // Obligatorio solo si es finiquitado
        $sheet->getStyle('O1')->applyFromArray($redStyle); // Periodo es obligatorio

        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $ws = $event->sheet->getDelegate();
                $ws->freezePane('A2');

                $validationEstado = $ws->getCell('K2')->getDataValidation();
                $validationEstado->setType(DataValidation::TYPE_LIST);
                $validationEstado->setFormula1('"Activo,Nuevo,Finiquitado,Movido"');
                $validationEstado->setShowDropDown(true);
                $validationEstado->setShowErrorMessage(false); // Como dijo "no obligatorio", no forzamos error estricto si escribe algo que falta

                // Validación para Cargo (H)
                $validationCargo = $ws->getCell('H2')->getDataValidation();
                $validationCargo->setType(DataValidation::TYPE_LIST);
                $validationCargo->setFormula1('=ListCargos');
                $validationCargo->setShowDropDown(true);
                $validationCargo->setShowErrorMessage(false); // No estricto

                // Validación para Lugar (I)
                $validationLugar = $ws->getCell('I2')->getDataValidation();
                $validationLugar->setType(DataValidation::TYPE_LIST);
                $validationLugar->setFormula1('=ListLugares');
                $validationLugar->setShowDropDown(true);
                $validationLugar->setShowErrorMessage(false); // No estricto

                // Validación para Contrato (J)
                $validationContrato = $ws->getCell('J2')->getDataValidation();
                $validationContrato->setType(DataValidation::TYPE_LIST);
                $validationContrato->setFormula1('=ListContratos');
                $validationContrato->setShowDropDown(true);
                $validationContrato->setShowErrorMessage(false); // No estricto

                for ($r = 3; $r <= 500; $r++) {
                    $ws->getCell("H{$r}")->setDataValidation(clone $validationCargo);
                    $ws->getCell("I{$r}")->setDataValidation(clone $validationLugar);
                    $ws->getCell("J{$r}")->setDataValidation(clone $validationContrato);
                    $ws->getCell("K{$r}")->setDataValidation(clone $validationEstado);
                }
            }
        ];
    }
}
