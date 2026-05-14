<?php

namespace App\Livewire\Mandante;

use Livewire\Component;
use App\Models\Contratista;
use App\Models\Mandante;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

#[Layout('layouts.app')]
class Operaciones extends Component
{
    public $contratistasDisponibles = [];
    
    public $selectedMandanteId = null;

    #[Url] // Permite recibir el ID desde la URL (ej: desde Supervisión)
    public $selectedContratistaId = null;

    // Parámetros de preselección para filtros internos (vienen desde Supervisión)
    #[Url(as: 'lugar')]
    public $preselectedLugar = null;
    
    #[Url(as: 'uo')]
    public $preselectedUo = null;
    
    #[Url(as: 'contrato')]
    public $preselectedContrato = null;

    // Propiedad para controlar acceso de solo lectura (Mandante_Ver)
    public bool $esSoloLectura = false;

    public function mount()
    {
        $user = Auth::user();
        if ($user && $user->hasAnyRole(['Mandante_Admin', 'Mandante_Ver']) && $user->mandante_id) {
            // El mandante está fijo según el usuario logueado
            $this->selectedMandanteId = $user->mandante_id;
            
            // Determinar si es acceso de solo lectura (Mandante_Ver)
            $this->esSoloLectura = $user->hasRole('Mandante_Ver');
            
            // Cargamos los contratistas para este mandante inmediatamente.
            $this->loadContratistasForMandante($this->selectedMandanteId);
            
            // Si viene un contratistaId desde la URL, verificar si es principal o subcontratista
            if ($this->selectedContratistaId) {
                // Verificar si el contratista está en la lista de principales
                $esContratistaPrincipal = collect($this->contratistasDisponibles)->contains('id', (int)$this->selectedContratistaId);
                
                if ($esContratistaPrincipal) {
                    // Es principal, cargar sus subcontratistas
                    $this->cargarSubs();
                } else {
                    // Es un subcontratista, encontrar su contratista padre
                    $solicitud = \App\Models\SolicitudVinculacion::where('contratista_id', $this->selectedContratistaId)
                        ->where('mandante_id', $this->selectedMandanteId)
                        ->where('estado', 'APROBADA')
                        ->whereNotNull('contratista_padre_id')
                        ->first();
                    
                    if ($solicitud && $solicitud->contratista_padre_id) {
                        // Guardar el ID del subcontratista
                        $subContratistaId = $this->selectedContratistaId;
                        
                        // Establecer el padre como contratista principal seleccionado
                        $this->selectedContratistaId = $solicitud->contratista_padre_id;
                        
                        // Cargar los subcontratistas del padre
                        $this->cargarSubs();
                        
                        // Preseleccionar el subcontratista
                        $this->selectedSubContratistaId = $subContratistaId;
                    }
                }
            }
        }
    }

