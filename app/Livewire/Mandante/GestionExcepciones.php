<?php

namespace App\Livewire\Mandante;

use Livewire\Component;
use App\Models\Dependencia;
use App\Models\UnidadOrganizacionalMandante;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class GestionExcepciones extends Component
{
    public $mandanteId; // El ID del Mandante se fija al montar

    public $lugaresTrabajoDisponibles = [];
    public $filtroLugarTrabajoId = 'todos';

    public $unidadesOrganizacionalesDisponibles = [];
    public $filtroUoId = 'todos';

    public string $searchContratista = '';

    public $contextosEncontrados = null;
    public bool $esSoloLectura = false;

    public function mount()
    {
        // Doctrina: Identificar automáticamente el contexto del Mandante
        $this->mandanteId = Auth::user()->mandante_id;
        if (!$this->mandanteId) {
            abort(403, 'Usuario no asociado a un Principal.');
        }

        $user = Auth::user();
        if ($user && $user->hasAnyRole(['Mandante_Admin', 'Mandante_Ver'])) {
            $this->esSoloLectura = $user->hasRole('Mandante_Ver');
        }

        $this->actualizarLugaresTrabajo();
        
        // ================== INICIO DE LA MODIFICACIÓN DOCTRINAL "VISIÓN TOTAL" ==================
        // Ejecutar la búsqueda inicial para mostrar todos los contextos al cargar.
        $this->buscarContextos();
        // ================== FIN DE LA MODIFICACIÓN DOCTRINAL "VISIÓN TOTAL" ====================
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
        // La búsqueda se activa si hay un término de búsqueda o si el campo está vacío
        // para mostrar todos los contratistas del mandante.
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
            ->select(
                'contratistas.id as contratista_id', 'contratistas.razon_social as contratista_razon_social', 'contratistas.rut as contratista_rut',
                'dependencias.id as dependencia_id', 'dependencias.nombre as dependencia_nombre',
                'uo.id as uo_id', 'uo.nombre_unidad as uo_nombre'
            )
            ->where('uo.mandante_id', $this->mandanteId) // Consulta siempre anclada al mandante del usuario
            ->distinct();

        if (!empty($this->searchContratista)) {
            $contextosQuery->where(function ($query) {
                $query->where('contratistas.razon_social', 'like', '%' . $this->searchContratista . '%')
                      ->orWhere('contratistas.rut', 'like', '%' . $this->searchContratista . '%');
            });
        }

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
        $this->lugaresTrabajoDisponibles = Dependencia::where('estado', true)
            ->where('mandante_id', $this->mandanteId)
            ->with('parent')
            ->get()->sortBy('nombre_jerarquico');
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
        return view('livewire.mandante.gestion-excepciones');
    }
}