<div class="p-6 bg-white dark:bg-gray-800 shadow-md rounded-lg">
    <h2 class="text-2xl font-semibold text-gray-800 dark:text-white mb-4">Gestión de Estados Civiles</h2>

    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
             class="mb-4 px-4 py-2 bg-green-100 border border-green-400 text-green-700 rounded-md dark:bg-green-700 dark:text-green-100 dark:border-green-600">
            {{ session('message') }}
        </div>
    @endif
     @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            class="mb-4 px-4 py-2 bg-red-100 border border-red-400 text-red-700 rounded-md dark:bg-red-700 dark:text-red-100 dark:border-red-600">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row justify-between items-center mb-4 space-y-2 sm:space-y-0">
        <div class="w-full sm:w-1/3">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar estado civil..."
                   class="input-field w-full">
        </div>
        <button wire:click="create()"
                class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Nuevo Estado Civil
        </button>
    </div>

    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" wire:click="sortBy('id')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer">ID @if ($sortBy === 'id')<span class="ml-1">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>@endif</th>
                    <th scope="col" wire:click="sortBy('nombre')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer">Nombre @if ($sortBy === 'nombre')<span class="ml-1">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>@endif</th>
                    <th scope="col" wire:click="sortBy('is_active')" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer">Estado @if ($sortBy === 'is_active')<span class="ml-1">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>@endif</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($estadosCiviles as $ec)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $ec->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $ec->nombre }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                            <span wire:click="toggleActive({{ $ec->id }})"
                                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full cursor-pointer {{ $ec->is_active ? 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100' }}">
                                {{ $ec->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <button wire:click="edit({{ $ec->id }})" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200 mr-2" title="Editar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828zM5 12V7.172l2.121-2.121 2.828 2.828L7.172 10H5zM15 5.414l-2.828-2.828L14.586 1a1 1 0 011.414 0l1.414 1.414a1 1 0 010 1.414L15 5.414zM3 15a1 1 0 00-1 1v2a1 1 0 001 1h12a1 1 0 001-1v-2a1 1 0 00-1-1H3z"/></svg>
                            </button>
                            <button wire:click="delete({{ $ec->id }})" wire:confirm="¿Estás seguro de DESACTIVAR este estado civil?" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200" title="Desactivar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2H7a3 3 0 00-3 3v5a1 1 0 001 1h8a1 1 0 001-1v-5a3 3 0 00-3-3z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">No se encontraron estados civiles.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $estadosCiviles->links() }}</div>

    @if ($isOpen)
        <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit.prevent="store">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start w-full">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.364-1.756v-.003M15 19.128c-.071.02-.144.039-.22.056a4.5 4.5 0 01-8.44 0A12.318 12.318 0 013 12.53v-.003c0-1.113.285-2.16.786-3.07M3 12.53v-.003c0-1.009.196-1.966.546-2.861A12.318 12.318 0 018.624 3c2.331 0 4.512.645 6.364 1.756v.003M3 12.53c0 .44.02.873.056 1.304A4.5 4.5 0 006.85 17.02a4.5 4.5 0 008.44 0A12.318 12.318 0 0121 12.53v-.003A12.318 12.318 0 0015.376 3c-2.331 0-4.512.645-6.364 1.756v-.003" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">{{ $estadoCivilId ? 'Editar' : 'Crear Nuevo' }} Estado Civil</h3>
                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <label for="nombre_ec" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre <span class="text-red-500">*</span></label>
                                            <input type="text" wire:model.lazy="nombre" id="nombre_ec" class="input-field w-full @error('nombre') input-error @enderror" placeholder="Ej: Soltero(a), Casado(a)">
                                            @error('nombre') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="is_active_ec_modal" class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                                <input type="checkbox" wire:model="is_active" id="is_active_ec_modal" class="form-checkbox">
                                                <span class="ml-2">Activo</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm dark:focus:ring-offset-gray-800">Guardar</button>
                            <button type="button" wire:click="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Reutilizar estilos globales si existen, o definirlos como en fichas anteriores si es necesario --}}
    {{-- @push('styles')
    <style>
        .input-field { @apply mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200; }
        .input-error { @apply border-red-500 dark:border-red-500; }
        .error-message { @apply text-red-500 text-xs mt-1; }
        .form-checkbox { @apply h-4 w-4 text-indigo-600 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500 dark:bg-gray-700 dark:focus:ring-indigo-600 dark:ring-offset-gray-800; }
    </style>
    @endpush --}}
</div>