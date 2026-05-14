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
    public array $columnasExcluidas = ['id_bd'];

    public string $filtroNumeroContrato = '';
    public $filtroTipoContratoId = 'todos';
    public $tiposContratoDisponibles = [];

    public function mount()
    {
        $this->mandantesDisponibles = Mandante::where('is_active', true)->orderBy('razon_social')->get();
        $this->actualizarLugaresTrabajo();
        $this->contratistasConPromedios = [];
        $this->fechaCache = 'No disponible (se requiere cálculo inicial)';
        $this->inicializarTotales();
        $this->tiposContratoDisponibles = \App\Models\TipoContrato::where('is_active', true)->orderBy('nombre')->get();
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

            $contextosQuery = DB::table('contratista_unidad_organizacional as cuo')
                ->join('contratistas as c', 'cuo.contratista_id', '=', 'c.id')
                ->where('c.is_active', true)
                ->select(
                    'cuo.id as vinculacion_id',
                    'cuo.id_registro',
                    'cuo.contratista_id',
                    'cuo.dependencia_id',
                    'cuo.unidad_organizacional_mandante_id',
                    'cuo.numero_contrato',
                    'cuo.tipo_contrato_id',
                    'cuo.porcentaje_cumplimiento'
                );

            if ($mandanteId !== 'todos') {
                $contextosQuery->whereIn('cuo.unidad_organizacional_mandante_id', function ($query) use ($mandanteId) {
                    $query->select('id')->from('unidades_organizacionales_mandante')->where('mandante_id', $mandanteId);
                });
            }
            if ($lugarTrabajoId !== 'todos') {
                $contextosQuery->where('cuo.dependencia_id', $lugarTrabajoId);
            }
            if ($uoId !== 'todos') {
                $contextosQuery->where('cuo.unidad_organizacional_mandante_id', $uoId);
            }

            $contextos = $contextosQuery->get()->filter(function ($contexto) {
                return !is_null($contexto->contratista_id)
                    && !is_null($contexto->dependencia_id)
                    && !is_null($contexto->unidad_organizacional_mandante_id);
            });

            $resultadosGlobales = [];
            $contratistasCargados = Contratista::whereIn('id', $contextos->pluck('contratista_id')->unique())->get()->keyBy('id');
            $dependenciasCargadas = Dependencia::with('parent')->whereIn('id', $contextos->pluck('dependencia_id')->unique())->get()->keyBy('id');
            $uosCargadas = UnidadOrganizacionalMandante::with(['mandante', 'parent'])->whereIn('id', $contextos->pluck('unidad_organizacional_mandante_id')->unique())->get()->keyBy('id');

            $tiposContratoCargados = \App\Models\TipoContrato::whereIn('id', $contextos->pluck('tipo_contrato_id')->unique()->filter())->get()->keyBy('id');

            $relacionesPadreHijo = DB::table('solicitudes_vinculacion')
                ->where('estado', 'APROBADA')
                ->where('tipo_solicitud', 'SUBCONTRATISTA')
                ->whereIn('contratista_id', $contextos->pluck('contratista_id')->unique())
                ->pluck('contratista_padre_id', 'contratista_id')
                ->toArray();

            $calcularNivel = function($cId, $mId) use (&$relacionesPadreHijo, &$calcularNivel) {
                if (!isset($relacionesPadreHijo[$cId]) || empty($relacionesPadreHijo[$cId])) return 1;
                return min(4, 1 + $calcularNivel($relacionesPadreHijo[$cId], $mId));
            };

            foreach ($contextos as $contexto) {
                $contratista = $contratistasCargados->get($contexto->contratista_id);
                $dependencia = $dependenciasCargadas->get($contexto->dependencia_id);
                $uo = $uosCargadas->get($contexto->unidad_organizacional_mandante_id);
                if (!$contratista || !$dependencia || !$uo) continue;
                $mandanteActual = $uo->mandante;
                $contratistaPadreId = $relacionesPadreHijo[$contratista->id] ?? null;
                $nivelJerarquico = $calcularNivel($contratista->id, $mandanteActual->id);
                $tipoContrato = $contexto->tipo_contrato_id ? $tiposContratoCargados->get($contexto->tipo_contrato_id) : null;
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
                    'contratista_padre_id' => $contratistaPadreId,
                    'nivel_jerarquico' => $nivelJerarquico,
                    'vinculacion_id' => $contexto->vinculacion_id,
                    'id_registro' => $contexto->id_registro,
                    'id_bd' => $contexto->contratista_id,
                    'cumplimiento_empresa' => $contexto->porcentaje_cumplimiento ?? 0,
                    'numero_contrato' => $contexto->numero_contrato,
                    'tipo_contrato_id' => $contexto->tipo_contrato_id,
                    'tipo_contrato_nombre' => $tipoContrato->nombre ?? null,
                ];
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
            if (!empty($this->search)) {
                $matchSearch = str_contains(strtolower($item['razon_social']), strtolower($this->search)) ||
                       str_contains(str_replace(['.', '-'], '', $item['rut']), str_replace(['.', '-'], '', $this->search));
                if (!$matchSearch) return false;
            }
            if (!empty($this->filtroNumeroContrato)) {
                $numContrato = $item['numero_contrato'] ?? '';
                if (!str_contains(strtolower($numContrato), strtolower($this->filtroNumeroContrato))) return false;
            }
            if ($this->filtroTipoContratoId !== 'todos') {
                if (($item['tipo_contrato_id'] ?? null) != $this->filtroTipoContratoId) return false;
            }
            return true;
        });
        $datosAgrupados = $datosFiltrados->groupBy('vinculacion_id');
        $collectionPlana = $datosAgrupados->map(function($grupo) {
            $item = collect($grupo->first());
            $item['temporal_children'] = collect();
            $item['is_attached_to_parent'] = false;
            return $item;
        });
        foreach ($collectionPlana as $childId => $child) {
            if (empty($child['contratista_padre_id'])) continue;
            $candidatos = $collectionPlana->filter(function($p) use ($child) { return $p['contratista_id'] == $child['contratista_padre_id']; });
            if ($candidatos->isEmpty()) continue;
            $mejorPadreId = null; $mejorPuntaje = -1;
            foreach ($candidatos as $padreId => $padre) {
                $puntaje = 0;
                if ($padre['uo_id'] == $child['uo_id']) $puntaje += 10;
                elseif ($padre['uo_id'] && $child['uo_id']) $puntaje -= 50;
                if ($padre['lugar_trabajo_id'] == $child['lugar_trabajo_id']) $puntaje += 10;
                elseif ($padre['lugar_trabajo_id'] && $child['lugar_trabajo_id']) $puntaje -= 20;
                if (($child['numero_contrato'] ?? '') && ($padre['numero_contrato'] ?? '')) {
                    if ($child['numero_contrato'] == $padre['numero_contrato']) $puntaje += 50;
                    else $puntaje -= 100;
                }
                if ($puntaje > $mejorPuntaje) { $mejorPuntaje = $puntaje; $mejorPadreId = $padreId; }
            }
            if ($mejorPadreId && $mejorPuntaje > -50) {
                $collectionPlana[$mejorPadreId]['temporal_children']->push($childId);
                $upd = $collectionPlana[$childId]; $upd['is_attached_to_parent'] = true; $collectionPlana[$childId] = $upd;
            }
        }
        $resultadoPlano = collect();
        $aplanarArbol = function($itemIds, $prefijo = '') use (&$aplanarArbol, &$resultadoPlano, &$collectionPlana) {
            $itemsOrd = collect($itemIds)->map(function($id) use ($collectionPlana) { return $collectionPlana[$id]; })->sortBy('razon_social')->pluck('vinculacion_id');
            $c = 1;
            foreach ($itemsOrd as $id) {
                $item = $collectionPlana[$id];
                $item['correlativo_jerarquico'] = $prefijo === '' ? (string)$c : "$prefijo.$c";
                $collectionPlana[$id] = $item;
                $resultadoPlano->push($item);
                if ($item['temporal_children']->isNotEmpty()) $aplanarArbol($item['temporal_children'], $item['correlativo_jerarquico']);
                $c++;
            }
        };
        $raicesIds = $collectionPlana->filter(function($item) { return !$item['is_attached_to_parent']; })->pluck('vinculacion_id');
        $aplanarArbol($raicesIds);
        $gC = 0; $lBG = null; $sC = 0; $lBS = null;
        $itemsConColor = $resultadoPlano->map(function($item) use ($resultadoPlano, &$gC, &$lBG, &$sC, &$lBS) {
            $nB = (int)explode('.', $item['correlativo_jerarquico'])[0];
            $tieneSub = $resultadoPlano->filter(function($i) use ($nB) {
                return str_starts_with($i['correlativo_jerarquico'], $nB.'.') && $i['correlativo_jerarquico'] !== (string)$nB;
            })->count() > 0;
            if ($tieneSub) {
                if ($lBG !== $nB) { $gC++; $lBG = $nB; }
                $item['skill_bg_class'] = ($gC % 2 == 1) ? 'bg-yellow-100/50 dark:bg-yellow-900/30' : 'bg-orange-100/50 dark:bg-orange-900/30';
            } else {
                if ($lBS !== $nB) { $sC++; $lBS = $nB; }
                $item['skill_bg_class'] = ($sC % 2 == 1) ? 'bg-white dark:bg-gray-800' : 'bg-gray-100 dark:bg-gray-800';
            }
            return $item;
        });
        $contratistasAgrupados = collect();
        foreach ($itemsConColor as $rep) { $contratistasAgrupados[$rep['vinculacion_id']] = collect([$rep]); }
        return view('livewire.asem.supervision-global', ['contratistasAgrupados' => $contratistasAgrupados]);
    }
}