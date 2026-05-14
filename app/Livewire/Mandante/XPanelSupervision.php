<?php

namespace App\Livewire\Mandante;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Contratista;
use App\Models\Trabajador;
use App\Models\Vehiculo;
use App\Models\Maquinaria;
use App\Models\Embarcacion;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use App\Exports\SupervisionContratistasExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use App\Services\ReporteSupervisionService;
use Illuminate\Support\Collection;

class PanelSupervision extends Component
{
    public $mandante;
    public $contratistasConPromedios = [];
    public $fechaCache;
    public $calculandoEnVivo = false;
    public string $search = '';
    public string $sortBy = 'razon_social';
    public string $sortDir = 'asc';
    public array $formatosExportacion = [];
    public $entidadesControlables = [];
    public bool $confirmingRecalculo = false;

    public function mount()
    {
        $this->mandante = Auth::user()->mandante->load('tiposEntidadControlable');
        $this->entidadesControlables = $this->mandante->tiposEntidadControlable->pluck('nombre_entidad')->map(fn($nombre) => strtoupper($nombre))->toArray();
        $this->cargarDatosDesdeCache();
    }

    public function cargarDatosDesdeCache()
    {
        $this->calculandoEnVivo = false;
        $cacheKey = "supervision_mandante_{$this->mandante->id}";
        $data = Cache::get($cacheKey);
        $this->contratistasConPromedios = $data['promedios'] ?? [];
        $this->fechaCache = $data['fecha'] ?? 'No disponible (se requiere cálculo inicial)';
    }

    public function solicitarConfirmacionRecalculo()
    {
        $this->confirmingRecalculo = true;
    }

    public function cancelarRecalculo()
    {
        $this->confirmingRecalculo = false;
    }

    public function forzarRecalculoEnVivo()
    {
        $this->confirmingRecalculo = false;
        $this->calculandoEnVivo = true;
        $this->contratistasConPromedios = [];

        try {
            $mandante = $this->mandante;
            $mandante->load([
                'tiposEntidadControlable', 
                'contratistasPrincipalesAprobados.subContratistasAprobados'
            ]);
            $entidadesPermitidas = $mandante->tiposEntidadControlable->pluck('nombre_entidad')->map(fn($nombre) => strtoupper($nombre))->toArray();

            $todosLosContratistas = new Collection();
            foreach ($mandante->contratistasPrincipalesAprobados as $principal) {
                $todosLosContratistas->push($principal);
                foreach ($principal->subContratistasAprobados as $sub) {
                    $todosLosContratistas->push($sub);
                }
            }
            
            $resultados = [];
            foreach ($todosLosContratistas->unique('id') as $contratista) {
                $uosDelMandante = $contratista->unidadesOrganizacionalesMandante()->where('mandante_id', $mandante->id)->get();
                if ($uosDelMandante->isEmpty()) continue;
                
                $uoContextoId = $uosDelMandante->first()->id;
                $padre = $contratista->contratistaPadreAprobado()->where('solicitudes_vinculacion.mandante_id', $mandante->id)->first();

                $resultadoContratista = [
                    'id' => $contratista->id,
                    'razon_social' => $contratista->razon_social,
                    'rut' => $contratista->rut,
                    'contratista_padre_id' => $padre->id ?? null,
                ];

                if (in_array('EMPRESA', $entidadesPermitidas)) {
                    $resultadoContratista['cumplimiento_empresa'] = $contratista->calcularPorcentajeCumplimiento($mandante->id, $uoContextoId);
                }
                
                $entidadesHijas = [
                    'PERSONA' => ['modelo' => Trabajador::class, 'propiedad' => 'promedio_trabajadores', 'relacion' => 'trabajadores'],
                    'VEHICULO' => ['modelo' => Vehiculo::class, 'propiedad' => 'promedio_vehiculos', 'relacion' => 'vehiculos'],
                    'MAQUINARIA' => ['modelo' => Maquinaria::class, 'propiedad' => 'promedio_maquinarias', 'relacion' => 'maquinarias'],
                    'EMBARCACION' => ['modelo' => Embarcacion::class, 'propiedad' => 'promedio_embarcaciones', 'relacion' => 'embarcaciones'],
                ];

                foreach ($entidadesHijas as $nombreEntidad => $config) {
                    if (in_array($nombreEntidad, $entidadesPermitidas)) {
                        $entidades = $contratista->{$config['relacion']};
                        if ($entidades->isEmpty()) {
                            $resultadoContratista[$config['propiedad']] = ['promedio' => 100, 'total' => 0];
                        } else {
                            $totalPorcentaje = 0;
                            foreach ($entidades as $entidad) {
                                $totalPorcentaje += $entidad->calcularPorcentajeCumplimiento($mandante->id, $uoContextoId);
                            }
                            $resultadoContratista[$config['propiedad']] = ['promedio' => (int) round($totalPorcentaje / $entidades->count()), 'total' => $entidades->count()];
                        }
                    }
                }
                
                $resultados[$contratista->id] = $resultadoContratista;
            }

            $cacheKey = "supervision_mandante_{$mandante->id}";
            $fecha = now()->format('d-m-Y H:i:s');
            Cache::put($cacheKey, ['promedios' => $resultados, 'fecha' => $fecha], now()->addHours(1));

            $this->contratistasConPromedios = $resultados;
            $this->fechaCache = $fecha;
            $this->dispatch('notificacion-exito', 'Cálculo en vivo completado.');

        } catch (\Exception $e) {
            Log::error("Error en recálculo en vivo para mandante {$this->mandante->id}: " . $e->getMessage());
            $this->dispatch('notificacion-error', 'Ocurrió un error durante el recálculo.');
        } finally {
            $this->calculandoEnVivo = false;
        }
    }

