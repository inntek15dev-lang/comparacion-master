<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Mis Vinculaciones') }}
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

                <!-- Filtros -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <input wire:model.debounce.500ms="busqueda" type="text" 
                           class="form-input rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300" 
                           placeholder="Buscar por Principal, Lugar de Trabajo o N° Contrato...">
                </div>

                <!-- Info del Contratista -->
                <div class="mb-4 p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg">
                    <span class="text-sm text-indigo-700 dark:text-indigo-300">
                        <strong>Mi Empresa:</strong> {{ Auth::user()->contratista->razon_social ?? 'No definida' }}
                    </span>
                </div>

                <!-- Tabla de Vinculaciones -->
                <div class="overflow-x-auto rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-indigo-600 to-indigo-700">
                                <th class="border-r border-indigo-500 px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">#</th>
                                <th class="border-r border-indigo-500 px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">Principal</th>
                                <th class="border-r border-indigo-500 px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">Lugar de Trabajo</th>
                                <th class="border-r border-indigo-500 px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">Unidad Operativa</th>
                                <th class="border-r border-indigo-500 px-4 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider">N° Contrato</th>
                                <th class="border-r border-indigo-500 px-4 py-3 text-center text-xs font-semibold text-white uppercase tracking-wider">Acredita</th>
                                <th class="border-r border-indigo-500 px-4 py-3 text-center text-xs font-semibold text-white uppercase tracking-wider">Verifica</th>
                                <th class="border-r border-indigo-500 px-4 py-3 text-center text-xs font-semibold text-white uppercase tracking-wider">Usuarios Asignados</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-white uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vinculaciones as $index => $vinculacion)
                            @php
                                $bgColor = $index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-750';
                            @endphp
                            <tr wire:key="vinc-{{ $vinculacion->id }}" class="{{ $bgColor }} hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors duration-150">
                                <td class="border border-gray-200 dark:border-gray-600 px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $loop->iteration + ($vinculaciones->currentPage() - 1) * $vinculaciones->perPage() }}
                                </td>
                                <td class="border border-gray-200 dark:border-gray-600 px-4 py-3 text-sm text-gray-900 dark:text-gray-100 font-medium">
                                    {{ $vinculacion->unidadOrganizacionalMandante?->mandante?->razon_social ?? 'Sin Principal' }}
                                </td>
                                <td class="border border-gray-200 dark:border-gray-600 px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                    @if($vinculacion->dependencia)
                                        @if($vinculacion->dependencia->parent)
                                            {{ $vinculacion->dependencia->parent->nombre }} > 
                                        @endif
                                        {{ $vinculacion->dependencia->nombre }}
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="border border-gray-200 dark:border-gray-600 px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $vinculacion->unidadOrganizacionalMandante->nombre_unidad ?? '-' }}
                                </td>
                                <td class="border border-gray-200 dark:border-gray-600 px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $vinculacion->numero_contrato ?? '-' }}
                                </td>
                                <td class="border border-gray-200 dark:border-gray-600 px-4 py-3 text-center">
                                    @if($vinculacion->acredita)
                                        <span class="flex flex-col items-center justify-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                            <span class="font-bold">SÍ</span>
                                            @if($vinculacion->fecha_inicio_acredita || $vinculacion->fecha_fin_acredita)
                                            <span class="text-[10px] text-gray-600 mt-0.5">
                                                {{ $vinculacion->fecha_inicio_acredita?->format('d/m/Y') }} - {{ $vinculacion->fecha_fin_acredita?->format('d/m/Y') }}
                                            </span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">NO</span>
                                    @endif
                                </td>
                                <td class="border border-gray-200 dark:border-gray-600 px-4 py-3 text-center">
                                    @if($vinculacion->verifica)
                                        <span class="flex flex-col items-center justify-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                            <span class="font-bold">SÍ</span>
                                            @if($vinculacion->fecha_inicio_verifica || $vinculacion->fecha_fin_verifica)
                                            <span class="text-[10px] text-gray-600 mt-0.5">
                                                {{ $vinculacion->fecha_inicio_verifica?->format('d/m/Y') }} - {{ $vinculacion->fecha_fin_verifica?->format('d/m/Y') }}
                                            </span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">NO</span>
                                    @endif
                                </td>
                                <td class="border border-gray-200 dark:border-gray-600 px-4 py-3 text-center">
                                    @php
                                        $usuariosVinc = $this->getUsuariosAsignadosNombres($vinculacion->id);
                                    @endphp
                                    @if($usuariosVinc->isNotEmpty())
                                        <div class="flex flex-col gap-1">
                                            @foreach($usuariosVinc as $usu)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $usu->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs italic">Sin asignar</span>
                                    @endif
                                </td>
                                <td class="border border-gray-200 dark:border-gray-600 px-4 py-3 text-center">
                                    <button wire:click="abrirModalAsignar({{ $vinculacion->id }})" 
                                            class="inline-flex items-center px-3 py-1.5 border border-indigo-300 text-xs font-medium rounded text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        Asignar Usuarios
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                        </svg>
                                        <span class="font-medium">No tienes vinculaciones activas</span>
                                        <span class="text-sm">Contacta al Principal para solicitar una vinculación</span>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                @if ($vinculaciones->hasPages())
                <div class="mt-6 px-2 py-4 border-t border-gray-200 dark:border-gray-700 sm:flex sm:items-center sm:justify-between">
                    <div class="text-sm text-gray-700 dark:text-gray-400">
                        Mostrando <span class="font-medium">{{ $vinculaciones->firstItem() }}</span> a <span class="font-medium">{{ $vinculaciones->lastItem() }}</span> de <span class="font-medium">{{ $vinculaciones->total() }}</span> vinculaciones
                    </div>
                    <div>
                        {{ $vinculaciones->links('pagination::tailwind') }}
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>

    <!-- Modal para Asignar Usuarios -->
    @if ($showModalAsignar)
    <div class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity"><div class="absolute inset-0 bg-gray-500 opacity-75"></div></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">​</span>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                Asignar Usuarios a Vinculación
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $vinculacionNombre }}
                            </p>
                            
                            <div class="mt-4">
                                @if(count($usuariosDisponibles) > 0)
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Usuarios Contratista_User disponibles:
                                </label>
                                <div class="max-h-60 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-md p-2 space-y-2 bg-gray-50 dark:bg-gray-700">
                                    @foreach($usuariosDisponibles as $usuario)
                                    <label class="flex items-center space-x-3 p-2 hover:bg-gray-100 dark:hover:bg-gray-600 rounded cursor-pointer">
                                        <input type="checkbox" 
                                               wire:model.defer="usuariosAsignados" 
                                               value="{{ $usuario->id }}" 
                                               class="form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $usuario->name }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $usuario->email }}</span>
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                                @else
                                <div class="p-4 bg-yellow-50 dark:bg-yellow-900/30 rounded-md">
                                    <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                        No hay usuarios Contratista_User creados. 
                                        <a href="{{ route('contratista.gestion-usuarios') }}" class="underline font-medium">Crear uno aquí</a>
                                    </p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    @if(count($usuariosDisponibles) > 0)
                    <button wire:click="guardarAsignaciones()" type="button" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Guardar Asignaciones
                    </button>
                    @endif
                    <button wire:click="cerrarModal()" type="button" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
