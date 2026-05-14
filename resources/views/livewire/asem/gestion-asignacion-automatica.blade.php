<div>
    <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">Gestión de Asignación Automática</h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Configure las reglas para asignar automáticamente los documentos cargados por los contratistas a los validadores de turno.
        </p>
    </div>

    <div class="p-6">
        @if (session()->has('status'))
            <div class="alert-success mb-4">{{ session('status') }}</div>
        @endif

        <div class="overflow-x-auto shadow-md sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="table-header">Principal</th>
                        <th scope="col" class="table-header">Validadores Asignados</th>
                        <th scope="col" class="table-header text-center">Estado</th>
                        <th scope="col" class="table-header text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($mandantes as $mandante)
                        <tr wire:key="mandante-{{ $mandante->id }}">
                            <td class="table-cell font-medium">{{ $mandante->razon_social }}</td>
                            <td class="table-cell">
                                @if ($mandante->configuracionAsignacion && $mandante->configuracionAsignacion->validadores->isNotEmpty())
                                    {{ $mandante->configuracionAsignacion->validadores->pluck('name')->join(', ') }}
                                @else
                                    <span class="text-gray-400 italic">Sin asignar</span>
                                @endif
                            </td>
                            <td class="table-cell text-center">
                                @if ($mandante->configuracionAsignacion)
                                    {{-- ================================================================== --}}
                                    {{-- INICIO DE LA MODIFICACIÓN CANÓNICA: EL INTERRUPTOR REFORJADO --}}
                                    {{-- ================================================================== --}}
                                    <label for="toggle-{{ $mandante->configuracionAsignacion->id }}" class="flex items-center cursor-pointer justify-center">
                                        <div class="relative">
                                            <input type="checkbox" id="toggle-{{ $mandante->configuracionAsignacion->id }}" class="sr-only peer" 
                                                   wire:change="toggleActivo({{ $mandante->configuracionAsignacion->id }})" 
                                                   {{ $mandante->configuracionAsignacion->is_active ? 'checked' : '' }}>
                                            <div class="block w-14 h-8 rounded-full transition-colors duration-300 ease-in-out
                                                        bg-gray-300 peer-checked:bg-indigo-600 dark:bg-gray-600 dark:peer-checked:bg-indigo-500"></div>
                                            <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition-transform duration-300 ease-in-out
                                                        transform peer-checked:translate-x-6"></div>
                                        </div>
                                        <span class="ml-3 text-sm font-medium 
                                                     {{ $mandante->configuracionAsignacion->is_active ? 'text-indigo-700 dark:text-indigo-300' : 'text-gray-500 dark:text-gray-400' }}">
                                            {{ $mandante->configuracionAsignacion->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </label>
                                    {{-- ================================================================== --}}
                                    {{-- FIN DE LA MODIFICACIÓN CANÓNICA --}}
                                    {{-- ================================================================== --}}
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200">Inactivo</span>
                                @endif
                            </td>
                            <td class="table-cell text-center">
                                <button wire:click="abrirModal({{ $mandante->id }})" class="btn-primary">Gestionar</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="table-cell text-center">No hay Principales activas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de Gestión -->
    @if ($showModal && $selectedMandante)
        <div class="fixed z-30 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="$set('showModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit.prevent="guardarConfiguracion">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                Asignar Validadores para: <span class="font-bold">{{ $selectedMandante->razon_social }}</span>
                            </h3>
                            <div class="mt-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Seleccione los validadores que participarán en el ciclo de asignación automática para este mandante (máximo 5).</p>
                                <div class="mt-4 space-y-2 max-h-60 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-md p-4">
                                    @forelse ($this->validadoresDisponibles as $validador)
                                        <label for="validator-{{ $validador->id }}" class="flex items-center">
                                            <input id="validator-{{ $validador->id }}" type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                                   wire:model="selectedValidators" value="{{ $validador->id }}">
                                            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">{{ $validador->name }}</span>
                                        </label>
                                    @empty
                                        <p class="text-sm text-gray-500">No hay validadores disponibles.</p>
                                    @endforelse
                                </div>
                                @error('selectedValidators') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="btn-primary">Guardar Configuración</button>
                            <button type="button" wire:click="$set('showModal', false)" class="btn-secondary mr-2">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>