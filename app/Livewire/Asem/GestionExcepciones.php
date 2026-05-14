<?php

namespace App\Livewire\Asem;

use Livewire\Component;
use App\Models\Mandante;
use App\Models\Dependencia;
use App\Models\UnidadOrganizacionalMandante;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class GestionExcepciones extends Component
{
    public $mandantesDisponibles = [];
    public $filtroMandanteId;
    
    public $lugaresTrabajoDisponibles = [];
    public $filtroLugarTrabajoId = 'todos';

    public $unidadesOrganizacionalesDisponibles = [];
    public $filtroUoId = 'todos';

    public string $searchContratista = '';

    public $contextosEncontrados = null;

    public function mount()
    {
        $this->mandantesDisponibles = Mandante::where('is_active', true)->orderBy('razon_social')->get();
        $this->actualizarLugaresTrabajo();
    }

    public function updatedFiltroMandanteId()
    {
        $this->filtroLugarTrabajoId = 'todos';
        $this->filtroUoId = 'todos';
        $this->actualizarLugaresTrabajo();
        $this->unidadesOrganizacionalesDisponibles = [];
        $this->buscarContextos();
    }

    public function updatedFiltroLugarTrabajoId()
    {
        $this->filtroUoId = 'todos';
        $this->actualizarUnidadesOrganizacionales();
        $this->buscarContextos();
    }

    public function updatedFiltroUoId()
    {
        $this->buscarContextos();
    }

    public function updatedSearchContratista()
    {
        $this->buscarContextos();
    }

    public function buscarContextos()
    {
        // ================== INICIO DE LA MODIFICACIÓN DOCTRINAL ==================
        // Doctrina corregida: Solo se requiere un Principal para iniciar la búsqueda.
        if (empty($this->filtroMandanteId)) {
            $this->contextosEncontrados = null; // Mostrar mensaje inicial si no hay Principal
            return;
        }
        // ================== FIN DE LA MODIFICACIÓN DOCTRINAL ====================

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
            ->join('contratistas', 'contextos.contratista_id', '=', 'contratistas.id')
            ->join('dependencias', 'contextos.dependencia_id', '=', 'dependencias.id')
            ->join('unidades_organizacionales_mandante as uo', 'contextos.unidad_organizacional_mandante_id', '=', 'uo.id')
            ->join('mandantes', 'uo.mandante_id', '=', 'mandantes.id')
            ->select(
                'contratistas.id as contratista_id', 'contratistas.razon_social as contratista_razon_social', 'contratistas.rut as contratista_rut',
                'dependencias.id as dependencia_id', 'dependencias.nombre as dependencia_nombre',
                'uo.id as uo_id', 'uo.nombre_unidad as uo_nombre',
                'mandantes.id as mandante_id', 'mandantes.razon_social as mandante_razon_social'
            )
            ->where('mandantes.id', $this->filtroMandanteId)
            ->distinct();

        // ================== INICIO DE LA MODIFICACIÓN DOCTRINAL ==================
        // Aplicar filtro de búsqueda de contratista solo si existe un término.
        if (!empty($this->searchContratista)) {
            $contextosQuery->where(function ($query) {
                $query->where('contratistas.razon_social', 'like', '%' . $this->searchContratista . '%')
                      ->orWhere('contratistas.rut', 'like', '%' . $this->searchContratista . '%');
            });
        }
        // ================== FIN DE LA MODIFICACIÓN DOCTRINAL ====================

        if ($this->filtroLugarTrabajoId !== 'todos') {
            $contextosQuery->where('dependencias.id', $this->filtroLugarTrabajoId);
        }
        if ($this->filtroUoId !== 'todos') {
            $contextosQuery->where('uo.id', $this->filtroUoId);
        }

        $this->contextosEncontrados = $contextosQuery->orderBy('contratistas.razon_social')->get();
    }

    public function actualizarLugaresTrabajo()
    {
        $query = Dependencia::query()->where('estado', true);
        if (!empty($this->filtroMandanteId)) {
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

    public function render()
    {
        return view('livewire.asem.gestion-excepciones');
    }
}