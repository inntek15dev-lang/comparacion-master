<div>
    <div class="py-0">
        <div class="max-w-full mx-auto">
            <div class="bg-transparent overflow-hidden">

                @if (session()->has('message_embarcacion') || session()->has('message_vinculacion'))
                    <div class="alert-success mb-4">{{ session('message_embarcacion') ?? session('message_vinculacion') }}</div>
                @endif
                @if (session()->has('error_embarcacion') || session()->has('error_vinculacion') || session()->has('error'))
                    <div class="alert-danger mb-4">{{ session('error_embarcacion') ?? session('error_vinculacion') ?? session('error') }}</div>
                @endif

                @if ($vistaActual === 'listado_embarcaciones')
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <div class="md:col-span-2">
                            <label for="searchEmbarcacion" class="label-form">Buscar Embarcación</label>
                            <input wire:model.live.debounce.300ms="searchEmbarcacion" id="searchEmbarcacion" type="text" placeholder="Buscar por Matrícula o Tipo..." class="input-field w-full">
                        </div>
                        <div>
                            <label for="filtroEstadoEmbarcacion" class="label-form">Filtrar por Estado</label>
                            <select wire:model.live="filtroEstado" id="filtroEstadoEmbarcacion" class="input-field w-full">
                                <option value="activos">Sólo Activas</option>
                                <option value="inactivos">Sólo Inactivas</option>
                                <option value="todos">Todas</option>
                            </select>
                        </div>
                        <div class="md:col-span-3 text-right">
                            <button wire:click="abrirModalNuevaEmbarcacion" class="btn-primary" @if(!$lugarDeTrabajoId || !is_numeric($lugarDeTrabajoId)) disabled title="Debe seleccionar un Lugar de Trabajo/Departamento específico para agregar una embarcación." @endif>
                                <x-icons.plus class="w-5 h-5 mr-1 inline-block"/> Agregar Nueva Embarcación
                            </button>
                        </div>
                    </div>

                    <div class="mb-4 text-sm font-semibold text-gray-600">
                        Mostrando {{ $totalAsignaciones }} asignaciones de {{ $totalEmbarcacionesUnicas }} embarcaciones únicas.
                    </div>

                    {{-- ================== INICIO DE LA MODIFICACIÓN (AÑADIR CONTENEDOR RELATIVO Y SPINNER) ================== --}}
                    <div class="relative">
                        <!-- Overlay de Carga para Acciones -->
                        <div wire:loading wire:target="abrirModalCargaDocumentos, abrirModalEditarEmbarcacion, eliminarVinculacion, seleccionarEmbarcacionParaVinculaciones, toggleActivoEmbarcacion"
                             class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-20 rounded-lg">
                            <div class="flex items-center">
                                <div class="animate-spin rounded-full h-20 w-20 border-t-4 border-b-4 border-blue-600"></div>
                                <span class="ml-6 text-2xl font-bold text-blue-800 drop-shadow">TRABAJANDO PARA USTED...</span>
                            </div>
                        </div>
                    {{-- ================== FIN DE LA MODIFICACIÓN ======================================================= --}}

                        @forelse ($gruposDeVinculaciones as $nombreLugar => $gruposPorUO)
                            @foreach($gruposPorUO as $nombreUO => $vinculaciones)
                                <div class="mb-8">
                                    <h3 class="text-xl font-bold text-blue-800 dark:text-blue-300 bg-blue-100 dark:bg-gray-700 p-3 rounded-t-lg border-b-2 border-blue-500">
                                        <span class="font-light">Lugar de Trabajo/Departamento:</span> {{ $nombreLugar }} 
                                        <span class="mx-2">|</span> 
                                        <span class="font-light">UNIDAD OPERATIVA:</span> {{ $nombreUO }}
                                    </h3>
                                    <div class="overflow-x-auto shadow-md sm:rounded-b-lg border-x border-b border-gray-200 dark:border-gray-700">
                                        <table class="min-w-full">
                                            <thead class="bg-gray-50 dark:bg-gray-700">
                                                <tr>
                                                    <th class="table-header border border-gray-200 dark:border-gray-600">#</th>
                                                    <th class="table-header border border-gray-200 dark:border-gray-600 sticky left-0 bg-gray-50 dark:bg-gray-700 z-10">ID</th>
                                                    <th class="table-header border border-gray-200 dark:border-gray-600">Matrícula</th>
                                                    <th class="table-header border border-gray-200 dark:border-gray-600">Tipo</th>
                                                    <th class="table-header border border-gray-200 dark:border-gray-600 text-center">% Cumplimiento</th>
                                                    <th class="table-header border border-gray-200 dark:border-gray-600 text-center">Acceso</th>
                                                    <th class="table-header border border-gray-200 dark:border-gray-600 text-center">Estado</th>
                                                    
                                                    @if(!empty($documentosMaestros))
                                                        @foreach($documentosMaestros as $doc)
                                                            <th class="table-header border border-gray-200 dark:border-gray-600 text-center" title="{{ \App\Models\NombreDocumento::find($doc['nombre_documento_id'])->nombre ?? 'Documento' }}">
                                                                {{ $doc['numero'] }}
                                                            </th>
                                                        @endforeach
                                                    @endif

                                                    <th class="table-header border border-gray-200 dark:border-gray-600 text-center sticky right-0 bg-gray-50 dark:bg-gray-700 z-10">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                @foreach ($vinculaciones as $vinculacion)
                                                    @php $emb = $vinculacion->embarcacion; @endphp
                                                    {{-- ================== INICIO DE LA MODIFICACIÓN (AÑADIR 'group') ================== --}}
                                                    <tr wire:key="vinculacion-emb-{{ $vinculacion->id }}" class="group hover:bg-blue-50 dark:hover:bg-gray-700 odd:bg-white even:bg-gray-50 dark:odd:bg-gray-800 dark:even:bg-gray-800/50">
                                                    {{-- ================== FIN DE LA MODIFICACIÓN ======================================== --}}
                                                        <td class="table-cell border border-gray-200 dark:border-gray-600 text-center">{{ $loop->iteration }}</td>
                                                        {{-- ================== INICIO DE LA MODIFICACIÓN (FONDO SÓLIDO) ================== --}}
                                                        <td class="table-cell border border-gray-200 dark:border-gray-600 sticky left-0 bg-white dark:bg-gray-800 group-hover:bg-blue-50 dark:group-hover:bg-gray-700 z-10">{{ $emb->id }}</td>
                                                        {{-- ================== FIN DE LA MODIFICACIÓN ======================================== --}}
                                                        <td class="table-cell border border-gray-200 dark:border-gray-600 font-mono">{{ $emb->matricula_completa }}</td>
                                                        <td class="table-cell border border-gray-200 dark:border-gray-600 font-semibold">{{ $emb->tipoEmbarcacion->nombre ?? 'N/A' }}</td>
                                                        
                                                        @php
                                                            $mandanteIdParaCalculo = $vinculacion->unidadOrganizacionalMandante->mandante_id;
                                                            $cumplimientoData = $emb->calcularPorcentajeCumplimiento($mandanteIdParaCalculo, $vinculacion->unidad_organizacional_mandante_id);
                                                        @endphp
                                                        <td class="table-cell border border-gray-200 dark:border-gray-600 text-center font-semibold {{ $cumplimientoData < 100 ? 'text-orange-600 dark:text-orange-400' : 'text-green-600 dark:text-green-400' }}">
                                                            {{ $cumplimientoData }}%
                                                        </td>
                                                        <td class="table-cell border border-gray-200 dark:border-gray-600 text-center text-sm">
                                                            @php
                                                                $accesoData = $emb->determinarAccesoHabilitado($mandanteIdParaCalculo, $vinculacion->unidad_organizacional_mandante_id);
                                                            @endphp
                                                            <span class="inline-flex items-center justify-center w-full h-full" title="{{ $accesoData['motivo'] }}">
                                                                @if($accesoData['habilitado'])
                                                                    <x-icons.check-circle class="w-6 h-6 text-green-500"/>
                                                                @else
                                                                    <x-icons.x-circle class="w-6 h-6 text-red-500"/>
                                                                @endif
                                                            </span>
                                                        </td>
                                                        
                                                        <td class="table-cell border border-gray-200 dark:border-gray-600 text-center">
                                                            <span wire:click="toggleActivoEmbarcacion({{ $emb->id }})"
                                                                  wire:confirm="¿Está seguro de cambiar el estado de esta embarcación ({{ $emb->is_active ? 'Activa -> Inactiva' : 'Inactiva -> Activa' }})?"
                                                                  class="status-badge {{ $emb->is_active ? 'status-active' : 'status-inactive' }}">
                                                                {{ $emb->is_active ? 'Activa' : 'Inactiva' }}
                                                            </span>
                                                        </td>

                                                        @if(!empty($documentosMaestros))
                                                            @foreach($documentosMaestros as $doc)
                                                                <td class="table-cell border border-gray-200 dark:border-gray-600 text-center">
                                                                    <div class="flex justify-center items-center">
                                                                        @php
                                                                            $estado = $vinculacion->estadosDocumentos->get($doc['nombre_documento_id']);
                                                                            $simbolo = '-';
                                                                            $colorClass = 'bg-gray-400';
                                                                            $title = 'No Cargado';

                                                                            if ($estado) {
                                                                                $colorClass = match($estado) {
                                                                                    'Aprobado', 'Aprobado-Modificado' => 'bg-green-500',
                                                                                    'Rechazado' => 'bg-red-500',
                                                                                    'Vencido' => 'bg-yellow-500',
                                                                                    'Pendiente Validación' => 'bg-blue-500',
                                                                                    'En Revisión' => 'bg-purple-500',
                                                                                    default => 'bg-gray-500',
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
                                                                            }
                                                                        @endphp
                                                                        <span class="inline-block w-6 h-6 {{ $colorClass }} rounded-full text-white text-xs font-bold flex items-center justify-center" title="{{ $title }}">
                                                                            {{ $simbolo }}
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                            @endforeach
                                                        @endif

                                                        {{-- ================== INICIO DE LA MODIFICACIÓN (FONDO SÓLIDO) ================== --}}
                                                        <td class="table-cell border border-gray-200 dark:border-gray-600 text-center whitespace-nowrap sticky right-0 bg-white dark:bg-gray-800 group-hover:bg-blue-50 dark:group-hover:bg-gray-700 z-10">
                                                        {{-- ================== FIN DE LA MODIFICACIÓN ======================================== --}}
                                                            @php
                                                                $mandanteContexto = $vinculacion->unidadOrganizacionalMandante->mandante;
                                                                $contextoCompleto = ($mandanteContexto->razon_social ?? 'N/A') . ' - ' . ($vinculacion->unidadOrganizacionalMandante->nombre_jerarquico ?? 'N/A');
                                                            @endphp
                                                            <button wire:click="abrirModalCargaDocumentos({{ $emb->id }}, {{ $mandanteContexto->id }}, {{ $vinculacion->unidad_organizacional_mandante_id }}, '{{ $contextoCompleto }}')" 
                                                                    class="action-button-info" title="Gestionar Documentos">
                                                                <x-icons.document-text class="inline-block"/>
                                                            </button>
                                                            <button wire:click="abrirModalEditarEmbarcacion({{ $emb->id }})" class="action-button-edit" title="Editar Ficha Embarcación"><x-icons.edit/></button>
                                                            <button wire:click="eliminarVinculacion({{ $vinculacion->id }})" 
                                                                    wire:confirm="¿Está seguro de eliminar esta asignación específica?" 
                                                                    class="action-button-delete"
                                                                    @if($emb->vinculaciones_count <= 1)
                                                                        disabled
                                                                        title="No se puede eliminar la última asignación."
                                                                    @else
                                                                        title="Eliminar Asignación"
                                                                    @endif
                                                                    >
                                                                <x-icons.trash/>
                                                            </button>
                                                            <button wire:click="seleccionarEmbarcacionParaVinculaciones({{ $emb->id }})" class="action-button-link" title="Ver Todas las Vinculaciones"><x-icons.link/></button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        @empty
                            <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                                No se encontraron embarcaciones para los filtros seleccionados.
                            </div>
                        @endforelse
                    {{-- ================== INICIO DE LA MODIFICACIÓN (CIERRE DE DIV RELATIVO) ================== --}}
                    </div>
                    {{-- ================== FIN DE LA MODIFICACIÓN ======================================================= --}}
                @endif

                {{-- El resto del archivo blade (vista de vinculaciones y modales) no requiere cambios --}}
                @if ($vistaActual === 'listado_vinculaciones' && $embarcacionSeleccionada)
                    <div class="mb-4 p-4 border rounded-md">
                        <h3 class="text-lg font-semibold">Embarcación: <span class="font-normal">{{ $embarcacionSeleccionada->matricula_completa }}</span></h3>
                        <button wire:click="abrirModalEditarEmbarcacion({{ $embarcacionSeleccionada->id }})" class="text-sm text-indigo-600 hover:underline">Editar Ficha de esta Embarcación</button>
                    </div>
                    <div class="mb-4 flex justify-between items-center">
                        <button wire:click="irAListadoEmbarcaciones" class="btn-secondary"><x-icons.arrow-left class="w-5 h-5 mr-1"/> Volver a Listado</button>
                        <button wire:click="abrirModalNuevaVinculacion" class="btn-primary"><x-icons.plus class="w-5 h-5 mr-1"/> Agregar Vinculación</button>
                    </div>
                    <div class="overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="table-header">Vinculación (UO)</th>
                                    <th class="table-header">Lugar de Trabajo/Departamento</th>
                                    <th class="table-header text-center">F. Asignación</th>
                                    <th class="table-header text-center">Estado</th>
                                    <th class="table-header text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($vinculacionesPaginadas ?? [] as $vinc)
                                <tr wire:key="vinculacion-emb-{{ $vinc->id }}" class="table-row-hover">
                                    <td class="table-cell">{{ $vinc->unidadOrganizacionalMandante?->nombre_jerarquico ?? 'N/A' }}</td>
                                    <td class="table-cell font-medium text-blue-700">{{ $vinc->dependencia->nombre_jerarquico ?? 'EN RESERVA' }}</td>
                                    <td class="table-cell text-center">{{ $vinc->fecha_asignacion->format('d-m-Y') }}</td>
                                    <td class="table-cell text-center"><span class="status-badge {{ $vinc->is_active ? 'status-active' : 'status-inactive' }}">{{ $vinc->is_active ? 'Activa' : 'Inactiva' }}</span></td>
                                    <td class="table-cell text-center whitespace-nowrap">
                                        <button wire:click="abrirModalEditarVinculacion({{ $vinc->id }})" class="action-button-edit" title="Editar Vinculación"><x-icons.edit/></button>
                                        <button wire:click="eliminarVinculacion({{ $vinc->id }})" 
                                                wire:confirm="¿Está seguro?" 
                                                class="action-button-delete"
                                                @if($embarcacionSeleccionada->vinculaciones()->count() <= 1)
                                                    disabled
                                                    title="No se puede eliminar la última vinculación."
                                                @else
                                                    title="Eliminar Vinculación"
                                                @endif
                                                >
                                            <x-icons.trash/>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="table-cell text-center">No se encontraron vinculaciones.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($vinculacionesPaginadas && $vinculacionesPaginadas->hasPages())
                        <div class="mt-4">{{ $vinculacionesPaginadas->links('vendor.livewire.tailwind', ['pageName' => 'vinculacionesPage']) }}</div>
                    @endif
                @endif

                @if ($showModalFichaEmbarcacion)
                    <div class="fixed z-[100] inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="cerrarModalFichaEmbarcacion"></div>
                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                                <form wire:submit.prevent="guardarEmbarcacion">
                                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900 section-title">{{ $embarcacionId ? 'Editar Ficha de Embarcación' : 'Agregar Nueva Embarcación' }}</h3>
                                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="label-form">Matrícula <span class="text-red-500">*</span></label>
                                                <div class="flex items-center space-x-2">
                                                    <input type="text" wire:model.lazy="matricula_letras" class="input-field w-1/2 uppercase" placeholder="LETRAS">
                                                    <input type="text" wire:model.lazy="matricula_numeros" class="input-field w-1/2" placeholder="NÚMEROS">
                                                </div>
                                                @error('matricula_letras')<span class="error-message">{{$message}}</span>@enderror
                                            </div>
                                            <div><label for="ano_fabricacion_emb" class="label-form">Año <span class="text-red-500">*</span></label><input type="number" wire:model.lazy="ano_fabricacion" id="ano_fabricacion_emb" class="input-field w-full">@error('ano_fabricacion')<span class="error-message">{{$message}}</span>@enderror</div>
                                            <div><label for="tipo_embarcacion_id" class="label-form">Tipo <span class="text-red-500">*</span></label><select wire:model="tipo_embarcacion_id" id="tipo_embarcacion_id" class="input-field w-full"><option value="">Seleccione</option>@foreach($tiposEmbarcacion as $t)<option value="{{$t->id}}">{{$t->nombre}}</option>@endforeach</select>@error('tipo_embarcacion_id')<span class="error-message">{{$message}}</span>@enderror</div>
                                            <div><label for="tenencia_vehiculo_id_emb" class="label-form">Tenencia</label><select wire:model="tenencia_vehiculo_id" id="tenencia_vehiculo_id_emb" class="input-field w-full"><option value="">Seleccione</option>@foreach($tenencias as $t)<option value="{{$t->id}}">{{$t->nombre}}</option>@endforeach</select>@error('tenencia_vehiculo_id')<span class="error-message">{{$message}}</span>@enderror</div>
                                            @if(!$embarcacionId)
                                                <div class="md:col-span-2"><label for="v_uo_id_emb" class="label-form">Unidad Organizacional Inicial <span class="text-red-500">*</span></label><select wire:model="v_unidad_organizacional_mandante_id" id="v_uo_id_emb" class="input-field w-full"><option value="">Seleccione</option>@foreach($unidadesOrganizacionalesDisponibles as $uo)<option value="{{$uo->id}}">{{$uo->nombre_jerarquico}}</option>@endforeach</select>@error('v_unidad_organizacional_mandante_id')<span class="error-message">{{$message}}</span>@enderror</div>
                                            @endif
                                            <div class="md:col-span-2"><label class="label-form flex items-center"><input type="checkbox" wire:model="embarcacion_is_active" class="form-checkbox"><span class="ml-2">Ficha Activa</span></label></div>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-between">
                                        <div>@if($embarcacionId)<button type="button" wire:click="eliminarEmbarcacion({{$embarcacionId}})" wire:confirm="ADVERTENCIA: ¿Está seguro de eliminar PERMANENTEMENTE la ficha de esta embarcación y TODAS sus vinculaciones?" class="btn-danger">Eliminación Permanente</button>@endif</div>
                                        <div class="flex"><button type="button" wire:click="cerrarModalFichaEmbarcacion" class="btn-secondary mr-2">Cancelar</button><button type="submit" class="btn-primary">{{$embarcacionId ? 'Guardar Cambios' : 'Crear Embarcación'}}</button></div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
                
                @if ($showModalNuevaVinculacion)
                    <div class="fixed z-[100] inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="cerrarModalVinculacion"></div>
                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                <form wire:submit.prevent="guardarVinculacion">
                                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900 section-title">{{ $vinculacionId ? 'Editar Vinculación' : 'Nueva Vinculación' }}</h3>
                                        <div class="mt-4 space-y-4">
                                            <div><label for="v_mandante_id_emb" class="label-form">Principal <span class="text-red-500">*</span></label><select wire:model.live="v_mandante_id" id="v_mandante_id_emb" class="input-field w-full"><option value="">Seleccione</option>@foreach($mandantesDisponibles as $m)<option value="{{$m->id}}">{{$m->razon_social}}</option>@endforeach</select>@error('v_mandante_id')<span class="error-message">{{$message}}</span>@enderror</div>
                                            <div><label for="v_uo_id_emb" class="label-form">Unidad Operativa <span class="text-red-500">*</span></label><select wire:model="v_unidad_organizacional_mandante_id" id="v_uo_id_emb" class="input-field w-full" @if(empty($unidadesOrganizacionalesDisponibles)) disabled @endif><option value="">Seleccione</option>@foreach($unidadesOrganizacionalesDisponibles as $uo)<option value="{{$uo->id}}">{{$uo->nombre_jerarquico}}</option>@endforeach</select>@error('v_unidad_organizacional_mandante_id')<span class="error-message">{{$message}}</span>@enderror</div>
                                            <div><label for="v_dependencia_id_emb" class="label-form">Lugar de Trabajo/Departamento</label><select wire:model="v_dependencia_id" id="v_dependencia_id_emb" class="input-field w-full" @if(empty($dependenciasDisponibles)) disabled @endif><option value="">Seleccione</option>@if($puedeEstarEnReserva)<option value="null">-- Dejar en Reserva --</option>@endif @foreach($dependenciasDisponibles as $d)<option value="{{$d->id}}">{{$d->nombre_jerarquico}}</option>@endforeach</select>@error('v_dependencia_id')<span class="error-message">{{$message}}</span>@enderror</div>
                                            <div><label for="v_fecha_asignacion_emb" class="label-form">Fecha Asignación <span class="text-red-500">*</span></label><input type="date" wire:model.lazy="v_fecha_asignacion" id="v_fecha_asignacion_emb" class="input-field w-full">@error('v_fecha_asignacion')<span class="error-message">{{$message}}</span>@enderror</div>
                                            <div><label class="label-form flex items-center"><input type="checkbox" wire:model.live="v_is_active" class="form-checkbox"><span class="ml-2">Vinculación Activa</span></label></div>
                                            @if(!$v_is_active)
                                                <div><label for="v_fecha_desasignacion_emb" class="label-form">Fecha Desactivación <span class="text-red-500">*</span></label><input type="date" wire:model.lazy="v_fecha_desasignacion" id="v_fecha_desasignacion_emb" class="input-field w-full">@error('v_fecha_desasignacion')<span class="error-message">{{$message}}</span>@enderror</div>
                                                <div><label for="v_motivo_desasignacion_emb" class="label-form">Motivo <span class="text-red-500">*</span></label><textarea wire:model.lazy="v_motivo_desasignacion" id="v_motivo_desasignacion_emb" class="input-field w-full"></textarea>@error('v_motivo_desasignacion')<span class="error-message">{{$message}}</span>@enderror</div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse"><button type="submit" class="btn-primary">{{$vinculacionId ? 'Guardar Cambios' : 'Crear Vinculación'}}</button><button type="button" wire:click="cerrarModalVinculacion" class="btn-secondary mr-2">Cancelar</button></div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>