<?php

namespace App\Exports\Sheets;

use App\Models\Nacionalidad;
use App\Models\TipoPermanencia;
use App\Models\Sexo;
use App\Models\EstadoCivil;
use App\Models\Etnia;
use App\Models\NivelEducacional;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\Dependencia;
use App\Models\CargoMandante;
use App\Models\Mandante;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrabajadoresListadosSheet implements FromCollection, WithTitle, WithHeadings, WithEvents
{
    protected $mandante_id;
    protected $contratista_id;

    public function __construct($mandante_id = null, $contratista_id = null)
    {
        $this->mandante_id = $mandante_id;
        $this->contratista_id = $contratista_id;
    }

    public function collection()
    {
        // Si hay un mandante específico, filtramos por él. Si no, cargamos todos.
        if ($this->mandante_id) {
            $mandantes = Mandante::where('id', $this->mandante_id)->pluck('razon_social')->toArray();
        } else {
            $mandantes = Mandante::where('is_active', true)->orderBy('razon_social')->pluck('razon_social')->toArray();
        }

        $nacionalidades = Nacionalidad::where('is_active', true)->orderBy('nombre')->pluck('nombre')->toArray();
        $tiposPermanencia = TipoPermanencia::orderBy('nombre')->pluck('nombre')->toArray();
        $sexos = Sexo::where('is_active', true)->orderBy('nombre')->pluck('nombre')->toArray();
        $estadosCiviles = EstadoCivil::where('is_active', true)->orderBy('nombre')->pluck('nombre')->toArray();
        $etnias = Etnia::where('is_active', true)->orderBy('nombre')->pluck('nombre')->toArray();
        $nivelesEducacionales = NivelEducacional::where('is_active', true)->orderBy('nombre')->pluck('nombre')->toArray();

        // Construir listas compuestas: "MANDANTE - RECURSO". Filtrar por mandante si existe.
        $uosQuery = UnidadOrganizacionalMandante::with('mandante')->where('is_active', true);
        if ($this->mandante_id) $uosQuery->where('mandante_id', $this->mandante_id);
        $uos = $uosQuery->get()->map(function ($uo) {
            return ($uo->mandante->razon_social ?? 'SIN MANDANTE') . ' — ' . $uo->nombre_jerarquico;
        })->sort()->values()->toArray();

        // Eliminar 'where is_active' ya que tabla dependencias no lo tiene
        $lugaresQuery = Dependencia::with('mandante');
        if ($this->mandante_id) $lugaresQuery->where('mandante_id', $this->mandante_id);
        $lugares = $lugaresQuery->get()->map(function ($dep) {
            return ($dep->mandante->razon_social ?? 'SIN MANDANTE') . ' — ' . $dep->nombre_jerarquico;
        })->sort()->values()->toArray();

        $cargosQuery = CargoMandante::with('mandante')->where('is_active', true);
        if ($this->mandante_id) $cargosQuery->where('mandante_id', $this->mandante_id);
        $cargos = $cargosQuery->get()->map(function ($cargo) {
            return ($cargo->mandante->razon_social ?? 'SIN MANDANTE') . ' — ' . $cargo->nombre_cargo;
        })->sort()->values()->toArray();

        // Encontrar la lista más larga para iterar
        $maxRows = max(
            count($mandantes), count($nacionalidades), count($tiposPermanencia), count($sexos),
            count($estadosCiviles), count($etnias), count($nivelesEducacionales),
            count($uos), count($lugares), count($cargos)
        );

        $data = [];
        for ($i = 0; $i < $maxRows; $i++) {
            $data[] = [
                $mandantes[$i] ?? null,
                $nacionalidades[$i] ?? null,
                $tiposPermanencia[$i] ?? null,
                $sexos[$i] ?? null,
                $estadosCiviles[$i] ?? null,
                $etnias[$i] ?? null,
                $nivelesEducacionales[$i] ?? null,
                $uos[$i] ?? null,
                $lugares[$i] ?? null,
                $cargos[$i] ?? null,
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Mandantes',
            'Nacionalidades',
            'Tipos de Permanencia',
            'Sexos',
            'Estados Civiles',
            'Etnias',
            'Niveles Educacionales',
            'Unidades Organizativas (UO)',
            'Lugares de Trabajo',
            'Cargos'
        ];
    }

    public function title(): string
    {
        return 'Listados';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Ocultar esta hoja para que el usuario no la vea, pero sirva para las validaciones
                $event->sheet->getDelegate()->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
            },
        ];
    }
}
