<div class="min-h-screen bg-gradient-to-br from-white to-blue-50 dark:from-gray-900 dark:to-gray-800">
    <div class="py-8">
        <div class="max-w-[140rem] mx-auto sm:px-6 lg:px-8"> 
            <div class="bg-white dark:bg-gray-800 shadow-2xl rounded-2xl border-2 border-blue-300 dark:border-gray-600 overflow-hidden ring-4 ring-blue-100 dark:ring-gray-700">
                <div class="p-8">
                    <div class="mb-8 bg-gradient-to-r from-blue-100 to-blue-50 dark:from-gray-700 dark:to-gray-800 p-6 rounded-xl border-2 border-blue-400 dark:border-gray-600 shadow-lg">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 items-end">
                            
                            @if(!$mandanteIdForzado)
                            <div>
                                <div class="flex items-center mb-2">
                                    <svg class="w-6 h-6 text-purple-800 dark:text-purple-300 mr-3 drop-shadow-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" /></svg>
                                    <label for="mandante_seleccionado_id" class="text-lg font-bold text-purple-900 dark:text-purple-200 drop-shadow-sm">
                                        1. Filtre por Principal
                                    </label>
                                </div>
                                <select wire:model.live="mandanteSeleccionadoId" id="mandante_seleccionado_id" 
                                        class="w-full px-4 py-3 text-base border-2 border-purple-400 bg-white dark:bg-gray-700 text-purple-900 dark:text-purple-200 focus:outline-none focus:ring-4 focus:ring-purple-200 dark:focus:ring-purple-900 focus:border-purple-700 rounded-lg shadow-md transition-all duration-200 hover:border-purple-600 hover:shadow-lg font-medium">
                                    <option value="">-- Todos sus Principales --</option>
                                    @foreach ($mandantesDisponibles as $mandante)
                                        <option value="{{ $mandante->id }}" class="text-purple-900 dark:text-purple-100 dark:bg-gray-800 font-medium">{{ $mandante->razon_social }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            {{-- FILTRO CONTRATISTA (SOLO PARA MANDANTE) --}}
                            @if(!$contratistaIdForzado)
                            <div>
                                <div class="flex items-center mb-2">
                                    <svg class="w-6 h-6 text-blue-800 dark:text-blue-300 mr-3 drop-shadow-sm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                    </svg>
                                    <label for="contratista_seleccionado_id" class="text-lg font-bold text-blue-900 dark:text-blue-200 drop-shadow-sm">
                                        1. Seleccione Contratista
                                    </label>
                                </div>
                                <select wire:model.live="contratistaSeleccionadoId" id="contratista_seleccionado_id" 
                                        class="w-full px-4 py-3 text-base border-2 border-blue-400 bg-white dark:bg-gray-700 text-blue-900 dark:text-blue-200 focus:outline-none focus:ring-4 focus:ring-blue-200 dark:focus:ring-blue-900 focus:border-blue-700 rounded-lg shadow-md transition-all duration-200 hover:border-blue-600 hover:shadow-lg font-medium">
                                    <option value="">-- Seleccione un Contratista --</option>
                                    @foreach($contratistasDisponibles as $contratista)
                                        <option value="{{ $contratista->id }}">
                                            {{ $contratista->razon_social }} ({{ $contratista->rut }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            
                            {{-- FILTRO SUB-CONTRATISTA (Para Contratista que gestiona sus Subs) --}}
                            {{-- Ocultar cuando se accede desde ASEM/Mandante (donde ya hay filtro externo de sub-contratista) --}}
                            @if(!$contratistaIdForzado && (!empty($subContratistasDisponibles) || ($contratistaActual && (($contratistaSeleccionadoId && $contratistaActual->id != $contratistaSeleccionadoId) || (Auth::user()->contratista_id && $contratistaActual->id != Auth::user()->contratista_id)))))
                            <div>
                                <div class="flex items-center mb-2 justify-between">
                                    <div class="flex items-center">
                                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-300 mr-3 drop-shadow-sm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                                        </svg>
                                        <label for="selectedSubContratistaId" class="text-lg font-bold text-indigo-900 dark:text-indigo-200 drop-shadow-sm">
                                            {{ !$mandanteIdForzado && !$contratistaIdForzado ? '3. ' : (!$contratistaIdForzado ? '2. ' : '1. ') }} Sub-Contratista (Opcional)
                                        </label>
                                    </div>

                                    @if(($contratistaSeleccionadoId && $contratistaActual->id != $contratistaSeleccionadoId) || (Auth::user()->contratista_id && $contratistaActual->id != Auth::user()->contratista_id))
                                        <button wire:click="volverAlContratistaOriginal" 
                                                class="text-xs bg-indigo-100 hover:bg-indigo-200 text-indigo-700 font-bold py-1 px-2 rounded border border-indigo-300 transition-colors flex items-center shadow-sm">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                                            Volver al Inicio
                                        </button>
                                    @endif
                                </div>
                                <select wire:model.live="selectedSubContratistaId" id="selectedSubContratistaId" 
                                        class="w-full px-4 py-3 text-base border-2 border-indigo-400 bg-white dark:bg-gray-700 text-indigo-900 dark:text-indigo-200 focus:outline-none focus:ring-4 focus:ring-indigo-200 dark:focus:ring-indigo-900 focus:border-indigo-700 rounded-lg shadow-md transition-all duration-200 hover:border-indigo-600 hover:shadow-lg font-medium">
                                    <option value="">-- {{ $contratistaActual->razon_social }} (Gestión Propia) --</option>
                                    @foreach($subContratistasDisponibles as $sub)
                                        <option value="{{ $sub['id'] }}">
                                            {{ $sub['razon_social'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="mt-1 text-xs text-indigo-600 dark:text-indigo-400 italic">
                                    * Seleccione para gestionar entidades de este subcontratista
                                </div>
                            </div>
                            @endif

                            <div>
                                <div class="flex items-center mb-2">
                                    <svg class="w-6 h-6 text-blue-800 dark:text-blue-300 mr-3 drop-shadow-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                    </svg>
                                    <label for="lugar_de_trabajo_seleccionado" class="text-lg font-bold text-blue-900 dark:text-blue-200 drop-shadow-sm">
                                        {{ !$mandanteIdForzado ? '2.' : '1.' }} Filtre por Lugar de Trabajo/Departamento
                                    </label>
                                </div>
                                <select wire:model.live="lugarDeTrabajoSeleccionado" id="lugar_de_trabajo_seleccionado" 
                                        class="w-full px-4 py-3 text-base border-2 border-blue-400 bg-white dark:bg-gray-700 text-blue-900 dark:text-blue-200 focus:outline-none focus:ring-4 focus:ring-blue-200 dark:focus:ring-blue-900 focus:border-blue-700 rounded-lg shadow-md transition-all duration-200 hover:border-blue-600 hover:shadow-lg font-medium">
                                    <option value="">-- Todos los Lugares de Trabajo --</option>
                                    
                                    @if($pestañaActiva === 'vehiculos')
                                        @if($existenVehiculosEnReserva) <option value="in_reserve" class="text-gray-700 font-bold bg-gray-100">-- VER VEHÍCULOS EN RESERVA --</option> @endif
                                        @if($existenVehiculosHuerfanos) <option value="orphaned" class="text-red-700 font-bold bg-red-50">-- VER VEHÍCULOS EN LUGARES REVOCADOS --</option> @endif
                                    @elseif($pestañaActiva === 'maquinaria')
                                        @if($existenMaquinariasEnReserva) <option value="in_reserve" class="text-gray-700 font-bold bg-gray-100">-- VER MAQUINARIAS EN RESERVA --</option> @endif
                                        @if($existenMaquinariasHuerfanas) <option value="orphaned" class="text-red-700 font-bold bg-red-50">-- VER MAQUINARIAS EN LUGARES REVOCADOS --</option> @endif
                                    @elseif($pestañaActiva === 'embarcaciones')
                                        @if($existenEmbarcacionesEnReserva) <option value="in_reserve" class="text-gray-700 font-bold bg-gray-100">-- VER EMBARCACIONES EN RESERVA --</option> @endif
                                        @if($existenEmbarcacionesHuerfanas) <option value="orphaned" class="text-red-700 font-bold bg-red-50">-- VER EMBARCACIONES EN LUGARES REVOCADOS --</option> @endif
                                    @else
                                        @if($existenTrabajadoresEnReserva) <option value="in_reserve" class="text-gray-700 font-bold bg-gray-100">-- VER TRABAJORES EN RESERVA --</option> @endif
                                        @if($existenTrabajadoresHuerfanos) <option value="orphaned" class="text-red-700 font-bold bg-red-50">-- VER TRABAJORES EN LUGARES REVOCADOS --</option> @endif
                                    @endif

                                    @if($lugaresDeTrabajoDisponibles)
                                        @foreach ($lugaresDeTrabajoDisponibles as $lugar)
                                            <option value="{{ $lugar->id }}" class="text-blue-900 dark:text-blue-100 dark:bg-gray-800 font-medium">{{ $lugar->nombre_jerarquico }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div>
                                <div class="flex items-center mb-2">
                                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-300 mr-3 drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    <label for="vinculacion_seleccionada" class="text-lg font-bold text-gray-700 dark:text-gray-200 drop-shadow-sm">
                                        {{ !$mandanteIdForzado ? '3.' : '2.' }} (Opcional) Filtre por U.O.
                                    </label>
                                </div>
                                <select wire:model.live="vinculacionSeleccionada" id="vinculacion_seleccionada" 
                                        class="w-full px-4 py-3 text-base border-2 border-gray-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-600 focus:border-gray-700 rounded-lg shadow-md transition-all duration-200 hover:border-gray-600 hover:shadow-lg font-medium">
                                    <option value="">-- Todas las Unidades Operativas --</option>
                                    @if($vinculacionesDisponibles)
                                        @foreach ($vinculacionesDisponibles as $v)
                                            <option value="{{ $v['id_seleccion'] }}" class="text-gray-900 dark:text-gray-100 dark:bg-gray-800 font-medium">{{ $v['texto_visible'] }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            {{-- FILTRO N° CONTRATO (Select desplegable) --}}
                            <div>
                                <div class="flex items-center mb-2">
                                    <svg class="w-6 h-6 text-green-600 dark:text-green-300 mr-3 drop-shadow-sm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                    <label for="filtro_numero_contrato" class="text-lg font-bold text-green-700 dark:text-green-200 drop-shadow-sm">
                                        {{ !$mandanteIdForzado ? '4.' : '3.' }} (Opcional) N° Contrato
                                    </label>
                                </div>
                                <select wire:model.live="filtroNumeroContrato" id="filtro_numero_contrato" 
                                        class="w-full px-4 py-3 text-base border-2 border-green-400 bg-white dark:bg-gray-700 text-green-900 dark:text-green-200 focus:outline-none focus:ring-4 focus:ring-green-200 dark:focus:ring-green-900 focus:border-green-700 rounded-lg shadow-md transition-all duration-200 hover:border-green-600 hover:shadow-lg font-medium">
                                    <option value="">-- Todos los Contratos --</option>
                                    <option value="sin_contrato" class="text-green-900 dark:text-green-100 dark:bg-gray-800 font-bold">-- Sin Contrato --</option>
                                    @foreach ($contratosDisponibles as $contrato)
                                        <option value="{{ $contrato }}" class="text-green-900 dark:text-green-100 dark:bg-gray-800 font-medium">{{ $contrato }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    @if ($mandanteContextoId || !$lugarDeTrabajoSeleccionado || $lugarDeTrabajoSeleccionado === 'orphaned' || $lugarDeTrabajoSeleccionado === 'in_reserve')
                        <div class="mb-6">
                            <div class="border-b-2 border-blue-300 dark:border-gray-600 bg-gradient-to-r from-blue-100 to-blue-50 dark:from-gray-700 dark:to-gray-800 rounded-t-lg shadow-lg">
                                <nav class="flex space-x-2 px-4 py-2 overflow-x-auto" aria-label="Tabs">
                                    
                                    {{-- PESTAÑA EMPRESA (ICONO EDIFICIO/FÁBRICA) --}}
                                    @if (in_array('EMPRESA', $tiposEntidadPermitidosContextoActual))
                                    <button wire:click="seleccionarPestaña('empresa')"
                                            class="flex items-center px-6 py-3 text-sm font-bold rounded-t-lg transition-all duration-200 shadow-md {{ $pestañaActiva === 'empresa' ? 'bg-blue-800 text-white border-b-2 border-blue-800 shadow-xl transform -translate-y-1' : 'text-blue-800 dark:text-blue-200 hover:text-blue-900 dark:hover:text-white hover:bg-blue-200 dark:hover:bg-gray-600 border-b-2 border-transparent hover:shadow-lg' }}">
                                        {{-- Icono Edificio Industrial --}}
                                        <svg class="w-9 h-9 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M4 22V8h4V4h12v18H4zm2-2h2v-2H6v2zm0-4h2v-2H6v2zm0-4h2V8H6v4zm4 8h2v-2h-2v2zm0-4h2v-2h-2v2zm0-4h2V8h-2v4zm4 8h2v-2h-2v2zm0-4h2v-2h-2v2zm0-4h2V8h-2v4zm4 8h2v-2h-2v2zm0-4h2v-2h-2v2zm0-4h2V8h-2v4z"/>
                                        </svg>
                                        Empresa
                                    </button>
                                    @endif

                                    {{-- PESTAÑA TRABAJADORES (ICONO CASCO NUEVO) --}}
                                    @if (in_array('PERSONA', $tiposEntidadPermitidosContextoActual))
                                    <button wire:click="seleccionarPestaña('trabajadores')"
                                            class="flex items-center px-6 py-3 text-sm font-bold rounded-t-lg transition-all duration-200 shadow-md {{ $pestañaActiva === 'trabajadores' ? 'bg-blue-800 text-white border-b-2 border-blue-800 shadow-xl transform -translate-y-1' : 'text-blue-800 dark:text-blue-200 hover:text-blue-900 dark:hover:text-white hover:bg-blue-200 dark:hover:bg-gray-600 border-b-2 border-transparent hover:shadow-lg' }}">
                                        <img src="{{ asset('images/casco2.png') }}?v=2" alt="Trabajadores" class="w-9 h-9 mr-2 object-contain">
                                        Trabajadores
                                    </button>
                                    @endif
                                    
                                    {{-- PESTAÑA VEHÍCULOS (ICONO AUTO ROJO - MANTENIDO) --}}
                                    @if (in_array('VEHICULO', $tiposEntidadPermitidosContextoActual))
                                    <button wire:click="seleccionarPestaña('vehiculos')"
                                            class="flex items-center px-6 py-3 text-sm font-bold rounded-t-lg transition-all duration-200 shadow-md {{ $pestañaActiva === 'vehiculos' ? 'bg-blue-800 text-white border-b-2 border-blue-800 shadow-xl transform -translate-y-1' : 'text-blue-800 dark:text-blue-200 hover:text-blue-900 dark:hover:text-white hover:bg-blue-200 dark:hover:bg-gray-600 border-b-2 border-transparent hover:shadow-lg' }}">
                                        <svg class="w-9 h-9 mr-2" viewBox="0 0 24 24" fill="#EF4444"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
                                        Vehículos
                                    </button>
                                    @endif
                                    
                                    {{-- PESTAÑA MAQUINARIA (ICONO GRÚA TORRE) --}}
                                    @if (in_array('MAQUINARIA', $tiposEntidadPermitidosContextoActual))
                                    <button wire:click="seleccionarPestaña('maquinaria')"
                                            class="flex items-center px-6 py-3 text-sm font-bold rounded-t-lg transition-all duration-200 shadow-md {{ $pestañaActiva === 'maquinaria' ? 'bg-blue-800 text-white border-b-2 border-blue-800 shadow-xl transform -translate-y-1' : 'text-blue-800 dark:text-blue-200 hover:text-blue-900 dark:hover:text-white hover:bg-blue-200 dark:hover:bg-gray-600 border-b-2 border-transparent hover:shadow-lg' }}">
                                        {{-- Icono Grúa Torre --}}
                                        <svg class="w-9 h-9 mr-2" viewBox="0 0 24 24">
                                            <path fill="#FBBF24" d="M8 6h11v2H8V6zm11 2V6l3-3h-2l-2 2-2-2h-2l3 3v2h2zM4 22V10h2v12H4zm2-12V8H4v2h2zm0 0h2v2H6v-2zm0 4h2v2H6v-2zm0 4h2v2H6v-2z"/>
                                            <path fill="#FBBF24" d="M18 10h2v4h-2v-4zm0 4h2l-1 2-1-2z"/>
                                            <rect x="4" y="20" width="6" height="2" fill="black"/>
                                        </svg>
                                        Maquinaria
                                    </button>
                                    @endif
                                    
                                    {{-- PESTAÑA EMBARCACIONES (ICONO BARCO CARGUERO) --}}
                                    @if (in_array('EMBARCACION', $tiposEntidadPermitidosContextoActual))
                                    <button wire:click="seleccionarPestaña('embarcaciones')"
                                            class="flex items-center px-6 py-3 text-sm font-bold rounded-t-lg transition-all duration-200 shadow-md {{ $pestañaActiva === 'embarcaciones' ? 'bg-blue-800 text-white border-b-2 border-blue-800 shadow-xl transform -translate-y-1' : 'text-blue-800 dark:text-blue-200 hover:text-blue-900 dark:hover:text-white hover:bg-blue-200 dark:hover:bg-gray-600 border-b-2 border-transparent hover:shadow-lg' }}">
                                        {{-- Icono Barco Carguero --}}
                                        <svg class="w-9 h-9 mr-2" viewBox="0 0 24 24">
                                            <path fill="#10B981" d="M3 14l2 6h14l2-6H3zm1-2h3v-3H4v3zm5 0h3v-3H9v3zm5 0h3v-3h-3v3zm-2-5h2v-4h-2v4z"/>
                                            <path fill="black" d="M12 2v5h2V2h-2z"/>
                                        </svg>
                                        Embarcaciones
                                    </button>
                                    @endif
                                    
                                    {{-- CARGA FLASH: Solo visible si el CUO acredita o el contexto es Admin/Mandante --}}
                                    @if($cuoAcredita || !$contratistaIdForzado)
                                    <button wire:click="seleccionarPestaña('carga_flash')"
                                            class="flex items-center px-6 py-3 text-sm font-bold rounded-t-lg transition-all duration-200 shadow-md {{ $pestañaActiva === 'carga_flash' ? 'bg-orange-600 text-white border-b-2 border-orange-600 shadow-xl transform -translate-y-1' : 'text-orange-600 hover:text-orange-700 hover:bg-orange-200 border-b-2 border-transparent hover:shadow-lg' }}">
                                        <svg class="w-9 h-9 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" /></svg>
                                        Carga Flash
                                    </button>
                                    @endif

                                </nav>
                            </div>
                        </div>

                        @if(!empty($documentosMaestros))
                            <div class="mb-6 p-4 bg-gray-800 text-white rounded-lg shadow-lg">
                                <div class="flex flex-wrap items-center text-sm">
                                    <span class="font-bold text-base mr-4">Simbología:</span>
                                    <span class="inline-flex items-center mr-4 my-1">
                                        <svg class="w-4 h-4 mr-1 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        = Aprobado
                                    </span>
                                    <span class="inline-flex items-center mr-4 my-1"><strong class="text-red-400 mr-1">R</strong> = Rechazado</span>
                                    <span class="inline-flex items-center mr-4 my-1"><strong class="text-orange-400 mr-1">V</strong> = Vencido</span>
                                    <span class="inline-flex items-center mr-4 my-1"><strong class="text-blue-400 mr-1">P</strong> = Pendiente Validación</span>
                                    <span class="inline-flex items-center mr-4 my-1"><strong class="text-purple-400 mr-1">ER</strong> = En Revisión</span>
                                    <span class="inline-flex items-center mr-4 my-1"><strong class="text-gray-400 mr-1">-</strong> = No Cargado</span>
                                    <span class="inline-flex items-center mr-4 my-1"><strong class="text-gray-400 mr-1">N/A</strong> = No Le Aplica</span>
                                </div>
                                <div class="border-t border-gray-600 pt-4 mt-4">
                                    <button wire:click="toggleGlosario" class="flex items-center justify-between w-full text-left">
                                        <span class="font-bold text-base">Glosario de Documentos</span>
                                        <x-icons.chevron-down class="w-5 h-5 transition-transform duration-200 {{ $showGlosario ? 'rotate-180' : '' }}" />
                                    </button>
                                    @if($showGlosario)
                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-8 gap-y-2 mt-2 text-sm animate-fade-in">
                                        @foreach($glosarioDocumentos as $doc)
                                            <div><span class="font-bold">{{ $doc['numero'] }}.</span> {{ $doc['nombre'] }}</div>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="relative min-h-[300px]">
                            
                            <div wire:loading wire:target="seleccionarPestaña, mandanteSeleccionadoId, lugarDeTrabajoSeleccionado, vinculacionSeleccionada" 
                                 class="absolute inset-0 bg-white dark:bg-gray-800 bg-opacity-75 dark:bg-opacity-90 flex items-center justify-center z-10 rounded-lg">
                                <div class="flex items-center">
                                    <div class="animate-spin rounded-full h-20 w-20 border-t-4 border-b-4 border-blue-600"></div>
                                    <span class="ml-6 text-2xl font-bold text-blue-800 drop-shadow">TRABAJANDO PARA USTED...</span>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-lg">
                                @if ($pestañaActiva === 'empresa')
                                    <div class="bg-gradient-to-r from-blue-100 to-blue-50 dark:from-gray-800 dark:to-gray-900 p-6 rounded-xl border-2 border-blue-400 dark:border-gray-600 shadow-lg">
                                        @livewire('contratista.gestion-empresa-contratista', ['contratistaIdForzado' => $contratistaActual->id, 'mandanteId' => $mandanteContextoId, 'unidadOrganizacionalId' => $unidadOrganizacionalContextoId, 'lugarDeTrabajoId' => $lugarDeTrabajoSeleccionado, 'documentosMaestros' => $documentosMaestros, 'sinAcreditacion' => ($contratistaIdForzado && !$cuoAcredita)], key('empresa-' . $force_remount_key))
                                    </div>
                                @elseif ($pestañaActiva === 'trabajadores')
                                    <div class="bg-gradient-to-r from-blue-100 to-blue-50 dark:from-gray-800 dark:to-gray-900 p-6 rounded-xl border-2 border-blue-400 dark:border-gray-600 shadow-lg">
                                        @livewire('contratista.gestion-trabajadores-contratista', ['mandanteId' => $mandanteContextoId, 'unidadOrganizacionalId' => $unidadOrganizacionalContextoId, 'lugarDeTrabajoId' => empty($lugarDeTrabajoSeleccionado) ? null : $lugarDeTrabajoSeleccionado, 'contratistaIdForzado' => $contratistaActual->id, 'documentosMaestros' => $documentosMaestros, 'numeroContrato' => $numeroContratoContexto, 'sinAcreditacion' => ($contratistaIdForzado && !$cuoAcredita)], key('trabajadores-' . $force_remount_key))
                                    </div>
                                @elseif ($pestañaActiva === 'vehiculos')
                                    <div class="bg-gradient-to-r from-blue-100 to-blue-50 dark:from-gray-800 dark:to-gray-900 p-6 rounded-xl border-2 border-blue-400 dark:border-gray-600 shadow-lg">
                                        @livewire('contratista.gestion-vehiculos-contratista', ['mandanteId' => $mandanteContextoId, 'unidadOrganizacionalId' => $unidadOrganizacionalContextoId, 'lugarDeTrabajoId' => empty($lugarDeTrabajoSeleccionado) ? null : $lugarDeTrabajoSeleccionado, 'contratistaIdForzado' => $contratistaActual->id, 'documentosMaestros' => $documentosMaestros], key('vehiculos-' . $force_remount_key))
                                    </div>
                                @elseif ($pestañaActiva === 'maquinaria')
                                    <div class="bg-gradient-to-r from-blue-100 to-blue-50 dark:from-gray-800 dark:to-gray-900 p-6 rounded-xl border-2 border-blue-400 dark:border-gray-600 shadow-lg">
                                        @livewire('contratista.gestion-maquinaria-contratista', ['mandanteId' => $mandanteContextoId, 'unidadOrganizacionalId' => $unidadOrganizacionalContextoId, 'lugarDeTrabajoId' => empty($lugarDeTrabajoSeleccionado) ? null : $lugarDeTrabajoSeleccionado, 'contratistaIdForzado' => $contratistaActual->id, 'documentosMaestros' => $documentosMaestros], key('maquinaria-' . $force_remount_key))
                                    </div>
                                @elseif ($pestañaActiva === 'embarcaciones')
                                    <div class="bg-gradient-to-r from-blue-100 to-blue-50 dark:from-gray-800 dark:to-gray-900 p-6 rounded-xl border-2 border-blue-400 dark:border-gray-600 shadow-lg">
                                        @livewire('contratista.gestion-embarcaciones-contratista', ['mandanteId' => $mandanteContextoId, 'unidadOrganizacionalId' => $unidadOrganizacionalContextoId, 'lugarDeTrabajoId' => empty($lugarDeTrabajoSeleccionado) ? null : $lugarDeTrabajoSeleccionado, 'contratistaIdForzado' => $contratistaActual->id, 'documentosMaestros' => $documentosMaestros], key('embarcaciones-' . $force_remount_key))
                                    </div>
                                @elseif ($pestañaActiva === 'carga_flash')
                                    <div>
                                        @livewire('contratista.carga-flash-contratista', ['contratistaIdForzado' => $contratistaActual->id, 'mandanteId' => $mandanteContextoId, 'unidadOrganizacionalId' => $unidadOrganizacionalContextoId, 'lugarDeTrabajoId' => $lugarDeTrabajoSeleccionado], key('carga-flash-' . $force_remount_key))
                                    </div>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="text-center py-16">
                            <div class="max-w-md mx-auto bg-gradient-to-br from-blue-100 to-blue-50 p-8 rounded-2xl border-2 border-blue-400 shadow-2xl">
                                <svg class="mx-auto h-24 w-24 text-blue-700 mb-6 drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                <h3 class="text-xl font-bold text-blue-900 mb-3 drop-shadow-sm">Seleccione un Filtro</h3>
                                <p class="text-blue-800 font-medium">
                                    Por favor, seleccione un filtro en los selectores superiores para comenzar.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($contratistaActual)
        @livewire('contratista.modal-gestion-documentos-recurso', ['contratistaIdForzado' => $contratistaActual->id])
    @endif
</div>