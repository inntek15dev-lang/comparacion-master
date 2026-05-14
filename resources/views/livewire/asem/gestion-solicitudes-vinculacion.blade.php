<div class="p-6 bg-white dark:bg-gray-800 shadow-md rounded-lg">
    <h2 class="text-2xl font-semibold text-gray-800 dark:text-white mb-6">Gestión de Solicitudes de Vinculación</h2>

    @include('components.session-messages')

    <div class="flex flex-col sm:flex-row justify-between items-center mb-4 space-y-2 sm:space-y-0">
        <div class="w-full sm:w-2/5">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar por Razón Social o RUT/NIT/RUC/CNPJ del solicitante..." class="input-field w-full">
        </div>
        <div class="flex items-center gap-4">
            {{-- ================== INICIO DE LA MODIFICACIÓN CANÓNICA ================== --}}
            @if($esAdminAsem)
                {{-- <button wire:click="abrirModalManual" class="btn-secondary"><x-icons.plus class="inline-block h-5 w-5 mr-1"/> Solicitud Manual</button> --}}
                @if($filtroEstado === 'APROBADA')
                    <button wire:click="archivarCompletados" wire:confirm="¿Está seguro de archivar todos los contratistas que han completado los 7 pasos?" class="btn-secondary">
                        <x-icons.archive-box class="inline-block h-5 w-5 mr-1"/> Archivar Completados
                    </button>
                @endif
            @endif
            
            <select wire:model.live="filtroEstado" class="input-field">
                <option value="PENDIENTE">Pendientes</option>
                @if($esAdminAsem)
                    <option value="APROBADA">Aprobadas (Onboarding)</option>
                @endif
                <option value="RECHAZADA">Rechazadas</option>
            </select>
            {{-- ================== FIN DE LA MODIFICACIÓN CANÓNICA ==================== --}}
        </div>
    </div>

    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Solicitante</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipo / Vínculo</th>
                    
                    @if($filtroEstado === 'APROBADA' && $esAdminAsem)
                        @foreach($nombresPasos as $numero => $nombre)
                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-48">{{ $nombre }}</th>
                        @endforeach
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-56">Bitácora General</th>
                    @else
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha Solicitud</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha Acción / Responsable</th>
                    @endif
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($solicitudes as $solicitud)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 align-top">
                            <span class="font-semibold">{{ $solicitud->contratista->razon_social ?? 'N/A' }}</span><br>
                            <small class="text-gray-500">{{ $solicitud->contratista->rut ?? 'N/A' }}</small>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 align-top">
                            <span class="font-semibold">{{ $solicitud->tipo_solicitud }}</span><br>
                            <small class="text-gray-500">
                                @if($solicitud->tipo_solicitud == 'SUBCONTRATISTA')
                                    Sub de: {{ $solicitud->contratistaPadre->razon_social ?? 'N/A' }} <br>
                                @endif
                                Principal: {{ $solicitud->mandante->razon_social ?? 'N/A' }}
                            </small>
                        </td>
                        
                        @if($filtroEstado === 'APROBADA' && $esAdminAsem)
                            @for ($i = 1; $i <= 7; $i++)
                                @php
                                    $contratistaId = $solicitud->contratista->id;
                                    $onboarding = $solicitud->contratista->onboarding;
                                    $esCompleto = $onboarding?->{$this->getCampoPaso($i, 'completo')} ?? false;
                                @endphp
                                <td class="px-2 py-3 text-center align-top">
                                    <div class="flex flex-col items-center space-y-2" x-data="{ guardado: false }" x-on:datos-paso-guardados.window="if ($event.detail.contratistaId === {{ $contratistaId }} && $event.detail.paso === {{ $i }}) { guardado = true; setTimeout(() => guardado = false, 2000) }">
                                        <input type="checkbox" 
                                               class="form-checkbox h-5 w-5 text-indigo-600 rounded focus:ring-indigo-500"
                                               wire:click="marcarPaso({{ $contratistaId }}, {{ $i }})"
                                               {{ $esCompleto ? 'checked' : '' }}>
                                        <input type="date" wire:model.defer="pasoData.{{$contratistaId}}.{{$i}}.fecha" class="input-field text-xs w-full max-w-[150px]">
                                        <textarea wire:model.defer="pasoData.{{$contratistaId}}.{{$i}}.comentario" rows="2" class="input-field text-xs w-full max-w-[150px]" placeholder="Comentario del paso..."></textarea>
                                        <button wire:click="guardarDatosPaso({{ $contratistaId }}, {{ $i }})" class="btn-secondary btn-sm w-full max-w-[150px]">
                                            <span wire:loading.remove wire:target="guardarDatosPaso({{ $contratistaId }}, {{ $i }})">Guardar</span>
                                            <span wire:loading wire:target="guardarDatosPaso({{ $contratistaId }}, {{ $i }})">...</span>
                                        </button>
                                        <span x-show="guardado" x-transition class="text-green-500 text-xs">¡Guardado!</span>
                                    </div>
                                </td>
                            @endfor
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 align-top" x-data="{ guardado: false }" x-on:comentario-guardado.window="if ($event.detail.contratistaId === {{ $solicitud->contratista->id }}) { guardado = true; setTimeout(() => guardado = false, 2000) }">
                                <textarea wire:model.defer="comentariosOnboarding.{{ $solicitud->contratista->id }}" rows="3" class="input-field w-full text-xs" placeholder="Anotaciones generales..."></textarea>
                                <button wire:click="guardarComentarioGeneral({{ $solicitud->contratista->id }})" class="btn-secondary btn-sm mt-1 w-full">
                                    <span wire:loading.remove wire:target="guardarComentarioGeneral({{ $solicitud->contratista->id }})">Guardar Bitácora</span>
                                    <span wire:loading wire:target="guardarComentarioGeneral({{ $solicitud->contratista->id }})">...</span>
                                </button>
                                <span x-show="guardado" x-transition class="text-green-500 text-xs mt-1">¡Guardado!</span>
                            </td>
                        @else
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 align-top">{{ $solicitud->created_at->format('d-m-Y H:i') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 align-top">
                                {{ $solicitud->fecha_aprobacion ? $solicitud->fecha_aprobacion->format('d-m-Y H:i') : 'N/A' }}<br>
                                <small class="text-gray-500">{{ $solicitud->aprobador->name ?? 'Sistema' }}</small>
                            </td>
                        @endif
                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium align-top">
                            @if($solicitud->estado == 'PENDIENTE')
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <a href="{{ route('gestion.contratistas.ver', ['contratistaId' => $solicitud->contratista->id, 'readOnly' => true]) }}" target="_blank" class="btn-secondary btn-sm w-full" title="Ver Ficha">Ver Ficha</a>
                                    <div class="flex items-center justify-center space-x-2 w-full">
                                        <button wire:click="abrirModalAprobacion({{ $solicitud->id }})" class="btn-primary btn-sm flex-1" title="Aprobar Solicitud"><x-icons.check class="inline-block"/> Aprobar</button>
                                        <button wire:click="abrirModalRechazo({{ $solicitud->id }})" class="btn-danger btn-sm flex-1" title="Rechazar Solicitud"><x-icons.x-mark class="inline-block"/> Rechazar</button>
                                    </div>
                                </div>
                            @else
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <a href="{{ route('gestion.contratistas.ver', ['contratistaId' => $solicitud->contratista->id, 'readOnly' => true]) }}" target="_blank" class="btn-secondary btn-sm w-full" title="Ver Ficha">Ver Ficha</a>
                                    <div x-data="{ open: false }" class="relative w-full">
                                        <button @click="open = !open" class="w-full px-2 py-1 text-xs font-semibold rounded-full transition-all {{ $solicitud->estado == 'APROBADA' ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
                                            {{ $solicitud->estado }} <span class="ml-1">▾</span>
                                        </button>
                                        <div x-show="open" @click.away="open = false" x-transition class="absolute z-10 mt-2 w-48 bg-white rounded-md shadow-lg right-0" style="display:none;">
                                            <a href="#" wire:click.prevent="revertirEstado({{ $solicitud->id }}, 'PENDIENTE')" wire:confirm="¿Está seguro de revertir esta solicitud a PENDIENTE? Se eliminarán las vinculaciones a UOs." class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Revertir a Pendiente</a>
                                            <a href="#" wire:click.prevent="revertirEstado({{ $solicitud->id }}, 'RECHAZADA')" wire:confirm="¿Está seguro de revertir esta solicitud a RECHAZADA?" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Revertir a Rechazada</a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ ($filtroEstado === 'APROBADA' && $esAdminAsem) ? 11 : 5 }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">No se encontraron solicitudes con el estado '{{ $filtroEstado }}'.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $solicitudes->links() }}</div>

    @if($showModalAprobacion && $solicitudParaAprobar)
    <x-modal-pro wire:model="showModalAprobacion">
        <x-slot name="title">
            @if($esSubContratista)
                Aprobar Sub-Contratista y Asignar Vinculaciones
            @else
                Aprobar y Vincular Solicitud
            @endif
        </x-slot>
        <x-slot name="content">
            @if($esSubContratista)
                {{-- Modal para SUB-CONTRATISTAS --}}
                <div class="bg-yellow-50 dark:bg-yellow-900/30 p-3 rounded-lg border border-yellow-200 dark:border-yellow-700 mb-4">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <strong>Solicitud de Sub-Contratista</strong>
                    </p>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    La empresa <strong>{{ $solicitudParaAprobar->contratista->razon_social }}</strong> 
                    solicita ser <strong>Sub-Contratista</strong> de <strong>{{ $solicitudParaAprobar->contratistaPadre->razon_social ?? 'N/A' }}</strong>.
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Seleccione las vinculaciones del contratista padre que desea asignar al sub-contratista:
                </p>
                
                <div class="max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg">
                    @forelse($vinculacionesPadreDisponibles as $vinculacion)
                        <label class="flex items-center p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0">
                            <input type="checkbox" 
                                   wire:model.live="vinculacionesSeleccionadas" 
                                   value="{{ $vinculacion->id }}"
                                   class="form-checkbox h-5 w-5 text-indigo-600 rounded focus:ring-indigo-500">
                            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                                <strong>{{ $vinculacion->unidadOrganizacionalMandante->nombre_unidad ?? 'Sin UO' }}</strong>
                                @if($vinculacion->dependencia)
                                    <span class="text-gray-500"> - {{ $vinculacion->dependencia->nombre }}</span>
                                @endif
                                @if($vinculacion->numero_contrato)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 ml-2">
                                        Contrato: {{ $vinculacion->numero_contrato }}
                                    </span>
                                @endif
                                <br>
                                <small class="text-gray-500">Principal: {{ $vinculacion->unidadOrganizacionalMandante->mandante->razon_social ?? 'N/A' }}</small>
                            </span>
                        </label>
                    @empty
                        <div class="p-4 text-center text-gray-500">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            El contratista padre no tiene vinculaciones activas.
                        </div>
                    @endforelse
                </div>
                
                @error('vinculacionesSeleccionadas') 
                    <span class="text-red-500 text-sm mt-2 block">{{ $message }}</span> 
                @enderror
                
                @if(count($vinculacionesSeleccionadas) > 0)
                    <p class="text-sm text-indigo-600 dark:text-indigo-400 mt-2">
                        <strong>{{ count($vinculacionesSeleccionadas) }}</strong> vinculación(es) seleccionada(s)
                    </p>
                @endif
            @else
                {{-- Modal para CONTRATISTAS DIRECTOS --}}
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Está a punto de aprobar a la empresa <strong>{{ $solicitudParaAprobar->contratista->razon_social }}</strong> para la Principal <strong>{{ $solicitudParaAprobar->mandante->razon_social }}</strong>.
                    Para completar el proceso, debe seleccionar la Unidad Operativa (UO) principal a la que será vinculada.
                </p>
                <div>
                    <label for="unidadOrganizacionalSeleccionadaId" class="label-form">Unidad Operativa <span class="text-red-500">*</span></label>
                    <select wire:model="unidadOrganizacionalSeleccionadaId" id="unidadOrganizacionalSeleccionadaId" class="input-field w-full @error('unidadOrganizacionalSeleccionadaId') input-error @enderror">
                        <option value="">-- Seleccione una UO --</option>
                        @foreach($unidadesOrganizacionalesDisponibles as $uo)
                            <option value="{{ $uo->id }}">{{ $uo->nombre_unidad }}</option>
                        @endforeach
                    </select>
                    @error('unidadOrganizacionalSeleccionadaId') <span class="error-message">{{ $message }}</span> @enderror
                </div>
            @endif
        </x-slot>
        <x-slot name="footer">
            <button wire:click="aprobarSolicitud" 
                    class="btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
                    @if($esSubContratista && empty($vinculacionesSeleccionadas)) disabled @endif>
                <span wire:loading.remove wire:target="aprobarSolicitud">
                    @if($esSubContratista)
                        Aprobar y Asignar Vinculaciones
                    @else
                        Aprobar y Vincular
                    @endif
                </span>
                <span wire:loading wire:target="aprobarSolicitud">Procesando...</span>
            </button>
            <button wire:click="cerrarModalAprobacion" class="btn-secondary">Cancelar</button>
        </x-slot>
    </x-modal-pro>
    @endif

    @if($showModalRechazo && $solicitudParaRechazar)
    <x-modal-pro wire:model="showModalRechazo">
        <x-slot name="title">Rechazar Solicitud</x-slot>
        <x-slot name="content">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Está a punto de rechazar la solicitud de <strong>{{ $solicitudParaRechazar->contratista->razon_social }}</strong>.
                Por favor, ingrese un motivo claro para el rechazo.
            </p>
            <div>
                <label for="motivoRechazo" class="label-form">Motivo del Rechazo <span class="text-red-500">*</span></label>
                <textarea wire:model="motivoRechazo" id="motivoRechazo" rows="4" class="input-field w-full @error('motivoRechazo') input-error @enderror"></textarea>
                @error('motivoRechazo') <span class="error-message">{{ $message }}</span> @enderror
            </div>
        </x-slot>
        <x-slot name="footer">
            <button wire:click="rechazarSolicitud" class="btn-danger">Confirmar Rechazo</button>
            <button wire:click="cerrarModalRechazo" class="btn-secondary">Cancelar</button>
        </x-slot>
    </x-modal-pro>
    @endif
</div>