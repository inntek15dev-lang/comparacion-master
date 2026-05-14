<?php

namespace App\Exports\Sheets;

use App\Models\Mandante;
use App\Models\Contratista;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\ReglaDocumental;
use App\Models\TipoVencimiento;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DocumentosListadosSheet implements FromCollection, WithTitle, WithHeadings, WithEvents
{
    protected $mandanteId;
    protected $contratistaId;
    protected $reglaDocumentalId;

    public function __construct($mandanteId = null, $contratistaId = null, $reglaDocumentalId = null)
    {
        $this->mandanteId = $mandanteId;
        $this->contratistaId = $contratistaId;
        $this->reglaDocumentalId = $reglaDocumentalId;
    }

    public function collection()
    {
        // 1. Mandante (filtrado o todos)
        $mandantesQuery = Mandante::where('is_active', true)->orderBy('razon_social');
        if ($this->mandanteId) {
            $mandantesQuery->where('id', $this->mandanteId);
        }
        $mandantes = $mandantesQuery->pluck('razon_social')->toArray();

        // 2. Contratistas (filtrado o todos)
        $contratistasQuery = Contratista::where('is_active', true)->orderBy('razon_social');
        
        $cuos = [];
        if ($this->mandanteId) {
            $cuosList = \App\Models\ContratistaUnidadOrganizacional::select('contratista_unidad_organizacional.contratista_id', 'contratista_unidad_organizacional.id_registro')
                ->join('unidades_organizacionales_mandante as uo', 'uo.id', '=', 'contratista_unidad_organizacional.unidad_organizacional_mandante_id')
                ->where('uo.mandante_id', $this->mandanteId)
                ->get();
                
            $cuos = $cuosList->keyBy('contratista_id');
            
            if ($this->contratistaId) {
                $contratistasQuery->where('id', $this->contratistaId);
            } else {
                $contratistasQuery->whereIn('id', $cuosList->pluck('contratista_id'));
            }
        } elseif ($this->contratistaId) {
            $contratistasQuery->where('id', $this->contratistaId);
        }
        
        $contratistas = $contratistasQuery->get()->map(function ($c) use ($cuos) {
            $idRegistro = $cuos[$c->id]->id_registro ?? null;
            return $idRegistro ? "{$idRegistro}" : "{$c->razon_social} (Sin ID_REGISTRO)";
        })->toArray();
        
        // 3. UOs (filtradas por mandante)
        $uosQuery = UnidadOrganizacionalMandante::with('mandante')->where('is_active', true);
        if ($this->mandanteId) {
            $uosQuery->where('mandante_id', $this->mandanteId);
        }
        $uos = $uosQuery->get()->map(function ($uo) {
            return ($uo->mandante->razon_social ?? 'SIN MANDANTE') . ' — ' . $uo->nombre_jerarquico;
        })->sort()->values()->toArray();

        $tiposEntidad = [
            'App\Models\Trabajador',
            'App\Models\Vehiculo',
            'App\Models\Maquinaria',
            'App\Models\Embarcacion',
            'App\Models\Contratista',
        ];

        // 4. Reglas Documentales (filtradas por mandante y opcionamente por regla)
        $reglasQuery = ReglaDocumental::with(['mandante', 'nombreDocumento'])->where('is_active', true);
        if ($this->mandanteId) {
            $reglasQuery->where('mandante_id', $this->mandanteId);
        }
        if ($this->reglaDocumentalId) {
            $reglasQuery->where('id', $this->reglaDocumentalId);
        }
        
        $reglas = $reglasQuery->get()->map(function ($regla) {
            return ($regla->mandante->razon_social ?? 'SIN MANDANTE') . ' — ' . ($regla->nombreDocumento->nombre ?? 'DOCUMENTO SIN NOMBRE');
        })->sort()->values()->toArray();

        $resultados = ['Aprobado', 'Rechazado'];

        $estadosValidacion = [
            'Pendiente',
            'Asignado',
            'Aprobado',
            'Rechazado',
            'Archivado',
            'Archivado-Revalidado',
            'Revisado-Revalidado',
            'En Revisión (Asem)',
            'Pendiente Validación Mandante',
            'Revisado (Finalizado)'
        ];

        // 5. Tipos de Vencimiento desde la BD (mismos del formulario de Regla Documental)
        // Se excluyen los que no aplican a reglas documentales (son de migración o catálogos históricos)
        $tiposVencimiento = TipoVencimiento::orderBy('nombre')
            ->whereNotIn('nombre', ['6 MESES', 'ANUAL', 'INDEFINIDA'])
            ->pluck('nombre')
            ->toArray();
        // Agregar MIGRADO para documentos históricos
        $tiposVencimiento[] = 'MIGRADO';

        $maxRows = max(
            count($mandantes), count($contratistas), count($uos), 
            count($tiposEntidad), count($reglas), count($resultados),
            count($estadosValidacion), count($tiposVencimiento)
        );

        $data = [];
        for ($i = 0; $i < $maxRows; $i++) {
            $data[] = [
                $mandantes[$i] ?? null,
                $contratistas[$i] ?? null,
                $uos[$i] ?? null,
                $tiposEntidad[$i] ?? null,
                $reglas[$i] ?? null,
                $resultados[$i] ?? null,
                $estadosValidacion[$i] ?? null,
                $tiposVencimiento[$i] ?? null
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Mandantes',
            'Contratistas',
            'UOs',
            'Tipos Entidad',
            'Reglas Documentales',
            'Resultados',
            'Estados Validación',
            'Tipos Vencimiento'
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
                $event->sheet->getDelegate()->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
            },
        ];
    }
}
