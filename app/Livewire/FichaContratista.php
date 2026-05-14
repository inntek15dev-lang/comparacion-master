<?php

namespace App\Livewire;

use App\Models\Contratista;
use App\Models\User;
use App\Models\TipoEmpresaLegal;
use App\Models\Rubro;
use App\Models\RangoCantidadTrabajadores;
use App\Models\Mutualidad;
use App\Models\Region;
use App\Models\Comuna;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

#[Layout('layouts.app')]
class FichaContratista extends Component
{
    public Contratista $contratista;
    public User $adminUser;

    // Propiedades del Contratista (editables por el contratista)
    public $nombre_fantasia;
    public $direccion_calle, $direccion_numero, $comuna_id, $selected_region_id;
    public $telefono_empresa, $email_empresa_contratista;

    // Propiedades del Contratista (informativas)
    public $razon_social_info, $rut_contratista_info, $tipo_inscripcion_info;
    public $tipo_empresa_legal_info, $rubro_info, $rango_cantidad_info, $mutualidad_info;

    // Propiedades del Representante Legal (editables)
    public $rep_legal_nombres, $rep_legal_apellido_paterno, $rep_legal_apellido_materno;
    public $rep_legal_rut, $rep_legal_telefono, $rep_legal_email;

    // Propiedades para editar datos del propio Usuario Administrador
    public $admin_user_name_actual;
    public $admin_email_actual;
    public $admin_email_actual_confirmation; // NUEVA PROPIEDAD PARA CONFIRMACIÓN
    public $admin_current_password, $admin_new_password, $admin_new_password_confirmation;

    // Listas para Selects
    public $regiones = [];
    public $comunasDisponibles = [];

    // Para el mensaje junto al botón
    public $formStatusMessage = '';
    public $formStatusType = '';

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
        $this->adminUser = Auth::user();
        if (!$this->adminUser->contratista_id) {
            session()->flash('error', 'Usuario no está asociado a una empresa contratista.');
            return redirect()->route('dashboard');
        }

        $this->contratista = Contratista::with([
            'comuna.region', 'tipoEmpresaLegal', 'rubro',
            'rangoCantidadTrabajadores', 'mutualidad'
        ])->findOrFail($this->adminUser->contratista_id);

        $this->nombre_fantasia = $this->contratista->nombre_fantasia;
        $this->direccion_calle = $this->contratista->direccion_calle;
        $this->direccion_numero = $this->contratista->direccion_numero;
        if ($this->contratista->comuna_id) {
            $this->selected_region_id = $this->contratista->comuna->region_id;
        }
        $this->comuna_id = $this->contratista->comuna_id;
        $this->telefono_empresa = $this->contratista->telefono_empresa;
        $this->email_empresa_contratista = $this->contratista->email_empresa;

        $this->razon_social_info = $this->contratista->razon_social;
        $this->rut_contratista_info = $this->contratista->rut;
        $this->tipo_inscripcion_info = $this->contratista->tipo_inscripcion;
        $this->tipo_empresa_legal_info = $this->contratista->tipoEmpresaLegal?->nombre;
        $this->rubro_info = $this->contratista->rubro?->nombre;
        $this->rango_cantidad_info = $this->contratista->rangoCantidadTrabajadores?->nombre;
        $this->mutualidad_info = $this->contratista->mutualidad?->nombre;

        $this->rep_legal_nombres = $this->contratista->rep_legal_nombres;
        $this->rep_legal_apellido_paterno = $this->contratista->rep_legal_apellido_paterno;
        $this->rep_legal_apellido_materno = $this->contratista->rep_legal_apellido_materno;
        $this->rep_legal_rut = $this->contratista->rep_legal_rut;
        $this->rep_legal_telefono = $this->contratista->rep_legal_telefono;
        $this->rep_legal_email = $this->contratista->rep_legal_email;

        $this->admin_user_name_actual = $this->adminUser->name;
        $this->admin_email_actual = $this->adminUser->email;
        // No inicializamos la confirmación para obligar al usuario a escribirla si cambia el email

