<?php

namespace App\Livewire\Asem;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Contratista;
use App\Models\Mandante;
use App\Models\Trabajador;
use App\Models\Vehiculo;
use App\Models\Maquinaria;
use App\Models\Embarcacion;
use App\Models\Dependencia;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\ContratistaUnidadOrganizacional;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use App\Exports\SupervisionContratistasExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use App\Services\ReporteSupervisionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Layout('layouts.app')]
class SupervisionGlobal extends Component
{
    public $mandantesDisponibles = [];
    public $filtroMandanteId = 'todos';
    public $contratistasConPromedios = [];
    public $fechaCache;
    public $calculandoEnVivo = false;
    public string $search = '';
    
    // Se eliminó $confirmingRecalculo
    
    public array $formatosExportacion = [];
    public $entidadesControlables = [];

    public $lugaresTrabajoDisponibles = [];
    public $filtroLugarTrabajoId = 'todos';
    public $unidadesOrganizacionalesDisponibles = [];
    public $filtroUoId = 'todos';

    public bool $filtrosCambiados = false;

    public array $totales = [];

    public function mount()
    {
        $this->mandantesDisponibles = Mandante::where('is_active', true)->orderBy('razon_social')->get();
        $this->actualizarLugaresTrabajo();
        $this->contratistasConPromedios = [];
        $this->fechaCache = 'No disponible (se requiere cálculo inicial)';
        $this->inicializarTotales();
    }

    protected function inicializarTotales()
    {
        $this->totales = [
            'contratistas' => 0,
            'trabajadores' => 0,
            'vehiculos' => 0,
            'maquinarias' => 0,
            'embarcaciones' => 0,
        ];
    }

    public function updatedFiltroMandanteId()
    {
        $this->filtroLugarTrabajoId = 'todos';
        $this->filtroUoId = 'todos';
        $this->actualizarLugaresTrabajo();
        $this->unidadesOrganizacionalesDisponibles = [];
        $this->filtrosCambiados = true;
    }

    public function updatedFiltroLugarTrabajoId()
    {
        $this->filtroUoId = 'todos';
        $this->actualizarUnidadesOrganizacionales();
        $this->filtrosCambiados = true;
    }

    public function updatedFiltroUoId()
    {
        $this->filtrosCambiados = true;
    }

    public function actualizarLugaresTrabajo()
    {
        $query = Dependencia::query()->where('estado', true);
        if ($this->filtroMandanteId !== 'todos') {
            $query->where('mandante_id', $this->filtroMandanteId);
        }
        $this->lugaresTrabajoDisponibles = $query->with('parent')->get()->sortBy('nombre_jerarquico');
    }

    public function actualizarUnidadesOrganizacionales()
    {
        if ($this->filtroLugarTrabajoId === 'todos') {
            $this->unidadesOrganizacionalesDisponibles = [];
            return;
        }

        $uoIdsQuery = DB::table('trabajador_vinculaciones')->select('unidad_organizacional_mandante_id')
            ->where('dependencia_id', $this->filtroLugarTrabajoId)
            ->union(DB::table('vehiculo_asignaciones')->select('unidad_organizacional_mandante_id')->where('dependencia_id', $this->filtroLugarTrabajoId))
            ->union(DB::table('maquinaria_asignaciones')->select('unidad_organizacional_mandante_id')->where('dependencia_id', $this->filtroLugarTrabajoId))
            ->union(DB::table('embarcacion_asignaciones')->select('unidad_organizacional_mandante_id')->where('dependencia_id', $this->filtroLugarTrabajoId));
        
        $uoIds = $uoIdsQuery->pluck('unidad_organizacional_mandante_id')->unique()->filter();

        $this->unidadesOrganizacionalesDisponibles = UnidadOrganizacionalMandante::whereIn('id', $uoIds)
            ->where('is_active', true)
            ->with('parent')
            ->get()
            ->sortBy('nombre_jerarquico');
    }

    protected function getCacheKey()
    {
        return "supervision_global_m{$this->filtroMandanteId}_lt{$this->filtroLugarTrabajoId}_uo{$this->filtroUoId}";
    }

    public function cargarDatosDesdeCache()
    {
        $this->calculandoEnVivo = false;
        $cacheKey = $this->getCacheKey();
        $data = Cache::get($cacheKey);
        $this->contratistasConPromedios = $data['promedios'] ?? [];
        $this->fechaCache = $data['fecha'] ?? 'No disponible (se requiere cálculo inicial)';
    }

