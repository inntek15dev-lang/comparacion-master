<?php

namespace App\Livewire\Mandante;

use App\Models\ContratistaUnidadOrganizacional;
use App\Models\CarpetaVerificacion;
use App\Models\CalendarioVerificacion;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Verificacion extends Component
{
    public $anio_seleccionado;
    public $mes_seleccionado;
    public $contratistas_carpetas = [];
    public $inicio_global = null;
    public bool $esSoloLectura = false;

    public function mount()
    {
        $user = Auth::user();
        if ($user && $user->hasAnyRole(['Mandante_Admin', 'Mandante_Ver'])) {
            $this->esSoloLectura = $user->hasRole('Mandante_Ver');
        }

        $this->anio_seleccionado = date('Y');
        $this->mes_seleccionado = date('n'); // Mes actual numérico
        $this->cargarCarpetas();
    }

    public function cargarCarpetas()
    {
        $user = Auth::user();
        $mandanteId = $user->mandante_id;

        if (!$mandanteId) {
            $this->contratistas_carpetas = [];
            return;
        }

        // Buscar inicio global del mandante
        $registroInicio = CalendarioVerificacion::where('mandante_id', $mandanteId)
            ->where('is_inicio', true)
            ->first();
        
        $this->inicio_global = $registroInicio ? [
            'mes' => $registroInicio->nombre_mes,
            'anio' => $registroInicio->anio,
            'periodo' => \Carbon\Carbon::create($registroInicio->anio, $registroInicio->mes, 1)->subMonth()->translatedFormat('F Y')
        ] : null;

        // 1. Obtener vinculaciones verificables con el contratista_padre_id
        $vinculacionesBase = ContratistaUnidadOrganizacional::select('contratista_unidad_organizacional.*', 'sv.contratista_padre_id')
            ->leftJoin('solicitudes_vinculacion as sv', function($join) use ($mandanteId) {
                $join->on('sv.contratista_id', '=', 'contratista_unidad_organizacional.contratista_id')
                     ->where('sv.mandante_id', '=', (int)$mandanteId)
                     ->where('sv.estado', '=', 'APROBADA')
                     ->where('sv.tipo_solicitud', '=', 'SUBCONTRATISTA');
            })
            ->where('contratista_unidad_organizacional.verifica', true)
            ->whereHas('unidadOrganizacionalMandante', function($q) use ($mandanteId) {
                $q->where('mandante_id', $mandanteId);
            })
            ->with(['contratista', 'unidadOrganizacionalMandante', 'dependencia'])
            ->get();

        // 2. Ordenar vinculaciones jerárquicamente de forma flexible (Lógica SKILL)
        $vinculacionesJerarquicas = $this->ordenarJerarquicamente($vinculacionesBase);

        $this->contratistas_carpetas = [];

        foreach ($vinculacionesJerarquicas as $v) {
            $nominaDate = \Carbon\Carbon::create($this->anio_seleccionado, $this->mes_seleccionado, 1)->subMonth();
            
            // Buscar carpeta para el periodo de NOMINA
            $carpeta = CarpetaVerificacion::where('contratista_unidad_organizacional_id', $v->id)
                ->where('anio', $nominaDate->year)
                ->where('mes', $nominaDate->month)
                ->with('documentos.requisito')
                ->first();

            $this->contratistas_carpetas[] = [
                'vinculacion' => $v,
                'carpeta' => $carpeta,
                'documentos' => $carpeta ? $carpeta->documentos : [],
                'correlativo_jerarquico' => $v->correlativo_jerarquico 
            ];
        }
    }

    protected function ordenarJerarquicamente($collection)
    {
        $resultadoFinal = collect();
        $contadorRaicesGlobal = 1;

        $aplanarArbol = function ($items, $prefijo = '') use (&$aplanarArbol, &$resultadoFinal, &$contadorRaicesGlobal) {
            $subContador = 1;
            foreach ($items as $item) {
                if ($prefijo === '') {
                    $item->correlativo_jerarquico = (string)$contadorRaicesGlobal;
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

        foreach ($collection as $item) {
            $item->temporal_children = collect();
            $item->is_attached_to_parent = false;
        }

        $byContratista = $collection->groupBy(fn($c) => $c->contratista_id ?? 0);

        foreach ($collection as $child) {
            if (empty($child->contratista_padre_id)) continue;

            $candidatos = $byContratista->get($child->contratista_padre_id);
            if (!$candidatos || $candidatos->isEmpty()) continue;

            $bestPadre = null;
            $bestScore = -1;

            foreach ($candidatos as $padre) {
                $score = 0;
                if ($padre->unidad_organizacional_mandante_id == $child->unidad_organizacional_mandante_id) $score += 10;
                elseif ($padre->unidad_organizacional_mandante_id && $child->unidad_organizacional_mandante_id) $score -= 50;
                
                if ($padre->dependencia_id == $child->dependencia_id) $score += 10;
                elseif ($padre->dependencia_id && $child->dependencia_id) $score -= 20;
                
                if ($child->numero_contrato && $padre->numero_contrato) {
                    $score += ($child->numero_contrato == $padre->numero_contrato) ? 50 : -100;
                }

                if ($score > $bestScore) { $bestScore = $score; $bestPadre = $padre; }
            }

            if ($bestPadre && $bestScore > -50) {
                $bestPadre->temporal_children->push($child);
                $child->is_attached_to_parent = true;
            }
        }

        $raices = $collection->filter(fn($item) => !$item->is_attached_to_parent);
        $raices = $raices->sortBy(fn($item) => $item->contratista->razon_social ?? '');
        $aplanarArbol($raices);

        return $resultadoFinal;
    }

    public function setPeriodo($mes)
    {
        $this->mes_seleccionado = $mes;
        $this->cargarCarpetas();
    }

    public function render()
    {
        $user = Auth::user();
        $mandanteId = $user->mandante_id;

        $calendario = CalendarioVerificacion::where('mandante_id', $mandanteId)
            ->where('anio', $this->anio_seleccionado)
            ->orderBy('mes')
            ->get();

        return view('livewire.mandante.verificacion', [
            'calendario' => $calendario
        ])->layout('layouts.app');
    }
}
