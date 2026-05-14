<div class="overflow-x-auto shadow-md sm:rounded-lg">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Documento</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Afecta % Cumplimiento (General / Excepción)</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Restringe Acceso (General / Excepción)</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Es Perseguidor (General / Excepción)</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Válido Hasta</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-600">
            @foreach($documentosConCriticidad as $docId => $doc)
                <tr wire:key="doc-excepcion-{{ $docId }}">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $doc['nombre_documento'] }}</td>
                    
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $doc['config_general']['afecta_cumplimiento'] ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $doc['config_general']['afecta_cumplimiento'] ? 'Sí' : 'No' }}
                            </span>
                            <select wire:model.defer="documentosConCriticidad.{{ $docId }}.excepcion.afecta_cumplimiento_override" class="input-field !py-1 !text-xs">
                                <option value="">N/A</option>
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $doc['config_general']['restringe_acceso'] ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $doc['config_general']['restringe_acceso'] ? 'Sí' : 'No' }}
                            </span>
                            <select wire:model.defer="documentosConCriticidad.{{ $docId }}.excepcion.restringe_acceso_override" class="input-field !py-1 !text-xs">
                                <option value="">N/A</option>
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $doc['config_general']['es_perseguidor'] ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $doc['config_general']['es_perseguidor'] ? 'Sí' : 'No' }}
                            </span>
                            <select wire:model.defer="documentosConCriticidad.{{ $docId }}.excepcion.es_perseguidor_override" class="input-field !py-1 !text-xs">
                                <option value="">N/A</option>
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="date" wire:model.defer="documentosConCriticidad.{{ $docId }}.valido_hasta" class="input-field !py-1 !text-xs">
                        @error('documentosConCriticidad.'.$docId.'.valido_hasta') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center space-x-2">
                            <button wire:click="guardarExcepcion({{ $docId }})" class="btn-primary !py-1 !px-3 !text-xs">
                                Excepcionar
                            </button>
                            @if($doc['tiene_excepcion_activa'])
                                <button wire:click="eliminarExcepcion({{ $docId }})" 
                                        wire:confirm="¿Está seguro de que desea eliminar esta excepción? La criticidad del documento volverá a su configuración general."
                                        class="btn-danger !py-1 !px-3 !text-xs">
                                    Eliminar Excepción
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>