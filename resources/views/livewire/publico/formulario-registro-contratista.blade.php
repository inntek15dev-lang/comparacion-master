<div>
    <div class="sm:mx-auto sm:w-full sm:max-w-7xl">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
            Registro de Nueva Empresa
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
            Complete los siguientes pasos para enviar su solicitud de registro.
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-7xl">
        <div class="bg-white dark:bg-gray-800 py-8 px-4 shadow-lg sm:rounded-lg sm:px-10">
            
            @if (session()->has('error_general'))
                <div class="mb-4 px-4 py-3 bg-red-100 border border-red-400 text-red-700 rounded-md dark:bg-red-700 dark:text-red-100 dark:border-red-600">
                    {{ session('error_general') }}
                </div>
            @endif

            @if ($pasoActual === 3)
                <form wire:submit.prevent="enviarSolicitud">
                    <h3 class="text-center text-xl font-medium text-gray-800 dark:text-gray-200 mb-6">Paso 3: Complete los datos de su empresa</h3>

                    <div class="space-y-8 max-h-[70vh] overflow-y-auto pr-4">
                        
                        <!-- Sección Datos Empresa -->
                        <div>
                            <div class="bg-black text-white font-bold p-2 text-center uppercase tracking-wider">
                                DATOS EMPRESA
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                <div>
                                    <label for="razon_social" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Razón Social <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model.defer="razon_social" id="razon_social" class="input-field @error('razon_social') input-error @enderror">
                                    @error('razon_social') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="rut_contratista" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">NIT Empresa <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model.defer="rut_contratista" id="rut_contratista" placeholder="Ej: 900123456-7" class="input-field @error('rut_contratista') input-error @enderror">
                                    @error('rut_contratista') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="nombre_fantasia" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Nombre Comercial <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model.defer="nombre_fantasia" id="nombre_fantasia" class="input-field @error('nombre_fantasia') input-error @enderror">
                                    @error('nombre_fantasia') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="email_empresa" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Email Empresa <span class="text-red-500">*</span></label>
                                    <input type="email" wire:model.defer="email_empresa" id="email_empresa" class="input-field @error('email_empresa') input-error @enderror">
                                    @error('email_empresa') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="telefono_empresa" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Teléfono Empresa <span class="text-red-500">*</span></label>
                                    <input type="tel" wire:model.defer="telefono_empresa" id="telefono_empresa" class="input-field @error('telefono_empresa') input-error @enderror">
                                    @error('telefono_empresa') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="email_empresa_confirmation" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Confirmar Email Empresa <span class="text-red-500">*</span></label>
                                    <input type="email" wire:model.defer="email_empresa_confirmation" id="email_empresa_confirmation" class="input-field">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
                                <div>
                                    <label for="tipo_empresa_legal_id" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Tipo Empresa Legal <span class="text-red-500">*</span></label>
                                    <select wire:model.defer="tipo_empresa_legal_id" id="tipo_empresa_legal_id" class="input-field @error('tipo_empresa_legal_id') input-error @enderror">
                                        <option value="">Seleccione...</option>
                                        @foreach ($tiposEmpresaLegal as $tipo)
                                            <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('tipo_empresa_legal_id') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="rubro_id" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Actividad Económica <span class="text-red-500">*</span></label>
                                    <select wire:model.defer="rubro_id" id="rubro_id" class="input-field @error('rubro_id') input-error @enderror">
                                        <option value="">Seleccione...</option>
                                        @foreach ($rubros as $rubro)
                                            <option value="{{ $rubro->id }}">{{ $rubro->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('rubro_id') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="mutualidad_id" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">ARL <span class="text-red-500">*</span></label>
                                    <select wire:model.defer="mutualidad_id" id="mutualidad_id" class="input-field @error('mutualidad_id') input-error @enderror">
                                        <option value="">Seleccione...</option>
                                        @foreach ($mutualidades as $mutual)
                                            <option value="{{ $mutual->id }}">{{ $mutual->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('mutualidad_id') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="rango_cantidad_trabajadores_id" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Rango Empleados <span class="text-red-500">*</span></label>
                                    <select wire:model.defer="rango_cantidad_trabajadores_id" id="rango_cantidad_trabajadores_id" class="input-field @error('rango_cantidad_trabajadores_id') input-error @enderror">
                                        <option value="">Seleccione...</option>
                                        @foreach ($rangosCantidad as $rango)
                                            <option value="{{ $rango->id }}">{{ $rango->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('rango_cantidad_trabajadores_id') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Sección Ubicación (sin título) -->
                        <div>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                <div>
                                    <label for="direccion_calle" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">DIRECCION <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model.defer="direccion_calle" id="direccion_calle" class="input-field @error('direccion_calle') input-error @enderror">
                                    @error('direccion_calle') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="direccion_numero" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">BARRIO <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model.defer="direccion_numero" id="direccion_numero" class="input-field @error('direccion_numero') input-error @enderror">
                                    @error('direccion_numero') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="selected_region_id_contratista" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Departamento <span class="text-red-500">*</span></label>
                                    <select wire:model.live="selected_region_id_contratista" id="selected_region_id_contratista" class="input-field @error('selected_region_id_contratista') input-error @enderror">
                                        <option value="">Seleccione Departamento...</option>
                                        @foreach ($regiones as $region)
                                            <option value="{{ $region->id }}">{{ $region->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('selected_region_id_contratista') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="comuna_id" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Municipio <span class="text-red-500">*</span></label>
                                    <select wire:model.defer="comuna_id" id="comuna_id" class="input-field @error('comuna_id') input-error @enderror" @if(empty($selected_region_id_contratista) || $comunasDisponiblesContratista->isEmpty()) disabled @endif>
                                        <option value="">Seleccione Municipio...</option>
                                        @foreach ($comunasDisponiblesContratista as $comuna)
                                            <option value="{{ $comuna->id }}">{{ $comuna->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('comuna_id') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Sección Representante Legal -->
                        <div>
                            <div class="bg-black text-white font-bold p-2 text-center uppercase tracking-wider">
                                DATOS REPRESENTANTE LEGAL
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                                <div>
                                    <label for="rep_legal_nombres" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Nombres <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model.defer="rep_legal_nombres" id="rep_legal_nombres" class="input-field @error('rep_legal_nombres') input-error @enderror">
                                    @error('rep_legal_nombres') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="rep_legal_apellido_paterno" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Primer Apellido <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model.defer="rep_legal_apellido_paterno" id="rep_legal_apellido_paterno" class="input-field @error('rep_legal_apellido_paterno') input-error @enderror">
                                    @error('rep_legal_apellido_paterno') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="rep_legal_apellido_materno" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Segundo Apellido <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model.defer="rep_legal_apellido_materno" id="rep_legal_apellido_materno" class="input-field @error('rep_legal_apellido_materno') input-error @enderror">
                                    @error('rep_legal_apellido_materno') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="rep_legal_rut" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Numero de cedula/identificacion<span class="text-red-500">*</span></label>
                                    <input type="text" wire:model.defer="rep_legal_rut" id="rep_legal_rut" placeholder="Ej: 900123456-7" class="input-field @error('rep_legal_rut') input-error @enderror">
                                    @error('rep_legal_rut') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="rep_legal_email" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Email <span class="text-red-500">*</span></label>
                                    <input type="email" wire:model.defer="rep_legal_email" id="rep_legal_email" class="input-field @error('rep_legal_email') input-error @enderror">
                                    @error('rep_legal_email') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="rep_legal_telefono" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Teléfono <span class="text-red-500">*</span></label>
                                    <input type="tel" wire:model.defer="rep_legal_telefono" id="rep_legal_telefono" class="input-field @error('rep_legal_telefono') input-error @enderror">
                                    @error('rep_legal_telefono') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Sección Datos de Usuario del Sistema -->
                        <div>
                            <div class="bg-black text-white font-bold p-2 text-center uppercase tracking-wider">
                                DATOS USUARIO DEL SISTEMA
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                                <div class="lg:col-span-2">
                                    <label for="admin_name" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Nombre y apellidos<span class="text-red-500">*</span></label>
                                    <input type="text" wire:model.defer="admin_name" id="admin_name" class="input-field @error('admin_name') input-error @enderror" placeholder="Ej: Juan Alberto Pérez González">
                                    @error('admin_name') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div></div>
                                <div>
                                    <label for="admin_rut_usuario" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Numero de cedula/identificacion<span class="text-red-500">*</span></label>
                                    <input type="text" wire:model.defer="admin_rut_usuario" id="admin_rut_usuario" placeholder="Ej: 900123456-7" class="input-field @error('admin_rut_usuario') input-error @enderror">
                                    @error('admin_rut_usuario') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="admin_email" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Email de Acceso (será su usuario) <span class="text-red-500">*</span></label>
                                    <input type="email" wire:model.defer="admin_email" id="admin_email" class="input-field @error('admin_email') input-error @enderror">
                                    @error('admin_email') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="admin_email_confirmation" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Confirmar Email de Acceso <span class="text-red-500">*</span></label>
                                    <input type="email" wire:model.defer="admin_email_confirmation" id="admin_email_confirmation" class="input-field">
                                </div>
                                <div>
                                    <label for="admin_password" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Contraseña <span class="text-red-500">*</span></label>
                                    <input type="password" id="admin_password" 
                                           wire:model.live.debounce.300ms="admin_password"
                                           @focus="$el.scrollIntoView({ behavior: 'smooth', block: 'center' })"
                                           class="input-field @error('admin_password') input-error @enderror">
                                    @error('admin_password') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="admin_password_confirmation" class="block text-sm font-medium text-gray-800 mb-1 bg-indigo-100 px-2 py-1 rounded-md">Confirmar Contraseña <span class="text-red-500">*</span></label>
                                    <input type="password" wire:model.defer="admin_password_confirmation" id="admin_password_confirmation" class="input-field">
                                </div>

                                <!-- Bloque de validación de contraseña en tiempo real -->
                                @if (!empty($admin_password))
                                <div class="mt-2 p-3 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-200 dark:border-gray-600">
                                    <ul class="space-y-1 text-sm">
                                        <li class="flex items-center {{ $passwordValidationRules['length'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            @if($passwordValidationRules['length'])
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            @else
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            @endif
                                            <span>8 a 12 caracteres</span>
                                        </li>
                                        <li class="flex items-center {{ $passwordValidationRules['uppercase'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            @if($passwordValidationRules['uppercase'])
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            @else
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            @endif
                                            <span>Al menos una mayúscula (A-Z)</span>
                                        </li>
                                        <li class="flex items-center {{ $passwordValidationRules['lowercase'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            @if($passwordValidationRules['lowercase'])
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            @else
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            @endif
                                            <span>Al menos una minúscula (a-z)</span>
                                        </li>
                                        <li class="flex items-center {{ $passwordValidationRules['number'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            @if($passwordValidationRules['number'])
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            @else
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            @endif
                                            <span>Al menos un número (0-9)</span>
                                        </li>
                                        <li class="flex items-center {{ $passwordValidationRules['special'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            @if($passwordValidationRules['special'])
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            @else
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            @endif
                                            <span>Al menos un caracter especial (!, @, #, $, etc.)</span>
                                        </li>
                                    </ul>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <button type="submit" class="btn-primary w-full sm:w-auto">
                            Enviar Solicitud de Registro
                        </button>
                    </div>
                </form>
            @endif

            @if ($pasoActual === 4)
                <div class="text-center space-y-4">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
                        <svg class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-gray-200">¡Solicitud Enviada Exitosamente!</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Hemos recibido sus datos correctamente. Su solicitud será revisada por un administrador.
                        Recibirá una notificación por correo electrónico una vez que su cuenta sea aprobada y activada.
                    </p>
                    <div class="pt-4">
                        <a href="{{ route('login') }}" class="btn-secondary">Volver a la página de inicio</a>
                    </div>
                </div>
            @endif

        </div>
    </div>

    @push('styles')
    <style>
        .input-field { @apply mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200; }
        .input-error { @apply border-red-500 dark:border-red-500; }
        .error-message { @apply text-red-500 text-xs mt-1; }
        .btn-primary { @apply px-6 py-3 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed; }
        .btn-secondary { @apply px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150; }
        .btn-paso { @apply w-full md:w-1/2 flex flex-col items-center justify-center p-6 border-2 border-gray-300 dark:border-gray-600 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all; }
    </style>
    @endpush
    
</div>