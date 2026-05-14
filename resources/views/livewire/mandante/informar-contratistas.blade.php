<div class="py-4 px-4 sm:px-6 lg:px-8 max-w-full mx-auto">

    {{-- Título --}}
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">INFORMAR CONTRATISTAS PARA VERIFICACIÓN</h1>

    {{-- Mensajes flash --}}
    @if (session()->has('message'))
        <div class="mb-4 p-3 bg-green-100 dark:bg-green-900/40 border border-green-400 text-green-700 dark:text-green-300 rounded-lg text-sm">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 p-3 bg-red-100 dark:bg-red-900/40 border border-red-400 text-red-700 dark:text-red-300 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="flex flex-wrap items-end gap-4">

            {{-- Selector de Principal (solo ASEM_Admin) --}}
            @if(Auth::user()->hasRole('ASEM_Admin'))
            <div class="flex-1 min-w-[250px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Principal</label>
                <select wire:model.live="mandanteIdSeleccionado" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200">
                    <option value="">-- Seleccione --</option>
                    @foreach($mandantesDisponibles as $m)
                        <option value="{{ $m->id }}">{{ $m->razon_social }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Período --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mes de Nómina (a verificar)</label>
                <div class="flex gap-2">
                    <select wire:model.live="periodoMes" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}">{{ ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'][$m] }}</option>
                        @endfor
                    </select>
                    <select wire:model.live="periodoAnio" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200">
                        @for($y = now()->year - 1; $y <= now()->year + 2; $y++)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            {{-- Info período --}}
            <div class="flex-1 flex items-center">
                <div class="bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-700 rounded-lg px-4 py-2">
                    <span class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">
                        📋 {{ $mandanteNombre }} — {{ $nombreMes }} {{ $periodoAnio }}
                    </span>
                </div>
            </div>

            {{-- Botón Guardar --}}
            @if(!$esSoloLectura)
            <div>
                <button wire:click="guardarSelecciones"
                        wire:confirm="¿Guardar las selecciones para {{ $nombreMes }} {{ $periodoAnio }}?"
                        class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition shadow-md flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    GUARDAR SELECCIONES
                </button>
            </div>
            @else
            <div>
                <span class="px-4 py-2 bg-gray-200 text-gray-500 font-semibold rounded-lg text-sm border border-gray-300 flex items-center gap-2">
                    🔒 Solo lectura
                </span>
            </div>
            @endif
        </div>
    </div>

    {{-- Tabla de contratistas --}}
    @if($mandanteIdSeleccionado)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-100 dark:bg-gray-700 sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase border border-gray-300 dark:border-gray-600">#</th>
                        <th class="px-3 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase border border-gray-300 dark:border-gray-600">ID</th>
                        <th class="px-3 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase border border-gray-300 dark:border-gray-600">SAP</th>
                        <th class="px-3 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase border border-gray-300 dark:border-gray-600">Razón Social</th>
                        <th class="px-3 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase border border-gray-300 dark:border-gray-600">{{ config('pais.code') === 'cl' ? 'RUT' : (config('pais.code') === 'co' ? 'NIT' : 'RUT/NIT') }}</th>
                        <th class="px-3 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase border border-gray-300 dark:border-gray-600">Lugar/Dto</th>
                        <th class="px-3 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase border border-gray-300 dark:border-gray-600">U.O.</th>
                        <th class="px-3 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase border border-gray-300 dark:border-gray-600">Nro. Contrato</th>
                        <th class="px-3 py-3 text-center text-xs font-bold text-gray-700 dark:text-gray-200 uppercase border border-gray-300 dark:border-gray-600" style="min-width: 280px">
                            Verifica (Inicio - Fin)
                        </th>
                        <th class="px-3 py-3 text-center text-xs font-bold text-gray-700 dark:text-gray-200 uppercase border border-gray-300 dark:border-gray-600" style="min-width: 100px">Seleccionar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($vinculaciones as $index => $v)
                                                                        @php
                            // Lógica de colores estilo sistema anterior:
                            // 1. Filas simples sin subcontratistas: alternan Blanco y Gris claro
                            // 2. Grupos con subcontratistas: fondo Amarillo
                            // 3. Grupos adyacentes con subcontratistas: alternan a fondo Naranja
                            
                            $correlativoArray = explode('.', $v->correlativo_jerarquico);
                            $numeroBase = (int) $correlativoArray[0];
                            
                            // Para saber si el grupo tiene subcontratistas, necesitamos verificar si hay más elementos con este número base
                            // Como no podemos hacer look-ahead fácilmente en blade, contaremos en la colección original
                            $tieneSubcontratistas = $vinculaciones->filter(function($item) use ($numeroBase) {
                                return str_starts_with($item->correlativo_jerarquico, $numeroBase . '.');
                            })->count() > 0;

                            // Establecer color
                            if ($tieneSubcontratistas) {
                                // Alternar entre Amarillo y Naranja para grupos con subcontratistas
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
                                // Para simples, alternamos blanco y gris basado en su index global de simples
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

                            $cuoId = $v->cuo_id;
                            $seleccionado = $selecciones[$cuoId] ?? false;
                            $cubrePeriodo = $fechasVerifica[$cuoId]['cubre_periodo'] ?? false;
                            
                            $indentationClass = count($correlativoArray) > 1 ? 'pl-' . ((count($correlativoArray) - 1) * 3) : '';
                        @endphp
                        <tr class="{{ $fondoClase }} hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors duration-150">
                            <td class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400 border border-gray-300 dark:border-gray-600 whitespace-nowrap">{{ $v->correlativo_jerarquico }}</td>
                            <td class="px-3 py-2 text-sm font-medium text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-600">{{ $v->id_registro ?? '-' }}</td>
                            <td class="px-3 py-2 text-sm font-mono text-indigo-700 dark:text-indigo-300 border border-gray-300 dark:border-gray-600 whitespace-nowrap">
                                {{ $v->sap ?? '-' }}
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 whitespace-nowrap {{ $indentationClass }}">
                                @if(count($correlativoArray) > 1) └ @endif {{ $v->razon_social }}
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600">{{ $v->rut }}</td>
                            <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 max-w-[150px] truncate" title="{{ $v->lugar_nombre }}">{{ $v->lugar_nombre ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 max-w-[150px] truncate" title="{{ $v->uo_nombre }}">{{ $v->uo_nombre ?: '-' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 max-w-[150px] truncate" title="{{ $v->numero_contrato }}">{{ $v->numero_contrato ?: '-' }}</td>
                            <td class="px-3 py-2 border border-gray-300 dark:border-gray-600">
                                                                <div class="flex items-center justify-center gap-1 relative">
                                    <input type="date"
                                           wire:model.lazy="fechasVerifica.{{ $cuoId }}.fecha_inicio_verifica"
                                           {{ $esSoloLectura ? 'disabled readonly' : '' }}
                                           class="w-[110px] flex-shrink-0 text-center text-xs px-1 py-1 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-gray-200 focus:ring-indigo-500 focus:border-indigo-500 {{ $esSoloLectura ? 'opacity-60 cursor-not-allowed bg-gray-100 dark:bg-gray-600' : '' }}">
                                    <span class="text-gray-400 text-xs flex-shrink-0">—</span>
                                    <input type="date"
                                           wire:model.lazy="fechasVerifica.{{ $cuoId }}.fecha_fin_verifica"
                                           {{ $esSoloLectura ? 'disabled readonly' : '' }}
                                           class="w-[110px] flex-shrink-0 text-center text-xs px-1 py-1 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-gray-200 focus:ring-indigo-500 focus:border-indigo-500 {{ $esSoloLectura ? 'opacity-60 cursor-not-allowed bg-gray-100 dark:bg-gray-600' : '' }}">
                                    @if(!$cubrePeriodo)
                                        <span class="text-red-500 text-sm font-bold absolute -right-5" title="Fechas fuera del período">⚠️</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-2 text-center border border-gray-300 dark:border-gray-600"
                                @if(!$cubrePeriodo) title="Debe modificar las fechas para que cubran el período antes de poder seleccionar a este contratista" @endif>
                                <input type="checkbox"
                                       wire:model="selecciones.{{ $cuoId }}"
                                       @if(!$cubrePeriodo || $esSoloLectura) disabled @endif
                                       class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 focus:ring-indigo-500 dark:bg-gray-700 
                                              {{ (!$cubrePeriodo || $esSoloLectura) ? 'cursor-not-allowed opacity-50 bg-gray-200' : 'cursor-pointer text-indigo-600 ' . ($seleccionado ? 'bg-indigo-600' : '') }}">
                                @if(!$cubrePeriodo && !$seleccionado)
                                    <div class="text-[11px] font-bold text-red-600 mt-1 leading-tight">Fuera<br>de rango</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No se encontraron contratistas con verificación activa para esta principal.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 flex justify-between items-center">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            <span class="font-bold">Indicaciones:</span> Los contratistas marcados serán informados para verificación en el período {{ $nombreMes }} {{ $periodoAnio }}.
            Los que están desmarcados no podrán enviar documentos para ese período.
            Puede modificar las fechas de verificación directamente en la tabla.
        </p>
        @if(!$esSoloLectura)
        <button wire:click="guardarSelecciones"
                wire:confirm="¿Guardar las selecciones para {{ $nombreMes }} {{ $periodoAnio }}?"
                class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition shadow-md">
            GUARDAR SELECCIONES
        </button>
        @endif
    </div>
    @else
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl p-6 text-center">
        <p class="text-yellow-700 dark:text-yellow-300 text-sm">Seleccione una Principal para ver los contratistas a informar.</p>
    </div>
    @endif

    @push('styles')
    <style>
        .btn-primary { @apply px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150; }
    </style>
    @endpush
</div>