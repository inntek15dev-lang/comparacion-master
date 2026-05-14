<div>
    {{-- El slot del header se mantiene vacío para eliminar cualquier título superior --}}
    <x-slot name="header">
    </x-slot>

    {{-- Se elimina el padding superior para que el contenido se ajuste al contenedor padre --}}
    <div>
        <div class="max-w-4xl mx-auto">
            <div class="bg-white dark:bg-gray-800">

                <!-- Título Centrado y Único -->
                <h1 class="text-2xl font-bold text-center text-gray-800 dark:text-gray-200 mb-10">
                    FICHA EMPRESA
                </h1>

                @if (session()->has('message'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                         class="mb-4 px-4 py-3 bg-green-100 border-2 border-green-600 text-green-800 rounded-md dark:bg-green-700 dark:text-green-100 dark:border-green-400">
                        {{ session('message') }}
                    </div>
                @endif
                @if (session()->has('error'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                        class="mb-4 px-4 py-3 bg-red-100 border-2 border-red-600 text-red-800 rounded-md dark:bg-red-700 dark:text-red-100 dark:border-red-400">
                        {{ session('error') }}
                    </div>
                @endif

                <form wire:submit.prevent="updateFicha">
                    <div class="space-y-8">

                        <!-- Sección Datos Empresa -->
                        <div>
                            <div class="bg-black text-white font-bold p-2 text-center uppercase tracking-wider">
                                DATOS EMPRESA
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">Razón Social</label>
                                    <input type="text" value="{{ $razon_social_info }}" class="mt-1 block w-full px-3 py-2 bg-gray-100 border-2 border-gray-600 rounded-md shadow-sm focus:outline-none sm:text-sm text-gray-700 font-semibold cursor-not-allowed dark:bg-gray-700 dark:border-gray-500 dark:text-gray-300" disabled>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">RUT/NIT/RUC/CNPJ Empresa</label>
                                    <input type="text" value="{{ $rut_contratista_info }}" class="mt-1 block w-full px-3 py-2 bg-gray-100 border-2 border-gray-600 rounded-md shadow-sm focus:outline-none sm:text-sm text-gray-700 font-semibold cursor-not-allowed dark:bg-gray-700 dark:border-gray-500 dark:text-gray-300" disabled>
                                </div>
                                <div>
                                    <label for="nombre_fantasia" class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">Nombre Comercial <span class="text-red-600 font-bold">*</span></label>
                                    <input type="text" wire:model.lazy="nombre_fantasia" id="nombre_fantasia" class="mt-1 block w-full px-3 py-2 bg-white border-2 border-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 dark:border-gray-100 @error('nombre_fantasia') border-red-600 dark:border-red-500 @enderror">
                                    @error('nombre_fantasia') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="email_empresa_contratista" class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">Email Empresa <span class="text-red-600 font-bold">*</span></label>
                                    <input type="email" wire:model.lazy="email_empresa_contratista" id="email_empresa_contratista" class="mt-1 block w-full px-3 py-2 bg-white border-2 border-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 dark:border-gray-100 @error('email_empresa_contratista') border-red-600 dark:border-red-500 @enderror">
                                    @error('email_empresa_contratista') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="telefono_empresa" class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">Teléfono Empresa <span class="text-red-600 font-bold">*</span></label>
                                    <input type="tel" wire:model.lazy="telefono_empresa" id="telefono_empresa" class="mt-1 block w-full px-3 py-2 bg-white border-2 border-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 dark:border-gray-100 @error('telefono_empresa') border-red-600 dark:border-red-500 @enderror">
                                    @error('telefono_empresa') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                 <div>
                                    <label class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">Tipo Inscripción</label>
                                    <input type="text" value="{{ $tipo_inscripcion_info }}" class="mt-1 block w-full px-3 py-2 bg-gray-100 border-2 border-gray-600 rounded-md shadow-sm focus:outline-none sm:text-sm text-gray-700 font-semibold cursor-not-allowed dark:bg-gray-700 dark:border-gray-500 dark:text-gray-300" disabled>
                                </div>
                            </div>
                        </div>

                        <!-- Sección Ubicación (sin título) -->
                        <div>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                <div>
                                    <label for="direccion_calle" class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">DIRECCION <span class="text-red-600 font-bold">*</span></label>
                                    <input type="text" wire:model.lazy="direccion_calle" id="direccion_calle" class="mt-1 block w-full px-3 py-2 bg-white border-2 border-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 dark:border-gray-100 @error('direccion_calle') border-red-600 dark:border-red-500 @enderror">
                                    @error('direccion_calle') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="direccion_numero" class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">BARRIO <span class="text-red-600 font-bold">*</span></label>
                                    <input type="text" wire:model.lazy="direccion_numero" id="direccion_numero" class="mt-1 block w-full px-3 py-2 bg-white border-2 border-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 dark:border-gray-100 @error('direccion_numero') border-red-600 dark:border-red-500 @enderror">
                                    @error('direccion_numero') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="selected_region_id" class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">Departamento <span class="text-red-600 font-bold">*</span></label>
                                    <select wire:model.live="selected_region_id" id="selected_region_id" class="mt-1 block w-full px-3 py-2 bg-white border-2 border-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 dark:border-gray-100 @error('selected_region_id') border-red-600 dark:border-red-500 @enderror">
                                        <option value="">Seleccione Departamento...</option>
                                        @foreach ($regiones as $region)
                                            <option value="{{ $region->id }}">{{ $region->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('selected_region_id') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="comuna_id" class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">Municipio <span class="text-red-600 font-bold">*</span></label>
                                    <select wire:model="comuna_id" id="comuna_id" class="mt-1 block w-full px-3 py-2 bg-white border-2 border-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 dark:border-gray-100 disabled:bg-gray-200 disabled:dark:bg-gray-600 disabled:text-gray-500 disabled:dark:text-gray-400 disabled:cursor-not-allowed disabled:border-gray-400 @error('comuna_id') border-red-600 dark:border-red-500 @enderror" @if(empty($selected_region_id)) disabled @endif>
                                        <option value="">Seleccione Municipio...</option>
                                        @foreach ($comunasDisponibles as $comuna)
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
                                    <label for="rep_legal_nombres" class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">Nombres <span class="text-red-600 font-bold">*</span></label>
                                    <input type="text" wire:model.lazy="rep_legal_nombres" id="rep_legal_nombres" class="mt-1 block w-full px-3 py-2 bg-white border-2 border-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 dark:border-gray-100 @error('rep_legal_nombres') border-red-600 dark:border-red-500 @enderror">
                                    @error('rep_legal_nombres') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="rep_legal_apellido_paterno" class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">Primer Apellido <span class="text-red-600 font-bold">*</span></label>
                                    <input type="text" wire:model.lazy="rep_legal_apellido_paterno" id="rep_legal_apellido_paterno" class="mt-1 block w-full px-3 py-2 bg-white border-2 border-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 dark:border-gray-100 @error('rep_legal_apellido_paterno') border-red-600 dark:border-red-500 @enderror">
                                    @error('rep_legal_apellido_paterno') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="rep_legal_apellido_materno" class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">Segundo Apellido <span class="text-red-600 font-bold">*</span></label>
                                    <input type="text" wire:model.lazy="rep_legal_apellido_materno" id="rep_legal_apellido_materno" class="mt-1 block w-full px-3 py-2 bg-white border-2 border-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 dark:border-gray-100 @error('rep_legal_apellido_materno') border-red-600 dark:border-red-500 @enderror">
                                    @error('rep_legal_apellido_materno') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="rep_legal_rut" class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">Numero de cedula/identificacion<span class="text-red-600 font-bold">*</span></label>
                                    <input type="text" wire:model.lazy="rep_legal_rut" id="rep_legal_rut" placeholder="Ej: 900123456-7" class="mt-1 block w-full px-3 py-2 bg-white border-2 border-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 dark:border-gray-100 @error('rep_legal_rut') border-red-600 dark:border-red-500 @enderror">
                                    @error('rep_legal_rut') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="rep_legal_email" class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">Email <span class="text-red-600 font-bold">*</span></label>
                                    <input type="email" wire:model.lazy="rep_legal_email" id="rep_legal_email" class="mt-1 block w-full px-3 py-2 bg-white border-2 border-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 dark:border-gray-100 @error('rep_legal_email') border-red-600 dark:border-red-500 @enderror">
                                    @error('rep_legal_email') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="rep_legal_telefono" class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">Teléfono <span class="text-red-600 font-bold">*</span></label>
                                    <input type="tel" wire:model.lazy="rep_legal_telefono" id="rep_legal_telefono" class="mt-1 block w-full px-3 py-2 bg-white border-2 border-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 dark:border-gray-100 @error('rep_legal_telefono') border-red-600 dark:border-red-500 @enderror">
                                    @error('rep_legal_telefono') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Sección Datos de Usuario Administrador -->
                        <div>
                            <div class="bg-black text-white font-bold p-2 text-center uppercase tracking-wider">
                                DATOS USUARIO DEL SISTEMA
                            </div>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">Nombre Usuario</label>
                                    <input type="text" value="{{ $admin_user_name_actual }}" class="mt-1 block w-full px-3 py-2 bg-gray-100 border-2 border-gray-600 rounded-md shadow-sm focus:outline-none sm:text-sm text-gray-700 font-semibold cursor-not-allowed dark:bg-gray-700 dark:border-gray-500 dark:text-gray-300" disabled>
                                    <small class="text-xs text-gray-500 dark:text-gray-400">Para cambiar su nombre, contacte a ASEM.</small>
                                </div>
                                <div>
                                    <label for="admin_email_actual" class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">Mi Email de Acceso <span class="text-red-600 font-bold">*</span></label>
                                    <input type="email" wire:model.lazy="admin_email_actual" id="admin_email_actual" class="mt-1 block w-full px-3 py-2 bg-white border-2 border-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 dark:border-gray-100 @error('admin_email_actual') border-red-600 dark:border-red-500 @enderror">
                                    @error('admin_email_actual') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                
                                {{-- CAMPO DE CONFIRMACIÓN DE EMAIL --}}
                                <div class="md:col-start-2">
                                    <label for="admin_email_actual_confirmation" class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">Confirmar Email de Acceso <span class="text-red-600 font-bold">*</span></label>
                                    <input type="email" wire:model.lazy="admin_email_actual_confirmation" id="admin_email_actual_confirmation" class="mt-1 block w-full px-3 py-2 bg-white border-2 border-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 dark:border-gray-100">
                                </div>

                                <div class="md:col-span-2"><hr class="my-3 border-t-2 border-gray-900 dark:border-gray-100"></div>
                                <div>
                                    <label for="admin_current_password" class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">Contraseña Actual (para cambiar)</label>
                                    <input type="password" wire:model.lazy="admin_current_password" id="admin_current_password" class="mt-1 block w-full px-3 py-2 bg-white border-2 border-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 dark:border-gray-100 @error('admin_current_password') border-red-600 dark:border-red-500 @enderror" placeholder="Dejar en blanco si no cambia">
                                    @error('admin_current_password') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="admin_new_password" class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">Nueva Contraseña</label>
                                    <input type="password" wire:model.live.debounce.300ms="admin_new_password" id="admin_new_password" class="mt-1 block w-full px-3 py-2 bg-white border-2 border-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 dark:border-gray-100 @error('admin_new_password') border-red-600 dark:border-red-500 @enderror">
                                    @error('admin_new_password') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="admin_new_password_confirmation" class="block text-sm font-bold text-gray-900 mb-1 bg-indigo-100 px-2 py-1 rounded-md border-2 border-indigo-200">Confirmar Nueva Contraseña</label>
                                    <input type="password" wire:model.lazy="admin_new_password_confirmation" id="admin_new_password_confirmation" class="mt-1 block w-full px-3 py-2 bg-white border-2 border-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 dark:border-gray-100">
                                </div>
                                
                                <!-- Bloque de validación de contraseña en tiempo real -->
                                @if (!empty($admin_new_password))
                                <div class="md:col-start-2 mt-2 p-3 bg-gray-50 dark:bg-gray-700 rounded-md border-2 border-gray-900 dark:border-gray-100">
                                    <ul class="space-y-1 text-sm">
                                        <li class="flex items-center {{ $passwordValidationRules['length'] ? 'text-green-700 font-bold dark:text-green-400' : 'text-red-700 font-bold dark:text-red-400' }}">
                                            @if($passwordValidationRules['length'])
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                            @else
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            @endif
                                            <span>8 a 12 caracteres</span>
                                        </li>
                                        <li class="flex items-center {{ $passwordValidationRules['uppercase'] ? 'text-green-700 font-bold dark:text-green-400' : 'text-red-700 font-bold dark:text-red-400' }}">
                                            @if($passwordValidationRules['uppercase'])
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                            @else
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            @endif
                                            <span>Al menos una mayúscula (A-Z)</span>
                                        </li>
                                        <li class="flex items-center {{ $passwordValidationRules['lowercase'] ? 'text-green-700 font-bold dark:text-green-400' : 'text-red-700 font-bold dark:text-red-400' }}">
                                            @if($passwordValidationRules['lowercase'])
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                            @else
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            @endif
                                            <span>Al menos una minúscula (a-z)</span>
                                        </li>
                                        <li class="flex items-center {{ $passwordValidationRules['number'] ? 'text-green-700 font-bold dark:text-green-400' : 'text-red-700 font-bold dark:text-red-400' }}">
                                            @if($passwordValidationRules['number'])
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                            @else
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            @endif
                                            <span>Al menos un número (0-9)</span>
                                        </li>
                                        <li class="flex items-center {{ $passwordValidationRules['special'] ? 'text-green-700 font-bold dark:text-green-400' : 'text-red-700 font-bold dark:text-red-400' }}">
                                            @if($passwordValidationRules['special'])
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                            @else
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            @endif
                                            <span>Al menos un caracter especial (!, @, #, $, etc.)</span>
                                        </li>
                                    </ul>
                                </div>
                                @endif

                            </div>
                        </div>
                        
                        <div class="flex items-center justify-end mt-8 space-x-4">
                            @if ($formStatusMessage)
                                <span class="text-sm font-bold
                                    @if ($formStatusType === 'success') text-green-700 dark:text-green-400 @endif
                                    @if ($formStatusType === 'error') text-red-700 dark:text-red-400 @endif">
                                    {{ $formStatusMessage }}
                                </span>
                            @endif

                            <button type="submit" 
                                    wire:loading.attr="disabled" 
                                    wire:target="updateFicha"
                                    class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 border-2 border-indigo-800">
                                <span wire:loading.remove wire:target="updateFicha">
                                    Guardar Cambios en FICHA EMPRESA 
                                </span>
                                <span wire:loading wire:target="updateFicha">
                                    Guardando...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .error-message {
            @apply text-red-600 font-bold text-xs mt-1;
        }
    </style>
    @endpush

</div>