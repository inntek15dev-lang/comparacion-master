<div class="py-6 px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white uppercase">Verificación Laboral - Repositorio</h1>
            <div class="flex items-center gap-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Consulte la documentación mensual cargada por sus contratistas.</p>
                @if($inicio_global)
                    <div class="px-3 py-1 bg-green-100 border border-green-200 rounded-full flex items-center gap-2" title="Punto de partida para contratistas antiguos">
                        <span class="text-green-700 font-black text-[9px] uppercase tracking-widest">Inicio del Servicio:</span>
                        <span class="text-green-800 font-bold text-[10px] uppercase">Periodo Remuneraciones {{ $inicio_global['periodo'] }}</span>
                    </div>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-2 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <span class="text-xs font-bold text-gray-500 uppercase">Año:</span>
            <input type="number" wire:model.live="anio_seleccionado" wire:change="cargarCarpetas" class="w-20 rounded-md border-gray-300 dark:bg-gray-700 dark:text-white text-xs py-1">
        </div>
    </div>

    <!-- SELECTOR DE MESES (TIPO TIMELINE) -->
    <div class="mb-8 overflow-x-auto pb-2">
        <div class="flex gap-2">
            @foreach($calendario as $cal)
                <button wire:click="setPeriodo({{ $cal->mes }})" 
                        class="px-4 py-2 rounded-full text-[10px] font-bold uppercase transition-all whitespace-nowrap {{ $mes_seleccionado == $cal->mes ? 'bg-blue-600 text-white shadow-lg' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-300' }}">
                    Periodo {{ $cal->nombre_periodo }}
                </button>
            @endforeach
            @if($calendario->isEmpty())
                <div class="text-xs text-amber-600 italic">No hay meses configurados en el calendario para el año {{ $anio_seleccionado }}.</div>
            @endif
        </div>
    </div>

    <!-- LISTADO DE CARPETAS -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-center font-bold text-gray-500 uppercase tracking-wider w-12">#</th>
                    <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Contratista</th>
                    <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Vinculación (Lugar/UO)</th>
                    <th class="px-4 py-3 text-center font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Documentos Cargados</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($contratistas_carpetas as $index => $item)
                    @php
                        $correlativoJerarquico = $item['correlativo_jerarquico'] ?? ($index + 1);
                        $correlativoArray = explode('.', (string)$correlativoJerarquico);
                        $numeroBase = (int) $correlativoArray[0];

                        // ¿Este grupo tiene jerarquía?
                        $tieneJerarquia = false;
                        if (isset($item['correlativo_jerarquico'])) {
                            $tieneJerarquia = collect($contratistas_carpetas)->filter(
                                fn($i) => isset($i['correlativo_jerarquico']) && 
                                             str_starts_with((string)$i['correlativo_jerarquico'], $numeroBase . '.')
                            )->count() > 1;
                        }

                        if ($tieneJerarquia) {
                            static $grupoCounter = 0;
                            static $lastBaseGroup = null;
                            if ($lastBaseGroup !== $numeroBase) { 
                                $grupoCounter++; 
                                $lastBaseGroup = $numeroBase; 
                            }
                            $fondoClase = ($grupoCounter % 2 == 1)
                                ? 'bg-yellow-50 dark:bg-yellow-900/20'
                                : 'bg-orange-50 dark:bg-orange-900/20';
                        } else {
                            $fondoClase = $loop->even ? 'bg-gray-50 dark:bg-gray-750' : 'bg-white dark:bg-gray-800';
                        }

                        $nivel = count($correlativoArray) - 1;
                        $indentClass = $nivel > 0 ? 'pl-' . ($nivel * 4) : '';
                    @endphp
                    <tr class="{{ $fondoClase }} hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors border-l-4 {{ $nivel > 0 ? 'border-blue-400' : 'border-transparent' }}">
                        <td class="px-4 py-4 text-center text-[10px] {{ $nivel > 0 ? 'font-black text-blue-600' : 'text-gray-400' }} font-mono">
                            {{ $correlativoJerarquico }}
                        </td>
                        <td class="px-4 py-4 {{ $indentClass }}">
                            <div class="font-bold text-gray-800 dark:text-white uppercase">
                                @if($nivel > 0) <span class="text-blue-500 mr-1">└</span> @endif
                                {{ $item['vinculacion']->contratista->razon_social }}
                            </div>
                            <div class="text-[10px] text-gray-500 italic">{{ $item['vinculacion']->contratista->rut }}</div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="font-medium text-gray-700 dark:text-gray-300 uppercase">{{ $item['vinculacion']->unidadOrganizacionalMandante->nombre_unidad }}</div>
                            <div class="text-[10px] text-gray-500">{{ $item['vinculacion']->dependencia->nombre ?? 'N/A' }}</div>
                            <div class="text-[9px] text-blue-600 font-bold uppercase mt-1">Contrato: {{ $item['vinculacion']->numero_contrato ?: 'N/A' }}</div>
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if($item['carpeta'])
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full font-bold uppercase text-[9px]">Cargado</span>
                            @else
                                <span class="px-2 py-1 bg-amber-100 text-amber-800 rounded-full font-bold uppercase text-[9px]">Pendiente</span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            @if($item['carpeta'] && $item['documentos']->isNotEmpty())
                                <div class="space-y-1">
                                    @foreach($item['documentos']->groupBy('requisito_verificacion_id') as $requisitoId => $docs)
                                        <div class="mb-2">
                                            <div class="text-[10px] font-bold text-gray-500 uppercase mb-1">{{ $docs->first()->requisito->nombre ?? 'Requisito #' . $requisitoId }}</div>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($docs as $doc)
                                                    <a href="{{ Storage::url($doc->path) }}" target="_blank" class="inline-flex items-center px-2 py-0.5 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-blue-600 hover:bg-blue-50 transition-colors">
                                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"></path></svg>
                                                        PDF
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-gray-400 italic">Sin documentos</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center text-gray-500 italic">No se encontraron contratistas con servicio de verificación para este periodo.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