        $this->regiones = Region::where('is_active', true)->orderBy('nombre')->get();
        if ($this->selected_region_id) {
            $this->comunasDisponibles = Comuna::where('region_id', $this->selected_region_id)
                                            ->where('is_active', true)->orderBy('nombre')->get();
        }
    }

    public function rules()
    {
        return [
            'nombre_fantasia' => 'required|string|max:255',
            'direccion_calle' => 'required|string|max:255',
            'direccion_numero' => 'required|string|max:50',
            'selected_region_id' => 'required|exists:regiones,id',
            'comuna_id' => 'required|exists:comunas,id',
            'telefono_empresa' => 'required|string|max:20',
            'email_empresa_contratista' => ['required', 'email', 'max:255', Rule::unique('contratistas', 'email_empresa')->ignore($this->contratista->id)],

            'rep_legal_nombres' => 'required|string|max:255',
            'rep_legal_apellido_paterno' => 'required|string|max:255',
            'rep_legal_apellido_materno' => 'required|string|max:255',
            'rep_legal_rut' => ['required', 'string', 'max:12'/*, new RutRule*/],
            'rep_legal_telefono' => 'required|string|max:20',
            'rep_legal_email' => 'required|email|max:255',

            // REGLA ACTUALIZADA: Se agregó 'confirmed'
            'admin_email_actual' => ['required', 'email', 'max:255', 'confirmed', Rule::unique('users', 'email')->ignore($this->adminUser->id)],
            
            'admin_current_password' => 'nullable|string|current_password',
            'admin_new_password' => ['nullable', 'required_with:admin_current_password', 'confirmed', Password::min(8)->max(12)->mixedCase()->numbers()->symbols()],
        ];
    }

    protected $messages = [
        'email_empresa_contratista.unique' => 'El email de la empresa ya está en uso por otro contratista.',
        'admin_email_actual.unique' => 'El nuevo email de administrador ya está en uso.',
        'admin_email_actual.confirmed' => 'La confirmación del email de acceso no coincide.', // MENSAJE PERSONALIZADO
        'admin_current_password.current_password' => 'La contraseña actual no es correcta.',
        'admin_new_password.required_with' => 'La nueva contraseña es requerida si ingresa la contraseña actual.',
         '*.required' => 'Este campo es obligatorio.',
    ];

    public function validationAttributes()
    {
        return [
            'nombre_fantasia' => 'nombre comercial',
            'direccion_numero' => 'número de dirección',
            'selected_region_id' => 'departamento',
            'comuna_id' => 'municipio',
            'telefono_empresa' => 'teléfono de la empresa',
            'email_empresa_contratista' => 'email de la empresa',
            'rep_legal_apellido_paterno' => 'primer apellido',
            'rep_legal_apellido_materno' => 'segundo apellido',
            'rep_legal_rut' => 'NIT del representante legal',
            'rep_legal_telefono' => 'teléfono del representante legal',
            'rep_legal_email' => 'email del representante legal',
            'admin_new_password' => 'nueva contraseña',
            'admin_email_actual' => 'email de acceso',
        ];
    }

    public function updatedSelectedRegionId($region_id)
    {
        if (!empty($region_id)) {
            $this->comunasDisponibles = Comuna::where('region_id', $region_id)->where('is_active', true)->orderBy('nombre')->get();
        } else {
            $this->comunasDisponibles = [];
        }
        $this->comuna_id = null;
    }

    public function updatedAdminNewPassword($value)
    {
        $this->passwordValidationRules['length'] = Str::length($value) >= 8 && Str::length($value) <= 12;
        $this->passwordValidationRules['uppercase'] = Str::match('/[A-Z]/', $value);
        $this->passwordValidationRules['lowercase'] = Str::match('/[a-z]/', $value);
        $this->passwordValidationRules['number'] = Str::match('/[0-9]/', $value);
        $this->passwordValidationRules['special'] = Str::match('/[\W_]/', $value);
    }

    public function updateFicha()
    {
        $this->resetFormStatus();

        try {
            $validatedData = $this->validate();

            DB::transaction(function () use ($validatedData) {
                $this->contratista->nombre_fantasia = $this->nombre_fantasia;
                $this->contratista->direccion_calle = $this->direccion_calle;
                $this->contratista->direccion_numero = $this->direccion_numero;
                $this->contratista->comuna_id = $this->comuna_id;
                $this->contratista->telefono_empresa = $this->telefono_empresa;
                $this->contratista->email_empresa = $this->email_empresa_contratista;

                $this->contratista->rep_legal_nombres = $this->rep_legal_nombres;
                $this->contratista->rep_legal_apellido_paterno = $this->rep_legal_apellido_paterno;
                $this->contratista->rep_legal_apellido_materno = $this->rep_legal_apellido_materno;
                $this->contratista->rep_legal_rut = $this->rep_legal_rut;
                $this->contratista->rep_legal_telefono = $this->rep_legal_telefono;
                $this->contratista->rep_legal_email = $this->rep_legal_email;
                $this->contratista->save();

                $userChanged = false;
                if ($this->adminUser->email !== $this->admin_email_actual) {
                    $this->adminUser->email = $this->admin_email_actual;
                    $this->adminUser->email_verified_at = null;
                    $userChanged = true;
                }

                if (!empty($this->admin_current_password) && !empty($this->admin_new_password)) {
                    $this->adminUser->password = Hash::make($this->admin_new_password);
                    $userChanged = true;
                }

                if ($userChanged) {
                    $this->adminUser->save();
                }

                $this->admin_current_password = null;
                $this->admin_new_password = null;
                $this->admin_new_password_confirmation = null;
                // Limpiamos también la confirmación del email
                $this->admin_email_actual_confirmation = null;

                $this->formStatusMessage = '¡Ficha actualizada correctamente!';
                $this->formStatusType = 'success';
                session()->flash('message', 'Ficha de la empresa actualizada.');
            });
        } catch (ValidationException $e) {
            $this->formStatusMessage = 'Faltan campos obligatorios o hay errores de validación.';
            $this->formStatusType = 'error';
        } catch (\Exception $e) {
            $this->formStatusMessage = 'Ocurrió un error inesperado al actualizar.';
            $this->formStatusType = 'error';
            session()->flash('error', 'Error inesperado: ' . $e->getMessage());
        }
    }

    public function resetFormStatus()
    {
        $this->formStatusMessage = '';
        $this->formStatusType = '';
    }

    public function updated($propertyName)
    {
        if ($propertyName === 'admin_new_password') {
            $this->updatedAdminNewPassword($this->admin_new_password);
        }

        $nonResettableProperties = [
            'formStatusMessage', 'formStatusType', 'contratista', 'adminUser', 
            'regiones', 'comunasDisponibles'
        ];

        if (!in_array($propertyName, $nonResettableProperties)) {
            $this->resetFormStatus();
        }
    }

    public function render()
    {
        return view('livewire.ficha-contratista');
    }
}