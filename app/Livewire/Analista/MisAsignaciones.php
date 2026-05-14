<?php

namespace App\Livewire\Analista;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CarpetaVerificacion;
use App\Models\RequisitoVerificacion;
use App\Models\Mandante;
use App\Models\Contratista;
use App\Models\CarpetaVerificacionTrabajador;
use App\Exports\DotacionPeriodoExport;
use Maatwebsite\Excel\Facades\Excel;

use Livewire\Attributes\Title;

#[Title('ANALIZAR PERIODOS')]
class MisAsignaciones extends Component
{
    use WithPagination;

    // Filtros
    public $mandante_id = '';
    public $contratista_id = '';
    public $estado_revision = '';
    public $anio = '';
    public $mes = '';
    public $estado_plazo = ''; // NORMAL, FUERA_PLAZO

    // Modal VER DOCS
    public $carpeta_detalle_id = null;
    public $showModalDocs = false;

    // Modal FINALIZAR
    public $showModalFinalizar = false;
    public $carpeta_finalizar_id = null;

    // Campos del formulario de cierre (Pre-cierre)
    public $fin_contratados_periodo = 0;
    public $fin_desvinculados_periodo = 0;
    public $fin_total_vigentes = 0;
    public $fin_trabajadores_revisados = 0;
    public $fin_remuneraciones_pagadas = 0;
    public $fin_cotizaciones_pagadas = 0;
    public $fin_aviso_previo_trabajadores = 0;
    public $fin_aviso_previo_total = 0;
    public $fin_anio_servicio_trabajadores = 0;
    public $fin_anio_servicio_total = 0;
    public $fin_feriado_trabajadores = 0;
    public $fin_feriado_total = 0;
    public $fin_liquido_total = 0;
    public $fin_doy_finalizado = false;

    protected $queryString = [
        'mandante_id' => ['except' => ''],
        'contratista_id' => ['except' => ''],
        'estado_revision' => ['except' => ''],
        'anio' => ['except' => ''],
        'mes' => ['except' => ''],
        'estado_plazo' => ['except' => ''],
    ];

    public function mount()
    {
        $this->anio = date('Y');
    }

    public function updatingMandanteId()
    {
        $this->contratista_id = '';
        $this->resetPage();
    }
    public function updatingContratistaId()
    {
        $this->resetPage();
    }
    public function updatingEstadoRevision()
    {
        $this->resetPage();
    }
    public function updatingAnio()
    {
        $this->resetPage();
    }
    public function updatingMes()
    {
        $this->resetPage();
    }
    public function updatingEstadoPlazo()
    {
        $this->resetPage();
    }

    public function verDetalle($carpetaId)
    {
        $this->carpeta_detalle_id = $carpetaId;
        $this->showModalDocs = true;

        // Marcar como EN_REVISION si aún está ASIGNADO
        $carpeta = CarpetaVerificacion::find($carpetaId);
        if ($carpeta && $carpeta->estado_revision === 'ASIGNADO') {
            $carpeta->update([
                'estado_revision' => 'EN_CARGA',
                'fecha_inicio_revision' => now(),
            ]);
        }
    }

    public function cerrarDetalle()
    {
        $this->carpeta_detalle_id = null;
        $this->showModalDocs = false;
    }

    // Alias para el modal de docs
    public function abrirModalDocs($carpetaId)
    {
        $this->verDetalle($carpetaId);
    }

    public function cerrarModalDocs()
    {
        $this->cerrarDetalle();
    }

