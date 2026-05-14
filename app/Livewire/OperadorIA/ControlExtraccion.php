<?php

namespace App\Livewire\OperadorIA;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CarpetaVerificacion;
use App\Models\Mandante;
use App\Models\Contratista;
use App\Models\RequisitoVerificacion;
use App\Models\DocumentoVerificacion;
use Illuminate\Support\Facades\DB;
use App\Traits\DescargaContextualTrait;

class ControlExtraccion extends Component
{
    use WithPagination, DescargaContextualTrait;

    // Filtros
    public $mandante_id = '';
    public $contratista_id = '';
    public $anio = '';
    public $mes = '';
    public $fecha_envio_desde = '';
    public $fecha_envio_hasta = '';
    public $filtro_id_registro = '';
    public $filtro_ia = ''; // IA_OK, IA_PENDIENTE
    public $estado_plazo = ''; // NORMAL, FUERA_PLAZO

    // Detalle
    public $carpeta_detalle_id = null;
    public $trabajadores_periodo = [];

    protected $queryString = [
        'mandante_id' => ['except' => ''],
        'contratista_id' => ['except' => ''],
        'anio' => ['except' => ''],
        'mes' => ['except' => ''],
        'fecha_envio_desde' => ['except' => ''],
        'fecha_envio_hasta' => ['except' => ''],
        'filtro_id_registro' => ['except' => ''],
        'filtro_ia' => ['except' => ''],
        'estado_plazo' => ['except' => ''],
    ];

    public function mount()
    {
        $this->anio = date('Y');
    }

    public function updatingMandanteId() { $this->resetPage(); $this->contratista_id = ''; }
    public function updatingContratistaId() { $this->resetPage(); }
    public function updatingAnio() { $this->resetPage(); }
    public function updatingMes() { $this->resetPage(); }
    public function updatingFiltroIdRegistro() { $this->resetPage(); }
    public function updatingFiltroIa() { $this->resetPage(); }
    public function updatingEstadoPlazo() { $this->resetPage(); }

    public function limpiarFiltros()
    {
        $this->reset(['mandante_id', 'contratista_id', 'mes', 'fecha_envio_desde', 'fecha_envio_hasta', 'filtro_id_registro', 'filtro_ia', 'estado_plazo']);
        $this->anio = date('Y');
        $this->resetPage();
    }

    public function toggleExtraido($carpetaId)
    {
        $carpeta = CarpetaVerificacion::find($carpetaId);
        if ($carpeta && $carpeta->estado === 'ENVIADO') {
            $carpeta->update([
                'ia_datos_extraidos' => !$carpeta->ia_datos_extraidos
            ]);
            $msg = $carpeta->ia_datos_extraidos ? 'Marcado como extraído.' : 'Extracción revertida.';
            $this->dispatch('notify', ['type' => 'success', 'message' => $msg]);
        }
    }

    public function verDetalle($carpetaId)
    {
        $this->carpeta_detalle_id = $carpetaId;
    }

    public function cerrarDetalle()
    {
        $this->carpeta_detalle_id = null;
        $this->trabajadores_periodo = [];
    }

