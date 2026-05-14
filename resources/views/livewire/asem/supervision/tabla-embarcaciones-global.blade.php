<div>
    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar embarcación por matrícula..." class="input-field w-full mb-4">
    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="table-header">#</th>
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
                        $acceso = $embarcacion->determinarAccesoHabilitado($mandanteId, $this->uoId);
                        $cumplimiento = $embarcacion->calcularPorcentajeCumplimiento($mandanteId, $this->uoId);
                    @endphp
                    <tr class="table-row-hover">
                        <td class="table-cell font-mono text-center">{{ ($embarcaciones->currentPage() - 1) * $embarcaciones->perPage() + $loop->iteration }}</td>
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
                            @if(!$esSoloLectura)
                            @if($acceso['es_excepcion'])
                                <button wire:click="$parent.revertirAnulacionManual({{ $embarcacion->id }}, '{{ addslashes(get_class($embarcacion)) }}')" 
                                        wire:confirm="¿Está seguro que desea eliminar la anulación manual y volver al estado calculado por el sistema?"
                                        class="btn-secondary-sm">
                                    Revertir
                                </button>
                            @else
                                @if ($acceso['habilitado'])
                                    <button wire:click="$parent.abrirModalAnulacion({{ $embarcacion->id }}, '{{ addslashes(get_class($embarcacion)) }}', 'RESTRINGIR')" class="btn-danger-sm">
                                        Restringir
                                    </button>
                                @else
                                    <button wire:click="$parent.abrirModalAnulacion({{ $embarcacion->id }}, '{{ addslashes(get_class($embarcacion)) }}', 'HABILITAR')" class="btn-primary-sm">
                                        Habilitar
                                    </button>
                                @endif
                            @endif
                            @endif{{-- /esSoloLectura --}}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="table-cell text-center">No se encontraron embarcaciones en este contexto.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $embarcaciones->links('pagination::tailwind', ['pageName' => 'embarcacionesPage']) }}</div>
</div>