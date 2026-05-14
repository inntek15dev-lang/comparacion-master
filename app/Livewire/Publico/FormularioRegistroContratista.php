<?php

namespace App\Livewire\Publico;

use Livewire\Component;
use App\Models\Contratista;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\TipoEmpresaLegal;
use App\Models\Rubro;
use App\Models\Region;
use App\Models\Comuna;
use App\Models\RangoCantidadTrabajadores;
use App\Models\Mutualidad;
use App\Models\Mandante;
use App\Models\SolicitudVinculacion;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Rules\ValidarRutRule;
use Livewire\Attributes\Layout;
use Illuminate\Validation\Rules\Password;

#[Layout('layouts.guest')]
class FormularioRegistroContratista extends Component
{
    public int $pasoActual = 1;
    public ?string $tipoRegistro = null;
    public $mandanteSeleccionado = null;
    public $rutContratistaPadreInput = null;
    public $contratistaPadreSeleccionado = null;
    public $razon_social, $nombre_fantasia, $rut_contratista, $direccion_calle, $direccion_numero, $comuna_id;
    public $selected_region_id_contratista;
    public $telefono_empresa, $email_empresa, $email_empresa_confirmation, $tipo_empresa_legal_id, $rubro_id;
    public $rango_cantidad_trabajadores_id, $mutualidad_id;
    public $rep_legal_nombres, $rep_legal_apellido_paterno, $rep_legal_apellido_materno, $rep_legal_rut, $rep_legal_telefono, $rep_legal_email;
    public string $admin_name = '';
    public string $admin_rut_usuario = '';
    public string $admin_email = '';
    public string $admin_email_confirmation = '';
    public $admin_password, $admin_password_confirmation;
    public $tiposEmpresaLegal, $rubros, $regiones, $comunasDisponiblesContratista = [], $rangosCantidad, $mutualidades;
    public bool $isAdminMode = false;

    // Propiedad para la validación en tiempo real de la contraseña
    public array $passwordValidationRules = [
        'length' => false,
        'uppercase' => false,
        'lowercase' => false,
        'number' => false,
        'special' => false,
    ];

    public function mount()
    {
        if (request()->routeIs('gestion.solicitudes.crear-manual')) {
            $this->isAdminMode = true;
        }

        $tipo = request()->query('tipo');
        $mandanteId = request()->query('mandante_id');
        $contratistaRut = request()->query('contratista_rut');

        if ($tipo === 'CONTRATISTA' && $mandanteId) {
            if (Mandante::where('id', $mandanteId)->exists()) {
                $this->tipoRegistro = 'CONTRATISTA';
                $this->mandanteSeleccionado = $mandanteId;
                $this->pasoActual = 3;
            }
        } elseif ($tipo === 'SUBCONTRATISTA' && $contratistaRut) {
            $this->tipoRegistro = 'SUBCONTRATISTA';
            $this->rutContratistaPadreInput = $contratistaRut;
            $this->pasoActual = 3;
        }

        if ($this->pasoActual === 1 && !$this->isAdminMode) {
            redirect()->route('home');
            return;
        }

        $this->tiposEmpresaLegal = TipoEmpresaLegal::where('is_active', true)->orderBy('nombre')->get();
        $this->rubros = Rubro::where('is_active', true)->orderBy('nombre')->get();
        $this->regiones = Region::where('is_active', true)->orderBy('nombre')->get();
        $this->rangosCantidad = RangoCantidadTrabajadores::where('is_active', true)->orderBy('id')->get();
        $this->mutualidades = Mutualidad::where('is_active', true)->orderBy('nombre')->get();
    }

    protected function rules()
    {
        return [
            'razon_social' => 'required|string|min:3|max:255',
            'nombre_fantasia' => 'required|string|max:255',
            'rut_contratista' => ['required', 'string', 'max:12', 'unique:contratistas,rut', new ValidarRutRule()],
            'telefono_empresa' => 'required|string|max:20',
            'email_empresa' => ['required', 'email', 'max:255', 'unique:contratistas,email_empresa', 'confirmed'],
            'direccion_calle' => 'required|string|max:255',
            'direccion_numero' => 'required|string|max:50',
            'selected_region_id_contratista' => 'required|exists:regiones,id',
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
            'admin_name' => 'required|string|max:255',
            'admin_rut_usuario' => ['required', 'string', 'max:12', 'unique:users,rut', new ValidarRutRule()],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email', 'confirmed'],
            'admin_password' => ['required', 'confirmed', Password::min(8)->max(12)->mixedCase()->numbers()->symbols()],
        ];
    }

