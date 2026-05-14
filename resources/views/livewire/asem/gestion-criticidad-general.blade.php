<div>
    <div class="p-6 bg-white border-b border-gray-200">
        <h2 class="text-2xl font-bold mb-4">Gestión de %  y Acceso</h2>

        <!-- Contenedor de Filtros -->
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Filtro por Principal -->
                <div>
                    <label for="mandante_id" class="block text-sm font-medium text-gray-700">Principal:</label>
                    <select wire:model.live="mandante_id" id="mandante_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        @foreach($mandantes as $mandante)
                            <option value="{{ $mandante['id'] }}">{{ $mandante['razon_social'] }}</option>
                        @endforeach
                    </select>
                </div>
                
                {{-- ===================== INICIO: NUEVOS FILTROS ===================== --}}
                <!-- Filtro por Unidad Operativa -->
                <div>
                    <label for="unidadOrganizacionalIdFiltro" class="block text-sm font-medium text-gray-700">Filtrar por U.O.:</label>
                    <select wire:model.live="unidadOrganizacionalIdFiltro" id="unidadOrganizacionalIdFiltro" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" @if(empty($unidadesOrganizacionales)) disabled @endif>
                        <option value="">-- Todas las Unidades --</option>
                        @foreach($unidadesOrganizacionales as $uo)
                            <option value="{{ $uo['id'] }}">{{ $uo['nombre_unidad'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro por Tipo de Entidad -->
                <div>
                    <label for="tipoEntidadIdFiltro" class="block text-sm font-medium text-gray-700">Filtrar por Entidad:</label>
                    <select wire:model.live="tipoEntidadIdFiltro" id="tipoEntidadIdFiltro" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" @if(empty($tiposEntidad)) disabled @endif>
                        <option value="">-- Todas las Entidades --</option>
                         @foreach($tiposEntidad as $tipo)
                            <option value="{{ $tipo['id'] }}">{{ $tipo['nombre_entidad'] }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- ===================== FIN: NUEVOS FILTROS ===================== --}}
            </div>
        </div>
        
        <div wire:loading wire:target="mandante_id, unidadOrganizacionalIdFiltro, tipoEntidadIdFiltro" class="text-center w-full">Cargando...</div>

        <!-- Tabla de Documentos -->
        <div wire:loading.remove wire:target="mandante_id, unidadOrganizacionalIdFiltro, tipoEntidadIdFiltro" class="overflow-x-auto">
            @forelse($documentosAgrupados as $nombreEntidad => $documentos)
                <h3 class="text-lg font-semibold mt-6 mb-2 bg-gray-100 p-2 rounded-md">
                    Documentos para Entidad: {{ $nombreEntidad ?? 'General' }}
                </h3>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Documento
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Afecta % Cumplimiento
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Restringe Acceso
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Es Perseguidor
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($documentos as $documento)
                            @if(isset($configuraciones[$documento['id']]))
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $documento['nombre'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <input type="checkbox" 
                                        wire:click="actualizarCriticidad({{ $documento['id'] }}, 'afecta_cumplimiento')" 
                                        @if($configuraciones[$documento['id']]['afecta_cumplimiento']) checked @endif
                                        class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <input type="checkbox" 
                                        wire:click="actualizarCriticidad({{ $documento['id'] }}, 'restringe_acceso')" 
                                        @if($configuraciones[$documento['id']]['restringe_acceso']) checked @endif
                                        class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <input type="checkbox" 
                                        wire:click="actualizarCriticidad({{ $documento['id'] }}, 'es_perseguidor')"
                                        @if($configuraciones[$documento['id']]['es_perseguidor']) checked @endif
                                        class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                </td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @empty
                <div class="text-center py-8 bg-gray-50 rounded-lg">
                    <p class="text-gray-500">No hay documentos que coincidan con los filtros seleccionados para esta Principal.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>