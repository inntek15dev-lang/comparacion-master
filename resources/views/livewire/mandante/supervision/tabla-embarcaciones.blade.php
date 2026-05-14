<div>
    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar embarcación por matrícula..." class="input-field w-full mb-4">
    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="table-header">Matrícula</th>
                    <th class="table-header">Tipo</th>
                    <th class="table-header text-center">% Cumplimiento</th>
                    <th class="table-header text-center">Acceso</th>
                    <th class="table-header text-center">Venc. Anulación</th>
                    <th class="table-header text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($embarcaciones as $embarcacion)
                    @php
                        $uo = $embarcacion->contratista->unidadesOrganizacionalesMandante->where('mandante_id', $mandanteId)->first();
                        if ($uo) {
                            $uoId = $uo->id;
                            $acceso = $embarcacion->determinarAccesoHabilitado($mandanteId, $uoId);
                            $cumplimiento = $embarcacion->calcularPorcentajeCumplimiento($mandanteId, $uoId);
                        } else {
                            $acceso = ['habilitado' => false, 'motivo' => 'Sin UO Asignada'];
                            $cumplimiento = 0;
                        }
                    @endphp
                    <tr class="table-row-hover">
                        <td class="table-cell font-mono">{{ $embarcacion->matricula_completa }}</td>
                        <td class="table-cell">{{ $embarcacion->tipoEmbarcacion->nombre ?? 'N/A' }}</td>
                        <td class="table-cell text-center font-semibold">{{ $cumplimiento }}%</td>
                        <td class="table-cell text-center">
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $acceso['habilitado'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $acceso['motivo'] }}
                            </span>
                        </td>
                        <td class="table-cell text-center text-xs">
                            @if($acceso['es_excepcion'] && $embarcacion->anulacionManualActiva?->valido_hasta)
                                {{ \Carbon\Carbon::parse($embarcacion->anulacionManualActiva->valido_hasta)->format('d-m-Y') }}
                            @else
                                ---
                            @endif
                        </td>
                        <td class="table-cell text-center">
                            @if ($acceso['habilitado'])
                                <button wire:click="$parent.abrirModalAnulacion({{ $embarcacion->id }}, '{{ addslashes(get_class($embarcacion)) }}', 'RESTRINGIR')" class="btn-danger-sm">
                                    Restringir
                                </button>
                            @else
                                <button wire:click="$parent.abrirModalAnulacion({{ $embarcacion->id }}, '{{ addslashes(get_class($embarcacion)) }}', 'HABILITAR')" class="btn-primary-sm">
                                    Habilitar
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-cell text-center">No se encontraron embarcaciones.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $embarcaciones->links('pagination::tailwind', ['pageName' => 'embarcacionesPage']) }}</div>
</div>