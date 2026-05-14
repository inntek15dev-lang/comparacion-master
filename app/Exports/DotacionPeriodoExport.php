<?php
namespace App\Exports;

use App\Models\CarpetaVerificacion;
use App\Models\CarpetaVerificacionTrabajador;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class DotacionPeriodoExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $carpetaId;
    protected $carpetaAnio;
    protected $carpetaMes;

    public function __construct(int $carpetaId)
    {
        $this->carpetaId = $carpetaId;

        $carpeta = CarpetaVerificacion::find($carpetaId);
        $this->carpetaAnio = $carpeta->anio ?? null;
        $this->carpetaMes  = $carpeta->mes ?? null;
    }

    public function query()
    {
        return CarpetaVerificacionTrabajador::where('carpeta_verificacion_id', $this->carpetaId)
            ->with([
                'vinculacion.trabajador',
                'vinculacion.cargoMandante'
            ]);
    }

    public function headings(): array
    {
        return [
            'RUT',
            'NOMBRES',
            'APELLIDO PATERNO',
            'APELLIDO MATERNO',
            'CARGO',
            'F. INGRESO VINCULACION',
            '¿NUEVO INGRESO?',
            'F. CONTRATO',
            'F. CREACION SISTEMA',
            'ESTADO REVISION',
            'FECHA FINIQUITO',
            'ORIGEN (VIGENTE/ARRASTRE)',
        ];
    }

    public function map($vt): array
    {
        $v = $vt->vinculacion;
        $t = $v->trabajador ?? null;

        $esNuevo = false;
        if ($v->fecha_ingreso_vinculacion && $this->carpetaAnio && $this->carpetaMes) {
            $fi = Carbon::parse($v->fecha_ingreso_vinculacion);
            $esNuevo = $fi->year == $this->carpetaAnio && $fi->month == $this->carpetaMes;
        }

        return [
            $t->rut ?? '-',
            $t->nombres ?? '-',
            $t->apellido_paterno ?? '-',
            $t->apellido_materno ?? '-',
            $v->cargoMandante->nombre_cargo ?? 'N/A',
            $v->fecha_ingreso_vinculacion ? $v->fecha_ingreso_vinculacion->format('d/m/Y') : 'N/A',
            $esNuevo ? 'NUEVO' : '',
            $v->fecha_contrato ? $v->fecha_contrato->format('d/m/Y') : 'N/A',
            $v->created_at ? $v->created_at->format('d/m/Y') : 'N/A',
            $vt->estado_revision,
            $v->fecha_finiquito ? Carbon::parse($v->fecha_finiquito)->format('d/m/Y') : 'N/A',
            $vt->tipo_registro,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
