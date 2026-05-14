<div>
    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar vehículo por patente..." class="input-field w-full mb-4">
    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="table-header">Patente</th>
                    <th class="table-header">Tipo / Marca</th>
                    <th class="table-header text-center">% Cumplimiento</th>
                    <th class="table-header text-center">Acceso</th>
                    <th class="table-header text-center">Venc. Anulación</th>
                    <th class="table-header text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($vehiculos as $vehiculo)
                    @php
                        $uo = $vehiculo->contratista->unidadesOrganizacionalesMandante->where('mandante_id', $mandanteId)->first();
                        if ($uo) {
                            $uoId = $uo->id;
                            $acceso = $vehiculo->determinarAccesoHabilitado($mandanteId, $uoId);
                            $cumplimiento = $vehiculo->calcularPorcentajeCumplimiento($mandanteId, $uoId);
                        } else {
                            $acceso = ['habilitado' => false, 'motivo' => 'Sin UO Asignada'];
                            $cumplimiento = 0;
                        }
                    @endphp
                    <tr class="table-row-hover">
                        <td class="table-cell font-mono">{{ $vehiculo->patente_completa }}</td>
                        <td class="table-cell">{{ $vehiculo->tipoVehiculo->nombre ?? 'N/A' }} / {{ $vehiculo->marcaVehiculo->nombre ?? 'N/A' }}</td>
                        <td class="table-cell text-center font-semibold">{{ $cumplimiento }}%</td>
                        <td class="table-cell text-center">
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $acceso['habilitado'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $acceso['motivo'] }}
                            </span>
                        </td>
                        <td class="table-cell text-center text-xs">
                            @if($acceso['es_excepcion'] && $vehiculo->anulacionManualActiva?->valido_hasta)
                                {{ \Carbon\Carbon::parse($vehiculo->anulacionManualActiva->valido_hasta)->format('d-m-Y') }}
                            @else
                                ---
                            @endif
                        </td>
                        <td class="table-cell text-center">
                            @if ($acceso['habilitado'])
                                <button wire:click="$parent.abrirModalAnulacion({{ $vehiculo->id }}, '{{ addslashes(get_class($vehiculo)) }}', 'RESTRINGIR')" class="btn-danger-sm">
                                    Restringir
                                </button>
                            @else
                                <button wire:click="$parent.abrirModalAnulacion({{ $vehiculo->id }}, '{{ addslashes(get_class($vehiculo)) }}', 'HABILITAR')" class="btn-primary-sm">
                                    Habilitar
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-cell text-center">No se encontraron vehículos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $vehiculos->links('pagination::tailwind', ['pageName' => 'vehiculosPage']) }}</div>
</div>