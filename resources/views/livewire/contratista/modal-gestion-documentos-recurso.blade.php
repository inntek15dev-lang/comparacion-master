<div>
    @if ($showModal && $recurso)
        <div class="fixed z-[100] inset-0 overflow-y-auto" aria-labelledby="modal-title-documentos" role="dialog" aria-modal="true">
            <div class="flex items-start justify-center min-h-screen pt-24 px-4 pb-20 text-center">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75" aria-hidden="true" wire:click="cerrarModal"></div>
                
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
                
                <div class="inline-block bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-full lg:max-w-7xl xl:max-w-screen-2xl sm:w-full">
                    <form wire:submit.prevent="cargarDocumentos">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-xl leading-6 font-medium text-gray-900 dark:text-gray-100 section-title mb-1" id="modal-title-documentos">
                                        Documentos Requeridos para: <span class="font-semibold">{{ $nombreRecursoParaModal }}</span>
                                    </h3>
                                    @if($identificadorRecursoParaModal)
                                        <p class="text-sm text-gray-900 dark:text-gray-900">{{ $identificadorRecursoParaModal }}</p>
                                    @endif
                                    <p class="text-sm text-gray-900 dark:text-gray-900">
                                        Vinculación: {{ $contextoParaModal }}</p>
                                        @if(isset($infoExtraParaModal['cargo']))
                                            Cargo: {{ $infoExtraParaModal['cargo'] }}
                                        @endif
                                    </p>
                                    
                                    <div class="mt-6 overflow-x-auto shadow-md sm:rounded-lg border border-gray-400 dark:border-gray-500">
                                        <table class="min-w-full">
                                            <thead class="bg-gray-100 dark:bg-gray-700">
                                                <tr>
                                                    <th class="table-header-sm px-2 border-r border-gray-400 dark:border-gray-500" style="width: 3%;">N°</th>
                                                    <th class="table-header-sm border-r border-gray-400 dark:border-gray-500" style="width: 30.5%;">Documento</th>
                                                    <th class="table-header-sm text-center border-r border-gray-400 dark:border-gray-500" style="width: 6%;">Afecta %</th>
                                                    <th class="table-header-sm text-center border-r border-gray-400 dark:border-gray-500" style="width: 10%;">Restringe Acceso</th>
                                                    <th class="table-header-sm border-r border-gray-400 dark:border-gray-500" style="width: 9%;">Estado Actual</th>
                                                    <th class="table-header-sm border-r border-gray-400 dark:border-gray-500" style="width: 7.5%;">Vencimiento</th>
                                                    <th class="table-header-sm border-r border-gray-400 dark:border-gray-500" style="min-width: 350px;">Seleccione Fecha(s) y Archivo</th>
                                                    <th class="table-header-sm text-center" style="width: 5%;">Requisitos</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-400 dark:divide-gray-500">
                                                @forelse ($documentosRequeridos as $index => $doc)
                                                    @php
                                                        $key = $doc['archivo_cargado'] ? $doc['archivo_cargado']->id : 'regla_' . $doc['regla_documental_id_origen'];
                                                        $estado = $doc['estado_actual_documento'];
                                                        $colorClass = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
                                                        if ($estado === 'No Cargado' || $estado === 'Rechazado' || in_array($estado, ['Vencido', 'Vencido-Modificado'])) { 
                                                            $colorClass = 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100'; 
                                                        }
                                                        if (in_array($estado, ['Aprobado', 'Aprobado-Modificado'])) { 
                                                            $colorClass = 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'; 
                                                        }
                                                        if ($estado === 'Pendiente Validación' || $estado === 'En Revisión' || $estado === 'Pendiente Validación Mandante') { 
                                                            $colorClass = 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100'; 
                                                        }
                                                        
                                                        $tooltipText = '';
                                                        if ($doc['estado_actual_documento'] === 'Rechazado' && $doc['motivo_rechazo']) {
                                                            $tooltipText .= $this->formatarMotivoRechazo($doc['motivo_rechazo']);
                                                        }
                                                        if ($doc['archivo_cargado']?->observacion_validador) {
                                                            if (!empty($tooltipText)) { $tooltipText .= "\n\n"; }
                                                            $tooltipText .= "Observación del Validador:\n" . $doc['archivo_cargado']->observacion_validador;
                                                        }
                                                    @endphp
                                                    <tr wire:key="{{ $key }}" class="text-sm {{ $loop->iteration % 2 == 0 ? 'bg-violet-100 dark:bg-violet-900/30' : 'bg-white dark:bg-gray-800' }}">
                                                        <td class="table-cell-sm px-2 border-r border-gray-400 dark:border-gray-500">{{ $loop->iteration }}</td>
                                                        <td class="table-cell-sm font-medium border-r border-gray-400 dark:border-gray-500">{{ $doc['nombre_documento_texto'] }}</td>
                                                        <td class="table-cell-sm text-center font-semibold text-black dark:text-white border-r border-gray-400 dark:border-gray-500">{{ $doc['afecta_cumplimiento'] ? 'SI' : 'NO' }}</td>
                                                        <td class="table-cell-sm text-center font-semibold text-black dark:text-white border-r border-gray-400 dark:border-gray-500">{{ $doc['restringe_acceso'] ? 'SI' : 'NO' }}</td>
                                                        <td class="table-cell-sm border-r border-gray-400 dark:border-gray-500">
                                                            <div class="flex items-center space-x-2">
                                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $colorClass }}"
                                                                      @if(!empty($tooltipText)) title="{{ $tooltipText }}" @endif
                                                                >{{ $doc['estado_actual_documento'] }}</span>

                                                                @if($doc['archivo_cargado'] && is_null($doc['archivo_cargado']->resultado_validacion))
                                                                    <button type="button" wire:click="eliminarDocumentoCargado({{ $doc['archivo_cargado']->id }})" wire:confirm="¿Seguro?" class="text-red-500 hover:text-red-700"><x-icons.trash class="w-4 h-4" /></button>
                                                                @endif
                                                            </div>
                                                            @if($doc['archivo_cargado'])
                                                                <a href="{{ $doc['archivo_cargado']->url }}" target="_blank" class="text-xs text-blue-500 hover:text-blue-700 block mt-1">Ver Archivo</a>
                                                            @endif
                                                        </td>
                                                        <td class="table-cell-sm border-r border-gray-400 dark:border-gray-500 font-semibold">
                                                            <!-- ================== INICIO DE LA CORRECCIÓN ================== -->
                                                            @if ($doc['archivo_cargado'])
                                                                {{ $doc['archivo_cargado']->fecha_vencimiento_formateada }}
                                                            @else
                                                                <span class="text-gray-400 font-normal">N/A</span>
                                                            @endif
                                                            <!-- ================== FIN DE LA CORRECCIÓN ==================== -->
                                                        </td>
                                                        <td class="table-cell-sm border-r border-gray-400 dark:border-gray-500">
                                                            <div class="flex items-center space-x-2">
                                                                @if ($doc['tipo_vencimiento_nombre'] === 'POR PERIODO')
                                                                    <div class="flex-shrink-0 w-40">
                                                                        <label for="periodo_{{ $key }}" class="text-xs font-semibold">Período a Cargar:</label>
                                                                        <input id="periodo_{{ $key }}" type="text" class="input-field-sm w-full py-1 bg-gray-200" readonly value="{{ $documentosParaCargar[$key]['periodo_input'] ?? 'N/A' }}">
                                                                    </div>
                                                                @else
                                                                    @if ($doc['valida_emision'] || $doc['tipo_vencimiento_nombre'] === 'DESDE EMISION')
                                                                        <div class="flex-shrink-0 w-40">
                                                                            <label for="fecha_emision_{{ $key }}" class="text-xs font-semibold">F. Emisión:</label>
                                                                            <input id="fecha_emision_{{ $key }}" type="date" class="input-field-sm w-full py-1" wire:model.defer="documentosParaCargar.{{ $key }}.fecha_emision_input">
                                                                            @error('documentosParaCargar.' . $key . '.fecha_emision_input') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                                                        </div>
                                                                    @endif
                                                                    @if ($doc['valida_vencimiento'] || $doc['tipo_vencimiento_nombre'] === 'SEGUN DOCUMENTO')
                                                                        <div class="flex-shrink-0 w-40">
                                                                            <label for="fecha_vencimiento_{{ $key }}" class="text-xs font-semibold">F. Vencimiento:</label>
                                                                            <input id="fecha_vencimiento_{{ $key }}" type="date" class="input-field-sm w-full py-1" wire:model.defer="documentosParaCargar.{{ $key }}.fecha_vencimiento_input">
                                                                            @error('documentosParaCargar.' . $key . '.fecha_vencimiento_input') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                                                        </div>
                                                                    @endif
                                                                @endif
                                                                
                                                                <div class="flex-grow" x-data="{ filename: '' }">
                                                                    <label class="text-xs font-semibold">Archivo:</label>
                                                                    <div class="flex items-center space-x-2">
                                                                        <input type="file" id="file-input-{{ $key }}" class="hidden"
                                                                               wire:model="documentosParaCargar.{{ $key }}.archivo_input"
                                                                               @change="filename = $event.target.files.length > 0 ? $event.target.files[0].name : ''">
                                                                        
                                                                        <label for="file-input-{{ $key }}" class="cursor-pointer inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                                            Seleccionar...
                                                                        </label>
                                                                        
                                                                        <span x-text="filename" class="text-xs text-gray-500 dark:text-gray-400 truncate" title="filename"></span>
                                                                        
                                                                        <div wire:loading wire:target="documentosParaCargar.{{ $key }}.archivo_input" class="text-xs text-blue-500">Cargando...</div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="table-cell-sm text-center">
                                                            <button type="button" wire:click="abrirModalInfoCarga('{{ $key }}')" class="text-blue-600 hover:text-blue-800 font-semibold">Ver Más</button>
                                                        </td>
                                                    </tr>
                                                    @if(isset($uploadSuccess[$key]) || $errors->has('documentosParaCargar.' . $key . '.archivo_input'))
                                                        <tr class="text-xs"><td colspan="8" class="p-1 px-4">
                                                            @if(isset($uploadSuccess[$key]))<span class="text-green-600 font-semibold flex items-center"><x-icons.check-circle-solid class="w-4 h-4 mr-1"/> {{ $uploadSuccess[$key] }}</span>@endif
                                                            @error('documentosParaCargar.' . $key . '.archivo_input')<span class="text-red-600 font-semibold flex items-center"><x-icons.x-circle-solid class="w-4 h-4 mr-1"/> {{ $message }}</span>@enderror
                                                        </td></tr>
                                                    @endif
                                                @empty
                                                    <tr><td colspan="8" class="table-cell text-center p-4">No se encontraron documentos requeridos.</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="btn-primary sm:ms-3" wire:loading.attr="disabled"><x-icons.upload class="w-5 h-5 mr-1 inline-block"/> Cargar Documentos</button>
                            <button type="button" wire:click="cerrarModal" class="btn-secondary">Cerrar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($showModalInfoCarga)
        <div class="fixed z-[110] inset-0 overflow-y-auto" aria-labelledby="modal-title-info-carga" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="cerrarModalInfoCarga"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">Manual de Campo Táctico</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $infoCargaSeleccionada['nombre_documento_texto'] ?? 'N/A' }}</p>
                        <div class="mt-4 border-t dark:border-gray-600">
                            <div class="divide-y dark:divide-gray-600">
                                <div class="py-3 grid grid-cols-3 gap-4">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo de Vigencia</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100 col-span-2">{{ $infoCargaSeleccionada['tipo_vencimiento_nombre'] ?? 'No especificada' }}</dd>
                                </div>

                                @if($infoCargaSeleccionada['tipo_vencimiento_nombre'] === 'DESDE EMISION' && !empty($infoCargaSeleccionada['dias_validez_documento']))
                                <div class="py-3 grid grid-cols-3 gap-4">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Validez desde Emisión</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100 col-span-2 font-semibold">{{ $infoCargaSeleccionada['dias_validez_documento'] }} días</dd>
                                </div>
                                @endif

                                @if($infoCargaSeleccionada['tipo_vencimiento_nombre'] === 'DESDE CARGA' && !empty($infoCargaSeleccionada['dias_validez_documento']))
                                <div class="py-3 grid grid-cols-3 gap-4">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Validez desde Carga</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100 col-span-2 font-semibold">{{ $infoCargaSeleccionada['dias_validez_documento'] }} días</dd>
                                </div>
                                @endif

                                @if($infoCargaSeleccionada['tipo_vencimiento_nombre'] === 'POR PERIODO' && !empty($infoCargaSeleccionada['dias_gracia_carga']))
                                <div class="py-3 grid grid-cols-3 gap-4">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Días de Gracia para Carga</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100 col-span-2 font-semibold">{{ $infoCargaSeleccionada['dias_gracia_carga'] }} días en el mes siguiente</dd>
                                </div>
                                @endif

                                @if (!empty($infoCargaSeleccionada['criterios_evaluacion']))
                                <div class="py-3 grid grid-cols-3 gap-4">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 align-top">Criterios de Evaluación</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100 col-span-2">
                                        <ul class="space-y-3">
                                            @foreach($infoCargaSeleccionada['criterios_evaluacion'] as $criterio)
                                            <li class="flex">
                                                <span class="font-bold mr-2">{{ $loop->iteration }}.</span>
                                                <div>
                                                    <span>{{ $criterio['criterio'] ?? 'N/A' }}</span>
                                                    @if(!empty($criterio['sub_criterio']))
                                                        <span class="block text-blue-600 dark:text-blue-400 font-semibold ml-4">- {{ $criterio['sub_criterio'] }}</span>
                                                    @endif
                                                    @if(!empty($criterio['aclaracion']))
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 italic mt-1 ml-4">{{ $criterio['aclaracion'] }}</p>
                                                    @endif
                                                </div>
                                            </li>
                                            @endforeach
                                        </ul>
                                    </dd>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="cerrarModalInfoCarga" class="btn-primary">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>