    public function abrirModalFinalizar($carpetaId)
    {
        $carpeta = CarpetaVerificacion::with('vinculacion')->find($carpetaId);
        if (!$carpeta)
            return;

        $this->carpeta_finalizar_id = $carpetaId;
        $this->showModalFinalizar = true;

        // Cargar datos existentes o inicializar
        $this->fin_trabajadores_revisados = $carpeta->fin_trabajadores_revisados ?? 0;
        $this->fin_remuneraciones_pagadas = $carpeta->fin_remuneraciones_pagadas ?? 0;
        $this->fin_cotizaciones_pagadas = $carpeta->fin_cotizaciones_pagadas ?? 0;
        $this->fin_aviso_previo_trabajadores = $carpeta->fin_aviso_previo_trabajadores ?? 0;
        $this->fin_aviso_previo_total = $carpeta->fin_aviso_previo_total ?? 0;
        $this->fin_anio_servicio_trabajadores = $carpeta->fin_anio_servicio_trabajadores ?? 0;
        $this->fin_anio_servicio_total = $carpeta->fin_anio_servicio_total ?? 0;
        $this->fin_feriado_trabajadores = $carpeta->fin_feriado_trabajadores ?? 0;
        $this->fin_feriado_total = $carpeta->fin_feriado_total ?? 0;
        $this->fin_liquido_total = $carpeta->fin_liquido_total ?? 0;
        $this->fin_doy_finalizado = $carpeta->fin_doy_finalizado ?? false;

        // Lógica de Situación de Trabajadores (Cálculo Automático)
        $this->calcularSituacionTrabajadores($carpeta);
    }

    protected function calcularSituacionTrabajadores($carpeta)
    {
        $v = $carpeta->vinculacion;
        if (!$v)
            return;

        // Asegurar que la nómina esté inicializada antes de leer
        if ($carpeta->trabajadoresVerificados()->count() === 0) {
            $this->inicializarNominaVerificada($carpeta);
        }

        // Cargar la nómina del período con la vinculación para leer fecha_ingreso
        $nomina = $carpeta->trabajadoresVerificados()
            ->with('vinculacion')
            ->get();

        // 1. Contratados en el período = trabajadores de la nómina con fecha_ingreso en este período
        $this->fin_contratados_periodo = $nomina->filter(function ($tv) use ($carpeta) {
            $fecha = $tv->vinculacion->fecha_ingreso_vinculacion ?? null;
            if (!$fecha)
                return false;
            $f = \Carbon\Carbon::parse($fecha);
            return $f->year == $carpeta->anio && $f->month == $carpeta->mes;
        })->count();

        // 2. Desvinculados = Trabajadores clasificados explícitamente, 
        //    O aquellos que en la realidad (BD) fueron finiquitados/desactivados en este mes.
        $this->fin_desvinculados_periodo = $nomina->filter(function ($tv) use ($carpeta) {
            // Si el analista ya lo revisó y clasificó
            if (in_array($tv->estado_revision, ['FINIQUITADO', 'MOVIDO', 'BAJA_MANDANTE'])) {
                return true;
            }

            // Si aún está PENDIENTE (el analista no ha abierto VER DOCS), inferimos de la DB real.
            if ($tv->vinculacion) {
                // Revisar fecha_finiquito
                $ff = $tv->vinculacion->fecha_finiquito ? \Carbon\Carbon::parse($tv->vinculacion->fecha_finiquito) : null;
                if ($ff && $ff->year == $carpeta->anio && $ff->month == $carpeta->mes) {
                    return true;
                }
                // Revisar fecha_desactivacion
                $fd = $tv->vinculacion->fecha_desactivacion ? \Carbon\Carbon::parse($tv->vinculacion->fecha_desactivacion) : null;
                if ($fd && $fd->year == $carpeta->anio && $fd->month == $carpeta->mes) {
                    return true;
                }
            }

            return false;
        })->count();

        // 3. Total Vigentes (Dotación) = Trabajadores totales que participan en la nómina del período.
        //    Esto devuelve la cantidad exacta de filas en la nómina del analista (1 en lugar de 3).
        $this->fin_total_vigentes = $nomina->count();
    }

