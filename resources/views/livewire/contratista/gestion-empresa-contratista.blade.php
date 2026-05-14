<div>
    @if (!$mandanteId)
        <div class="p-4 text-center text-gray-500 dark:text-gray-400">
            Por favor, seleccione una Principal para ver las asignaciones y documentos requeridos para su empresa.
        </div>
    @else
        <div class="overflow-x-auto shadow-md sm:rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="table-header border-r border-gray-200 dark:border-gray-600 text-center">Unidad Operativa (U.O.)
                        </th>
                        @if(!$sinAcreditacion)
                        <th class="table-header border-r border-gray-200 dark:border-gray-600 text-center">% Cumplimiento
                        </th>
                        <th class="table-header border-r border-gray-200 dark:border-gray-600 text-center">Acceso</th>
                        @endif

                        @if (!empty($documentosMaestros))
                            @foreach ($documentosMaestros as $doc)
                                <th class="table-header border-r border-gray-200 dark:border-gray-600 text-center"
                                    title="{{ \App\Models\NombreDocumento::find($doc['nombre_documento_id'])->nombre ?? 'Documento' }}">
                                    {{ $doc['numero'] }}
                                </th>
                            @endforeach
                        @endif

                        <th class="table-header text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($asignaciones as $asignacion)
                        <tr wire:key="asignacion-empresa-{{ $asignacion['id'] }}"
                            class="hover:bg-blue-50 dark:hover:bg-gray-700 odd:bg-white even:bg-gray-50 dark:odd:bg-gray-800 dark:even:bg-gray-800/50">
                            <td class="table-cell border-r border-gray-200 dark:border-gray-600 font-medium">
                                {{ $asignacion['unidad_organizacional_nombre'] }}</td>
                            @if(!$sinAcreditacion)
                            <td
                                class="table-cell border-r border-gray-200 dark:border-gray-600 text-center font-semibold {{ $asignacion['porcentaje_cumplimiento'] < 100 ? 'text-orange-600' : 'text-green-600' }}">
                                {{ $asignacion['porcentaje_cumplimiento'] }}%
                            </td>
                            <td class="table-cell border-r border-gray-200 dark:border-gray-600 text-center">
                                <span class="inline-flex items-center justify-center w-full h-full"
                                    title="{{ $asignacion['acceso_motivo'] }}">
                                    @if ($asignacion['acceso_habilitado'])
                                        <x-icons.check-circle class="w-6 h-6 text-green-500" />
                                    @else
                                        <x-icons.x-circle class="w-6 h-6 text-red-500" />
                                    @endif
                                </span>
                            </td>
                            @endif

                            @if (!empty($documentosMaestros))
                                @foreach ($documentosMaestros as $doc)
                                    <td class="table-cell border-r border-gray-200 dark:border-gray-600 text-center">
                                        {{-- ================== INICIO DE LA MODIFICACIÓN (CENTRAR SÍMBOLO) ================== --}}
                                        <div class="flex justify-center items-center">
                                            {{-- ================== FIN DE LA MODIFICACIÓN ======================================== --}}
                                            @php
                                                $estado = $asignacion['estados_documentos']->get(
                                                    $doc['nombre_documento_id'],
                                                );
                                                $simbolo = '-';
                                                $textColorClass = 'text-gray-500';
                                                $title = 'No Cargado';

                                                if ($estado) {
                                                    $textColorClass = match ($estado) {
                                                        'Aprobado', 'Aprobado-Modificado' => 'text-green-500',
                                                        'Rechazado' => 'text-red-500',
                                                        'Vencido' => 'text-orange-500',
                                                        'Pendiente Validación' => 'text-blue-500',
                                                        'En Revisión' => 'text-purple-500',
                                                        default => 'text-gray-500',
                                                    };
                                                    $simbolo = match ($estado) {
                                                        'Aprobado', 'Aprobado-Modificado' => 'A',
                                                        'Rechazado' => 'R',
                                                        'Vencido' => 'V',
                                                        'Pendiente Validación' => 'P',
                                                        'En Revisión' => 'ER',
                                                        default => '-',
                                                    };
                                                    $title = $estado;
                                                } else {
                                                    $simbolo = 'N/A';
                                                    $title = 'No Aplica';
                                                    $textColorClass = 'text-gray-400';
                                                }
                                            @endphp
                                            <span class="font-bold text-lg {{ $textColorClass }}"
                                                title="{{ $title }}">
                                                @if ($simbolo === 'A')
                                                    <x-icons.check class="w-6 h-6 inline-block" />
                                                @else
                                                    {{ $simbolo }}
                                                @endif
                                            </span>
                                            {{-- ================== INICIO DE LA MODIFICACIÓN (CIERRE DE DIV) ================== --}}
                                        </div>
                                        {{-- ================== FIN DE LA MODIFICACIÓN ======================================== --}}
                                    </td>
                                @endforeach
                            @endif

                            <td class="table-cell text-center">
                                {{-- ================== INICIO DE LA MODIFICACIÓN: PASAR VINCULACIÓN ================== --}}
                                @if(!$sinAcreditacion)
                                <button
                                    wire:click="abrirModalCargaDocumentos({{ $asignacion['mandante_id'] }}, {{ $asignacion['unidad_organizacional_id'] }}, {{ $asignacion['vinculacion_contratista_id'] ?? 'null' }})"
                                    class="action-button-info" title="Gestionar Documentos para esta U.O.">
                                    <x-icons.document-text class="inline-block" />
                                </button>
                                @else
                                <span class="text-xs text-amber-600 font-bold italic">Sin acceso</span>
                                @endif
                                {{-- ================== FIN DE LA MODIFICACIÓN ================== --}}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 4 + count($documentosMaestros) }}" class="table-cell text-center">
                                Su empresa no tiene asignaciones a Unidades Operativas para los filtros
                                seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
