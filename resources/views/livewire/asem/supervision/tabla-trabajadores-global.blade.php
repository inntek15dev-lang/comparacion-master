<div>
    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar por CEDULA, RUT o nombre del trabajador" class="input-field w-full mb-4">
    
    <div class="overflow-hidden shadow-md sm:rounded-lg border border-gray-300 dark:border-gray-600">
        <div class="overflow-x-auto overflow-y-auto" style="max-height: 70vh;">
            <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-600">
                <thead class="bg-gray-100 dark:bg-gray-700 sticky top-0 z-10">
                    <tr>
                        <th class="table-header border border-gray-300 dark:border-gray-600 px-6 py-4 bg-gray-100 dark:bg-gray-700">#</th>
                        <th class="table-header border border-gray-300 dark:border-gray-600 px-6 py-4 bg-gray-100 dark:bg-gray-700">RUT/CEDULA</th>
                        <th class="table-header border border-gray-300 dark:border-gray-600 px-6 py-4 bg-gray-100 dark:bg-gray-700">Trabajador</th>
                        <th class="table-header text-center border border-gray-300 dark:border-gray-600 px-6 py-4 bg-gray-100 dark:bg-gray-700">% Cumplimiento</th>
                        <th class="table-header text-center border border-gray-300 dark:border-gray-600 px-6 py-4 bg-gray-100 dark:bg-gray-700">Acceso</th>
                        <th class="table-header text-center border border-gray-300 dark:border-gray-600 px-6 py-4 bg-gray-100 dark:bg-gray-700">Venc. Anulación</th>
                        <th class="table-header text-center border border-gray-300 dark:border-gray-600 px-6 py-4 bg-gray-100 dark:bg-gray-700">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800">
                    @forelse ($trabajadores as $trabajador)
                        @php
                            $acceso = $trabajador->determinarAccesoHabilitado($mandanteId, $this->uoId);
                            $cumplimiento = $trabajador->calcularPorcentajeCumplimiento($mandanteId, $this->uoId);
                        @endphp
                        <tr class="hover:bg-blue-50 dark:hover:bg-blue-900/20 {{ $loop->even ? 'bg-orange-50 dark:bg-orange-900/20' : 'bg-white dark:bg-gray-800' }}">
                            <td class="table-cell font-mono text-center border border-gray-300 dark:border-gray-600 px-6 py-4">{{ ($trabajadores->currentPage() - 1) * $trabajadores->perPage() + $loop->iteration }}</td>
                            <td class="table-cell border border-gray-300 dark:border-gray-600 px-6 py-4">{{ $trabajador->rut }}</td>
                            <td class="table-cell border border-gray-300 dark:border-gray-600 px-6 py-4">{{ $trabajador->nombre_completo }}</td>
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
                                @if($acceso['es_excepcion'] && $trabajador->anulacionManualActiva?->valido_hasta)
                                    {{ \Carbon\Carbon::parse($trabajador->anulacionManualActiva->valido_hasta)->format('d-m-Y') }}
                                @else
                                    ---
                                @endif
                            </td>
                            
                            <td class="table-cell text-center border border-gray-300 dark:border-gray-600 px-6 py-4">
                                @if(!$esSoloLectura)
                                    @if($acceso['es_excepcion'])
                                        <button wire:click="$parent.revertirAnulacionManual({{ $trabajador->id }}, '{{ addslashes(get_class($trabajador)) }}')" 
                                                wire:confirm="¿Está seguro que desea eliminar la anulación manual y volver al estado calculado por el sistema?"
                                                class="btn-secondary-sm">
                                            Revertir
                                        </button>
                                    @else
                                        @if ($acceso['habilitado'])
                                            <button wire:click="$parent.abrirModalAnulacion({{ $trabajador->id }}, '{{ addslashes(get_class($trabajador)) }}', 'RESTRINGIR')" class="btn-danger-sm">
                                                Restringir
                                            </button>
                                        @else
                                            <button wire:click="$parent.abrirModalAnulacion({{ $trabajador->id }}, '{{ addslashes(get_class($trabajador)) }}', 'HABILITAR')" class="btn-primary-sm">
                                                Habilitar
                                            </button>
                                        @endif
                                    @endif
                                @endif{{-- /esSoloLectura --}}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="table-cell text-center border border-gray-300 dark:border-gray-600 px-6 py-4">No se encontraron trabajadores en este contexto.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="mt-4">{{ $trabajadores->links() }}</div>
</div>