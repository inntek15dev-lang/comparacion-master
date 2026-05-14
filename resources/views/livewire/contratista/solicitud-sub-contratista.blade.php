<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Solicitar Sub-Contratista') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8" style="width: 95%; max-width: 98%;">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                
                @if (session()->has('message'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                    <p>{{ session('message') }}</p>
                </div>
                @endif
                @if (session()->has('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p>{{ session('error') }}</p>
                </div>
                @endif

                @if ($solicitudEnviada)
                <!-- Mensaje de éxito -->
                <div class="text-center py-12">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                        <svg class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-2">¡Solicitud Enviada!</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-lg mx-auto">
                        La solicitud de sub-contratista ha sido enviada. Un administrador del Principal la revisará y asignará las vinculaciones correspondientes.
                    </p>
                    <button wire:click="nuevaSolicitud" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Solicitar Otro Sub-Contratista
                    </button>
                </div>
                @else
                <!-- Formulario -->
                <form wire:submit.prevent="enviarSolicitud">
                    <!-- Info del contratista actual -->
                    <div class="mb-6 p-4 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg border border-indigo-200 dark:border-indigo-700">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <div>
                                <p class="text-sm text-indigo-700 dark:text-indigo-300">
                                    <strong>Mi Empresa:</strong> {{ $contratistaActual->razon_social ?? 'No definida' }}
                                </p>
                                <p class="text-xs text-indigo-600 dark:text-indigo-400">
                                    Usted está solicitando un nuevo sub-contratista para su empresa.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Selectores de Jerarquía y Principal -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Selector de Padre -->
                        <div class="bg-yellow-50 dark:bg-yellow-900/30 p-4 rounded-lg border border-yellow-200 dark:border-yellow-700">
                            <label for="contratista_padre_id" class="block text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-2">
                                <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                ¿De quién será sub-contratista? <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="contratista_padre_id" id="contratista_padre_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
                                <option value="{{ $contratistaActual->id }}">{{ $contratistaActual->razon_social }} (Mi empresa - Nivel 1)</option>
                                @foreach($subContratistasExistentes as $sub)
                                    <option value="{{ $sub['id'] }}">{{ $sub['razon_social'] }} ({{ $sub['nivel'] }})</option>
                                @endforeach
                            </select>
                            @error('contratista_padre_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-2">
                                Entidad padre directa del nuevo sub-contratista.
                            </p>
                        </div>

                        <!-- Selector de Mandante (Principal) -->
                        <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-lg border border-blue-200 dark:border-blue-700">
                            <label for="mandante_id" class="block text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
                                <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                Principal (Mandante) <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="mandante_id" id="mandante_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
                                <option value="">Seleccione Principal...</option>
                                @foreach($mandantesDisponibles as $mandante)
                                    <option value="{{ $mandante['id'] }}">{{ $mandante['razon_social'] }}</option>
                                @endforeach
                            </select>
                            @error('mandante_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            <p class="text-xs text-blue-700 dark:text-blue-300 mt-2">
                                Principal donde se prestará el servicio.
                            </p>
                        </div>
                    </div>

                    <div class="space-y-8 max-h-[60vh] overflow-y-auto pr-4">
                        
                        <!-- Sección Datos Empresa -->
                        <div>
                            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 text-white font-bold p-3 text-center uppercase tracking-wider rounded-t-lg">
                                DATOS DE LA EMPRESA SUB-CONTRATISTA
                            </div>
                            <div class="border border-gray-200 dark:border-gray-600 border-t-0 rounded-b-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="razon_social" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Razón Social <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model.defer="razon_social" id="razon_social" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 @error('razon_social') border-red-500 @enderror">
                                        @error('razon_social') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="rut_contratista" class="block text-sm font-medium text-gray-700 dark:text-gray-300">NIT Empresa <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model.defer="rut_contratista" id="rut_contratista" placeholder="Ej: 900123456-7" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 @error('rut_contratista') border-red-500 @enderror">
                                        @error('rut_contratista') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="nombre_fantasia" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre Comercial <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model.defer="nombre_fantasia" id="nombre_fantasia" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 @error('nombre_fantasia') border-red-500 @enderror">
                                        @error('nombre_fantasia') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="email_empresa" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email Empresa <span class="text-red-500">*</span></label>
                                        <input type="email" wire:model.defer="email_empresa" id="email_empresa" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 @error('email_empresa') border-red-500 @enderror">
                                        @error('email_empresa') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="telefono_empresa" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono Empresa <span class="text-red-500">*</span></label>
                                        <input type="tel" wire:model.defer="telefono_empresa" id="telefono_empresa" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 @error('telefono_empresa') border-red-500 @enderror">
                                        @error('telefono_empresa') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
                                    <div>
                                        <label for="tipo_empresa_legal_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo Empresa Legal <span class="text-red-500">*</span></label>
                                        <select wire:model.defer="tipo_empresa_legal_id" id="tipo_empresa_legal_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 @error('tipo_empresa_legal_id') border-red-500 @enderror">
                                            <option value="">Seleccione...</option>
                                            @foreach ($tiposEmpresaLegal as $tipo)
                                                <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                            @endforeach
                                        </select>
                                        @error('tipo_empresa_legal_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="rubro_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Actividad Económica <span class="text-red-500">*</span></label>
                                        <select wire:model.defer="rubro_id" id="rubro_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 @error('rubro_id') border-red-500 @enderror">
                                            <option value="">Seleccione...</option>
                                            @foreach ($rubros as $rubro)
                                                <option value="{{ $rubro->id }}">{{ $rubro->nombre }}</option>
                                            @endforeach
                                        </select>
                                        @error('rubro_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="mutualidad_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">ARL <span class="text-red-500">*</span></label>
                                        <select wire:model.defer="mutualidad_id" id="mutualidad_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 @error('mutualidad_id') border-red-500 @enderror">
                                            <option value="">Seleccione...</option>
                                            @foreach ($mutualidades as $mutual)
                                                <option value="{{ $mutual->id }}">{{ $mutual->nombre }}</option>
                                            @endforeach
                                        </select>
                                        @error('mutualidad_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="rango_cantidad_trabajadores_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rango Empleados <span class="text-red-500">*</span></label>
                                        <select wire:model.defer="rango_cantidad_trabajadores_id" id="rango_cantidad_trabajadores_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 @error('rango_cantidad_trabajadores_id') border-red-500 @enderror">
                                            <option value="">Seleccione...</option>
                                            @foreach ($rangosCantidad as $rango)
                                                <option value="{{ $rango->id }}">{{ $rango->nombre }}</option>
                                            @endforeach
                                        </select>
                                        @error('rango_cantidad_trabajadores_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección Ubicación -->
                        <div>
                            <div class="bg-gradient-to-r from-gray-600 to-gray-700 text-white font-bold p-3 text-center uppercase tracking-wider rounded-t-lg">
                                UBICACIÓN
                            </div>
                            <div class="border border-gray-200 dark:border-gray-600 border-t-0 rounded-b-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="direccion_calle" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dirección <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model.defer="direccion_calle" id="direccion_calle" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 @error('direccion_calle') border-red-500 @enderror">
                                        @error('direccion_calle') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="direccion_numero" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Barrio <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model.defer="direccion_numero" id="direccion_numero" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 @error('direccion_numero') border-red-500 @enderror">
                                        @error('direccion_numero') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="selected_region_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Departamento <span class="text-red-500">*</span></label>
                                        <select wire:model.live="selected_region_id" id="selected_region_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 @error('selected_region_id') border-red-500 @enderror">
                                            <option value="">Seleccione Departamento...</option>
                                            @foreach ($regiones as $region)
                                                <option value="{{ $region->id }}">{{ $region->nombre }}</option>
                                            @endforeach
                                        </select>
                                        @error('selected_region_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="comuna_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Municipio <span class="text-red-500">*</span></label>
                                        <select wire:model.defer="comuna_id" id="comuna_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 @error('comuna_id') border-red-500 @enderror" @if(count($comunasDisponibles) == 0) disabled @endif>
                                            <option value="">Seleccione Municipio...</option>
                                            @foreach ($comunasDisponibles as $comuna)
                                                <option value="{{ $comuna->id }}">{{ $comuna->nombre }}</option>
                                            @endforeach
                                        </select>
                                        @error('comuna_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección Representante Legal -->
                        <div>
                            <div class="bg-gradient-to-r from-green-600 to-green-700 text-white font-bold p-3 text-center uppercase tracking-wider rounded-t-lg">
                                REPRESENTANTE LEGAL
                            </div>
                            <div class="border border-gray-200 dark:border-gray-600 border-t-0 rounded-b-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div>
                                        <label for="rep_legal_nombres" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombres <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model.defer="rep_legal_nombres" id="rep_legal_nombres" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 @error('rep_legal_nombres') border-red-500 @enderror">
                                        @error('rep_legal_nombres') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="rep_legal_apellido_paterno" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Primer Apellido <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model.defer="rep_legal_apellido_paterno" id="rep_legal_apellido_paterno" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 @error('rep_legal_apellido_paterno') border-red-500 @enderror">
                                        @error('rep_legal_apellido_paterno') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="rep_legal_apellido_materno" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Segundo Apellido <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model.defer="rep_legal_apellido_materno" id="rep_legal_apellido_materno" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 @error('rep_legal_apellido_materno') border-red-500 @enderror">
                                        @error('rep_legal_apellido_materno') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="rep_legal_rut" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Número de Cédula <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model.defer="rep_legal_rut" id="rep_legal_rut" placeholder="Ej: 900123456-7" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 @error('rep_legal_rut') border-red-500 @enderror">
                                        @error('rep_legal_rut') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="rep_legal_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email <span class="text-red-500">*</span></label>
                                        <input type="email" wire:model.defer="rep_legal_email" id="rep_legal_email" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 @error('rep_legal_email') border-red-500 @enderror">
                                        @error('rep_legal_email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="rep_legal_telefono" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono <span class="text-red-500">*</span></label>
                                        <input type="tel" wire:model.defer="rep_legal_telefono" id="rep_legal_telefono" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200 @error('rep_legal_telefono') border-red-500 @enderror">
                                        @error('rep_legal_telefono') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Botón Enviar -->
                    <div class="mt-8 flex justify-end">
                        <button type="submit" class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span wire:loading.remove wire:target="enviarSolicitud">Enviar Solicitud de Sub-Contratista</span>
                            <span wire:loading wire:target="enviarSolicitud">Procesando...</span>
                        </button>
                    </div>
                </form>
                @endif

            </div>
        </div>
    </div>
</div>