    public function finalizarRevision()
    {
        $rules = [
            'fin_trabajadores_revisados' => 'required|numeric|min:0',
            'fin_remuneraciones_pagadas' => 'nullable|numeric|min:0',
            'fin_cotizaciones_pagadas' => 'nullable|numeric|min:0',
        ];

        if ($this->fin_doy_finalizado) {
            $rules['fin_trabajadores_revisados'] = 'required|numeric|min:1';
        }

        $this->validate($rules, [
            'fin_trabajadores_revisados.min' => 'Debe ingresar al menos 1 trabajador revisado para finalizar el informe.'
        ]);

        $carpeta = CarpetaVerificacion::find($this->carpeta_finalizar_id);
        if (!$carpeta)
            return;

        if (in_array($carpeta->estado_revision, ['REVISADO', 'PARA_EMITIR', 'EMITIDO'])) {
            session()->flash('error', 'El informe ya fue finalizado y no puede modificarse.');
            return;
        }

        $data = [
            'fin_contratados_periodo' => $this->fin_contratados_periodo,
            'fin_desvinculados_periodo' => $this->fin_desvinculados_periodo,
            'fin_total_vigentes' => $this->fin_total_vigentes,
            'fin_trabajadores_revisados' => $this->fin_trabajadores_revisados,
            'fin_remuneraciones_pagadas' => $this->fin_remuneraciones_pagadas,
            'fin_cotizaciones_pagadas' => $this->fin_cotizaciones_pagadas,
            'fin_liquido_total' => $this->fin_liquido_total,
            'fin_aviso_previo_trabajadores' => $this->fin_aviso_previo_trabajadores,
            'fin_aviso_previo_total' => $this->fin_aviso_previo_total,
            'fin_anio_servicio_trabajadores' => $this->fin_anio_servicio_trabajadores,
            'fin_anio_servicio_total' => $this->fin_anio_servicio_total,
            'fin_feriado_trabajadores' => $this->fin_feriado_trabajadores,
            'fin_feriado_total' => $this->fin_feriado_total,
            'fin_doy_finalizado' => $this->fin_doy_finalizado,
        ];

        if ($this->fin_doy_finalizado) {
            $data['estado_revision'] = 'REVISADO';
            $data['fecha_fin_revision'] = now();
        }

        $carpeta->update($data);

        $this->cerrarModalFinalizar();
        $this->dispatch('periodo-finalizado');
        session()->flash('success', $this->fin_doy_finalizado ? 'Periodo finalizado y enviado a auditoría.' : 'Información de pre-cierre guardada como borrador.');
    }

    public function cerrarModalFinalizar()
    {
        $this->showModalFinalizar = false;
        $this->carpeta_finalizar_id = null;
    }

    public function marcarRevisado($carpetaId)
    {
        $carpeta = CarpetaVerificacion::find($carpetaId);
        if ($carpeta) {
            $carpeta->update([
                'estado_revision' => 'REVISADO',
                'fecha_fin_revision' => now(),
            ]);
            $this->carpeta_detalle_id = null;
            session()->flash('success', 'Periodo marcado como revisado correctamente.');
        }
    }

    public function guardarObservacion($carpetaId, $observacion)
    {
        $carpeta = CarpetaVerificacion::find($carpetaId);
        if ($carpeta) {
            if (in_array($carpeta->estado_revision, ['REVISADO', 'PARA_EMITIR', 'EMITIDO'])) {
                session()->flash('error', 'No se puede modificar la observación de un periodo finalizado.');
                return;
            }
            $carpeta->update(['observaciones_analista' => $observacion]);
            session()->flash('success', 'Observación guardada.');
        }
    }

    public function limpiarFiltros()
    {
        $this->reset(['mandante_id', 'contratista_id', 'mes', 'estado_revision']);
        $this->anio = date('Y');
        $this->resetPage();
    }