    // Se eliminaron las funciones solicitarConfirmacionRecalculo y cancelarRecalculo

    public function forzarRecalculoEnVivo()
    {
        $this->filtrosCambiados = false;
        $this->calculandoEnVivo = true;
        $this->contratistasConPromedios = [];
        $this->inicializarTotales();

        try {
            $mandanteId = $this->filtroMandanteId;
            $lugarTrabajoId = $this->filtroLugarTrabajoId;
            $uoId = $this->filtroUoId;

            $queryTrabajadores = DB::table('trabajador_vinculaciones as tv')
                ->join('trabajadores as t', 'tv.trabajador_id', '=', 't.id')
                ->select('t.contratista_id', 'tv.dependencia_id', 'tv.unidad_organizacional_mandante_id');

            $queryVehiculos = DB::table('vehiculo_asignaciones as va')
                ->join('vehiculos as v', 'va.vehiculo_id', '=', 'v.id')
                ->select('v.contratista_id', 'va.dependencia_id', 'va.unidad_organizacional_mandante_id');
            
            $queryMaquinarias = DB::table('maquinaria_asignaciones as ma')
                ->join('maquinarias as m', 'ma.maquinaria_id', '=', 'm.id')
                ->select('m.contratista_id', 'ma.dependencia_id', 'ma.unidad_organizacional_mandante_id');

            $queryEmbarcaciones = DB::table('embarcacion_asignaciones as ea')
                ->join('embarcaciones as e', 'ea.embarcacion_id', '=', 'e.id')
                ->select('e.contratista_id', 'ea.dependencia_id', 'ea.unidad_organizacional_mandante_id');

            $baseQuery = $queryTrabajadores
                ->union($queryVehiculos)
                ->union($queryMaquinarias)
                ->union($queryEmbarcaciones);

            $contextosQuery = DB::query()->fromSub($baseQuery, 'contextos')
                ->select('contratista_id', 'dependencia_id', 'unidad_organizacional_mandante_id')
                ->distinct();

            if ($mandanteId !== 'todos') {
                $contextosQuery->whereIn('unidad_organizacional_mandante_id', function ($query) use ($mandanteId) {
                    $query->select('id')->from('unidades_organizacionales_mandante')->where('mandante_id', $mandanteId);
                });
            }
            if ($lugarTrabajoId !== 'todos') {
                $contextosQuery->where('dependencia_id', $lugarTrabajoId);
            }
            if ($uoId !== 'todos') {
                $contextosQuery->where('unidad_organizacional_mandante_id', $uoId);
            }

            $contextos = $contextosQuery->get()->filter(function ($contexto) {
                return !is_null($contexto->contratista_id) && !is_null($contexto->dependencia_id) && !is_null($contexto->unidad_organizacional_mandante_id);
            });

            $resultadosGlobales = [];
            $contratistasCargados = Contratista::whereIn('id', $contextos->pluck('contratista_id')->unique())->get()->keyBy('id');
            $dependenciasCargadas = Dependencia::with('parent')->whereIn('id', $contextos->pluck('dependencia_id')->unique())->get()->keyBy('id');
            $uosCargadas = UnidadOrganizacionalMandante::with(['mandante', 'parent'])->whereIn('id', $contextos->pluck('unidad_organizacional_mandante_id')->unique())->get()->keyBy('id');

            foreach ($contextos as $contexto) {
                $contratista = $contratistasCargados->get($contexto->contratista_id);
                $dependencia = $dependenciasCargadas->get($contexto->dependencia_id);
                $uo = $uosCargadas->get($contexto->unidad_organizacional_mandante_id);

                if (!$contratista || !$dependencia || !$uo) continue;

                $mandanteActual = $uo->mandante;

                $resultadoContexto = [
                    'contratista_id' => $contratista->id,
                    'razon_social' => $contratista->razon_social,
                    'rut' => $contratista->rut,
                    'mandante_nombre' => $mandanteActual->razon_social,
                    'mandante_id' => $mandanteActual->id,
                    'lugar_trabajo_id' => $dependencia->id,
                    'lugar_trabajo_nombre_jerarquico' => $dependencia->nombre_jerarquico,
                    'uo_id' => $uo->id,
                    'uo_nombre_jerarquico' => $uo->nombre_jerarquico,
                ];

                $vinculacionContratista = ContratistaUnidadOrganizacional::where('contratista_id', $contratista->id)
                    ->where('unidad_organizacional_mandante_id', $uo->id)->first();
                $resultadoContexto['cumplimiento_empresa'] = $vinculacionContratista->porcentaje_cumplimiento ?? 0;

                $resultadoContexto['promedio_trabajadores'] = $this->leerPromedioParaEntidadContextual('trabajador_vinculaciones', 'trabajador_id', $contratista->id, $dependencia->id, $uo->id);
                $resultadoContexto['promedio_vehiculos'] = $this->leerPromedioParaEntidadContextual('vehiculo_asignaciones', 'vehiculo_id', $contratista->id, $dependencia->id, $uo->id);
                $resultadoContexto['promedio_maquinarias'] = $this->leerPromedioParaEntidadContextual('maquinaria_asignaciones', 'maquinaria_id', $contratista->id, $dependencia->id, $uo->id);
                $resultadoContexto['promedio_embarcaciones'] = $this->leerPromedioParaEntidadContextual('embarcacion_asignaciones', 'embarcacion_id', $contratista->id, $dependencia->id, $uo->id);
                
                $resultadosGlobales[] = $resultadoContexto;
            }

            $cacheKey = $this->getCacheKey();
            $fecha = now()->format('d-m-Y H:i:s');
            Cache::put($cacheKey, ['promedios' => $resultadosGlobales, 'fecha' => $fecha], now()->addHours(1));

            $this->contratistasConPromedios = $resultadosGlobales;
            $this->fechaCache = $fecha;
            $this->calcularTotalesUnicos($contextos);
            $this->dispatch('notificacion-exito', 'Cálculo en vivo completado.');

        } catch (\Exception $e) {
            Log::error("Error en recálculo en vivo para ASEM: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
            $this->dispatch('notificacion-error', 'Ocurrió un error durante el recálculo.');
        } finally {
            $this->calculandoEnVivo = false;
        }
    }

    private function calcularTotalesUnicos($contextos)
    {
        if ($contextos->isEmpty()) {
            $this->inicializarTotales();
            return;
        }

        $this->totales['contratistas'] = $contextos->pluck('contratista_id')->unique()->count();

        $this->totales['trabajadores'] = DB::table('trabajador_vinculaciones')
            ->whereIn('dependencia_id', $contextos->pluck('dependencia_id')->unique())
            ->whereIn('unidad_organizacional_mandante_id', $contextos->pluck('unidad_organizacional_mandante_id')->unique())
            ->distinct('trabajador_id')->count('trabajador_id');

        $this->totales['vehiculos'] = DB::table('vehiculo_asignaciones')
            ->whereIn('dependencia_id', $contextos->pluck('dependencia_id')->unique())
            ->whereIn('unidad_organizacional_mandante_id', $contextos->pluck('unidad_organizacional_mandante_id')->unique())
            ->distinct('vehiculo_id')->count('vehiculo_id');

        $this->totales['maquinarias'] = DB::table('maquinaria_asignaciones')
            ->whereIn('dependencia_id', $contextos->pluck('dependencia_id')->unique())
            ->whereIn('unidad_organizacional_mandante_id', $contextos->pluck('unidad_organizacional_mandante_id')->unique())
            ->distinct('maquinaria_id')->count('maquinaria_id');

        $this->totales['embarcaciones'] = DB::table('embarcacion_asignaciones')
            ->whereIn('dependencia_id', $contextos->pluck('dependencia_id')->unique())
            ->whereIn('unidad_organizacional_mandante_id', $contextos->pluck('unidad_organizacional_mandante_id')->unique())
            ->distinct('embarcacion_id')->count('embarcacion_id');
    }

    private function leerPromedioParaEntidadContextual($tablaVinculacion, $columnaRecursoId, $contratistaId, $dependenciaId, $uoId)
    {
        $mapaTablas = [
            'trabajador_id' => 'trabajadores',
            'vehiculo_id' => 'vehiculos',
            'maquinaria_id' => 'maquinarias',
            'embarcacion_id' => 'embarcaciones',
        ];

        $tablaRecurso = $mapaTablas[$columnaRecursoId] ?? null;

        if (!$tablaRecurso) {
            return ['promedio' => 100, 'total' => 0];
        }

        $stats = DB::table($tablaVinculacion)
            ->join($tablaRecurso, "{$tablaVinculacion}.{$columnaRecursoId}", '=', "{$tablaRecurso}.id")
            ->where("{$tablaRecurso}.contratista_id", $contratistaId)
            ->where("{$tablaVinculacion}.dependencia_id", $dependenciaId)
            ->where("{$tablaVinculacion}.unidad_organizacional_mandante_id", $uoId)
            ->select(
                DB::raw('COUNT(DISTINCT ' . "{$tablaVinculacion}.{$columnaRecursoId}" . ') as total'),
                DB::raw('AVG(porcentaje_cumplimiento) as promedio')
            )
            ->first();

        return [
            'promedio' => (int) round($stats->promedio ?? 100),
            'total' => (int) ($stats->total ?? 0)
        ];
    }

    private function getDatosParaExportar()
    {
        $datosFiltrados = collect($this->contratistasConPromedios)->filter(function ($item) {
            if (empty($this->search)) return true;
            return str_contains(strtolower($item['razon_social']), strtolower($this->search)) ||
                   str_contains(str_replace(['.', '-'], '', $item['rut']), str_replace(['.', '-'], '', $this->search));
        });

        return $datosFiltrados->sortBy('razon_social');
    }

    public function exportarReportes()
    {
        $this->validate(['formatosExportacion' => 'required|array|min:1'], 
            ['formatosExportacion.required' => 'Debe seleccionar al menos un formato de exportación.']);

        $data = $this->getDatosParaExportar();
        if ($data->isEmpty()) {
            $this->dispatch('notificacion-error', 'No hay datos para exportar con los filtros actuales.');
            return;
        }

        $timestamp = now()->format('Y-m-d_His');
        $archivosGenerados = [];
        $incluirMandante = $this->filtroMandanteId === 'todos';

        if (in_array('excel', $this->formatosExportacion)) {
            $nombreArchivo = "reporte_supervision_{$timestamp}.xlsx";
            Excel::store(new SupervisionContratistasExport($data, $incluirMandante, $this->totales), $nombreArchivo, 'local');
            $archivosGenerados['excel'] = ['nombre' => $nombreArchivo, 'ruta' => Storage::disk('local')->path($nombreArchivo)];
        }
        
        if (in_array('pdf', $this->formatosExportacion)) {
            $nombreArchivo = "reporte_supervision_{$timestamp}.pdf";
            $pdf = Pdf::loadView('exports.supervision-global-pdf', [
                'data' => $data,
                'incluirMandante' => $incluirMandante,
                'totales' => $this->totales
            ])->setPaper('a4', 'landscape');
            
            Storage::disk('local')->put($nombreArchivo, $pdf->output());
            $archivosGenerados['pdf'] = ['nombre' => $nombreArchivo, 'ruta' => Storage::disk('local')->path($nombreArchivo)];
        }

        if (count($archivosGenerados) > 1) {
            $zipFileName = "reportes_supervision_{$timestamp}.zip";
            $zipPath = Storage::disk('local')->path($zipFileName);
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                foreach ($archivosGenerados as $file) {
                    $zip->addFile($file['ruta'], $file['nombre']);
                }
                $zip->close();
            }
            foreach ($archivosGenerados as $file) { Storage::disk('local')->delete($file['nombre']); }
            return response()->download($zipPath)->deleteFileAfterSend(true);
        } elseif (count($archivosGenerados) === 1) {
            $file = array_pop($archivosGenerados);
            return response()->download($file['ruta'], $file['nombre'])->deleteFileAfterSend(true);
        }
    }

    public function render()
    {
        $datosFiltrados = collect($this->contratistasConPromedios)->filter(function ($item) {
            if (empty($this->search)) return true;
            return str_contains(strtolower($item['razon_social']), strtolower($this->search)) ||
                   str_contains(str_replace(['.', '-'], '', $item['rut']), str_replace(['.', '-'], '', $this->search));
        });

        $datosOrdenados = $datosFiltrados->sortBy('razon_social');

        $contratistasAgrupados = $datosOrdenados->groupBy('contratista_id');

        return view('livewire.asem.supervision-global', [
            'contratistasAgrupados' => $contratistasAgrupados
        ]);
    }
}