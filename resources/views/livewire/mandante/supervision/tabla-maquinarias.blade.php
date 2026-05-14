<div>
    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar maquinaria por identificador..." class="input-field w-full mb-4">
    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="table-header">Identificador</th>
                    <th class="table-header">Tipo / Marca</th>
                    <th class="table-header text-center">% Cumplimiento</th>
                    <th class="table-header text-center">Acceso</th>
                    <th class="table-header text-center">Venc. Anulación</th>
                    <th class="table-header text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($maquinarias as $maquinaria)
                    @php
                        $uo = $maquinaria->contratista->unidadesOrganizacionalesMandante->where('mandante_id', $mandanteId)->first();
                        if ($uo) {
                            $uoId = $uo->id;
                            $acceso = $maquinaria->determinarAccesoHabilitado($mandanteId, $uoId);
                            $cumplimiento = $maquinaria->calcularPorcentajeCumplimiento($mandanteId, $uoId);
                        } else {
                            $acceso = ['habilitado' => false, 'motivo' => 'Sin UO Asignada'];
                            $cumplimiento = 0;
                        }
                    @endphp
                    <tr class="table-row-hover">
                        <td class="table-cell font-mono">{{ $maquinaria->identificador_completo }}</td>
                        <td class="table-cell">{{ $maquinaria->tipoMaquinaria->nombre ?? 'N/A' }} / {{ $maquinaria->marca->nombre ?? 'N/A' }}</td>
                        <td class="table-cell text-center font-semibold">{{ $cumplimiento }}%</td>
                        <td class="table-cell text-center">
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $acceso['habilitado'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $acceso['motivo'] }}
                            </span>
                        </td>
                        <td class="table-cell text-center text-xs">
                            @if($acceso['es_excepcion'] && $maquinaria->anulacionManualActiva?->valido_hasta)
                                {{ \Carbon\Carbon::parse($maquinaria->anulacionManualActiva->valido_hasta)->format('d-m-Y') }}
                            @else
                                ---
                            @endif
                        </td>
                        <td class="table-cell text-center">
                            @if ($acceso['habilitado'])
                                <button wire:click="$parent.abrirModalAnulacion({{ $maquinaria->id }}, '{{ addslashes(get_class($maquinaria)) }}', 'RESTRINGIR')" class="btn-danger-sm">
                                    Restringir
                                </button>
                            @else
                                <button wire:click="$parent.abrirModalAnulacion({{ $maquinaria->id }}, '{{ addslashes(get_class($maquinaria)) }}', 'HABILITAR')" class="btn-primary-sm">
                                    Habilitar
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-cell text-center">No se encontraron maquinarias.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $maquinarias->links('pagination::tailwind', ['pageName' => 'maquinariasPage']) }}</div>
</div>