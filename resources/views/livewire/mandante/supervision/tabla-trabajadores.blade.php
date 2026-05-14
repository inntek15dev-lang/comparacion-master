<div>
    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar trabajador por RUT o nombre..." class="input-field w-full mb-4">
    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="table-header">RUT</th>
                    <th class="table-header">Trabajador</th>
                    <th class="table-header text-center">% Cumplimiento</th>
                    <th class="table-header text-center">Acceso</th>
                    <!-- ========================================================================================= -->
                    <!-- INICIO: REFORZAMIENTO VISUAL -->
                    <!-- Se actualiza el encabezado de la columna según la directriz. -->
                    <!-- ========================================================================================= -->
                    <th class="table-header text-center">EXPIRACION EXCEPCION</th>
                    <!-- ========================================================================================= -->
                    <!-- FIN: REFORZAMIENTO VISUAL -->
                    <!-- ========================================================================================= -->
                    <th class="table-header text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($trabajadores as $trabajador)
                    @php
                        $uo = $trabajador->contratista->unidadesOrganizacionalesMandante->where('mandante_id', $mandanteId)->first();
                        if ($uo) {
                            $uoId = $uo->id;
                            $acceso = $trabajador->determinarAccesoHabilitado($mandanteId, $uoId);
                            $cumplimiento = $trabajador->calcularPorcentajeCumplimiento($mandanteId, $uoId);
                        } else {
                            $acceso = ['habilitado' => false, 'motivo' => 'Sin UO Asignada', 'es_excepcion' => false];
                            $cumplimiento = 0;
                        }
                    @endphp
                    <tr class="table-row-hover">
                        <td class="table-cell">{{ $trabajador->rut }}</td>
                        <td class="table-cell">{{ $trabajador->nombre_completo }}</td>
                        <td class="table-cell text-center font-semibold">{{ $cumplimiento }}%</td>
                        <td class="table-cell text-center">
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $acceso['habilitado'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $acceso['motivo'] }}
                            </span>
                        </td>
                        <td class="table-cell text-center text-xs">
                            @if($acceso['es_excepcion'] && $trabajador->anulacionManualActiva?->valido_hasta)
                                {{ \Carbon\Carbon::parse($trabajador->anulacionManualActiva->valido_hasta)->format('d-m-Y') }}
                            @else
                                ---
                            @endif
                        </td>
                        <td class="table-cell text-center">
                            @if ($acceso['habilitado'])
                                <button wire:click="$parent.abrirModalAnulacion({{ $trabajador->id }}, '{{ addslashes(get_class($trabajador)) }}', 'RESTRINGIR')" class="btn-danger-sm">
                                    Restringir
                                </button>
                            @else
                                <button wire:click="$parent.abrirModalAnulacion({{ $trabajador->id }}, '{{ addslashes(get_class($trabajador)) }}', 'HABILITAR')" class="btn-primary-sm">
                                    Habilitar
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-cell text-center">No se encontraron trabajadores.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $trabajadores->links() }}</div>
</div>