    public function render()
    {
        $userId = auth()->id();

        // Listas para filtros
        $mandantes = Mandante::where('is_active', true)->orderBy('razon_social')->get();

        $contratistas = collect();
        if ($this->mandante_id) {
            $contratistas = Contratista::whereHas('unidadesOrganizacionalesMandante', function ($q) {
                $q->where('mandante_id', $this->mandante_id);
            })->orderBy('razon_social')->get();
        }

        // Query de mis asignaciones
        $query = CarpetaVerificacion::select('carpetas_verificacion.*', 'sv.contratista_padre_id')
            ->join('contratista_unidad_organizacional as cuo', 'cuo.id', '=', 'carpetas_verificacion.contratista_unidad_organizacional_id')
            ->leftJoin('unidades_organizacionales_mandante as uo', 'uo.id', '=', 'cuo.unidad_organizacional_mandante_id')
            ->leftJoin('dependencias as dep', 'dep.id', '=', 'cuo.dependencia_id')
            ->leftJoin('solicitudes_vinculacion as sv', function ($join) {
                $join->on('sv.contratista_id', '=', 'cuo.contratista_id')
                    ->on('sv.mandante_id', '=', \Illuminate\Support\Facades\DB::raw('COALESCE(uo.mandante_id, dep.mandante_id)'))
                    ->where('sv.estado', '=', 'APROBADA')
                    ->where('sv.tipo_solicitud', '=', 'SUBCONTRATISTA');
            })
            ->with([
                'vinculacion.contratista',
                'vinculacion.unidadOrganizacional.mandante',
                'vinculacion.dependencia',
                'supervisor',
            ])->when(!auth()->user()->hasAnyRole(['ASEM_Admin', 'OVAL_Admin']), function ($q) use ($userId) {
                return $q->where('carpetas_verificacion.analista_id', $userId);
            });

        if ($this->mandante_id) {
            $query->whereHas(
                'vinculacion.unidadOrganizacional',
                fn($q) =>
                $q->where('mandante_id', $this->mandante_id)
            );
        }
        if ($this->contratista_id) {
            $query->whereHas(
                'vinculacion',
                fn($q) =>
                $q->where('contratista_id', $this->contratista_id)
            );
        }
        if ($this->anio) {
            $query->where('carpetas_verificacion.anio', $this->anio);
        }
        if ($this->mes) {
            $query->where('carpetas_verificacion.mes', $this->mes);
        }
        if ($this->estado_revision) {
            if ($this->estado_revision === 'FINALIZADO') {
                $query->whereIn('carpetas_verificacion.estado_revision', ['REVISADO', 'PARA_EMITIR', 'EMITIDO']);
            } elseif ($this->estado_revision === 'PROCESO') {
                $query->where('carpetas_verificacion.estado_revision', 'EN_CARGA');
            } elseif ($this->estado_revision === 'DEVUELTO') {
                $query->where('carpetas_verificacion.estado_revision', 'EN_REVISION');
            } else {
                $query->where('carpetas_verificacion.estado_revision', $this->estado_revision);
            }
        }
        if ($this->estado_plazo) {
            if ($this->estado_plazo === 'FUERA_PLAZO') {
                $query->whereIn('carpetas_verificacion.tipo_envio', ['FUERA_PLAZO', 'FUERA_PERIODO']);
            } else {
                $query->where('carpetas_verificacion.tipo_envio', $this->estado_plazo);
            }
        }

        // Obtener carpetas base para ordenamiento
        $carpetasBase = $query->get();

        // Aplicar ordenamiento jerárquico SIEMPRE
        $carpetas = $this->ordenarJerarquicamente($carpetasBase);

        // Paginación manual para colecciones jerárquicas
        $perPage = 50;
        $currentPage = $this->getPage();
        $carpetas = new \Illuminate\Pagination\LengthAwarePaginator(
            $carpetas->forPage($currentPage, $perPage),
            $carpetas->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Support\Facades\Request::url(), 'query' => \Illuminate\Support\Facades\Request::query()]
        );

        // Contadores
        $isAdmin = auth()->user()->hasAnyRole(['ASEM_Admin', 'OVAL_Admin']);

        $totalAsignados = CarpetaVerificacion::where('estado_revision', 'ASIGNADO')
            ->when(!$isAdmin, fn($q) => $q->where('analista_id', $userId))
            ->count();

