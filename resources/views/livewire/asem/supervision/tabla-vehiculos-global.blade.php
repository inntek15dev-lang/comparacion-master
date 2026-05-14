<div>
    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar por patente/placa" class="input-field w-full mb-4">
    
    <div class="overflow-hidden shadow-md sm:rounded-lg border border-gray-300 dark:border-gray-600">
        <div class="overflow-x-auto overflow-y-auto" style="max-height: 70vh;">
            <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-600">
                <thead class="bg-gray-100 dark:bg-gray-700 sticky top-0 z-10">
                    <tr>
                        <th class="table-header border border-gray-300 dark:border-gray-600 px-6 py-4 bg-gray-100 dark:bg-gray-700">#</th>
                        <th class="table-header border border-gray-300 dark:border-gray-600 px-6 py-4 bg-gray-100 dark:bg-gray-700">ID</th>
                        <th class="table-header border border-gray-300 dark:border-gray-600 px-6 py-4 bg-gray-100 dark:bg-gray-700">Patente</th>
                        <th class="table-header border border-gray-300 dark:border-gray-600 px-6 py-4 bg-gray-100 dark:bg-gray-700">Tipo / Marca</th>
                        <th class="table-header text-center border border-gray-300 dark:border-gray-600 px-6 py-4 bg-gray-100 dark:bg-gray-700">% Cumplimiento</th>
                        <th class="table-header text-center border border-gray-300 dark:border-gray-600 px-6 py-4 bg-gray-100 dark:bg-gray-700">Acceso</th>
                        <th class="table-header text-center border border-gray-300 dark:border-gray-600 px-6 py-4 bg-gray-100 dark:bg-gray-700">Venc. Anulación</th>
                        <th class="table-header text-center border border-gray-300 dark:border-gray-600 px-6 py-4 bg-gray-100 dark:bg-gray-700">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800">
                    @forelse ($vehiculos as $vehiculo)
                        @php
                            $acceso = $vehiculo->determinarAccesoHabilitado($mandanteId, $this->uoId);
                            $cumplimiento = $vehiculo->calcularPorcentajeCumplimiento($mandanteId, $this->uoId);
                        @endphp
                        <tr class="hover:bg-blue-50 dark:hover:bg-blue-900/20 {{ $loop->even ? 'bg-orange-50 dark:bg-orange-900/20' : 'bg-white dark:bg-gray-800' }}">
                            <td class="table-cell font-mono text-center border border-gray-300 dark:border-gray-600 px-6 py-4">{{ ($vehiculos->currentPage() - 1) * $vehiculos->perPage() + $loop->iteration }}</td>
                            <td class="table-cell font-mono text-center border border-gray-300 dark:border-gray-600 px-6 py-4">{{ $vehiculo->id }}</td>
                            <td class="table-cell font-mono border border-gray-300 dark:border-gray-600 px-6 py-4">{{ $vehiculo->patente_completa }}</td>
                            <td class="table-cell border border-gray-300 dark:border-gray-600 px-6 py-4">{{ $vehiculo->tipoVehiculo->nombre ?? 'N/A' }} / {{ $vehiculo->marcaVehiculo->nombre ?? 'N/A' }}</td>
                            <td class="table-cell text-center font-semibold border border-gray-300 dark:border-gray-600 px-6 py-4">{{ $cumplimiento }}%</td>
                            
                            <td class="table-cell text-center text-sm border border-gray-300 dark:border-gray-600 px-6 py-4">
                                @if($acceso['habilitado'])
                                    <span class="font-semibold text-green-600 dark:text-green-400" title="{{ $acceso['motivo'] ?? 'Estado calculado por el sistema' }}">
                                        HABILITADO
                                        @if($acceso['es_excepcion'] ?? false)
                                            <span class="text-xs block mt-1">(MANUAL)</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center font-bold text-2xl text-red-500" title="{{ $acceso['motivo'] ?? 'Acceso restringido' }}">
                                        ✕
                                    </span>
                                @endif
                            </td>
                            
                            <td class="table-cell text-center text-xs border border-gray-300 dark:border-gray-600 px-6 py-4">
                                @if($acceso['es_excepcion'] && $vehiculo->anulacionManualActiva?->valido_hasta)
                                    {{ \Carbon\Carbon::parse($vehiculo->anulacionManualActiva->valido_hasta)->format('d-m-Y') }}
                                @else
                                    ---
                                @endif
                            </td>
                            
                            <td class="table-cell text-center border border-gray-300 dark:border-gray-600 px-6 py-4">
                                @if(!$esSoloLectura)
                                @if($acceso['es_excepcion'])
                                    <button wire:click="$parent.revertirAnulacionManual({{ $vehiculo->id }}, '{{ addslashes(get_class($vehiculo)) }}')" 
                                            wire:confirm="¿Está seguro que desea eliminar la anulación manual y volver al estado calculado por el sistema?"
                                            class="btn-secondary-sm">
                                        Revertir
                                    </button>
                                @else
                                    @if ($acceso['habilitado'])
                                        <button wire:click="$parent.abrirModalAnulacion({{ $vehiculo->id }}, '{{ addslashes(get_class($vehiculo)) }}', 'RESTRINGIR')" class="btn-danger-sm">
                                            Restringir
                                        </button>
                                    @else
                                        <button wire:click="$parent.abrirModalAnulacion({{ $vehiculo->id }}, '{{ addslashes(get_class($vehiculo)) }}', 'HABILITAR')" class="btn-primary-sm">
                                            Habilitar
                                        </button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="table-cell text-center border border-gray-300 dark:border-gray-600 px-6 py-4">No se encontraron vehículos en este contexto.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="mt-4">{{ $vehiculos->links('pagination::tailwind', ['pageName' => 'vehiculosPage']) }}</div>
</div>