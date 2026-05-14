<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestión de Reglas Documentales') }}
        </h2>
    </x-slot>

    <div class="py-2">
        <div class="w-full sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4">
                
                @if (session()->has('success')) <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded dark:bg-green-700 dark:text-green-100 dark:border-green-600">{{ session('success') }}</div> @endif
                @if (session()->has('error')) <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded dark:bg-red-700 dark:text-red-100 dark:border-red-600">{{ session('error') }}</div> @endif

                <div class="flex justify-between items-center mb-10">
                    <div class="flex items-center space-x-4">
                        
                        @if($filtroMandanteId && $imcTotalPrincipal > 0)
                            <div class="flex items-center space-x-2">
                                <!-- Tarjeta Total -->
                                <div class="flex items-center space-x-3 bg-indigo-50 dark:bg-indigo-900/40 border border-indigo-200 dark:border-indigo-800 px-8 py-4 rounded-xl shadow-md animate-fade-in-down border-l-8 border-l-indigo-600">
                                    <div class="bg-indigo-600 p-2 rounded-lg">
                                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-black text-indigo-500 dark:text-indigo-400 uppercase tracking-widest leading-none">ICM TOTAL TEORICO</p>
                                        <p class="text-2xl font-black text-indigo-900 dark:text-indigo-100 leading-none mt-1">
                                            {{ number_format($imcTotalPrincipal, 3, ',', '.') }} 
                                            <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">/ mes</span>
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Divisor -->
                                <div class="w-px h-8 bg-gray-300 dark:bg-gray-600 mx-1"></div>

                                <!-- Desglose por Entidad -->
                                @foreach($imcDesglose as $entidad => $valor)
                                    @if($valor > 0)
                                        @php
                                            $colorMap = [
                                                'PERSONA' => ['bg' => 'bg-emerald-50 dark:bg-emerald-900/30', 'border' => 'border-emerald-200 dark:border-emerald-800', 'text' => 'text-emerald-900 dark:text-emerald-100', 'label' => 'text-emerald-600 dark:text-emerald-400', 'icon' => 'bg-emerald-500 text-white', 'leftBorder' => 'border-l-emerald-500'],
                                                'VEHICULO' => ['bg' => 'bg-blue-50 dark:bg-blue-900/30', 'border' => 'border-blue-200 dark:border-blue-800', 'text' => 'text-blue-900 dark:text-blue-100', 'label' => 'text-blue-600 dark:text-blue-400', 'icon' => 'bg-blue-500 text-white', 'leftBorder' => 'border-l-blue-500'],
                                                'MAQUINARIA' => ['bg' => 'bg-amber-50 dark:bg-amber-900/30', 'border' => 'border-amber-200 dark:border-amber-800', 'text' => 'text-amber-900 dark:text-amber-100', 'label' => 'text-amber-600 dark:text-amber-400', 'icon' => 'bg-amber-500 text-white', 'leftBorder' => 'border-l-amber-500'],
                                                'EMBARCACION' => ['bg' => 'bg-cyan-50 dark:bg-cyan-900/30', 'border' => 'border-cyan-200 dark:border-cyan-800', 'text' => 'text-cyan-900 dark:text-cyan-100', 'label' => 'text-cyan-600 dark:text-cyan-400', 'icon' => 'bg-cyan-500 text-white', 'leftBorder' => 'border-l-cyan-500'],
                                                'EMPRESA' => ['bg' => 'bg-purple-50 dark:bg-purple-900/30', 'border' => 'border-purple-200 dark:border-purple-800', 'text' => 'text-purple-900 dark:text-purple-100', 'label' => 'text-purple-600 dark:text-purple-400', 'icon' => 'bg-purple-500 text-white', 'leftBorder' => 'border-l-purple-500'],
                                            ];
                                            $c = $colorMap[$entidad] ?? $colorMap['PERSONA'];
                                        @endphp
                                        <div class="flex items-center space-x-3 {{ $c['bg'] }} border {{ $c['border'] }} px-6 py-4 rounded-xl shadow-md animate-fade-in-down border-l-8 {{ $c['leftBorder'] }}">
                                            <div>
                                                <p class="text-[10px] font-black {{ $c['label'] }} uppercase tracking-widest leading-none">
                                                    {{ $entidad === 'PERSONA' ? 'ICM TRABAJADORES TEORICO' : ($entidad === 'VEHICULO' ? 'ICM VEHICULO TEORICO' : $entidad) }}
                                                </p>
                                                <p class="text-xl font-black {{ $c['text'] }} leading-none mt-1">
                                                    {{ number_format($valor, 3, ',', '.') }}
                                                </p>
                                            </div>
                                        </div>
                                        
                                        @if($entidad === 'VEHICULO' || ($entidad === 'PERSONA' && ($imcDesglose['VEHICULO'] ?? 0) <= 0))
                                            <div class="relative group">
                                                <div class="flex items-center space-x-3 bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800 px-6 py-4 rounded-xl shadow-md animate-fade-in-down border-l-8 border-l-indigo-500 cursor-help" title="">
                                                    <div>
                                                        <p class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest leading-none">TOTAL ENTIDADES CONTROLADAS</p>
                                                        <p class="text-xl font-black text-indigo-900 dark:text-indigo-100 leading-none mt-1">
                                                            {{ number_format($totalEntidadesControladas ?? 0, 0, ',', '.') }}
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                <!-- Tooltip Nivel Dios -->
                                                <div class="absolute z-[100] top-full left-1/2 -translate-x-1/2 mt-3 w-64 opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-300 transform -translate-y-2 group-hover:translate-y-0">
                                                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-indigo-100 dark:border-indigo-900 overflow-hidden">
                                                        <div class="bg-indigo-600 px-4 py-2 flex items-center justify-between">
                                                            <p class="text-[10px] font-bold text-white uppercase tracking-wider">Desglose de Entidades</p>
                                                            <svg class="w-3 h-3 text-indigo-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                        </div>
                                                        <div class="p-3 space-y-2.5">
                                                            <div class="flex justify-between items-center text-xs">
                                                                <span class="text-gray-500 dark:text-gray-400 flex items-center">
                                                                    <div class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></div>
                                                                    Trabajadores
                                                                </span>
                                                                <span class="font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalTrabajadoresPersona ?? 0, 0, ',', '.') }}</span>
                                                            </div>
                                                            <div class="flex justify-between items-center text-xs">
                                                                <span class="text-gray-500 dark:text-gray-400 flex items-center">
                                                                    <div class="w-2 h-2 rounded-full bg-blue-500 mr-2"></div>
                                                                    Vehículos
                                                                </span>
                                                                <span class="font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalVehiculos ?? 0, 0, ',', '.') }}</span>
                                                            </div>
                                                            <div class="flex justify-between items-center text-xs">
                                                                <span class="text-gray-500 dark:text-gray-400 flex items-center">
                                                                    <div class="w-2 h-2 rounded-full bg-amber-500 mr-2"></div>
                                                                    Maquinaria
                                                                </span>
                                                                <span class="font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalMaquinarias ?? 0, 0, ',', '.') }}</span>
                                                            </div>
                                                            <div class="flex justify-between items-center text-xs">
                                                                <span class="text-gray-500 dark:text-gray-400 flex items-center">
                                                                    <div class="w-2 h-2 rounded-full bg-cyan-500 mr-2"></div>
                                                                    Embarcaciones
                                                                </span>
                                                                <span class="font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalEmbarcaciones ?? 0, 0, ',', '.') }}</span>
                                                            </div>
                                                            <div class="flex justify-between items-center text-xs pt-2 border-t border-gray-100 dark:border-gray-700">
                                                                <span class="text-gray-500 dark:text-gray-400 flex items-center">
                                                                    <div class="w-2 h-2 rounded-full bg-purple-500 mr-2"></div>
                                                                    Entidad Empresa
                                                                </span>
                                                                <span class="font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalEmpresas ?? 0, 0, ',', '.') }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="bg-indigo-50 dark:bg-indigo-900/40 px-3 py-2 flex justify-between items-center">
                                                            <span class="text-[10px] font-black text-indigo-700 dark:text-indigo-300 uppercase">Total Entidades</span>
                                                            <span class="text-sm font-black text-indigo-900 dark:text-indigo-100">{{ number_format($totalEntidadesControladas ?? 0, 0, ',', '.') }}</span>
                                                        </div>
                                                    </div>
                                                    <!-- Flecha -->
                                                    <div class="w-3 h-3 bg-indigo-600 border-l border-t border-indigo-600 absolute left-1/2 -translate-x-1/2 -top-1.5 rotate-45"></div>
                                                </div>
                                            </div>
                                            <!-- Nueva Tarjeta: Documentos Esperados con Tooltip Nivel Dios -->
                                            <div class="relative group">
                                                <div class="flex items-center space-x-3 bg-pink-50 dark:bg-pink-900/30 border border-pink-200 dark:border-pink-800 px-6 py-4 rounded-xl shadow-md animate-fade-in-down border-l-8 border-l-pink-500 cursor-help" title="">
                                                    <div>
                                                        <p class="text-[10px] font-black text-pink-600 dark:text-pink-400 uppercase tracking-widest leading-none">DOCS. ESPERADOS</p>
                                                        <p class="text-xl font-black text-pink-900 dark:text-pink-100 leading-none mt-1">
                                                            {{ number_format($totalDocumentosEsperadosGlobal ?? 0, 1, ',', '.') }} 
                                                            <span class="text-sm font-bold text-pink-500">/ mes</span>
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- Tooltip Nivel Dios: Docs Esperados -->
                                                <div class="absolute z-[100] top-full left-1/2 -translate-x-1/2 mt-3 w-64 opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-300 transform -translate-y-2 group-hover:translate-y-0">
                                                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-pink-100 dark:border-pink-900 overflow-hidden">
                                                        <div class="bg-pink-600 px-4 py-2 flex items-center justify-between">
                                                            <p class="text-[10px] font-bold text-white uppercase tracking-wider">Desglose de Documentos</p>
                                                            <svg class="w-3 h-3 text-pink-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                                        </div>
                                                        <div class="p-3 space-y-2.5">
                                                            <div class="flex justify-between items-center text-xs">
                                                                <span class="text-gray-500 dark:text-gray-400 flex items-center">
                                                                    <div class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></div>
                                                                    Docs. Trabajadores
                                                                </span>
                                                                <span class="font-bold text-gray-900 dark:text-gray-100">{{ number_format($docsEsperadosDesglose['PERSONA'] ?? 0, 1, ',', '.') }}</span>
                                                            </div>
                                                            <div class="flex justify-between items-center text-xs">
                                                                <span class="text-gray-500 dark:text-gray-400 flex items-center">
                                                                    <div class="w-2 h-2 rounded-full bg-blue-500 mr-2"></div>
                                                                    Docs. Vehículos
                                                                </span>
                                                                <span class="font-bold text-gray-900 dark:text-gray-100">{{ number_format($docsEsperadosDesglose['VEHICULO'] ?? 0, 1, ',', '.') }}</span>
                                                            </div>
                                                            <div class="flex justify-between items-center text-xs">
                                                                <span class="text-gray-500 dark:text-gray-400 flex items-center">
                                                                    <div class="w-2 h-2 rounded-full bg-amber-500 mr-2"></div>
                                                                    Docs. Maquinaria
                                                                </span>
                                                                <span class="font-bold text-gray-900 dark:text-gray-100">{{ number_format($docsEsperadosDesglose['MAQUINARIA'] ?? 0, 1, ',', '.') }}</span>
                                                            </div>
                                                            <div class="flex justify-between items-center text-xs">
                                                                <span class="text-gray-500 dark:text-gray-400 flex items-center">
                                                                    <div class="w-2 h-2 rounded-full bg-cyan-500 mr-2"></div>
                                                                    Docs. Embarcaciones
                                                                </span>
                                                                <span class="font-bold text-gray-900 dark:text-gray-100">{{ number_format($docsEsperadosDesglose['EMBARCACION'] ?? 0, 1, ',', '.') }}</span>
                                                            </div>
                                                            <div class="flex justify-between items-center text-xs pt-2 border-t border-gray-100 dark:border-gray-700">
                                                                <span class="text-gray-500 dark:text-gray-400 flex items-center">
                                                                    <div class="w-2 h-2 rounded-full bg-purple-500 mr-2"></div>
                                                                    Docs. Empresa
                                                                </span>
                                                                <span class="font-bold text-gray-900 dark:text-gray-100">{{ number_format($docsEsperadosDesglose['EMPRESA'] ?? 0, 1, ',', '.') }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="bg-pink-50 dark:bg-pink-900/40 px-3 py-2 flex justify-between items-center">
                                                            <span class="text-[10px] font-black text-pink-700 dark:text-pink-300 uppercase">Total Esperados</span>
                                                            <span class="text-sm font-black text-pink-900 dark:text-pink-100">{{ number_format($totalDocumentosEsperadosGlobal ?? 0, 1, ',', '.') }}</span>
                                                        </div>
                                                    </div>
                                                    <!-- Flecha -->
                                                    <div class="w-3 h-3 bg-pink-600 border-l border-t border-pink-600 absolute left-1/2 -translate-x-1/2 -top-1.5 rotate-45"></div>
                                                </div>
                                            </div>
                                            <!-- Nueva Tarjeta: Promedio por Trabajador con Tooltip Nivel Dios -->
                                            @php
                                                $ratioDocsTrabajador = ($totalTrabajadoresPersona ?? 0) > 0 ? (($totalDocumentosEsperadosGlobal ?? 0) / $totalTrabajadoresPersona) : 0;
                                            @endphp
                                            <div class="relative group">
                                                <div class="flex items-center space-x-3 bg-teal-50 dark:bg-teal-900/30 border border-teal-200 dark:border-teal-800 px-6 py-4 rounded-xl shadow-md animate-fade-in-down border-l-8 border-l-teal-500 cursor-help">
                                                    <div>
                                                        <p class="text-[10px] font-black text-teal-600 dark:text-teal-400 uppercase tracking-widest leading-none">ICM</p>
                                                        <p class="text-xl font-black text-teal-900 dark:text-teal-100 leading-none mt-1">
                                                            {{ number_format($ratioDocsTrabajador, 2, ',', '.') }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- Tooltip Nivel Dios: ICM -->
                                                <div class="absolute z-[100] top-full left-1/2 -translate-x-1/2 mt-3 w-80 opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-300 transform -translate-y-2 group-hover:translate-y-0 text-left">
                                                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-teal-100 dark:border-teal-900 overflow-hidden">
                                                        <div class="bg-teal-600 px-4 py-2.5 flex items-center justify-between">
                                                            <p class="text-[11px] font-bold text-white uppercase tracking-wider">Cálculo del ICM Global</p>
                                                            <svg class="w-4 h-4 text-teal-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                        </div>
                                                        <div class="p-4 space-y-3">
                                                            <div class="flex justify-between items-center text-sm">
                                                                <span class="text-gray-500 dark:text-gray-400">Total Docs / mes</span>
                                                                <span class="font-black text-gray-900 dark:text-gray-100">{{ number_format($totalDocumentosEsperadosGlobal ?? 0, 1, ',', '.') }}</span>
                                                            </div>
                                                            <div class="flex justify-between items-center text-sm">
                                                                <span class="text-gray-500 dark:text-gray-400">Total Trabajadores</span>
                                                                <span class="font-black text-gray-900 dark:text-gray-100">{{ number_format($totalTrabajadoresPersona ?? 0, 0, ',', '.') }}</span>
                                                            </div>
                                                            
                                                            <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                                                                <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg flex items-center justify-center space-x-3 text-xs font-mono text-gray-400">
                                                                    <span class="font-bold text-gray-600 dark:text-gray-300">{{ number_format($totalDocumentosEsperadosGlobal ?? 0, 1, ',', '.') }}</span>
                                                                    <span class="text-lg">÷</span>
                                                                    <span class="font-bold text-gray-600 dark:text-gray-300">{{ number_format($totalTrabajadoresPersona ?? 0, 0, ',', '.') }}</span>
                                                                    <span class="text-lg">=</span>
                                                                    <span class="text-teal-600 dark:text-teal-400 font-black text-sm">{{ number_format($ratioDocsTrabajador, 2, ',', '.') }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="bg-teal-50 dark:bg-teal-900/40 px-4 py-2.5">
                                                            <p class="text-[11px] text-teal-700 dark:text-teal-300 leading-tight italic font-medium">
                                                                * Indica el promedio de carga documental mensual por cada trabajador activo.
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <!-- Flecha -->
                                                    <div class="w-3 h-3 bg-teal-600 border-l border-t border-teal-600 absolute left-1/2 -translate-x-1/2 -top-1.5 rotate-45"></div>
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center space-x-2">
                        <button wire:click="$set('showExportOptionsModal', true)" class="flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors duration-200" title="Exportación Selectiva / Opciones Avanzadas">
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            Exportar Selección
                        </button>
                        <button wire:click="descargarPlantilla" class="flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-md shadow-sm transition-colors duration-200" title="Descargar plantilla Excel para importar">
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" /></svg>
                            Plantilla
                        </button>
                        <button wire:click="$set('showImportModal', true)" class="flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors duration-200" title="Carga Masiva desde Excel">
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                            Importar
                        </button>
                        <button wire:click="$set('showReporteImcModal', true)" class="flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors duration-200" title="Generar Reporte Ejecutivo ICM">
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            Reporte ICM
                        </button>
                        <div class="relative group">
                            <div class="flex items-center space-x-2">
                                <button wire:click="exportarExcel" class="flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors duration-200" title="Exportar a Excel (Respeta filtros y selección)">
                                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    Excel
                                </button>
                                <button wire:click="exportarPDF" class="flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors duration-200" title="Exportar a PDF (Respeta filtros y selección)">
                                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h1.5m1.5 0H13m-4 4h4m-4 4h4" /></svg>
                                    PDF
                                </button>
                            </div>
                            <div class="absolute -bottom-7 left-0 right-0 text-center pointer-events-none">
                                <span class="text-[20px] text-gray-500 dark:text-gray-400 font-black italic whitespace-nowrap opacity-90 tracking-tight">
                                    * Exporta todo lo filtrado
                                </span>
                            </div>
                        </div>
                        <button wire:click="create()" class="btn-primary">
                            <x-icons.plus class="w-5 h-5 mr-2"/>
                            {{ __('Agregar Nueva Regla') }}
                        </button>
                    </div>
                </div>

                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg shadow">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div>
                            <label for="filtroMandanteId" class="label-generic">Filtrar por Principal:</label>
                            <select wire:model.live="filtroMandanteId" id="filtroMandanteId" class="input-field">
                                <option value="">Todos los Principales</option>
                                @if(!empty($listaMandantes))
                                    @foreach ($listaMandantes as $mandante)
                                        <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div>
                            <label for="filtroTipoEntidadId" class="label-generic">Filtrar por Entidad:</label>
                            <select wire:model.live="filtroTipoEntidadId" id="filtroTipoEntidadId" class="input-field">
                                <option value="">Todas las Entidades</option>
                                 @if(!empty($listaTiposEntidad))
                                    @foreach ($listaTiposEntidad as $tipo)
                                        <option value="{{ $tipo->id }}">{{ $tipo->nombre_entidad }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div>
                            <label for="filtroNombreDocumento" class="label-generic">Buscar por Nombre Documento:</label>
                            <input type="text" wire:model.live.debounce.300ms="filtroNombreDocumento" id="filtroNombreDocumento" class="input-field" placeholder="Escriba para buscar...">
                        </div>
                        <div class="flex items-center justify-end">
                            <button wire:click="resetFilters" class="flex items-center px-4 py-2 border border-red-300 dark:border-red-800 rounded-lg text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 font-bold text-sm transition-all duration-200 shadow-sm group">
                                <svg class="w-4 h-4 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                Limpiar Filtros
                            </button>
                        </div>
                    </div>
                </div>

                <div x-data="{
                        width: 0,
                        updateWidth() {
                            if (this.$refs.table) {
                                this.width = this.$refs.table.scrollWidth;
                            }
                        },
                        syncScroll() {
                            this.$refs.topScroll.scrollLeft = this.$refs.bottomScroll.scrollLeft;
                        },
                        syncScrollBottom() {
                            this.$refs.bottomScroll.scrollLeft = this.$refs.topScroll.scrollLeft;
                        }
                    }"
                    x-init="
                        updateWidth();
                        new ResizeObserver(() => updateWidth()).observe($refs.table);
                        window.addEventListener('resize', () => updateWidth());
                        window.addEventListener('livewire:load', () => updateWidth());
                        document.addEventListener('livewire:navigated', () => updateWidth());
                    "
                >
                    <div x-ref="topScroll" @scroll="syncScrollBottom()" class="overflow-x-auto overflow-y-hidden mb-2">
                        <div :style="{ width: width + 'px' }" class="h-1"></div>
                    </div>

                    <div x-ref="bottomScroll" @scroll="syncScroll()" class="overflow-x-auto overflow-y-auto min-h-[70vh] max-h-[85vh] custom-scrollbar">
                        <table x-ref="table" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th scope="col" class="table-header w-10">
                                        <input type="checkbox" wire:model.live="seleccionarTodas" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    </th>
                                    <th scope="col" class="table-header w-16">#</th>
                                    <th scope="col" class="table-header">ID</th>
                                    <th scope="col" class="table-header">Principal</th>
                                    <th scope="col" class="table-header">Entidad</th>
                                    <th scope="col" class="table-header max-w-sm">Documento</th>
                                    <th scope="col" class="table-header">UNIDAD OPERATIVA</th>
                                    @if(!empty($filtroMandanteId))
                                        <th scope="col" class="table-header text-center" title="Cantidad de trabajadores activos afectados por esta regla (solo se calcula cuando hay un filtro por Principal activo)">Afectados</th>
                                    @endif
                                    <th scope="col" class="table-header text-center" title="Meses definidos para el cálculo del ICM. Use los botones para ajuste rápido.">Meses ICM</th>
                                    <th scope="col" class="table-header text-center">ICM</th>
                                    <th scope="col" class="table-header">Activa</th>
                                    <th scope="col" class="table-header text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($reglas as $regla)
                                    <tr wire:key="regla-{{ $regla->id }}" class="even:bg-white odd:bg-gray-50 dark:even:bg-gray-800 dark:odd:bg-gray-700/50">
                                        <td class="table-cell text-center">
                                            <input type="checkbox" wire:model.live="reglasSeleccionadas" value="{{ (string)$regla->id }}" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                        </td>
                                        <td class="table-cell font-medium text-gray-500 dark:text-gray-400">
                                            {{ ($reglas->currentPage() - 1) * $reglas->perPage() + $loop->iteration }}
                                        </td>
                                        <td class="table-cell">{{ $regla->id }}</td>
                                        <td class="table-cell">{{ $regla->mandante->razon_social ?? 'N/A' }}</td>
                                        <td class="table-cell">{{ $regla->tipoEntidadControlada->nombre_entidad ?? 'N/A' }}</td>
                                        <td class="table-cell max-w-sm relative group">
                                            <span class="font-bold cursor-help border-b border-dotted border-gray-400 dark:border-gray-500 pb-0.5 whitespace-normal">
                                                {{ $regla->nombreDocumento->nombre ?? 'N/A' }}
                                            </span>

                                            <!-- Tooltip Nivel Dios: Aplicabilidad Completa -->
                                            <div class="absolute z-[100] top-full left-0 mt-2 w-[600px] opacity-0 group-hover:opacity-100 pointer-events-none transition-all duration-300 transform -translate-y-2 group-hover:translate-y-0">
                                                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-blue-100 dark:border-blue-900 overflow-hidden">
                                                    <div class="bg-blue-600 px-4 py-2.5 flex items-center justify-between">
                                                        <p class="text-xs font-bold text-white uppercase tracking-wider">Aplicabilidad de la Regla</p>
                                                        <svg class="w-4 h-4 text-blue-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    </div>
                                                    
                                                    <div class="p-4 grid grid-cols-2 gap-x-6 gap-y-4">
                                                        @php 
                                                            $entidadNombre = strtoupper($regla->tipoEntidadControlada->nombre_entidad ?? 'OTRO');
                                                        @endphp

                                                        {{-- SECCIÓN: EMPRESA (Aplica a todas las entidades) --}}
                                                        <div class="space-y-1.5">
                                                            <p class="text-[11px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest">Condiciones Empresa</p>
                                                            <div class="flex flex-wrap gap-1.5">
                                                                @forelse($regla->condicionesEmpresaAplica as $c)
                                                                    <span class="px-2 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded text-xs border border-blue-100 dark:border-blue-800">{{ $c->nombre }}</span>
                                                                @empty
                                                                    <span class="text-xs italic font-bold text-gray-400 dark:text-gray-500">APLICA A TODAS</span>
                                                                @endforelse
                                                            </div>
                                                        </div>

                                                        {{-- SECCIÓN: ESPECÍFICA PERSONA --}}
                                                        @if($entidadNombre === 'PERSONA')
                                                            <div class="space-y-1.5">
                                                                <p class="text-[11px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Condiciones Persona</p>
                                                                <div class="flex flex-wrap gap-1.5">
                                                                    @forelse($regla->condicionesPersonaAplica as $c)
                                                                        <span class="px-2 py-1 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded text-xs border border-emerald-100 dark:border-emerald-800">{{ $c->nombre }}</span>
                                                                    @empty
                                                                        <span class="text-xs italic font-bold text-gray-400 dark:text-gray-500">APLICA A TODAS</span>
                                                                    @endforelse
                                                                </div>
                                                            </div>

                                                            <div class="space-y-1.5">
                                                                <p class="text-[11px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">Cargos Aplicables</p>
                                                                <div class="flex flex-wrap gap-1.5">
                                                                    @forelse($regla->cargosAplica as $c)
                                                                        <span class="px-2 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded text-xs border border-indigo-100 dark:border-indigo-800">{{ $c->nombre_cargo }}</span>
                                                                    @empty
                                                                        <span class="text-xs italic font-bold text-gray-400 dark:text-gray-500">APLICA A TODOS</span>
                                                                    @endforelse
                                                                </div>
                                                            </div>

                                                            <div class="space-y-1.5">
                                                                <p class="text-[11px] font-black text-teal-600 dark:text-teal-400 uppercase tracking-widest">Permanencia</p>
                                                                <div class="flex flex-wrap gap-1.5">
                                                                    @forelse($regla->tiposPermanenciaAplica as $p)
                                                                        <span class="px-2 py-1 bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 rounded text-xs border border-teal-100 dark:border-teal-800">{{ $p->nombre }}</span>
                                                                    @empty
                                                                        <span class="text-xs italic font-bold text-gray-400 dark:text-gray-500">APLICA A TODAS</span>
                                                                    @endforelse
                                                                </div>
                                                            </div>

                                                            <div class="space-y-1.5">
                                                                <p class="text-[11px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest">Nacionalidades</p>
                                                                <div class="flex flex-wrap gap-1.5">
                                                                    @forelse($regla->nacionalidadesAplica as $n)
                                                                        <span class="px-2 py-1 bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 rounded text-xs border border-rose-100 dark:border-rose-800">{{ $n->nombre }}</span>
                                                                    @empty
                                                                        <span class="text-xs italic font-bold text-gray-400 dark:text-gray-500">APLICA A TODAS</span>
                                                                    @endforelse
                                                                </div>
                                                            </div>
                                                        @endif

                                                        {{-- SECCIÓN: ESPECÍFICA RECURSOS (Vehículo, Maquinaria, etc.) --}}
                                                        @if(in_array($entidadNombre, ['VEHICULO', 'MAQUINARIA', 'EMBARCACION']))
                                                            <div class="space-y-1.5">
                                                                <p class="text-[11px] font-black text-amber-600 dark:text-amber-400 uppercase tracking-widest">Tipo de Recurso</p>
                                                                <div class="flex flex-wrap gap-1.5">
                                                                    @php 
                                                                        $tiposRelacion = $entidadNombre === 'VEHICULO' ? 'tiposVehiculoAplica' : ($entidadNombre === 'MAQUINARIA' ? 'tiposMaquinariaAplica' : 'tiposEmbarcacionAplica');
                                                                        $items = $regla->$tiposRelacion ?? collect();
                                                                    @endphp
                                                                    @forelse($items as $i)
                                                                        <span class="px-2 py-1 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded text-xs border border-amber-100 dark:border-amber-800">{{ $i->nombre }}</span>
                                                                    @empty
                                                                        <span class="text-xs italic font-bold text-gray-400 dark:text-gray-500">APLICA A TODOS</span>
                                                                    @endforelse
                                                                </div>
                                                            </div>

                                                            @if($entidadNombre === 'VEHICULO')
                                                                <div class="space-y-1.5">
                                                                    <p class="text-[11px] font-black text-orange-600 dark:text-orange-400 uppercase tracking-widest">Condiciones Vehículo</p>
                                                                    <div class="flex flex-wrap gap-1.5">
                                                                        @forelse($regla->condicionesVehiculoAplica as $c)
                                                                            <span class="px-2 py-1 bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 rounded text-xs border border-orange-100 dark:border-orange-800">{{ $c->nombre }}</span>
                                                                        @empty
                                                                            <span class="text-xs italic font-bold text-gray-400 dark:text-gray-500">APLICA A TODAS</span>
                                                                        @endforelse
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            <div class="space-y-1.5">
                                                                <p class="text-[11px] font-black text-purple-600 dark:text-purple-400 uppercase tracking-widest">Tenencia</p>
                                                                <div class="flex flex-wrap gap-1.5">
                                                                    @forelse($regla->tenenciasAplica as $t)
                                                                        <span class="px-2 py-1 bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded text-xs border border-purple-100 dark:border-purple-800">{{ $t->nombre }}</span>
                                                                    @empty
                                                                        <span class="text-xs italic font-bold text-gray-400 dark:text-gray-500">APLICA A TODAS</span>
                                                                    @endforelse
                                                                </div>
                                                            </div>
                                                        @endif

                                                        @if($entidadNombre === 'EMPRESA')
                                                            <div class="flex items-center space-x-2 text-indigo-600 font-bold text-sm py-1 uppercase tracking-tight col-span-2">
                                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                                                <span>Documento Institucional</span>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-2 border-t border-gray-100 dark:border-gray-700">
                                                        <p class="text-[10px] text-gray-400 dark:text-gray-500 italic font-bold">Desglose completo de aplicabilidad para este documento.</p>
                                                    </div>
                                                </div>
                                                <!-- Flecha -->
                                                <div class="w-3 h-3 bg-blue-600 border-l border-t border-blue-600 absolute left-4 -top-1.5 rotate-45"></div>
                                            </div>
                                        </td>
                                        <td class="table-cell">
                                            @if($regla->unidadesOrganizacionales->isNotEmpty())
                                                {{ $regla->unidadesOrganizacionales->pluck('nombre_unidad')->take(2)->join(', ') }}
                                                @if($regla->unidadesOrganizacionales->count() > 2)
                                                    ... (+{{ $regla->unidadesOrganizacionales->count() - 2 }})
                                                @endif
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        
                                        @if(!empty($filtroMandanteId))
                                        <td class="table-cell text-center font-bold text-indigo-600 dark:text-indigo-400">
                                            <span class="inline-flex items-center justify-center px-2 py-1 bg-indigo-50 dark:bg-indigo-900/30 rounded-md shadow-sm border border-indigo-100 dark:border-indigo-800">
                                                @php $entidadTipo = strtoupper($regla->tipoEntidadControlada->nombre_entidad ?? ''); @endphp
                                                @if($entidadTipo === 'PERSONA')
                                                    <svg class="w-3.5 h-3.5 mr-1 text-indigo-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path></svg>
                                                @elseif($entidadTipo === 'VEHICULO')
                                                    <svg class="w-3.5 h-3.5 mr-1 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                                                @elseif($entidadTipo === 'MAQUINARIA')
                                                    <svg class="w-3.5 h-3.5 mr-1 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                                @elseif($entidadTipo === 'EMBARCACION')
                                                    <svg class="w-3.5 h-3.5 mr-1 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5H1m0 0l3 3m-3-3l3-3M5 10l7-7m8 11l-3 3m3-3l-3-3m5 5H9" /></svg>
                                                @endif
                                                {{ $regla->contarAfectados() }}
                                            </span>
                                        </td>
                                        @endif
                                        
                                        <!-- NUEVA COLUMNA MESES IMC INTERACTIVA -->

                                        <td class="table-cell text-center whitespace-nowrap">
                                            <div class="inline-flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-md p-0.5 border border-gray-200 dark:border-gray-700">
                                                <button wire:click="decrementarMesesImc({{ $regla->id }})" wire:loading.attr="disabled" class="w-6 h-6 flex items-center justify-center rounded text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors" title="Disminuir / Auto">
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M20 12H4" /></svg>
                                                </button>
                                                
                                                @php
                                                    $mesesEfectivos = $regla->imc_meses_estimados;
                                                    $esAuto = false;
                                                    if ($mesesEfectivos === null && $regla->dias_validez_documento > 0) {
                                                        $mesesEfectivos = (int) round($regla->dias_validez_documento / 30.44);
                                                        $esAuto = true;
                                                    }
                                                @endphp
                                                <span class="px-1 text-center text-xs font-bold {{ $esAuto ? 'text-gray-400 italic' : 'text-gray-700 dark:text-gray-300' }}" title="{{ $esAuto ? 'Valor auto-calculado desde días de vigencia' : 'Valor manual' }}">
                                                    {{ $mesesEfectivos ?? '-' }}{{ $esAuto ? '*' : '' }}
                                                </span>
                                                
                                                <button wire:click="incrementarMesesImc({{ $regla->id }})" wire:loading.attr="disabled" class="w-6 h-6 flex items-center justify-center rounded text-gray-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 transition-colors" title="Aumentar">
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
                                                </button>
                                            </div>
                                        </td>
                                        <!-- COLUMNA IMC MATEMÁTICO ORIGINAL -->
                                        <td class="table-cell text-center">
                                            @php $imcVal = $regla->imc; @endphp
                                            @if($imcVal !== null)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wide
                                                    {{ $imcVal >= 0.5 ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 border border-red-200 dark:border-red-800' : ($imcVal >= 0.1 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800' : 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 border border-green-200 dark:border-green-800') }}">
                                                    {{ number_format($imcVal, 4, ',', '.') }}
                                                </span>
                                            @else
                                                <span class="text-gray-400 dark:text-gray-500 text-xs italic">—</span>
                                            @endif
                                        </td>
                                        <td class="table-cell">
                                            @if ($regla->is_active)
                                                <span class="badge-active">Sí</span>
                                            @else
                                                <span class="badge-inactive">No</span>
                                            @endif
                                        </td>
                                        <td class="table-cell text-right">
                                            <button wire:click="verHistorial({{ $regla->id }})" class="btn-info btn-sm mr-1 p-1" title="Ver Historial de Cambios">
                                                <x-icons.history class="w-4 h-4"/>
                                            </button>
                                            <button wire:click="toggleActivo({{ $regla->id }})" wire:loading.attr="disabled" wire:target="toggleActivo({{ $regla->id }})" class="btn-secondary-outline btn-sm mr-1 p-1 {{ $regla->is_active ? 'hover:bg-yellow-100 dark:hover:bg-yellow-700' : 'hover:bg-green-100 dark:hover:bg-green-700' }}" title="{{ $regla->is_active ? 'Desactivar Regla' : 'Activar Regla' }}"> <span wire:loading.remove wire:target="toggleActivo({{ $regla->id }})"> @if ($regla->is_active) <x-icons.eye-slash class="w-4 h-4 text-yellow-600 dark:text-yellow-400"/> @else <x-icons.eye class="w-4 h-4 text-green-600 dark:text-green-400"/> @endif </span> <span wire:loading wire:target="toggleActivo({{ $regla->id }})"> <svg class="animate-spin h-4 w-4 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"> <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle> <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path> </svg> </span> </button>
                                            <button wire:click="edit({{ $regla->id }})" class="btn-secondary btn-sm mr-1 p-1" title="Editar Regla"> <x-icons.edit class="w-4 h-4"/> </button>
                                            <button wire:click="confirmarEliminacion({{ $regla->id }})" wire:loading.attr="disabled" wire:target="confirmarEliminacion({{ $regla->id }})" class="btn-danger btn-sm p-1" title="Eliminar Regla"> <span wire:loading.remove wire:target="confirmarEliminacion({{ $regla->id }})"> <x-icons.trash class="w-4 h-4"/> </span> <span wire:loading wire:target="confirmarEliminacion({{ $regla->id }})"> <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"> <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle> <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path> </svg> </span> </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr> <td colspan="12" class="table-cell text-center">No hay reglas documentales que coincidan con los filtros aplicados.</td> </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($reglas->hasPages()) <div class="mt-4"> {{ $reglas->links() }} </div> @endif
            </div>
        </div>
    </div>

    @if ($isOpen)
        @php
            $labelIdentificadorEspecifico = 'APLICA SOLO A LOS RUT';
            $labelIdentificadorExcluido = 'NO APLICA A LOS RUT';
            $placeholderIdentificador = 'RUTs separados por ;';

            if ($nombreEntidadSeleccionada === 'VEHICULO') {
                $labelIdentificadorEspecifico = 'APLICA SOLO A patentes';
                $labelIdentificadorExcluido = 'NO APLICA A patentes';
                $placeholderIdentificador = 'Patentes separadas por ;';
            } elseif ($nombreEntidadSeleccionada === 'MAQUINARIA') {
                $labelIdentificadorEspecifico = 'APLICA SOLO A patente/código';
                $labelIdentificadorExcluido = 'NO APLICA A patente/código';
                $placeholderIdentificador = 'Patentes/Códigos separados por ;';
            } elseif ($nombreEntidadSeleccionada === 'EMBARCACION') {
                $labelIdentificadorEspecifico = 'APLICA SOLO A nombre/matrícula';
                $labelIdentificadorExcluido = 'NO APLICA A nombre/matrícula';
                $placeholderIdentificador = 'Nombres/Matrículas separados por ;';
            }
        @endphp
        <div class="fixed z-[100] inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75" aria-hidden="true" wire:click="closeModal()"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-7xl sm:w-full">
                    <form wire:submit.prevent="{{ $modoEdicion ? 'update' : 'store' }}">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start mb-4"> <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full"> <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title"> {{ $modoEdicion ? 'Editar Regla Documental' : 'Agregar Nueva Regla Documental' }} </h3> </div> </div>
                            <hr class="my-4 border-gray-300 dark:border-gray-600"/>
                            <div class="space-y-6 max-h-[70vh] overflow-y-auto pr-2">

                                <div class="grid grid-cols-1">
                                    <div>
                                        <label for="nombre_documento_id" class="label-generic">DOCUMENTO <span class="text-red-500">*</span></label>
                                        <select wire:model="nombre_documento_id" id="nombre_documento_id" class="input-field">
                                            <option value="">Seleccione...</option>
                                            @if(isset($listaNombresDocumento))
                                                @foreach ($listaNombresDocumento as $doc)
                                                    <option value="{{ $doc->id }}">{{ $doc->nombre }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        @error('nombre_documento_id') <span class="error-message">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div> <label for="mandante_id" class="label-generic">PRINCIPAL <span class="text-red-500">*</span></label> <select wire:model.live="mandante_id" id="mandante_id" class="input-field"> <option value="">Seleccione...</option> @if(isset($listaMandantes)) @foreach ($listaMandantes as $mandante) <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option> @endforeach @endif </select> @error('mandante_id') <span class="error-message">{{ $message }}</span> @enderror </div>
                                    <div> <label for="tipo_entidad_controlada_id" class="label-generic">Entidad Controlada <span class="text-red-500">*</span></label> <select wire:model.live="tipo_entidad_controlada_id" id="tipo_entidad_controlada_id" class="input-field"> <option value="">Seleccione...</option> @if(isset($listaTiposEntidadControlable)) @foreach ($listaTiposEntidadControlable as $tipo) <option value="{{ $tipo->id }}">{{ $tipo->nombre_entidad }}</option> @endforeach @endif </select> @error('tipo_entidad_controlada_id') <span class="error-message">{{ $message }}</span> @enderror </div>
                                    <div> <label for="valor_nominal_documento" class="label-generic">Valor Nominal DEL DOCUMENTO</label> <input type="number" wire:model="valor_nominal_documento" id="valor_nominal_documento" class="input-field" min="0"> <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Valor Defecto: 1</p> @error('valor_nominal_documento') <span class="error-message">{{ $message }}</span> @enderror </div>
                                </div>

                                {{-- APLICA SOLO A / NO APLICA A --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div> <label for="rut_especificos" class="label-generic">{{ $labelIdentificadorEspecifico }}</label> <textarea wire:model="rut_especificos" id="rut_especificos" rows="2" class="input-field" placeholder="{{ $placeholderIdentificador }}"></textarea> <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">(SEPARADOS CON ;)</p> @error('rut_especificos') <span class="error-message">{{ $message }}</span> @enderror </div>
                                    <div> <label for="rut_excluidos" class="label-generic">{{ $labelIdentificadorExcluido }}</label> <textarea wire:model="rut_excluidos" id="rut_excluidos" rows="2" class="input-field" placeholder="{{ $placeholderIdentificador }}"></textarea> <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">(SEPARADOS CON ;)</p> @error('rut_excluidos') <span class="error-message">{{ $message }}</span> @enderror </div>
                                </div>

                                {{-- NO APLICA AL CONTRATISTA COMPLETO (solo para Persona, Vehículo, Maquinaria, Embarcación) --}}
                                @if ($nombreEntidadSeleccionada !== 'EMPRESA' && $nombreEntidadSeleccionada !== null)
                                    @php
                                        $terminoPlural = match($nombreEntidadSeleccionada) {
                                            'PERSONA' => 'trabajadores',
                                            'VEHICULO' => 'vehículos',
                                            'MAQUINARIA' => 'maquinarias',
                                            'EMBARCACION' => 'embarcaciones',
                                            default => 'unidades'
                                        };
                                    @endphp
                                    <div class="grid grid-cols-1">
                                        <div class="p-3 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700 rounded-lg">
                                            <label for="rut_contratistas_excluidos" class="block text-xs font-bold text-orange-700 dark:text-orange-300 uppercase tracking-wider mb-1">
                                                NO APLICA AL CONTRATISTA (RUT)
                                            </label>
                                            <textarea
                                                wire:model="rut_contratistas_excluidos"
                                                id="rut_contratistas_excluidos"
                                                rows="2"
                                                class="input-field border-orange-300 dark:border-orange-600 focus:border-orange-400 focus:ring-orange-300"
                                                placeholder="RUTs de contratistas separados por ;"
                                            ></textarea>
                                            <p class="text-xs text-orange-600 dark:text-orange-400 mt-1 font-semibold">
                                                Excluye a <strong>TODOS</strong> los {{ $terminoPlural }} de los contratistas indicados. (Separados con ;)
                                            </p>
                                            @error('rut_contratistas_excluidos') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                @endif

                                {{-- CONDICIONES (EMPRESA Y PERSONA) --}}
                                <div class="grid grid-cols-1 {{ $nombreEntidadSeleccionada === 'PERSONA' ? 'md:grid-cols-2' : 'md:grid-cols-3' }} gap-6">

                                    {{-- CONDICIÓN EMPRESA (Multi-Select con Buscador) --}}
                                    <div class="space-y-3">
                                        @include('livewire._partials._multi_select_condicion', [
                                            'opciones'      => $listaTiposCondicionEmpresa ?? collect(),
                                            'seleccionados' => $condicionesEmpresaSeleccionadas ?? [],
                                            'wireKey'       => 'condicionesEmpresaSeleccionadas',
                                            'label'         => 'CONDICIÓN EMPRESA',
                                            'placeholder'   => 'Buscar condición de empresa...',
                                        ])
                                        <p class="text-xs font-bold text-red-600 dark:text-red-400 italic mt-1">
                                            * SIN SELECCIÓN = APLICA A TODAS LAS CONDICIONES DE EMPRESA. Con selección, filtra solo las marcadas.
                                        </p>
                                        @error('condicionesEmpresaSeleccionadas') <span class="error-message">{{ $message }}</span> @enderror
                                    </div>

                                    {{-- CONDICIÓN PERSONA con Buscador (solo si entidad es PERSONA) --}}
                                    @if ($nombreEntidadSeleccionada === 'PERSONA')
                                        <div class="space-y-3">
                                            @include('livewire._partials._multi_select_condicion', [
                                                'opciones'      => $listaTiposCondicionPersonal ?? collect(),
                                                'seleccionados' => $condicionesPersonaSeleccionadas ?? [],
                                                'wireKey'       => 'condicionesPersonaSeleccionadas',
                                                'label'         => 'CONDICIÓN PERSONA',
                                                'placeholder'   => 'Buscar condición de persona...',
                                            ])
                                            <p class="text-xs font-bold text-red-600 dark:text-red-400 italic mt-1">
                                                * SIN SELECCIÓN = APLICA A TODAS LAS CONDICIONES DE PERSONA. Con selección, filtra solo las marcadas.
                                            </p>
                                            @error('condicionesPersonaSeleccionadas') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                    @endif
                                    
                                    {{-- TIPO DE EMPRESA LEGAL con Buscador (solo si entidad es EMPRESA) --}}
                                    @if ($nombreEntidadSeleccionada === 'EMPRESA')
                                        <div class="space-y-3">
                                            @include('livewire._partials._multi_select_condicion', [
                                                'opciones'      => $listaTiposEmpresaLegal ?? collect(),
                                                'seleccionados' => $tiposEmpresaLegalSeleccionados ?? [],
                                                'wireKey'       => 'tiposEmpresaLegalSeleccionados',
                                                'label'         => 'TIPOS DE EMPRESA LEGAL',
                                                'placeholder'   => 'Buscar tipo de empresa legal...',
                                            ])
                                            <p class="text-xs font-bold text-red-600 dark:text-red-400 italic mt-1">
                                                * SIN SELECCIÓN = APLICA A TODOS LOS TIPOS. Con selección, filtra solo los marcados.
                                            </p>
                                            @error('tiposEmpresaLegalSeleccionados') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                    @endif

                                </div>

                                @if ($nombreEntidadSeleccionada === 'PERSONA')
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <!-- Selector de Cargos (Multi-Select con Buscador) -->
                                        @php
                                            $cargosParaMultiSelect = isset($listaCargosMandante)
                                                ? $listaCargosMandante->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre_cargo])
                                                : collect();
                                        @endphp
                                        <div class="space-y-3">
                                            @include('livewire._partials._multi_select_condicion', [
                                                'opciones'      => $cargosParaMultiSelect,
                                                'seleccionados' => $cargosSeleccionados ?? [],
                                                'wireKey'       => 'cargosSeleccionados',
                                                'label'         => 'CARGOS ESPECÍFICOS',
                                                'placeholder'   => 'Buscar cargo...',
                                            ])
                                            <p class="text-xs font-bold text-red-600 dark:text-red-400 italic mt-1">
                                                * SIN SELECCIÓN = APLICA A TODOS LOS CARGOS. Con selección, filtra solo los marcados.
                                            </p>
                                            @error('cargosSeleccionados') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>

                                        <!-- Selector de Nacionalidades (Multi-Select con Buscador) -->
                                        <div class="space-y-3">
                                            @include('livewire._partials._multi_select_condicion', [
                                                'opciones'      => $listaNacionalidades ?? collect(),
                                                'seleccionados' => $nacionalidadesSeleccionadas ?? [],
                                                'wireKey'       => 'nacionalidadesSeleccionadas',
                                                'label'         => 'NACIONALIDADES',
                                                'placeholder'   => 'Buscar nacionalidad...',
                                            ])
                                            <p class="text-xs font-bold text-red-600 dark:text-red-400 italic mt-1">
                                                * SIN SELECCIÓN = APLICA A TODAS LAS NACIONALIDADES. Con selección, filtra solo las marcadas.
                                            </p>
                                            @error('nacionalidadesSeleccionadas') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>

                                        <!-- Selector de Tipos de Permanencia (Multi-Select con Buscador) -->
                                        <div class="space-y-3">
                                            @include('livewire._partials._multi_select_condicion', [
                                                'opciones'      => $listaTiposPermanencia ?? collect(),
                                                'seleccionados' => $tiposPermanenciaSeleccionados ?? [],
                                                'wireKey'       => 'tiposPermanenciaSeleccionados',
                                                'label'         => 'TIPOS DE PERMANENCIA',
                                                'placeholder'   => 'Buscar tipo de permanencia...',
                                            ])
                                            <p class="text-xs font-bold text-red-600 dark:text-red-400 italic mt-1">
                                                * SIN SELECCIÓN = APLICA A TODOS LOS TIPOS DE PERMANENCIA. Con selección, filtra solo los marcados.
                                            </p>
                                            @error('tiposPermanenciaSeleccionados') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                @endif

                                @if ($nombreEntidadSeleccionada === 'VEHICULO')
                                    <div class="space-y-4">
                                        <!-- Selector de Vehículos -->
                                        <div class="space-y-3">
                                            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700/50 p-2 rounded-t-lg border-b border-gray-200 dark:border-gray-600">
                                                <div class="flex items-center space-x-2">
                                                    <label class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Tipos de Vehículo</label>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-700/50">
                                                        {{ count($tiposVehiculoSeleccionados) }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center space-x-1">
                                                    <button type="button" wire:click="seleccionarTodosLosTiposVehiculo" class="p-1 hover:bg-white dark:hover:bg-gray-600 rounded-md transition-all text-gray-500 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400" title="Marcar Todos" @if(!isset($listaTiposVehiculo) || $listaTiposVehiculo->isEmpty()) disabled @endif>
                                                        <x-icons.check-circle class="w-5 h-5"/>
                                                    </button>
                                                    <button type="button" wire:click="quitarSeleccionDeTiposVehiculo" class="p-1 hover:bg-white dark:hover:bg-gray-600 rounded-md transition-all text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400" title="Desmarcar Todos" @if(empty($tiposVehiculoSeleccionados)) disabled @endif>
                                                        <x-icons.x-circle class="w-5 h-5"/>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="h-48 overflow-y-auto border border-t-0 border-gray-200 dark:border-gray-600 rounded-b-lg p-3 bg-white dark:bg-gray-900/30 shadow-sm custom-scrollbar">
                                                @if(isset($listaTiposVehiculo) && $listaTiposVehiculo->isNotEmpty())
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1">
                                                        @foreach ($listaTiposVehiculo as $vehiculo)
                                                            <label class="inline-flex items-center text-xs cursor-pointer hover:bg-amber-50 dark:hover:bg-amber-900/20 p-1.5 rounded-md transition-colors group">
                                                                <input type="checkbox" wire:model.live="tiposVehiculoSeleccionados" value="{{ $vehiculo->id }}" class="rounded border-gray-300 text-amber-600 shadow-sm focus:border-amber-300 focus:ring focus:ring-amber-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600">
                                                                <span class="ml-2 text-gray-600 dark:text-gray-300 group-hover:text-amber-700 dark:group-hover:text-amber-400 transition-colors">{{ $vehiculo->nombre }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="flex flex-col items-center justify-center h-full text-gray-400 dark:text-gray-500 italic space-y-2">
                                                        <x-icons.information-circle class="w-8 h-8 opacity-20"/>
                                                        <p class="text-[11px]">No hay tipos de vehículo disponibles</p>
                                                    </div>
                                                @endif
                                            </div>
                                            <p class="text-xs font-bold text-red-600 dark:text-red-400 italic mt-3">
                                                * SIN SELECCIÓN = APLICA A TODOS LOS TIPOS DE VEHÍCULO. Con selección, filtra solo los marcados.
                                            </p>
                                            @error('tiposVehiculoSeleccionados') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>

                                        @if (!empty($mandante_id))
                                            <div class="border border-amber-200 dark:border-amber-800 rounded-lg p-4 bg-amber-50 dark:bg-amber-900/20 mt-4">
                                                <div class="flex justify-between items-center mb-3">
                                                    <label class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Sub-Tipos de Vehículo (por Principal)</label>
                                                    <div class="flex gap-1">
                                                        <button type="button" wire:click="seleccionarTodosLosSubTiposVehiculo"
                                                                class="p-1 hover:bg-white dark:hover:bg-gray-600 rounded-md transition-all text-gray-500 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400"
                                                                title="Marcar Todos"
                                                                @if(empty($listaSubTiposVehiculoMandante) || (is_object($listaSubTiposVehiculoMandante) && $listaSubTiposVehiculoMandante->isEmpty())) disabled @endif>
                                                            <x-icons.check class="w-4 h-4"/>
                                                        </button>
                                                        <button type="button" wire:click="quitarSeleccionDeSubTiposVehiculo"
                                                                class="p-1 hover:bg-white dark:hover:bg-gray-600 rounded-md transition-all text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400"
                                                                title="Desmarcar Todos"
                                                                @if(empty($subTiposVehiculoSeleccionados)) disabled @endif>
                                                            <x-icons.x-mark class="w-4 h-4"/>
                                                        </button>
                                                        <span class="text-xs text-amber-600 dark:text-amber-400 font-semibold self-center ml-1">
                                                            {{ count($subTiposVehiculoSeleccionados) }} selec.
                                                        </span>
                                                    </div>
                                                </div>
                                                @if(!empty($listaSubTiposVehiculoMandante) && (is_object($listaSubTiposVehiculoMandante) ? $listaSubTiposVehiculoMandante->isNotEmpty() : count($listaSubTiposVehiculoMandante) > 0))
                                                    <div class="max-h-40 overflow-y-auto space-y-1 pr-1">
                                                        @foreach ($listaSubTiposVehiculoMandante as $subTipo)
                                                            <label class="flex items-center group cursor-pointer p-1 rounded hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-colors">
                                                                <input type="checkbox" wire:model.live="subTiposVehiculoSeleccionados" value="{{ $subTipo->id }}"
                                                                       class="rounded border-gray-300 text-amber-600 shadow-sm focus:border-amber-300 focus:ring focus:ring-amber-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600">
                                                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-300 group-hover:text-amber-700 dark:group-hover:text-amber-400 transition-colors">
                                                                    {{ $subTipo->nombre }}
                                                                    @if($subTipo->tipoVehiculo)
                                                                        <span class="text-xs text-gray-400">({{ $subTipo->tipoVehiculo->nombre }})</span>
                                                                    @endif
                                                                </span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <p class="text-[11px] text-gray-400 dark:text-gray-500 italic">No hay sub-tipos de vehículo definidos para esta Principal. Créelos en "Sub-Tipos de Vehículo".</p>
                                                @endif
                                                <p class="text-xs font-bold text-red-600 dark:text-red-400 italic mt-4">
                                                    * SIN SELECCIÓN = APLICA A TODOS LOS SUB-TIPOS DE VEHÍCULO. Con selección, filtra solo los marcados.
                                                </p>
                                                @error('subTiposVehiculoSeleccionados') <span class="error-message">{{ $message }}</span> @enderror
                                            </div>
                                        @endif

                                        {{-- CONDICIÓN VEHÍCULO --}}
                                        <div class="space-y-3 pt-4">
                                            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700/50 p-2 rounded-t-lg border-b border-gray-200 dark:border-gray-600">
                                                <div class="flex items-center space-x-2">
                                                    <label class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Condiciones de Vehículo</label>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-700/50">
                                                        {{ count($condicionesVehiculoSeleccionadas) }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center space-x-1">
                                                    <button type="button" wire:click="seleccionarTodasLasCondicionesVehiculo" class="p-1 hover:bg-white dark:hover:bg-gray-600 rounded-md transition-all text-gray-500 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400" title="Marcar Todas" @if(!isset($listaTiposCondicionVehiculo) || $listaTiposCondicionVehiculo->isEmpty()) disabled @endif>
                                                        <x-icons.check-circle class="w-5 h-5"/>
                                                    </button>
                                                    <button type="button" wire:click="quitarSeleccionDeCondicionesVehiculo" class="p-1 hover:bg-white dark:hover:bg-gray-600 rounded-md transition-all text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400" title="Desmarcar Todas" @if(empty($condicionesVehiculoSeleccionadas)) disabled @endif>
                                                        <x-icons.x-circle class="w-5 h-5"/>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="h-48 overflow-y-auto border border-t-0 border-gray-200 dark:border-gray-600 rounded-b-lg p-3 bg-white dark:bg-gray-900/30 shadow-sm custom-scrollbar">
                                                @if(isset($listaTiposCondicionVehiculo) && $listaTiposCondicionVehiculo->isNotEmpty())
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1">
                                                        @foreach ($listaTiposCondicionVehiculo as $condicion)
                                                            <label class="inline-flex items-center text-xs cursor-pointer hover:bg-amber-50 dark:hover:bg-amber-900/20 p-1.5 rounded-md transition-colors group">
                                                                <input type="checkbox" wire:model.live="condicionesVehiculoSeleccionadas" value="{{ $condicion->id }}" class="rounded border-gray-300 text-amber-600 shadow-sm focus:border-amber-300 focus:ring focus:ring-amber-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600">
                                                                <span class="ml-2 text-gray-600 dark:text-gray-300 group-hover:text-amber-700 dark:group-hover:text-amber-400 transition-colors">{{ $condicion->nombre }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="flex flex-col items-center justify-center h-full text-gray-400 dark:text-gray-500 italic space-y-2">
                                                        <x-icons.information-circle class="w-8 h-8 opacity-20"/>
                                                        <p class="text-[11px]">No hay condiciones de vehículo disponibles</p>
                                                    </div>
                                                @endif
                                            </div>
                                            <p class="text-xs font-bold text-red-600 dark:text-red-400 italic mt-3">
                                                * SIN SELECCIÓN = APLICA A TODAS LAS CONDICIONES DE VEHÍCULO. Con selección, filtra solo las marcadas.
                                            </p>
                                            @error('condicionesVehiculoSeleccionadas') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    </div>
                                @endif
                                
                                @if ($nombreEntidadSeleccionada === 'MAQUINARIA')
                                    <div class="space-y-4">
                                        <!-- Selector de Maquinaria -->
                                        <div class="space-y-3">
                                            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700/50 p-2 rounded-t-lg border-b border-gray-200 dark:border-gray-600">
                                                <div class="flex items-center space-x-2">
                                                    <label class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Tipos de Maquinaria</label>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-700/50">
                                                        {{ count($tiposMaquinariaSeleccionados) }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center space-x-1">
                                                    <button type="button" wire:click="seleccionarTodosLosTiposMaquinaria" class="p-1 hover:bg-white dark:hover:bg-gray-600 rounded-md transition-all text-gray-500 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400" title="Marcar Todas" @if(!isset($listaTiposMaquinaria) || $listaTiposMaquinaria->isEmpty()) disabled @endif>
                                                        <x-icons.check-circle class="w-5 h-5"/>
                                                    </button>
                                                    <button type="button" wire:click="quitarSeleccionDeTiposMaquinaria" class="p-1 hover:bg-white dark:hover:bg-gray-600 rounded-md transition-all text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400" title="Desmarcar Todas" @if(empty($tiposMaquinariaSeleccionados)) disabled @endif>
                                                        <x-icons.x-circle class="w-5 h-5"/>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="h-48 overflow-y-auto border border-t-0 border-gray-200 dark:border-gray-600 rounded-b-lg p-3 bg-white dark:bg-gray-900/30 shadow-sm custom-scrollbar">
                                                @if(isset($listaTiposMaquinaria) && $listaTiposMaquinaria->isNotEmpty())
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1">
                                                        @foreach ($listaTiposMaquinaria as $maquinaria)
                                                            <label class="inline-flex items-center text-xs cursor-pointer hover:bg-amber-50 dark:hover:bg-amber-900/20 p-1.5 rounded-md transition-colors group">
                                                                <input type="checkbox" wire:model.live="tiposMaquinariaSeleccionados" value="{{ $maquinaria->id }}" class="rounded border-gray-300 text-amber-600 shadow-sm focus:border-amber-300 focus:ring focus:ring-amber-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600">
                                                                <span class="ml-2 text-gray-600 dark:text-gray-300 group-hover:text-amber-700 dark:group-hover:text-amber-400 transition-colors">{{ $maquinaria->nombre }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="flex flex-col items-center justify-center h-full text-gray-400 dark:text-gray-500 italic space-y-2">
                                                        <x-icons.information-circle class="w-8 h-8 opacity-20"/>
                                                        <p class="text-[11px]">No hay tipos de maquinaria disponibles</p>
                                                    </div>
                                                @endif
                                            </div>
                                            <p class="text-xs font-bold text-red-600 dark:text-red-400 italic mt-3">
                                                * SIN SELECCIÓN = APLICA A TODOS LOS TIPOS DE MAQUINARIA. Con selección, filtra solo las marcadas.
                                            </p>
                                            @error('tiposMaquinariaSeleccionados') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                @endif

                                @if ($nombreEntidadSeleccionada === 'EMBARCACION')
                                     <div class="space-y-4">
                                        <!-- Selector de Embarcaciones -->
                                        <div class="space-y-3">
                                            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700/50 p-2 rounded-t-lg border-b border-gray-200 dark:border-gray-600">
                                                <div class="flex items-center space-x-2">
                                                    <label class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Tipos de Embarcación</label>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-700/50">
                                                        {{ count($tiposEmbarcacionSeleccionados) }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center space-x-1">
                                                    <button type="button" wire:click="seleccionarTodosLosTiposEmbarcacion" class="p-1 hover:bg-white dark:hover:bg-gray-600 rounded-md transition-all text-gray-500 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400" title="Marcar Todas" @if(!isset($listaTiposEmbarcacion) || $listaTiposEmbarcacion->isEmpty()) disabled @endif>
                                                        <x-icons.check-circle class="w-5 h-5"/>
                                                    </button>
                                                    <button type="button" wire:click="quitarSeleccionDeTiposEmbarcacion" class="p-1 hover:bg-white dark:hover:bg-gray-600 rounded-md transition-all text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400" title="Desmarcar Todas" @if(empty($tiposEmbarcacionSeleccionados)) disabled @endif>
                                                        <x-icons.x-circle class="w-5 h-5"/>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="h-48 overflow-y-auto border border-t-0 border-gray-200 dark:border-gray-600 rounded-b-lg p-3 bg-white dark:bg-gray-900/30 shadow-sm custom-scrollbar">
                                                @if(isset($listaTiposEmbarcacion) && $listaTiposEmbarcacion->isNotEmpty())
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1">
                                                        @foreach ($listaTiposEmbarcacion as $embarcacion)
                                                            <label class="inline-flex items-center text-xs cursor-pointer hover:bg-amber-50 dark:hover:bg-amber-900/20 p-1.5 rounded-md transition-colors group">
                                                                <input type="checkbox" wire:model.live="tiposEmbarcacionSeleccionados" value="{{ $embarcacion->id }}" class="rounded border-gray-300 text-amber-600 shadow-sm focus:border-amber-300 focus:ring focus:ring-amber-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600">
                                                                <span class="ml-2 text-gray-600 dark:text-gray-300 group-hover:text-amber-700 dark:group-hover:text-amber-400 transition-colors">{{ $embarcacion->nombre }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="flex flex-col items-center justify-center h-full text-gray-400 dark:text-gray-500 italic space-y-2">
                                                        <x-icons.information-circle class="w-8 h-8 opacity-20"/>
                                                        <p class="text-[11px]">No hay tipos de embarcación disponibles</p>
                                                    </div>
                                                @endif
                                            </div>
                                            <p class="text-xs font-bold text-red-600 dark:text-red-400 italic mt-3">
                                                * SIN SELECCIÓN = APLICA A TODOS LOS TIPOS DE EMBARCACIÓN. Con selección, filtra solo las marcadas.
                                            </p>
                                            @error('tiposEmbarcacionSeleccionados') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                @endif
                                
                                @php
                                    $entidadesActivos = ['VEHICULO', 'MAQUINARIA', 'EMBARCACION'];
                                @endphp

                                @if (in_array($nombreEntidadSeleccionada, $entidadesActivos))
                                    <div class="space-y-4">
                                        <!-- Selector de Tenencias -->
                                        <div class="space-y-3">
                                            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700/50 p-2 rounded-t-lg border-b border-gray-200 dark:border-gray-600">
                                                <div class="flex items-center space-x-2">
                                                    <label class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Tipos de Tenencia</label>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-700/50">
                                                        {{ count($tenenciasSeleccionadas) }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center space-x-1">
                                                    <button type="button" wire:click="seleccionarTodasLasTenencias" class="p-1 hover:bg-white dark:hover:bg-gray-600 rounded-md transition-all text-gray-500 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400" title="Marcar Todas" @if(!isset($listaTenenciasVehiculo) || $listaTenenciasVehiculo->isEmpty()) disabled @endif>
                                                        <x-icons.check-circle class="w-5 h-5"/>
                                                    </button>
                                                    <button type="button" wire:click="quitarSeleccionDeTenencias" class="p-1 hover:bg-white dark:hover:bg-gray-600 rounded-md transition-all text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400" title="Desmarcar Todas" @if(empty($tenenciasSeleccionadas)) disabled @endif>
                                                        <x-icons.x-circle class="w-5 h-5"/>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="h-48 overflow-y-auto border border-t-0 border-gray-200 dark:border-gray-600 rounded-b-lg p-3 bg-white dark:bg-gray-900/30 shadow-sm custom-scrollbar">
                                                @if(isset($listaTenenciasVehiculo) && $listaTenenciasVehiculo->isNotEmpty())
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1">
                                                        @foreach ($listaTenenciasVehiculo as $tenencia)
                                                            <label class="inline-flex items-center text-xs cursor-pointer hover:bg-amber-50 dark:hover:bg-amber-900/20 p-1.5 rounded-md transition-colors group">
                                                                <input type="checkbox" wire:model.live="tenenciasSeleccionadas" value="{{ $tenencia->id }}" class="rounded border-gray-300 text-amber-600 shadow-sm focus:border-amber-300 focus:ring focus:ring-amber-200 focus:ring-opacity-50 dark:bg-gray-800 dark:border-gray-600">
                                                                <span class="ml-2 text-gray-600 dark:text-gray-300 group-hover:text-amber-700 dark:group-hover:text-amber-400 transition-colors">{{ $tenencia->nombre }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="flex flex-col items-center justify-center h-full text-gray-400 dark:text-gray-500 italic space-y-2">
                                                        <x-icons.information-circle class="w-8 h-8 opacity-20"/>
                                                        <p class="text-[11px]">No hay tipos de tenencia disponibles</p>
                                                    </div>
                                                @endif
                                            </div>
                                            <p class="text-xs font-bold text-red-600 dark:text-red-400 italic mt-3">
                                                * SIN SELECCIÓN = APLICA A TODAS LAS TENENCIAS. Con selección, filtra solo las marcadas.
                                            </p>
                                            @error('tenenciasSeleccionadas') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                @endif


                                @if ($nombreEntidadSeleccionada === 'PERSONA')
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end border-t border-gray-200 dark:border-gray-600 pt-4">
                                        <div> <label for="condicion_fecha_ingreso_id" class="label-generic">Opción de Fechas Ingreso</label> <select wire:model.live="condicion_fecha_ingreso_id" id="condicion_fecha_ingreso_id" class="input-field"> <option value="">Todas las fechas</option> @if(isset($listaCondicionesFechaIngreso)) @foreach ($listaCondicionesFechaIngreso as $condicion) <option value="{{ $condicion->id }}">{{ $condicion->nombre }}</option> @endforeach @endif </select> @error('condicion_fecha_ingreso_id') <span class="error-message">{{ $message }}</span> @enderror </div>
                                        <div @if(empty($condicion_fecha_ingreso_id)) style="display: none;" @endif>  <label for="fecha_comparacion_ingreso" class="label-generic">Fecha de Comparación</label> <input type="date" wire:model="fecha_comparacion_ingreso" id="fecha_comparacion_ingreso" class="input-field"> @error('fecha_comparacion_ingreso') <span class="error-message">{{ $message }}</span> @enderror </div>
                                    </div>
                                @endif
                                
                                <div class="border-t border-gray-200 dark:border-gray-600 pt-4"> 
                                    <div class="flex justify-between items-center mb-2"> 
                                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Unidades Operativas A LAS QUE APLICA <span class="text-red-500">*</span></p> 
                                        <button type="button" wire:click="agregarUnidadSeleccionada" class="btn-success-outline btn-sm" @if(empty($mandante_id)) disabled title="Seleccione un principal primero" @endif> <x-icons.plus class="w-4 h-4 mr-1"/> Añadir U.O. </button> 
                                    </div> 
                                    @if(is_array($unidadesSeleccionadas) && count($unidadesSeleccionadas) > 0) 
                                        @foreach ($unidadesSeleccionadas as $index => $unidadSet) 
                                        <div class="grid grid-cols-1 md:grid-cols-5 gap-x-6 gap-y-2 mb-3 p-3 border dark:border-gray-700 rounded-md items-end" wire:key="unidad-org-{{ $index }}"> 
                                            <div> <label for="uo_nivel1_id_{{ $index }}" class="label-generic text-xs">Nivel Principal</label> <select wire:model="unidadesSeleccionadas.{{ $index }}.uo_nivel1_id" wire:change="uoNivel1Changed({{ $index }}, $event.target.value)" id="uo_nivel1_id_{{ $index }}" class="input-field text-sm" @if(empty($mandante_id)) disabled @endif> <option value="">Seleccione...</option> @foreach($this->getNivel1Options($index) as $uo1) <option value="{{ $uo1['id'] }}">{{ $uo1['nombre_unidad'] }}</option> @endforeach </select> </div> 
                                            <div> <label for="uo_nivel2_id_{{ $index }}" class="label-generic text-xs">Nivel 2</label> <select wire:model="unidadesSeleccionadas.{{ $index }}.uo_nivel2_id" wire:change="uoNivel2Changed({{ $index }}, $event.target.value)" id="uo_nivel2_id_{{ $index }}" class="input-field text-sm" @if(empty($unidadesSeleccionadas[$index]['uo_nivel1_id'])) disabled @endif> <option value="">Seleccione...</option> @foreach($this->getNivel2Options($index) as $uo2) <option value="{{ $uo2['id'] }}">{{ $uo2['nombre_unidad'] }}</option> @endforeach </select> </div> 
                                            <div> <label for="uo_nivel3_id_{{ $index }}" class="label-generic text-xs">Nivel 3</label> <select wire:model="unidadesSeleccionadas.{{ $index }}.uo_nivel3_id" wire:change="uoNivel3Changed({{ $index }}, $event.target.value)" id="uo_nivel3_id_{{ $index }}" class="input-field text-sm" @if(empty($unidadesSeleccionadas[$index]['uo_nivel2_id'])) disabled @endif> <option value="">Seleccione...</option> @foreach($this->getNivel3Options($index) as $uo3) <option value="{{ $uo3['id'] }}">{{ $uo3['nombre_unidad'] }}</option> @endforeach </select> </div> 
                                            <div> <label for="uo_nivel4_id_{{ $index }}" class="label-generic text-xs">Nivel 4</label> <select wire:model="unidadesSeleccionadas.{{ $index }}.uo_nivel4_id" wire:change="uoNivel4Changed({{ $index }}, $event.target.value)" id="uo_nivel4_id_{{ $index }}" class="input-field text-sm" @if(empty($unidadesSeleccionadas[$index]['uo_nivel3_id'])) disabled @endif> <option value="">Seleccione...</option> @foreach($this->getNivel4Options($index) as $uo4) <option value="{{ $uo4['id'] }}">{{ $uo4['nombre_unidad'] }}</option> @endforeach </select> </div> 
                                            <div class="flex items-end"> @if(count($unidadesSeleccionadas) > 1) <button type="button" wire:click="eliminarUnidadSeleccionada({{ $index }})" class="btn-danger-outline btn-sm ml-auto"> <x-icons.trash class="w-4 h-4"/> </button> @endif </div> 
                                        </div> 
                                        @error('unidadesSeleccionadas.' . $index . '.final_uo_id') <span class="error-message mb-2 block">{{ $message }}</span> @enderror 
                                        @endforeach 
                                    @else 
                                        @if(!empty($mandante_id)) <p class="text-sm text-gray-500 dark:text-gray-400">Haga clic en "Añadir U.O." para seleccionar la primera Unidad Operativa.</p> 
                                        @else <p class="text-sm text-gray-500 dark:text-gray-400">Seleccione un Principal para habilitar la selección de Unidades Operativas.</p> @endif 
                                    @endif 
                                    @error('unidadesSeleccionadas') <span class="error-message mt-2 block">{{ $message }}</span> @enderror 
                                </div>

                                <div class="border-t border-gray-200 dark:border-gray-600 pt-4">
                                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">ADICIONALES DE AYUDA QUE VERÁ EL ANALISTA AL VALIDAR UN DOCUMENTO</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        
                                        <!-- Selector de Observación Documento -->
                                        @php
                                            $observacionesParaMultiSelect = isset($listaObservacionesDocumento)
                                                ? $listaObservacionesDocumento->map(fn($o) => ['id' => $o->id, 'nombre' => $o->titulo])
                                                : collect();
                                        @endphp
                                        <div class="space-y-3">
                                            @include('livewire._partials._multi_select_condicion', [
                                                'opciones'      => $observacionesParaMultiSelect,
                                                'seleccionados' => $observacionesSeleccionadas ?? [],
                                                'wireKey'       => 'observacionesSeleccionadas',
                                                'label'         => 'OBSERVACIONES DOCUMENTO',
                                                'placeholder'   => 'Buscar observación...',
                                            ])
                                            @error('observacionesSeleccionadas') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>

                                        <!-- Selector de Formato Documento -->
                                        <div class="space-y-3">
                                            @include('livewire._partials._multi_select_condicion', [
                                                'opciones'      => $listaFormatosDocumentoMuestra ?? collect(),
                                                'seleccionados' => $formatosSeleccionados ?? [],
                                                'wireKey'       => 'formatosSeleccionados',
                                                'label'         => 'FORMATOS DOCUMENTO',
                                                'placeholder'   => 'Buscar formato...',
                                            ])
                                            @error('formatosSeleccionados') <span class="error-message">{{ $message }}</span> @enderror
                                            
                                            <!-- Sección de Visualización de Formatos Seleccionados (opcional para mantener la funcionalidad de VER) -->
                                            @if(!empty($formatosSeleccionados) && isset($listaFormatosDocumentoMuestra))
                                                <div class="flex flex-wrap gap-2 mt-2">
                                                    @foreach($formatosSeleccionados as $fid)
                                                        @php
                                                            $fmt = $listaFormatosDocumentoMuestra->firstWhere('id', $fid);
                                                        @endphp
                                                        @if($fmt && !empty($fmt->ruta_archivo))
                                                            <a href="{{ Storage::url($fmt->ruta_archivo) }}" target="_blank" class="text-xs text-blue-600 hover:underline inline-flex items-center">
                                                                <x-icons.document-text class="w-3 h-3 mr-1"/> Ver {{ Str::limit($fmt->nombre, 15) }}
                                                            </a>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>

                                    </div>
                                    <div class="mt-6">
                                        <!-- Selector de Documento Relacionado -->
                                        @php
                                            $docRelacionadosValidos = isset($listaNombresDocumento) 
                                                ? $listaNombresDocumento->filter(fn($doc) => $doc->id != $this->nombre_documento_id)->values()
                                                : collect();
                                        @endphp
                                        <div class="space-y-3">
                                            @include('livewire._partials._multi_select_condicion', [
                                                'opciones'      => $docRelacionadosValidos,
                                                'seleccionados' => $documentosRelacionadosSeleccionados ?? [],
                                                'wireKey'       => 'documentosRelacionadosSeleccionados',
                                                'label'         => 'DOCUMENTOS RELACIONADOS (APOYO AL ANALISTA)',
                                                'placeholder'   => 'Buscar documento relacionado...',
                                            ])
                                            @error('documentosRelacionadosSeleccionados') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="border-t border-gray-200 dark:border-gray-600 pt-4 space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                                        <div>
                                            <label for="tipo_vencimiento_id" class="label-generic">TIPO DE VENCIMIENTO</label>
                                            <select wire:model.live="tipo_vencimiento_id" id="tipo_vencimiento_id" class="input-field">
                                                <option value="">Seleccione...</option>
                                                @if(isset($listaTiposVencimiento))
                                                    @foreach ($listaTiposVencimiento as $tipo)
                                                        <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @error('tipo_vencimiento_id') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        
                                        @php
                                            $tipoVencimientoSeleccionadoObj = null;
                                            if ($this->tipo_vencimiento_id && isset($listaTiposVencimiento) && $listaTiposVencimiento instanceof \Illuminate\Support\Collection) {
                                                $tipoVencimientoSeleccionadoObj = $listaTiposVencimiento->firstWhere('id', $this->tipo_vencimiento_id);
                                            }
                                            $nombresTiposVencimientoQueRequierenDias = ['DESDE CARGA', 'DESDE EMISION'];
                                            $mostrarDiasValidez = $tipoVencimientoSeleccionadoObj && in_array(strtoupper($tipoVencimientoSeleccionadoObj->nombre), $nombresTiposVencimientoQueRequierenDias);
                                            $mostrarDiasGracia = $tipoVencimientoSeleccionadoObj && strtoupper($tipoVencimientoSeleccionadoObj->nombre) === 'POR PERIODO';
                                        @endphp

                                        <div @if(!$mostrarDiasValidez) style="display: none;" @endif>
                                            <label for="dias_validez_documento" class="label-generic">Días Validez Documento</label>
                                            <input type="number" wire:model="dias_validez_documento" id="dias_validez_documento" class="input-field" min="0" placeholder="Ej: 30">
                                            @error('dias_validez_documento') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>

                                        <div @if(!$mostrarDiasGracia) style="display: none;" @endif>
                                            <label for="dias_gracia_carga" class="label-generic">Días de Gracia para Carga</label>
                                            <input type="number" wire:model="dias_gracia_carga" id="dias_gracia_carga" class="input-field" min="0" placeholder="Ej: 10">
                                            @error('dias_gracia_carga') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>

                                        <div>
                                            <label for="dias_aviso_vencimiento" class="label-generic">Días Aviso Vencimiento</label>
                                            <input type="number" wire:model="dias_aviso_vencimiento" id="dias_aviso_vencimiento" class="input-field" min="0" placeholder="Ej: 15">
                                            @error('dias_aviso_vencimiento') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="bg-indigo-50 dark:bg-indigo-900/20 p-2 rounded-lg border border-indigo-100 dark:border-indigo-800 mt-4">
                                            <label for="imc_meses_estimados" class="label-generic text-indigo-700 dark:text-indigo-400 font-bold flex items-center">
                                                <x-icons.chart-bar class="w-4 h-4 mr-1" />
                                                DURACIÓN MESES (PARA IMC)
                                            </label>
                                            <input type="number" wire:model="imc_meses_estimados" id="imc_meses_estimados" class="input-field border-indigo-200 focus:ring-indigo-500" min="0" step="0.5" placeholder="Ej: 6">
                                            <p class="text-[9px] text-indigo-500 mt-1 uppercase italic font-medium">Define la frecuencia estimada para el cálculo de carga.</p>
                                            @error('imc_meses_estimados') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="flex items-center">
                                            <input wire:model="valida_emision" id="valida_emision" type="checkbox" class="checkbox-generic">
                                            <label for="valida_emision" class="ml-2 label-generic">Valida Emisión</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input wire:model="valida_vencimiento" id="valida_vencimiento" type="checkbox" class="checkbox-generic">
                                            <label for="valida_vencimiento" class="ml-2 label-generic">Valida Vencimiento</label>
                                        </div>
                                    </div>
                                    
                                    <div class="border-t border-gray-200 dark:border-gray-600 pt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="flex items-center">
                                            <input wire:model.live="requiere_validacion_mandante" id="requiere_validacion_mandante" type="checkbox" class="checkbox-generic" @if($valida_solo_mandante) disabled @endif>
                                            <label for="requiere_validacion_mandante" class="ml-2 label-generic @if($valida_solo_mandante) opacity-50 @endif">Requiere Validación de Principal</label>
                                            @error('requiere_validacion_mandante') <span class="error-message ml-2">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="flex items-center">
                                            <input wire:model.live="valida_solo_mandante" id="valida_solo_mandante" type="checkbox" class="checkbox-generic text-purple-600 focus:ring-purple-500">
                                            <label for="valida_solo_mandante" class="ml-2 label-generic font-bold text-gray-700 dark:text-gray-300">Valida solo la Principal (Excluir ASEM)</label>
                                            <span class="ml-2 text-xs text-gray-500 italic" title="El documento entrará directamente a Pendiente Validación Mandante">ℹ️</span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <input wire:model="mostrar_historico_documento" id="mostrar_historico_documento" type="checkbox" class="checkbox-generic">
                                        <label for="mostrar_historico_documento" class="ml-2 label-generic">Mostrar Histórico</label>
                                    </div>
                                    
                                    @if($nombreEntidadSeleccionada === 'PERSONA')
                                        <div class="border-t border-gray-200 dark:border-gray-600 pt-4 mt-4">
                                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Opcionales Identidad Controlada Persona</p>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                                                <div class="flex items-center"> <input wire:model="permite_ver_nacionalidad_trabajador" id="permite_ver_nacionalidad_trabajador" type="checkbox" class="checkbox-generic"> <label for="permite_ver_nacionalidad_trabajador" class="ml-2 label-generic">Ver Nacionalidad</label> </div>
                                                <div class="flex items-center"> <input wire:model="permite_modificar_nacionalidad_trabajador" id="permite_modificar_nacionalidad_trabajador" type="checkbox" class="checkbox-generic"> <label for="permite_modificar_nacionalidad_trabajador" class="ml-2 label-generic">Modificar Nacionalidad</label> </div>
                                                <div class="flex items-center"> <input wire:model="permite_ver_fecha_nacimiento_trabajador" id="permite_ver_fecha_nacimiento_trabajador" type="checkbox" class="checkbox-generic"> <label for="permite_ver_fecha_nacimiento_trabajador" class="ml-2 label-generic">Ver Fecha de Nacimiento</label> </div>
                                                <div class="flex items-center"> <input wire:model="permite_modificar_fecha_nacimiento_trabajador" id="permite_modificar_fecha_nacimiento_trabajador" type="checkbox" class="checkbox-generic"> <label for="permite_modificar_fecha_nacimiento_trabajador" class="ml-2 label-generic">Modificar Fecha de Nacimiento</label> </div>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="flex items-center pt-4"> <input wire:model="is_active" id="is_active" type="checkbox" class="checkbox-generic"> <label for="is_active" class="ml-2 label-generic">Regla Activa</label> </div>
                                </div>

                                <div class="border-t border-gray-300 dark:border-gray-700 pt-4">
                                    <h4 class="text-md font-semibold mb-4 text-gray-800 dark:text-gray-200">Criterios de Evaluación (ASEM)</h4>
                                    @if(is_array($criterios))
                                        @foreach ($criterios as $index => $criterio)
                                            <div wire:key="{{ $criterio['temp_id'] }}">
                                                <div class="p-4 border dark:border-gray-600 rounded-md relative flex items-start gap-4">
                                                    <div class="flex-shrink-0 flex items-center justify-center h-8 w-8 bg-gray-100 dark:bg-gray-700 rounded-full text-gray-500 dark:text-gray-400 font-bold mt-5">
                                                        {{ $loop->iteration }}
                                                    </div>
                                                    <div class="flex-grow grid grid-cols-1 md:grid-cols-2 gap-4">
                                                        <div>
                                                            @include('livewire._partials._single_select_buscador', [
                                                                'opciones'      => $listaCriteriosEvaluacion ?? [],
                                                                'seleccionado'  => $criterios[$index]['criterio_evaluacion_id'] ?? null,
                                                                'wireKey'       => "criterios.{$index}.criterio_evaluacion_id",
                                                                'label'         => 'Criterio*',
                                                                'placeholder'   => 'Seleccione...',
                                                            ])
                                                            @error("criterios.$index.criterio_evaluacion_id") <span class="error-message mt-1 block">{{ $message }}</span> @enderror
                                                            {{-- ==========================================
                                                             TABLA DINÁMICA DE SUB-CRITERIOS CONDICIONALES
                                                             Cada fila = 1 EPP con su condición opcional
                                                             NULL en condición = Universal (siempre aplica)
                                                             ========================================== --}}
                                                        <div class="space-y-2 mt-4">
                                                            <div class="flex items-center justify-between">
                                                                <label class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Sub-Criterios</label>
                                                                <button type="button"
                                                                    wire:click="agregarSubCriterioACriterio({{ $index }})"
                                                                    class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-md bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700 hover:bg-emerald-100 dark:hover:bg-emerald-800/40 transition-colors">
                                                                    <x-icons.plus class="w-3 h-3 mr-1"/> SUBCRITERIO
                                                                </button>
                                                            </div>

                                                            {{-- Cabecera de columnas --}}
                                                            @if(!empty($criterios[$index]['sub_criterios_config']))
                                                            <div class="grid grid-cols-12 gap-1 px-1 pb-1">
                                                                <div class="col-span-4 text-[9px] font-bold text-gray-400 dark:text-gray-500 uppercase">Sub-criterio</div>
                                                                <div class="col-span-4 text-[9px] font-bold text-gray-400 dark:text-gray-500 uppercase">Cond. Trabajador</div>
                                                                <div class="col-span-3 text-[9px] font-bold text-gray-400 dark:text-gray-500 uppercase">Cond. Empresa</div>
                                                                <div class="col-span-1"></div>
                                                            </div>
                                                            @endif

                                                            {{-- Filas de EPP --}}
                                                            <div class="space-y-1 max-h-52 overflow-y-auto custom-scrollbar pr-1">
                                                                @forelse($criterios[$index]['sub_criterios_config'] ?? [] as $scIndex => $scConfig)
                                                                <div wire:key="criterio-{{ $index }}-sc-{{ $scIndex }}" class="grid grid-cols-12 gap-1 items-center bg-gray-50 dark:bg-gray-700/40 rounded-md p-1.5 border border-gray-200 dark:border-gray-600/50">
                                                                    {{-- EPP selector --}}
                                                                    <div class="col-span-4">
                                                                        <select wire:model.live="criterios.{{ $index }}.sub_criterios_config.{{ $scIndex }}.sub_criterio_id"
                                                                            class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 py-1 focus:ring-emerald-500 focus:border-emerald-500">
                                                                            <option value="">-- Seleccione --</option>
                                                                            @if(isset($listaSubCriterios))
                                                                                @foreach($listaSubCriterios as $sc)
                                                                                    <option value="{{ $sc->id }}">{{ $sc->nombre }}</option>
                                                                                @endforeach
                                                                            @endif
                                                                        </select>
                                                                        @error("criterios.$index.sub_criterios_config.$scIndex.sub_criterio_id") <span class="text-[9px] text-red-500">{{ $message }}</span> @enderror
                                                                    </div>
                                                                    {{-- Condición trabajador --}}
                                                                    <div class="col-span-4">
                                                                        <select wire:model.live="criterios.{{ $index }}.sub_criterios_config.{{ $scIndex }}.cond_personal_id"
                                                                            class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 py-1 focus:ring-blue-500 focus:border-blue-500">
                                                                            <option value="">Universal</option>
                                                                            @if(isset($listaTiposCondicionPersonal))
                                                                                @foreach($listaTiposCondicionPersonal as $cp)
                                                                                    <option value="{{ $cp->id }}">{{ $cp->nombre }}</option>
                                                                                @endforeach
                                                                            @endif
                                                                        </select>
                                                                    </div>
                                                                    {{-- Condición empresa --}}
                                                                    <div class="col-span-3">
                                                                        <select wire:model.live="criterios.{{ $index }}.sub_criterios_config.{{ $scIndex }}.cond_empresa_id"
                                                                            class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 py-1 focus:ring-amber-500 focus:border-amber-500">
                                                                            <option value="">Universal</option>
                                                                            @if(isset($listaTiposCondicionEmpresa))
                                                                                @foreach($listaTiposCondicionEmpresa as $ce)
                                                                                    <option value="{{ $ce->id }}">{{ $ce->nombre }}</option>
                                                                                @endforeach
                                                                            @endif
                                                                        </select>
                                                                    </div>
                                                                    {{-- Eliminar fila --}}
                                                                    <div class="col-span-1 flex justify-center">
                                                                        <button type="button"
                                                                            wire:click="eliminarSubCriterioACriterio({{ $index }}, {{ $scIndex }})"
                                                                            class="p-0.5 rounded text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors"
                                                                            title="Quitar">
                                                                            <x-icons.x-mark class="w-3.5 h-3.5"/>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                @empty
                                                                <p class="text-[10px] text-gray-400 dark:text-gray-500 italic text-center py-2">Sin sub-criterios configurados. Haga clic en "+ SUBCRITERIO" para agregar.</p>
                                                                @endforelse
                                                            </div>
                                                            <p class="text-[9px] text-gray-400 dark:text-gray-500 italic mt-1">
                                                                Condición "Universal" = aplica a todos. Seleccione condición para filtrar dinámicamente por trabajador/empresa.
                                                            </p>
                                                        </div>
                                                        </div>
                                                        <div>
                                                            @include('livewire._partials._single_select_buscador', [
                                                                'opciones'      => $listaTextosRechazo ?? [],
                                                                'seleccionado'  => $criterios[$index]['texto_rechazo_id'] ?? null,
                                                                'wireKey'       => "criterios.{$index}.texto_rechazo_id",
                                                                'label'         => 'Texto Rechazo',
                                                                'placeholder'   => 'Seleccione...',
                                                            ])
                                                            @error("criterios.$index.texto_rechazo_id") <span class="error-message mt-1 block">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div>
                                                            @include('livewire._partials._single_select_buscador', [
                                                                'opciones'      => $listaAclaracionesCriterio ?? [],
                                                                'seleccionado'  => $criterios[$index]['aclaracion_criterio_id'] ?? null,
                                                                'wireKey'       => "criterios.{$index}.aclaracion_criterio_id",
                                                                'label'         => 'Aclaración Criterio',
                                                                'placeholder'   => 'Seleccione...',
                                                            ])
                                                            @error("criterios.$index.aclaracion_criterio_id") <span class="error-message mt-1 block">{{ $message }}</span> @enderror
                                                        </div>
                                                    </div>
                                                    <div class="absolute top-2 right-2">
                                                        @if (count($criterios) > 1)
                                                            <button type="button" wire:click="eliminarCriterio('{{ $criterio['temp_id'] }}')" class="btn-danger-outline btn-sm p-1">
                                                                <x-icons.trash class="w-4 h-4"/>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                                @if (!$loop->last)
                                                    <hr class="my-4 border-gray-200 dark:border-gray-600">
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                    <button type="button" wire:click="agregarCriterio" class="btn-secondary btn-sm mt-4">
                                        <x-icons.plus class="w-4 h-4 mr-1"/>
                                        Agregar Criterio ASEM
                                    </button>
                                </div>
                                
                                @if($requiere_validacion_mandante)
                                <div class="border-t border-gray-300 dark:border-gray-700 pt-4 mt-6 animate-fade-in">
                                    <h4 class="text-md font-semibold mb-2 text-indigo-800 dark:text-indigo-200">Criterios de Evaluación (PRINCIPAL)</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Estos criterios solo serán visibles y aplicados por un usuario del principal después de que ASEM haya pre-aprobado el documento.</p>
                                    @if(is_array($criteriosMandante))
                                        @foreach ($criteriosMandante as $index => $criterio)
                                            <div wire:key="mandante-{{ $criterio['temp_id'] }}">
                                                <div class="p-4 border border-indigo-200 dark:border-indigo-700 rounded-md bg-indigo-50 dark:bg-indigo-900/20 relative flex items-start gap-4">
                                                    <div class="flex-shrink-0 flex items-center justify-center h-8 w-8 bg-indigo-100 dark:bg-indigo-800 rounded-full text-indigo-600 dark:text-indigo-300 font-bold mt-5">
                                                        {{ $loop->iteration }}
                                                    </div>
                                                    <div class="flex-grow grid grid-cols-1 md:grid-cols-2 gap-4">
                                                        <div>
                                                            @include('livewire._partials._single_select_buscador', [
                                                                'opciones'      => $listaCriteriosEvaluacion ?? [],
                                                                'seleccionado'  => $criteriosMandante[$index]['criterio_evaluacion_id'] ?? null,
                                                                'wireKey'       => "criteriosMandante.{$index}.criterio_evaluacion_id",
                                                                'label'         => 'Criterio*',
                                                                'placeholder'   => 'Seleccione...',
                                                            ])
                                                            @error("criteriosMandante.$index.criterio_evaluacion_id") <span class="error-message mt-1 block">{{ $message }}</span> @enderror
                                                            {{-- ==========================================
                                                             TABLA DINÁMICA DE SUB-CRITERIOS CONDICIONALES (MANDANTE)
                                                             ========================================== --}}
                                                        <div class="space-y-2 mt-4">
                                                            <div class="flex items-center justify-between">
                                                                <label class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Sub-Criterios (Principal)</label>
                                                                <button type="button"
                                                                    wire:click="agregarSubCriterioACriterioMandante({{ $index }})"
                                                                    class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-md bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700 hover:bg-indigo-100 dark:hover:bg-indigo-800/40 transition-colors">
                                                                    <x-icons.plus class="w-3 h-3 mr-1"/> SUBCRITERIO
                                                                </button>
                                                            </div>

                                                            @if(!empty($criteriosMandante[$index]['sub_criterios_config']))
                                                            <div class="grid grid-cols-12 gap-1 px-1 pb-1">
                                                                <div class="col-span-4 text-[9px] font-bold text-indigo-400 uppercase">Sub-criterio</div>
                                                                <div class="col-span-4 text-[9px] font-bold text-indigo-400 uppercase">Cond. Trabajador</div>
                                                                <div class="col-span-3 text-[9px] font-bold text-indigo-400 uppercase">Cond. Empresa</div>
                                                                <div class="col-span-1"></div>
                                                            </div>
                                                            @endif

                                                            <div class="space-y-1 max-h-52 overflow-y-auto custom-scrollbar pr-1">
                                                                @forelse($criteriosMandante[$index]['sub_criterios_config'] ?? [] as $scIndex => $scConfig)
                                                                <div wire:key="mandante-criterio-{{ $index }}-sc-{{ $scIndex }}" class="grid grid-cols-12 gap-1 items-center bg-indigo-50/60 dark:bg-indigo-900/20 rounded-md p-1.5 border border-indigo-200 dark:border-indigo-700/50">
                                                                    <div class="col-span-4">
                                                                        <select wire:model.live="criteriosMandante.{{ $index }}.sub_criterios_config.{{ $scIndex }}.sub_criterio_id"
                                                                            class="w-full text-xs rounded border-indigo-300 dark:border-indigo-600 dark:bg-gray-800 dark:text-gray-200 py-1 focus:ring-indigo-500 focus:border-indigo-500">
                                                                            <option value="">-- Seleccione --</option>
                                                                            @if(isset($listaSubCriterios))
                                                                                @foreach($listaSubCriterios as $sc)
                                                                                    <option value="{{ $sc->id }}">{{ $sc->nombre }}</option>
                                                                                @endforeach
                                                                            @endif
                                                                        </select>
                                                                        @error("criteriosMandante.$index.sub_criterios_config.$scIndex.sub_criterio_id") <span class="text-[9px] text-red-500">{{ $message }}</span> @enderror
                                                                    </div>
                                                                    <div class="col-span-4">
                                                                        <select wire:model.live="criteriosMandante.{{ $index }}.sub_criterios_config.{{ $scIndex }}.cond_personal_id"
                                                                            class="w-full text-xs rounded border-indigo-300 dark:border-indigo-600 dark:bg-gray-800 dark:text-gray-200 py-1 focus:ring-indigo-500">
                                                                            <option value="">Universal</option>
                                                                            @if(isset($listaTiposCondicionPersonal))
                                                                                @foreach($listaTiposCondicionPersonal as $cp)
                                                                                    <option value="{{ $cp->id }}">{{ $cp->nombre }}</option>
                                                                                @endforeach
                                                                            @endif
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-span-3">
                                                                        <select wire:model.live="criteriosMandante.{{ $index }}.sub_criterios_config.{{ $scIndex }}.cond_empresa_id"
                                                                            class="w-full text-xs rounded border-indigo-300 dark:border-indigo-600 dark:bg-gray-800 dark:text-gray-200 py-1 focus:ring-indigo-500">
                                                                            <option value="">Universal</option>
                                                                            @if(isset($listaTiposCondicionEmpresa))
                                                                                @foreach($listaTiposCondicionEmpresa as $ce)
                                                                                    <option value="{{ $ce->id }}">{{ $ce->nombre }}</option>
                                                                                @endforeach
                                                                            @endif
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-span-1 flex justify-center">
                                                                        <button type="button"
                                                                            wire:click="eliminarSubCriterioACriterioMandante({{ $index }}, {{ $scIndex }})"
                                                                            class="p-0.5 rounded text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors"
                                                                            title="Quitar">
                                                                            <x-icons.x-mark class="w-3.5 h-3.5"/>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                @empty
                                                                <p class="text-[10px] text-indigo-400 dark:text-indigo-500 italic text-center py-2">Sin sub-criterios configurados. Haga clic en "+ SUBCRITERIO" para agregar.</p>
                                                                @endforelse
                                                            </div>
                                                            <p class="text-[9px] text-indigo-400 italic mt-1">
                                                                Condición "Universal" = aplica a todos. Seleccione condición para filtrar dinámicamente.
                                                            </p>
                                                        </div>
                                                        </div>
                                                        <div>
                                                            @include('livewire._partials._single_select_buscador', [
                                                                'opciones'      => $listaTextosRechazo ?? [],
                                                                'seleccionado'  => $criteriosMandante[$index]['texto_rechazo_id'] ?? null,
                                                                'wireKey'       => "criteriosMandante.{$index}.texto_rechazo_id",
                                                                'label'         => 'Texto Rechazo',
                                                                'placeholder'   => 'Seleccione...',
                                                            ])
                                                            @error("criteriosMandante.$index.texto_rechazo_id") <span class="error-message mt-1 block">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div>
                                                            @include('livewire._partials._single_select_buscador', [
                                                                'opciones'      => $listaAclaracionesCriterio ?? [],
                                                                'seleccionado'  => $criteriosMandante[$index]['aclaracion_criterio_id'] ?? null,
                                                                'wireKey'       => "criteriosMandante.{$index}.aclaracion_criterio_id",
                                                                'label'         => 'Aclaración Criterio',
                                                                'placeholder'   => 'Seleccione...',
                                                            ])
                                                            @error("criteriosMandante.$index.aclaracion_criterio_id") <span class="error-message mt-1 block">{{ $message }}</span> @enderror
                                                        </div>
                                                    </div>
                                                    <div class="absolute top-2 right-2">
                                                        @if (count($criteriosMandante) > 1)
                                                            <button type="button" wire:click="eliminarCriterioMandante('{{ $criterio['temp_id'] }}')" class="btn-danger-outline btn-sm p-1">
                                                                <x-icons.trash class="w-4 h-4"/>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                                @if (!$loop->last)
                                                    <hr class="my-4 border-indigo-200 dark:border-indigo-700">
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                    <button type="button" wire:click="agregarCriterioMandante" class="btn-secondary btn-sm mt-4">
                                        <x-icons.plus class="w-4 h-4 mr-1"/>
                                        Agregar Criterio Principal
                                    </button>
                                    @error('criteriosMandante') <span class="error-message mt-2 block">{{ $message }}</span> @enderror
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="btn-primary sm:ml-3 sm:w-auto" wire:loading.attr="disabled" wire:target="{{ $modoEdicion ? 'update' : 'store' }}"> <span wire:loading.remove wire:target="{{ $modoEdicion ? 'update' : 'store' }}"> {{ $modoEdicion ? 'Actualizar Regla' : 'Guardar Regla' }} </span> <span wire:loading wire:target="{{ $modoEdicion ? 'update' : 'store' }}"> <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"> <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle> <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path> </svg> {{ $modoEdicion ? 'Actualizando...' : 'Guardando...' }} </span> </button>
                            <button type="button" wire:click="closeModal()" class="btn-secondary-outline mt-3 sm:mt-0 sm:w-auto"> Cancelar </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($showConfirmDeleteModal) 
    <div class="fixed z-20 inset-0 overflow-y-auto" aria-labelledby="modal-title-delete" role="dialog" aria-modal="true"> 
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"> 
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-80" aria-hidden="true" wire:click="$set('showConfirmDeleteModal', false)"></div> 
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span> 
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"> 
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4"> 
                    <div class="sm:flex sm:items-start"> 
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-700 sm:mx-0 sm:h-10 sm:w-10"> 
                            <x-icons.warning class="h-6 w-6 text-red-600 dark:text-red-200"/> 
                        </div> 
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left"> 
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title-delete"> Confirmar Eliminación </h3> 
                            <div class="mt-2"> 
                                <p class="text-sm text-gray-500 dark:text-gray-300"> ¿Está seguro de que desea eliminar la regla: "<strong>{{ $nombreReglaParaEliminar }}</strong>"? </p> 
                                <p class="text-sm text-red-500 dark:text-red-400 mt-2"> Esta acción no se puede deshacer. Todos los criterios y asociaciones con Unidades Operativas, cargos y nacionalidades también serán eliminados. </p> 
                            </div> 
                        </div> 
                    </div> 
                </div> 
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse"> 
                    <button wire:click="deleteRegla()" type="button" class="btn-danger sm:ml-3 sm:w-auto" wire:loading.attr="disabled" wire:target="deleteRegla"> 
                        <span wire:loading.remove wire:target="deleteRegla"> Eliminar Definitivamente </span> 
                        <span wire:loading wire:target="deleteRegla"> <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"> <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle> <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path> </svg> Eliminando... </span> 
                    </button> 
                    <button wire:click="$set('showConfirmDeleteModal', false)" type="button" class="btn-secondary-outline mt-3 sm:mt-0 sm:w-auto"> Cancelar </button> 
                </div> 
            </div> 
        </div> 
    </div> 
    @endif

    @if ($showHistoryModal)
    <div class="fixed z-[130] inset-0 overflow-y-auto" aria-labelledby="modal-title-history" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-80" aria-hidden="true" wire:click="$set('showHistoryModal', false)"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                            <x-icons.history class="h-6 w-6 text-blue-600 dark:text-blue-300"/>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title-history">
                                    Historial de Cambios: <span class="font-bold text-blue-600 dark:text-blue-400">{{ $nombreReglaHistorial }}</span>
                                </h3>
                                <div class="flex items-center divide-x divide-gray-200 dark:divide-gray-700">
                                    <div class="flex items-center space-x-2 pr-4">
                                        <span class="text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500">Toda la Regla:</span>
                                        <button wire:click="exportarReglaIndividual('xlsx')" class="inline-flex items-center px-2 py-1 border border-transparent text-[10px] font-medium rounded text-white bg-green-600 hover:bg-green-700 transition-colors shadow-sm" title="Exportar regla completa a Excel">
                                            Excel
                                        </button>
                                        <button wire:click="exportarReglaIndividual('pdf')" class="inline-flex items-center px-2 py-1 border border-transparent text-[10px] font-medium rounded text-white bg-red-600 hover:bg-red-700 transition-colors shadow-sm" title="Exportar regla completa a PDF">
                                            PDF
                                        </button>
                                    </div>
                                    <div class="flex items-center space-x-2 pl-4">
                                        <span class="text-[10px] uppercase font-bold text-blue-500 dark:text-blue-400">Solo Cambios:</span>
                                        <button wire:click="exportarReglaIndividual('xlsx', true)" class="inline-flex items-center px-2 py-1 border border-blue-200 dark:border-blue-800 text-[10px] font-medium rounded text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 transition-colors shadow-sm" title="Exportar solo historial a Excel">
                                            Excel
                                        </button>
                                        <button wire:click="exportarReglaIndividual('pdf', true)" class="inline-flex items-center px-2 py-1 border border-blue-200 dark:border-blue-800 text-[10px] font-medium rounded text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 transition-colors shadow-sm" title="Exportar solo historial a PDF">
                                            PDF
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            @if($reglaParaHistorial)
                                <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-4 p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800/50">
                                    <div>
                                        <p class="text-[10px] uppercase font-bold text-blue-600 dark:text-blue-400">Principal</p>
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $reglaParaHistorial->mandante->razon_social ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] uppercase font-bold text-blue-600 dark:text-blue-400">Entidad</p>
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $reglaParaHistorial->tipoEntidadControlada->nombre_entidad ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] uppercase font-bold text-blue-600 dark:text-blue-400">Documento</p>
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $reglaParaHistorial->nombreDocumento->nombre ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:p-6 max-h-[60vh] overflow-y-auto">
                    <div class="space-y-4">
                        @forelse($historialActividad as $actividad)
                            <div class="p-4 rounded-lg @if($actividad->description == 'Regla creada') bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800 @else bg-yellow-50 dark:bg-yellow-900/50 border border-yellow-200 dark:border-yellow-800 @endif">
                                <div class="flex justify-between items-center">
                                    <p class="font-semibold text-gray-800 dark:text-gray-200">
                                        <span class="capitalize">{{ $actividad->description }}</span> por 
                                        <span class="font-bold">{{ $actividad->causer->name ?? 'Sistema' }}</span>
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $actividad->created_at->format('d/m/Y H:i:s') }}</p>
                                </div>
                                @if($actividad->properties && ($actividad->properties->has('attributes') || $actividad->properties->has('relations') || $actividad->properties->has('criterios_nuevos') || $actividad->properties->has('criterios')))
                                    <div class="mt-2 pl-4 border-l-2 border-gray-300 dark:border-gray-600">
                                        {{-- Atributos escalares (imc_meses_estimados, etc.) --}}
                                        @if($actividad->properties->has('attributes'))
                                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Campos Modificados:</p>
                                            <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 mt-1 space-y-1">
                                                @foreach(($actividad->properties['attributes'] ?? []) as $key => $values)
                                                    @if(is_array($values) && isset($values['old'], $values['new']))
                                                        {{-- Nuevo formato: ['old' => ..., 'new' => ...] --}}
                                                        <li>
                                                            <span class="font-semibold">{{ $key }}:</span>
                                                            <span class="text-red-600 dark:text-red-400 line-through">{{ $values['old'] ?? 'vacío' }}</span>
                                                            <span class="text-green-600 dark:text-green-400">→ {{ $values['new'] ?? 'vacío' }}</span>
                                                        </li>
                                                    @elseif(isset($actividad->properties['old']) && array_key_exists($key, $actividad->properties['old']))
                                                        {{-- Formato antiguo Laravel --}}
                                                        <li>
                                                            <span class="font-semibold">{{ $key }}:</span>
                                                            <span class="text-red-600 dark:text-red-400 line-through">{{ $actividad->properties['old'][$key] ?? 'vacío' }}</span>
                                                            <span class="text-green-600 dark:text-green-400">→ {{ $values ?? 'vacío' }}</span>
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @endif

                                        {{-- Criterios modificados --}}
                                        @if($actividad->properties->has('criterios_nuevos'))
                                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mt-2">Criterios Modificados:</p>
                                            <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 mt-1 space-y-1">
                                                @foreach(($actividad->properties['criterios_nuevos'] ?? []) as $idx => $crit)
                                                    @php
                                                        $orig = $actividad->properties['criterios_originales'][$idx] ?? null;
                                                        $cambios = [];
                                                        if ($orig) {
                                                            foreach ($crit as $k => $v) {
                                                                if (($orig[$k] ?? null) != $v) {
                                                                    $cambios[$k] = ['old' => $orig[$k], 'new' => $v];
                                                                }
                                                            }
                                                        }
                                                    @endphp
                                                    @foreach($cambios as $campo => $vals)
                                                        <li>
                                                            <span class="font-semibold">{{ $campo }} (criterio {{ $idx + 1 }}):</span>
                                                            <span class="text-red-600 dark:text-red-400 line-through">{{ $vals['old'] ?? 'vacío' }}</span>
                                                            <span class="text-green-600 dark:text-green-400">→ {{ $vals['new'] ?? 'vacío' }}</span>
                                                        </li>
                                                    @endforeach
                                                @endforeach
                                            </ul>
                                        @endif

                                        {{-- Criterios ASEM y Mandante (nuevo formato simple old/new) --}}
                                        @if($actividad->properties->has('criterios'))
                                            @php $crits = $actividad->properties['criterios']; @endphp
                                            <p class="text-sm font-semibold text-purple-700 dark:text-purple-300 mt-2">CRITERIOS MODIFICADOS:</p>
                                            <div class="mt-1 space-y-1 text-sm">
                                                <div class="flex items-start gap-2">
                                                    <span class="shrink-0 font-bold text-red-600 dark:text-red-400">ANTES:</span>
                                                    <span class="text-red-600 dark:text-red-400 line-through break-all">{{ $crits['old'] ?? '(ninguno)' }}</span>
                                                </div>
                                                <div class="flex items-start gap-2">
                                                    <span class="shrink-0 font-bold text-green-600 dark:text-green-400">AHORA:</span>
                                                    <span class="text-green-600 dark:text-green-400 break-all">{{ $crits['new'] ?? '(ninguno)' }}</span>
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Relaciones (cargos, nacionalidades, etc.) --}}
                                        @if($actividad->properties->has('relations'))
                                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mt-2">Detalle de Cambios en Relaciones:</p>
                                            <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 mt-1 space-y-1">
                                                @foreach(($actividad->properties['relations'] ?? []) as $relationName => $values)
                                                    <li>
                                                        <span class="font-semibold">{{ $relationName }}:</span>
                                                        <span class="text-red-600 dark:text-red-400 line-through">{{ $values['old'] }}</span>
                                                        <span class="text-green-600 dark:text-green-400">→ {{ $values['new'] }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-center text-gray-500 dark:text-gray-400 py-4">No hay historial de cambios para esta regla.</p>
                        @endforelse
                    </div>
                </div>
                <div class="bg-gray-100 dark:bg-gray-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" wire:click="$set('showHistoryModal', false)" class="btn-secondary-outline mt-3 sm:mt-0 sm:w-auto">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if ($showExportOptionsModal)
    <div class="fixed z-[100] inset-0 overflow-y-auto" aria-labelledby="modal-title-export" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-80" aria-hidden="true" wire:click="$set('showExportOptionsModal', false)"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                            <x-icons.history class="h-6 w-6 text-blue-600 dark:text-blue-300"/>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title-export">
                                Opciones de Exportación Excel
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Configure los parámetros para la generación del archivo Excel.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 space-y-4">
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Reglas Seleccionadas:</span>
                                <span class="px-2 py-1 text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-full">
                                    {{ count(array_filter($reglasSeleccionadas)) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center p-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors cursor-pointer group">
                            <input type="checkbox" wire:model="exportSelectedOnly" id="exportSelectedOnly" class="checkbox-generic" @if(count(array_filter($reglasSeleccionadas)) == 0) disabled @endif>
                            <label for="exportSelectedOnly" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer @if(count(array_filter($reglasSeleccionadas)) == 0) opacity-50 @endif">
                                Exportar solo reglas seleccionadas
                            </label>
                        </div>

                        <div class="flex items-center p-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors cursor-pointer group">
                            <input type="checkbox" wire:model="exportIncludeHistory" id="exportIncludeHistory" class="checkbox-generic">
                            <label for="exportIncludeHistory" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                                Incluir historial de cambios (Pestaña adicional)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-100 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse space-y-3 sm:space-y-0 gap-2">
                    <button wire:click="exportarExcel" type="button" class="btn-primary w-full sm:w-auto">
                        <svg wire:loading wire:target="exportarExcel" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Excel
                    </button>
                    <button wire:click="exportarPDF" type="button" class="btn-primary bg-red-600 hover:bg-red-700 w-full sm:w-auto">
                        <svg wire:loading wire:target="exportarPDF" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        PDF
                    </button>
                    <button wire:click="$set('showExportOptionsModal', false)" type="button" class="btn-secondary-outline w-full sm:mt-0 sm:w-auto mr-auto">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if ($showImportModal)
    <div class="fixed z-[100] inset-0 overflow-y-auto" aria-labelledby="modal-title-import" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-80" aria-hidden="true" wire:click="$set('showImportModal', false)"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full">
                <!-- Header -->
                <div class="bg-white dark:bg-gray-800 px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="bg-indigo-100 dark:bg-indigo-900/50 p-2 rounded-lg mr-4">
                            <x-icons.upload class="h-6 w-6 text-indigo-600 dark:text-indigo-400"/>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Importación Masiva de Reglas</h3>
                    </div>
                    <button wire:click="$set('showImportModal', false)" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <x-icons.x-mark class="h-6 w-6"/>
                    </button>
                </div>

                <div class="p-6 md:p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                        <!-- Columna Izquierda: Pasos -->
                        <div class="space-y-8">
                            <!-- Paso 1 -->
                            <div class="border border-gray-100 dark:border-gray-700 rounded-xl p-6 bg-gray-50/50 dark:bg-gray-800/50">
                                <div class="flex items-center mb-4">
                                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-600 text-white text-sm font-bold mr-3 shadow-sm">1</span>
                                    <h4 class="text-lg font-bold text-gray-800 dark:text-white">Preparar el Archivo</h4>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                                    Use nuestra plantilla oficial para asegurar que los datos cumplen con el formato requerido. Las columnas con (*) son obligatorias.
                                </p>
                                <button wire:click="descargarPlantilla" class="w-full flex items-center justify-center px-4 py-3 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl shadow-md transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                                    <x-icons.download class="h-5 w-5 mr-3"/>
                                    Descargar Plantilla Excel
                                </button>
                            </div>

                            <!-- Paso 2 -->
                            <div class="border border-gray-100 dark:border-gray-700 rounded-xl p-6 bg-gray-50/50 dark:bg-gray-800/50">
                                <div class="flex items-center mb-4">
                                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-600 text-white text-sm font-bold mr-3 shadow-sm">2</span>
                                    <h4 class="text-lg font-bold text-gray-800 dark:text-white">Subir y Procesar</h4>
                                </div>
                                
                                @if (!$importResults)
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-center w-full">
                                            <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed rounded-xl cursor-pointer bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 hover:bg-gray-50 transition-all group">
                                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                    <x-icons.upload class="w-10 h-10 mb-3 text-gray-400 group-hover:text-indigo-500 transition-colors"/>
                                                    <p class="text-sm text-gray-500 font-medium text-center px-4">
                                                        <span class="text-indigo-600">Suelte el archivo aquí</span> o haga clic para buscar
                                                    </p>
                                                </div>
                                                <input id="dropzone-file" type="file" wire:model="archivoImport" class="hidden" />
                                            </label>
                                        </div>

                                        @if ($archivoImport)
                                            <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-100 dark:border-indigo-800 flex items-center justify-between">
                                                <div class="flex items-center min-w-0">
                                                    <x-icons.document-text class="w-8 h-8 text-indigo-500 mr-2 flex-shrink-0"/>
                                                    <div class="truncate">
                                                        <p class="text-xs font-bold text-gray-700 dark:text-gray-200 truncate">{{ $archivoImport->getClientOriginalName() }}</p>
                                                        <p class="text-[10px] text-gray-500">{{ number_format($archivoImport->getSize() / 1024, 2) }} KB</p>
                                                    </div>
                                                </div>
                                                <button wire:click="$set('archivoImport', null)" class="text-gray-400 hover:text-red-500 ml-2">
                                                    <x-icons.x-mark class="w-5 h-5"/>
                                                </button>
                                            </div>
                                        @endif

                                        <button wire:click="importarExcel" class="w-full btn-primary py-3 rounded-xl shadow-lg" wire:loading.attr="disabled" wire:target="archivoImport, importarExcel">
                                            <div wire:loading.remove wire:target="importarExcel" class="flex items-center justify-center font-bold">
                                                <x-icons.check-circle class="h-5 w-5 mr-2"/>
                                                Importar Reglas
                                            </div>
                                            <div wire:loading wire:target="importarExcel" class="flex items-center justify-center font-bold">
                                                <x-icons.spinner class="h-5 w-5 mr-2 animate-spin"/>
                                                Procesando...
                                            </div>
                                        </button>
                                        @error('archivoImport') <p class="text-xs text-red-600 font-bold text-center mt-2">{{ $message }}</p> @enderror
                                    </div>
                                @else
                                    <div class="text-center py-6">
                                        <div class="mb-4 inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                                            <x-icons.check class="h-6 w-6"/>
                                        </div>
                                        <h5 class="text-lg font-bold text-gray-800 dark:text-white">¡Archivo Procesado!</h5>
                                        <p class="text-sm text-gray-500 mb-6">Revise los resultados en el panel lateral.</p>
                                        <button wire:click="$set('importResults', null)" class="btn-secondary w-full rounded-xl">
                                            <x-icons.plus class="h-4 w-4 mr-2"/>
                                            Subir otro archivo
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Columna Derecha: Resultados -->
                        <div class="border border-gray-100 dark:border-gray-700 rounded-xl bg-gray-50/30 dark:bg-gray-900/20 p-6 flex flex-col min-h-[400px]">
                            <h4 class="text-lg font-bold text-gray-800 dark:text-white mb-6 flex items-center">
                                <x-icons.clipboard-list class="h-5 w-5 mr-2 text-indigo-500"/>
                                Resultados del Proceso
                            </h4>

                            @if (!$importResults)
                                <div class="flex-1 flex flex-col items-center justify-center text-center text-gray-400 dark:text-gray-600 p-8">
                                    <x-icons.chart-bar class="h-16 w-16 mb-4 opacity-20"/>
                                    <p class="text-sm">Inicie el procesamiento para ver el resumen estadístico y detalle de errores aquí.</p>
                                </div>
                            @else
                                <div class="space-y-6">
                                    <!-- Stats Cards -->
                                    <div class="grid grid-cols-3 gap-3">
                                        <div class="p-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-green-100 dark:border-green-900/50 text-center">
                                            <p class="text-[10px] font-bold text-green-600 uppercase mb-1 tracking-tighter">Creados</p>
                                            <p class="text-xl font-black text-green-700 dark:text-green-300">{{ $importResults['creados'] }}</p>
                                        </div>
                                        <div class="p-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-blue-100 dark:border-blue-900/50 text-center">
                                            <p class="text-[10px] font-bold text-blue-600 uppercase mb-1 tracking-tighter">Actualizados</p>
                                            <p class="text-xl font-black text-blue-700 dark:text-blue-300">{{ $importResults['actualizados'] }}</p>
                                        </div>
                                        <div class="p-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-red-100 dark:border-red-900/50 text-center">
                                            <p class="text-[10px] font-bold text-red-600 uppercase mb-1 tracking-tighter">Errores</p>
                                            <p class="text-xl font-black text-red-700 dark:text-red-300">{{ count($importResults['errores']) }}</p>
                                        </div>
                                    </div>

                                    @if (count($importResults['errores']) > 0)
                                        <div class="flex-1 border border-red-100 dark:border-red-900/30 rounded-xl bg-white dark:bg-gray-800 overflow-hidden shadow-sm">
                                            <div class="bg-red-50 dark:bg-red-900/10 px-4 py-2 border-b border-red-100 dark:border-red-900/30">
                                                <p class="text-xs font-bold text-red-800 dark:text-red-300">Detalle de Conflictos</p>
                                            </div>
                                            <div class="max-h-64 overflow-y-auto">
                                                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                                                    <thead class="bg-gray-50/50 dark:bg-gray-800/50 sticky top-0">
                                                        <tr>
                                                            <th class="px-4 py-2 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Fila</th>
                                                            <th class="px-4 py-2 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Error Detectado</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                                        @foreach ($importResults['errores'] as $error)
                                                            <tr class="hover:bg-red-50/30 dark:hover:bg-red-900/5 transition-colors">
                                                                <td class="px-4 py-2 text-xs font-bold text-gray-600 dark:text-gray-400">#{{ $error['row'] }}</td>
                                                                <td class="px-4 py-2">
                                                                    <p class="text-xs font-bold text-gray-800 dark:text-gray-200 mb-0.5">{{ $error['id'] }}</p>
                                                                    <p class="text-[10px] text-red-600 dark:text-red-400 font-medium leading-tight">{{ $error['errors'] }}</p>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex-1 flex flex-col items-center justify-center text-center p-8 bg-green-50/30 dark:bg-green-900/10 rounded-xl border border-dashed border-green-200 dark:border-green-800">
                                            <x-icons.check-circle-solid class="h-12 w-12 text-green-500 mb-4 opacity-50"/>
                                            <p class="text-sm font-bold text-green-800 dark:text-green-300">¡Importación Perfecta!</p>
                                            <p class="text-xs text-green-600/70">No se detectaron errores en el archivo.</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 flex flex-row-reverse gap-3 rounded-b-lg border-t border-gray-100 dark:border-gray-800">
                    <button wire:click="$set('showImportModal', false)" type="button" class="btn-secondary rounded-xl px-8 shadow-sm">
                        Cerrar Ventana
                    </button>
                    @if ($importResults)
                         <button wire:click="$set('importResults', null)" type="button" class="btn-primary-outline rounded-xl px-8">
                            Limpiar Resultados
                         </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if ($showReporteImcModal)
        <div class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-80" wire:click="$set('showReporteImcModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-purple-100 dark:border-purple-900/40">
                    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 flex justify-between items-center">
                        <h3 class="text-lg font-black text-white flex items-center" id="modal-title">
                            <svg class="h-5 w-5 mr-2 text-purple-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            Reporte de Carga Documental (IMC)
                        </h3>
                        <button wire:click="$set('showReporteImcModal', false)" class="text-white/70 hover:text-white transition-colors">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="px-6 py-5">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Seleccione las Principales que desea incluir en la exportación del reporte ejecutivo.</p>
                        
                        <div class="bg-purple-50 dark:bg-purple-900/10 rounded-lg p-4 border border-purple-100 dark:border-purple-900/30 mb-5">
                            <div class="flex items-center space-x-3 mb-3 pb-3 border-b border-purple-200 dark:border-purple-800/50">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" wire:click="$set('mandantesSeleccionadosParaImc', {{ empty($mandantesSeleccionadosParaImc) || count($mandantesSeleccionadosParaImc) !== count($listaMandantes ?? []) ? collect($listaMandantes)->pluck('id') : '[]' }})" class="w-5 h-5 text-purple-600 rounded bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:ring-purple-500" {{ count($mandantesSeleccionadosParaImc) === count($listaMandantes ?? []) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm font-bold text-gray-700 dark:text-gray-300">Seleccionar Todas las Principales</span>
                                </label>
                            </div>
                            
                            <div class="max-h-48 overflow-y-auto space-y-2 pr-2">
                                @if(!empty($listaMandantes))
                                    @foreach ($listaMandantes as $mandante)
                                        <label class="flex items-center cursor-pointer p-2 hover:bg-white dark:hover:bg-gray-800 rounded transition-colors">
                                            <input type="checkbox" wire:model.defer="mandantesSeleccionadosParaImc" value="{{ $mandante->id }}" class="w-4 h-4 text-purple-600 rounded bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:ring-purple-500">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $mandante->razon_social }}</span>
                                        </label>
                                    @endforeach
                                @endif
                            </div>
                            @error('mandantesSeleccionadosParaImc') <span class="text-xs text-red-500 font-bold block mt-2">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-100 dark:border-gray-800">
                            <label class="flex items-center cursor-pointer relative">
                                <input type="checkbox" wire:model.defer="imcSoloActivas" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                                <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">Exportar Solo Reglas Activas</span>
                            </label>
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-auto italic">Recomendado</span>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 flex flex-col sm:flex-row-reverse gap-3 rounded-b-xl border-t border-gray-100 dark:border-gray-800">
                        <button wire:click="generarReporteImcPDF" wire:loading.attr="disabled" type="button" class="btn-primary flex justify-center items-center w-full sm:w-auto rounded-xl px-5 py-2.5 bg-red-600 hover:bg-red-700 focus:ring-red-500 border-red-600 dark:bg-red-600 transition-colors shadow-md text-white font-bold">
                            <span wire:loading.remove wire:target="generarReporteImcPDF" class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                Descargar PDF
                            </span>
                            <span wire:loading wire:target="generarReporteImcPDF" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Generando...
                            </span>
                        </button>
                        
                        <button wire:click="generarReporteImc" wire:loading.attr="disabled" type="button" class="btn-primary flex justify-center items-center w-full sm:w-auto rounded-xl px-5 py-2.5 bg-green-600 hover:bg-green-700 focus:ring-green-500 border-green-600 dark:bg-green-600 transition-colors shadow-md text-white font-bold">
                            <span wire:loading.remove wire:target="generarReporteImc" class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Descargar Excel
                            </span>
                            <span wire:loading wire:target="generarReporteImc" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Generando...
                            </span>
                        </button>
                        
                        <button wire:click="$set('showReporteImcModal', false)" type="button" class="btn-secondary rounded-xl px-6 w-full sm:w-auto mr-auto">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>