    public function validationAttributes()
    {
        return [
            'razon_social' => 'Razón Social',
            'nombre_fantasia' => 'Nombre Comercial',
            'rut_contratista' => 'NIT Empresa',
            'telefono_empresa' => 'Teléfono Empresa',
            'email_empresa' => 'Email Empresa',
            'direccion_calle' => 'Calle',
            'direccion_numero' => 'Número',
            'selected_region_id_contratista' => 'Departamento',
            'comuna_id' => 'Municipio',
            'tipo_empresa_legal_id' => 'Tipo Empresa Legal',
            'rubro_id' => 'Actividad Económica',
            'rango_cantidad_trabajadores_id' => 'Rango Empleados',
            'mutualidad_id' => 'ARL',
            'rep_legal_nombres' => 'Nombres del Representante Legal',
            'rep_legal_apellido_paterno' => 'Primer Apellido del Representante Legal',
            'rep_legal_apellido_materno' => 'Segundo Apellido del Representante Legal',
            'rep_legal_rut' => 'NIT del Representante Legal',
            'rep_legal_telefono' => 'Teléfono del Representante Legal',
            'rep_legal_email' => 'Email del Representante Legal',
            'admin_name' => 'Su Nombre Completo',
            'admin_rut_usuario' => 'Su NIT',
            'admin_email' => 'Su Email',
            'admin_password' => 'Contraseña',
        ];
    }

    public function updated($propertyName)
    {
        // Se excluye la validación en tiempo real de la contraseña aquí para manejarla por separado
        if ($propertyName !== 'admin_password') {
            $this->validateOnly($propertyName);
        }
    }

    // Método que se ejecuta cada vez que la propiedad $admin_password cambia
    public function updatedAdminPassword($value)
    {
        $this->passwordValidationRules['length'] = Str::length($value) >= 8 && Str::length($value) <= 12;
        $this->passwordValidationRules['uppercase'] = Str::match('/[A-Z]/', $value);
        $this->passwordValidationRules['lowercase'] = Str::match('/[a-z]/', $value);
        $this->passwordValidationRules['number'] = Str::match('/[0-9]/', $value);
        $this->passwordValidationRules['special'] = Str::match('/[\W_]/', $value); // \W es cualquier no-letra, no-número. _ se añade por separado.
    }

    public function updatedSelectedRegionIdContratista($region_id)
    {
        if (!empty($region_id)) {
            $this->comunasDisponiblesContratista = Comuna::where('region_id', $region_id)->where('is_active', true)->orderBy('nombre')->get();
        } else {
            $this->comunasDisponiblesContratista = [];
        }
        $this->comuna_id = null;
    }

    public function enviarSolicitud()
    {
        $validatedData = $this->validate();

        if ($this->tipoRegistro === 'SUBCONTRATISTA') {
            $contratistaPadre = Contratista::where('rut', $this->rutContratistaPadreInput)->first();
            if (!$contratistaPadre) {
                session()->flash('error_general', 'El NIT del Contratista Principal ingresado no fue encontrado en nuestros registros.');
                return;
            }
            $this->contratistaPadreSeleccionado = $contratistaPadre->id;
            $this->mandanteSeleccionado = 99999; 
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validatedData['admin_name'],
                'rut' => $validatedData['admin_rut_usuario'],
                'email' => $validatedData['admin_email'],
                'password' => Hash::make($validatedData['admin_password']),
                'is_active' => true,
                'user_type' => 'Contratista',
            ]);
            $contratistaAdminRole = Role::where('name', 'Contratista_Admin')->firstOrFail();
            $user->roles()->attach($contratistaAdminRole);
            $contratista = Contratista::create([
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
                'tipo_inscripcion' => $this->tipoRegistro === 'CONTRATISTA' ? 'Contratista' : 'Subcontratista',
                'rango_cantidad_trabajadores_id' => $validatedData['rango_cantidad_trabajadores_id'],
                'mutualidad_id' => $validatedData['mutualidad_id'],
                'rep_legal_nombres' => $validatedData['rep_legal_nombres'],
                'rep_legal_apellido_paterno' => $validatedData['rep_legal_apellido_paterno'],
                'rep_legal_apellido_materno' => $validatedData['rep_legal_apellido_materno'],
                'rep_legal_rut' => $validatedData['rep_legal_rut'],
                'rep_legal_telefono' => $validatedData['rep_legal_telefono'],
                'rep_legal_email' => $validatedData['rep_legal_email'],
                'is_active' => false,
                'estado_plataforma' => 'Pendiente de Aprobacion',
                'admin_user_id' => $user->id,
            ]);
            $user->contratista_id = $contratista->id;
            $user->save();

            $estadoSolicitud = 'PENDIENTE';
            if ($this->tipoRegistro === 'SUBCONTRATISTA') {
                $estadoSolicitud = 'PENDIENTE_VINCULACION_CONTRATISTA';
            }

            SolicitudVinculacion::create([
                'contratista_id' => $contratista->id,
                'tipo_solicitud' => $this->tipoRegistro,
                'mandante_id' => $this->mandanteSeleccionado,
                'contratista_padre_id' => $this->contratistaPadreSeleccionado,
                'estado' => $estadoSolicitud,
            ]);
            DB::commit();
            if ($this->isAdminMode) {
                session()->flash('message', 'Nueva solicitud creada y dejada en estado PENDIENTE.');
                $this->redirect(route('gestion.solicitudes-vinculacion'), navigate: true);
            } else {
                $this->pasoActual = 4;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error_general', 'Ocurrió un error inesperado al procesar su solicitud. Por favor, inténtelo de nuevo. Error: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $layout = $this->isAdminMode ? 'layouts.app' : 'layouts.guest';
        return view('livewire.publico.formulario-registro-contratista')->layout($layout);
    }
}