
<div class="p-6 bg-white dark:bg-gray-800 shadow-md rounded-lg">
    <h2 class="text-2xl font-semibold text-gray-800 dark:text-white mb-6">LISTADO DE CONTRATISTAS</h2>

    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="mb-4 px-4 py-3 bg-green-100 border border-green-400 text-green-700 rounded-md dark:bg-green-700 dark:text-green-100 dark:border-green-600">
            {{ session('message') }}
        </div>
    @endif
     @if (session()->has('admin_password_generated'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 10000)"
             class="mb-4 px-4 py-3 bg-blue-100 border border-blue-400 text-blue-700 rounded-md dark:bg-blue-700 dark:text-blue-100 dark:border-blue-600">
            <span class="font-bold">Información:</span> {{ session('admin_password_generated') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            class="mb-4 px-4 py-3 bg-red-100 border border-red-400 text-red-700 rounded-md dark:bg-red-700 dark:text-red-100 dark:border-red-600">
            {{ session('error') }}
        </div>
    @endif

    @if ($isOpen && $errors->any())
        <div class="alert alert-danger bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">¡Hay errores de validación! Por favor, revise los campos del formulario del Contratista.</strong>
            <ul class="mt-2 list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    @if (!Str::startsWith($error, 'selectedUnidadesConCondicion.') && !Str::startsWith($error, 'selectedDependencias'))
                        <li>{{ $error }}</li>
                    @endif
                @endforeach
            </ul>
        </div>
    @endif
    

    {{-- Panel de Filtros Compacto --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-4">
        {{-- Fila 1: Búsqueda y Botones principales --}}
        <div class="flex flex-wrap gap-3 items-end mb-3">
            <div class="flex-grow min-w-[200px] max-w-md">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Buscar</label>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Razón Social, RUT, Admin..."
                       class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200">
            </div>
            <div class="w-32">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">N° Contrato</label>
                <input type="text" wire:model.live.debounce.300ms="filtroContrato" 
                       class="w-full px-2 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200" placeholder="Buscar...">
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tipo Contrato</label>
                <select wire:model.live="filtroTipoContrato" class="w-full px-2 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-200">
                    <option value="todos">Todos</option>
                    @foreach ($tiposContrato as $tc)
                        <option value="{{ $tc->id }}">{{ $tc->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <button wire:click="limpiarFiltros" class="px-3 py-2 text-sm bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 rounded-md transition">
                <x-icons.x-circle class="h-4 w-4 inline-block mr-1"/> Limpiar
            </button>
            <button wire:click="create()" class="px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-md transition font-medium">
                <x-icons.plus class="h-4 w-4 inline-block mr-1"/> Nueva Empresa
            </button>
        </div>

        {{-- Fila 2: Filtros Select en línea --}}
        <div class="flex flex-wrap gap-3 items-end border-t border-gray-200 dark:border-gray-600 pt-3">
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Principal</label>
                <select wire:model.live="filtroMandante" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-200">
                    <option value="todos">Todas</option>
                    @foreach ($mandantesDisponibles as $mandante)
                        <option value="{{ $mandante->id }}">{{ Str::limit($mandante->razon_social, 15) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-28">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tipo</label>
                <select wire:model.live="filtroTipo" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-200">
                    <option value="todos">Todos</option>
                    <option value="Contratista">Contratista</option>
                    <option value="Subcontratista">Sub-Contratista</option>
                </select>
            </div>
            <div class="w-24">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Estado</label>
                <select wire:model.live="filtroEstado" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-200">
                    <option value="todos">Todos</option>
                    <option value="activos">Activos</option>
                    <option value="inactivos">Inactivos</option>
                </select>
            </div>
            <div class="w-20">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Acredita</label>
                <select wire:model.live="filtroAcredita" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-200">
                    <option value="todos">--</option>
                    <option value="si">SÍ</option>
                    <option value="no">NO</option>
                </select>
            </div>
            <div class="w-20">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Verifica</label>
                <select wire:model.live="filtroVerifica" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-200">
                    <option value="todos">--</option>
                    <option value="si">SÍ</option>
                    <option value="no">NO</option>
                </select>
            </div>

            {{-- Separador vertical --}}
            <div class="hidden sm:block h-8 w-px bg-gray-300 dark:bg-gray-600 mx-1"></div>

            {{-- Excluir columnas inline --}}
            <div class="flex items-center gap-2 text-xs">
                <span class="font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Ocultar:</span>
                <label class="inline-flex items-center cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 px-1 py-0.5 rounded">
                    <input type="checkbox" wire:model.live="columnasExcluidas" value="id_bd" class="w-3 h-3 rounded text-indigo-600">
                    <span class="ml-1 text-gray-600 dark:text-gray-300">ID_BD</span>
                </label>
                <label class="inline-flex items-center cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 px-1 py-0.5 rounded">
                    <input type="checkbox" wire:model.live="columnasExcluidas" value="id_registro" class="w-3 h-3 rounded text-indigo-600">
                    <span class="ml-1 text-gray-600 dark:text-gray-300">ID</span>
                </label>
                <label class="inline-flex items-center cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 px-1 py-0.5 rounded">
                    <input type="checkbox" wire:model.live="columnasExcluidas" value="tipo" class="w-3 h-3 rounded text-indigo-600">
                    <span class="ml-1 text-gray-600 dark:text-gray-300">Tipo</span>
                </label>
                <label class="inline-flex items-center cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 px-1 py-0.5 rounded">
                    <input type="checkbox" wire:model.live="columnasExcluidas" value="admin" class="w-3 h-3 rounded text-indigo-600">
                    <span class="ml-1 text-gray-600 dark:text-gray-300">Admin</span>
                </label>
                <label class="inline-flex items-center cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 px-1 py-0.5 rounded">
                    <input type="checkbox" wire:model.live="columnasExcluidas" value="principal" class="w-3 h-3 rounded text-indigo-600">
                    <span class="ml-1 text-gray-600 dark:text-gray-300">Principal</span>
                </label>
                <label class="inline-flex items-center cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 px-1 py-0.5 rounded">
                    <input type="checkbox" wire:model.live="columnasExcluidas" value="lugar" class="w-3 h-3 rounded text-indigo-600">
                    <span class="ml-1 text-gray-600 dark:text-gray-300">Lugar</span>
                </label>
                <label class="inline-flex items-center cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 px-1 py-0.5 rounded">
                    <input type="checkbox" wire:model.live="columnasExcluidas" value="uo" class="w-3 h-3 rounded text-indigo-600">
                    <span class="ml-1 text-gray-600 dark:text-gray-300">U.O.</span>
                </label>
                <label class="inline-flex items-center cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 px-1 py-0.5 rounded">
                    <input type="checkbox" wire:model.live="columnasExcluidas" value="rut" class="w-3 h-3 rounded text-indigo-600">
                    <span class="ml-1 text-gray-600 dark:text-gray-300">RUT</span>
                </label>
            </div>
        </div>
    </div>

    <!-- ================== INICIO DE LA MODIFICACIÓN (TABLA FIJA Y ESTILIZADA) ================== -->
    <div class="overflow-hidden shadow-md sm:rounded-lg border border-gray-300 dark:border-gray-600">
        <div class="overflow-x-auto overflow-y-auto h-[70vh]">
            <table class="min-w-full border-collapse">
                <thead class="bg-gray-100 dark:bg-gray-700 sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-400 dark:border-gray-500">#</th>
                        @unless(in_array('id_registro', $columnasExcluidas))
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-400 dark:border-gray-500">ID</th>
                        @endunless
                        @unless(in_array('id_bd', $columnasExcluidas))
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-400 dark:border-gray-500">ID_BD</th>
                        @endunless
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-400 dark:border-gray-500">SAP</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-400 dark:border-gray-500">Razón Social</th>
                        @unless(in_array('rut', $columnasExcluidas))
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-400 dark:border-gray-500">{{ config('pais.code') === 'cl' ? 'RUT' : (config('pais.code') === 'co' ? 'NIT' : 'RUT/NIT') }}</th>
                        @endunless
                        @unless(in_array('tipo', $columnasExcluidas))
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-400 dark:border-gray-500">Tipo</th>
                        @endunless
                        @unless(in_array('admin', $columnasExcluidas))
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-400 dark:border-gray-500">Admin. Plataforma</th>
                        @endunless
                        @unless(in_array('principal', $columnasExcluidas))
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-400 dark:border-gray-500">Principal</th>
                        @endunless
                        @unless(in_array('lugar', $columnasExcluidas))
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-400 dark:border-gray-500">Lugar de Trabajo/Departamento</th>
                        @endunless
                        @unless(in_array('uo', $columnasExcluidas))
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-400 dark:border-gray-500">U.O.</th>
                        @endunless
                        <th scope="col" class="px-4 py-3 text-center text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-400 dark:border-gray-500">Acredita</th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-400 dark:border-gray-500">Verifica</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-400 dark:border-gray-500">N° Contrato</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-400 dark:border-gray-500">Tipo Contrato</th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-400 dark:border-gray-500">Cuota Trab.</th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-400 dark:border-gray-500">Estado</th>
                        <th scope="col" class="px-4 py-3 text-center text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-400 dark:border-gray-500">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($solicitudes as $solicitud)
                        @if($solicitud->contratista)
                        @php
                                                        $correlativoArray = explode('.', $solicitud->correlativo_jerarquico ?? $loop->iteration);
                            $numeroBase = (int) $correlativoArray[0];
                            
                            // Verificar si el grupo tiene subcontratistas mirando la colección actual ($solicitudes)
                            $tieneSubcontratistas = collect($solicitudes->items())->filter(function($item) use ($numeroBase) {
                                return str_starts_with($item->correlativo_jerarquico ?? '', $numeroBase . '.');
                            })->count() > 0;

                            // Establecer color estilo legacy
                            if ($tieneSubcontratistas) {
                                static $grupoCounter = 0;
                                static $lastBaseGroup = null;
                                if ($lastBaseGroup !== $numeroBase) {
                                    $grupoCounter++;
                                    $lastBaseGroup = $numeroBase;
                                }
                                $fondoClase = ($grupoCounter % 2 == 1) 
                                    ? 'bg-yellow-100/50 dark:bg-yellow-900/30' 
                                    : 'bg-orange-100/50 dark:bg-orange-900/30';
                            } else {
                                static $simpleCounter = 0;
                                static $lastBaseSimple = null;
                                if ($lastBaseSimple !== $numeroBase) {
                                    $simpleCounter++;
                                    $lastBaseSimple = $numeroBase;
                                }
                                $fondoClase = ($simpleCounter % 2 == 1) 
                                    ? 'bg-white dark:bg-gray-800' 
                                    : 'bg-gray-300 dark:bg-gray-600';
                            }
                        @endphp
                        <tr wire:key="row-{{ $solicitud->id }}-{{ $solicitud->pivot_id ?? 'gen' }}-{{ $loop->index }}" class="{{ $fondoClase }} hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors duration-150">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-400 border border-gray-400 dark:border-gray-500">{{ $solicitud->correlativo_jerarquico ?? $loop->iteration }}</td>
                            @unless(in_array('id_registro', $columnasExcluidas))
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100 border border-gray-400 dark:border-gray-500">{{ $solicitud->pivot_id_registro ?? '-' }}</td>
                            @endunless
                            @unless(in_array('id_bd', $columnasExcluidas))
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100 border border-gray-400 dark:border-gray-500">{{ $solicitud->contratista->id }}</td>
                            @endunless
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-mono font-semibold text-indigo-700 dark:text-indigo-300 border border-gray-400 dark:border-gray-500">{{ $solicitud->pivot_sap ?? '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 border border-gray-400 dark:border-gray-500">
                                @if(isset($solicitud->nivel_jerarquia) && $solicitud->nivel_jerarquia > 0)
                                    <span class="text-gray-400 dark:text-gray-500">@for($i = 0; $i < $solicitud->nivel_jerarquia; $i++)&nbsp;&nbsp;&nbsp;&nbsp;@endfor└ </span>
                                @endif
                                {{ $solicitud->contratista->razon_social }}
                            </td>
                            @unless(in_array('rut', $columnasExcluidas))
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 border border-gray-400 dark:border-gray-500">{{ $solicitud->contratista->rut }}</td>
                            @endunless
                            @unless(in_array('tipo', $columnasExcluidas))
                            <td class="px-4 py-3 whitespace-nowrap text-sm border border-gray-400 dark:border-gray-500">
                                @if(isset($solicitud->nivel_jerarquia) && $solicitud->nivel_jerarquia > 0)
                                    <span class="text-purple-600 dark:text-purple-400 font-semibold">{{ $solicitud->cadena_ancestros ?? 'Sub' }}</span>
                                @else
                                    <span class="text-blue-600 dark:text-blue-400 font-semibold">Contratista</span>
                                @endif
                            </td>
                            @endunless
                            @unless(in_array('admin', $columnasExcluidas))
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 border border-gray-400 dark:border-gray-500">
                                {{ $solicitud->contratista->adminUser->name ?? 'No asignado' }}
                                @if($solicitud->contratista->adminUser) <br><small class="text-gray-500 dark:text-gray-400">{{ $solicitud->contratista->adminUser->email }}</small> @endif
                            </td>
                            @endunless
                            @unless(in_array('principal', $columnasExcluidas))
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 border border-gray-400 dark:border-gray-500">
                                <div class="text-xs py-0.5">{{ $solicitud->mandante->razon_social ?? 'N/D' }}</div>
                            </td>
                            @endunless
                            @unless(in_array('lugar', $columnasExcluidas))
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 border border-gray-400 dark:border-gray-500">
                                @if($solicitud->dep_row_id)
                                    @php
                                        $dep = $solicitud->contratista->dependencias->firstWhere('id', $solicitud->dep_row_id);
                                    @endphp
                                    <div class="text-xs">{{ $dep->nombre_jerarquico ?? 'N/D' }}</div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Sin Lugar asignado</span>
                                @endif
                            </td>
                            @endunless
                            @unless(in_array('uo', $columnasExcluidas))
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 border border-gray-400 dark:border-gray-500">
                                @if($solicitud->uo_row_id)
                                    @php
                                        $uo = $solicitud->contratista->unidadesOrganizacionalesMandante->firstWhere('id', $solicitud->uo_row_id);
                                    @endphp
                                    <div class="text-xs font-semibold">{{ $uo->nombre_jerarquico ?? 'U.O. No Encontrada' }}</div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Sin U.O. asignada</span>
                                @endif
                            </td>
                            @endunless
                            <td class="px-2 py-2 text-center text-sm border border-gray-400 dark:border-gray-500">
                                @if($solicitud->pivot_id)
                                    <div class="flex flex-col items-center gap-0.5">
                                        <span wire:click="toggleAcreditaUo({{ $solicitud->pivot_id }})"
                                              class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full cursor-pointer {{ $solicitud->pivot_acredita ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-700 dark:text-indigo-100' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100' }}">
                                            {{ $solicitud->pivot_acredita ? 'SÍ' : 'NO' }}
                                        </span>
                                        @if($solicitud->pivot_fecha_inicio_acredita || $solicitud->pivot_fecha_fin_acredita)
                                            <div class="text-[8px] text-gray-500 dark:text-gray-400 leading-tight">
                                                {{ $solicitud->pivot_fecha_inicio_acredita ? \Carbon\Carbon::parse($solicitud->pivot_fecha_inicio_acredita)->format('d/m/y') : '--' }}
                                                -
                                                {{ $solicitud->pivot_fecha_fin_acredita ? \Carbon\Carbon::parse($solicitud->pivot_fecha_fin_acredita)->format('d/m/y') : '--' }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-center text-sm border border-gray-400 dark:border-gray-500">
                                @if($solicitud->pivot_id)
                                    <div class="flex flex-col items-center gap-0.5">
                                        <span wire:click="toggleVerificaUo({{ $solicitud->pivot_id }})"
                                              class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full cursor-pointer {{ $solicitud->pivot_verifica ? 'bg-purple-100 text-purple-800 dark:bg-purple-700 dark:text-purple-100' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100' }}">
                                            {{ $solicitud->pivot_verifica ? 'SÍ' : 'NO' }}
                                        </span>
                                        @if($solicitud->pivot_verifica && ($solicitud->pivot_fecha_inicio_verifica || $solicitud->pivot_fecha_fin_verifica))
                                            <div class="text-[8px] text-gray-500 dark:text-gray-400 leading-tight">
                                                {{ $solicitud->pivot_fecha_inicio_verifica ? \Carbon\Carbon::parse($solicitud->pivot_fecha_inicio_verifica)->format('d/m/y') : '--' }}
                                                -
                                                {{ $solicitud->pivot_fecha_fin_verifica ? \Carbon\Carbon::parse($solicitud->pivot_fecha_fin_verifica)->format('d/m/y') : '--' }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 border border-gray-400 dark:border-gray-500">
                                {{ $solicitud->pivot_numero_contrato ?? '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 border border-gray-400 dark:border-gray-500">
                                @if($solicitud->pivot_tipo_contrato_id)
                                    @php
                                        $tipoContratoRow = $tiposContrato->firstWhere('id', $solicitud->pivot_tipo_contrato_id);
                                    @endphp
                                    {{ $tipoContratoRow->nombre ?? '-' }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-semibold text-gray-700 dark:text-gray-300 border border-gray-400 dark:border-gray-500">
                                @if($solicitud->nivel_jerarquia == 0)
                                    {{ $solicitud->count_trabajadores_familia ?? 0 }} / {{ $solicitud->pivot_trabajadores_cuota ?? 'Sin Cuota' }}
                                @else
                                    {{ $solicitud->count_trabajadores_propios ?? 0 }}
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm border border-gray-400 dark:border-gray-500">
                                <span wire:click="toggleActive({{ $solicitud->contratista->id }})"
                                      wire:confirm="¿Está seguro de {{ $solicitud->contratista->is_active ? 'desactivar' : 'activar' }} a {{ $solicitud->contratista->razon_social }}?"
                                      class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full cursor-pointer {{ $solicitud->contratista->is_active ? 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100' }}">
                                    {{ $solicitud->contratista->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium border border-gray-400 dark:border-gray-500">
                                <div class="flex flex-col items-start space-y-1">
                                    <button wire:click="edit({{ $solicitud->contratista->id }})" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200 text-xs">Editar Ficha</button>
                                    
                                    @if($solicitud->pivot_id)
                                        <button wire:click="abrirModalVinculaciones({{ $solicitud->contratista->id }}, {{ $solicitud->pivot_id }})" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-200 text-xs font-bold">Gestionar Vinculación</button>
                                    @endif

                                    <button wire:click="abrirModalVinculaciones({{ $solicitud->contratista->id }})" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-200 text-xs font-bold">+ Agregar Vinculación</button>
                                    @if ($solicitud->contratista->tipo_inscripcion === 'Contratista')
                                        <button wire:click="abrirModalAsignarMandante({{ $solicitud->contratista->id }})" class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-200 text-xs">Asignar Nueva Principal</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="15" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center border border-gray-400 dark:border-gray-500">No se encontraron empresas contratistas que coincidan con los filtros aplicados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <!-- ================== FIN DE LA MODIFICACIÓN ==================== -->

    <div class="mt-4">{{ $solicitudes->links() }}</div>

    @if ($isOpen)
        <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title-contratista" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75" aria-hidden="true" wire:click="closeModal()"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <form wire:submit.prevent="store">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                            <div class="sm:flex sm:items-start mb-4">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18A2.25 2.25 0 004.5 21h3.75V7.5h3v13.5h3.75v-13.5h3V21h3.75a2.25 2.25 0 002.25-2.25V3" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title-contratista">
                                        {{ $contratistaId ? 'Editar' : 'Crear Nueva' }} Empresa Contratista
                                    </h3>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <fieldset class="border border-gray-300 dark:border-gray-600 p-4 rounded-md">
                                    <legend class="text-md font-semibold text-gray-700 dark:text-gray-300 px-2">Datos de la Empresa</legend>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                                        @if(!$contratistaId)
                                            <div class="md:col-span-2">
                                                <label for="mandante_id_vinculacion" class="label-form">Vincular a la Principal <span class="text-red-500">*</span></label>
                                                <select wire:model="mandante_id_vinculacion" id="mandante_id_vinculacion" class="input-field @error('mandante_id_vinculacion') input-error @enderror">
                                                    <option value="">Seleccione una Principal...</option>
                                                    @foreach ($mandantesDisponibles as $mandante)
                                                        <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                                                    @endforeach
                                                </select>
                                                @error('mandante_id_vinculacion') <span class="error-message">{{ $message }}</span> @enderror
                                            </div>
                                        @endif
                                        <div>
                                            <label for="razon_social" class="label-form">Razón Social <span class="text-red-500">*</span></label>
                                            <input type="text" wire:model.lazy="razon_social" id="razon_social" class="input-field @error('razon_social') input-error @enderror">
                                            @error('razon_social') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="nombre_fantasia" class="label-form">Nombre Comercial <span class="text-red-500">*</span></label>
                                            <input type="text" wire:model.lazy="nombre_fantasia" id="nombre_fantasia" class="input-field @error('nombre_fantasia') input-error @enderror">
                                            @error('nombre_fantasia') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="rut_contratista" class="label-form">RUT/NIT/RUC/CNPJ Empresa <span class="text-red-500">*</span></label>
                                            <input type="text" wire:model.lazy="rut_contratista" id="rut_contratista" placeholder="Ej: 900123456-7" class="input-field @error('rut_contratista') input-error @enderror">
                                            @error('rut_contratista') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="email_empresa" class="label-form">Email Empresa <span class="text-red-500">*</span></label>
                                            <input type="email" wire:model.lazy="email_empresa" id="email_empresa" class="input-field @error('email_empresa') input-error @enderror">
                                            @error('email_empresa') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="telefono_empresa" class="label-form">Teléfono Empresa <span class="text-red-500">*</span></label>
                                            <input type="tel" wire:model.lazy="telefono_empresa" id="telefono_empresa" class="input-field @error('telefono_empresa') input-error @enderror">
                                            @error('telefono_empresa') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                         <div>
                                            <label for="tipo_inscripcion" class="label-form">Tipo Inscripción <span class="text-red-500">*</span></label>
                                            <select wire:model="tipo_inscripcion" id="tipo_inscripcion" class="input-field @error('tipo_inscripcion') input-error @enderror" @if(!$contratistaId) disabled @else @endif>
                                                <option value="Contratista">Contratista</option>
                                                <option value="Subcontratista">Subcontratista</option>
                                            </select>
                                            @error('tipo_inscripcion') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </fieldset>

                                <fieldset class="border border-gray-300 dark:border-gray-600 p-4 rounded-md">
                                    <legend class="text-md font-semibold text-gray-700 dark:text-gray-300 px-2">Dirección</legend>
                                     <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">
                                        <div>
                                            <label for="direccion_calle" class="label-form">Calle <span class="text-red-500">*</span></label>
                                            <input type="text" wire:model.lazy="direccion_calle" id="direccion_calle" class="input-field @error('direccion_calle') input-error @enderror">
                                            @error('direccion_calle') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="direccion_numero" class="label-form">Número <span class="text-red-500">*</span></label>
                                            <input type="text" wire:model.lazy="direccion_numero" id="direccion_numero" class="input-field @error('direccion_numero') input-error @enderror">
                                            @error('direccion_numero') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                         <div></div>
                                        <div>
                                            <label for="selected_region_id_contratista" class="label-form">Departamento <span class="text-red-500">*</span></label>
                                            <select wire:model.live="selected_region_id_contratista" id="selected_region_id_contratista" class="input-field @error('selected_region_id_contratista') input-error @enderror">
                                                <option value="">Seleccione Departamento...</option>
                                                @foreach ($regiones as $region)
                                                    <option value="{{ $region->id }}">{{ $region->nombre }}</option>
                                                @endforeach
                                            </select>
                                            @error('selected_region_id_contratista') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="comuna_id" class="label-form">Municipio <span class="text-red-500">*</span></label>
                                            <select wire:model="comuna_id" id="comuna_id" class="input-field @error('comuna_id') input-error @enderror" @if(empty($selected_region_id_contratista) || $comunasDisponiblesContratista->isEmpty()) disabled @endif>
                                                <option value="">Seleccione Municipio...</option>
                                                @foreach ($comunasDisponiblesContratista as $comuna)
                                                    <option value="{{ $comuna->id }}">{{ $comuna->nombre }}</option>
                                                @endforeach
                                            </select>
                                            @error('comuna_id') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </fieldset>

                                <fieldset class="border border-gray-300 dark:border-gray-600 p-4 rounded-md">
                                    <legend class="text-md font-semibold text-gray-700 dark:text-gray-300 px-2">Detalles Adicionales</legend>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">
                                        <div>
                                            <label for="tipo_empresa_legal_id" class="label-form">Tipo Empresa Legal <span class="text-red-500">*</span></label>
                                            <select wire:model="tipo_empresa_legal_id" id="tipo_empresa_legal_id" class="input-field @error('tipo_empresa_legal_id') input-error @enderror">
                                                <option value="">Seleccione...</option>
                                                @foreach ($tiposEmpresaLegal as $tipo)
                                                    <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                                @endforeach
                                            </select>
                                            @error('tipo_empresa_legal_id') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="rubro_id" class="label-form">Actividad Económica <span class="text-red-500">*</span></label>
                                            <select wire:model="rubro_id" id="rubro_id" class="input-field @error('rubro_id') input-error @enderror">
                                                <option value="">Seleccione...</option>
                                                @foreach ($rubros as $rubro)
                                                    <option value="{{ $rubro->id }}">{{ $rubro->nombre }}</option>
                                                @endforeach
                                            </select>
                                            @error('rubro_id') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="rango_cantidad_trabajadores_id" class="label-form">Rango Empleados <span class="text-red-500">*</span></label>
                                            <select wire:model="rango_cantidad_trabajadores_id" id="rango_cantidad_trabajadores_id" class="input-field @error('rango_cantidad_trabajadores_id') input-error @enderror">
                                                <option value="">Seleccione...</option>
                                                @foreach ($rangosCantidad as $rango)
                                                    <option value="{{ $rango->id }}">{{ $rango->nombre }}</option>
                                                @endforeach
                                            </select>
                                            @error('rango_cantidad_trabajadores_id') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="mutualidad_id" class="label-form">ARL <span class="text-red-500">*</span></label>
                                            <select wire:model="mutualidad_id" id="mutualidad_id" class="input-field @error('mutualidad_id') input-error @enderror">
                                                <option value="">Seleccione...</option>
                                                @foreach ($mutualidades as $mutual)
                                                    <option value="{{ $mutual->id }}">{{ $mutual->nombre }}</option>
                                                @endforeach
                                            </select>
                                            @error('mutualidad_id') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </fieldset>

                                <fieldset class="border border-gray-300 dark:border-gray-600 p-4 rounded-md">
                                    <legend class="text-md font-semibold text-gray-700 dark:text-gray-300 px-2">Representante Legal</legend>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">
                                        <div>
                                            <label for="rep_legal_nombres" class="label-form">Nombres Rep. Legal <span class="text-red-500">*</span></label>
                                            <input type="text" wire:model.lazy="rep_legal_nombres" id="rep_legal_nombres" class="input-field @error('rep_legal_nombres') input-error @enderror">
                                            @error('rep_legal_nombres') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="rep_legal_apellido_paterno" class="label-form">Primer Apellido Rep. Legal <span class="text-red-500">*</span></label>
                                            <input type="text" wire:model.lazy="rep_legal_apellido_paterno" id="rep_legal_apellido_paterno" class="input-field @error('rep_legal_apellido_paterno') input-error @enderror">
                                            @error('rep_legal_apellido_paterno') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="rep_legal_apellido_materno" class="label-form">Segundo Apellido Rep. Legal <span class="text-red-500">*</span></label>
                                            <input type="text" wire:model.lazy="rep_legal_apellido_materno" id="rep_legal_apellido_materno" class="input-field @error('rep_legal_apellido_materno') input-error @enderror">
                                            @error('rep_legal_apellido_materno') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="rep_legal_rut" class="label-form">RUT/NUIP/DNI/CEDULA/CPF Rep. Legal <span class="text-red-500">*</span></label>
                                            <input type="text" wire:model.lazy="rep_legal_rut" id="rep_legal_rut" placeholder="Ej: 900123456-7" class="input-field @error('rep_legal_rut') input-error @enderror">
                                            @error('rep_legal_rut') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="rep_legal_email" class="label-form">Email Rep. Legal <span class="text-red-500">*</span></label>
                                            <input type="email" wire:model.lazy="rep_legal_email" id="rep_legal_email" class="input-field @error('rep_legal_email') input-error @enderror">
                                            @error('rep_legal_email') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="rep_legal_telefono" class="label-form">Teléfono Rep. Legal <span class="text-red-500">*</span></label>
                                            <input type="tel" wire:model.lazy="rep_legal_telefono" id="rep_legal_telefono" class="input-field @error('rep_legal_telefono') input-error @enderror">
                                            @error('rep_legal_telefono') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </fieldset>

                                <fieldset class="border border-gray-300 dark:border-gray-600 p-4 rounded-md">
                                    <legend class="text-md font-semibold text-gray-700 dark:text-gray-300 px-2">Administrador de Plataforma (Usuario)</legend>
                                    
                                    @if ($admin_user_id && !$crear_nuevo_admin)
                                        <div class="mb-3 p-3 bg-yellow-50 dark:bg-yellow-700 dark:text-yellow-100 border border-yellow-300 dark:border-yellow-600 rounded-md">
                                            <p class="text-sm">Editando datos del administrador existente: <strong>{{ $admin_name }}</strong> ({{ $admin_email }})</p>
                                            <button type="button" wire:click="$set('crear_nuevo_admin', true)" class="mt-1 text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Reemplazar y Crear un nuevo administrador</button>
                                        </div>
                                    @else
                                        <div class="mb-2">
                                            <input type="checkbox" wire:model.live="crear_nuevo_admin" id="crear_nuevo_admin_chk" class="form-checkbox" 
                                                   @if(!$admin_user_id) checked disabled @endif
                                            >
                                            <label for="crear_nuevo_admin_chk" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                                {{ ($admin_user_id && $crear_nuevo_admin) ? 'Reemplazar y Crear Nuevo Administrador' : ((!$admin_user_id) ? 'Crear Nuevo Usuario Administrador (obligatorio)' : 'Editar Administrador Actual') }}
                                                @if($admin_user_id && !$crear_nuevo_admin) (desmarque para editar el actual) @endif
                                            </label>
                                        </div>
                                    @endif

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">
                                        <div class="lg:col-span-2">
                                            <label for="admin_name" class="label-form">Nombre Completo Admin. <span class="text-red-500">*</span></label>
                                            <input type="text" wire:model.lazy="admin_name" id="admin_name" class="input-field @error('admin_name') input-error @enderror" placeholder="Ej: Juan Alberto Pérez González">
                                            @error('admin_name') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div> </div>
                                        <div>
                                            <label for="admin_rut_usuario" class="label-form">RUT/NUIP/DNI/CEDULA/CPF Admin. <span class="text-red-500">*</span></label>
                                            <input type="text" wire:model.lazy="admin_rut_usuario" id="admin_rut_usuario" placeholder="Ej: 900123456-7" class="input-field @error('admin_rut_usuario') input-error @enderror">
                                            @error('admin_rut_usuario') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="admin_email" class="label-form">Email Admin. <span class="text-red-500">*</span></label>
                                            <input type="email" wire:model.lazy="admin_email" id="admin_email" class="input-field @error('admin_email') input-error @enderror">
                                            @error('admin_email') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        
                                        {{-- CAMPO DE CONFIRMACIÓN DE EMAIL --}}
                                        <div>
                                            <label for="admin_email_confirmation" class="label-form">Confirmar Email Admin. <span class="text-red-500">*</span></label>
                                            <input type="email" wire:model.lazy="admin_email_confirmation" id="admin_email_confirmation" class="input-field">
                                        </div>

                                        <div class="col-span-1 md:col-span-2 lg:col-span-3">
                                            <input type="checkbox" wire:model.live="generar_password_auto" id="generar_password_auto_input_chk" class="form-checkbox">
                                            <label for="generar_password_auto_input_chk" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                                {{ ($crear_nuevo_admin || !$admin_user_id) ? 'Generar contraseña automáticamente y notificar' : 'Generar nueva contraseña automáticamente (reemplazará la actual)'}}
                                            </label>
                                        </div>

                                        @if (!$generar_password_auto)
                                            <div>
                                                <label for="admin_password" class="label-form">
                                                    {{ ($crear_nuevo_admin || !$admin_user_id) ? 'Contraseña' : 'Nueva Contraseña (opcional)'}}
                                                    @if($crear_nuevo_admin || !$admin_user_id) <span class="text-red-500">*</span> @endif
                                                </label>
                                                <input type="password" wire:model.lazy="admin_password" id="admin_password" class="input-field @error('admin_password') input-error @enderror" placeholder="{{ ($admin_user_id && !$crear_nuevo_admin) ? 'Dejar en blanco para no cambiar' : '' }}">
                                                @error('admin_password') <span class="error-message">{{ $message }}</span> @enderror
                                            </div>
                                            <div>
                                                <label for="admin_password_confirmation" class="label-form">
                                                    Confirmar {{ ($crear_nuevo_admin || !$admin_user_id) ? 'Contraseña' : 'Nueva Contraseña'}}
                                                    @if($crear_nuevo_admin || !$admin_user_id) <span class="text-red-500">*</span> @endif
                                                </label>
                                                <input type="password" wire:model.lazy="admin_password_confirmation" id="admin_password_confirmation" class="input-field">
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="mt-4">
                                        <label for="admin_is_active" class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                            <input type="checkbox" wire:model="admin_is_active" id="admin_is_active" class="form-checkbox">
                                            <span class="ml-2">Usuario Administrador Activo</span>
                                        </label>
                                        @error('admin_is_active') <span class="error-message">{{ $message }}</span> @enderror
                                    </div>
                                </fieldset>

                                <fieldset class="border border-gray-300 dark:border-gray-600 p-4 rounded-md text-right">
                                     <div class="flex items-center justify-end">
                                        <input type="checkbox" wire:model="is_active" id="is_active_contratista_modal" class="form-checkbox">
                                        <label for="is_active_contratista_modal" class="ml-2 text-sm text-gray-700 dark:text-gray-300 font-bold">Empresa Activa</label>
                                    </div>
                                    @error('is_active') <span class="error-message">{{ $message }}</span> @enderror
                                </fieldset>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="btn-primary w-full sm:w-auto sm:ml-3">
                                Guardar Contratista
                            </button>
                            <button type="button" wire:click="closeModal()" class="btn-secondary w-full mt-3 sm:mt-0 sm:w-auto">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($showModalVinculaciones)
        <div class="fixed z-20 inset-0 overflow-y-auto" aria-labelledby="modal-title-vinculaciones" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75" aria-hidden="true" wire:click="cerrarModalVinculaciones()"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left shadow-xl transform transition-all sm:my-8 sm:align-middle sm:w-full" style="max-width: 90vw;">
                    <form wire:submit.prevent="guardarVinculaciones">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                            <div class="flex items-center justify-between mb-6 pb-2 border-b dark:border-gray-700">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mr-3">
                                        <x-icons.link class="h-6 w-6 text-blue-600 dark:text-blue-300"/>
                                    </div>
                                    <div>
                                        <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-white" id="modal-title-vinculaciones">
                                            {{ $selectedPivotId ? 'Editar Vinculación' : 'Nueva Vinculación' }}
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Contratista: <span class="font-bold text-gray-700 dark:text-gray-300">{{ $nombreContratistaVinculaciones }}</span>
                                        </p>
                                                                   <button type="button" wire:click="cerrarModalVinculaciones()" class="text-gray-400 hover:text-gray-500">
                                    <x-icons.x-mark class="h-6 w-6"/>
                                </button>
                            </div>

                            <div class="mt-4">
                                @if (session()->has('error_vinculaciones'))
                                    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-md border border-red-400 text-sm flex items-start">
                                        <x-icons.warning class="h-5 w-5 mr-2 flex-shrink-0"/>
                                        {{ session('error_vinculaciones') }}
                                    </div>
                                @endif

                                @foreach($vinculacionesTemp as $index => $row)
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" wire:key="vinc-form-{{ $index }}">
                                        
                                        {{-- Sección 1: Definición --}}
                                        <div class="space-y-4">
                                            <h4 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider flex items-center justify-between">
                                                <span class="flex items-center">
                                                    <span class="w-6 h-6 rounded-full bg-indigo-50 dark:bg-indigo-900/50 flex items-center justify-center mr-2 text-xs">1</span>
                                                    Definición de Vinculación
                                                </span>
                                                
                                                @if($row['id'])
                                                <button type="button" 
                                                    wire:click="eliminarVinculacion({{ $row['id'] }})"
                                                    wire:confirm="⚠️ ADVERTENCIA: ¿Estás seguro de que deseas eliminar esta vinculación?\n\nESTO ELIMINARÁ TAMBIÉN TODAS LAS VINCULACIONES DE LOS SUBCONTRATISTAS asociados a este contrato/lugar en toda la cadena (Sub, Sub-Sub, etc.). Esta acción NO se puede deshacer."
                                                    class="text-red-500 hover:text-red-700 transition-colors flex items-center text-[10px] font-bold uppercase p-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20"
                                                    title="Eliminar vinculación y todos sus descendientes">
                                                    <x-icons.trash class="h-4 w-4 mr-1"/>
                                                    Eliminar
                                                </button>
                                                @endif
                                            </h4>
                                            
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1 uppercase tracking-tighter">Empresa Principal <span class="text-red-500">*</span></label>
                                                <select wire:model.live="vinculacionesTemp.{{ $index }}.mandante_id" class="input-field-sm w-full @if($selectedPivotId) opacity-70 bg-gray-50 @endif" @if($selectedPivotId) disabled @endif>
                                                    <option value="">-- Seleccionar --</option>
                                                    @foreach($mandantesAprobados as $m)
                                                        <option value="{{ $m->id }}">{{ $m->razon_social }}</option>
                                                    @endforeach
                                                </select>
                                                @if($selectedPivotId)
                                                    <p class="text-[10px] text-gray-500 mt-1 italic">La principal no se puede cambiar una vez creada.</p>
                                                @endif
                                            </div>

                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1 uppercase tracking-tighter">Lugar de Trabajo / Departamento</label>
                                                <select wire:model.live="vinculacionesTemp.{{ $index }}.dependencia_id" class="input-field-sm w-full" @if(!$row['mandante_id']) disabled @endif>
                                                    <option value="">-- Ninguno --</option>
                                                    @if($row['mandante_id'])
                                                        @php $mSel = $mandantesAprobados->find($row['mandante_id']); @endphp
                                                        @if($mSel)
                                                            @foreach($mSel->dependencias->where('estado', true) as $d)
                                                                @if(!$isSubcontractorMode || in_array($d->id, $dependenciasPadresPermitidas))
                                                                    <option value="{{ $d->id }}">{{ $d->nombre_jerarquico }}</option>
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                    @endif
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1 uppercase tracking-tighter">Unidad Operativa (U.O.)</label>
                                                <select wire:model.live="vinculacionesTemp.{{ $index }}.unidad_organizacional_mandante_id" class="input-field-sm w-full" @if(!$row['mandante_id']) disabled @endif>
                                                    <option value="">-- Ninguna --</option>
                                                    @if($row['mandante_id'])
                                                        @php $mSel = $mandantesAprobados->find($row['mandante_id']); @endphp
                                                        @if($mSel)
                                                            @foreach($mSel->unidadesOrganizacionales->where('is_active', true) as $uo)
                                                                @if(!$isSubcontractorMode || in_array($uo->id, $uosPadresPermitidas))
                                                                    <option value="{{ $uo->id }}">{{ $uo->nombre_jerarquico }}</option>
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                    @endif
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Sección 2: Identificación y Acreditación --}}
                                        <div class="space-y-4">
                                            <h4 class="text-sm font-bold text-green-600 dark:text-green-400 uppercase tracking-wider flex items-center">
                                                <span class="w-6 h-6 rounded-full bg-green-50 dark:bg-green-900/50 flex items-center justify-center mr-2 text-xs">2</span>
                                                Identificación y Acreditación
                                            </h4>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1 uppercase tracking-tighter">ID Registro</label>
                                                    <input type="text" wire:model.lazy="vinculacionesTemp.{{ $index }}.id_registro" class="input-field-sm w-full" placeholder="Automático" @if(!empty($row['generar_id_registro_auto'])) disabled @endif>
                                                    <label class="flex items-center mt-1 cursor-pointer">
                                                        <input type="checkbox" wire:model.live="vinculacionesTemp.{{ $index }}.generar_id_registro_auto" class="form-checkbox h-3 w-3">
                                                        <span class="ml-1 text-[10px] text-gray-500">Auto</span>
                                                    </label>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1 uppercase tracking-tighter">Código SAP</label>
                                                    <input type="text" wire:model.lazy="vinculacionesTemp.{{ $index }}.sap" class="input-field-sm w-full font-mono uppercase @if(!auth()->user()->hasRole('ASEM_Admin')) bg-gray-50 opacity-70 @endif" @if(!auth()->user()->hasRole('ASEM_Admin')) disabled @endif>
                                                </div>
                                            </div>

                                            <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-100 dark:border-blue-800">
                                                <div class="flex items-center mb-2">
                                                    <input type="checkbox" wire:model.live="vinculacionesTemp.{{ $index }}.acredita" class="form-checkbox text-indigo-600 mr-2">
                                                    <label class="text-xs font-bold text-indigo-800 dark:text-indigo-300">REQUIERE ACREDITACIÓN</label>
                                                </div>
                                                @if($row['acredita'])
                                                    <div class="grid grid-cols-2 gap-2 mt-2">
                                                        <div>
                                                            <label class="text-[10px] uppercase font-bold text-gray-500">Inicio</label>
                                                            <input type="date" wire:model.lazy="vinculacionesTemp.{{ $index }}.fecha_inicio_acredita" class="input-field-sm w-full text-[10px] py-1">
                                                        </div>
                                                        <div>
                                                            <label class="text-[10px] uppercase font-bold text-gray-500">Fin</label>
                                                            <input type="date" wire:model.lazy="vinculacionesTemp.{{ $index }}.fecha_fin_acredita" class="input-field-sm w-full text-[10px] py-1">
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="bg-purple-50 dark:bg-purple-900/20 p-3 rounded-lg border border-purple-100 dark:border-purple-800">
                                                <div class="flex items-center mb-2">
                                                    <input type="checkbox" wire:model.live="vinculacionesTemp.{{ $index }}.verifica" class="form-checkbox text-purple-600 mr-2">
                                                    <label class="text-xs font-bold text-purple-800 dark:text-purple-300">REQUIERE VERIFICACIÓN</label>
                                                </div>
                                                @if($row['verifica'])
                                                    <div class="grid grid-cols-2 gap-2 mt-2">
                                                        <div>
                                                            <label class="text-[10px] uppercase font-bold text-gray-500">Inicio</label>
                                                            <input type="date" wire:model.lazy="vinculacionesTemp.{{ $index }}.fecha_inicio_verifica" class="input-field-sm w-full text-[10px] py-1">
                                                        </div>
                                                        <div>
                                                            <label class="text-[10px] uppercase font-bold text-gray-500">Fin</label>
                                                            <input type="date" wire:model.lazy="vinculacionesTemp.{{ $index }}.fecha_fin_verifica" class="input-field-sm w-full text-[10px] py-1">
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Sección 3: Contrato y Cuotas --}}
                                        <div class="space-y-4">
                                            <h4 class="text-sm font-bold text-orange-600 dark:text-orange-400 uppercase tracking-wider flex items-center">
                                                <span class="w-6 h-6 rounded-full bg-orange-50 dark:bg-orange-900/50 flex items-center justify-center mr-2 text-xs">3</span>
                                                Contrato y Control de Cuotas
                                            </h4>

                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1 uppercase tracking-tighter">N° Contrato</label>
                                                @if(!$isSubcontractorMode)
                                                <input type="text" wire:model.lazy="vinculacionesTemp.{{ $index }}.numero_contrato" class="input-field-sm w-full" placeholder="Ej: 1000">
                                                @else
                                                <select wire:model.lazy="vinculacionesTemp.{{ $index }}.numero_contrato" class="input-field-sm w-full">
                                                    <option value="">-- Seleccionar Padre --</option>
                                                    @foreach($contratosPadresPermitidos as $num)
                                                        @if(!empty($num))
                                                        <option value="{{ $num }}">{{ $num }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                                <span class="text-[10px] text-gray-400 italic">Heredado de principal</span>
                                                @endif
                                            </div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1 uppercase tracking-tighter">Tipo Contrato</label>
                                                    <select wire:model.lazy="vinculacionesTemp.{{ $index }}.tipo_contrato_id" class="input-field-sm w-full">
                                                        <option value="">-- Seleccionar --</option>
                                                        @foreach($tiposContrato as $tc)
                                                            <option value="{{ $tc->id }}">{{ $tc->nombre }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="md:col-span-2">
                                                    @include('livewire._partials._multi_select_condicion', [
                                                        'opciones'      => $tiposCondicionDisponibles,
                                                        'seleccionados' => $row['condiciones_ids'] ?? [],
                                                        'wireKey'       => "vinculacionesTemp.{$index}.condiciones_ids",
                                                        'label'         => 'Condición(es) de Empresa',
                                                        'placeholder'   => 'Buscar condición...',
                                                    ])
                                                </div>
                                            </div>

                                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-dashed border-gray-300 dark:border-gray-500">
                                                <div class="flex justify-between items-center mb-3">
                                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1 uppercase tracking-tighter !mb-0">Cuota General Trab.</label>
                                                    <input type="number" wire:model.lazy="vinculacionesTemp.{{ $index }}.trabajadores_cuota" class="w-20 px-2 py-1 border rounded dark:bg-gray-700 text-center text-sm" placeholder="Ej: 10">
                                                </div>
                                                <hr class="my-3 border-gray-200 dark:border-gray-600">
                                                <div class="flex flex-col items-center">
                                                    <span class="text-[10px] text-gray-500 mb-2 italic">{{ $isSubcontractorMode ? 'Cargos Compartidos (Heredados del Padre)' : 'Configuración Granular de Cargos' }}</span>
                                                    <button type="button" wire:click="iniciarGestionCargos({{ $index }})" class="w-full flex items-center justify-center px-4 py-2 bg-white dark:bg-gray-800 border border-indigo-600 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg text-xs font-bold transition">
                                                        <x-icons.clipboard-list class="h-4 w-4 mr-2"/>
                                                        {{ $isSubcontractorMode ? 'Ver Cargos Autorizados' : 'Configurar Cargos Autorizados' }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                @if(!$isSubcontractorMode && !$selectedPivotId)
                                    <div class="mt-4 flex justify-start items-center bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg border border-dashed border-gray-300 dark:border-gray-600">
                                        <button type="button" wire:click="agregarFilaVinculacion" class="inline-flex items-center px-3 py-1 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring focus:ring-indigo-300 disabled:opacity-25 transition">
                                            <x-icons.plus class="h-4 w-4 mr-1"/>
                                            Agregar Otra Fila
                                        </button>
                                    </div>
                                @endif
                                
                                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-4 px-1 flex items-center">
                                    <x-icons.information-circle class="h-3 w-3 mr-1 text-blue-500"/>
                                    <span class="font-bold">Ayuda:</span> Para completar la vinculación, asigne una Principal y al menos un Lugar de Trabajo o una U.O.
                                </p>
                                
                                @if($isSubcontractorMode)
                                    <p class="mt-4 p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded text-[10px] text-yellow-700 dark:text-yellow-400 italic">
                                        <strong>Modo Subcontratista:</strong> Seleccione las vinculaciones del padre ({{ $vinculacionesTemp[0]['parent_razon_social'] ?? 'Padre' }}) en las que participará este subcontratista.
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="bg-gray-100 dark:bg-gray-700/50 px-6 py-4 flex flex-col sm:flex-row justify-end items-center gap-4">
                            <div class="flex gap-3 w-full sm:w-auto">
                                <button type="button" wire:click="cerrarModalVinculaciones()" class="btn-secondary flex-1 sm:flex-initial">
                                    Cancelar
                                </button>
                                <button type="button" wire:click="guardarVinculaciones" class="btn-primary flex-1 sm:flex-initial !bg-blue-600 hover:!bg-blue-700">
                                    {{ $selectedPivotId ? 'Guardar Cambios' : 'Crear Vinculación' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($showModalAsignarMandante)
        <div class="fixed z-20 inset-0 overflow-y-auto" aria-labelledby="modal-title-mandante" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75" aria-hidden="true" wire:click="cerrarModalAsignarMandante()"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit.prevent="guardarAsignacionMandante">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                            <div class="sm:flex sm:items-start mb-4">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 dark:bg-purple-900 sm:mx-0 sm:h-10 sm:w-10">
                                    <x-icons.plus class="h-6 w-6 text-purple-600 dark:text-purple-300"/>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title-mandante">
                                        Asignar Nueva Principal
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        A: {{ $nombreContratistaParaAsignar }}
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                @if(count($mandantesParaAsignar) > 0)
                                    <div>
                                        <label for="nuevoMandanteId" class="label-form">Seleccione la nueva Principal a vincular <span class="text-red-500">*</span></label>
                                        <select wire:model="nuevoMandanteId" id="nuevoMandanteId" class="input-field @error('nuevoMandanteId') input-error @enderror">
                                            <option value="">-- Seleccione --</option>
                                            @foreach($mandantesParaAsignar as $mandante)
                                                <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                                            @endforeach
                                        </select>
                                        @error('nuevoMandanteId') <span class="error-message">{{ $message }}</span> @enderror
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">Este contratista ya está vinculado a todas las principales activas disponibles.</p>
                                @endif
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            @if(count($mandantesParaAsignar) > 0)
                                <button type="submit" 
                                        wire:confirm="¿Está seguro de que desea vincular a {{ $nombreContratistaParaAsignar }} con la principal seleccionada?"
                                        class="btn-primary-purple w-full sm:w-auto sm:ml-3">
                                    Guardar Asignación
                                </button>
                            @endif
                            <button type="button" wire:click="cerrarModalAsignarMandante()" class="btn-secondary w-full mt-3 sm:mt-0 sm:w-auto">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL GESTIÓN DE CARGOS Y CUOTAS --}}
    @if($showModalGestionCargos)
        <div class="fixed z-[30] inset-0 overflow-y-auto" aria-labelledby="modal-title-cargos" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75" aria-hidden="true" wire:click="$set('showModalGestionCargos', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border-t-4 border-indigo-600">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                        <div class="flex items-center justify-between mb-4 border-b dark:border-gray-700 pb-2">
                            <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-white" id="modal-title-cargos">
                                <x-icons.building-office class="inline-block h-5 w-5 mr-1 text-indigo-600"/>
                                Configura Cargos y Cuotas
                            </h3>
                            <button wire:click="$set('showModalGestionCargos', false)" class="text-gray-400 hover:text-gray-500">
                                <x-icons.x-mark class="h-6 w-6"/>
                            </button>
                        </div>
                        
                        <div class="mb-4">
                            <p class="text-xs text-gray-600 dark:text-gray-400 italic">
                                @if($isSubcontractorMode)
                                    Estos son los cargos autorizados por la empresa principal para esta vinculación. Como subcontratista, usted hereda estas restricciones.
                                @else
                                    Marque los cargos autorizados para esta vinculación. 
                                    <span class="font-bold">Nota:</span> Si no selecciona ninguno, se permitirán todos los cargos sin restricción.
                                @endif
                            </p>
                        </div>

                        <div class="max-h-[60vh] overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase">¿Aplica?</th>
                                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase">Nombre Cargo</th>
                                        <th class="px-2 py-2 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase" style="width: 100px;">Cuota</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($cargosDisponiblesModal as $cargo)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 font-semibold" wire:key="cargo-modal-{{ $cargo->id }}">
                                            <td class="px-2 py-2">
                                                <input type="checkbox" 
                                                       wire:model.live="cargosSeleccionadosTemp.{{ $cargo->id }}.selected"
                                                       class="form-checkbox h-4 w-4 text-indigo-600 border-gray-300 rounded"
                                                       @if($isSubcontractorMode) disabled @endif>
                                            </td>
                                            <td class="px-2 py-2 text-sm text-gray-900 dark:text-gray-200">
                                                {{ $cargo->nombre_cargo }}
                                            </td>
                                            <td class="px-2 py-2 text-center">
                                                <input type="number" 
                                                       wire:model="cargosSeleccionadosTemp.{{ $cargo->id }}.cuota"
                                                       placeholder="{{ $isSubcontractorMode ? 'Compartida' : 'Sin límite' }}"
                                                       class="w-full text-xs px-2 py-1 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-gray-200 text-center @if(empty($cargosSeleccionadosTemp[$cargo->id]['selected'])) opacity-40 @endif"
                                                       @if(empty($cargosSeleccionadosTemp[$cargo->id]['selected']) || $isSubcontractorMode) disabled @endif>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-2 py-4 text-center text-sm text-gray-500 italic">No hay cargos definidos para este Mandante.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse space-x-reverse space-x-2">
                        @if(!$isSubcontractorMode)
                        <button type="button" wire:click="guardarCargosTemp()" class="btn-primary-teal w-full sm:w-auto">
                            Aplicar Configuración
                        </button>
                        @endif
                        <button type="button" wire:click="$set('showModalGestionCargos', false)" class="btn-secondary w-full sm:w-auto">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @push('styles')
    <style>
        .label-form { @apply block text-sm font-medium text-gray-700 dark:text-gray-300; }
        .input-field { @apply mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200; }
        .input-field-sm { @apply text-xs px-2 py-1; }
        .input-error { @apply border-red-500 dark:border-red-500; }
        .error-message { @apply text-red-500 text-xs mt-1; }
        .text-xxs { font-size: 0.65rem; line-height: 0.85rem; }
        .form-checkbox { @apply h-4 w-4 text-indigo-600 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500 dark:bg-gray-700 dark:focus:ring-indigo-600 dark:ring-offset-gray-800; }
        .btn-primary { @apply px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150; }
        .btn-primary-teal { @apply px-4 py-2 bg-teal-600 text-white font-semibold rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150; }
        .btn-primary-purple { @apply px-4 py-2 bg-purple-600 text-white font-semibold rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150; }
        .btn-secondary { @apply px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150; }
    </style>
    @endpush
</div>