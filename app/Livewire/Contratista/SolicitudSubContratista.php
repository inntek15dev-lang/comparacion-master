<?php

namespace App\Livewire\Contratista;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Contratista;
use App\Models\SolicitudVinculacion;
use App\Models\TipoEmpresaLegal;
use App\Models\Rubro;
use App\Models\Region;
use App\Models\Comuna;
use App\Models\RangoCantidadTrabajadores;
use App\Models\Mutualidad;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Rules\ValidarRutRule;

#[Layout('layouts.app')]
class SolicitudSubContratista extends Component
{
    // Datos empresa sub-contratista
    public $razon_social, $nombre_fantasia, $rut_contratista;
    public $direccion_calle, $direccion_numero, $comuna_id;
    public $selected_region_id;
    public $telefono_empresa, $email_empresa;
    public $tipo_empresa_legal_id, $rubro_id;
    public $rango_cantidad_trabajadores_id, $mutualidad_id;
    
    // Representante Legal
    public $rep_legal_nombres, $rep_legal_apellido_paterno, $rep_legal_apellido_materno;
    public $rep_legal_rut, $rep_legal_telefono, $rep_legal_email;
    
    // Selector de padre (para crear subsub y subsubsub)
    public $contratista_padre_id;
    
    // Selector de Principal (Mandante)
    public $mandante_id;
    public $mandantesDisponibles = [];
    
    // Catálogos
    public $tiposEmpresaLegal, $rubros, $regiones, $comunasDisponibles = [];
    public $rangosCantidad, $mutualidades;
    
    // Sub-contratistas existentes (para crear jerarquía)
    public $subContratistasExistentes = [];
    public $contratistaActual;
    
    // Estado del formulario
    public $solicitudEnviada = false;

    public function mount()
    {
        $user = Auth::user();
        
        if (!$user->hasRole('Contratista_Admin')) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }
        
        $this->contratistaActual = $user->contratista;
        
        // Cargar catálogos
        $this->tiposEmpresaLegal = TipoEmpresaLegal::where('is_active', true)->orderBy('nombre')->get();
        $this->rubros = Rubro::where('is_active', true)->orderBy('nombre')->get();
        $this->regiones = Region::where('is_active', true)->orderBy('nombre')->get();
        $this->rangosCantidad = RangoCantidadTrabajadores::where('is_active', true)->orderBy('id')->get();
        $this->mutualidades = Mutualidad::where('is_active', true)->orderBy('nombre')->get();
        
        // Cargar Mandantes (Principales) donde el contratista presta servicio
        $this->cargarMandantesDisponibles();
        
        // Cargar sub-contratistas ya aprobados (para poder crear subsub y subsubsub)
        $this->cargarSubContratistasExistentes();
        
