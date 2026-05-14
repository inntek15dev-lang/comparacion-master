<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestión de Textos de Rechazo Predefinidos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Mensajes Flash --}}
            @if (session()->has('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded dark:bg-green-700 dark:text-green-100 dark:border-green-600">
                    {{ session('success') }}
                </div>
            @endif
            @if (session()->has('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded dark:bg-red-700 dark:text-red-100 dark:border-red-600">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    @if(Auth::user()->hasRole('ASEM_Admin'))
                        <div class="mb-4">
                            <button wire:click="abrirModalParaCrear" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Crear Nuevo Texto de Rechazo
                            </button>
                        </div>
                    @endif

                    {{-- SECCIÓN DE FILTROS --}}
                    <div class="mb-6 p-4 bg-gray-100 dark:bg-gray-700 rounded-md">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Filtrar Textos de Rechazo</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="filtroTituloRechazo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título</label>
                                <input type="text" wire:model.live.debounce.300ms="filtroTitulo" id="filtroTituloRechazo" placeholder="Buscar por título..."
                                       class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-900 dark:text-gray-200 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label for="filtroEstadoRechazo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
                                <select wire:model.live="filtroEstado" id="filtroEstadoRechazo"
                                        class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-900 dark:text-gray-200 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="todos">Todos</option>
                                    <option value="activos">Solo Activos</option>
                                    <option value="inactivos">Solo Inactivos</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    {{-- FIN SECCIÓN DE FILTROS --}}

                    @if ($textosRechazo->count())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Título</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Descripción Detallada</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                                        @if(Auth::user()->hasRole('ASEM_Admin'))
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($textosRechazo as $texto)
                                        <tr class="{{ !$texto->is_active ? 'bg-red-50 dark:bg-red-800 opacity-70' : '' }}">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $texto->titulo }}</td>
                                            <td class="px-6 py-4 whitespace-normal text-sm text-gray-500 dark:text-gray-300">{{ Str::limit($texto->descripcion_detalle, 70) }}</td> {{-- Limitar para visualización --}}
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @if($texto->is_active)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100">Activo</span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100">Inactivo</span>
                                                @endif
                                            </td>
                                            @if(Auth::user()->hasRole('ASEM_Admin'))
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <button wire:click="abrirModalParaEditar({{ $texto->id }})" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200">Editar</button>
                                                    <button wire:click="confirmarAlternarEstado({{ $texto->id }})" 
                                                            wire:confirm="¿Está seguro de que quiere {{ $texto->is_active ? 'desactivar' : 'activar' }} este texto: '{{ $texto->titulo }}'?" 
                                                            class="ms-2 {{ $texto->is_active ? 'text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200' : 'text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-200' }}">
                                                        {{ $texto->is_active ? 'Desactivar' : 'Activar' }}
                                                    </button>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $textosRechazo->links() }}
                        </div>
                    @else
                        <p class="text-center py-4">No hay textos de rechazo que coincidan con los filtros aplicados.</p>
                    @endif
                </div>
            </div>

            {{-- Modal para Crear/Editar Texto de Rechazo --}}
            @if ($mostrarModal)
                <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title-texto-rechazo" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75" aria-hidden="true" wire:click="cerrarModal"></div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
                        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <form wire:submit.prevent="guardarTextoRechazo">
                                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <div class="sm:flex sm:items-start">
                                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title-texto-rechazo">
                                                {{ $textoActual && $textoActual->id ? 'Editar Texto de Rechazo' : 'Crear Nuevo Texto de Rechazo' }}
                                            </h3>
                                            <div class="mt-4 space-y-4">
                                                <div>
                                                    <label for="titulo_rechazo_modal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título</label>
                                                    <input type="text" wire:model.defer="titulo" id="titulo_rechazo_modal" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-200 focus:ring-indigo-500 focus:border-indigo-500">
                                                    @error('titulo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>
                                                <div>
                                                    <label for="descripcion_detalle_rechazo_modal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción Detallada</label>
                                                    <textarea wire:model.defer="descripcion_detalle" id="descripcion_detalle_rechazo_modal" rows="5" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-200 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                                                    @error('descripcion_detalle') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>
                                                <div>
                                                    <label for="is_active_rechazo_modal" class="flex items-center">
                                                        <input type="checkbox" wire:model.defer="is_active" id="is_active_rechazo_modal" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-indigo-400">
                                                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Activo</span>
                                                    </label>
                                                    @error('is_active') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ms-3 sm:w-auto sm:text-sm">
                                        Guardar
                                    </button>
                                    <button type="button" wire:click="cerrarModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>