<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            GESTION DE ENTIDADES
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-[120rem] mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                
                {{-- SELECTORES EXTERNOS --}}
                <div class="mb-8 bg-gray-100 dark:bg-gray-700 p-6 rounded-lg border border-gray-300 dark:border-gray-600 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="selectedContratistaId" class="block text-lg font-medium text-gray-900 dark:text-gray-200 mb-2">
                            1. Seleccione Contratista:
                        </label>
                        <select wire:model.live="selectedContratistaId" wire:change="cargarSubs" id="selectedContratistaId" class="input-field w-full text-base border-2 border-gray-300 dark:border-gray-500">
                            <option value="">-- Seleccione un Contratista --</option>
                            @foreach($contratistasDisponibles as $contratista)
                                <option value="{{ $contratista->id }}">
                                    {{ $contratista->razon_social }} ({{ $contratista->rut }})
                                </option>
                            @endforeach
                        </select>
                        {{-- Botón manual eliminado tras fix automático --}}
                        {{-- DEBUG VISIBLE --}}
                        <div class="text-xs text-red-500 mt-1" style="display:none">
                           Cambio detectado. ID: {{ $selectedContratistaId }}
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="selectedSubContratistaId" class="block text-lg font-medium text-gray-900 dark:text-gray-200">
                                2. Sub-Contratista (Opcional) <span class="text-xs text-gray-400">(Subs: {{ count($subContratistasDisponibles) }} | ID: {{ $debugPadreId }})</span>:
                            </label>
                            @if($selectedSubContratistaId)
                                <button wire:click="$set('selectedSubContratistaId', '')" 
                                        class="text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 font-bold py-1 px-2 rounded border border-blue-300 transition-colors flex items-center shadow-sm">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                                    Volver al Contratista
                                </button>
                            @endif
                        </div>
                        <select wire:model.change="selectedSubContratistaId" id="selectedSubContratistaId" class="input-field w-full text-base border-2 border-gray-300 dark:border-gray-500" @if(empty($subContratistasDisponibles)) disabled @endif>
                            <option value="">-- Seleccione un Sub-Contratista --</option>
                            @foreach($subContratistasDisponibles as $sub)
                                <option value="{{ $sub['id'] }}">
                                    {{ $sub['razon_social'] }} ({{ $sub['rut'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- CONTENEDOR DEL PANEL OPERATIVO --}}
                <div class="relative min-h-[300px]">
                    <!-- Overlay de Carga -->
                    <div wire:loading wire:target="selectedContratistaId,selectedSubContratistaId" 
                         class="absolute inset-0 bg-white dark:bg-gray-800 bg-opacity-75 dark:bg-opacity-90 flex items-center justify-center z-10 rounded-lg">
                        <div class="flex items-center">
                            <div class="animate-spin rounded-full h-20 w-20 border-t-4 border-b-4 border-blue-600"></div>
                            <span class="ml-6 text-2xl font-bold text-blue-800 drop-shadow">CARGANDO PANEL...</span>
                        </div>
                    </div>

                    @if($selectedContratistaId)
                        @livewire('contratista.panel-operacion', [
                            'contratistaIdForzado' => $selectedSubContratistaId ?: $selectedContratistaId,
                            'mandanteIdForzado' => $selectedMandanteId,
                            'esSoloLectura' => $esSoloLectura,
                            'preselectedLugar' => $preselectedLugar,
                            'preselectedUo' => $preselectedUo,
                            'preselectedContrato' => $preselectedContrato
                        ], key('panel-op-mandante-' . ($selectedSubContratistaId ?: $selectedContratistaId) . '-' . $selectedMandanteId . '-' . ($preselectedLugar ?? 'noplace')))
                    @else
                         <div class="text-center py-16">
                            <div class="max-w-md mx-auto bg-gray-50 dark:bg-gray-700/50 p-8 rounded-lg border border-gray-200 dark:border-gray-600">
                                <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300">Selección Requerida</h3>
                                <p class="mt-2 text-gray-500 dark:text-gray-400">
                                    Por favor, seleccione un Contratista para ver su información operativa.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>