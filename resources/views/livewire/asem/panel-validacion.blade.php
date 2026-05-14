<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Panel de Validación de Documentos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                
                <p class="text-gray-600 dark:text-gray-400 mb-6">Estos son los documentos pendientes que tienes asignados para su revisión.</p>

                <!-- ================ SECCIÓN DE FILTROS ACTUALIZADA ================ -->
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
                    <input wire:model.live.debounce.500ms="filtroContratista" type="text" placeholder="Filtrar por Contratista..." class="form-input rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                    
                    <select wire:model.live="filtroMandante" class="form-select rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                        <option value="">-- Todos las Principales --</option>
                        @foreach($mandantes as $mandante)
                            <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                        @endforeach
                    </select>
                    
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Principal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Contratista</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Documento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Entidad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID Entidad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tiempo en Cola</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                            @forelse ($documentosAsignados as $key => $documento)
                            <tr wire:key="doc-val-{{ $documento->id }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documentosAsignados->firstItem() + $key }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documento->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documento->mandante->razon_social ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documento->contratista->razon_social ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    {{ $documento->nombre_documento_snapshot }}
                                    @if(!empty($documento->motivo_revalidacion))
                                        <div class="text-xs text-purple-600 dark:text-purple-400 mt-1 p-1 bg-purple-100 dark:bg-purple-900/50 rounded" title="{{ $documento->motivo_revalidacion }}">
                                            <strong>Motivo Rev:</strong> {{ Str::limit($documento->motivo_revalidacion, 50) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ class_basename($documento->entidad_type) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($documento->entidad)
                                        @if($documento->entidad instanceof \App\Models\Vehiculo) {{ $documento->entidad->patente_letras }} {{ $documento->entidad->patente_numeros }}
                                        @elseif($documento->entidad instanceof \App\Models\Trabajador) {{ $documento->entidad->rut }}
                                        @elseif($documento->entidad instanceof \App\Models\Maquinaria) {{ $documento->entidad->identificador_letras }} {{ $documento->entidad->identificador_numeros }}
                                        @elseif($documento->entidad instanceof \App\Models\Embarcacion) {{ $documento->entidad->matricula_letras }} {{ $documento->entidad->matricula_numeros }}
                                        @elseif($documento->entidad instanceof \App\Models\Contratista) {{ $documento->entidad->rut }}
                                        @else N/A @endif
                                    @else N/A @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documento->created_at->diffForHumans() }}</td>
                                {{-- <<< CORRECCIÓN CRÍTICA >>> Se usa el nuevo nombre de la ruta. --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm"><a href="{{ route('document.revisar', ['documentoId' => $documento->id]) }}" wire:navigate class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 font-semibold">Revisar</a></td>
                            </tr>
                            @empty
                            <tr><td colspan="9" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No tienes documentos pendientes que coincidan con los filtros.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $documentosAsignados->links() }}</div>
            </div>
        </div>
    </div>
</div>