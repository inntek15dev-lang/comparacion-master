<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestión de Tipos de Maquinaria') }} {{-- Cambiado --}}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

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

                <div class="flex justify-between items-center mb-6">
                    <div></div> 
                    <button wire:click="create()" class="btn-primary">
                        <x-icons.plus class="w-5 h-5 mr-2"/>
                        {{ __('Crear Nuevo Tipo de Maquinaria') }} {{-- Cambiado --}}
                    </button>
                </div>

                {{-- Filtros --}}
                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Filtrar Tipos de Maquinaria</h3> {{-- Cambiado --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="searchTerm" class="label-generic">Nombre:</label>
                            <input type="text" wire:model.live.debounce.300ms="searchTerm" id="searchTerm" class="input-field" placeholder="Buscar por nombre...">
                        </div>
                        <div>
                            <label for="filterByStatus" class="label-generic">Estado:</label>
                            <select wire:model.live="filterByStatus" id="filterByStatus" class="input-field">
                                <option value="">Todos</option>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="table-header">Nombre del Tipo de Maquinaria</th> {{-- Cambiado --}}
                                <th scope="col" class="table-header">Estado</th>
                                <th scope="col" class="table-header text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($tiposMaquinaria as $tipoMaquinaria) {{-- Cambiado --}}
                                <tr wire:key="tipo-maquinaria-{{ $tipoMaquinaria->id }}"> {{-- Cambiado --}}
                                    <td class="table-cell">{{ $tipoMaquinaria->nombre }}</td> 
                                    <td class="table-cell">
                                        @if ($tipoMaquinaria->is_active) 
                                            <span class="badge-active">Activo</span>
                                        @else
                                            <span class="badge-inactive">Inactivo</span>
                                        @endif
                                    </td>
                                    <td class="table-cell text-right">
                                        <button wire:click="edit({{ $tipoMaquinaria->id }})" class="btn-secondary btn-sm mr-1" title="Editar"> 
                                            Editar
                                        </button>
                                        <button 
                                            wire:click="toggleStatus({{ $tipoMaquinaria->id }})" 
                                            wire:loading.attr="disabled"
                                            wire:target="toggleStatus({{ $tipoMaquinaria->id }})" 
                                            class="btn-sm {{ $tipoMaquinaria->is_active ? 'btn-danger-outline' : 'btn-success-outline' }}" 
                                            title="{{ $tipoMaquinaria->is_active ? 'Desactivar' : 'Activar' }}"> 
                                            <span wire:loading.remove wire:target="toggleStatus({{ $tipoMaquinaria->id }})"> 
                                                {{ $tipoMaquinaria->is_active ? 'Desactivar' : 'Activar' }} 
                                            </span>
                                            <span wire:loading wire:target="toggleStatus({{ $tipoMaquinaria->id }})"> 
                                                <svg class="animate-spin inline-block h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
                                        No hay tipos de maquinaria registrados o que coincidan con la búsqueda. {{-- Cambiado --}}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($tiposMaquinaria->hasPages()) 
                    <div class="mt-4">
                        {{ $tiposMaquinaria->links() }} 
                    </div>
                @endif

            </div>
        </div>
    </div>

    {{-- Modal --}}
    @if ($isOpen)
        <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75" aria-hidden="true" wire:click="closeModal()"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit.prevent="store">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start mb-4">
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                        {{ $tipo_maquinaria_id ? 'Editar Tipo de Maquinaria' : 'Crear Nuevo Tipo de Maquinaria' }} {{-- Cambiado --}}
                                    </h3>
                                </div>
                            </div>
                            <hr class="my-2 border-gray-300 dark:border-gray-600"/>
                            
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="nombre_maquinaria" class="label-generic">Nombre del Tipo de Maquinaria <span class="text-red-500">*</span></label> {{-- Cambiado --}}
                                    <input type="text" wire:model.defer="nombre" id="nombre_maquinaria" class="input-field" placeholder="Ej: Excavadora"> {{-- Cambiado placeholder e id --}}
                                    @error('nombre') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="descripcion_maquinaria" class="label-generic">Descripción</label> {{-- Cambiado --}}
                                    <textarea wire:model.defer="descripcion" id="descripcion_maquinaria" rows="3" class="input-field" placeholder="Descripción detallada del tipo de maquinaria (opcional)"></textarea> {{-- Cambiado placeholder e id --}}
                                    @error('descripcion') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div class="flex items-center">
                                    <input wire:model.defer="is_active" id="is_active_modal_tipo_maquinaria" type="checkbox" class="checkbox-generic"> {{-- Cambiado id --}}
                                    <label for="is_active_modal_tipo_maquinaria" class="ml-2 label-generic">Tipo de Maquinaria Activo</label> {{-- Cambiado --}}
                                    @error('is_active') <span class="error-message ml-3">{{ $message }}</span> @enderror
                                </div>
                            </div>

                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="btn-primary sm:ml-3 sm:w-auto" wire:loading.attr="disabled" wire:target="store">
                                <span wire:loading.remove wire:target="store">
                                    {{ $tipo_maquinaria_id ? 'Actualizar' : 'Guardar' }} 
                                </span>
                                <span wire:loading wire:target="store">
                                     <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Procesando...
                                </span>
                            </button>
                            <button type="button" wire:click="closeModal()" class="btn-secondary-outline mt-3 sm:mt-0 sm:w-auto">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>