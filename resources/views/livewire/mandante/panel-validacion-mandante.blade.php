<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Panel de Validación de Mandante') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                
                <p class="text-gray-600 dark:text-gray-400 mb-6">Estos son los documentos pendientes que tienes asignados para su revisión.</p>

                <!-- SECCIÓN DE FILTROS -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <input wire:model.live.debounce.500ms="filtroContratista" type="text" placeholder="Filtrar por Contratista..." class="form-input rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                    
                    <select wire:model.live="filtroEntidad" class="form-select rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                        <option value="">-- Todas las Entidades --</option>
                        <option value="App\Models\Contratista">Empresa</option>
                        <option value="App\Models\Trabajador">Trabajador</option>
                        <option value="App\Models\Vehiculo">Vehículo</option>
                        <option value="App\Models\Maquinaria">Maquinaria</option>
                        <option value="App\Models\Embarcacion">Embarcación</option>
                    </select>

                    <input wire:model.live.debounce.500ms="filtroDocumento" type="text" placeholder="Filtrar por Nombre documento" class="form-input rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                    
                    <input wire:model.live.debounce.500ms="filtroIdEntidad" type="text" placeholder="Filtrar por ID Entidad..." class="form-input rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nº</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"># ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Contratista</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Documento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Entidad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID Entidad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tiempo en Cola</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                            @forelse ($documentos as $key => $documento)
                            <tr wire:key="doc-mand-val-{{ $documento->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documentos->firstItem() + $key }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documento->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documento->contratista->razon_social ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="font-medium">{{ $documento->nombre_documento_snapshot }}</div>
                                    @if(!empty($documento->motivo_revalidacion))
                                        <div class="text-xs text-purple-600 dark:text-purple-400 mt-1 p-1 bg-purple-100 dark:bg-purple-900/50 rounded" title="{{ $documento->motivo_revalidacion }}">
                                            <strong>Motivo Rev:</strong> {{ Str::limit($documento->motivo_revalidacion, 50) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ class_basename($documento->entidad_type) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($documento->entidad)
                                        @if($documento->entidad instanceof \App\Models\Vehiculo) 
                                            <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ $documento->entidad->patente_letras }} {{ $documento->entidad->patente_numeros }}</span>
                                        @elseif($documento->entidad instanceof \App\Models\Trabajador) 
                                            <div class="font-bold">{{ $documento->entidad->rut }}</div>
                                            <div class="text-xs text-gray-500">{{ $documento->entidad->nombre_completo }}</div>
                                        @elseif($documento->entidad instanceof \App\Models\Maquinaria) 
                                            <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ $documento->entidad->identificador_letras }} {{ $documento->entidad->identificador_numeros }}</span>
                                        @elseif($documento->entidad instanceof \App\Models\Embarcacion) 
                                            <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ $documento->entidad->matricula_letras }} {{ $documento->entidad->matricula_numeros }}</span>
                                        @elseif($documento->entidad instanceof \App\Models\Contratista) 
                                            <span class="font-bold">{{ $documento->entidad->rut }}</span>
                                        @else N/A @endif
                                    @else N/A @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $documento->created_at->diffForHumans() }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="{{ route('document.revisar', $documento->id) }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition-all duration-200 shadow hover:shadow-md gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        REVISAR
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-3">
                                        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">No tienes documentos pendientes que coincidan con los filtros.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($documentos->hasPages())
                    <div class="mt-6 border-t border-gray-100 dark:border-gray-700 pt-4">
                        {{ $documentos->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>