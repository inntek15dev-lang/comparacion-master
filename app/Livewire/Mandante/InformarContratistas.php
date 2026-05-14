<?php

namespace App\Livewire\Mandante;

use App\Models\Contratista;
use App\Models\ContratistaUnidadOrganizacional;
use App\Models\ExclusionVerificacionPeriodo;
use App\Models\Mandante;
use App\Models\SolicitudVinculacion;
use App\Models\UnidadOrganizacionalMandante;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class InformarContratistas extends Component
{
    public int $periodoMes;
    public int $periodoAnio;
    public ?int $mandanteIdSeleccionado = null;
    public $mandantesDisponibles = [];
    public array $selecciones = [];
    public array $fechasVerifica = [];
    public bool $esSoloLectura = false;

    public function mount()
    {
        $ahora = Carbon::now();
        $this->periodoMes  = $ahora->month;
        $this->periodoAnio = $ahora->year;
        $user = Auth::user();

        if ($user->hasRole('ASEM_Admin')) {
            $this->mandantesDisponibles   = Mandante::where('is_active', true)->orderBy('razon_social')->get();
            $this->mandanteIdSeleccionado = $this->mandantesDisponibles->first()?->id;
        } elseif ($user->hasRole('Mandante_Admin') || $user->hasRole('Mandante_Ver')) {
            $this->mandanteIdSeleccionado = $user->mandante_id;
        }

        $this->cargarContratistas();
        $this->esSoloLectura = $user->hasRole('Mandante_Ver');
    }

    public function updatedPeriodoMes()             { $this->cargarContratistas(); }
    public function updatedPeriodoAnio()            { $this->cargarContratistas(); }
    public function updatedMandanteIdSeleccionado() { $this->cargarContratistas(); }

    public function cargarContratistas()
    {
        if (!$this->mandanteIdSeleccionado) {
            $this->selecciones    = [];
            $this->fechasVerifica = [];
            return;
        }

        $inicioMesNomina = Carbon::create($this->periodoAnio, $this->periodoMes, 1)->startOfDay();
        $finMesNomina    = $inicioMesNomina->copy()->endOfMonth();

        // El Ccalendario de verificación (donde se carga) es el mes SIGUIENTE a la nómina
        $inicioCalendario = $inicioMesNomina->copy()->addMonth();
        $periodoVerifStr  = $inicioCalendario->toDateString();

        $exclusionesExistentes = ExclusionVerificacionPeriodo::where('mandante_id', $this->mandanteIdSeleccionado)
            ->where('periodo', $periodoVerifStr)
            ->pluck('contratista_unidad_organizacional_id')
            ->toArray();

        $vinculaciones = $this->ordenarJerarquicamente(collect($this->getVinculacionesVerificables()));

        $this->selecciones    = [];
        $this->fechasVerifica = [];

        foreach ($vinculaciones as $v) {
            $cuoId       = $v->cuo_id;
            $fechaInicio = $v->fecha_inicio_verifica ? Carbon::parse($v->fecha_inicio_verifica) : null;
            $fechaFin    = $v->fecha_fin_verifica    ? Carbon::parse($v->fecha_fin_verifica)    : null;
            
            // Validamos si la vigencia del contrato cubre el mes de NÓMINA seleccionado
            $cubrePeriodo = ($fechaInicio && $fechaFin)
                ? ($fechaInicio->lte($finMesNomina) && $fechaFin->gte($inicioMesNomina))
                : false;
            $this->selecciones[$cuoId]    = $cubrePeriodo && !in_array($cuoId, $exclusionesExistentes);
            $this->fechasVerifica[$cuoId] = [
                'fecha_inicio_verifica' => $v->fecha_inicio_verifica ? Carbon::parse($v->fecha_inicio_verifica)->format('Y-m-d') : null,
                'fecha_fin_verifica'    => $v->fecha_fin_verifica    ? Carbon::parse($v->fecha_fin_verifica)->format('Y-m-d')    : null,
                'cubre_periodo'         => $cubrePeriodo,
            ];
        }
    }

    public function guardarSelecciones()
    {
        if ($this->esSoloLectura) { abort(403); }
        if (!$this->mandanteIdSeleccionado) return;

        $inicioMesNomina = Carbon::create($this->periodoAnio, $this->periodoMes, 1)->startOfDay();
        $inicioCalendario = $inicioMesNomina->copy()->addMonth();
        $periodoCalendarStr = $inicioCalendario->toDateString();
        $userId     = Auth::id();

        DB::beginTransaction();
        try {
            ExclusionVerificacionPeriodo::where('mandante_id', $this->mandanteIdSeleccionado)
                ->where('periodo', $periodoCalendarStr)->delete();

            foreach ($this->selecciones as $cuoId => $seleccionado) {
                if (!$seleccionado) {
                    $fechas = $this->fechasVerifica[$cuoId] ?? null;
                    if ($fechas && ($fechas['cubre_periodo'] ?? false)) {
                        ExclusionVerificacionPeriodo::create([
                            'mandante_id'                          => $this->mandanteIdSeleccionado,
                            'contratista_unidad_organizacional_id' => $cuoId,
                            'periodo'                              => $periodoCalendarStr,
                            'excluido_por_user_id'                 => $userId,
                        ]);
                    }
                }
            }

            foreach ($this->fechasVerifica as $cuoId => $fechas) {
                ContratistaUnidadOrganizacional::where('id', $cuoId)->update([
                    'fecha_inicio_verifica' => $fechas['fecha_inicio_verifica'] ?? null,
                    'fecha_fin_verifica'    => $fechas['fecha_fin_verifica']    ?? null,
                ]);
            }

            DB::commit();
            session()->flash('message', '✅ Selecciones guardadas para ' . $this->getNombreMes() . ' ' . $this->periodoAnio . '.');
            $this->cargarContratistas();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', '❌ Error al guardar: ' . $e->getMessage());
        }
    }

    public function getNombreMes(): string
    {
        return [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
                7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre']
               [$this->periodoMes] ?? '';
    }

    public function getMandanteNombre(): string
    {
        if (!$this->mandanteIdSeleccionado) return '';
        $m = Mandante::find($this->mandanteIdSeleccionado);
        return $m ? $m->razon_social : '';
    }

    protected function getVinculacionesVerificables()
    {
        $mandanteId = $this->mandanteIdSeleccionado;
        return DB::table('contratista_unidad_organizacional as cuo')
            ->join('contratistas', 'contratistas.id', '=', 'cuo.contratista_id')
            ->join('solicitudes_vinculacion as sv', function ($join) use ($mandanteId) {
                $join->on('sv.contratista_id', '=', 'cuo.contratista_id')
                     ->where('sv.mandante_id', '=', $mandanteId)
                     ->where('sv.estado', '=', 'APROBADA');
            })
            ->leftJoin('unidades_organizacionales_mandante as uo', 'uo.id', '=', 'cuo.unidad_organizacional_mandante_id')
            ->leftJoin('dependencias as dep', 'dep.id', '=', 'cuo.dependencia_id')
            ->where('cuo.verifica', true)
            ->where(function ($q) use ($mandanteId) {
                $q->where('uo.mandante_id', $mandanteId)->orWhere('dep.mandante_id', $mandanteId);
            })
            ->select(
                'cuo.id as cuo_id', 'cuo.contratista_id', 'cuo.id_registro', 'cuo.sap',
                'cuo.fecha_inicio_verifica', 'cuo.fecha_fin_verifica',
                'cuo.unidad_organizacional_mandante_id', 'cuo.dependencia_id', 'cuo.numero_contrato',
                'contratistas.razon_social', 'contratistas.rut', 'contratistas.id as contratista_db_id',
                'sv.contratista_padre_id',
                DB::raw("COALESCE(uo.nombre_unidad, '') as uo_nombre"),
                DB::raw("COALESCE(dep.nombre, '') as lugar_nombre")
            )
            ->orderBy('contratistas.razon_social')->orderBy('cuo.id')->get();
    }

    protected function ordenarJerarquicamente($collection)
    {
        $byContratista = $collection->groupBy('contratista_id');
        foreach ($collection as $item) { $item->temporal_children = collect(); $item->is_attached_to_parent = false; }
        foreach ($collection as $child) {
            if (empty($child->contratista_padre_id)) continue;
            $candidatos = $byContratista->get($child->contratista_padre_id);
            if (!$candidatos || $candidatos->isEmpty()) continue;
            $best = null; $bestScore = -1;
            foreach ($candidatos as $padre) {
                $s = 0;
                if ($padre->unidad_organizacional_mandante_id == $child->unidad_organizacional_mandante_id) $s += 10;
                elseif ($padre->unidad_organizacional_mandante_id && $child->unidad_organizacional_mandante_id) $s -= 50;
                if ($padre->dependencia_id == $child->dependencia_id) $s += 10;
                elseif ($padre->dependencia_id && $child->dependencia_id) $s -= 20;
                if ($child->numero_contrato && $padre->numero_contrato) {
                    $s += ($child->numero_contrato == $padre->numero_contrato) ? 50 : -100;
                }
                if ($s > $bestScore) { $bestScore = $s; $best = $padre; }
            }
            if ($best && $bestScore > -50) { $best->temporal_children->push($child); $child->is_attached_to_parent = true; }
        }
        $resultado = collect();
        $flatten = function ($items, $prefix = '') use (&$flatten, &$resultado) {
            $n = 1;
            foreach ($items as $item) {
                $item->correlativo_jerarquico = $prefix === '' ? (string)$n : "$prefix.$n";
                $resultado->push($item);
                if ($item->temporal_children->isNotEmpty()) $flatten($item->temporal_children, $item->correlativo_jerarquico);
                $n++;
            }
        };
        $flatten($collection->filter(fn($i) => !$i->is_attached_to_parent));
        return $resultado;
    }

    public function render()
    {
        $vinculaciones = $this->mandanteIdSeleccionado
            ? $this->ordenarJerarquicamente(collect($this->getVinculacionesVerificables()))
            : collect();
        return view('livewire.mandante.informar-contratistas', [
            'vinculaciones'  => $vinculaciones,
            'nombreMes'      => $this->getNombreMes(),
            'mandanteNombre' => $this->getMandanteNombre(),
            'esSoloLectura'  => $this->esSoloLectura,
        ])->layout('layouts.app');
    }
}
