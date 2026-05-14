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
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-4">
                    
                    <!-- Filtro Lugar de Trabajo/Departamento -->
                    <div>
                        <label for="filtroLugarTrabajoId" class="label-form">Filtrar por Lugar de Trabajo/Departamento</label>
                        <select wire:model.live="filtroLugarTrabajoId" id="filtroLugarTrabajoId" class="input-field w-full">
                            <option value="todos">-- Todos los Lugares de Trabajo --</option>
                            @foreach($lugaresTrabajoDisponibles as $lugar)
                                <option value="{{ $lugar->id }}">{{ $lugar->nombre_jerarquico }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filtro Unidad Operativa -->
                    <div>
                        <label for="filtroUoId" class="label-form">Filtrar por U.O.</label>
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

                    <!-- Botones de Acción juntos -->
                    <div class="self-end flex gap-2">
                        <button wire:click="forzarRecalculoEnVivo" wire:loading.attr="disabled" class="btn-primary px-4 py-2 text-sm">
                            <x-icons.refresh class="w-4 h-4 mr-1"/> Mostrar Resultados
                        </button>
                        <!-- DROPDOWN DE EXPORTACIÓN -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <x-icons.download class="w-4 h-4 mr-1"/>
                                <span x-show="!open">Descargables</span>
                                <span x-show="open">Ocultar</span>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute mt-2 w-72 bg-white dark:bg-gray-800 border rounded-md shadow-lg z-50 p-4 right-0">
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

                <!-- TABLA DE RESULTADOS -->
                <div class="overflow-hidden shadow-md sm:rounded-lg border border-gray-300 dark:border-gray-600">
                    <div class="overflow-x-auto overflow-y-auto" style="max-height: 70vh;">
                        <table class="min-w-full border-collapse" style="border: 1px solid #d1d5db;">
                            <thead class="sticky top-0 z-20 bg-gray-100 dark:bg-gray-700">
                                <tr>
                                    <th class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider sticky left-0 z-30 bg-gray-100 dark:bg-gray-700" style="width: 50px; min-width: 50px; max-width: 50px;">N°</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider sticky z-30 bg-gray-100 dark:bg-gray-700" style="left: 50px; width: 60px; min-width: 60px; max-width: 60px;">#</th>
                                    
                                    <th class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider sticky z-20 bg-gray-100 dark:bg-gray-700" style="width: 200px; min-width: 200px; max-width: 200px; left: 110px;">Contratista</th>
                                    
                                    <th class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700" style="width: 100px; min-width: 100px; max-width: 100px;">Empresa<br><span class="text-xs font-normal">(%)</span></th>
                                    
                                    @if($entidadesHabilitadas['trabajadores'])
                                    <th class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700" style="width: 110px; min-width: 110px; max-width: 110px;">Trabajadores<br><span class="text-xs font-normal">(% / Total)</span></th>
                                    @endif

                                    @if($entidadesHabilitadas['vehiculos'])
                                    <th class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700" style="width: 110px; min-width: 110px; max-width: 110px;">Vehículos<br><span class="text-xs font-normal">(% / Total)</span></th>
                                    @endif

                                    @if($entidadesHabilitadas['maquinarias'])
                                    <th class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700" style="width: 110px; min-width: 110px; max-width: 110px;">Maquinaria<br><span class="text-xs font-normal">(% / Total)</span></th>
                                    @endif

                                    @if($entidadesHabilitadas['embarcaciones'])
                                    <th class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700" style="width: 130px; min-width: 130px; max-width: 130px;">Embarcaciones<br><span class="text-xs font-normal">(% / Total)</span></th>
                                    @endif

                                    <th class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700" style="width: 110px; min-width: 110px; max-width: 110px;">Lugar de Trabajo/Departamento</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700" style="width: 110px; min-width: 110px; max-width: 110px;">U.O.</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700" style="width: 110px; min-width: 110px; max-width: 110px;">N° Contrato</th>
                                    <th class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700" style="width: 120px; min-width: 120px; max-width: 120px;">Tipo Contrato</th>

                                    <th class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-700" style="width: 100px; min-width: 100px; max-width: 100px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php 
                                    // Cálculo dinámico del colspan
                                    $colspan = 9; // Base: N°, #, Contratista, Empresa, Lugar, UO, N° Contrato, Tipo Contrato, Acciones
                                    if($entidadesHabilitadas['trabajadores']) $colspan++;
                                    if($entidadesHabilitadas['vehiculos']) $colspan++;
                                    if($entidadesHabilitadas['maquinarias']) $colspan++;
                                    if($entidadesHabilitadas['embarcaciones']) $colspan++;
                                    
                                    $correlativo = 0;
                                @endphp
                                @forelse ($contratistasAgrupados as $contratistaItems)
                                    @foreach($contratistaItems as $item)
                                        @php
                                            $correlativo++;
                                            $isEven = $correlativo % 2 == 0;
                                            $bgClass = $isEven ? 'bg-orange-50 dark:bg-gray-700' : 'bg-white dark:bg-gray-800';
                                        @endphp
                                        <tr class="hover:bg-blue-50 dark:hover:bg-blue-900 {{ $bgClass }}">
                                            <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 font-bold text-center sticky left-0 z-10 {{ $bgClass }}" style="width: 50px; min-width: 50px; max-width: 50px;">{{ $correlativo }}</td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 font-mono sticky z-10 {{ $bgClass }}" style="left: 50px; width: 60px; min-width: 60px; max-width: 60px;">{{ $loop->parent->iteration }}.{{ $loop->iteration }}</td>
                                            
                                            <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 font-medium sticky z-10 whitespace-nowrap overflow-hidden text-ellipsis {{ $bgClass }}" style="width: 200px; min-width: 200px; max-width: 200px; left: 110px;">
                                                <a href="{{ route('mandante.gestion-entidades', ['selectedContratistaId' => $item['contratista_id'], 'lugar' => $item['lugar_trabajo_id'], 'uo' => $item['uo_id'], 'contrato' => $item['numero_contrato'] ?? '']) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200" title="{{ $item['razon_social'] }}">
                                                    {{ $item['razon_social'] }}
                                                    <span class="block text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $item['rut'] }}</span>
                                                </a>
                                            </td>
                                            
                                            <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center" style="width: 100px; min-width: 100px; max-width: 100px;">
                                                @if(isset($item['cumplimiento_empresa']))
                                                <span class="font-semibold {{ $item['cumplimiento_empresa'] < 100 ? 'text-orange-600' : 'text-green-600' }}">
                                                    {{ $item['cumplimiento_empresa'] }}%
                                                </span>
                                                @endif
                                            </td>

                                            @if($entidadesHabilitadas['trabajadores'])
                                            <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center" style="width: 110px; min-width: 110px; max-width: 110px;">
                                                @if(isset($item['promedio_trabajadores']) && $item['promedio_trabajadores']['total'] > 0)
                                                    <span class="font-semibold {{ $item['promedio_trabajadores']['promedio'] < 100 ? 'text-orange-600' : 'text-green-600' }}">
                                                        {{ $item['promedio_trabajadores']['promedio'] }}%
                                                    </span>
                                                    <span class="text-gray-500 dark:text-gray-400"> ({{ $item['promedio_trabajadores']['total'] }})</span>
                                                @else
                                                    <span class="text-gray-500 dark:text-gray-400">N/A</span>
                                                @endif
                                            </td>
                                            @endif

                                            @if($entidadesHabilitadas['vehiculos'])
                                            <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center" style="width: 110px; min-width: 110px; max-width: 110px;">
                                                @if(isset($item['promedio_vehiculos']) && $item['promedio_vehiculos']['total'] > 0)
                                                    <span class="font-semibold {{ $item['promedio_vehiculos']['promedio'] < 100 ? 'text-orange-600' : 'text-green-600' }}">
                                                        {{ $item['promedio_vehiculos']['promedio'] }}%
                                                    </span>
                                                    <span class="text-gray-500 dark:text-gray-400"> ({{ $item['promedio_vehiculos']['total'] }})</span>
                                                @else
                                                    <span class="text-gray-500 dark:text-gray-400">N/A</span>
                                                @endif
                                            </td>
                                            @endif

                                            @if($entidadesHabilitadas['maquinarias'])
                                            <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center" style="width: 110px; min-width: 110px; max-width: 110px;">
                                                @if(isset($item['promedio_maquinarias']) && $item['promedio_maquinarias']['total'] > 0)
                                                    <span class="font-semibold {{ $item['promedio_maquinarias']['promedio'] < 100 ? 'text-orange-600' : 'text-green-600' }}">
                                                        {{ $item['promedio_maquinarias']['promedio'] }}%
                                                    </span>
                                                    <span class="text-gray-500 dark:text-gray-400"> ({{ $item['promedio_maquinarias']['total'] }})</span>
                                                @else
                                                    <span class="text-gray-500 dark:text-gray-400">N/A</span>
                                                @endif
                                            </td>
                                            @endif

                                            @if($entidadesHabilitadas['embarcaciones'])
                                            <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center" style="width: 130px; min-width: 130px; max-width: 130px;">
                                                @if(isset($item['promedio_embarcaciones']) && $item['promedio_embarcaciones']['total'] > 0)
                                                    <span class="font-semibold {{ $item['promedio_embarcaciones']['promedio'] < 100 ? 'text-orange-600' : 'text-green-600' }}">
                                                        {{ $item['promedio_embarcaciones']['promedio'] }}%
                                                    </span>
                                                    <span class="text-gray-500 dark:text-gray-400"> ({{ $item['promedio_embarcaciones']['total'] }})</span>
                                                @else
                                                    <span class="text-gray-500 dark:text-gray-400">N/A</span>
                                                @endif
                                            </td>
                                            @endif

                                            <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 whitespace-nowrap overflow-hidden text-ellipsis" style="width: 110px; min-width: 110px; max-width: 110px;" title="{{ $item['lugar_trabajo_nombre_jerarquico'] ?? '' }}">
                                                {{ $item['lugar_trabajo_nombre_jerarquico'] ?? 'Recalcular...' }}
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 whitespace-nowrap overflow-hidden text-ellipsis" style="width: 110px; min-width: 110px; max-width: 110px;" title="{{ $item['uo_nombre_jerarquico'] ?? '' }}">
                                                {{ $item['uo_nombre_jerarquico'] ?? 'Recalcular...' }}
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 whitespace-nowrap text-sm" style="width: 110px; min-width: 110px; max-width: 110px;">
                                                {{ $item['numero_contrato'] ?? '-' }}
                                            </td>
                                            <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 whitespace-nowrap text-sm" style="width: 120px; min-width: 120px; max-width: 120px;">
                                                {{ $item['tipo_contrato_nombre'] ?? '-' }}
                                            </td>

                                            <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center" style="width: 100px; min-width: 100px; max-width: 100px;">
                                                <div class="flex flex-col space-y-2">
                                                    <a href="{{ route('mandante.supervision-detalle', ['contratistaId' => $item['contratista_id'], 'mandanteId' => $item['mandante_id'], 'lugarDeTrabajoId' => $item['lugar_trabajo_id'], 'uoId' => $item['uo_id']]) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-semibold text-sm">
                                                        Excepciones
                                                    </a>
                                                    {{-- Enlace a Gestión de Entidades con parámetro para pre-selección --}}
                                                    <a href="{{ route('mandante.gestion-entidades', ['selectedContratistaId' => $item['contratista_id'], 'lugar' => $item['lugar_trabajo_id'], 'uo' => $item['uo_id'], 'contrato' => $item['numero_contrato'] ?? '']) }}" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold text-sm">
                                                        Gestión Entidad
                                                    </a>
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
                                    <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 font-bold text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-700" colspan="5">TOTALES RECURSOS ÚNICOS:</td>
                                    <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center font-bold text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-700">{{ $totales['contratistas'] }} Contratistas</td>
                                    
                                    @if($entidadesHabilitadas['trabajadores'])
                                    <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center font-bold text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-700">{{ $totales['trabajadores'] }} Trabajadores</td>
                                    @endif

                                    @if($entidadesHabilitadas['vehiculos'])
                                    <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center font-bold text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-700">{{ $totales['vehiculos'] }} Vehículos</td>
                                    @endif

                                    @if($entidadesHabilitadas['maquinarias'])
                                    <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center font-bold text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-700">{{ $totales['maquinarias'] }} Maquinarias</td>
                                    @endif

                                    @if($entidadesHabilitadas['embarcaciones'])
                                    <td class="border border-gray-300 dark:border-gray-600 px-6 py-4 text-center font-bold text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-700">{{ $totales['embarcaciones'] }} Embarcaciones</td>
                                    @endif

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