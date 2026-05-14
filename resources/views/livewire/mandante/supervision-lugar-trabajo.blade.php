<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $titulo }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                
                {{-- Panel de Filtros --}}
                <div class="mb-6 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="lugar_trabajo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lugar de Trabajo/Departamento</label>
                            <select wire:model.live="selectedLugarTrabajoId" id="lugar_trabajo" class="input-field mt-1">
                                <option value="todos">Todos los Lugares de Trabajo</option>
                                @foreach($lugaresDeTrabajoOptions as $id => $nombre)
                                    <option value="{{ $id }}">{!! str_replace('--', '&nbsp;&nbsp;&nbsp;', $nombre) !!}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Filtro UOs --}}
                        <div x-data="{ open: false }" class="relative">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">CLASIFICACION ORGANIZACIONAL</label>
                            <button @click="open = !open" type="button" class="input-field text-left mt-1 w-full flex justify-between items-center">
                                <span>
                                    @if(count($filtroUoIds) === count($unidadesOrganizacionalesOptions))
                                        Todas
                                    @elseif(count($filtroUoIds) === 0)
                                        Ninguna
                                    @else
                                        {{ count($filtroUoIds) }} seleccionada(s)
                                    @endif
                                </span>
                                <x-icons.chevron-down class="h-4 w-4"/>
                            </button>
                            <div x-show="open" @click.away="open = false" class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                <div class="p-2 space-x-2">
                                    {{-- ================== INICIO DE LA CORRECCIÓN CANÓNICA ================== --}}
                                    <button type="button" wire:click="toggleTodosUos" class="btn-secondary text-xs">
                                        {{ count($filtroUoIds) === count($unidadesOrganizacionalesOptions) ? 'Desmarcar Todos' : 'Marcar Todos' }}
                                    </button>
                                    {{-- ================== FIN DE LA CORRECCIÓN CANÓNICA ==================== --}}
                                </div>
                                @foreach($unidadesOrganizacionalesOptions as $id => $nombre)
                                    <label class="flex items-center px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer">
                                        <input type="checkbox" wire:model.live="filtroUoIds" value="{{ $id }}" class="form-checkbox">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{!! str_replace('--', '&nbsp;&nbsp;&nbsp;', $nombre) !!}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    {{-- Filtro Entidades --}}
                    <div class="pt-4 border-t border-gray-300 dark:border-gray-600">
                        <div x-data="{ open: false }" class="relative">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipos de Entidad a Mostrar</label>
                            <button @click="open = !open" type="button" class="input-field text-left mt-1 w-full flex justify-between items-center">
                                <span>
                                    @if(count($filtroEntidadTipos) === count($entidadesControlablesOptions))
                                        Todas
                                    @elseif(count($filtroEntidadTipos) === 0)
                                        Ninguna
                                    @else
                                        {{ count($filtroEntidadTipos) }} seleccionada(s)
                                    @endif
                                </span>
                                <x-icons.chevron-down class="h-4 w-4"/>
                            </button>
                            <div x-show="open" @click.away="open = false" class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                <div class="p-2 space-x-2">
                                    {{-- ================== INICIO DE LA CORRECCIÓN CANÓNICA ================== --}}
                                    <button type="button" wire:click="toggleTodosEntidades" class="btn-secondary text-xs">
                                        {{ count($filtroEntidadTipos) === count($entidadesControlablesOptions) ? 'Desmarcar Todos' : 'Marcar Todos' }}
                                    </button>
                                    {{-- ================== FIN DE LA CORRECCIÓN CANÓNICA ==================== --}}
                                </div>
                                @foreach($entidadesControlablesOptions as $entidad)
                                    <label class="flex items-center px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer">
                                        <input type="checkbox" wire:model.live="filtroEntidadTipos" value="{{ $entidad }}" class="form-checkbox">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ Str::plural(ucfirst(strtolower($entidad))) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Resultados --}}
                <div wire:loading.class="opacity-50" class="mt-6 space-y-8">
                    @forelse($lugaresDeTrabajoConContratistas as $lugarDeTrabajo)
                        <div wire:key="lugar-trabajo-{{ $lugarDeTrabajo->id }}">
                            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 border-b-2 border-indigo-500 pb-2 mb-4">
                                <x-icons.building-office class="inline-block h-6 w-6 mr-2 text-indigo-600 dark:text-indigo-400"/>
                                {{ $lugarDeTrabajo->nombre }}
                            </h3>
                            <div class="space-y-6">
                                @foreach($lugarDeTrabajo->contratistas as $contratista)
                                    @include('livewire.supervision-lugar-trabajo._contratista-card-partial', [
                                        'contratista' => $contratista,
                                        'mandanteId' => $selectedMandanteId,
                                        'filtroUoIds' => $filtroUoIds,
                                        'filtroEntidadTipos' => $filtroEntidadTipos
                                    ])
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 px-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                            <p class="text-gray-600 dark:text-gray-400">No se encontraron contratistas para los filtros seleccionados.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>