<?php

namespace App\Livewire\Asem\Informes;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Models\DocumentoCargado;
use App\Models\Mandante;
use App\Models\Contratista;
use App\Models\NombreDocumento;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArrayExport;

class PanelInformes extends Component
{
    use WithPagination;

    public string $informeActivo = 'tiempos';

    // Propiedades para los filtros
    public $mandanteId = '';
    public $contratistaId = '';
    public $nombreDocumento = '';
    public $entidadType = '';
    public $fechaDesde = '';
    public $fechaHasta = '';
    public $resultadoValidacion = '';
    public $fechaCargaDesde = '';
    public $fechaCargaHasta = '';
    public array $estadoValidacion = [];
    public $validadorId = '';
    public $validadores = [];
    
    public array $listaEstados = [];

    public function mount()
    {
        $this->listaEstados = [
            'Revisado' => 'Revisado (Finalizado)',
            'Revisado-Revalidado' => 'Revisado (Por Revalidación)',
            'Archivado' => 'Archivado',
            'Archivado-Revalidado' => 'Archivado (Por Revalidación)',
        ];
        $this->validadores = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['ASEM_Admin', 'ASEM_Validator']);
        })->orderBy('name')->get();
    }

    public function seleccionarInforme(string $tipo)
    {
        $this->informeActivo = $tipo;
        $this->resetPage();
    }

    private function buildBaseQuery()
    {
        $query = DocumentoCargado::query()
            ->with(['mandante', 'contratista', 'entidad', 'validadorAsem']);

        $query->when($this->mandanteId, fn ($q) => $q->where('mandante_id', $this->mandanteId));
        $query->when($this->contratistaId, fn ($q) => $q->where('contratista_id', $this->contratistaId));
        $query->when($this->nombreDocumento, fn ($q) => $q->where('nombre_documento_snapshot', $this->nombreDocumento));
        $query->when($this->entidadType, fn ($q) => $q->where('entidad_type', $this->entidadType));
        $query->when($this->fechaDesde, fn ($q) => $q->whereDate('fecha_validacion', '>=', $this->fechaDesde));
        $query->when($this->fechaHasta, fn ($q) => $q->whereDate('fecha_validacion', '<=', $this->fechaHasta));
        $query->when($this->resultadoValidacion, fn ($q) => $q->where('resultado_validacion', $this->resultadoValidacion));
        $query->when($this->fechaCargaDesde, fn ($q) => $q->whereDate('created_at', '>=', $this->fechaCargaDesde));
        $query->when($this->fechaCargaHasta, fn ($q) => $q->whereDate('created_at', '<=', $this->fechaCargaHasta));
        
        if ($this->informeActivo === 'tiempos' && !empty($this->estadoValidacion)) {
            $query->whereIn('estado_validacion', $this->estadoValidacion);
        }

        $query->when($this->validadorId, fn ($q) => $q->where('asem_validador_id', $this->validadorId));

        return $query;
    }

    private function parsearObservacionRechazo(string $observacion): array
    {
        $rechazos = [];
        $lineas = explode("\n", $observacion);
        foreach ($lineas as $linea) {
            $lineaTrimmed = trim($linea);
            if (str_starts_with($lineaTrimmed, '-')) {
                $rechazos[] = trim(substr($lineaTrimmed, 1));
            }
        }
        if (empty($rechazos) && !empty(trim($observacion))) {
            $prefijo = "Motivos de rechazo:";
            if (stripos(trim($observacion), $prefijo) === 0) {
                return [trim(substr(trim($observacion), strlen($prefijo)))];
            }
            return [trim($observacion)];
        }
        return $rechazos;
    }

    public function render()
    {
        $datos = null;

        if ($this->informeActivo === 'tiempos') {
            $query = $this->buildBaseQuery()->whereNotNull('fecha_validacion');
            $datos = $query->orderBy('fecha_validacion', 'desc')->paginate(15);
        } 
        elseif ($this->informeActivo === 'rechazos') {
            $query = $this->buildBaseQuery()
                ->where('resultado_validacion', 'Rechazado')
                ->whereNotNull('observacion_rechazo')
                ->where('observacion_rechazo', '!=', '');
            
            $documentosRechazados = $query->get();
            $filasInforme = new Collection();

            foreach ($documentosRechazados as $doc) {
                $rechazos = $this->parsearObservacionRechazo($doc->observacion_rechazo);
                foreach ($rechazos as $textoRechazo) {
                    $fila = new \stdClass();
                    $fila->id = $doc->id;
                    $fila->nombre_documento_snapshot = $doc->nombre_documento_snapshot;
                    $fila->mandante = $doc->mandante;
                    $fila->contratista = $doc->contratista;
                    $fila->entidad = $doc->entidad;
                    $fila->texto_rechazo = $textoRechazo;
                    $fila->validadorAsem = $doc->validadorAsem;
                    $filasInforme->push($fila);
                }
            }

            $page = $this->getPage();
            $perPage = 15;
            $datos = new LengthAwarePaginator(
                $filasInforme->forPage($page, $perPage),
                $filasInforme->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'pageName' => 'page']
            );
        }

        return view('livewire.asem.informes.panel-informes', [
            'datos' => $datos,
            'mandantes' => Mandante::orderBy('razon_social')->get(),
            'contratistas' => Contratista::orderBy('razon_social')->get(),
            'nombresDocumentos' => NombreDocumento::orderBy('nombre')->get(),
        ])->layout('layouts.app');
    }

    public function exportarExcel()
    {
        \App\Services\AuditService::securityAlert(
            "Exportación masiva de datos sensibles desde el Panel de Informes (Contexto: " . $this->informeActivo . ")",
            "EXPORTE_MASIVO",
            ['informe' => $this->informeActivo]
        );

        if ($this->informeActivo === 'tiempos') {
            $query = $this->buildBaseQuery()->whereNotNull('fecha_validacion');
            $datosParaExportar = $query->orderBy('fecha_validacion', 'desc')->get();

            $data = $datosParaExportar->map(function ($doc, $index) {
                return [
                    '#' => $index + 1,
                    'ID Documento' => $doc->id,
                    'Documento' => $doc->nombre_documento_snapshot,
                    'Mandante' => $doc->mandante->razon_social ?? 'N/A',
                    'Contratista' => $doc->contratista->razon_social ?? 'N/A',
                    'Recurso' => $this->getNombreRecurso($doc->entidad),
                    'Validador' => $doc->validadorAsem->name ?? 'N/A',
                    'Fecha Carga' => Carbon::parse($doc->created_at)->format('d-m-Y H:i:s'),
                    'FECHA DE VALIDACION' => Carbon::parse($doc->fecha_validacion)->format('d-m-Y H:i:s'),
                    'Tiempo de Validación (Hrs)' => $this->calcularHorasValidacion($doc->created_at, $doc->fecha_validacion),
                    'RESULTADO VALIDACION' => $doc->resultado_validacion,
                    'ESTADO VALIDACION' => $doc->estado_validacion,
                ];
            })->toArray();

            $headings = [
                '#', 'ID Documento', 'Documento', 'Mandante', 'Contratista', 'Recurso', 'Validador',
                'Fecha Carga', 'FECHA DE VALIDACION', 'Tiempo de Validación (Hrs)', 'RESULTADO VALIDACION', 'ESTADO VALIDACION'
            ];
            
            $nombreArchivo = 'Informe_Tiempos_de_Validacion_' . now()->format('Y-m-d_His') . '.xlsx';
            return Excel::download(new ArrayExport($data, $headings), $nombreArchivo);

        } elseif ($this->informeActivo === 'rechazos') {
            $query = $this->buildBaseQuery()
                ->where('resultado_validacion', 'Rechazado')
                ->whereNotNull('observacion_rechazo')
                ->where('observacion_rechazo', '!=', '');
            
            $documentosRechazados = $query->get();
            $data = [];
            $correlativo = 1;
            foreach ($documentosRechazados as $doc) {
                $rechazos = $this->parsearObservacionRechazo($doc->observacion_rechazo);
                foreach ($rechazos as $textoRechazo) {
                    $data[] = [
                        '#' => $correlativo++,
                        'ID Documento' => $doc->id,
                        'Documento' => $doc->nombre_documento_snapshot,
                        'Mandante' => $doc->mandante->razon_social ?? 'N/A',
                        'Contratista' => $doc->contratista->razon_social ?? 'N/A',
                        'Recurso' => $this->getNombreRecurso($doc->entidad),
                        'Validador' => $doc->validadorAsem->name ?? 'N/A',
                        'RECHAZO' => $textoRechazo,
                    ];
                }
            }

            $headings = ['#', 'ID Documento', 'Documento', 'Mandante', 'Contratista', 'Recurso', 'Validador', 'RECHAZO'];
            $nombreArchivo = 'Informe_Textos_de_Rechazo_' . now()->format('Y-m-d_His') . '.xlsx';
            return Excel::download(new ArrayExport($data, $headings), $nombreArchivo);
        }
    }

    public function generarInformeInteractivo()
    {
        \App\Services\AuditService::securityAlert(
            "Generación de Informe Interactivo de Tiempos de Validación (HTML)",
            "EXPORTE_MASIVO",
            ['formato' => 'HTML_INTERACTIVO']
        );

        $query = $this->buildBaseQuery()->whereNotNull('fecha_validacion');
        $datos = $query->orderBy('fecha_validacion', 'asc')->get();

        if ($datos->isEmpty()) {
            return;
        }

        $totalDocs = $datos->count();
        $aprobados = $datos->where('resultado_validacion', 'Aprobado')->count();
        $rechazados = $totalDocs - $aprobados;
        $tasaAprobacion = $totalDocs > 0 ? round(($aprobados / $totalDocs) * 100, 2) : 0;
        
        $totalHoras = $datos->sum(fn($doc) => $this->calcularHorasValidacion($doc->created_at, $doc->fecha_validacion));
        $tiempoPromedio = $totalDocs > 0 ? round($totalHoras / $totalDocs, 2) : 0;

        $volumenPorDia = $datos->groupBy(fn($doc) => Carbon::parse($doc->fecha_validacion)->format('Y-m-d'))->map(fn ($group) => $group->count());

        $rendimientoValidador = $datos->groupBy('validadorAsem.name')
            ->map(function ($group, $name) {
                if(empty($name)) $name = 'Sin Asignar';
                $total = $group->count();
                $avgHoras = $total > 0 ? $group->sum(fn ($d) => $this->calcularHorasValidacion($d->created_at, $d->fecha_validacion)) / $total : 0;
                return ['validador' => $name, 'total_docs' => $total, 'tiempo_promedio' => round($avgHoras, 2)];
            })->sortBy('validador')->values();

        // ==================================================================
        // INICIO DE LA MODIFICACIÓN CANÓNICA: PRE-PROCESAMIENTO DE DATOS PARA LA VISTA
        // ==================================================================
        $datosTabla = $datos->map(function ($doc, $index) {
            return (object)[
                'correlativo' => $index + 1,
                'id' => $doc->id,
                'nombre_documento_snapshot' => $doc->nombre_documento_snapshot,
                'mandante_nombre' => $doc->mandante->razon_social ?? 'N/A',
                'contratista_nombre' => $doc->contratista->razon_social ?? 'N/A',
                'validador_nombre' => $doc->validadorAsem->name ?? 'N/A',
                'horas_validacion' => $this->calcularHorasValidacion($doc->created_at, $doc->fecha_validacion),
                'resultado_validacion' => $doc->resultado_validacion,
            ];
        });
        // ==================================================================
        // FIN DE LA MODIFICACIÓN CANÓNICA
        // ==================================================================

        $payload = [
            'fechaGeneracion' => now()->format('d-m-Y H:i:s'),
            'filtros' => $this->getFiltrosActivos(),
            'kpis' => ['totalDocs' => $totalDocs, 'tasaAprobacion' => $tasaAprobacion, 'tiempoPromedio' => $tiempoPromedio],
            'graficos' => [
                'volumenPorDia' => ['labels' => $volumenPorDia->keys(), 'data' => $volumenPorDia->values()],
                'rendimientoValidador' => ['labels' => $rendimientoValidador->pluck('validador'), 'dataTotal' => $rendimientoValidador->pluck('total_docs'), 'dataTiempo' => $rendimientoValidador->pluck('tiempo_promedio')],
                'tasaGeneral' => ['aprobados' => $aprobados, 'rechazados' => $rechazados]
            ],
            // ==================================================================
            // INICIO DE LA MODIFICACIÓN CANÓNICA: USAR DATOS PRE-PROCESADOS
            // ==================================================================
            'tablaDatos' => $datosTabla
            // ==================================================================
            // FIN DE LA MODIFICACIÓN CANÓNICA
            // ==================================================================
        ];

        $html = view('exports.informe-tiempos-interactivo', $payload)->render();
        $nombreArchivo = 'Informe_Interactivo_Tiempos_Validacion_' . now()->format('Y-m-d_His') . '.html';

        return response()->streamDownload(fn() => print($html), $nombreArchivo);
    }

    private function getFiltrosActivos(): array
    {
        $filtros = [];
        if($this->mandanteId) $filtros['Mandante'] = Mandante::find($this->mandanteId)->razon_social ?? 'N/A';
        if($this->contratistaId) $filtros['Contratista'] = Contratista::find($this->contratistaId)->razon_social ?? 'N/A';
        if($this->nombreDocumento) $filtros['Documento'] = $this->nombreDocumento;
        if($this->entidadType) $filtros['Entidad'] = str_replace('App\\Models\\', '', $this->entidadType);
        if($this->resultadoValidacion) $filtros['Resultado'] = $this->resultadoValidacion;
        if($this->validadorId) $filtros['Validador'] = User::find($this->validadorId)->name ?? 'N/A';
        if(!empty($this->estadoValidacion)) $filtros['Estados'] = implode(', ', $this->estadoValidacion);
        if($this->fechaDesde) $filtros['Validación Desde'] = Carbon::parse($this->fechaDesde)->format('d-m-Y');
        if($this->fechaHasta) $filtros['Validación Hasta'] = Carbon::parse($this->fechaHasta)->format('d-m-Y');
        if($this->fechaCargaDesde) $filtros['Carga Desde'] = Carbon::parse($this->fechaCargaDesde)->format('d-m-Y');
        if($this->fechaCargaHasta) $filtros['Carga Hasta'] = Carbon::parse($this->fechaCargaHasta)->format('d-m-Y');
        
        return $filtros;
    }

    private function getNombreRecurso($entidad): string
    {
        if (!$entidad) { return 'Entidad no encontrada'; }
        switch (get_class($entidad)) {
            case 'App\Models\Contratista': return 'Empresa: ' . $entidad->razon_social;
            case 'App\Models\Trabajador': return 'Trabajador: ' . trim($entidad->nombres . ' ' . $entidad->apellido_paterno . ' ' . $entidad->apellido_materno);
            case 'App\Models\Vehiculo': return 'Vehículo: ' . $entidad->patente_letras . $entidad->patente_numeros;
            case 'App\Models\Maquinaria': return 'Maquinaria: ' . ($entidad->identificador_letras ?? '') . ($entidad->identificador_numeros ?? '');
            case 'App\Models\Embarcacion': return 'Embarcación: ' . ($entidad->matricula_letras ?? '') . ($entidad->matricula_numeros ?? '');
            default: return 'Recurso Desconocido';
        }
    }

    public function calcularHorasValidacion($inicio, $fin): ?int
    {
        if (!$inicio || !$fin) { return null; }
        return Carbon::parse($inicio)->diffInHours(Carbon::parse($fin));
    }
    
    public function updating($property)
    {
        if (in_array($property, ['mandanteId', 'contratistaId', 'nombreDocumento', 'entidadType', 'fechaDesde', 'fechaHasta', 'resultadoValidacion', 'fechaCargaDesde', 'fechaCargaHasta', 'estadoValidacion', 'validadorId'])) {
            $this->resetPage();
        }
    }
}