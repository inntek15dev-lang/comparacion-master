<div class="mb-4">
    <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Personas</h5>
    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="table-header">RUT/NUIP/DNI/CEDULA/CPF</th>
                    <th class="table-header">Trabajador</th>
                    <th class="table-header">U.O. (Contexto)</th>
                    <th class="table-header text-center">% Cumplimiento</th>
                    <th class="table-header text-center">Acceso</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @php
                    $trabajadoresMostrados = 0;
                @endphp
                @foreach ($trabajadores as $trabajador)
                    @foreach($trabajador->vinculaciones as $vinculacion)
                        @if(in_array($vinculacion->unidad_organizacional_mandante_id, $filtroUoIds))
                            @php
                                $trabajadoresMostrados++;
                                $acceso = $trabajador->determinarAccesoHabilitado($mandanteId, $vinculacion->unidad_organizacional_mandante_id);
                                $cumplimiento = $trabajador->calcularPorcentajeCumplimiento($mandanteId, $vinculacion->unidad_organizacional_mandante_id);
                            @endphp
                            <tr class="table-row-hover">
                                <td class="table-cell">{{ $trabajador->rut }}</td>
                                <td class="table-cell">{{ $trabajador->nombre_completo }}</td>
                                <td class="table-cell text-xs">{{ $vinculacion->unidadOrganizacionalMandante->nombre_unidad }}</td>
                                <td class="table-cell text-center font-semibold">{{ $cumplimiento }}%</td>
                                <td class="table-cell text-center">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $acceso['habilitado'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $acceso['motivo'] }}
                                    </span>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                @endforeach
                @if($trabajadoresMostrados === 0)
                    <tr><td colspan="5" class="table-cell text-center">No se encontraron trabajadores para los filtros de U.O. seleccionados.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>