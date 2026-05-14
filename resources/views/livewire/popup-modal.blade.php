<div>
@if($mostrarPopup && $popupActivo)
<div 
    x-data="{ show: true }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[100] overflow-y-auto" 
    aria-labelledby="popup-modal-title" 
    role="dialog" 
    aria-modal="true">
    
    {{-- Overlay --}}
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-900/80 backdrop-blur-sm" aria-hidden="true"></div>
        
        {{-- Modal Content --}}
        <div 
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            class="relative inline-block overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-gray-800 rounded-2xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            
            {{-- Header decorativo --}}
            <div class="h-2 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>
            
            {{-- Botón cerrar (solo si no requiere aceptación o ya aceptó) --}}
            @if(!$popupActivo->requiere_aceptacion || $aceptoCondiciones)
            <button 
                wire:click="cerrarPopup" 
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors z-10 p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            @endif
            
            {{-- Contenido --}}
            <div class="px-6 py-6 sm:px-8">
                {{-- Ícono --}}
                <div class="flex items-center justify-center w-14 h-14 mx-auto bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/50 dark:to-purple-900/50 rounded-full mb-4">
                    <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                    </svg>
                </div>
                
                {{-- Título --}}
                <h3 id="popup-modal-title" class="text-xl font-bold text-center text-gray-900 dark:text-white mb-4">
                    {{ $popupActivo->titulo }}
                </h3>
                
                {{-- Contenido del popup --}}
                <div class="prose prose-sm dark:prose-invert max-w-none text-gray-600 dark:text-gray-300 text-center leading-relaxed max-h-60 overflow-y-auto">
                    {!! nl2br(e($popupActivo->contenido_mostrar)) !!}
                </div>
                
                {{-- Contador de visualizaciones --}}
                @if($maxVisualizaciones > 0)
                <div class="mt-4 flex justify-center">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                        </svg>
                        Vista {{ $visualizacionActual }} de {{ $maxVisualizaciones }}
                    </span>
                </div>
                @endif
                
                {{-- Checkbox de aceptación --}}
                @if($popupActivo->requiere_aceptacion)
                <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
                    <label class="flex items-start cursor-pointer group">
                        <input 
                            type="checkbox" 
                            wire:model.live="aceptoCondiciones"
                            class="mt-1 rounded border-amber-300 text-amber-600 focus:ring-amber-500 cursor-pointer">
                        <span class="ml-3 text-sm text-amber-800 dark:text-amber-200 group-hover:text-amber-900 dark:group-hover:text-amber-100">
                            {{ $popupActivo->texto_aceptacion ?? 'Acepto los términos y condiciones' }}
                        </span>
                    </label>
                </div>
                @endif
            </div>
            
            {{-- Footer --}}
            <div class="px-6 py-4 sm:px-8 bg-gray-50 dark:bg-gray-700/50">
                @if($popupActivo->tipo_interaccion === 'requiere_click')
                    <button 
                        wire:click="accionClick"
                        @if($popupActivo->requiere_aceptacion && !$aceptoCondiciones) disabled @endif
                        class="w-full px-6 py-3 text-base font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-lg hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-indigo-600 disabled:hover:to-purple-600">
                        @if($popupActivo->url_destino)
                            <span class="flex items-center justify-center">
                                Ir al enlace
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </span>
                        @else
                            Entendido
                        @endif
                    </button>
                    @if($popupActivo->requiere_aceptacion && !$aceptoCondiciones)
                    <p class="mt-2 text-xs text-center text-amber-600 dark:text-amber-400">
                        Debe aceptar los términos para continuar
                    </p>
                    @endif
                @else
                    <button 
                        wire:click="cerrarPopup"
                        @if($popupActivo->requiere_aceptacion && !$aceptoCondiciones) disabled @endif
                        class="w-full px-6 py-3 text-base font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                        Cerrar
                    </button>
                    @if($popupActivo->requiere_aceptacion && !$aceptoCondiciones)
                    <p class="mt-2 text-xs text-center text-amber-600 dark:text-amber-400">
                        Debe aceptar los términos para cerrar
                    </p>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endif
</div>