        // Por defecto, el padre es el contratista actual
        $this->contratista_padre_id = $this->contratistaActual->id;
    }
    
    private function cargarMandantesDisponibles()
    {
        // Obtener mandantes únicos desde las vinculaciones activas del contratista
        $this->mandantesDisponibles = \App\Models\Mandante::whereHas('unidadesOrganizacionales', function($query) {
            $query->whereHas('contratistasHabilitados', function($q) {
                $q->where('contratista_id', $this->contratistaActual->id);
            });
        })->orderBy('razon_social')->get();
        
        // Si hay solo un mandante, seleccionarlo por defecto
        if ($this->mandantesDisponibles->count() == 1) {
            $this->mandante_id = $this->mandantesDisponibles->first()->id;
        }
    }
    
    private function cargarSubContratistasExistentes()
    {
        // Obtener todos los niveles de sub-contratistas aprobados
        $this->subContratistasExistentes = collect();
        
        // Nivel 1: Sub-contratistas directos
        $subsDirectos = $this->contratistaActual->subContratistasAprobados;
        
        foreach ($subsDirectos as $sub) {
            $this->subContratistasExistentes->push([
                'id' => $sub->id,
                'razon_social' => $sub->razon_social,
                'nivel' => 'Sub-contratista',
                'padre_nombre' => $this->contratistaActual->razon_social,
            ]);
            
            // Nivel 2: Sub-sub-contratistas
            $subsubs = $sub->subContratistasAprobados;
            foreach ($subsubs as $subsub) {
                $this->subContratistasExistentes->push([
                    'id' => $subsub->id,
                    'razon_social' => $subsub->razon_social,
                    'nivel' => 'Sub-sub-contratista',
                    'padre_nombre' => $sub->razon_social,
                ]);
                
                // Nivel 3: Sub-sub-sub-contratistas
                $subsubsubs = $subsub->subContratistasAprobados;
                foreach ($subsubsubs as $subsubsub) {
                    $this->subContratistasExistentes->push([
                        'id' => $subsubsub->id,
                        'razon_social' => $subsubsub->razon_social,
                        'nivel' => 'Sub-sub-sub-contratista',
                        'padre_nombre' => $subsub->razon_social,
                    ]);
                }
            }
        }
    }
    
    public function updatedSelectedRegionId($regionId)
    {
        if (!empty($regionId)) {
            $this->comunasDisponibles = Comuna::where('region_id', $regionId)
                ->where('is_active', true)
                ->orderBy('nombre')
                ->get();
        } else {
            $this->comunasDisponibles = collect();
        }
        $this->comuna_id = null;
    }
    
    protected function rules()
    {
        return [
            'contratista_padre_id' => 'required|exists:contratistas,id',
            'mandante_id' => 'required|exists:mandantes,id',
            'razon_social' => 'required|string|min:3|max:255',
            'nombre_fantasia' => 'required|string|max:255',
            'rut_contratista' => ['required', 'string', 'max:12', 'unique:contratistas,rut', new ValidarRutRule()],
            'telefono_empresa' => 'required|string|max:20',
            'email_empresa' => 'required|email|max:255|unique:contratistas,email_empresa',
            'direccion_calle' => 'required|string|max:255',
            'direccion_numero' => 'required|string|max:50',
            'selected_region_id' => 'required|exists:regiones,id',
            'comuna_id' => 'required|exists:comunas,id',
            'tipo_empresa_legal_id' => 'required|exists:tipos_empresa_legal,id',
            'rubro_id' => 'required|exists:rubros,id',
            'rango_cantidad_trabajadores_id' => 'required|exists:rangos_cantidad_trabajadores,id',
            'mutualidad_id' => 'required|exists:mutualidades,id',
            'rep_legal_nombres' => 'required|string|max:100',
            'rep_legal_apellido_paterno' => 'required|string|max:100',
            'rep_legal_apellido_materno' => 'required|string|max:100',
            'rep_legal_rut' => ['required', 'string', 'max:12', new ValidarRutRule()],
            'rep_legal_telefono' => 'required|string|max:20',
            'rep_legal_email' => 'required|email|max:255',
        ];
    }
    
    public function validationAttributes()
    {
        return [
            'contratista_padre_id' => 'Contratista Padre',
            'mandante_id' => 'Principal (Mandante)',
            'razon_social' => 'Razón Social',
            'nombre_fantasia' => 'Nombre Comercial',
            'rut_contratista' => 'NIT Empresa',
            'telefono_empresa' => 'Teléfono Empresa',
            'email_empresa' => 'Email Empresa',
            'direccion_calle' => 'Dirección',
            'direccion_numero' => 'Barrio',
            'selected_region_id' => 'Departamento',
            'comuna_id' => 'Municipio',
            'tipo_empresa_legal_id' => 'Tipo Empresa Legal',
            'rubro_id' => 'Actividad Económica',
            'rango_cantidad_trabajadores_id' => 'Rango Empleados',
            'mutualidad_id' => 'ARL',
            'rep_legal_nombres' => 'Nombres Rep. Legal',
            'rep_legal_apellido_paterno' => 'Primer Apellido Rep. Legal',
            'rep_legal_apellido_materno' => 'Segundo Apellido Rep. Legal',
            'rep_legal_rut' => 'NIT Rep. Legal',
            'rep_legal_telefono' => 'Teléfono Rep. Legal',
            'rep_legal_email' => 'Email Rep. Legal',
        ];
    }
    
    public function enviarSolicitud()
    {
        $validatedData = $this->validate();
        
        // Verificar que el padre seleccionado pertenece a la jerarquía del contratista
        $padreValido = $this->validarPadre();
        if (!$padreValido) {
            session()->flash('error', 'El contratista padre seleccionado no es válido.');
            return;
        }
        
        // Verificar nivel máximo (solo hasta 4 niveles: Contratista -> Sub -> SubSub -> SubSubSub)
        $nivelPadre = $this->obtenerNivelPadre();
        if ($nivelPadre >= 4) {
            session()->flash('error', 'No se pueden crear más de 4 niveles de sub-contratistas.');
            return;
        }
        
        DB::beginTransaction();
        try {
            // Crear el registro del sub-contratista (sin usuario por ahora)
            $subContratista = Contratista::create([
                'razon_social' => $validatedData['razon_social'],
                'nombre_fantasia' => $validatedData['nombre_fantasia'],
                'rut' => $validatedData['rut_contratista'],
                'direccion_calle' => $validatedData['direccion_calle'],
                'direccion_numero' => $validatedData['direccion_numero'],
                'comuna_id' => $validatedData['comuna_id'],
                'telefono_empresa' => $validatedData['telefono_empresa'],
                'email_empresa' => $validatedData['email_empresa'],
                'tipo_empresa_legal_id' => $validatedData['tipo_empresa_legal_id'],
                'rubro_id' => $validatedData['rubro_id'],
                'tipo_inscripcion' => 'Subcontratista',
                'rango_cantidad_trabajadores_id' => $validatedData['rango_cantidad_trabajadores_id'],
                'mutualidad_id' => $validatedData['mutualidad_id'],
                'rep_legal_nombres' => $validatedData['rep_legal_nombres'],
                'rep_legal_apellido_paterno' => $validatedData['rep_legal_apellido_paterno'],
                'rep_legal_apellido_materno' => $validatedData['rep_legal_apellido_materno'],
                'rep_legal_rut' => $validatedData['rep_legal_rut'],
                'rep_legal_telefono' => $validatedData['rep_legal_telefono'],
                'rep_legal_email' => $validatedData['rep_legal_email'],
                'is_active' => false, // Inactivo hasta aprobación
                'estado_plataforma' => 'Pendiente de Aprobacion',
                'admin_user_id' => null, // Sin usuario admin aún
            ]);
            
            // Crear solicitud de vinculación
            SolicitudVinculacion::create([
                'contratista_id' => $subContratista->id,
                'tipo_solicitud' => 'SUBCONTRATISTA',
                'mandante_id' => $validatedData['mandante_id'],
                'contratista_padre_id' => $validatedData['contratista_padre_id'],
                'estado' => 'PENDIENTE',
            ]);
            
            DB::commit();
            
            $this->solicitudEnviada = true;
            session()->flash('message', 'Solicitud de sub-contratista enviada exitosamente. Un administrador del Principal la revisará y asignará las vinculaciones correspondientes.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error al procesar la solicitud: ' . $e->getMessage());
        }
    }
    
    private function validarPadre()
    {
        $padreId = $this->contratista_padre_id;
        
        // Si es el contratista actual, es válido
        if ($padreId == $this->contratistaActual->id) {
            return true;
        }
        
        // Verificar que está en la lista de sub-contratistas existentes
        return $this->subContratistasExistentes->contains('id', $padreId);
    }
    
    private function obtenerNivelPadre()
    {
        $padreId = $this->contratista_padre_id;
        
        // Nivel 1: Contratista principal
        if ($padreId == $this->contratistaActual->id) {
            return 1;
        }
        
        // Buscar en la jerarquía
        $subExistente = $this->subContratistasExistentes->firstWhere('id', $padreId);
        
        if (!$subExistente) {
            return 1;
        }
        
        switch ($subExistente['nivel']) {
            case 'Sub-contratista':
                return 2;
            case 'Sub-sub-contratista':
                return 3;
            case 'Sub-sub-sub-contratista':
                return 4;
            default:
                return 1;
        }
    }
    
    public function nuevaSolicitud()
    {
        $this->reset([
            'razon_social', 'nombre_fantasia', 'rut_contratista',
            'direccion_calle', 'direccion_numero', 'comuna_id', 'selected_region_id',
            'telefono_empresa', 'email_empresa',
            'tipo_empresa_legal_id', 'rubro_id',
            'rango_cantidad_trabajadores_id', 'mutualidad_id',
            'rep_legal_nombres', 'rep_legal_apellido_paterno', 'rep_legal_apellido_materno',
            'rep_legal_rut', 'rep_legal_telefono', 'rep_legal_email',
            'solicitudEnviada'
        ]);
        
        $this->contratista_padre_id = $this->contratistaActual->id;
        $this->comunasDisponibles = collect();
        $this->cargarSubContratistasExistentes();
    }

    public function render()
    {
        return view('livewire.contratista.solicitud-sub-contratista');
    }
}
