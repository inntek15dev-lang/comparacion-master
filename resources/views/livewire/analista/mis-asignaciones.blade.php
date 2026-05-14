<div class="p-4 bg-gray-100 dark:bg-gray-900 min-h-screen">

    <!-- TITULO -->
    <div class="mb-4">
        <h1 class="text-xl font-bold text-gray-700 dark:text-white uppercase tracking-tighter">
            ANALIZAR PERIODOS
        </h1>
        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-black">
            Periodos asignados para revision de documentacion laboral
        </p>
    </div>

    <!-- MENSAJES -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- CONTADORES -->
    <div class="grid grid-cols-4 gap-4 mb-4">
        <div class="bg-blue-500 text-white p-4 rounded-lg shadow">
            <div class="text-3xl font-black">{{ $totalAsignados }}</div>
            <div class="text-[10px] font-bold uppercase">Asignados</div>
        </div>
        <div class="bg-purple-500 text-white p-4 rounded-lg shadow">
            <div class="text-3xl font-black">{{ $totalEnRevision }}</div>
            <div class="text-[10px] font-bold uppercase">En Revision</div>
        </div>
        <div class="bg-amber-500 text-white p-4 rounded-lg shadow">
            <div class="text-3xl font-black">{{ $totalDevueltos }}</div>
            <div class="text-[10px] font-bold uppercase">Devueltos</div>
        </div>
        <div class="bg-green-500 text-white p-4 rounded-lg shadow">
            <div class="text-3xl font-black">{{ $totalRevisados }}</div>
            <div class="text-[10px] font-bold uppercase">Finalizados</div>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="bg-[#004b75] p-4 rounded-lg shadow mb-4">
        <div class="text-white text-[10px] font-black uppercase mb-3 border-b border-white/30 pb-2">
            FILTROS DE BUSQUEDA
        </div>
        <div class="grid grid-cols-1 md:grid-cols-7 gap-3">
            <!-- Principal -->
            <div>
                <label class="text-white/70 text-[9px] font-bold uppercase block mb-1">PRINCIPAL</label>
                <select wire:model.live="mandante_id" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-700 dark:text-white">
                    <option value="">-- Todos --</option>
                    @foreach($mandantes as $m)
                        <option value="{{ $m->id }}">{{ $m->razon_social }}</option>
                    @endforeach
                </select>
            </div>
            <!-- Contratista -->
            <div>
                <label class="text-white/70 text-[9px] font-bold uppercase block mb-1">CONTRATISTA</label>
                <select wire:model.live="contratista_id" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-700 dark:text-white" {{ !$mandante_id ? 'disabled' : '' }}>
                    <option value="">-- Todos --</option>
                    @foreach($contratistas as $c)
                        <option value="{{ $c->id }}">{{ Str::limit($c->razon_social, 30) }}</option>
                    @endforeach
                </select>
            </div>
            <!-- Anio -->
            <div>
                <label class="text-white/70 text-[9px] font-bold uppercase block mb-1">AÑO</label>
                <select wire:model.live="anio" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-700 dark:text-white">
                    @for($y = date('Y'); $y >= 2024; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <!-- Mes -->
            <div>
                <label class="text-white/70 text-[9px] font-bold uppercase block mb-1">MES</label>
                <select wire:model.live="mes" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-700 dark:text-white">
                    <option value="">-- Todos --</option>
                    @foreach(['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $i => $nombre)
                        <option value="{{ $i + 1 }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <!-- Estado -->
            <div>
                <label class="text-white/70 text-[9px] font-bold uppercase block mb-1">ESTADO</label>
                <select wire:model.live="estado_revision" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-700 dark:text-white font-bold">
                    <option value="">-- Todos --</option>
                    <option value="ASIGNADO">Asignado</option>
                    <option value="PROCESO">En Revision</option>
                    <option value="FINALIZADO">Finalizado</option>
                    <option value="DEVUELTO">Devuelto</option>
                </select>
            </div>
            <!-- Envío -->
            <div>
                <label class="text-white/70 text-[9px] font-bold uppercase block mb-1">ENVÍO</label>
                <select wire:model.live="estado_plazo" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-700 dark:text-white">
                    <option value="">-- Todos --</option>
                    <option value="NORMAL">✓ Dentro de Plazo</option>
                    <option value="FUERA_PLAZO">⚠ Fuera de Plazo</option>
                </select>
            </div>
            <!-- Limpiar -->
            <div class="flex items-end">
                <button wire:click="limpiarFiltros" class="w-full bg-gray-500 hover:bg-gray-600 text-white text-[10px] font-bold px-3 py-1.5 rounded uppercase">
                    Limpiar
                </button>
            </div>
        </div>
    </div>

    <!-- TABLA DE ASIGNACIONES -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="bg-[#003a5c] text-white px-4 py-2 text-[10px] font-black uppercase flex justify-between items-center">
            <span>MIS ASIGNACIONES ({{ $carpetas->total() }} registros)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <tr>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase w-8">N</th>
                        <th class="px-2 py-2 text-left text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Principal</th>
                        <th class="px-2 py-2 text-left text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Contratista</th>
                        <th class="px-2 py-2 text-left text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">ID REG</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Lugar/Contrato</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Periodo</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Estado</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">ENVÍO</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">F. Asignacion</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($carpetas as $index => $carpeta)
                        @php
                            $correlativoJerarquico = $carpeta->correlativo_jerarquico ?? ($carpetas->firstItem() + $index);
                            $correlativoArray = explode('.', (string)$correlativoJerarquico);
                            $numeroBase = (int) $correlativoArray[0];

                            $tieneJerarquia = false;
                            if (isset($carpeta->correlativo_jerarquico)) {
                                $tieneJerarquia = collect($carpetas->items())->filter(
                                    fn($item) => isset($item->correlativo_jerarquico) &&
                                                 str_starts_with((string)$item->correlativo_jerarquico, $numeroBase . '.')
                                )->count() > 0;
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
                        <tr class="{{ $fondoClase }} {{ $carpeta_detalle_id == $carpeta->id ? 'ring-2 ring-blue-400' : '' }} hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors duration-150 border-l-4 {{ $nivel > 0 ? 'border-blue-400' : 'border-transparent' }}">

                            <td class="px-2 py-1.5 text-center text-[10px] {{ $nivel > 0 ? 'font-black text-blue-600' : 'text-gray-500 font-mono' }}">
                                {{ $correlativoJerarquico }}
                            </td>

                            <td class="px-2 py-1.5 text-[10px] font-bold text-gray-700 dark:text-gray-300">
                                {{ Str::limit($carpeta->vinculacion->unidadOrganizacional->mandante->razon_social ?? '-', 18) }}
                            </td>

                            <td class="px-2 py-1.5 text-[10px] font-bold text-gray-900 dark:text-white uppercase leading-tight {{ $indentClass }}">
                                @if($nivel > 0) <span class="text-blue-500 mr-1">L</span> @endif
                                {{ Str::limit($carpeta->vinculacion->contratista->razon_social ?? '-', 25) }}
                                <div class="text-[8px] text-gray-400 font-mono">{{ $carpeta->vinculacion->contratista->rut ?? '' }}</div>
                            </td>

                            <td class="px-2 py-1.5 text-[10px] font-bold text-blue-700 dark:text-blue-400">
                                {{ $carpeta->vinculacion->id_registro ?? '-' }}
                            </td>

                            <td class="px-2 py-1.5 text-center">
                                <span class="block text-[9px] font-bold text-gray-700 dark:text-gray-300 uppercase">
                                    {{ Str::limit($carpeta->vinculacion->dependencia->nombre ?? '-', 15) }}
                                </span>
                                <span class="block text-[8px] font-mono text-blue-600 dark:text-blue-400">
                                    CT: {{ $carpeta->vinculacion->numero_contrato ?? 'N/A' }}
                                </span>
                            </td>

                            <td class="px-2 py-1.5 text-center">
                                <span class="bg-gray-100 text-gray-800 border border-gray-300 text-[9px] font-black px-1.5 py-0.5 rounded uppercase whitespace-nowrap">
                                    {{ strtoupper(substr($carpeta->nombre_mes, 0, 3)) }} {{ $carpeta->anio }}
                                </span>
                            </td>

                            <td class="px-2 py-1.5 text-center">
                                @switch($carpeta->estado_revision)
                                    @case('ASIGNADO')
                                    @case('EN_CARGA')
                                        <span class="bg-blue-100 text-blue-700 border border-blue-200 text-[8px] font-black px-2 py-0.5 rounded-full {{ $carpeta->estado_revision === 'EN_CARGA' ? 'animate-pulse shadow-sm' : '' }}">ASIGNADO</span>
                                        @break
                                    @case('REVISADO')
                                    @case('PARA_EMITIR')
                                    @case('EMITIDO')
                                        <span class="bg-green-100 text-green-700 border border-green-200 text-[8px] font-black px-2 py-0.5 rounded-full">FINALIZADO</span>
                                        @break
                                    @case('EN_REVISION')
                                        <div x-data="{ open: false }" class="relative inline-block">
                                            <span @mouseenter="open = true" @mouseleave="open = false" 
                                                  class="bg-red-100 text-red-700 border border-red-200 text-[8px] font-black px-2 py-0.5 rounded-full cursor-help uppercase">
                                                DEVUELTO
                                            </span>
                                            <template x-if="open && '{{ addslashes($carpeta->motivo_devolucion) }}'">
                                                <div class="absolute z-[60] bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-3 bg-gray-900 text-white text-[10px] rounded-xl shadow-2xl border border-gray-700 animate-in fade-in zoom-in duration-200">
                                                    <div class="font-black text-red-400 mb-1 uppercase tracking-widest border-b border-gray-700 pb-1">Motivo de Devolución</div>
                                                    <div class="font-medium leading-relaxed italic text-gray-200">
                                                        "{{ $carpeta->motivo_devolucion }}"
                                                    </div>
                                                    <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-8 border-transparent border-t-gray-900"></div>
                                                </div>
                                            </template>
                                        </div>
                                        @break
                                    @default
                                        <span class="bg-gray-100 text-gray-600 text-[8px] font-bold px-2 py-0.5 rounded-full">-</span>
                                @endswitch
                            </td>

                            <td class="px-2 py-1.5 text-center">
                                @if($carpeta->tipo_envio == 'NORMAL')
                                    <span class="text-green-600 dark:text-green-400 font-black text-[10px]">✔ DnP</span>
                                @elseif($carpeta->tipo_envio == 'FUERA_PLAZO' || $carpeta->tipo_envio == 'FUERA_PERIODO')
                                    <span class="bg-red-600 text-white text-[9px] font-bold px-2 py-0.5 rounded shadow-sm whitespace-nowrap">
                                        FUERA DE PLAZO
                                    </span>
                                @else
                                    <span class="bg-gray-500 text-white text-[9px] font-bold px-2 py-0.5 rounded shadow-sm whitespace-nowrap">
                                        {{ $carpeta->tipo_envio }}
                                    </span>
                                @endif
                                <div class="text-[8px] font-mono text-gray-500 mt-0.5">
                                    {{ $carpeta->fecha_envio ? $carpeta->fecha_envio->format('d/m/Y') : '-' }}
                                </div>
                            </td>

                            <td class="px-2 py-1.5 text-center text-[10px] font-mono text-gray-500 whitespace-nowrap">
                                {{ $carpeta->fecha_asignacion ? $carpeta->fecha_asignacion->format('d/m/Y') : '-' }}
                            </td>

                            <!-- ACCIONES -->
                            <td class="px-2 py-1.5 text-center">
                                <div class="flex items-center gap-1.5 justify-center">
                                    <button wire:click="verDetalle({{ $carpeta->id }})"
                                            class="bg-blue-600 hover:bg-blue-700 text-white text-[9px] font-black px-2.5 py-1 rounded shadow-sm flex items-center gap-1 transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        VER DOCS
                                    </button>
                                    <button wire:click="abrirModalFinalizar({{ $carpeta->id }})"
                                            class="{{ $carpeta->fin_doy_finalizado ? 'bg-teal-700 hover:bg-teal-800' : 'bg-emerald-600 hover:bg-emerald-700' }} text-white text-[9px] font-black px-2.5 py-1 rounded shadow-sm flex items-center gap-1 transition-colors">
                                        @if($carpeta->fin_doy_finalizado)
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            ✅ PRECIERRE OK
                                        @else
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            FINALIZAR
                                        @endif
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-10 text-center text-gray-400 text-[11px] font-bold uppercase">
                                No tiene asignaciones con los filtros seleccionados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginacion -->
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-750 border-t border-gray-200 dark:border-gray-700">
            {{ $carpetas->links() }}
        </div>
    </div>

    {{-- ============================================================= --}}
    {{-- MODAL VER DOCS                                                 --}}
    {{-- ============================================================= --}}
    @if($showModalDocs && $carpetaDetalle)
        @php
            $esSoloLectura = in_array($carpetaDetalle->estado_revision, ['REVISADO', 'PARA_EMITIR', 'EMITIDO']);
        @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" wire:click="cerrarModalDocs"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div x-data="{ showDates: localStorage.getItem('analista_ver_fechas') === 'true' }" 
                     class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-6xl max-h-[95vh] flex flex-col">

                    <!-- Cabecera -->
                    <div class="bg-[#004b75] text-white px-5 py-3 rounded-t-xl flex justify-between items-start flex-shrink-0">
                        <div>
                            <h2 class="text-sm font-black uppercase tracking-tight">
                                {{ $carpetaDetalle->vinculacion->contratista->razon_social ?? '-' }}
                            </h2>
                            <p class="text-[10px] text-white/70 mt-0.5">
                                <span class="font-bold">PERIODO:</span> {{ strtoupper($carpetaDetalle->nombre_mes) }} {{ $carpetaDetalle->anio }}
                                &nbsp;|&nbsp;
                                <span class="font-bold">PRINCIPAL:</span> {{ $carpetaDetalle->vinculacion->unidadOrganizacional->mandante->razon_social ?? '-' }}
                                &nbsp;|&nbsp;
                                <span class="font-bold">LUGAR:</span> {{ $carpetaDetalle->vinculacion->dependencia->nombre ?? '-' }}
                                &nbsp;|&nbsp;
                                <span class="font-bold">CONTRATO:</span> {{ $carpetaDetalle->vinculacion->numero_contrato ?? 'N/A' }}
                            </p>
                            @if($carpetaDetalle->supervisor)
                                <p class="text-[9px] text-white/50 mt-0.5">Asignado por: {{ $carpetaDetalle->supervisor->name }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 shrink-0 ml-4">
                            @if($carpetaDetalle->tipo_envio == 'NORMAL')
                                <span class="bg-green-500 text-white text-[9px] font-black px-2 py-1 rounded">DENTRO DE PLAZO</span>
                            @else
                                <span class="bg-red-500 text-white text-[9px] font-black px-2 py-1 rounded">FUERA DE PLAZO</span>
                            @endif
                            <button wire:click="cerrarModalDocs" class="bg-white/20 hover:bg-white/30 text-white text-[10px] font-bold px-3 py-1.5 rounded transition-colors">
                                X Cerrar
                            </button>
                        </div>
                    </div>

                    <!-- Cuerpo scrollable -->
                    <div class="overflow-y-auto p-5 flex-1">
                        @if($carpetaDetalle->estado_revision === 'EN_REVISION' && $carpetaDetalle->motivo_devolucion)
                            <div class="mb-5 bg-red-50 border-2 border-red-200 rounded-2xl p-4 shadow-sm animate-in slide-in-from-top-2 duration-500">
                                <div class="flex items-start gap-3">
                                    <div class="bg-red-600 text-white p-2 rounded-xl">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-black text-red-700 uppercase tracking-widest mb-1">PERIODO DEVUELTO POR AUDITORÍA</h4>
                                        <p class="text-[11px] font-bold text-red-900 leading-relaxed italic">
                                            "{{ $carpetaDetalle->motivo_devolucion }}"
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <h3 class="text-[11px] font-black text-gray-700 dark:text-white uppercase mb-3 border-b pb-2 dark:border-gray-600">
                            Documentacion Requerida ({{ $documentosPorRequisito->count() }} / {{ $requisitosPorClasif->flatten()->count() }} documentos cargados)
                        </h3>

                        @if($requisitosPorClasif->isEmpty())
                            <div class="text-center py-8 text-gray-400 text-[11px]">
                                No se encontraron requisitos configurados para esta Principal.
                            </div>
                        @else
                            @foreach($requisitosPorClasif as $clasificacion => $requisitos)
                                <div class="mb-4">
                                    <div class="bg-[#e8f0fe] dark:bg-blue-900/30 border-l-4 border-blue-500 px-3 py-1.5 mb-2 rounded-r">
                                        <span class="text-[10px] font-black text-blue-700 dark:text-blue-300 uppercase tracking-wide">
                                            {{ $clasificacion }}
                                        </span>
                                        <span class="text-[9px] text-blue-500 ml-2">
                                            ({{ $requisitos->filter(fn($r) => isset($documentosPorRequisito[$r->id]))->count() }}/{{ $requisitos->count() }})
                                        </span>
                                    </div>

                                    <div class="space-y-1.5 pl-2">
                                        @foreach($requisitos as $requisito)
                                            @php $docCargado = $documentosPorRequisito[$requisito->id] ?? null; @endphp
                                            <div class="flex items-center justify-between p-2.5 rounded border
                                                {{ $docCargado
                                                    ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700'
                                                    : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700' }}">

                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        @if($docCargado)
                                                            <svg class="w-3.5 h-3.5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                        @else
                                                            <svg class="w-3.5 h-3.5 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        @endif
                                                        <span class="text-[11px] font-bold text-gray-800 dark:text-white truncate">{{ $requisito->nombre }}</span>
                                                    </div>
                                                    @if($docCargado)
                                                        <div class="text-[9px] text-gray-500 dark:text-gray-400 ml-5 mt-0.5">
                                                            {{ $docCargado->nombre_original ?? basename($docCargado->path ?? '') }}
                                                            &nbsp;Cargado: {{ $docCargado->created_at->format('d/m/Y H:i') }}
                                                        </div>
                                                    @else
                                                        <div class="text-[9px] text-red-500 dark:text-red-400 ml-5 mt-0.5 italic">Sin documento cargado</div>
                                                    @endif
                                                    @if($requisito->descripcion)
                                                        <div class="text-[9px] text-gray-400 dark:text-gray-500 ml-5 mt-0.5">{{ $requisito->descripcion }}</div>
                                                    @endif
                                                </div>

                                                @if($docCargado && $docCargado->path)
                                                    <div class="flex items-center gap-1.5 ml-3 shrink-0">
                                                        <a href="{{ route('archivo.publico', ['filePath' => $docCargado->path, 'name' => $docCargado->nombre_original ?? basename($docCargado->path ?? '')]) }}"
                                                           target="_blank"
                                                           class="flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white text-[9px] font-bold px-2.5 py-1 rounded shadow-sm transition-colors">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                            Ver
                                                        </a>
                                                        <a href="{{ route('archivo.publico', ['filePath' => $docCargado->path, 'download' => 1, 'name' => $docCargado->nombre_original ?? basename($docCargado->path ?? '')]) }}"
                                                           download="{{ $docCargado->nombre_original ?? 'documento.pdf' }}"
                                                           class="flex items-center gap-1 bg-gray-600 hover:bg-gray-700 text-white text-[9px] font-bold px-2.5 py-1 rounded shadow-sm transition-colors">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                            Descargar
                                                        </a>
                                                    </div>
                                                @else
                                                    <div class="ml-3 shrink-0">
                                                        <span class="text-[9px] text-red-400 italic font-bold">Sin archivo</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        <!-- Nómina de Trabajadores -->
                        <div class="mt-6 border-t dark:border-gray-600 pt-4">
                                <div class="flex items-center justify-between w-full">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        Nómina de Trabajadores Vigentes ({{ ($trabajadoresPeriodo ?? collect())->count() }})
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button @click="showDates = !showDates; localStorage.setItem('analista_ver_fechas', showDates)" 
                                                class="flex items-center gap-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-[9px] font-black px-2.5 py-1 rounded border dark:border-gray-600 transition-colors">
                                            <span x-text="showDates ? 'OCULTAR FECHAS SECUNDARIAS' : 'MOSTRAR FECHAS SECUNDARIAS'"></span>
                                        </button>
                                        
                                        <button wire:click="exportarDotacion({{ $carpetaDetalle->id }})" 
                                                class="flex items-center gap-1.5 bg-green-600 hover:bg-green-700 text-white text-[9px] font-black px-2.5 py-1 rounded shadow-sm transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            EXPORTAR EXCEL
                                        </button>
                                    </div>
                                </div>
                            </h3>

                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg border dark:border-gray-700 overflow-hidden">
                                <table class="w-full text-left text-[10px]">
                                    <thead class="bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 uppercase font-black">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700">RUT</th>
                                            <th class="px-3 py-2 text-left text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700">Nombre Completo</th>
                                            <th class="px-3 py-2 text-left text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700">Cargo</th>
                                            <th class="px-3 py-2 text-center text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700">F. Finiquito Real</th>
                                            <th x-show="showDates" class="px-3 py-2 text-center text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700">F. Ingreso</th>
                                            <th class="px-3 py-2 text-center text-[9px] font-black text-emerald-500 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700">Nuevo</th>
                                            <th x-show="showDates" class="px-3 py-2 text-center text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700">F. Contrato</th>
                                            <th x-show="showDates" class="px-3 py-2 text-center text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700">F. Creación</th>
                                            <th class="px-3 py-2 text-center text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @forelse(($trabajadoresPeriodo ?? collect()) as $vt)
                                            <tr class="hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors {{ $vt->tipo_registro === 'ARRASTRE' ? 'bg-orange-50/50 dark:bg-orange-950/20' : '' }}">
                                                <td class="px-3 py-2">
                                                    <div class="flex flex-col">
                                                        <span class="font-mono text-blue-600 dark:text-blue-400 text-[10px]">{{ $vt->vinculacion->trabajador->rut ?? '-' }}</span>
                                                        @if($vt->tipo_registro === 'ARRASTRE')
                                                            <span class="text-[7px] bg-orange-100 text-orange-700 px-1 rounded w-fit font-black uppercase mt-0.5">Arrastre</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 font-bold text-gray-800 dark:text-gray-200 uppercase text-[10px] leading-tight">
                                                    {{ $vt->vinculacion->trabajador->nombre_completo ?? '-' }}
                                                </td>
                                                <td class="px-3 py-2 text-gray-600 dark:text-gray-400 uppercase text-[9px]">
                                                    {{ $vt->vinculacion->cargoMandante->nombre_cargo ?? 'N/A' }}
                                                </td>
                                                <td class="px-3 py-2 text-center">
                                                    @if($vt->vinculacion->fecha_finiquito)
                                                        <span class="text-[9px] font-black text-red-600 bg-red-50 dark:bg-red-900/30 px-2 py-0.5 rounded border border-red-200 dark:border-red-800">
                                                            {{ \Carbon\Carbon::parse($vt->vinculacion->fecha_finiquito)->format('d/m/Y') }}
                                                        </span>
                                                    @else
                                                        <span class="text-[9px] text-gray-400 italic font-medium">-</span>
                                                    @endif
                                                </td>
                                                <td x-show="showDates" class="px-3 py-2 text-center text-gray-500 text-[9px] font-bold">
                                                    {{ $vt->vinculacion->fecha_ingreso_vinculacion ? $vt->vinculacion->fecha_ingreso_vinculacion->format('d/m/Y') : '-' }}
                                                </td>
                                                <td class="px-3 py-2 text-center">
                                                    @php
                                                        $esNuevo = isset($carpetaDetalle) && $vt->vinculacion->fecha_ingreso_vinculacion
                                                            && \Carbon\Carbon::parse($vt->vinculacion->fecha_ingreso_vinculacion)->year == $carpetaDetalle->anio
                                                            && \Carbon\Carbon::parse($vt->vinculacion->fecha_ingreso_vinculacion)->month == $carpetaDetalle->mes;
                                                    @endphp
                                                    @if($esNuevo)
                                                        <span class="text-[8px] bg-emerald-100 text-emerald-700 border border-emerald-300 px-2 py-0.5 rounded font-black uppercase">🆕 Nuevo</span>
                                                    @endif
                                                </td>
                                                <td x-show="showDates" class="px-3 py-2 text-center text-gray-500 text-[9px] font-bold">
                                                    {{ $vt->vinculacion->fecha_contrato ? $vt->vinculacion->fecha_contrato->format('d/m/Y') : '-' }}
                                                </td>
                                                <td x-show="showDates" class="px-3 py-2 text-center text-gray-500 text-[9px] font-bold">
                                                    {{ $vt->vinculacion->created_at ? $vt->vinculacion->created_at->format('d/m/Y') : '-' }}
                                                </td>
                                                <td class="px-3 py-2 text-center">
                                                    <div class="flex flex-col gap-1 items-center">
                                                        <select 
                                                            wire:change="cambiarEstadoTrabajadorPeriodo({{ $vt->id }}, $event.target.value)"
                                                            {{ $esSoloLectura ? 'disabled' : '' }}
                                                            class="text-[9px] font-black uppercase rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600 py-1 px-2 w-full max-w-[220px] {{ $esSoloLectura ? 'opacity-70 cursor-not-allowed' : '' }}
                                                            @if($vt->estado_revision === 'PENDIENTE') bg-green-50 text-green-700 
                                                            @elseif($vt->estado_revision === 'FINIQUITADO') bg-red-50 text-red-700
                                                            @elseif($vt->estado_revision === 'MOVIDO') bg-sky-50 text-sky-700
                                                            @elseif($vt->estado_revision === 'BAJA_MANDANTE') bg-purple-50 text-purple-700
                                                            @endif"
                                                        >
                                                            <option value="PENDIENTE" {{ $vt->estado_revision === 'PENDIENTE' ? 'selected' : '' }}>1. ACTIVO</option>
                                                            <option value="FINIQUITADO" {{ $vt->estado_revision === 'FINIQUITADO' ? 'selected' : '' }}>2. FINIQUITADO</option>
                                                            <option value="MOVIDO" {{ $vt->estado_revision === 'MOVIDO' ? 'selected' : '' }}>3. MOVIDO A OTRA VINCULACIÓN</option>
                                                            <option value="BAJA_MANDANTE" {{ $vt->estado_revision === 'BAJA_MANDANTE' ? 'selected' : '' }}>4. BAJA POR PRINCIPAL</option>
                                                        </select>

                                                        @if($vt->estado_revision === 'BAJA_MANDANTE')
                                                            <span class="text-[10px] bg-purple-100 text-purple-700 font-black px-2 py-1 rounded uppercase mt-1 border-2 border-purple-300 text-center block leading-tight">
                                                                ⚠️ RESPALDAR EN SECCIÓN "OTROS"
                                                            </span>
                                                        @endif

                                                        @if($vt->estado_revision === 'MOVIDO')
                                                            @php $destinos = $this->getDestinosPosibles($vt->trabajador_vinculacion_id); @endphp
                                                            @if($destinos->isNotEmpty())
                                                                <div class="w-full max-w-[130px] animate-in slide-in-from-top-1">
                                                                    <select 
                                                                        wire:change="cambiarEstadoTrabajadorPeriodo({{ $vt->id }}, 'MOVIDO', $event.target.value)"
                                                                        {{ $esSoloLectura ? 'disabled' : '' }}
                                                                        class="text-[8px] font-bold uppercase rounded border-blue-300 bg-blue-50 dark:bg-gray-800 py-0.5 px-1 w-full mt-1 {{ $esSoloLectura ? 'opacity-70 cursor-not-allowed' : '' }}"
                                                                    >
                                                                        <option value="">-- SELECCIONAR DESTINO --</option>
                                                                        @foreach($destinos as $dest)
                                                                            <option value="{{ $dest->id }}" {{ $vt->destino_trabajador_vinculacion_id == $dest->id ? 'selected' : '' }}>
                                                                                {{ $dest->unidadOrganizacional->nombre_unidad ?? 'S/U' }} - {{ $dest->dependencia->nombre ?? 'S/D' }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                    @if($vt->destinoVinculacion)
                                                                        <span class="text-[7px] text-blue-600 font-bold block mt-0.5">Destino informado: {{ $vt->destinoVinculacion->unidadOrganizacional->nombre_unidad ?? 'S/U' }}</span>
                                                                    @endif
                                                                </div>
                                                            @else
                                                                <span class="text-[10px] bg-red-100 text-red-700 font-black px-2 py-1 rounded uppercase mt-1 border-2 border-red-300 text-center block leading-tight animate-bounce">
                                                                    ⚠️ TRABAJADOR NO REGISTRA OTRAS VINCULACIONES ACTIVAS
                                                                </span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-4 py-6 text-center text-gray-400 italic">No hay trabajadores vinculados registrados para este periodo.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div class="mt-4 border-t dark:border-gray-600 pt-4">
                            <label class="text-[10px] font-bold text-gray-600 dark:text-gray-400 uppercase block mb-1">
                                Observaciones del Analista
                            </label>
                            <textarea
                                wire:change="guardarObservacion({{ $carpetaDetalle->id }}, $event.target.value)"
                                {{ $esSoloLectura ? 'disabled' : '' }}
                                class="w-full text-[11px] p-2 rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-1 focus:ring-blue-400 {{ $esSoloLectura ? 'opacity-70 cursor-not-allowed' : '' }}"
                                rows="3"
                                placeholder="Escriba sus observaciones sobre la documentacion revisada...">{{ $carpetaDetalle->observaciones_analista }}</textarea>
                        </div>

                        <!-- Boton accion removido por requerimiento -->

                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================= --}}
    {{-- MODAL PRE-CIERRE (FINALIZAR)                                  --}}
    {{-- ============================================================= --}}
    @if($showModalFinalizar)
        @php
            $cfCarpeta = $carpeta_finalizar_id
                ? \App\Models\CarpetaVerificacion::with([
                    'vinculacion.contratista',
                    'vinculacion.unidadOrganizacional.mandante',
                    'vinculacion.dependencia',
                  ])->find($carpeta_finalizar_id)
                : null;
        @endphp
        @if($cfCarpeta)
        @php
            $esSoloLecturaFin = in_array($cfCarpeta->estado_revision, ['REVISADO', 'PARA_EMITIR', 'EMITIDO']);
        @endphp
        <div class="fixed inset-0 z-[60] overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity" wire:click="cerrarModalFinalizar"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden flex flex-col max-h-[95vh]">

                    {{-- HEADER NAVY --}}
                    <div class="bg-[#1a3560] text-white px-6 py-4 shrink-0">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="flex items-center gap-3 mb-1">
                                    <span class="bg-white/20 text-white text-[10px] font-black px-2 py-0.5 rounded uppercase tracking-widest">PRE-CIERRE</span>
                                    <span class="text-[11px] text-blue-200 font-bold uppercase">
                                        {{ strtoupper($cfCarpeta->nombre_mes) }} {{ $cfCarpeta->anio }}
                                    </span>
                                </div>
                                <h2 class="text-lg font-black uppercase tracking-tight leading-tight">
                                    {{ $cfCarpeta->vinculacion->contratista->razon_social ?? '-' }}
                                    <span class="text-blue-300 ml-2">ID: {{ $cfCarpeta->vinculacion->id_registro ?? '-' }}</span>
                                </h2>
                                <p class="text-[11px] text-blue-300 font-bold uppercase mt-1 leading-relaxed">
                                    <span class="text-white/60">PRINCIPAL:</span> {{ $cfCarpeta->vinculacion->unidadOrganizacional->mandante->razon_social ?? '-' }}
                                    &nbsp;·&nbsp;
                                    <span class="text-white/60">LUGAR:</span> {{ $cfCarpeta->vinculacion->dependencia->nombre ?? '-' }}
                                    &nbsp;·&nbsp;
                                    <span class="text-white/60">CT:</span> {{ $cfCarpeta->vinculacion->numero_contrato ?? 'N/A' }}
                                </p>
                            </div>
                            <button wire:click="cerrarModalFinalizar" class="text-white/40 hover:text-white transition-colors shrink-0 ml-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>



                    {{-- CUERPO SCROLLABLE --}}
                    <div class="p-5 overflow-y-auto flex-1 space-y-5">

                        {{-- SITUACION DE TRABAJADORES --}}
                        <div class="rounded-lg overflow-hidden border border-[#1a3560]/30">
                            <div class="bg-[#1a3560] text-white text-[9px] font-black uppercase text-center py-1.5 tracking-widest">
                                SITUACIÓN DE LOS TRABAJADORES DECLARADOS AL PERÍODO
                            </div>
                            <div class="grid grid-cols-3 text-center font-black text-[9px] uppercase">
                                <div class="bg-green-600 text-white py-1.5 border-r border-white/20">Contratados en el Período</div>
                                <div class="bg-red-600 text-white py-1.5 border-r border-white/20">Desvinculados en el Período</div>
                                <div class="bg-amber-500 text-white py-1.5">Total Vigentes</div>
                                <div class="bg-green-50 dark:bg-green-900/20 py-4 text-3xl font-black border-r border-gray-200 dark:border-gray-600 text-green-600">{{ $fin_contratados_periodo }}</div>
                                <div class="bg-red-50 dark:bg-red-900/20 py-4 text-3xl font-black border-r border-gray-200 dark:border-gray-600 text-red-600">{{ $fin_desvinculados_periodo }}</div>
                                <div class="bg-amber-50 dark:bg-amber-900/20 py-4 text-3xl font-black text-amber-600">{{ $fin_total_vigentes }}</div>
                            </div>
                        </div>

                        {{-- TRABAJADORES REVISADOS + RECEPCION DOC --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div class="rounded-lg overflow-hidden border border-[#1a3560]/30">
                                <div class="bg-[#1a3560] text-white text-[9px] font-black uppercase text-center py-1.5 tracking-widest">TRABAJADORES REVISADOS</div>
                                <div class="p-3 bg-white dark:bg-gray-700 flex items-center justify-center gap-3">
                                    <label class="text-[9px] font-black text-gray-500 uppercase">N° Total :</label>
                                    <input type="number" wire:model.live="fin_trabajadores_revisados" min="0" {{ $esSoloLecturaFin ? 'disabled' : '' }}
                                           class="w-24 text-center font-black text-lg bg-white dark:bg-gray-800 border-2 border-[#1a3560]/30 rounded-lg py-1 focus:border-[#1a3560] focus:ring-0 {{ $esSoloLecturaFin ? 'opacity-70 cursor-not-allowed' : '' }}">
                                </div>
                            </div>
                            <div class="rounded-lg overflow-hidden border border-[#1a3560]/30">
                                <div class="bg-[#1a3560] text-white text-[9px] font-black uppercase text-center py-1.5 tracking-widest">RECEPCIÓN DOCUMENTACIÓN</div>
                                <div class="p-3 bg-white dark:bg-gray-700 flex items-center justify-center">
                                    <span class="font-mono font-black text-[#1a3560] dark:text-blue-300 text-base bg-blue-50 dark:bg-blue-900/30 px-4 py-1.5 rounded-lg border border-blue-200 dark:border-blue-700">
                                        {{ $cfCarpeta->fecha_envio ? $cfCarpeta->fecha_envio->format('d/m/Y') : '—' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- REMUNERACIONES (Diseño Plano 1:1) -->
                        <div class="border border-gray-300 mb-0">
                            <div class="bg-[#003a5c] text-white text-center py-1 font-black text-[11px] uppercase tracking-widest">REMUNERACIONES</div>
                            <div class="bg-gray-50 px-4 py-1.5 flex items-center border-t border-gray-300">
                                <span class="w-1/2 text-[10px] font-bold text-gray-700 uppercase">Remuneraciones Pagadas</span>
                                <span class="px-2 font-bold text-gray-700">:</span>
                                <input type="number" wire:model="fin_remuneraciones_pagadas" {{ $esSoloLecturaFin ? 'disabled' : '' }} class="w-48 text-left font-mono font-bold text-xs border border-gray-300 px-2 py-0.5 outline-none focus:border-blue-500 bg-white {{ $esSoloLecturaFin ? 'opacity-70 cursor-not-allowed' : '' }}">
                            </div>
                        </div>

                        <!-- COTIZACIONES PREVISIONALES (Diseño Plano 1:1) -->
                        <div class="border border-gray-300 mb-0 border-t-0">
                            <div class="bg-[#003a5c] text-white text-center py-1 font-black text-[11px] uppercase tracking-widest">COTIZACIONES PREVISIONALES</div>
                            <div class="bg-gray-50 px-4 py-1.5 flex items-center border-t border-gray-300">
                                <span class="w-1/2 text-[10px] font-bold text-gray-700 uppercase">Cotizaciones Pagadas</span>
                                <span class="px-2 font-bold text-gray-700">:</span>
                                <input type="number" wire:model="fin_cotizaciones_pagadas" {{ $esSoloLecturaFin ? 'disabled' : '' }} class="w-48 text-left font-mono font-bold text-xs border border-gray-300 px-2 py-0.5 outline-none focus:border-blue-500 bg-white {{ $esSoloLecturaFin ? 'opacity-70 cursor-not-allowed' : '' }}">
                            </div>
                        </div>

                        <!-- INDEMNIZACIONES (Diseño Plano 1:1) -->
                        <div class="border border-gray-300 mb-0 border-t-0">
                            <div class="bg-[#003a5c] text-white text-center py-1 font-black text-[11px] uppercase tracking-widest">INDEMNIZACIONES</div>
                            
                            <!-- Aviso Previo -->
                            <div class="bg-[#fcc01a] text-black px-4 py-0.5 font-black text-[10px] uppercase border-t border-gray-300">Aviso Previo</div>
                            <div class="bg-white px-4 py-1 flex items-center border-t border-gray-100">
                                <span class="w-1/2 text-[10px] font-bold text-gray-700 uppercase">Trabajadores con pago</span>
                                <span class="px-2 font-bold text-gray-700">:</span>
                                <input type="number" wire:model="fin_aviso_previo_trabajadores" {{ $esSoloLecturaFin ? 'disabled' : '' }} class="w-48 text-left font-mono font-bold text-xs border border-gray-300 px-2 py-0.5 outline-none bg-white {{ $esSoloLecturaFin ? 'opacity-70 cursor-not-allowed' : '' }}">
                            </div>
                            <div class="bg-gray-50 px-4 py-1 flex items-center border-t border-gray-200">
                                <span class="w-1/2 text-[10px] font-bold text-gray-700 uppercase">Total pagado</span>
                                <span class="px-2 font-bold text-gray-700">:</span>
                                <input type="number" wire:model="fin_aviso_previo_total" {{ $esSoloLecturaFin ? 'disabled' : '' }} class="w-48 text-left font-mono font-bold text-xs border border-gray-300 px-2 py-0.5 outline-none bg-white {{ $esSoloLecturaFin ? 'opacity-70 cursor-not-allowed' : '' }}">
                            </div>

                            <!-- Año de Servicio -->
                            <div class="bg-[#fcc01a] text-black px-4 py-0.5 font-black text-[10px] uppercase border-t border-gray-300">Año de Servicio</div>
                            <div class="bg-white px-4 py-1 flex items-center border-t border-gray-100">
                                <span class="w-1/2 text-[10px] font-bold text-gray-700 uppercase">Trabajadores con pago</span>
                                <span class="px-2 font-bold text-gray-700">:</span>
                                <input type="number" wire:model="fin_anio_servicio_trabajadores" {{ $esSoloLecturaFin ? 'disabled' : '' }} class="w-48 text-left font-mono font-bold text-xs border border-gray-300 px-2 py-0.5 outline-none bg-white {{ $esSoloLecturaFin ? 'opacity-70 cursor-not-allowed' : '' }}">
                            </div>
                            <div class="bg-gray-50 px-4 py-1 flex items-center border-t border-gray-200">
                                <span class="w-1/2 text-[10px] font-bold text-gray-700 uppercase">Total pagado</span>
                                <span class="px-2 font-bold text-gray-700">:</span>
                                <input type="number" wire:model="fin_anio_servicio_total" {{ $esSoloLecturaFin ? 'disabled' : '' }} class="w-48 text-left font-mono font-bold text-xs border border-gray-300 px-2 py-0.5 outline-none bg-white {{ $esSoloLecturaFin ? 'opacity-70 cursor-not-allowed' : '' }}">
                            </div>

                            <!-- Feriado -->
                            <div class="bg-[#fcc01a] text-black px-4 py-0.5 font-black text-[10px] uppercase border-t border-gray-300">Feriado</div>
                            <div class="bg-white px-4 py-1 flex items-center border-t border-gray-100">
                                <span class="w-1/2 text-[10px] font-bold text-gray-700 uppercase">Trabajadores con pago</span>
                                <span class="px-2 font-bold text-gray-700">:</span>
                                <input type="number" wire:model="fin_feriado_trabajadores" {{ $esSoloLecturaFin ? 'disabled' : '' }} class="w-48 text-left font-mono font-bold text-xs border border-gray-300 px-2 py-0.5 outline-none bg-white {{ $esSoloLecturaFin ? 'opacity-70 cursor-not-allowed' : '' }}">
                            </div>
                            <div class="bg-gray-50 px-4 py-1 flex items-center border-t border-gray-200">
                                <span class="w-1/2 text-[10px] font-bold text-gray-700 uppercase">Total pagado</span>
                                <span class="px-2 font-bold text-gray-700">:</span>
                                <input type="number" wire:model="fin_feriado_total" {{ $esSoloLecturaFin ? 'disabled' : '' }} class="w-48 text-left font-mono font-bold text-xs border border-gray-300 px-2 py-0.5 outline-none bg-white {{ $esSoloLecturaFin ? 'opacity-70 cursor-not-allowed' : '' }}">
                            </div>
                        </div>

                        {{-- CONCLUSION (Diseño Plano 1:1) --}}
                        <div class="border border-gray-300 mt-8 overflow-hidden">
                            <div class="bg-[#003a5c] text-white text-[11px] font-black uppercase text-center py-2 tracking-widest border-b border-gray-300">
                                CONCLUIR INFORME PARA SU REVISIÓN
                            </div>
                            <div class="p-6 bg-white dark:bg-gray-700 space-y-4">
                                <p class="text-[11px] text-gray-600 dark:text-gray-400 text-center leading-relaxed">
                                    Estimado(a) <strong class="text-gray-800">{{ auth()->user()->name }}</strong>, se dará por finalizado este informe para la revisión del Jefe de Certificaciones. Se advierte que una vez finalizado el informe usted no podrá seguir editándolo, y solo el Jefe de Certificaciones lo podrá modificar.
                                </p>
                                <div class="bg-[#003a5c] text-white flex items-center justify-between px-6 py-3 rounded shadow-md">
                                    <span class="text-[10px] font-black uppercase tracking-wide">¿Doy por Finalizado el Informe?</span>
                                    <div class="flex items-center gap-6">
                                        <label class="flex items-center gap-2 {{ $esSoloLecturaFin ? 'cursor-not-allowed opacity-70' : 'cursor-pointer group' }} font-black uppercase text-[10px]">
                                            <input type="radio" wire:model.live="fin_doy_finalizado" value="1" class="w-4 h-4 accent-white" {{ $esSoloLecturaFin ? 'disabled' : '' }}>
                                            <span class="{{ !$esSoloLecturaFin ? 'group-hover:text-blue-200' : '' }} transition-colors">Sí</span>
                                        </label>
                                        <label class="flex items-center gap-2 {{ $esSoloLecturaFin ? 'cursor-not-allowed opacity-70' : 'cursor-pointer group' }} font-black uppercase text-[10px]">
                                            <input type="radio" wire:model.live="fin_doy_finalizado" value="0" class="w-4 h-4 accent-white" {{ $esSoloLecturaFin ? 'disabled' : '' }}>
                                            <span class="{{ !$esSoloLecturaFin ? 'group-hover:text-blue-200' : '' }} transition-colors">No (Borrador)</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- FOOTER ACCIONES --}}
                    <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 flex justify-between items-center shrink-0 border-t dark:border-gray-700">
                        <button wire:click="cerrarModalFinalizar" class="text-[11px] font-black text-gray-400 uppercase hover:text-gray-600 transition-colors flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Cancelar
                        </button>
                        @if(!$esSoloLecturaFin)
                            @php 
                                $esFinalizable = ($fin_doy_finalizado == 1 && $fin_trabajadores_revisados > 0);
                            @endphp
                            <button wire:click="finalizarRevision"
                                    class="{{ $esFinalizable ? 'bg-teal-600 hover:bg-teal-700' : 'bg-[#1a3560] hover:bg-[#0f2040]' }} text-white font-black text-xs px-8 py-2.5 rounded-lg shadow-lg transition-all transform hover:scale-105 flex items-center gap-2">
                                @if($esFinalizable)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    FINALIZAR
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                                    GUARDAR BORRADOR
                                @endif
                            </button>
                        @endif
                    </div>

                </div>
            </div>
        </div>
        @endif
    @endif

</div>