        $totalEnRevision = CarpetaVerificacion::where('estado_revision', 'EN_CARGA')
            ->when(!$isAdmin, fn($q) => $q->where('analista_id', $userId))
            ->count();

        $totalDevueltos = CarpetaVerificacion::where('estado_revision', 'EN_REVISION')
            ->when(!$isAdmin, fn($q) => $q->where('analista_id', $userId))
            ->count();

        $totalRevisados = CarpetaVerificacion::whereIn('estado_revision', ['REVISADO', 'PARA_EMITIR', 'EMITIDO'])
            ->when(!$isAdmin, fn($q) => $q->where('analista_id', $userId))
            ->count();

        // Detalle de carpeta seleccionada
        $carpetaDetalle = null;
        $requisitosPorClasif = collect();
        $documentosPorRequisito = collect();

        if ($this->carpeta_detalle_id) {
            $carpetaDetalle = CarpetaVerificacion::with([
                'vinculacion.contratista',
                'vinculacion.unidadOrganizacional.mandante',
                'vinculacion.dependencia',
                'documentos.requisito.clasificacion',
                'analista',
                'supervisor',
                'auditor',
            ])->find($this->carpeta_detalle_id);

            if ($carpetaDetalle) {
                // Verificar si ya existe nómina verificada persistida
                if ($carpetaDetalle->trabajadoresVerificados()->count() === 0) {
                    $this->inicializarNominaVerificada($carpetaDetalle);
                }

                // Cargar la nómina verificada con sus relaciones
                $trabajadoresPeriodo = $carpetaDetalle->trabajadoresVerificados()
                    ->with(['vinculacion.trabajador', 'vinculacion.cargoMandante', 'destinoVinculacion.unidadOrganizacional'])
                    ->get();

                $mandanteId = $carpetaDetalle->vinculacion->unidadOrganizacional->mandante_id ?? null;

                // Todos los requisitos activos del mandante, agrupados por clasificación
                if ($mandanteId) {
                    $requisitosPorClasif = RequisitoVerificacion::where('mandante_id', $mandanteId)
                        ->where('is_active', true)
                        ->with('clasificacion')
                        ->orderBy('nombre')
                        ->get()
                        ->groupBy(fn($r) => $r->clasificacion->nombre ?? 'Sin Clasificación');
                }

                // Documentos cargados: indexados por requisito_verificacion_id para lookup O(1)
                $documentosPorRequisito = $carpetaDetalle->documentos->keyBy('requisito_verificacion_id');
            }
        }

