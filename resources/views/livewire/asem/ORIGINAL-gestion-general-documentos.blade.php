@php use Carbon\Carbon; use Illuminate\Support\Str; @endphp
<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestión General de Documentos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    @if (session()->has('message'))<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">{{ session('message') }}</span></div>@endif
                    @if (session()->has('warning'))<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">{{ session('warning') }}</span></div>@endif
                    @if (session()->has('error'))<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">{{ session('error') }}</span></div>@endif
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-4">
                        <div>
                             <label for="filtroMostrar" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mostrar</label>
                            <select wire:model.live="filtroMostrar" id="filtroMostrar" class="form-select w-full rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                                <option value="">-- Estado de Carga --</option>
                                <option value="cargados">Documentos Cargados</option>
                                <option value="no_cargados">Documentos No Cargados</option>
                            </select>
                        </div>
                        <input wire:model.live.debounce.500ms="filtroContratista" type="text" placeholder="Filtrar por Contratista..." class="form-input rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300 self-end">
                        <select wire:model.live="filtroMandante" class="form-select rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300 self-end">
                            <option value="">-- Todos los Mandantes --</option>
                            @foreach($mandantes as $mandante)
                                <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="filtroEntidad" class="form-select rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300 self-end">
                            <option value="">-- Todas las Entidades --</option>
                            <option value="App\Models\Contratista">Empresa</option>
                            <option value="App\Models\Trabajador">Trabajador</option>
                            <option value="App\Models\Vehiculo">Vehículo</option>
                            <option value="App\Models\Maquinaria">Maquinaria</option>
                            <option value="App\Models\Embarcacion">Embarcación</option>
                        </select>
                        <input wire:model.live.debounce.500ms="filtroDocumento" type="text" placeholder="Filtrar por Nombre documento" class="form-input rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300 self-end">
                        <input wire:model.live.debounce.500ms="filtroIdEntidad" type="text" placeholder="Filtrar por ID Entidad..." class="form-input rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300 self-end">
                        
                        <select wire:model.live="filtroTipoContratista" class="form-select rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300 self-end">
                            <option value="todos">-- Todos los Tipos --</option>
                            <option value="contratistas">Solo Contratistas</option>
                            <option value="subcontratistas">Solo Sub-Contratistas</option>
                        </select>

                        @if($filtroMostrar === 'cargados')
                            <select wire:model.live="filtroEstado" class="form-select rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300 self-end">
                                <option value="">-- Estado de Validación --</option>
                                @foreach($listaDeEstados as $valor => $etiqueta)
                                <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                            <select wire:model.live="filtroResultado" class="form-select rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300 self-end">
                                <option value="">-- Resultado Validación --</option>
                                <option value="Aprobado">Aprobado</option>
                                <option value="Rechazado">Rechazado</option>
                            </select>
                            <select wire:model.live="filtroVigencia" class="form-select rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300 self-end">
                                <option value="">-- Estado de Vigencia --</option>
                                <option value="Vigente">Vigente</option>
                                <option value="Vigente-Modificado">Vigente (Modificado)</option>
                                <option value="Vencido">Vencido</option>
                                <option value="Vencido-Modificado">Vencido (Modificado)</option>
                                <option value="Por Periodo">Por Periodo</option>
                            </select>
                            <select wire:model.live="filtroValidador" class="form-select rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300 self-end">
                                <option value="">-- Todos los Validadores --</option>
                                @foreach ($validadores as $validador)
                                    <option value="{{ $validador->id }}">{{ $validador->name }}</option>
                                @endforeach
                            </select>
                            
                            <!-- INICIO DE LA MODIFICACIÓN: FILTRO FECHA DE CARGA -->
                            <div class="flex items-end space-x-2 lg:col-span-2">
                                <div class="flex-1">
                                    <label for="filtroFechaCargaDesde" class="block text-sm font-medium text-gray-700 dark:text-gray-300">F. Carga Desde</label>
                                    <input wire:model.live.debounce.500ms="filtroFechaCargaDesde" id="filtroFechaCargaDesde" type="date" class="form-input w-full rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                                </div>
                                <div class="flex-1">
                                     <label for="filtroFechaCargaHasta" class="block text-sm font-medium text-gray-700 dark:text-gray-300">F. Carga Hasta</label>
                                    <input wire:model.live.debounce.500ms="filtroFechaCargaHasta" id="filtroFechaCargaHasta" type="date" class="form-input w-full rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                                </div>
                            </div>
                            <!-- FIN DE LA MODIFICACIÓN: FILTRO FECHA DE CARGA -->

                            <div class="flex items-end space-x-2 lg:col-span-2">
                                <div class="flex-1">
                                    <label for="filtroFechaDesde" class="block text-sm font-medium text-gray-700 dark:text-gray-300">F. Val. Desde</label>
                                    <input wire:model.live.debounce.500ms="filtroFechaDesde" id="filtroFechaDesde" type="date" class="form-input w-full rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                                </div>
                                <div class="flex-1">
                                     <label for="filtroFechaHasta" class="block text-sm font-medium text-gray-700 dark:text-gray-300">F. Val. Hasta</label>
                                    <input wire:model.live.debounce.500ms="filtroFechaHasta" id="filtroFechaHasta" type="date" class="form-input w-full rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                                </div>
                            </div>
                        @endif
                        @if($filtroMostrar === 'cargados')
                        <div class="flex items-end">
                            <label class="flex items-center bg-yellow-100 dark:bg-yellow-900/50 p-2 rounded-md border border-yellow-300 dark:border-yellow-700 w-full h-full cursor-pointer">
                                <input type="checkbox" wire:model.live="filtroErrorValidador" class="form-checkbox h-5 w-5 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                <span class="ml-3 block text-sm font-bold text-yellow-800 dark:text-yellow-200">SOLO *</span>
                            </label>
                        </div>
                        @endif
                    </div>
                    
                    @if($filtroMostrar === 'cargados' && empty($filtroEstado))
                        <div class="mb-4 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Excluir Estados de la Vista:</h4>
                                <div class="flex space-x-2">
                                    <button wire:click="marcarTodosParaExcluir" class="text-xs px-2 py-1 bg-gray-200 dark:bg-gray-600 rounded hover:bg-gray-300 dark:hover:bg-gray-500">Marcar Todos</button>
                                    <button wire:click="desmarcarTodosParaExcluir" class="text-xs px-2 py-1 bg-gray-200 dark:bg-gray-600 rounded hover:bg-gray-300 dark:hover:bg-gray-500">Desmarcar Todos</button>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-x-4 gap-y-2">
                                @foreach($listaDeEstados as $valor => $etiqueta)
                                    <label class="flex items-center text-sm">
                                        <input type="checkbox" wire:model.live="estadosAExcluir" value="{{ $valor }}" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-gray-700 dark:text-gray-300">{{ $etiqueta }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end items-center space-x-4 mb-6">
                        @if($filtroMostrar === 'cargados' && ($filtroFechaCargaDesde || $filtroFechaCargaHasta))
                        <x-secondary-button wire:click="borrarFechasCarga" wire:loading.attr="disabled">
                            Borrar F. Carga
                        </x-secondary-button>
                        @endif
                        @if($filtroMostrar === 'cargados' && ($filtroFechaDesde || $filtroFechaHasta))
                        <x-secondary-button wire:click="borrarFechasValidacion" wire:loading.attr="disabled">
                            Borrar F. Val.
                        </x-secondary-button>
                        @endif
                        <x-danger-button wire:click="resetearFiltros" wire:loading.attr="disabled">
                            Resetear Filtros
                        </x-danger-button>
                    </div>

                    @if($filtroMostrar === 'cargados')
                    <div class="space-y-4 mb-4">
                        <div class="flex items-center justify-between bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                            <div class="flex-grow mr-4">
                                <label for="validador" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asignar a Validador:</label>
                                <select id="validador" wire:model.live="validadorSeleccionado" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-800 dark:text-gray-300">
                                    <option value="">-- Seleccione un validador --</option>
                                    @foreach ($validadores as $validador)<option value="{{ $validador->id }}">{{ $validador->name }}</option>@endforeach
                                </select>
                                @error('validadorSeleccionado') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                @error('documentosSeleccionados') <span class="block text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div class="flex items-center space-x-2">
                                <x-primary-button wire:click="asignarSeleccionados" wire:loading.attr="disabled" :disabled="!count($documentosSeleccionados) || !$validadorSeleccionado">
                                    <span wire:loading.remove wire:target="asignarSeleccionados"> Asignar ({{ count($documentosSeleccionados) }}) <span class="ml-2 font-bold">[ VN: {{ $totalValorNominal }} ]</span></span>
                                    <span wire:loading wire:target="asignarSeleccionados">Asignando...</span>
                                </x-primary-button>
                                <x-secondary-button wire:click="desasignarSeleccionados" wire:loading.attr="disabled" :disabled="!count($documentosSeleccionados)">
                                    <span wire:loading.remove wire:target="desasignarSeleccionados"> Desasignar ({{ count($documentosSeleccionados) }})</span>
                                    <span wire:loading wire:target="desasignarSeleccionados">Desasignando...</span>
                                </x-secondary-button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between bg-purple-50 dark:bg-purple-900/30 p-4 rounded-lg">
                            <div class="flex-grow mr-4">
                                <label for="motivoRevalidacionMasiva" class="block text-sm font-medium text-purple-700 dark:text-purple-300">Motivo para Revalidación Masiva:</label>
                                <input type="text" id="motivoRevalidacionMasiva" wire:model.live.debounce.300ms="motivoRevalidacionMasiva" placeholder="Ingrese un motivo único para todos los documentos marcados..." class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-purple-300 dark:border-purple-600 focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm rounded-md dark:bg-gray-800 dark:text-gray-300">
                                @error('motivoRevalidacionMasiva') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                @error('seleccionParaRevalidar') <span class="block text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div class="flex items-center">
                                <x-danger-button wire:click="revalidarSeleccionados" wire:loading.attr="disabled" :disabled="!count($seleccionParaRevalidar) || !$motivoRevalidacionMasiva">
                                    <span wire:loading.remove wire:target="revalidarSeleccionados">Revalidar Seleccionados ({{ count($seleccionParaRevalidar) }})</span>
                                    <span wire:loading wire:target="revalidarSeleccionados">Procesando...</span>
                                </x-danger-button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between bg-orange-50 dark:bg-orange-900/30 p-4 rounded-lg">
                            <div class="flex-grow mr-4">
                               <label class="block text-sm font-medium text-orange-700 dark:text-orange-300">Modificación de Vencimientos:</label>
                               <p class="text-xs text-orange-600 dark:text-orange-400">Seleccione documentos en la tabla para habilitar la modificación.</p>
                            </div>
                            <div class="flex items-center">
                                <x-primary-button wire:click="abrirModalModificarVencimiento" wire:loading.attr="disabled" :disabled="!count($seleccionParaModificar)">
                                    <span wire:loading.remove wire:target="abrirModalModificarVencimiento">Modificar Vencimientos ({{ count($seleccionParaModificar) }})</span>
                                    <span wire:loading wire:target="abrirModalModificarVencimiento">Abriendo...</span>
                                </x-primary-button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between bg-cyan-50 dark:bg-cyan-900/30 p-4 rounded-lg">
                            <div class="flex-grow mr-4">
                               <label class="block text-sm font-medium text-cyan-700 dark:text-cyan-300">Notificaciones Masivas por Email:</label>
                               <p class="text-xs text-cyan-600 dark:text-cyan-400">Enviará una notificación a los contratistas de todos los documentos visibles en la tabla (según filtros aplicados).</p>
                            </div>
                            <div class="flex items-center">
                                <x-primary-button wire:click="abrirModalNotificacion" class="!bg-cyan-600 hover:!bg-cyan-700" wire:loading.attr="disabled" :disabled="!isset($documentos) || $documentos->total() === 0">
                                    Notificar a Contratistas
                                </x-primary-button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between bg-blue-50 dark:bg-blue-900/30 p-4 rounded-lg">
                            <div class="flex-grow mr-4">
                               <label class="block text-sm font-medium text-blue-700 dark:text-blue-300">Informes de Producción:</label>
                               <p class="text-xs text-blue-600 dark:text-blue-400">Genera un informe consolidado de la producción de los validadores según los filtros actuales.</p>
                            </div>
                            <div class="flex items-center">
                                <x-primary-button wire:click="abrirModalInformeProduccion" class="!bg-blue-600 hover:!bg-blue-700" wire:loading.attr="disabled">
                                    Generar Informe de Producción
                                </x-primary-button>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="p-4"><input type="checkbox" wire:model.live="seleccionarTodos" title="Seleccionar todos para Asignar/Desasignar" @if($filtroMostrar !== 'cargados') disabled @endif></th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nº</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"># ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Mandante</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Contratista</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Documento</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor Nominal</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Entidad Asociada</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID Entidad</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado Validación</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Resultado Validación</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones Flash</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" wire:click="sortBy('fecha_validacion')" style="cursor: pointer;">Fecha Validación @if($sortField === 'fecha_validacion')<span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>@endif</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha Vencimiento</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado Vigencia</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Validador</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha Carga</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Horas en Cola</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Revalidar</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Mod. Venc.</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                                @forelse ($documentos as $key => $documento)
                                    @php
                                        $isRevisadoActivo = $filtroMostrar === 'cargados' && $documento->resultado_validacion && !in_array($documento->estado_validacion, ['Archivado', 'Archivado-Revalidado']);
                                        $displayState = $documento->estado_validacion;
                                        if ($isRevisadoActivo && !in_array($displayState, ['Revisado-Revalidado'])) {
                                            $displayState = 'Revisado';
                                        }
                                    @endphp
                                    
                                    <tr wire:key="doc-{{ $documento->id }}" class="{{ $documento->estado_validacion == 'Devuelto' ? 'bg-yellow-50 dark:bg-yellow-900/20' : ($documento->estado_validacion == 'No Cargado' ? 'bg-red-50 dark:bg-red-900/20' : (in_array($documento->estado_validacion, ['Archivado', 'Archivado-Revalidado']) ? 'opacity-50 bg-gray-100 dark:bg-gray-900/30' : '')) }}">
                                        <td class="p-4"><input type="checkbox" wire:model.live="documentosSeleccionados" value="{{ $documento->id }}" title="Marcar para Asignar/Desasignar" @if($filtroMostrar !== 'cargados' || $documento->resultado_validacion) disabled @endif></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documentos->firstItem() + $key }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $filtroMostrar === 'cargados' ? $documento->id : 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documento->mandante->razon_social ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($documento->contratista)
                                                @php $padre = $documento->contratista->contratistaPadreAprobado->first(); @endphp
                                                @if($padre)
                                                    <div class="text-xs">
                                                        <span class="font-semibold text-gray-500 dark:text-gray-400">Sub-Contratista de:</span>
                                                        <span class="block">{{ $padre->razon_social }}</span>
                                                    </div>
                                                @else
                                                    {{ $documento->contratista->razon_social }}
                                                @endif
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            {{ $documento->nombre_documento_snapshot }}
                                            @if($filtroMostrar === 'cargados' && $documento->estado_validacion === 'Devuelto' && $documento->observacion_interna_asem)
                                                <div class="text-xs text-yellow-600 dark:text-yellow-400 mt-1 p-1 bg-yellow-100 dark:bg-yellow-900/50 rounded" title="{{ $documento->observacion_interna_asem }}">
                                                    <strong>Motivo Dev:</strong> {{ Str::limit($documento->observacion_interna_asem, 50) }}
                                                </div>
                                            @endif
                                            @if($filtroMostrar === 'cargados' && !empty($documento->motivo_revalidacion))
                                                <div class="text-xs text-purple-600 dark:text-purple-400 mt-1 p-1 bg-purple-100 dark:bg-purple-900/50 rounded" title="{{ $documento->motivo_revalidacion }}">
                                                    <strong>Motivo Rev:</strong> {{ Str::limit($documento->motivo_revalidacion, 50) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-center">{{ $documento->valor_nominal_snapshot ?? 0 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ class_basename($documento->entidad_type) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($documento->entidad)
                                                @if($documento->entidad instanceof \App\Models\Vehiculo) {{ $documento->entidad->patente_letras }} {{ $documento->entidad->patente_numeros }}
                                                @elseif($documento->entidad instanceof \App\Models\Trabajador)
                                                    <div class="font-bold">{{ $documento->entidad->rut }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $documento->entidad->nombres }} {{ $documento->entidad->apellido_paterno }} {{ $documento->entidad->apellido_materno }}</div>
                                                @elseif($documento->entidad instanceof \App\Models\Maquinaria) {{ $documento->entidad->identificador_letras }} {{ $documento->entidad->identificador_numeros }}
                                                @elseif($documento->entidad instanceof \App\Models\Embarcacion) {{ $documento->entidad->matricula_letras }} {{ $documento->entidad->matricula_numeros }}
                                                @elseif($documento->entidad instanceof \App\Models\Contratista) {{ $documento->entidad->rut }}
                                                @else N/A @endif
                                            @else N/A @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span @class(['px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                                'bg-gray-200 text-gray-800' => in_array($documento->estado_validacion, ['Sin Asignar']),
                                                'bg-blue-100 text-blue-800' => str_contains($documento->estado_validacion, 'Asignado-'),
                                                'bg-yellow-100 text-yellow-800' => $documento->estado_validacion == 'Devuelto',
                                                'bg-green-100 text-green-800' => $isRevisadoActivo || $documento->estado_validacion === 'Revisado-Revalidado' || $documento->estado_validacion === 'Revisado',
                                                'bg-purple-100 text-purple-800' => str_contains($documento->estado_validacion, 'Revalidar') && !str_contains($documento->estado_validacion, 'Asignado-'),
                                                'bg-indigo-100 text-indigo-800' => $documento->estado_validacion == 'Pendiente Validación Mandante',
                                                'bg-gray-500 text-white' => in_array($documento->estado_validacion, ['Archivado', 'Archivado-Revalidado']),
                                                'bg-red-200 text-red-800 font-extrabold' => $documento->estado_validacion == 'No Cargado',
                                            ])>
                                                {{ $displayState }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $documento->resultado_validacion == 'Aprobado' ? 'bg-green-100 text-green-800' : '' }} {{ $documento->resultado_validacion == 'Rechazado' ? 'bg-red-100 text-red-800' : '' }}">
                                                {{ $documento->resultado_validacion ?? '---' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            @php
                                                $esRevisado = ($documento->resultado_validacion && !in_array($documento->estado_validacion, ['Archivado', 'Archivado-Revalidado']));
                                                $esArchivado = in_array($documento->estado_validacion, ['Archivado', 'Archivado-Revalidado']);
                                                $esActivo = !$documento->resultado_validacion && !$esArchivado;
                                            @endphp
                                
                                            <div class="flex items-center justify-center space-x-2">
                                                @if($filtroMostrar === 'cargados')
                                                    @if($esRevisado)
                                                        <button wire:click="abrirModalAuditoria({{ $documento->id }}, false)" class="text-blue-600 hover:text-blue-900 font-semibold" title="Auditar y/o Revalidar este documento">
                                                            Auditar
                                                        </button>
                                                    @elseif($esArchivado)
                                                        <button wire:click="abrirModalAuditoria({{ $documento->id }}, true)" class="text-gray-600 hover:text-gray-900 font-semibold" title="Ver detalle de documento archivado">
                                                            Ver
                                                        </button>
                                                    @elseif($esActivo)
                                                        <a href="{{ route('document.revisar', ['documentoId' => $documento->id]) }}" 
                                                           target="_blank"
                                                           class="text-green-600 hover:text-green-900 font-semibold" 
                                                           title="Tomar control y validar este documento en una nueva pestaña">
                                                            Validar
                                                        </a>
                                                    @else
                                                        ---
                                                    @endif
                                                @else
                                                    ---
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documento->fecha_validacion ? Carbon::parse($documento->fecha_validacion)->format('d-m-Y H:i') : '---' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documento->fecha_vencimiento ? Carbon::parse($documento->fecha_vencimiento)->format('d-m-Y') : 'Por Periodo' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div>
                                                <div class="flex items-center">
                                                    @php
                                                        $vigenciaClass = '';
                                                        if (str_contains($documento->estado_vigencia, 'Vigente')) $vigenciaClass = 'bg-green-100 text-green-800';
                                                        elseif (str_contains($documento->estado_vigencia, 'Vencido')) $vigenciaClass = 'bg-red-100 text-red-800';
                                                        else $vigenciaClass = 'bg-gray-200 text-gray-800';
                                                        
                                                        $finalClass = str_contains($documento->estado_vigencia, '-Modificado') 
                                                                    ? $vigenciaClass . ' ring-1 ring-orange-400 dark:ring-orange-500'
                                                                    : $vigenciaClass;
                                                    @endphp
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $finalClass }}">
                                                        {{ $documento->estado_vigencia ?? '---' }}
                                                    </span>

                                                    @if($documento->ruta_justificativo_modificacion)
                                                    <a href="{{ $documento->justificativo_url }}" target="_blank" class="ml-2 text-xs text-blue-500 hover:underline">
                                                        Ver
                                                    </a>
                                                    @endif
                                                </div>
                                                @if($documento->es_vencimiento_modificado && $documento->motivo_modificacion_vencimiento)
                                                    <div class="text-xs text-orange-600 dark:text-orange-400 mt-1 italic" title="{{ $documento->motivo_modificacion_vencimiento }}">
                                                        {{ Str::limit($documento->motivo_modificacion_vencimiento, 35) }}
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($filtroMostrar === 'cargados')
                                                @if($documento->validadorAsem && $documento->validadorMandante)
                                                    <div class="text-xs">
                                                        <span class="font-semibold text-gray-600 dark:text-gray-400">ASEM:</span>
                                                        @if($documento->es_error_validador) <span class="text-red-500 font-bold">*</span> @endif
                                                        {{ $documento->validadorAsem->name }}
                                                    </div>
                                                    <div class="text-xs mt-1">
                                                        <span class="font-semibold text-gray-600 dark:text-gray-400">Mandante:</span>
                                                        @if($documento->es_error_validador) <span class="text-red-500 font-bold">*</span> @endif
                                                        {{ $documento->validadorMandante->name }}
                                                    </div>
                                                @elseif($documento->validadorAsem)
                                                    <div class="text-xs">
                                                        <span class="font-semibold text-gray-600 dark:text-gray-400">ASEM:</span>
                                                        @if($documento->es_error_validador) <span class="text-red-500 font-bold">*</span> @endif
                                                        {{ $documento->validadorAsem->name }}
                                                    </div>
                                                @elseif($documento->validadorMandante)
                                                    <div class="text-xs">
                                                        <span class="font-semibold text-gray-600 dark:text-gray-400">Mandante:</span>
                                                        @if($documento->es_error_validador) <span class="text-red-500 font-bold">*</span> @endif
                                                        {{ $documento->validadorMandante->name }}
                                                    </div>
                                                @endif
                                            @else
                                                ---
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documento->created_at ? $documento->created_at->format('d-m-Y H:i') : '---' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            {{ $documento->horas_en_cola_formateado ?? '---' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            <input type="checkbox" wire:model.live="seleccionParaRevalidar" value="{{ $documento->id }}" title="Marcar para Revalidar" class="form-checkbox h-5 w-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500" @if(!$isRevisadoActivo) disabled @endif>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            <input type="checkbox" wire:model.live="seleccionParaModificar" value="{{ $documento->id }}" title="Marcar para Modificar Vencimiento" class="form-checkbox h-5 w-5 text-orange-600 border-gray-300 rounded focus:ring-orange-500" @if(!$isRevisadoActivo || !$documento->fecha_vencimiento) disabled @endif>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="21" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    @if($filtroMostrar === '') Seleccione un estado de carga para comenzar a buscar. @else No hay documentos que coincidan con los filtros aplicados. @endif
                                    </td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">@if($documentos) {{ $documentos->links() }} @endif</div>
                </div>
            </div>
        </div>
    </div>
    
    @if ($showModificarVencimientoModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60" wire:keydown.escape.window="cerrarModalModificarVencimiento">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-xl w-full max-w-lg" @click.away="cerrarModalModificarVencimiento">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 border-b pb-3">Modificar Vencimiento de Documentos ({{ count($seleccionParaModificar) }})</h3>
                <div class="space-y-4" x-data="{ tipo: @entangle('tipoModificacion').live }">
                    <fieldset>
                        <legend class="text-sm font-medium text-gray-900 dark:text-gray-100">Tipo de Modificación</legend>
                        <div class="mt-2 space-y-2">
                            <label class="flex items-center">
                                <input type="radio" wire:model.live="tipoModificacion" value="fecha_fija" name="tipo_mod" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                <span class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">Asignar Fecha Fija</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" wire:model.live="tipoModificacion" value="sumar_dias" name="tipo_mod" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                <span class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">Sumar / Restar Días</span>
                            </label>
                        </div>
                    </fieldset>
                    <div x-show="tipo === 'fecha_fija'">
                        <label for="fechaFija" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nueva Fecha de Vencimiento</label>
                        <input type="date" id="fechaFija" wire:model="fechaFija" class="mt-1 block w-full rounded-md shadow-sm border-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('fechaFija') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div x-show="tipo === 'sumar_dias'">
                        <label for="diasASumar" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Días a Sumar o Restar</label>
                        <input type="number" id="diasASumar" wire:model="diasASumar" placeholder="Ej: 365 para sumar un año, -30 para restar un mes" class="mt-1 block w-full rounded-md shadow-sm border-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 focus:ring-indigo-500 focus:border-indigo-500">
                         @error('diasASumar') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="motivoModificacion" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo de la Modificación (Obligatorio)</label>
                        <textarea id="motivoModificacion" wire:model="motivoModificacion" rows="3" class="mt-1 block w-full rounded-md shadow-sm border-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        @error('motivoModificacion') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="justificativo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Archivo Justificativo (Opcional)</label>
                        <input type="file" id="justificativo" wire:model="justificativoModificacion" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <div wire:loading wire:target="justificativoModificacion" class="text-sm text-gray-500 mt-1">Cargando archivo...</div>
                        @error('justificativoModificacion') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-4">
                    <x-secondary-button wire:click="cerrarModalModificarVencimiento">
                        Cancelar
                    </x-secondary-button>
                    <x-primary-button wire:click="confirmarModificacionVencimiento" wire:loading.attr="disabled" wire:target="confirmarModificacionVencimiento, justificativoModificacion">
                        <span wire:loading.remove wire:target="confirmarModificacionVencimiento">Confirmar Modificación</span>
                        <span wire:loading wire:target="confirmarModificacionVencimiento">Procesando...</span>
                    </x-primary-button>
                </div>
            </div>
        </div>
    @endif

    @if ($showAuditoriaModal && $documentoAuditoria)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60" wire:keydown.escape.window="cerrarModalAuditoria">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-xl w-full max-w-7xl mx-4" @click.away="cerrarModalAuditoria">
            <div class="flex justify-between items-start border-b border-gray-200 dark:border-gray-700 pb-3 mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                    @if($esAuditoriaSoloLectura)
                        Vista de Documento Archivado (ID: {{ $documentoAuditoria->id }})
                    @else
                        Auditoría de Documento (ID: {{ $documentoAuditoria->id }})
                    @endif
                </h3>
                <button wire:click="cerrarModalAuditoria" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">×</button>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-h-[75vh] overflow-y-auto pr-2">
                
                <div class="flex flex-col">
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">Documento Cargado</h4>
                    <div class="border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden flex-grow">
                        <iframe src="{{ $documentoAuditoria->url }}" class="w-full h-[65vh]" frameborder="0"></iframe>
                    </div>
                </div>

                <div class="flex flex-col space-y-4 text-sm">
                    
                    <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-600 space-y-2">
                        <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 border-b pb-2 mb-2">Principal</h4>
                        <p><strong class="text-gray-600 dark:text-gray-400 w-28 inline-block">Mandante:</strong> {{ $documentoAuditoria->mandante->razon_social ?? 'N/A' }}</p>
                        <p><strong class="text-gray-600 dark:text-gray-400 w-28 inline-block">Contratista:</strong> {{ $documentoAuditoria->contratista->razon_social ?? 'N/A' }}</p>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-600 space-y-2">
                        <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 border-b pb-2 mb-2">Entidad</h4>
                        <p><strong class="text-gray-600 dark:text-gray-400 w-28 inline-block">Tipo:</strong> {{ class_basename($documentoAuditoria->entidad_type) }}</p>
                        @if($documentoAuditoria->entidad)
                            @if($documentoAuditoria->entidad instanceof \App\Models\Trabajador)
                                <p><strong class="text-gray-600 dark:text-gray-400 w-28 inline-block">Nombre:</strong> {{ $documentoAuditoria->entidad->nombres }} {{ $documentoAuditoria->entidad->apellido_paterno }}</p>
                                <p><strong class="text-gray-600 dark:text-gray-400 w-28 inline-block">RUT:</strong> {{ $documentoAuditoria->entidad->rut }}</p>
                            @else
                                <p><strong class="text-gray-600 dark:text-gray-400 w-28 inline-block">ID:</strong> {{ $documentoAuditoria->entidad->identificador_completo ?? $documentoAuditoria->entidad->rut ?? 'N/A' }}</p>
                            @endif
                        @endif
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-600 space-y-2">
                        <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 border-b pb-2 mb-2">Documento</h4>
                        <p><strong class="text-gray-600 dark:text-gray-400 w-28 inline-block">Nombre:</strong> {{ $documentoAuditoria->reglaDocumental->nombre ?? $documentoAuditoria->nombre_documento_snapshot }}</p>
                        <p><strong class="text-gray-600 dark:text-gray-400 w-28 inline-block">Fecha de Carga:</strong> {{ $documentoAuditoria->created_at->format('d-m-Y H:i') }}</p>
                        @if($documentoAuditoria->fecha_emision)
                        <p><strong class="text-gray-600 dark:text-gray-400 w-28 inline-block">Fecha Emisión:</strong> {{ $documentoAuditoria->fecha_emision->format('d-m-Y') }}</p>
                        @endif
                        @if($documentoAuditoria->fecha_vencimiento)
                        <p><strong class="text-gray-600 dark:text-gray-400 w-28 inline-block">Fecha Vencim.:</strong> {{ $documentoAuditoria->fecha_vencimiento->format('d-m-Y') }}</p>
                        @endif
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-600 space-y-2">
                        <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 border-b pb-2 mb-2">Detalles de la Revisión Original</h4>
                        <p><strong>Resultado:</strong> 
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $documentoAuditoria->resultado_validacion == 'Aprobado' ? 'bg-green-100 text-green-800 dark:bg-green-800/50 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-800/50 dark:text-red-200' }}">
                                {{ $documentoAuditoria->resultado_validacion }}
                            </span>
                        </p>
                        <p><strong>Validador:</strong> 
                            @if($documentoAuditoria->validadorAsem)
                                {{ $documentoAuditoria->validadorAsem->name }} (ASEM)
                            @elseif($documentoAuditoria->validadorMandante)
                                {{ $documentoAuditoria->validadorMandante->name }} (Mandante)
                            @else
                                Sistema
                            @endif
                        </p>
                        <p><strong>Fecha de Validación:</strong> {{ $documentoAuditoria->fecha_validacion ? $documentoAuditoria->fecha_validacion->format('d-m-Y H:i:s') : 'N/A' }}</p>
                        <div>
                            <p><strong>Observaciones de la Revisión:</strong></p>
                            <div class="mt-1 p-2 border rounded bg-white dark:bg-gray-800 max-h-24 overflow-y-auto text-xs">
                                {{ $documentoAuditoria->observacion_rechazo ?: 'Sin observaciones.' }}
                            </div>
                        </div>
                    </div>
                    
                    @if(!empty($documentoAuditoria->criterios_snapshot))
                        <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-600 space-y-2">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 border-b pb-2 mb-2">CRITERIOS REVISADOS</h4>
                            <div class="space-y-1 max-h-32 overflow-y-auto pr-2">
                                @foreach($documentoAuditoria->criterios_snapshot as $criterioData)
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        <span class="font-bold text-gray-700 dark:text-gray-300">•</span> {{ $criterioData['criterio'] ?? 'Criterio no definido' }}
                                    </p>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    @if(!$esAuditoriaSoloLectura)
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-2">Acción: Iniciar Nueva Revalidación</h4>
                        <div class="mb-4">
                            <label class="flex items-center bg-yellow-100 dark:bg-yellow-900/50 p-3 rounded-md border border-yellow-300 dark:border-yellow-700">
                                <input type="checkbox" wire:model="marcarComoErrorValidador" class="form-checkbox h-5 w-5 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                <span class="ml-3 block text-sm font-medium text-yellow-800 dark:text-yellow-200">Marcar como Error del Validador</span>
                            </label>
                        </div>
                        <div>
                            <label for="motivoRevalidacionIndividual" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo para la Nueva Revalidación (Obligatorio)</label>
                            <textarea id="motivoRevalidacionIndividual" wire:model.live="motivoRevalidacionIndividual" rows="3" placeholder="Ej: Se detectó un error en la revisión original, el documento ha perdido validez por un evento externo..." class="mt-1 block w-full rounded-md shadow-sm border-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 focus:ring-red-500 focus:border-red-500"></textarea>
                            @error('motivoRevalidacionIndividual') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                <x-secondary-button wire:click="cerrarModalAuditoria">
                    @if($esAuditoriaSoloLectura)
                        Cerrar
                    @else
                        Cancelar
                    @endif
                </x-secondary-button>
                @if(!$esAuditoriaSoloLectura)
                <x-danger-button wire:click="iniciarRevalidacionIndividual" wire:loading.attr="disabled" :disabled="!$motivoRevalidacionIndividual || strlen($motivoRevalidacionIndividual) < 10">
                    <span wire:loading.remove wire:target="iniciarRevalidacionIndividual">Iniciar Revalidación</span>
                    <span wire:loading wire:target="iniciarRevalidacionIndividual">Procesando...</span>
                </x-danger-button>
                @endif
            </div>
        </div>
    </div>
    @endif
    
    @if ($showNotificacionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60" wire:keydown.escape.window="cerrarModalNotificacion">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-xl w-full max-w-lg" @click.away="cerrarModalNotificacion">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 border-b pb-3">Confirmar Notificación Masiva</h3>
                <div class="space-y-4">
                    
                    <div>
                        <label for="mensajeNotificacion" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mensaje para el Contratista:</label>
                        <textarea id="mensajeNotificacion" wire:model.defer="mensajeNotificacion" rows="5" class="mt-1 block w-full rounded-md shadow-sm border-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 focus:ring-cyan-500 focus:border-cyan-500"></textarea>
                        @error('mensajeNotificacion') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-md text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total de Documentos a Notificar:</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $conteoNotificacion['total'] }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Total de Contratistas a ser notificados:</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $conteoNotificacion['contratistas'] }}</p>
                    </div>
                    <p class="text-xs text-yellow-600 dark:text-yellow-400">
                        <strong>Nota:</strong> Esta acción se procesará en segundo plano. Los correos pueden tardar unos minutos en enviarse. Se enviará un correo por cada contratista afectado, listando todos sus documentos con observaciones.
                    </p>
                </div>
                <div class="mt-6 flex justify-end space-x-4">
                    <x-secondary-button wire:click="cerrarModalNotificacion">
                        Cancelar
                    </x-secondary-button>
                    <x-primary-button wire:click="despacharNotificaciones" class="!bg-cyan-600 hover:!bg-cyan-700" wire:loading.attr="disabled" wire:target="despacharNotificaciones">
                        <span wire:loading.remove wire:target="despacharNotificaciones">Confirmar y Enviar</span>
                        <span wire:loading wire:target="despacharNotificaciones">Despachando...</span>
                    </x-primary-button>
                </div>
            </div>
        </div>
    @endif

    @if ($showInformeProduccionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60" wire:keydown.escape.window="cerrarModalInformeProduccion">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-xl w-full max-w-6xl" @click.away="cerrarModalInformeProduccion">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">Informe de Producción de Validadores</h3>
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-4 border-b pb-3">
                    <p>Resultados basados en los filtros actualmente aplicados en la vista principal.</p>
                    @if($filtroFechaDesde && $filtroFechaHasta)
                        <p><strong>Periodo de Validación:</strong> {{ \Carbon\Carbon::parse($filtroFechaDesde)->format('d-m-Y') }} al {{ \Carbon\Carbon::parse($filtroFechaHasta)->format('d-m-Y') }}</p>
                    @endif
                    @if($filtroDocumento)
                        <p><strong>Filtro de Documento:</strong> {{ $filtroDocumento }}</p>
                    @endif
                </div>
                
                <div class="max-h-[60vh] overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                            <tr>
                                <th class="p-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <input type="checkbox" wire:model.live="seleccionarTodosValidadores" title="Seleccionar Todos">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Validador</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rol</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Revisados</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aprobados</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rechazados</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Errores (*)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($datosInformeProduccion as $dato)
                                <tr>
                                    <td class="p-2">
                                        <input type="checkbox" wire:model.live="validadoresParaExportar" value="{{ $dato->validador_id }}">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $dato->validador_nombre }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-400">{{ $dato->rol }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-700 dark:text-gray-300">{{ $dato->total_revisados }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600">{{ $dato->aprobados }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-orange-600">{{ $dato->rechazados }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-extrabold text-red-600">{{ $dato->errores }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-sm text-gray-500 dark:text-gray-400">No se encontraron datos de producción para los filtros seleccionados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-100 dark:bg-gray-900">
                            <tr class="font-bold text-gray-800 dark:text-white">
                                <td class="px-6 py-3 text-left text-sm uppercase" colspan="3">Totales Seleccionados</td>
                                <td class="px-6 py-3 text-right text-sm">{{ collect($datosInformeProduccion)->whereIn('validador_id', $validadoresParaExportar)->sum('total_revisados') }}</td>
                                <td class="px-6 py-3 text-right text-sm">{{ collect($datosInformeProduccion)->whereIn('validador_id', $validadoresParaExportar)->sum('aprobados') }}</td>
                                <td class="px-6 py-3 text-right text-sm">{{ collect($datosInformeProduccion)->whereIn('validador_id', $validadoresParaExportar)->sum('rechazados') }}</td>
                                <td class="px-6 py-3 text-right text-sm">{{ collect($datosInformeProduccion)->whereIn('validador_id', $validadoresParaExportar)->sum('errores') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-6 border-t pt-4">
                    <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-2">Opciones de Exportación</h4>
                    <div class="flex items-center space-x-6">
                        <div class="flex items-center">
                            <input id="export_excel" type="checkbox" wire:model.live="formatosExportacion" value="excel" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                            <label for="export_excel" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">Excel</label>
                        </div>
                        <div class="flex items-center">
                            <input id="export_pdf" type="checkbox" wire:model.live="formatosExportacion" value="pdf" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                            <label for="export_pdf" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">PDF</label>
                        </div>
                        <div class="flex items-center">
                            <input id="export_html" type="checkbox" wire:model.live="formatosExportacion" value="html" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                            <label for="export_html" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">HTML Interactivo</label>
                        </div>
                    </div>
                    @error('formatosExportacion') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="mt-6 flex justify-end space-x-4">
                    <x-secondary-button wire:click="cerrarModalInformeProduccion">
                        Cerrar
                    </x-secondary-button>
                    <x-primary-button wire:click="exportarInformeProduccion" wire:loading.attr="disabled" :disabled="empty($formatosExportacion) || empty($validadoresParaExportar)">
                        <span wire:loading.remove wire:target="exportarInformeProduccion">Generar Reportes</span>
                        <span wire:loading wire:target="exportarInformeProduccion">Generando...</span>
                    </x-primary-button>
                </div>
            </div>
        </div>
    @endif
</div>