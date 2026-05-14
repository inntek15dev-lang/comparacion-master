@php use Carbon\Carbon; use Illuminate\Support\Str; @endphp
<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestión y Supervisión de Documentos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    @if (session()->has('message'))<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">{{ session('message') }}</span></div>@endif
                    @if (session()->has('warning'))<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">{{ session('warning') }}</span></div>@endif
                    @if (session()->has('error'))<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">{{ session('error') }}</span></div>@endif
                    
                    {{-- SECCIÓN DE FILTROS --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-4">
                        <div>
                             <label for="filtroMostrar" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mostrar</label>
                            <select wire:model.live="filtroMostrar" id="filtroMostrar" class="form-select w-full rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                                <option value="">-- Estado de Carga --</option>
                                <option value="cargados">Documentos Cargados</option>
                                <option value="no_cargados">Documentos No Cargados</option>
                            </select>
                        </div>
                        <input wire:model.live.debounce.500ms="filtroContratista" type="text" placeholder="Filtrar por Contratista..." class="form-input rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300 self-end">
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
                        @if($filtroMostrar === 'cargados')
                            <select wire:model.live="filtroEstado" class="form-select rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300 self-end">
                                <option value="">-- Estado de Validación --</option>
                                <option value="Pendiente Validación Mandante">Pendiente Validación Mandante</option>
                                <option value="Revisado">Revisado (Finalizado)</option>
                                <option value="Archivado">Archivado</option>
                                <option value="Archivado-Revalidado">Archivado (Por Revalidación)</option>
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
                    </div>
                    
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
                                <label for="validador" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asignar a Validador de mi Empresa:</label>
                                <select id="validador" wire:model.live="validadorSeleccionado" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-800 dark:text-gray-300">
                                    <option value="">-- Seleccione un validador --</option>
                                    @foreach ($validadores as $validador)<option value="{{ $validador->id }}">{{ $validador->name }}</option>@endforeach
                                </select>
                                @error('validadorSeleccionado') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                @error('documentosSeleccionados') <span class="block text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div class="flex items-center space-x-2">
                                <x-primary-button wire:click="asignarSeleccionados" wire:loading.attr="disabled" :disabled="!count($documentosSeleccionados) || !$validadorSeleccionado">
                                    <span wire:loading.remove wire:target="asignarSeleccionados"> Asignar ({{ count($documentosSeleccionados) }})</span>
                                    <span wire:loading wire:target="asignarSeleccionados">Asignando...</span>
                                </x-primary-button>
                                <x-secondary-button wire:click="desasignarSeleccionados" wire:loading.attr="disabled" :disabled="!count($documentosSeleccionados)">
                                    <span wire:loading.remove wire:target="desasignarSeleccionados"> Desasignar ({{ count($documentosSeleccionados) }})</span>
                                    <span wire:loading wire:target="desasignarSeleccionados">Desasignando...</span>
                                </x-secondary-button>
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
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Contratista</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Documento</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Entidad Asociada</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID Entidad</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado Validación</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Resultado Validación</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones Flash</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" wire:click="sortBy('fecha_validacion')" style="cursor: pointer;">Fecha Validación @if($sortField === 'fecha_validacion')<span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>@endif</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha Vencimiento</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado Vigencia</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha Carga</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Horas en Cola</th>
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
                                    
                                    <tr wire:key="doc-{{ $documento->id }}" class="{{ $documento->estado_validacion == 'No Cargado' ? 'bg-red-50 dark:bg-red-900/20' : (in_array($documento->estado_validacion, ['Archivado', 'Archivado-Revalidado']) ? 'opacity-50 bg-gray-100 dark:bg-gray-900/30' : '') }}">
                                        <td class="p-4"><input type="checkbox" wire:model.live="documentosSeleccionados" value="{{ $documento->id }}" title="Marcar para Asignar/Desasignar" @if($filtroMostrar !== 'cargados' || $documento->estado_validacion !== 'Pendiente Validación Mandante') disabled @endif></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documentos->firstItem() + $key }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $filtroMostrar === 'cargados' ? $documento->id : 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documento->contratista->razon_social ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documento->nombre_documento_snapshot }}</td>
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
                                                'bg-green-100 text-green-800' => $isRevisadoActivo || $documento->estado_validacion === 'Revisado-Revalidado' || $documento->estado_validacion === 'Revisado',
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
                                            <div class="flex items-center justify-center space-x-2">
                                                @if($filtroMostrar === 'cargados')
                                                    @if($documento->estado_validacion === 'Pendiente Validación Mandante')
                                                        <a href="{{ route('document.revisar', ['documentoId' => $documento->id]) }}" 
                                                           target="_blank"
                                                           class="text-green-600 hover:text-green-900 font-semibold" 
                                                           title="Validar este documento en una nueva pestaña">
                                                            Validar
                                                        </a>
                                                    @elseif(in_array($documento->estado_validacion, ['Revisado', 'Revisado-Revalidado', 'Archivado', 'Archivado-Revalidado']))
                                                        <a href="{{ route('document.revisar', ['documentoId' => $documento->id]) }}" 
                                                           target="_blank"
                                                           class="text-gray-600 hover:text-gray-900 font-semibold" 
                                                           title="Ver detalle del documento en una nueva pestaña">
                                                            Ver
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
                                            @php
                                                $vigenciaClass = '';
                                                if (str_contains($documento->estado_vigencia, 'Vigente')) $vigenciaClass = 'bg-green-100 text-green-800';
                                                elseif (str_contains($documento->estado_vigencia, 'Vencido')) $vigenciaClass = 'bg-red-100 text-red-800';
                                                else $vigenciaClass = 'bg-gray-200 text-gray-800';
                                            @endphp
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $vigenciaClass }}">
                                                {{ $documento->estado_vigencia ?? '---' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documento->created_at ? $documento->created_at->format('d-m-Y H:i') : '---' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($filtroMostrar === 'cargados' && $documento->created_at)
                                                @php $horas = $documento->fecha_validacion ? (int) abs(Carbon::parse($documento->fecha_validacion)->diffInHours($documento->created_at)) : (int) abs(now()->diffInHours($documento->created_at)); @endphp
                                                {{ $horas }} horas
                                            @else --- @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="16" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
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
</div>