        return view('livewire.analista.mis-asignaciones', [
            'carpetas' => $carpetas,
            'mandantes' => $mandantes,
            'contratistas' => $contratistas,
            'totalAsignados' => $totalAsignados,
            'totalEnRevision' => $totalEnRevision,
            'totalDevueltos' => $totalDevueltos,
            'totalRevisados' => $totalRevisados,
            'carpetaDetalle' => $carpetaDetalle,
            'trabajadoresPeriodo' => $trabajadoresPeriodo ?? collect(),
            'requisitosPorClasif' => $requisitosPorClasif,
            'documentosPorRequisito' => $documentosPorRequisito,
        ])->layout('layouts.app');
    }

    public function inicializarNominaVerificada($carpeta)
    {
        // 1. Obtener dotación actual (VIGENTE) según filtros históricos
        $pStart = \Carbon\Carbon::create($carpeta->anio, $carpeta->mes, 1)->startOfMonth();
        $pEnd = $pStart->copy()->endOfMonth();

        $vigentes = \App\Models\TrabajadorVinculacion::where('unidad_organizacional_mandante_id', $carpeta->vinculacion->unidad_organizacional_mandante_id)
            ->where('dependencia_id', $carpeta->vinculacion->dependencia_id)
            ->where(function ($q) use ($carpeta) {
                if ($carpeta->vinculacion->numero_contrato) {
                    $q->where('numero_contrato', $carpeta->vinculacion->numero_contrato);
                }
            })
            ->whereHas('trabajador', function ($q) use ($carpeta) {
                $q->where('contratista_id', $carpeta->vinculacion->contratista_id);
            })
            ->where('fecha_ingreso_vinculacion', '<=', $pEnd)
            ->where(function ($sq) use ($pStart) {
                $sq->whereNull('fecha_desactivacion')
                    ->orWhere('fecha_desactivacion', '>=', $pStart);
            })
            ->get();

        foreach ($vigentes as $v) {
            $carpeta->trabajadoresVerificados()->create([
                'trabajador_vinculacion_id' => $v->id,
                'tipo_registro' => 'VIGENTE',
                'estado_revision' => 'PENDIENTE'
            ]);
        }

        // 2. Buscar último periodo verificado (AUDITADO o REVISADO)
        $ultimaCarpeta = \App\Models\CarpetaVerificacion::where('contratista_unidad_organizacional_id', $carpeta->contratista_unidad_organizacional_id)
            ->where('id', '!=', $carpeta->id)
            ->where(function ($q) use ($carpeta) {
                $q->where('anio', '<', $carpeta->anio)
                    ->orWhere(function ($sq) use ($carpeta) {
                        $sq->where('anio', $carpeta->anio)
                            ->where('mes', '<', $carpeta->mes);
                    });
            })
            ->whereIn('estado_revision', ['REVISADO', 'AUDITADO'])
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->with('trabajadoresVerificados')
            ->first();

        if ($ultimaCarpeta) {
            // Trabajadores de la última nómina que NO fueron finiquitados o movidos
            $baseAnterior = $ultimaCarpeta->trabajadoresVerificados()
                ->whereNotIn('estado_revision', ['FINIQUITADO', 'MOVIDO'])
                ->get();

            foreach ($baseAnterior as $tAnt) {
                // Si no está en los vigentes actuales, es ARRASTRE
                if (!$vigentes->contains('id', $tAnt->trabajador_vinculacion_id)) {
                    $carpeta->trabajadoresVerificados()->create([
                        'trabajador_vinculacion_id' => $tAnt->trabajador_vinculacion_id,
                        'tipo_registro' => 'ARRASTRE',
                        'estado_revision' => 'PENDIENTE'
                    ]);
                }
            }
        }
    }

    public function cambiarEstadoTrabajadorPeriodo($id, $nuevoEstado, $destinoId = null)
    {
        $reg = \App\Models\CarpetaVerificacionTrabajador::with('carpetaVerificacion')->find($id);
        if ($reg) {
            if ($reg->carpetaVerificacion && in_array($reg->carpetaVerificacion->estado_revision, ['REVISADO', 'PARA_EMITIR', 'EMITIDO'])) {
                $this->dispatch('notify', ['type' => 'error', 'message' => 'No se puede modificar un trabajador de un periodo finalizado.']);
                return;
            }
            $reg->update([
                'estado_revision' => $nuevoEstado,
                'destino_trabajador_vinculacion_id' => ($nuevoEstado === 'MOVIDO') ? $destinoId : null
            ]);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Estado del trabajador actualizado.']);
        }
    }

    public function exportarDotacion($carpetaId)
    {
        $carpeta = CarpetaVerificacion::find($carpetaId);
        if (!$carpeta)
            return;

        $nombreArchivo = 'dotacion_' . $carpeta->vinculacion->contratista->rut . '_' . $carpeta->nombre_mes . '_' . $carpeta->anio . '.xlsx';

        return Excel::download(new DotacionPeriodoExport($carpetaId), $nombreArchivo);
    }

    public function getDestinosPosibles($trabajadorVinculacionId)
    {
        $vinculacionOrigen = \App\Models\TrabajadorVinculacion::find($trabajadorVinculacionId);
        if (!$vinculacionOrigen)
            return collect();

        // Buscar otras vinculaciones activas del mismo trabajador para el mismo contratista
        return \App\Models\TrabajadorVinculacion::with(['unidadOrganizacional', 'dependencia'])
            ->where('trabajador_id', $vinculacionOrigen->trabajador_id)
            ->where('id', '!=', $trabajadorVinculacionId)
            ->where('is_active', true)
            ->get();
    }

    protected function ordenarJerarquicamente($collection)
    {
        // 1. Agrupar por Periodo (Año-Mes)
        $gruposPorPeriodo = $collection->groupBy(
            fn($item) =>
            $item->anio . '-' . str_pad($item->mes, 2, '0', STR_PAD_LEFT)
        )->sortKeys();

        $resultadoFinal = collect();
        $contadorRaicesGlobal = 1;

        // Función recursiva para aplanar
        $aplanarArbol = function ($items, $prefijo = '') use (&$aplanarArbol, &$resultadoFinal, &$contadorRaicesGlobal) {
            $subContador = 1;
            foreach ($items as $item) {
                if ($prefijo === '') {
                    $item->correlativo_jerarquico = (string) $contadorRaicesGlobal;
                    $contadorRaicesGlobal++;
                } else {
                    $item->correlativo_jerarquico = "$prefijo.$subContador";
                    $subContador++;
                }
                $resultadoFinal->push($item);
                if (isset($item->temporal_children) && $item->temporal_children->isNotEmpty()) {
                    $aplanarArbol($item->temporal_children, $item->correlativo_jerarquico);
                }
            }
        };

        foreach ($gruposPorPeriodo as $periodo => $carpetasDelPeriodo) {
            // Mapa de carpetas por Contratista ID para búsqueda flexible (Lógica SKILL)
            $byContratista = $carpetasDelPeriodo->groupBy(fn($c) => $c->vinculacion->contratista_id ?? 0);

            foreach ($carpetasDelPeriodo as $item) {
                $item->temporal_children = collect();
                $item->is_attached_to_parent = false;
            }

            // Construir árbol del periodo usando Lógica de la SKILL (Emparejamiento Flexible)
            foreach ($carpetasDelPeriodo as $child) {
                if (empty($child->contratista_padre_id))
                    continue;

                $candidatos = $byContratista->get($child->contratista_padre_id);
                if (!$candidatos || $candidatos->isEmpty())
                    continue;

                $bestPadre = null;
                $bestScore = -1;

                foreach ($candidatos as $padre) {
                    $score = 0;
                    $vP = $padre->vinculacion;
                    $vC = $child->vinculacion;
                    if (!$vP || !$vC)
                        continue;

                    // Coincidencia de UO
                    if ($vP->unidad_organizacional_mandante_id == $vC->unidad_organizacional_mandante_id)
                        $score += 10;
                    elseif ($vP->unidad_organizacional_mandante_id && $vC->unidad_organizacional_mandante_id)
                        $score -= 50;

                    // Coincidencia de Lugar (Dependencia)
                    if ($vP->dependencia_id == $vC->dependencia_id)
                        $score += 10;
                    elseif ($vP->dependencia_id && $vC->dependencia_id)
                        $score -= 20;

                    // Coincidencia de Contrato
                    if ($vC->numero_contrato && $vP->numero_contrato) {
                        $score += ($vC->numero_contrato == $vP->numero_contrato) ? 50 : -100;
                    }

                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestPadre = $padre;
                    }
                }

                if ($bestPadre && $bestScore > -50) {
                    $bestPadre->temporal_children->push($child);
                    $child->is_attached_to_parent = true;
                }
            }

            // Aplanar raíces de este periodo
            $raicesDelPeriodo = $carpetasDelPeriodo->filter(fn($item) => !$item->is_attached_to_parent);
            $raicesDelPeriodo = $raicesDelPeriodo->sortBy(fn($item) => $item->vinculacion->contratista->razon_social ?? '');
            $aplanarArbol($raicesDelPeriodo);
        }

        return $resultadoFinal;
    }
}
