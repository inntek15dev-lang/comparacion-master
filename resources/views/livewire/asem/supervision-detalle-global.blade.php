<div>
    <x-slot name="header">
        {{-- ================== INICIO DE LA MODIFICACIÓN (DIRECTIVA 1) ================== --}}
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            GESTION DE EXCEPCIONES: <span class="text-indigo-600 dark:text-indigo-400">{{ $contratista->razon_social }}</span>
        </h2>
        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            <p><span class="font-semibold">Lugar de Trabajo/Departamento:</span> {{ $lugarDeTrabajo->nombre_jerarquico }}</p>
            <p><span class="font-semibold">Unidad Operativa:</span> {{ $uo->nombre_jerarquico }}</p>
        </div>
        {{-- ================== FIN DE LA MODIFICACIÓN (DIRECTIVA 1) ==================== --}}
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                
                <div class="mb-4">
                    <a href="{{ route('gestion.supervision-global') }}" 
                       class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <x-icons.arrow-left class="w-5 h-5 mr-2"/> Volver a la Vista General
                    </a>
                </div>

                {{-- Pestañas de Navegación --}}
                <div class="mb-6">
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            @php
                                $entidadesPermitidas = $mandante->tiposEntidadControlable->pluck('nombre_entidad')->map(fn($nombre) => strtoupper($nombre));
                            @endphp

                            @if($entidadesPermitidas->contains('EMPRESA'))
                            <button wire:click="seleccionarPestaña('documentos_empresa')" class="{{ $pestañaActiva === 'documentos_empresa' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                Documentos Empresa
                            </button>
                            @endif
                            @if($entidadesPermitidas->contains('PERSONA'))
                            <button wire:click="seleccionarPestaña('trabajadores')" class="{{ $pestañaActiva === 'trabajadores' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                Trabajadores
                            </button>
                            @endif
                            @if($entidadesPermitidas->contains('VEHICULO'))
                            <button wire:click="seleccionarPestaña('vehiculos')" class="{{ $pestañaActiva === 'vehiculos' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                Vehículos
                            </button>
                            @endif
                            @if($entidadesPermitidas->contains('EMBARCACION'))
                            <button wire:click="seleccionarPestaña('embarcaciones')" class="{{ $pestañaActiva === 'embarcaciones' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                Embarcaciones
                            </button>
                            @endif
                            @if($entidadesPermitidas->contains('MAQUINARIA'))
                            <button wire:click="seleccionarPestaña('maquinaria')" class="{{ $pestañaActiva === 'maquinaria' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                Maquinaria
                            </button>
                            @endif
                        </nav>
                    </div>
                </div>

                {{-- Contenido de la Pestaña --}}
                <div>
                    @if ($pestañaActiva === 'documentos_empresa')
                        @php
                            $acceso = $contratista->determinarAccesoHabilitado($mandante->id, $uo->id);
                            $cumplimiento = $contratista->calcularPorcentajeCumplimiento($mandante->id, $uo->id);
                        @endphp
                        <div class="bg-gray-50 dark:bg-gray-700/50 p-6 rounded-lg shadow-inner">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">Estado General de la Empresa</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Supervisión de la documentación a nivel de ficha de empresa.</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-600 dark:text-gray-300">% Cumplimiento</p>
                                    <p class="text-3xl font-bold {{ $cumplimiento < 100 ? 'text-orange-500' : 'text-green-500' }}">{{ $cumplimiento }}%</p>
                                </div>
                            </div>
                            <div class="mt-4 border-t dark:border-gray-600 pt-4 flex justify-between items-center">
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">Estado de Acceso</p>
                                    <span class="px-3 py-1 text-base font-medium rounded-full {{ $acceso['habilitado'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $acceso['motivo'] }}
                                    </span>
                                </div>
                                <div>
                                    {{-- ================== INICIO DE LA MODIFICACIÓN (DIRECTIVA 4) ================== --}}
                                    @if($acceso['es_excepcion'])
                                        <button wire:click="revertirAnulacionManual({{ $contratista->id }}, '{{ addslashes(get_class($contratista)) }}')" 
                                                wire:confirm="¿Está seguro que desea eliminar la anulación manual y volver al estado calculado por el sistema?"
                                                class="btn-secondary">
                                            Revertir a Estado Original
                                        </button>
                                    @else
                                        @if ($acceso['habilitado'])
                                            <button wire:click="abrirModalAnulacion({{ $contratista->id }}, '{{ addslashes(get_class($contratista)) }}', 'RESTRINGIR')" class="btn-danger">
                                                Restringir Acceso Empresa
                                            </button>
                                        @else
                                            <button wire:click="abrirModalAnulacion({{ $contratista->id }}, '{{ addslashes(get_class($contratista)) }}', 'HABILITAR')" class="btn-primary">
                                                Habilitar Acceso Empresa
                                            </button>
                                        @endif
                                    @endif
                                    {{-- ================== FIN DE LA MODIFICACIÓN (DIRECTIVA 4) ==================== --}}
                                </div>
                            </div>
                        </div>
                    @elseif ($pestañaActiva === 'trabajadores')
                        @livewire('asem.supervision.tabla-trabajadores-global', ['contratistaId' => $contratista->id, 'mandanteId' => $mandante->id, 'lugarDeTrabajoId' => $lugarDeTrabajo->id, 'uoId' => $uo->id], key('trab-'.$contratista->id.'-'.$lugarDeTrabajo->id.'-'.$uo->id))
                    @elseif ($pestañaActiva === 'vehiculos')
                        @livewire('asem.supervision.tabla-vehiculos-global', ['contratistaId' => $contratista->id, 'mandanteId' => $mandante->id, 'lugarDeTrabajoId' => $lugarDeTrabajo->id, 'uoId' => $uo->id], key('veh-'.$contratista->id.'-'.$lugarDeTrabajo->id.'-'.$uo->id))
                    @elseif ($pestañaActiva === 'embarcaciones')
                        @livewire('asem.supervision.tabla-embarcaciones-global', ['contratistaId' => $contratista->id, 'mandanteId' => $mandante->id, 'lugarDeTrabajoId' => $lugarDeTrabajo->id, 'uoId' => $uo->id], key('emb-'.$contratista->id.'-'.$lugarDeTrabajo->id.'-'.$uo->id))
                    @elseif ($pestañaActiva === 'maquinaria')
                        @livewire('asem.supervision.tabla-maquinarias-global', ['contratistaId' => $contratista->id, 'mandanteId' => $mandante->id, 'lugarDeTrabajoId' => $lugarDeTrabajo->id, 'uoId' => $uo->id], key('maq-'.$contratista->id.'-'.$lugarDeTrabajo->id.'-'.$uo->id))
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- Modal para Anulación de Acceso --}}
    @if($showAnulacionModal && $recursoSeleccionado)
        <div class="fixed z-50 inset-0 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
                {{-- ================== INICIO DE LA MODIFICACIÓN (DIRECTIVA 3) ================== --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-2xl sm:w-full z-10">
                    <form wire:submit.prevent="guardarAnulacionAcceso">
                        <div class="px-4 pt-5 pb-4 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                @if($accionAnulacion === 'HABILITAR')
                                    Confirmar Habilitación Manual
                                @else
                                    Confirmar Restricción Manual
                                @endif
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Está a punto de anular manualmente el estado de acceso para el recurso: <strong class="font-semibold">{{ $recursoSeleccionado->razon_social ?? $recursoSeleccionado->nombre_completo ?? $recursoSeleccionado->patente_completa ?? $recursoSeleccionado->identificador_completo ?? $recursoSeleccionado->matricula_completa }}</strong>.
                                </p>
                            </div>
                            <div class="mt-4">
                                <label for="justificacion" class="label-form">Justificación (Obligatorio)</label>
                                <textarea id="justificacion" wire:model.defer="justificacion" rows="4" class="input-field w-full"></textarea>
                                @error('justificacion') <span class="error-message">{{ $message }}</span> @enderror
                            </div>
                            <div class="mt-4">
                                <label for="valido_hasta" class="label-form">
                                    @if($accionAnulacion === 'HABILITAR')
                                        Vencimiento de la Habilitación (Opcional)
                                    @else
                                        Vencimiento de la Restricción (Opcional)
                                    @endif
                                </label>
                                <input type="date" id="valido_hasta" wire:model.defer="valido_hasta" class="input-field w-full">
                                @error('valido_hasta') <span class="error-message">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="{{ $accionAnulacion === 'HABILITAR' ? 'btn-primary' : 'btn-danger' }} sm:ml-3">
                                Confirmar
                            </button>
                            <button type="button" wire:click="cerrarModalAnulacion" class="btn-secondary">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
                {{-- ================== FIN DE LA MODIFICACIÓN (DIRECTIVA 3) ==================== --}}
            </div>
        </div>
    @endif
</div>