    private function getNombreMes($mes)
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        return $meses[$mes] ?? '';
    }

    public function render()
    {
        $mandantes = Mandante::orderBy('razon_social')->get();
        $contratistas = collect();
        if ($this->mandante_id) {
            $contratistas = Contratista::whereHas('unidadesOrganizacionalesMandante', function ($q) {
                $q->where('mandante_id', $this->mandante_id);
            })->orderBy('razon_social')->get();
        }

        $query = CarpetaVerificacion::select('carpetas_verificacion.*', 'sv.contratista_padre_id')
            ->join('contratista_unidad_organizacional as cuo', 'cuo.id', '=', 'carpetas_verificacion.contratista_unidad_organizacional_id')
            ->leftJoin('unidades_organizacionales_mandante as uo', 'uo.id', '=', 'cuo.unidad_organizacional_mandante_id')
            ->leftJoin('dependencias as dep', 'dep.id', '=', 'cuo.dependencia_id')
            ->leftJoin('solicitudes_vinculacion as sv', function($join) {
                $join->on('sv.contratista_id', '=', 'cuo.contratista_id')
                     ->on('sv.mandante_id', '=', DB::raw('COALESCE(uo.mandante_id, dep.mandante_id)'))
                     ->where('sv.estado', '=', 'APROBADA')
                     ->where('sv.tipo_solicitud', '=', 'SUBCONTRATISTA');
            })
            ->with([
                'vinculacion.contratista',
                'vinculacion.unidadOrganizacional.mandante',
                'vinculacion.dependencia',
            ])
            ->where('carpetas_verificacion.estado', 'ENVIADO')
            ->has('vinculacion');

        if ($this->mandante_id) {
            $query->whereHas('vinculacion.unidadOrganizacional', fn($q) => $q->where('mandante_id', $this->mandante_id));
        }
        if ($this->contratista_id) {
            $query->whereHas('vinculacion', fn($q) => $q->where('contratista_id', $this->contratista_id));
        }
        if ($this->anio) {
            $query->where('carpetas_verificacion.anio', $this->anio);
        }
        if ($this->mes) {
            $query->where('carpetas_verificacion.mes', $this->mes);
        }
        if ($this->filtro_id_registro) {
            $query->where('cuo.id_registro', 'LIKE', '%' . $this->filtro_id_registro . '%');
        }
        if ($this->fecha_envio_desde) {
            $query->whereDate('carpetas_verificacion.fecha_envio', '>=', $this->fecha_envio_desde);
        }
        if ($this->fecha_envio_hasta) {
            $query->whereDate('carpetas_verificacion.fecha_envio', '<=', $this->fecha_envio_hasta);
        }

        if ($this->filtro_ia) {
            $query->where('carpetas_verificacion.ia_datos_extraidos', $this->filtro_ia === 'IA_OK');
        }
        if ($this->estado_plazo) { if ($this->estado_plazo === 'FUERA_PLAZO') { $query->whereIn('carpetas_verificacion.tipo_envio', ['FUERA_PLAZO', 'FUERA_PERIODO']); } else { $query->where('carpetas_verificacion.tipo_envio', $this->estado_plazo); } }

        $carpetasBase = $query->get();
        $carpetas = $this->ordenarJerarquicamente($carpetasBase);

        $perPage = 50;
        $currentPage = $this->getPage();
        $carpetas = new \Illuminate\Pagination\LengthAwarePaginator(
            $carpetas->forPage($currentPage, $perPage),
            $carpetas->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Support\Facades\Request::url(), 'query' => \Illuminate\Support\Facades\Request::query()]
        );

        $carpetaDetalle = null;
        $documentosPorRequisito = collect();
        $requisitosPorClasif = collect();

        if ($this->carpeta_detalle_id) {
            $carpetaDetalle = CarpetaVerificacion::with([
                'vinculacion.contratista',
                'vinculacion.unidadOrganizacional.mandante',
                'vinculacion.dependencia',
                'documentos.requisito.clasificacion',
            ])->find($this->carpeta_detalle_id);

            if ($carpetaDetalle) {
                $mandanteId = $carpetaDetalle->vinculacion->unidadOrganizacional->mandante_id ?? null;
                if ($mandanteId) {
                    $requisitosPorClasif = RequisitoVerificacion::where('mandante_id', $mandanteId)
                        ->where('is_active', true)
                        ->with('clasificacion')
                        ->orderBy('nombre')
                        ->get()
                        ->groupBy(fn ($r) => $r->clasificacion->nombre ?? 'Sin Clasificación');
                }
                $documentosPorRequisito = $carpetaDetalle->documentos->groupBy('requisito_verificacion_id');
            }
        }

        return view('livewire.operador-ia.control-extraccion', [
            'carpetas' => $carpetas,
            'mandantes' => $mandantes,
            'contratistas' => $contratistas,
            'carpetaDetalle' => $carpetaDetalle,
            'documentosPorRequisito' => $documentosPorRequisito,
            'requisitosPorClasif' => $requisitosPorClasif,
            'getNombreMes' => fn($m) => $this->getNombreMes($m),
        ])->layout('layouts.app');
    }

    protected function ordenarJerarquicamente($collection)
    {
        $gruposPorPeriodo = $collection->groupBy(fn($item) => $item->anio . '-' . str_pad($item->mes, 2, '0', STR_PAD_LEFT))->sortKeys();
        $resultadoFinal = collect();
        $contadorRaicesGlobal = 1;
        $aplanarArbol = function ($items, $prefijo = '') use (&$aplanarArbol, &$resultadoFinal, &$contadorRaicesGlobal) {
            $subContador = 1;
            foreach ($items as $item) {
                $item->correlativo_jerarquico = ($prefijo === '') ? (string)$contadorRaicesGlobal++ : "$prefijo.$subContador++";
                $resultadoFinal->push($item);
                if (isset($item->temporal_children) && $item->temporal_children->isNotEmpty()) {
                    $aplanarArbol($item->temporal_children, $item->correlativo_jerarquico);
                }
            }
        };
        foreach ($gruposPorPeriodo as $periodo => $carpetasDelPeriodo) {
            $byContratista = $carpetasDelPeriodo->groupBy(fn($c) => $c->vinculacion->contratista_id ?? 0);
            foreach ($carpetasDelPeriodo as $item) { $item->temporal_children = collect(); $item->is_attached_to_parent = false; }
            foreach ($carpetasDelPeriodo as $child) {
                if (empty($child->contratista_padre_id)) continue;
                $candidatos = $byContratista->get($child->contratista_padre_id);
                if (!$candidatos) continue; $bestPadre = null; $bestScore = -1;
                foreach ($candidatos as $padre) {
                    $score = 0; $vP = $padre->vinculacion; $vC = $child->vinculacion;
                    if (!$vP || !$vC) continue;
                    if ($vP->unidad_organizacional_mandante_id == $vC->unidad_organizacional_mandante_id) $score += 10;
                    if ($vP->dependencia_id == $vC->dependencia_id) $score += 10;
                    if ($vC->numero_contrato && $vP->numero_contrato) $score += ($vC->numero_contrato == $vP->numero_contrato) ? 50 : -10;
                    if ($score > $bestScore) { $bestScore = $score; $bestPadre = $padre; }
                }
                if ($bestPadre && $bestScore > 0) { $bestPadre->temporal_children->push($child); $child->is_attached_to_parent = true; }
            }
            $raicesDelPeriodo = $carpetasDelPeriodo->filter(fn($item) => !$item->is_attached_to_parent)->sortBy(fn($item) => $item->vinculacion->contratista->razon_social ?? '');
            $aplanarArbol($raicesDelPeriodo);
        }
        return $resultadoFinal;
    }

    public function descargarDocumentosFiltrados()
    {
        $query = CarpetaVerificacion::select('carpetas_verificacion.*')
            ->join('contratista_unidad_organizacional as cuo', 'cuo.id', '=', 'carpetas_verificacion.contratista_unidad_organizacional_id')
            ->leftJoin('unidades_organizacionales_mandante as uo', 'uo.id', '=', 'cuo.unidad_organizacional_mandante_id')
            ->leftJoin('dependencias as dep', 'dep.id', '=', 'cuo.dependencia_id')
            ->where('carpetas_verificacion.estado', 'ENVIADO')
            ->has('vinculacion');

        if ($this->mandante_id) {
            $query->whereHas('vinculacion.unidadOrganizacional', fn($q) => $q->where('mandante_id', $this->mandante_id));
        }
        if ($this->contratista_id) {
            $query->whereHas('vinculacion', fn($q) => $q->where('contratista_id', $this->contratista_id));
        }
        if ($this->anio) {
            $query->where('carpetas_verificacion.anio', $this->anio);
        }
        if ($this->mes) {
            $query->where('carpetas_verificacion.mes', $this->mes);
        }
        if ($this->filtro_id_registro) {
            $query->where('cuo.id_registro', 'LIKE', '%' . $this->filtro_id_registro . '%');
        }
        if ($this->fecha_envio_desde) {
            $query->whereDate('carpetas_verificacion.fecha_envio', '>=', $this->fecha_envio_desde);
        }
        if ($this->fecha_envio_hasta) {
            $query->whereDate('carpetas_verificacion.fecha_envio', '<=', $this->fecha_envio_hasta);
        }

        if ($this->filtro_ia) {
            $query->where('carpetas_verificacion.ia_datos_extraidos', $this->filtro_ia === 'IA_OK');
        }
        if ($this->estado_plazo) { if ($this->estado_plazo === 'FUERA_PLAZO') { $query->whereIn('carpetas_verificacion.tipo_envio', ['FUERA_PLAZO', 'FUERA_PERIODO']); } else { $query->where('carpetas_verificacion.tipo_envio', $this->estado_plazo); } }

        $carpetasId = $query->pluck('carpetas_verificacion.id')->toArray();

        \App\Services\AuditService::log('ia.extraccion', "Descargó Masivamente Documentos para Extracción IA (" . count($carpetasId) . " carpetas)");

        return $this->procesarDescargaContextual($carpetasId, "Extraccion_IA");
    }
}