    public function setSortBy($sortBy)
    {
        if ($this->sortBy === $sortBy) {
            $this->sortDir = ($this->sortDir === 'asc') ? 'desc' : 'asc';
        } else {
            $this->sortDir = 'desc';
        }
        $this->sortBy = $sortBy;
    }

    private function getDatosParaExportar()
    {
        $datosFiltrados = collect($this->contratistasConPromedios)->filter(function ($item) {
            if (empty($this->search)) return true;
            return str_contains(strtolower($item['razon_social']), strtolower($this->search)) ||
                   str_contains(str_replace(['.', '-'], '', $item['rut']), str_replace(['.', '-'], '', $this->search));
        });

        return $datosFiltrados->sortBy(function ($item) {
            if ($this->sortBy === 'cumplimiento_empresa') return $item['cumplimiento_empresa'] ?? 0;
            if (str_starts_with($this->sortBy, 'promedio_')) return $item[$this->sortBy]['promedio'] ?? 0;
            return $item[$this->sortBy];
        }, SORT_REGULAR, $this->sortDir === 'desc');
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

        if (in_array('excel', $this->formatosExportacion)) {
            $nombreArchivo = "reporte_supervision_{$timestamp}.xlsx";
            Excel::store(new SupervisionContratistasExport($data, $this->entidadesControlables), $nombreArchivo, 'local');
            $archivosGenerados['excel'] = ['nombre' => $nombreArchivo, 'ruta' => Storage::disk('local')->path($nombreArchivo)];
        }
        
        if (in_array('pdf', $this->formatosExportacion)) {
            $reporteService = new ReporteSupervisionService($data, $this->entidadesControlables);
            $viewData = $reporteService->generarDatosParaVista();
            
            $nombreArchivo = "reporte_ejecutivo_{$timestamp}.pdf";
            $pdf = Pdf::loadView('exports.reporte-ejecutivo', array_merge($viewData, [
                'mandanteNombre' => $this->mandante->razon_social,
                'filtros' => $this->search,
                'entidadesControlables' => $this->entidadesControlables,
            ]));
            Storage::disk('local')->put($nombreArchivo, $pdf->output());
            $archivosGenerados['pdf'] = ['nombre' => $nombreArchivo, 'ruta' => Storage::disk('local')->path($nombreArchivo)];
        }
        
        if (in_array('html', $this->formatosExportacion)) {
            $reporteService = new ReporteSupervisionService($data, $this->entidadesControlables);
            $viewData = $reporteService->generarDatosParaVista();

            $nombreArchivo = "reporte_ejecutivo_{$timestamp}.html";
            $html = view('exports.reporte-ejecutivo', array_merge($viewData, [
                'mandanteNombre' => $this->mandante->razon_social,
                'filtros' => $this->search,
                'entidadesControlables' => $this->entidadesControlables,
            ]))->render();
            Storage::disk('local')->put($nombreArchivo, $html);
            $archivosGenerados['html'] = ['nombre' => $nombreArchivo, 'ruta' => Storage::disk('local')->path($nombreArchivo)];
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
        $datosCompletos = collect($this->contratistasConPromedios);

        $datosFiltrados = $datosCompletos->filter(function ($item) use ($datosCompletos) {
            if (empty($this->search)) return true;
            
            $busquedaEnPadre = false;
            if (!empty($item['contratista_padre_id'])) {
                $padre = $datosCompletos->get($item['contratista_padre_id']);
                if ($padre) {
                    $busquedaEnPadre = str_contains(strtolower($padre['razon_social']), strtolower($this->search));
                }
            }

            return str_contains(strtolower($item['razon_social']), strtolower($this->search)) ||
                   str_contains(str_replace(['.', '-'], '', $item['rut']), str_replace(['.', '-'], '', $this->search)) ||
                   $busquedaEnPadre;
        });

        $principales = $datosFiltrados->whereNull('contratista_padre_id');
        $subcontratistas = $datosFiltrados->whereNotNull('contratista_padre_id');

        $principalesOrdenados = $principales->sortBy($this->sortBy, SORT_REGULAR, $this->sortDir === 'desc');

        $contratistasJerarquicos = new Collection();
        foreach ($principalesOrdenados as $principal) {
            $contratistasJerarquicos->push($principal);
            if ($subcontratistas->where('contratista_padre_id', $principal['id'])->isNotEmpty()) {
                foreach ($subcontratistas->where('contratista_padre_id', $principal['id'])->sortBy('razon_social') as $sub) {
                    $contratistasJerarquicos->push($sub);
                }
            }
        }

        $numPrincipales = $principales->count();
        $numSubcontratistas = $subcontratistas->count();
        $totalEmpresas = $contratistasJerarquicos->count();

        $totales = [
            'total_empresas_texto' => "{$numPrincipales} + {$numSubcontratistas} = {$totalEmpresas}",
            'total_trabajadores' => 0,
            'total_vehiculos' => 0,
            'total_maquinarias' => 0,
            'total_embarcaciones' => 0,
        ];

        foreach ($contratistasJerarquicos as $item) {
            $totales['total_trabajadores'] += $item['promedio_trabajadores']['total'] ?? 0;
            $totales['total_vehiculos'] += $item['promedio_vehiculos']['total'] ?? 0;
            $totales['total_maquinarias'] += $item['promedio_maquinarias']['total'] ?? 0;
            $totales['total_embarcaciones'] += $item['promedio_embarcaciones']['total'] ?? 0;
        }

        return view('livewire.mandante.panel-supervision', [
            'contratistasOrdenados' => $contratistasJerarquicos,
            'totales' => $totales,
        ])->layout('layouts.app');
    }
}