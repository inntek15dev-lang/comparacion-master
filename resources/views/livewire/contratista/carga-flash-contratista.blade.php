<div class="bg-white dark:bg-gray-800 shadow-2xl rounded-2xl border-2 border-orange-400 dark:border-orange-600 overflow-hidden ring-4 ring-orange-100 dark:ring-orange-900">
    <form wire:submit.prevent="cargarDocumentos">
        <div class="p-6">
            <h3 class="text-lg font-bold text-orange-800 dark:text-orange-300">Carga Rápida de Documentos Prioritarios</h3>
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                Esta vista consolida todos los documentos de sus recursos que están <span class="font-bold">No Cargados</span>, <span class="font-bold">Vencidos</span>, <span class="font-bold">Rechazados</span> o a punto de vencer (próximos 15 días).
            </p>
            @if (session()->has('message_carga_flash'))
                <div class="alert-success mt-4">{{ session('message_carga_flash') }}</div>
            @endif
            @if (session()->has('error_carga_flash'))
                <div class="alert-danger mt-4">{{ session('error_carga_flash') }}</div>
            @endif
            @if (session()->has('info_carga_flash'))
                <div class="alert-info mt-4">{{ session('info_carga_flash') }}</div>
            @endif
        </div>

        <!-- ================================================================== -->
        <!-- PUESTO DE MANDO DE FILTRADO -->
        <!-- ================================================================== -->
        <div class="px-6 pb-4 border-b border-gray-200 dark:border-gray-600">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="filtroRecurso" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Filtrar por Recurso (Nombre/ID)</label>
                    <input type="text" id="filtroRecurso" wire:model.live.debounce.500ms="filtroRecurso" class="input-field w-full mt-1" placeholder="Ej: Juan Perez o 33.333.333-3...">
                </div>
                <div>
                    <label for="filtroDocumento" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Filtrar por Nombre de Documento</label>
                    <input type="text" id="filtroDocumento" wire:model.live.debounce.500ms="filtroDocumento" class="input-field w-full mt-1" placeholder="Ej: Contrato de Trabajo...">
                </div>
            </div>
        </div>

        <!-- ================================================================== -->
        <!-- TABLA CON SCROLL Y ENCABEZADOS FIJOS -->
        <!-- ================================================================== -->
        <div class="overflow-y-auto h-[65vh] relative shadow-inner border-b border-gray-300 dark:border-gray-600">
            <table class="min-w-full border-collapse">
                <thead class="bg-gray-100 dark:bg-gray-700 sticky top-0 z-20 shadow-sm">
                    <tr>
                        <th class="table-header-sm px-2 border-r border-gray-400 dark:border-gray-500 bg-gray-100 dark:bg-gray-700" style="width: 3%;">N°</th>
                        <th class="table-header-sm border-r border-gray-400 dark:border-gray-500 bg-gray-100 dark:bg-gray-700" style="width: 15%;">Recurso</th>
                        <th class="table-header-sm border-r border-gray-400 dark:border-gray-500 bg-gray-100 dark:bg-gray-700" style="width: 25%;">Documento</th>
                        <th class="table-header-sm text-center border-r border-gray-400 dark:border-gray-500 bg-gray-100 dark:bg-gray-700" style="width: 5%;">Afecta %</th>
                        <th class="table-header-sm text-center border-r border-gray-400 dark:border-gray-500 bg-gray-100 dark:bg-gray-700" style="width: 8%;">Restringe Acceso</th>
                        <th class="table-header-sm border-r border-gray-400 dark:border-gray-500 bg-gray-100 dark:bg-gray-700" style="width: 8%;">Estado Actual</th>
                        <th class="table-header-sm border-r border-gray-400 dark:border-gray-500 bg-gray-100 dark:bg-gray-700" style="width: 7%;">Vencimiento</th>
                        
                        {{-- COLUMNAS SEPARADAS --}}
                        <th class="table-header-sm border-r border-gray-400 dark:border-gray-500 bg-gray-100 dark:bg-gray-700" style="width: 15%;">Fechas / Período</th>
                        <th class="table-header-sm border-r border-gray-400 dark:border-gray-500 bg-gray-100 dark:bg-gray-700" style="width: 10%;">Archivo</th>
                        
                        <th class="table-header-sm text-center bg-gray-100 dark:bg-gray-700" style="width: 4%;">Requisitos</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-400 dark:divide-gray-500">
                    @forelse ($documentosUrgentes as $doc)
                        @php
                            $key = $doc['unique_key'];
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
                            if ($doc['estado_flash'] === 'Por Vencer') {
                                $colorClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100';
                            }
                        @endphp
                        <tr wire:key="{{ $key }}" class="text-sm {{ $loop->iteration % 2 == 0 ? 'bg-violet-100 dark:bg-violet-900/30' : 'bg-white dark:bg-gray-800' }}">
                            <td class="table-cell-sm px-2 border-r border-gray-400 dark:border-gray-500">{{ ($documentosUrgentes->currentPage() - 1) * $documentosUrgentes->perPage() + $loop->iteration }}</td>
                            <td class="table-cell-sm px-4 border-r border-gray-400 dark:border-gray-500">
                                <span class="font-bold text-gray-900 dark:text-gray-200">{{ $doc['entidad_nombre'] }}</span>
                                <span class="block text-xs text-gray-600 dark:text-gray-400 font-mono">{{ $doc['entidad_identificador'] }}</span>
                            </td>
                            <td class="table-cell-sm font-medium border-r border-gray-400 dark:border-gray-500">{{ $doc['nombre_documento_texto'] }}</td>
                            <td class="table-cell-sm text-center font-semibold text-black dark:text-white border-r border-gray-400 dark:border-gray-500">{{ $doc['afecta_cumplimiento'] ? 'SI' : 'NO' }}</td>
                            <td class="table-cell-sm text-center font-semibold text-black dark:text-white border-r border-gray-400 dark:border-gray-500">{{ $doc['restringe_acceso'] ? 'SI' : 'NO' }}</td>
                            <td class="table-cell-sm border-r border-gray-400 dark:border-gray-500">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $colorClass }}"
                                      @if($doc['estado_actual_documento'] === 'Rechazado')
                                          title="{{ $this->formatarMotivoRechazo($doc['motivo_rechazo']) }}"
                                      @endif
                                >
                                    {{ $doc['estado_flash'] === 'Por Vencer' ? 'Por Vencer' : $doc['estado_actual_documento'] }}
                                </span>
                            </td>
                            <td class="table-cell-sm border-r border-gray-400 dark:border-gray-500">
                                @if ($doc['archivo_cargado'] && $doc['archivo_cargado']->fecha_vencimiento)
                                    {{ \Carbon\Carbon::parse($doc['archivo_cargado']->fecha_vencimiento)->format('d-m-Y') }}
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            
                            {{-- COLUMNA 1: FECHAS --}}
                            <td class="table-cell-sm border-r border-gray-400 dark:border-gray-500 p-2">
                                <div class="flex flex-col space-y-2">
                                    @if ($doc['tipo_vencimiento_nombre'] === 'POR PERIODO')
                                        <div>
                                            <label class="text-xs font-semibold block text-gray-600 dark:text-gray-300">Período:</label>
                                            <input type="text" class="input-field-sm w-full py-1 bg-gray-200 dark:bg-gray-600" readonly value="{{ $documentosParaCargar[$key]['periodo_input'] ?? 'N/A' }}">
                                        </div>
                                    @else
                                        @if ($doc['valida_emision'] || $doc['tipo_vencimiento_nombre'] === 'DESDE EMISION')
                                            <div>
                                                <label for="fecha_emision_{{ $key }}" class="text-xs font-semibold block text-gray-600 dark:text-gray-300">F. Emisión:</label>
                                                <input id="fecha_emision_{{ $key }}" type="date" class="input-field-sm w-full py-1" wire:model.defer="documentosParaCargar.{{ $key }}.fecha_emision_input">
                                            </div>
                                        @endif
                                        @if ($doc['valida_vencimiento'] || $doc['tipo_vencimiento_nombre'] === 'SEGUN DOCUMENTO')
                                            <div>
                                                <label for="fecha_vencimiento_{{ $key }}" class="text-xs font-semibold block text-gray-600 dark:text-gray-300">F. Vencimiento:</label>
                                                <input id="fecha_vencimiento_{{ $key }}" type="date" class="input-field-sm w-full py-1" wire:model.defer="documentosParaCargar.{{ $key }}.fecha_vencimiento_input">
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </td>

                            {{-- COLUMNA 2: ARCHIVO --}}
                            <td class="table-cell-sm border-r border-gray-400 dark:border-gray-500 p-2 align-middle">
                                <div x-data="{ filename: '' }" class="w-full">
                                    <input type="file" id="flash-file-input-{{ $key }}" class="hidden"
                                           wire:model="documentosParaCargar.{{ $key }}.archivo_input"
                                           @change="filename = $event.target.files.length > 0 ? $event.target.files[0].name : ''">
                                    
                                    <label for="flash-file-input-{{ $key }}" class="cursor-pointer w-full flex justify-center items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <x-icons.upload class="w-4 h-4 mr-2"/> Seleccionar
                                    </label>
                                    
                                    <div x-show="filename" class="mt-1 text-xs text-gray-600 dark:text-gray-400 truncate text-center" x-text="filename" title="filename"></div>
                                    
                                    <div wire:loading wire:target="documentosParaCargar.{{ $key }}.archivo_input" class="text-xs text-blue-500 text-center mt-1">Cargando...</div>
                                </div>
                            </td>

                            <td class="table-cell-sm text-center">
                                <button type="button" wire:click="abrirModalInfoCarga('{{ $key }}')" class="text-blue-600 hover:text-blue-800 font-semibold">Ver +</button>
                            </td>
                        </tr>
                        @if($errors->has('documentosParaCargar.' . $key . '.archivo_input'))
                            <tr class="text-xs {{ $loop->iteration % 2 == 0 ? 'bg-violet-100 dark:bg-violet-900/30' : 'bg-white dark:bg-gray-800' }}">
                                <td colspan="10" class="p-1 px-4">
                                    @error('documentosParaCargar.' . $key . '.archivo_input')<span class="text-red-600 font-semibold flex items-center"><x-icons.x-circle-solid class="w-4 h-4 mr-1"/> {{ $message }}</span>@enderror
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-16 h-16 text-green-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <p class="text-green-700 text-lg font-medium">¡Todo en Orden!</p>
                                    <p class="text-gray-500 text-sm">No se encontraron documentos urgentes para los filtros aplicados.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($documentosUrgentes && $documentosUrgentes->hasPages())
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                {{ $documentosUrgentes->links('vendor.livewire.tailwind') }}
            </div>
        @endif
        <div class="bg-gradient-to-r from-gray-200 to-gray-100 dark:from-gray-700 dark:to-gray-800 px-6 py-4 border-t-2 border-gray-300 dark:border-gray-600 rounded-b-lg flex justify-end shadow-inner">
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="cargarDocumentos, documentosParaCargar.*.archivo_input">
                <span wire:loading.remove wire:target="cargarDocumentos" class="flex items-center">
                    <x-icons.upload class="w-5 h-5 mr-2"/>
                    Cargar Documentos Seleccionados
                </span>
                <span wire:loading wire:target="cargarDocumentos" class="flex items-center">
                    <x-icons.spinner class="w-5 h-5 mr-2"/>
                    Procesando...
                </span>
            </button>
        </div>
    </form>

    @if ($showModalInfoCarga)
        <div class="fixed z-40 inset-0 overflow-y-auto" aria-labelledby="modal-title-info-carga" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="cerrarModalInfoCarga"></div><span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
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