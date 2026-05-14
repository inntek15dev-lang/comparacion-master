<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            RESUMEN GENERAL
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                
                <!-- SECCIÓN DE FILTROS -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-4">
                    <div>
                        <label for="filtroMandanteId" class="label-form">Principal</label>
                        <select wire:model.live="filtroMandanteId" id="filtroMandanteId" class="input-field w-full">
                            <option value="todos">-- Todos las Principales --</option>
                            @foreach($mandantesDisponibles as $mandante)
                                <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filtroLugarTrabajoId" class="label-form">Lugar/Depto</label>
                        <select wire:model.live="filtroLugarTrabajoId" id="filtroLugarTrabajoId" class="input-field w-full">
                            <option value="todos">-- Todos los Lugares de Trabajo --</option>
                            @foreach($lugaresTrabajoDisponibles as $lugar)
                                <option value="{{ $lugar->id }}">{{ $lugar->nombre_jerarquico }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filtroUoId" class="label-form">U.O.</label>
                        <select wire:model.live="filtroUoId" id="filtroUoId" class="input-field w-full" @if(count($unidadesOrganizacionalesDisponibles) == 0) disabled @endif>
                            <option value="todos">-- Todas las U.O. --</option>
                            @foreach($unidadesOrganizacionalesDisponibles as $uo)
                                <option value="{{ $uo->id }}">{{ $uo->nombre_jerarquico }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Filtro N° Contrato -->
                    <div>
                        <label for="filtroNumeroContrato" class="label-form">N° Contrato</label>
                        <input wire:model.live.debounce.300ms="filtroNumeroContrato" id="filtroNumeroContrato" type="text" placeholder="Buscar..." class="input-field w-full">
                    </div>
                    <!-- Filtro Tipo Contrato -->
                    <div>
                        <label for="filtroTipoContratoId" class="label-form">Tipo Contrato</label>
                        <select wire:model.live="filtroTipoContratoId" id="filtroTipoContratoId" class="input-field w-full">
                            <option value="todos">-- Todos --</option>
                            @foreach($tiposContratoDisponibles as $tc)
                                <option value="{{ $tc->id }}">{{ $tc->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Botón Mostrar Resultados y Descargables juntos -->
                    <div class="self-end flex gap-2 col-span-2">
                        <button wire:click="forzarRecalculoEnVivo" wire:loading.attr="disabled" class="btn-primary px-4 py-2 text-sm">
                            <x-icons.refresh class="w-4 h-4 mr-1"/> Mostrar Resultados
                        </button>
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed" :disabled="@json($filtroMandanteId === 'todos')">
                                <x-icons.download class="w-4 h-4 mr-1"/>
                                <span x-show="!open">Descargables</span>
                                <span x-show="open">Ocultar</span>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute mt-2 w-72 bg-white dark:bg-gray-800 border rounded-md shadow-lg z-50 p-4 right-0">
                                 @if($filtroMandanteId !== 'todos')
                                    <div class="grid grid-cols-1 gap-4 items-end">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Seleccione Formatos:</label>
                                            <div class="mt-2 flex flex-col space-y-2">
                                                <label class="flex items-center"><input type="checkbox" wire:model.defer="formatosExportacion" value="excel" class="form-checkbox"> <span class="ml-2 text-gray-700 dark:text-gray-300">Excel</span></label>
                                                <label class="flex items-center"><input type="checkbox" wire:model.defer="formatosExportacion" value="pdf" class="form-checkbox"> <span class="ml-2 text-gray-700 dark:text-gray-300">PDF</span></label>
                                            </div>
                                            @error('formatosExportacion') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <button wire:click="exportarReportes" wire:loading.attr="disabled" class="btn-primary w-full">
                                                <span wire:loading.remove wire:target="exportarReportes">Generar Reportes</span>
                                                <span wire:loading wire:target="exportarReportes">Generando...</span>
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Seleccione una Principal para exportar.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BUSCADOR -->
                <div class="mb-4">
                    <label for="search" class="label-form">Buscar en los resultados actuales</label>
                    <input wire:model.live.debounce.300ms="search" id="search" type="text" placeholder="Filtrar por Razón Social o NIT..." class="input-field w-full">
                </div>

                <!-- LOADING SPINNER -->
                <div wire:loading.flex wire:target="forzarRecalculoEnVivo" class="fixed inset-0 bg-gray-900 bg-opacity-60 z-50 items-center justify-center">
                    <div class="text-center text-white">
                        <x-icons.spinner class="w-12 h-12 mx-auto mb-4"/>
                        <p class="text-lg font-semibold">Calculando datos en tiempo real...</p>
                        <p>Este proceso puede tardar varios minutos. Por favor, espere.</p>
                    </div>
                </div>

                <!-- ALERTA DE FILTROS CAMBIADOS -->
                @if($filtrosCambiados)
                <div class="mb-4 p-4 bg-blue-100 border-l-4 border-blue-500 text-blue-700 dark:bg-blue-900 dark:border-blue-400 dark:text-blue-200" role="alert">
                    <p class="font-bold">Los filtros han cambiado.</p>
                    <p>Presione el botón "Mostrar Resultados" para actualizar la tabla con la nueva selección.</p>
                </div>
                @endif

                {{-- Checkboxes para excluir columnas --}}
                <div class="flex flex-wrap gap-4 items-center text-xs bg-gray-100 dark:bg-gray-700 p-2 rounded-lg mb-4">
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Ocultar:</span>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model.live="columnasExcluidas" value="id_bd" class="rounded text-blue-600">
                        <span class="ml-1 text-gray-600 dark:text-gray-400">ID_BD</span>
                    </label>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model.live="columnasExcluidas" value="id_registro" class="rounded text-blue-600">
                        <span class="ml-1 text-gray-600 dark:text-gray-400">ID</span>
                    </label>
                </div>

                <!-- TABLA DE RESULTADOS -->
                <div class="overflow-hidden shadow-md sm:rounded-lg border border-gray-300 dark:border-gray-600">
                    <div class="overflow-x-auto overflow-y-auto" style="max-height: 70vh;">
                        <table class="min-w-full border-collapse" style="border: 1px solid #d1d5db;">
                            <thead class="sticky top-0 z-20 bg-gray-100 dark:bg-gray-700">
                                @php
                                    $wOffsetId = in_array('id_registro', $columnasExcluidas) ? 0 : 60;
                                    $wOffsetIdBd = in_array('id_bd', $columnasExcluidas) ? 0 : 60;
                                    $offsetPrincipal = 40 + $wOffsetId + $wOffsetIdBd;
                                    $wOffsetPrincipal = ($filtroMandanteId === 'todos') ? 140 : 0;
                                    $offsetContratista = $offsetPrincipal + $wOffsetPrincipal;
                                @endphp
                                <tr>
                                    <th class="border border-gray-300 dark:border-gray-600 px-3 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider sticky left-0 z-30 bg-gray-100 dark:bg-gray-700" style="width: 40px; min-width: 40px;">#</th>
                                    
                                    @unless(in_array('id_registro', $columnasExcluidas))
                                    <th class="border border-gray-300 dark:border-gray-600 px-3 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider sticky z-30 bg-gray-100 dark:bg-gray-700" style="left: 40px; width: 60px; min-width: 60px;">ID</th>
                                    @endunless
                                    
                                    @unless(in_array('id_bd', $columnasExcluidas))
                                    <th class="border border-gray-300 dark:border-gray-600 px-3 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider sticky z-30 bg-gray-100 dark:bg-gray-700" style="left: {{ 40 + $wOffsetId }}px; width: 60px; min-width: 60px;">ID_BD</th>
                                    @endunless
                                    
                                    @if($filtroMandanteId === 'todos')
                                        <th class="border border-gray-300 dark:border-gray-600 px-3 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider sticky z-20 bg-gray-100 dark:bg-gray-700" style="left: {{ $offsetPrincipal }}px; min-width: 140px;">Principal</th>
                                    @endif
                                    
                                    <th class="border border-gray-300 dark:border-gray-600 px-3 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider sticky z-20 bg-gray-100 dark:bg-gray-700" style="min-width: 160px; left: {{ $offsetContratista }}px;">Contratista</th>
                                    
                                    <!-- Empresa después de Contratista -->
                                    <th class="border border-gray-300 dark:border-gray-600 px-3 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700" style="min-width: 70px;">Empresa<br><span class="text-xs font-normal">(%)</span></th>
                                    
                                    <!-- Entidades -->
                                    <th class="border border-gray-300 dark:border-gray-600 px-3 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700" style="min-width: 90px;">Trabajadores<br><span class="text-xs font-normal">(% / Total)</span></th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-3 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700" style="min-width: 80px;">Vehículos<br><span class="text-xs font-normal">(% / Total)</span></th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-3 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700" style="min-width: 80px;">Maquinaria<br><span class="text-xs font-normal">(% / Total)</span></th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-3 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700" style="min-width: 90px;">Embarcaciones<br><span class="text-xs font-normal">(% / Total)</span></th>
                                    
                                    <!-- Lugar, U.O., Contrato al final -->
                                    <th class="border border-gray-300 dark:border-gray-600 px-3 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700" style="min-width: 100px;">Lugar/Depto</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-3 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700" style="min-width: 80px;">U.O.</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-3 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700" style="min-width: 70px;">N° Cont.</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-3 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700" style="min-width: 100px;">Tipo Cont.</th>
                                    
                                    <th class="border border-gray-300 dark:border-gray-600 px-3 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700" style="min-width: 100px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $colspan = ($filtroMandanteId === 'todos' ? 14 : 13); @endphp
                                @forelse ($contratistasAgrupados as $contratistaId => $contratistaItems)
                                    @php
                                        $primerItem      = $contratistaItems->first();
                                        $nivel           = $primerItem['nivel_jerarquico'] ?? 1;
                                        $correlativoStr  = $primerItem['correlativo_jerarquico'] ?? '';
                                        $bgClass         = $primerItem['skill_bg_class'] ?? 'bg-white dark:bg-gray-800';
                                        $paddingNivel    = ($nivel - 1) * 16;
                                        $indicadorJerarquia = match($nivel) { 2 => '↳ ', 3 => '↳↳ ', 4 => '↳↳↳ ', default => '' };
                                        $colorNivel = match($nivel) {
                                            1 => 'text-indigo-600 dark:text-indigo-400',
                                            2 => 'text-purple-600 dark:text-purple-400',
                                            3 => 'text-violet-600 dark:text-violet-400',
                                            4 => 'text-fuchsia-600 dark:text-fuchsia-400',
                                            default => 'text-indigo-600 dark:text-indigo-400'
                                        };
                                    @endphp
                                    @foreach($contratistaItems as $item)
                                        <tr class="hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors {{ $bgClass }}">
                                            {{-- #: correlativo jerárquico 1 / 1.1 / 1.1.1 --}}
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 font-bold text-center text-sm sticky left-0 z-10 {{ $bgClass }}" style="width: 40px; min-width: 40px;">{{ $correlativoStr }}</td>
                                            
                                            @unless(in_array('id_registro', $columnasExcluidas))
                                            {{-- ID: id_registro de la CUO --}}
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 font-mono text-center text-xs text-gray-900 dark:text-gray-100 sticky z-10 {{ $bgClass }}" style="left: 40px; width: 60px; min-width: 60px;">{{ $item['id_registro'] ?? '-' }}</td>
                                            @endunless
                                            
                                            @unless(in_array('id_bd', $columnasExcluidas))
                                            {{-- ID_BD: contratista id --}}
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 font-mono text-center text-xs text-gray-900 dark:text-gray-100 sticky z-10 {{ $bgClass }}" style="left: {{ 40 + $wOffsetId }}px; width: 60px; min-width: 60px;">{{ $item['id_bd'] ?? '-' }}</td>
                                            @endunless
                                            
                                            @if($filtroMandanteId === 'todos')
                                                <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-sm font-medium sticky z-10 truncate {{ $bgClass }}" style="left: {{ $offsetPrincipal }}px; min-width: 140px; max-width: 140px;" title="{{ $item['mandante_nombre'] }}">{{ Str::limit($item['mandante_nombre'], 20) }}</td>
                                            @endif

                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-sm font-medium sticky z-10 {{ $bgClass }}" style="min-width: 140px; max-width: 200px; word-wrap: break-word; left: {{ $offsetContratista }}px;">
                                                <div style="padding-left: {{ $paddingNivel }}px;">
                                                    <span class="text-gray-400 text-xs">{{ $indicadorJerarquia }}</span>
                                                    <a href="{{ route('gestion.supervision-detalle', ['contratistaId' => $item['contratista_id'], 'mandanteId' => $item['mandante_id'], 'lugarDeTrabajoId' => $item['lugar_trabajo_id'], 'uoId' => $item['uo_id']]) }}" class="{{ $colorNivel }} hover:text-indigo-900 dark:hover:text-indigo-200" title="{{ $item['razon_social'] }}">
                                                        {{ $item['razon_social'] }}
                                                    </a>
                                                    <span class="block text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $item['rut'] }}</span>
                                                </div>
                                            </td>

                                            <!-- Empresa -->
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-center text-sm" style="min-width: 70px;">
                                                @if(isset($item['cumplimiento_empresa']))
                                                    <span class="font-semibold {{ $item['cumplimiento_empresa'] < 100 ? 'text-orange-600' : 'text-green-600' }}">{{ $item['cumplimiento_empresa'] }}%</span>
                                                @endif
                                            </td>

                                            <!-- Entidades -->
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-center text-sm" style="min-width: 90px;">
                                                @if(isset($item['promedio_trabajadores']) && $item['promedio_trabajadores']['total'] > 0)
                                                    <span class="font-semibold {{ $item['promedio_trabajadores']['promedio'] < 100 ? 'text-orange-600' : 'text-green-600' }}">{{ $item['promedio_trabajadores']['promedio'] }}%</span>
                                                    <span class="text-gray-500 dark:text-gray-400 text-xs"> ({{ $item['promedio_trabajadores']['total'] }})</span>
                                                @else <span class="text-gray-500 dark:text-gray-400">N/A</span> @endif
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-center text-sm" style="min-width: 80px;">
                                                @if(isset($item['promedio_vehiculos']) && $item['promedio_vehiculos']['total'] > 0)
                                                    <span class="font-semibold {{ $item['promedio_vehiculos']['promedio'] < 100 ? 'text-orange-600' : 'text-green-600' }}">{{ $item['promedio_vehiculos']['promedio'] }}%</span>
                                                    <span class="text-gray-500 dark:text-gray-400 text-xs"> ({{ $item['promedio_vehiculos']['total'] }})</span>
                                                @else <span class="text-gray-500 dark:text-gray-400">N/A</span> @endif
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-center text-sm" style="min-width: 80px;">
                                                @if(isset($item['promedio_maquinarias']) && $item['promedio_maquinarias']['total'] > 0)
                                                    <span class="font-semibold {{ $item['promedio_maquinarias']['promedio'] < 100 ? 'text-orange-600' : 'text-green-600' }}">{{ $item['promedio_maquinarias']['promedio'] }}%</span>
                                                    <span class="text-gray-500 dark:text-gray-400 text-xs"> ({{ $item['promedio_maquinarias']['total'] }})</span>
                                                @else <span class="text-gray-500 dark:text-gray-400">N/A</span> @endif
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-center text-sm" style="min-width: 90px;">
                                                @if(isset($item['promedio_embarcaciones']) && $item['promedio_embarcaciones']['total'] > 0)
                                                    <span class="font-semibold {{ $item['promedio_embarcaciones']['promedio'] < 100 ? 'text-orange-600' : 'text-green-600' }}">{{ $item['promedio_embarcaciones']['promedio'] }}%</span>
                                                    <span class="text-gray-500 dark:text-gray-400 text-xs"> ({{ $item['promedio_embarcaciones']['total'] }})</span>
                                                @else <span class="text-gray-500 dark:text-gray-400">N/A</span> @endif
                                            </td>

                                            <!-- Lugar, U.O., Contrato -->
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-sm truncate" style="min-width: 100px; max-width: 110px;" title="{{ $item['lugar_trabajo_nombre_jerarquico'] ?? '' }}">
                                                {{ Str::limit($item['lugar_trabajo_nombre_jerarquico'] ?? 'Recalcular...', 15) }}
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-sm truncate" style="min-width: 80px; max-width: 90px;" title="{{ $item['uo_nombre_jerarquico'] ?? '' }}">
                                                {{ Str::limit($item['uo_nombre_jerarquico'] ?? 'Recalcular...', 12) }}
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-sm text-center" style="min-width: 70px;">{{ $item['numero_contrato'] ?? '-' }}</td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-sm text-center truncate" style="min-width: 100px; max-width: 110px;">{{ Str::limit($item['tipo_contrato_nombre'] ?? '-', 12) }}</td>

                                            <!-- Acciones -->
                                            <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-center" style="min-width: 100px;">
                                                <div class="flex flex-col space-y-1">
                                                    <a href="{{ route('gestion.supervision-detalle', ['contratistaId' => $item['contratista_id'], 'mandanteId' => $item['mandante_id'], 'lugarDeTrabajoId' => $item['lugar_trabajo_id'], 'uoId' => $item['uo_id']]) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-semibold text-xs">Excepciones</a>
                                                    <a href="{{ route('gestion.operaciones-globales', ['selectedMandanteId' => $item['mandante_id'], 'selectedContratistaId' => $item['contratista_id'], 'lugar' => $item['lugar_trabajo_id'], 'uo' => $item['uo_id']]) }}" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold text-xs">Gestión Entidad</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach


                                @empty
                                    <tr>
                                        <td colspan="{{ $colspan }}" class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                            @if($calculandoEnVivo)
                                                Calculando...
                                            @else
                                                No hay datos para mostrar. Seleccione los filtros y presione "Mostrar Resultados".
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(!empty($contratistasConPromedios))
                            <tfoot class="sticky bottom-0 z-20 bg-gray-100 dark:bg-gray-700">
                                <tr>
                                    <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 font-bold text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-700" colspan="{{ $filtroMandanteId === 'todos' ? 8 : 7 }}">TOTALES RECURSOS ÚNICOS:</td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center font-bold text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-700">{{ $totales['contratistas'] }} Contratistas</td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center font-bold text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-700">{{ $totales['trabajadores'] }} Trabajadores</td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center font-bold text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-700">{{ $totales['vehiculos'] }} Vehículos</td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center font-bold text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-700">{{ $totales['maquinarias'] }} Maquinarias</td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center font-bold text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-700">{{ $totales['embarcaciones'] }} Embarcaciones</td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 font-bold bg-gray-100 dark:bg-gray-700"></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>