    private function loadContratistasForMandante($mandanteId)
    {
        if (!$mandanteId) {
            return;
        }

        $mandante = Mandante::find($mandanteId);
        if (!$mandante) {
            return;
        }

        // 1. Obtener IDs desde Solicitudes de Vinculación Aprobadas (Vía Legal)
        $idsSolicitudes = $mandante->contratistasPrincipalesAprobados()
            ->pluck('contratistas.id')
            ->toArray();

        // 2. Obtener IDs desde Asignaciones a Unidades Organizacionales (Vía Operativa 1)
        // Tabla: contratista_unidad_organizacional
        $idsUOs = DB::table('contratista_unidad_organizacional')
            ->join('unidades_organizacionales_mandante', 'contratista_unidad_organizacional.unidad_organizacional_mandante_id', '=', 'unidades_organizacionales_mandante.id')
            ->where('unidades_organizacionales_mandante.mandante_id', $mandanteId)
            ->pluck('contratista_unidad_organizacional.contratista_id')
            ->toArray();

        // 3. Obtener IDs desde Asignaciones a Dependencias (Vía Operativa 2)
        // Tabla: contratista_dependencia
        $idsDeps = DB::table('contratista_dependencia')
            ->join('dependencias', 'contratista_dependencia.dependencia_id', '=', 'dependencias.id')
            ->where('dependencias.mandante_id', $mandanteId)
            ->pluck('contratista_dependencia.contratista_id')
            ->toArray();

        // Unificar todos los IDs únicos para asegurar que no falte nadie que esté operando
        $allIds = array_unique(array_merge($idsSolicitudes, $idsUOs, $idsDeps));

        if (!empty($allIds)) {
            // Filtrar solo los que NO tienen padre registrado en este mandante (Son Principales)
            // O, si tienen padre, que su padre NO sea parte de este mandante (caso raro, pero posible)
            // La definición más estricta de "Principal" para este mandante es que su solicitud no tenga padre o padre sea nulo.
            
            $this->contratistasDisponibles = Contratista::whereIn('id', $allIds)
                ->whereDoesntHave('solicitudesVinculacion', function ($q) use ($mandanteId) {
                    $q->where('mandante_id', $mandanteId)
                      ->whereNotNull('contratista_padre_id') // Es un subcontratista
                      ->where('estado', 'APROBADA');
                })
                ->orderBy('razon_social')
                ->get(['id', 'razon_social', 'rut']);
        } else {
            $this->contratistasDisponibles = [];
        }
    }

    // ================== NUEVA LÓGICA DE SUBCONTRATISTAS (REPLICADA DE OperacionesGlobales) ==================
    public $subContratistasDisponibles = [];
    public $selectedSubContratistaId = null;

    public function updatedSelectedContratistaId($contratistaId)
    {
        $this->cargarSubs();
    }

    public function cargarSubs()
    {
        $this->selectedSubContratistaId = null;
        $this->subContratistasDisponibles = [];
        $this->debugPadreId = "Iniciando..."; 

        $contratistaId = $this->selectedContratistaId;

        if ($contratistaId) {
            $contratista = Contratista::find($contratistaId);
            if ($contratista) {
                // Aquí usamos la recursión y el modelo oficial
                $this->subContratistasDisponibles = $this->obtenerDescendientesPlanos($contratista);
            } else {
                 $this->debugPadreId = "Contratista No Encontrado (ID: $contratistaId)";
            }
        } else {
            $this->debugPadreId = "ID Nulo (Selección vacía)";
        }
    }

    public $debugPadreId = null; // Debug prop

    private function obtenerDescendientesPlanos($contratista, $nivel = 0)
    {
        $items = collect();
        $this->debugPadreId = $contratista->id; // Guardar ID para debug en vista
        
        // USO DEL MODELO OFICIAL (Igual que en MisSubcontratistas.php)
        $idsHijos = \App\Models\SolicitudVinculacion::where('contratista_padre_id', $contratista->id)
            ->where('estado', 'APROBADA')
            ->pluck('contratista_id');
            
        \Illuminate\Support\Facades\Log::info("MANDANTE DEBUG MODEL: IDs hijos para " . $contratista->id . ": " . implode(',', $idsHijos->toArray()));

        if ($idsHijos->isEmpty()) {
            return $items;
        }

        $hijos = Contratista::whereIn('id', $idsHijos)
            ->orderBy('razon_social')
            ->get();
        
        foreach ($hijos as $hijo) {
            $prefix = str_repeat('↳ ', $nivel + 1); 
            
            $items->push([
                'id' => $hijo->id,
                'razon_social' => $prefix . $hijo->razon_social,
                'rut' => $hijo->rut
            ]);
            
            $descendientes = $this->obtenerDescendientesPlanos($hijo, $nivel + 1);
            $items = $items->merge($descendientes);
        }
        
        return $items;
    }
    // ================== FIN NUEVA LÓGICA ==================

    public function render()
    {
        return view('livewire.mandante.operaciones');
    }
}