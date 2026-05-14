<div>
    <div class="py-0">
        <div class="max-w-full mx-auto">
            <div class="bg-transparent dark:bg-transparent overflow-hidden">

                @if (session()->has('message_vehiculo') || session()->has('message_vinculacion') || session()->has('success'))
                    <div class="alert-success mb-4">
                        {{ session('message_vehiculo') ?? session('message_vinculacion') ?? session('success') }}
                    </div>
                @endif
                @if (session()->has('error_vehiculo') || session()->has('error_vinculacion') || session()->has('error'))
                    <div class="alert-danger mb-4">
                        {{ session('error_vehiculo') ?? session('error_vinculacion') ?? session('error') }}
                    </div>
                @endif

                @if ($vistaActual === 'listado_vehiculos')
                    @if ($contratistaId)
                        <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                            <div class="md:col-span-2">
                                <label for="searchVehiculo" class="label-form">Buscar Vehículo</label>
                                <input wire:model.live.debounce.300ms="searchVehiculo" id="searchVehiculo" type="text"
                                       placeholder="Buscar por Patente o Marca..."
                                       class="input-field w-full">
                            </div>
                            <div>
                                <label for="filtroEstadoVehiculo" class="label-form">Filtrar por Estado</label>
                                <select wire:model.live="filtroEstado" id="filtroEstadoVehiculo" class="input-field w-full">
                                    <option value="activos">Sólo Activos</option>
                                    <option value="inactivos">Sólo Inactivos</option>
                                    <option value="todos">Todos</option>
                                </select>
                            </div>
                            <div class="md:col-span-3 text-right">
                                <button wire:click="abrirModalNuevoVehiculo" class="btn-primary" 
                                        wire:loading.attr="disabled"
                                        @if(!$lugarDeTrabajoId || !is_numeric($lugarDeTrabajoId)) disabled title="Debe seleccionar un Lugar de Trabajo/Departamento específico para agregar un vehículo." @endif>
                                    <span wire:loading.remove wire:target="abrirModalNuevoVehiculo">
                                        <x-icons.plus class="w-5 h-5 mr-1 inline-block"/> Agregar Nuevo Vehículo
                                    </span>
                                    <span wire:loading wire:target="abrirModalNuevoVehiculo">
                                        <x-icons.spinner class="w-5 h-5 mr-1 inline-block"/> Abriendo...
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4 text-sm font-semibold text-gray-600 dark:text-gray-300">
                            Mostrando {{ $vinculacionesPaginadas->count() }} de {{ $totalAsignaciones }} asignaciones ({{ $totalVehiculosUnicos }} vehículos únicos).
                        </div>

                        <div class="relative">
                            <div wire:loading.flex class="absolute inset-0 bg-white dark:bg-gray-800 bg-opacity-75 dark:bg-opacity-75 flex flex-col items-center justify-center z-50 rounded-lg">
                                <x-icons.spinner class="h-16 w-16 text-blue-600"/>
                                <p class="mt-4 text-lg font-semibold text-blue-700 dark:text-blue-300">Trabajando para usted...</p>
                            </div>

                            <div class="overflow-x-auto shadow-lg rounded-lg border border-gray-400 dark:border-gray-600">
                                <div class="max-h-[70vh] overflow-y-auto">
                                    <table class="min-w-full border-collapse table-fixed">
                                        <thead class="bg-gray-200 dark:bg-gray-700">
                                            <tr class="border-b-2 border-gray-500 dark:border-gray-600">
                                                <th class="table-header sticky top-0 z-30 bg-gray-200 dark:bg-gray-700 w-12 text-center left-0 border-r border-gray-400 dark:border-gray-500">#</th>
                                                {{-- <th class="table-header sticky top-0 z-30 bg-gray-200 dark:bg-gray-700 w-16 text-center border-r border-gray-400 dark:border-gray-500" style="left: 48px;">ID</th> --}}
                                                <th class="table-header sticky top-0 z-30 bg-gray-200 dark:bg-gray-700 w-32 border-r border-gray-400 dark:border-gray-500" style="left: 48px;">Patente</th>
                                                <th class="table-header sticky top-0 z-30 bg-gray-200 dark:bg-gray-700 w-64 border-r border-gray-400 dark:border-gray-500" style="left: 176px;">Vehículo</th>
                                                
                                                <th class="table-header sticky top-0 z-20 bg-gray-200 dark:bg-gray-700 w-32 text-center border-r border-gray-400 dark:border-gray-500">% Cumplimiento</th>
                                                <th class="table-header sticky top-0 z-20 bg-gray-200 dark:bg-gray-700 w-32 text-center border-r border-gray-400 dark:border-gray-500">Acceso</th>
                                                <th class="table-header sticky top-0 z-20 bg-gray-200 dark:bg-gray-700 w-28 text-center border-r border-gray-400 dark:border-gray-500">Estado</th>
                                                
                                                @if(!empty($documentosMaestros))
                                                    @foreach($documentosMaestros as $doc)
                                                        <th class="table-header sticky top-0 z-20 bg-gray-200 dark:bg-gray-700 w-16 text-center border-r border-gray-400 dark:border-gray-500" title="{{ \App\Models\NombreDocumento::find($doc['nombre_documento_id'])->nombre ?? 'Documento' }}">
                                                            {{ $doc['numero'] }}
                                                        </th>
                                                    @endforeach
                                                @endif

                                                <th class="table-header sticky top-0 z-20 bg-gray-200 dark:bg-gray-700 w-32 max-w-[8rem] border-r border-gray-400 dark:border-gray-500">Lugar de Trabajo/Departamento</th>
                                                <th class="table-header sticky top-0 z-20 bg-gray-200 dark:bg-gray-700 w-32 max-w-[8rem] border-r border-gray-400 dark:border-gray-500">U.O.</th>

                                                <th class="table-header sticky top-0 z-30 bg-gray-200 dark:bg-gray-700 w-40 text-center right-0">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-800">
                                            @forelse ($vinculacionesPaginadas as $vinculacion)
                                                @php 
                                                    $veh = $vinculacion->vehiculo;
                                                    
                                                    $estadoAccesoProtegido = $estadosPreCalculados[$vinculacion->id]['estado_acceso'] ?? ['habilitado' => false, 'motivo' => 'Estado no calculado'];
                                                    $porcentajeCumplimientoProtegido = $estadosPreCalculados[$vinculacion->id]['porcentaje_cumplimiento'] ?? 0;
                                                @endphp
                                                <tr wire:key="vinculacion-veh-{{ $vinculacion->id }}" class="group even:bg-gray-100 dark:even:bg-gray-900/50 hover:bg-blue-100 dark:hover:bg-blue-900/20 border-b border-gray-400 dark:border-gray-600">
                                                    
                                                    <td class="table-cell text-center sticky left-0 z-30 bg-white dark:bg-gray-800 group-even:bg-gray-100 dark:group-even:bg-gray-900/50 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/20 border-r border-gray-400 dark:border-gray-600">{{ ($vinculacionesPaginadas->currentPage() - 1) * $vinculacionesPaginadas->perPage() + $loop->iteration }}</td>
                                                    {{-- <td class="table-cell text-center sticky z-30 bg-white dark:bg-gray-800 group-even:bg-gray-100 dark:group-even:bg-gray-900/50 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/20 border-r border-gray-400 dark:border-gray-600" style="left: 48px;">{{ $veh->id }}</td> --}}
                                                    <td class="table-cell font-mono sticky z-30 bg-white dark:bg-gray-800 group-even:bg-gray-100 dark:group-even:bg-gray-900/50 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/20 border-r border-gray-400 dark:border-gray-600" style="left: 48px;">{{ $veh->patente_completa }}</td>
                                                    <td class="table-cell font-semibold sticky z-30 bg-white dark:bg-gray-800 group-even:bg-gray-100 dark:group-even:bg-gray-900/50 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/20 border-r border-gray-400 dark:border-gray-600" style="left: 176px;">
                                                        {{ $veh->marcaVehiculo->nombre ?? 'N/A' }} {{ $veh->tipoVehiculo->nombre ?? '' }}
                                                        @if($vinculacion->subTipoVehiculo)
                                                            <span class="text-gray-500 dark:text-gray-400 font-normal"> / {{ $vinculacion->subTipoVehiculo->nombre }}</span>
                                                        @endif
                                                    </td>
                                                    
                                                    <td class="table-cell text-center font-semibold border-r border-gray-400 dark:border-gray-600 {{ $porcentajeCumplimientoProtegido < 100 ? 'text-orange-600 dark:text-orange-400' : 'text-green-600 dark:text-green-400' }}">
                                                        {{ $porcentajeCumplimientoProtegido }}%
                                                    </td>

                                                    <td class="table-cell text-center text-sm border-r border-gray-400 dark:border-gray-600">
                                                        @if($estadoAccesoProtegido['habilitado'] ?? false)
                                                            <span class="font-semibold text-green-600 dark:text-green-400" title="{{ $estadoAccesoProtegido['motivo'] ?? 'Estado no calculado' }}">
                                                                HABILITADO
                                                                @if($estadoAccesoProtegido['es_excepcion'] ?? false)
                                                                    <span class="text-xs block mt-1">(MANUAL)</span>
                                                                @endif
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center justify-center font-bold text-2xl text-red-500" title="{{ $estadoAccesoProtegido['motivo'] ?? 'Estado no calculado' }}">
                                                                ✕
                                                            </span>
                                                        @endif
                                                    </td>
                                                    
                                                    <td class="table-cell text-center border-r border-gray-400 dark:border-gray-600">
                                                        <span wire:click="toggleActivoVehiculo({{ $veh->id }})"
                                                              wire:confirm="¿Está seguro de cambiar el estado de este vehículo ({{ $veh->is_active ? 'Activo -> Inactivo' : 'Inactivo -> Activo' }})?"
                                                              class="status-badge {{ $veh->is_active ? 'status-active' : 'status-inactive' }}">
                                                            {{ $veh->is_active ? 'Activo' : 'Inactivo' }}
                                                        </span>
                                                    </td>

                                                    @if(!empty($documentosMaestros))
                                                        @foreach($documentosMaestros as $doc)
                                                            <td class="table-cell text-center border-r border-gray-400 dark:border-gray-600">
                                                                @php
                                                                    $estadosDeVinculacion = $estadosDocumentosPorVinculacion[$vinculacion->id] ?? null;
                                                                    $estado = $estadosDeVinculacion ? $estadosDeVinculacion->get($doc['nombre_documento_id']) : null;
                                                                    $simbolo = '-';
                                                                    $title = 'No Cargado';
                                                                    $textColorClass = 'text-gray-500';

                                                                    if ($estado) {
                                                                        $textColorClass = match($estado) {
                                                                            'Aprobado', 'Aprobado-Modificado' => 'text-green-500',
                                                                            'Rechazado' => 'text-red-500',
                                                                            'Vencido' => 'text-orange-500',
                                                                            'Pendiente Validación' => 'text-blue-500',
                                                                            'En Revisión' => 'text-purple-500',
                                                                            default => 'text-gray-500',
                                                                        };
                                                                        $simbolo = match($estado) {
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
                                                                <span class="font-bold text-lg {{ $textColorClass }}" title="{{ $title }}">
                                                                    {{ $simbolo }}
                                                                </span>
                                                            </td>
                                                        @endforeach
                                                    @endif

                                                    <td class="table-cell w-32 max-w-[8rem] truncate overflow-hidden text-ellipsis border-r border-gray-400 dark:border-gray-600" title="{{ $vinculacion->unidadOrganizacionalMandante?->nombre_jerarquico ?? 'N/A' }}">{{ $vinculacion->unidadOrganizacionalMandante?->nombre_jerarquico ?? 'N/A' }}</td>

                                                    <td class="table-cell text-center whitespace-nowrap sticky right-0 z-10 bg-white dark:bg-gray-800 group-even:bg-gray-100 dark:group-even:bg-gray-900/50 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/20">
                                                        @php
                                                            $mandanteContexto = $vinculacion->unidadOrganizacionalMandante?->mandante ?? $vinculacion->subTipoVehiculo?->mandante;
                                                            $nombreUO = $vinculacion->unidadOrganizacionalMandante?->nombre_jerarquico ?? 'RESERVA';
                                                            $contextoCompleto = ($mandanteContexto?->razon_social ?? 'N/A') . ' - ' . $nombreUO;
                                                        @endphp
                                                        <button wire:click="abrirModalCargaDocumentos({{ $veh->id }}, {{ $mandanteContexto?->id ?? 'null' }}, {{ $vinculacion->unidad_organizacional_mandante_id ?? 'null' }}, '{{ $contextoCompleto }}')" 
                                                                class="action-button-info" title="Gestionar Documentos">
                                                            <x-icons.document-text class="inline-block"/>
                                                        </button>

                                                        <button wire:click="abrirModalEditarVehiculo({{ $veh->id }})" class="action-button-edit" title="Editar Ficha Vehículo"><x-icons.edit class="inline-block"/></button>
                                                        
                                                        <button 
                                                            wire:click="eliminarVinculacion({{ $vinculacion->id }})"
                                                            wire:confirm="¿Está seguro de eliminar esta asignación específica?\n\nEsta acción es irreversible."
                                                            class="action-button-delete"
                                                            @if($veh->vinculaciones_count <= 1)
                                                                disabled
                                                                title="No se puede eliminar la última asignación."
                                                            @else
                                                                title="Eliminar Asignación"
                                                            @endif
                                                            >
                                                            <x-icons.trash class="inline-block"/>
                                                        </button>
                                                        
                                                        <button wire:click="seleccionarVehiculoParaVinculaciones({{ $veh->id }})" class="action-button-link" title="Ver Todas las Vinculaciones"><x-icons.link class="inline-block"/></button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="{{ 8 + count($documentosMaestros) }}" class="table-cell text-center">
                                                        No se encontraron vehículos para los filtros seleccionados.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            @if($vinculacionesPaginadas->hasPages())
                                <div class="mt-4">
                                    {{ $vinculacionesPaginadas->links(data: ['scrollTo' => false]) }}
                                </div>
                            @endif
                        </div> 
                    @else
                         <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                             Por favor, seleccione una Principal y una pestaña para comenzar a gestionar los vehículos.
                         </div>
                    @endif
                @endif

                @if ($vistaActual === 'listado_vinculaciones' && $vehiculoSeleccionado)
                    <div class="mb-4 p-4 border dark:border-gray-700 rounded-md">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                            Vehículo: <span class="font-normal">{{ $vehiculoSeleccionado->patente_completa }}</span>
                        </h3>
                        <button wire:click="abrirModalEditarVehiculo({{ $vehiculoSeleccionado->id }})" class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200 mt-1 inline-flex items-center">
                            <x-icons.edit class="w-4 h-4 mr-1 inline-block"/> Editar Ficha de este Vehículo
                        </button>
                    </div>

                    <div class="mb-4 flex flex-col sm:flex-row justify-between items-center">
                        <button wire:click="irAListadoVehiculos" class="btn-secondary mb-2 sm:mb-0"> 
                            <x-icons.arrow-left class="w-5 h-5 mr-1 inline-block"/> Volver a Listado de Asignaciones
                        </button>
                        <button wire:click="abrirModalNuevaVinculacion" class="btn-primary">
                            <x-icons.plus class="w-5 h-5 mr-1 inline-block"/> Agregar Vinculación (UO + Lugar de Trabajo/Departamento)
                        </button>
                    </div>

                    <div class="overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="table-header">Vinculación (Principal / UO)</th>
                                    <th class="table-header">Lugar de Trabajo/Departamento</th>
                                    <th class="table-header text-center">F. Asignación</th>
                                    <th class="table-header text-center">Estado</th>
                                    <th class="table-header text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($vinculacionesPaginadas ?? [] as $vinc)
                                <tr wire:key="vinculacion-{{ $vinc->id }}" class="table-row-hover">
                                    <td class="table-cell">
                                        {{ $vinc->unidadOrganizacionalMandante?->mandante?->razon_social ?? 'N/A Mandante' }} / <br>
                                        {{ $vinc->unidadOrganizacionalMandante?->nombre_jerarquico ?? 'N/A UO' }}
                                    </td>
                                    <td class="table-cell font-medium text-blue-700 dark:text-blue-400">{{ $vinc->dependencia->nombre_jerarquico ?? 'NO ASIGNADO' }}</td>
                                    <td class="table-cell text-center">{{ $vinc->fecha_asignacion ? \Carbon\Carbon::parse($vinc->fecha_asignacion)->format('d-m-Y') : 'N/A' }}</td>
                                    <td class="table-cell text-center">
                                        <span wire:click="toggleActivoVinculacion({{ $vinc->id }})"
                                              class="status-badge {{ $vinc->is_active ? 'status-active' : 'status-inactive' }}">
                                            {{ $vinc->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                        @if(!$vinc->is_active && $vinc->fecha_desasignacion)
                                            <span class="text-xs block text-gray-500 dark:text-gray-400">Desact: {{ \Carbon\Carbon::parse($vinc->fecha_desasignacion)->format('d-m-Y') }}</span>
                                        @endif
                                    </td>
                                    <td class="table-cell text-center whitespace-nowrap">
                                        @php
                                            $estVincDetalle = $estadosPreCalculados[$vinc->id] ?? null;
                                            $vincReadyDetalle = ($estVincDetalle['estado_acceso']['habilitado'] ?? false) && ($estVincDetalle['porcentaje_cumplimiento'] ?? 0) >= 100;
                                        @endphp
                                        @if(!$vincReadyDetalle)
                                            <button wire:click="abrirModalEditarVinculacion({{ $vinc->id }})" class="action-button-edit" title="Editar Vinculación"><x-icons.edit class="inline-block"/></button>
                                        @endif
                                        <button wire:click="eliminarVinculacion({{ $vinc->id }})" 
                                                wire:confirm="¿Está seguro de eliminar esta vinculación específica? Esta acción es irreversible." 
                                                class="action-button-delete"
                                                @if($vehiculoSeleccionado->vinculaciones()->count() <= 1)
                                                    disabled
                                                    title="No se puede eliminar la última vinculación."
                                                @else
                                                    title="Eliminar Vinculación"
                                                @endif
                                                >
                                            <x-icons.trash class="inline-block"/>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="table-cell text-center">No se encontraron vinculaciones para este vehículo.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($vinculacionesPaginadas && $vinculacionesPaginadas->hasPages())
                        <div class="mt-4">
                            {{ $vinculacionesPaginadas->links(data: ['scrollTo' => false]) }}
                        </div>
                    @endif
                @endif

                @if ($showModalFichaVehiculo)
                    <div class="fixed z-[100] inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75" aria-hidden="true" wire:click="cerrarModalFichaVehiculo"></div>
                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                                <form wire:submit.prevent="guardarVehiculo">
                                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 section-title">{{ $vehiculoId ? 'Editar Ficha de Vehículo' : 'Agregar Nuevo Vehículo' }}</h3>
                                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="label-form">Patente <span class="text-red-500">*</span></label>
                                                <div class="flex items-center space-x-2">
                                                    <input type="text" wire:model.lazy="patente_letras" class="input-field w-1/2 uppercase" placeholder="ABCD">
                                                    <input type="text" wire:model.lazy="patente_numeros" class="input-field w-1/2" placeholder="1234">
                                                </div>
                                                @error('patente_letras')<span class="error-message">{{$message}}</span>@enderror
                                            </div>
                                            <div><label for="ano_fabricacion" class="label-form">Año <span class="text-red-500">*</span></label><input type="number" wire:model.lazy="ano_fabricacion" id="ano_fabricacion" class="input-field w-full">@error('ano_fabricacion')<span class="error-message">{{$message}}</span>@enderror</div>
                                            <div><label for="marca_vehiculo_id" class="label-form">Marca <span class="text-red-500">*</span></label><select wire:model="marca_vehiculo_id" id="marca_vehiculo_id" class="input-field w-full"><option value="">Seleccione</option>@foreach($marcasVehiculo as $m)<option value="{{$m->id}}">{{$m->nombre}}</option>@endforeach</select>@error('marca_vehiculo_id')<span class="error-message">{{$message}}</span>@enderror</div>
                                            <div><label for="tipo_vehiculo_id" class="label-form">Tipo <span class="text-red-500">*</span></label><select wire:model="tipo_vehiculo_id" id="tipo_vehiculo_id" class="input-field w-full"><option value="">Seleccione</option>@foreach($tiposVehiculo as $t)<option value="{{$t->id}}">{{$t->nombre}}</option>@endforeach</select>@error('tipo_vehiculo_id')<span class="error-message">{{$message}}</span>@enderror</div>
                                            <div><label for="color_vehiculo_id" class="label-form">Color <span class="text-red-500">*</span></label><select wire:model="color_vehiculo_id" id="color_vehiculo_id" class="input-field w-full"><option value="">Seleccione</option>@foreach($coloresVehiculo as $c)<option value="{{$c->id}}">{{$c->nombre}}</option>@endforeach</select>@error('color_vehiculo_id')<span class="error-message">{{$message}}</span>@enderror</div>
                                            <div><label for="tenencia_vehiculo_id" class="label-form">Tenencia</label><select wire:model="tenencia_vehiculo_id" id="tenencia_vehiculo_id" class="input-field w-full"><option value="">Seleccione</option>@foreach($tenenciasVehiculo as $t)<option value="{{$t->id}}">{{$t->nombre}}</option>@endforeach</select>@error('tenencia_vehiculo_id')<span class="error-message">{{$message}}</span>@enderror</div>
                                            @if(!$vehiculoId)
                                                <div class="md:col-span-2"><label for="v_unidad_organizacional_mandante_id_nuevo" class="label-form">Unidad Organizacional Inicial <span class="text-red-500">*</span></label><select wire:model="v_unidad_organizacional_mandante_id" id="v_unidad_organizacional_mandante_id_nuevo" class="input-field w-full"><option value="">Seleccione</option>@foreach($unidadesOrganizacionalesDisponibles as $uo)<option value="{{$uo->id}}">{{$uo->nombre_jerarquico}}</option>@endforeach</select>@error('v_unidad_organizacional_mandante_id')<span class="error-message">{{$message}}</span>@enderror</div>
                                            @endif
                                            <div class="md:col-span-2"><label class="label-form flex items-center"><input type="checkbox" wire:model="vehiculo_is_active" class="form-checkbox rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-indigo-400"><span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Ficha Activa</span></label></div>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 flex justify-between">
                                        <div>@if($vehiculoId)<button type="button" wire:click="eliminarVehiculo({{$vehiculoId}})" wire:confirm="ADVERTENCIA: ¿Está seguro de eliminar PERMANENTEMENTE la ficha de este vehículo y TODAS sus vinculaciones?" class="btn-danger">Eliminación Permanente</button>@endif</div>
                                        <div class="flex"><button type="button" wire:click="cerrarModalFichaVehiculo" class="btn-secondary mr-2">Cancelar</button><button type="submit" class="btn-primary">{{$vehiculoId ? 'Guardar Cambios' : 'Crear Vehículo'}}</button></div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
                
                @if ($showModalNuevaVinculacion)
                    <div class="fixed z-[100] inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75" aria-hidden="true" wire:click="cerrarModalVinculacion"></div>
                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                <form wire:submit.prevent="guardarVinculacion">
                                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 section-title">{{ $vinculacionId ? 'Editar Vinculación' : 'Nueva Vinculación' }}</h3>
                                        <div class="mt-4 space-y-4">
                                            <div><label for="v_mandante_id" class="label-form">Principal <span class="text-red-500">*</span></label><select wire:model.live="v_mandante_id" id="v_mandante_id" class="input-field w-full"><option value="">Seleccione</option>@foreach($mandantesDisponibles as $m)<option value="{{$m->id}}">{{$m->razon_social}}</option>@endforeach</select>@error('v_mandante_id')<span class="error-message">{{$message}}</span>@enderror</div>
                                            <div><label for="v_unidad_organizacional_mandante_id" class="label-form">Unidad Operativa <span class="text-red-500">*</span></label><select wire:model="v_unidad_organizacional_mandante_id" id="v_unidad_organizacional_mandante_id" class="input-field w-full" @if(empty($unidadesOrganizacionalesDisponibles)) disabled @endif><option value="">Seleccione</option>@foreach($unidadesOrganizacionalesDisponibles as $uo)<option value="{{$uo->id}}">{{$uo->nombre_jerarquico}}</option>@endforeach</select>@error('v_unidad_organizacional_mandante_id')<span class="error-message">{{$message}}</span>@enderror</div>
                                            <div><label for="v_dependencia_id" class="label-form">Lugar de Trabajo/Departamento</label><select wire:model.live="v_dependencia_id" id="v_dependencia_id" class="input-field w-full" @if(empty($dependenciasDisponibles)) disabled @endif><option value="">Seleccione</option>@if($puedeEstarEnReserva)<option value="null">-- Dejar en Reserva --</option>@endif @foreach($dependenciasDisponibles as $d)<option value="{{$d->id}}">{{$d->nombre_jerarquico}}</option>@endforeach</select>@error('v_dependencia_id')<span class="error-message">{{$message}}</span>@enderror</div>
                                            @if(!empty($subTiposVehiculoDisponibles))
                                                <div>
                                                    <label for="v_sub_tipo_vehiculo_mandante_id" class="label-form">Sub-Tipo de Vehículo (según Principal)</label>
                                                    @if($vinculacionId && $v_sub_tipo_vehiculo_mandante_id)
                                                        {{-- Ya tiene subtipo asignado: sólo lectura --}}
                                                        @php $stActual = $subTiposVehiculoDisponibles->firstWhere('id', $v_sub_tipo_vehiculo_mandante_id); @endphp
                                                        <div class="input-field w-full bg-gray-100 dark:bg-gray-700 flex items-center justify-between">
                                                            <span class="font-medium text-gray-700 dark:text-gray-300">
                                                                {{ $stActual->nombre ?? 'Subtipo asignado' }}{{ ($stActual && $stActual->tipoVehiculo) ? ' ('.$stActual->tipoVehiculo->nombre.')' : '' }}
                                                            </span>
                                                            <span class="text-xs text-orange-600 dark:text-orange-400 font-semibold ml-2">Solo Admin puede cambiar</span>
                                                        </div>
                                                        <input type="hidden" wire:model="v_sub_tipo_vehiculo_mandante_id">
                                                    @else
                                                        <select wire:model="v_sub_tipo_vehiculo_mandante_id" id="v_sub_tipo_vehiculo_mandante_id" class="input-field w-full">
                                                            <option value="">— Sin sub-tipo asignado —</option>
                                                            @foreach($subTiposVehiculoDisponibles as $st)
                                                                <option value="{{ $st->id }}">{{ $st->nombre }}{{ $st->tipoVehiculo ? ' ('.$st->tipoVehiculo->nombre.')' : '' }}</option>
                                                            @endforeach
                                                        </select>
                                                    @endif
                                                </div>
                                            @endif

                                            <div>
                                                @include('livewire._partials._multi_select_condicion', [
                                                    'opciones'      => $condicionesVehiculoDisponibles,
                                                    'seleccionados' => $v_condiciones_vehiculo_ids,
                                                    'wireKey'       => 'v_condiciones_vehiculo_ids',
                                                    'label'         => 'Condiciones de Vehículo en Proyecto',
                                                    'placeholder'   => 'Escoja condiciones para el vehículo...',
                                                ])
                                                @error('v_condiciones_vehiculo_ids')<span class="error-message">{{$message}}</span>@enderror
                                            </div>
                                            <div><label for="v_fecha_asignacion" class="label-form">Fecha Asignación <span class="text-red-500">*</span></label><input type="date" wire:model.lazy="v_fecha_asignacion" id="v_fecha_asignacion" class="input-field w-full">@error('v_fecha_asignacion')<span class="error-message">{{$message}}</span>@enderror</div>
                                            <div><label class="label-form flex items-center"><input type="checkbox" wire:model.live="v_is_active" class="form-checkbox rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-indigo-400"><span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Vinculación Activa</span></label></div>
                                            @if(!$v_is_active)
                                                <div><label for="v_fecha_desasignacion" class="label-form">Fecha Desactivación <span class="text-red-500">*</span></label><input type="date" wire:model.lazy="v_fecha_desasignacion" id="v_fecha_desasignacion" class="input-field w-full">@error('v_fecha_desasignacion')<span class="error-message">{{$message}}</span>@enderror</div>
                                                <div><label for="v_motivo_desasignacion" class="label-form">Motivo <span class="text-red-500">*</span></label><textarea wire:model.lazy="v_motivo_desasignacion" id="v_motivo_desasignacion" class="input-field w-full"></textarea>@error('v_motivo_desasignacion')<span class="error-message">{{$message}}</span>@enderror</div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse"><button type="submit" class="btn-primary">{{$vinculacionId ? 'Guardar Cambios' : 'Crear Vinculación'}}</button><button type="button" wire:click="cerrarModalVinculacion" class="btn-secondary mr-2">Cancelar</button></div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>