<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SupervisionContratistasExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $data;
    protected $incluirMandante;
    // ================== INICIO DE LA MODIFICACIÓN ==================
    protected $totales;

    public function __construct($data, $incluirMandante = false, $totales = [])
    {
        $this->data = $data;
        $this->incluirMandante = $incluirMandante;
        $this->totales = $totales;
    }

    public function collection()
    {
        $contador = 1;
        $collection = $this->data->map(function ($item) use (&$contador) {
            $fila = [];
            
            $fila['#'] = $contador++;

            if ($this->incluirMandante) {
                $fila['Mandante'] = $item['mandante_nombre'];
            }

            $fila['Contratista'] = $item['razon_social'];
            $fila['RUT'] = $item['rut'];
            $fila['Lugar de Trabajo'] = $item['lugar_trabajo_nombre_jerarquico'];
            $fila['U.O.'] = $item['uo_nombre_jerarquico'];
            
            $fila['Empresa (%)'] = isset($item['cumplimiento_empresa']) ? $item['cumplimiento_empresa'] . '%' : 'N/A';
            
            $fila['Trabajadores (% / Total)'] = (isset($item['promedio_trabajadores']) && $item['promedio_trabajadores']['total'] > 0)
                ? "{$item['promedio_trabajadores']['promedio']}% ({$item['promedio_trabajadores']['total']})"
                : 'N/A';

            $fila['Vehículos (% / Total)'] = (isset($item['promedio_vehiculos']) && $item['promedio_vehiculos']['total'] > 0)
                ? "{$item['promedio_vehiculos']['promedio']}% ({$item['promedio_vehiculos']['total']})"
                : 'N/A';

            $fila['Maquinaria (% / Total)'] = (isset($item['promedio_maquinarias']) && $item['promedio_maquinarias']['total'] > 0)
                ? "{$item['promedio_maquinarias']['promedio']}% ({$item['promedio_maquinarias']['total']})"
                : 'N/A';

            $fila['Embarcaciones (% / Total)'] = (isset($item['promedio_embarcaciones']) && $item['promedio_embarcaciones']['total'] > 0)
                ? "{$item['promedio_embarcaciones']['promedio']}% ({$item['promedio_embarcaciones']['total']})"
                : 'N/A';
            
            return $fila;
        });

        if (!empty($this->totales)) {
            $filaTotales = [
                '#',
                'Mandante' => $this->incluirMandante ? 'TOTALES RECURSOS ÚNICOS:' : 'TOTALES RECURSOS ÚNICOS:',
                'Contratista' => '',
                'RUT' => '',
                'Lugar de Trabajo' => '',
                'U.O.' => '',
                'Empresa (%)' => $this->totales['contratistas'] . ' Contratistas',
                'Trabajadores (% / Total)' => $this->totales['trabajadores'] . ' Trabajadores',
                'Vehículos (% / Total)' => $this->totales['vehiculos'] . ' Vehículos',
                'Maquinaria (% / Total)' => $this->totales['maquinarias'] . ' Maquinarias',
                'Embarcaciones (% / Total)' => $this->totales['embarcaciones'] . ' Embarcaciones',
            ];

            if (!$this->incluirMandante) {
                unset($filaTotales['Mandante']);
                $filaTotales['#'] = 'TOTALES RECURSOS ÚNICOS:';
            }

            $collection->push([]); // Fila en blanco para separar
            $collection->push($filaTotales);
        }

        return $collection;
    }
    // ================== FIN DE LA MODIFICACIÓN ====================

    public function headings(): array
    {
        $cabeceras = ['#'];

        if ($this->incluirMandante) {
            $cabeceras[] = 'Mandante';
        }

        $cabeceras = array_merge($cabeceras, [
            'Contratista',
            'RUT',
            'Lugar de Trabajo',
            'U.O.',
            'Empresa (%)',
            'Trabajadores (% / Total)',
            'Vehículos (% / Total)',
            'Maquinaria (% / Total)',
            'Embarcaciones (% / Total)',
        ]);

        return $cabeceras;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $styles = [
            1 => ['font' => ['bold' => true]],
        ];

        if (!empty($this->totales)) {
            $styles[$lastRow] = ['font' => ['bold' => true], 'borders' => ['top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];
        }

        return $styles;
    }
}