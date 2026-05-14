<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight sm:text-4xl">Control de Acceso Terreno</h1>
        <p class="mt-2 text-sm text-gray-500">Ingrese RUT (Ej: 12345678-9) o Patente (Ej: ABCD12)</p>
    </div>

    <!-- Filtros de Contexto (Mandante y Dependencia) -->
    <div class="bg-gray-50 rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6 mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Principal (Mandante)</label>
            <select wire:model.live="mandanteSeleccionadoId" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                <option value="">-- Seleccione Principal --</option>
                @foreach($mandantesDisponibles as $mandante)
                    <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Lugar de Trabajo (Dependencia)</label>
            <select wire:model.live="dependenciaSeleccionadaId" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm" @if(empty($mandanteSeleccionadoId)) disabled @endif>
                <option value="">-- Todas las Dependencias --</option>
                @foreach($dependenciasDisponibles as $dep)
                    <option value="{{ $dep->id }}">{{ $dep->nombre_jerarquico ?? $dep->nombre }}</option>
                @endforeach
            </select>
            @if(empty($mandanteSeleccionadoId))
                <p class="mt-1 text-xs text-gray-400">Seleccione un principal primero.</p>
            @endif
        </div>
    </div>

    <!-- Buscador -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6 mb-8">
        <form wire:submit.prevent="buscar" class="relative flex items-center">
            <input 
                type="text" 
                wire:model="searchTerm" 
                class="block w-full rounded-l-lg border-gray-300 pl-4 pr-12 py-4 text-xl sm:text-2xl focus:border-green-500 focus:ring-green-500 uppercase font-mono shadow-inner"
                placeholder="Escanee o escriba aquí..."
                autofocus
            >
            <button type="submit" class="absolute right-0 inset-y-0 px-6 bg-green-600 hover:bg-green-700 text-white rounded-r-lg font-bold text-lg transition duration-150 ease-in-out flex items-center justify-center">
                <span wire:loading.remove wire:target="buscar">Buscar</span>
                <svg wire:loading wire:target="buscar" class="animate-spin h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
        </form>
    </div>

    <!-- Errores -->
    @if($mensajeError)
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-8 rounded-r-lg shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ $mensajeError }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Resultados -->
    @if($entidadEncontrada && empty($mensajeError))
        <!-- Encabezado Entidad -->
        <div class="bg-gray-800 text-white rounded-t-xl p-6 shadow-md flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold uppercase">
                    {{ $tipoEntidad === 'trabajador' ? $entidadEncontrada->nombre_completo : $entidadEncontrada->patente_completa }}
                </h2>
                <p class="text-gray-300 mt-1 font-mono">
                    {{ $tipoEntidad === 'trabajador' ? 'RUT: ' . $entidadEncontrada->rut : 'Vehículo / Maquinaria' }}
                </p>
            </div>
            <div class="hidden sm:block">
                @if($tipoEntidad === 'trabajador')
                    <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                @else
                    <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path></svg>
                @endif
            </div>
        </div>

        <!-- Lista de Vinculaciones -->
        <div class="bg-gray-50 rounded-b-xl border border-t-0 border-gray-200 shadow-md divide-y divide-gray-200">
            @foreach($vinculacionesResultado as $vinc)
                @php
                    $isHabilitado = $vinc['estado_acceso']['habilitado'] ?? false;
                    $statusColor = $isHabilitado ? 'bg-green-100 border-green-500' : 'bg-red-50 border-red-500';
                    $statusTextColor = $isHabilitado ? 'text-green-800' : 'text-red-800';
                    $badgeColor = $isHabilitado ? 'bg-green-500' : 'bg-red-500';
                @endphp

                <div class="p-4 sm:p-6 flex flex-col md:flex-row gap-6">
                    <!-- Detalle Vinculación -->
                    <div class="flex-1">
                        <div class="mb-4">
                            <span class="text-xs font-semibold tracking-wider text-gray-500 uppercase">Mandante / Proyecto</span>
                            <div class="text-lg font-bold text-gray-900">{{ $vinc['mandante'] }}</div>
                            <div class="text-sm text-gray-700">{{ $vinc['unidad_organizacional'] }}</div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="block text-xs font-medium text-gray-500 uppercase">Contrato</span>
                                <span class="font-semibold text-gray-800">{{ $vinc['contrato'] }}</span>
                            </div>
                            <div>
                                <span class="block text-xs font-medium text-gray-500 uppercase">Cargo / Función</span>
                                <span class="font-semibold text-gray-800">{{ $vinc['cargo'] }}</span>
                            </div>
                            <div class="col-span-2">
                                <span class="block text-xs font-medium text-gray-500 uppercase">Lugar de Trabajo (Dependencia)</span>
                                <span class="font-semibold text-gray-800">{{ $vinc['lugar_trabajo'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Estado y Documentos -->
                    <div class="md:w-1/2 lg:w-5/12 flex flex-col justify-center">
                        <div class="border-l-4 {{ $statusColor }} rounded-r-lg p-4 h-full flex flex-col">
                            <div class="flex items-center space-x-3 mb-2">
                                <span class="flex-shrink-0 h-4 w-4 rounded-full {{ $badgeColor }} shadow-sm"></span>
                                <span class="text-xl font-black uppercase {{ $statusTextColor }}">
                                    {{ $isHabilitado ? 'ACCESO HABILITADO' : 'ACCESO RESTRINGIDO' }}
                                </span>
                            </div>
                            <p class="text-sm font-medium {{ $statusTextColor }} ml-7 mb-4">
                                Motivo: {{ $vinc['estado_acceso']['motivo'] ?? 'Desconocido' }}
                            </p>

                            @if(!$isHabilitado && count($vinc['documentos_problematicos']) > 0)
                                <div class="mt-auto bg-white bg-opacity-60 rounded p-3">
                                    <p class="text-xs font-bold text-red-900 uppercase mb-2 border-b border-red-200 pb-1">Documentos Críticos Pendientes / Vencidos</p>
                                    <ul class="space-y-1">
                                        @foreach($vinc['documentos_problematicos'] as $docProb)
                                            <li class="flex items-start text-xs text-red-800">
                                                <svg class="h-4 w-4 text-red-500 mr-1 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                <span><span class="font-semibold">{{ $docProb['nombre'] }}</span> ({{ $docProb['estado'] }})</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
