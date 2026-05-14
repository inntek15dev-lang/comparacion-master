<div class="p-4 bg-gray-100 dark:bg-gray-900 min-h-screen">

    <!-- TITULO -->
    <div class="mb-4">
        <h1 class="text-xl font-bold text-gray-700 dark:text-white uppercase tracking-tighter">
            AUDITAR PERIODOS
        </h1>
        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-black">
            Carpetas asignadas para revision de segundo nivel
        </p>
    </div>

    <!-- MENSAJES -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="bg-amber-100 border border-amber-400 text-amber-700 px-4 py-2 rounded mb-4 text-sm">
            {{ session('warning') }}
        </div>
    @endif

    <!-- CONTADORES -->
    <div class="grid grid-cols-4 gap-4 mb-4">
        <div class="bg-blue-600 text-white p-4 rounded-lg shadow shadow-blue-500/50 transform hover:scale-105 transition-transform">
            <div class="text-3xl font-black">{{ $totalAsignados }}</div>
            <div class="text-[10px] font-bold uppercase tracking-widest">Por Auditar</div>
        </div>
        <div class="bg-indigo-600 text-white p-4 rounded-lg shadow shadow-indigo-500/50 transform hover:scale-105 transition-transform">
            <div class="text-3xl font-black">{{ $totalAuditando }}</div>
            <div class="text-[10px] font-bold uppercase tracking-widest">En Auditoría</div>
        </div>
        <div class="bg-purple-600 text-white p-4 rounded-lg shadow shadow-purple-500/50 transform hover:scale-105 transition-transform">
            <div class="text-3xl font-black">{{ $totalEnRevision }}</div>
            <div class="text-[10px] font-bold uppercase tracking-widest">Devueltos al Analista</div>
        </div>
        <div class="bg-emerald-600 text-white p-4 rounded-lg shadow shadow-emerald-500/50 transform hover:scale-105 transition-transform">
            <div class="text-3xl font-black">{{ $totalParaEmitir }}</div>
            <div class="text-[10px] font-bold uppercase tracking-widest">Auditados</div>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="bg-[#1a3560] p-4 rounded-lg shadow-lg mb-4 border-b-4 border-blue-400">
        <div class="text-white text-[10px] font-black uppercase mb-3 border-b border-white/20 pb-2 tracking-widest flex items-center gap-2">
            FILTROS DE BUSQUEDA
        </div>
        <div class="grid grid-cols-1 md:grid-cols-7 gap-3">
            <div>
                <label class="text-blue-200 text-[9px] font-black uppercase block mb-1">Principal</label>
                <select wire:model.live="mandante_id" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-800 dark:text-white font-bold">
                    <option value="">-- Todos --</option>
                    @foreach($mandantes as $m)
                        <option value="{{ $m->id }}">{{ $m->razon_social }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-blue-200 text-[9px] font-black uppercase block mb-1">Contratista</label>
                <select wire:model.live="contratista_id" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-800 dark:text-white font-bold" {{ !$mandante_id ? 'disabled' : '' }}>
                    <option value="">-- Todos --</option>
                    @foreach($contratistas as $c)
                        <option value="{{ $c->id }}">{{ Str::limit($c->razon_social, 30) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-blue-200 text-[9px] font-black uppercase block mb-1">Año</label>
                <select wire:model.live="anio" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-800 dark:text-white font-bold">
                    @for($y = date('Y'); $y >= 2024; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="text-blue-200 text-[9px] font-black uppercase block mb-1">Mes</label>
                <select wire:model.live="mes" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-800 dark:text-white font-bold">
                    <option value="">-- Todos --</option>
                    @foreach(['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $i => $nombre)
                        <option value="{{ $i + 1 }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-blue-200 text-[9px] font-black uppercase block mb-1">Estado</label>
                <select wire:model.live="estado_revision" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-800 dark:text-white font-bold">
                    <option value="">-- Todos --</option>
                    <option value="REVISADO">Por Auditar</option>
                    <option value="AUDITANDO">Auditando</option>
                    <option value="PARA_EMITIR">Auditado</option>
                    <option value="EN_REVISION">Devuelto</option>
                    <option value="EN_CARGA">En Revision Analista</option>
                    <option value="EMITIDO">Emitido</option>
                </select>
            </div>
            <div>
                <label class="text-blue-200 text-[9px] font-black uppercase block mb-1">Envío</label>
                <select wire:model.live="estado_plazo" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-800 dark:text-white font-bold">
                    <option value="">-- Todos --</option>
                    <option value="NORMAL">✓ Dentro de Plazo</option>
                    <option value="FUERA_PLAZO">⚠ Fuera de Plazo</option>
                </select>
            </div>
            <div class="flex items-end gap-2 flex-col sm:flex-row col-span-1 md:col-span-full justify-end mt-2">
                <button wire:click="limpiarFiltros" class="bg-blue-500 hover:bg-blue-400 text-white text-[10px] font-black px-4 py-2 rounded uppercase transition-colors">
                    Limpiar
                </button>
                <button wire:click="descargarDocumentosFiltrados" wire:loading.attr="disabled" class="bg-indigo-600 hover:bg-indigo-500 text-white text-[10px] font-black px-4 py-2 rounded uppercase transition-colors flex items-center gap-1 disabled:opacity-50 inline-flex">
                    <svg wire:loading.remove wire:target="descargarDocumentosFiltrados" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <svg wire:loading wire:target="descargarDocumentosFiltrados" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>Descargar Filtrados</span>
                </button>
            </div>
        </div>
    </div>

    <!-- TABLA PRINCIPAL -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl overflow-hidden border dark:border-gray-700">
        <div class="bg-[#0f172a] text-white px-4 py-2.5 text-[10px] font-black uppercase flex justify-between items-center tracking-widest">
            <span>Panel de Auditoria ({{ $carpetas->total() }} registros)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-2 py-3 text-center text-[9px] font-black text-gray-500 uppercase w-8">H.</th>
                        <th class="px-3 py-3 text-left text-[9px] font-black text-gray-500 uppercase">ID</th>
                        <th class="px-2 py-3 text-left text-[9px] font-black text-gray-500 uppercase">Principal</th>
                        <th class="px-2 py-3 text-left text-[9px] font-black text-gray-500 uppercase">Contratista</th>
                        <th class="px-2 py-3 text-center text-[9px] font-black text-gray-500 uppercase">Lugar/Contrato</th>
                        <th class="px-2 py-3 text-center text-[9px] font-black text-gray-500 uppercase">Periodo</th>
                        <th class="px-2 py-3 text-center text-[9px] font-black text-gray-500 uppercase">Estado</th>
                        <th class="px-2 py-3 text-center text-[9px] font-black text-gray-500 uppercase">Analista</th>
                        <th class="px-2 py-3 text-center text-[9px] font-black text-gray-500 uppercase">ENVÍO</th>
                        <th class="px-2 py-3 text-center text-[9px] font-black text-gray-500 uppercase w-48">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($carpetas as $index => $carpeta)
                        @php
                            $correlativoJerarquico = $carpeta->correlativo_jerarquico ?? ($carpetas->firstItem() + $index);
                            $correlativoArray = explode('.', (string)$correlativoJerarquico);
                            $nivel = count($correlativoArray) - 1;
                            $indentClass = $nivel > 0 ? 'pl-' . ($nivel * 4) : '';
                            $fondoClase = $loop->even ? 'bg-gray-50/30 dark:bg-gray-800/30' : 'bg-white dark:bg-gray-800';
                        @endphp
                        <tr class="{{ $fondoClase }} hover:bg-blue-50/50 transition-colors border-l-4 {{ $nivel > 0 ? 'border-indigo-400' : 'border-transparent' }}">
                            <td class="px-2 py-2 text-center text-[10px] font-mono text-gray-400">
                                {{ $correlativoJerarquico }}
                            </td>
                            <td class="px-2 py-2 text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase italic">
                                {{ $carpeta->vinculacion->id_registro ?? '-' }}
                            </td>
                            <td class="px-2 py-2 text-[10px] font-bold text-gray-600 dark:text-gray-400">
                                {{ Str::limit($carpeta->vinculacion->unidadOrganizacional->mandante->razon_social ?? '-', 15) }}
                            </td>
                            <td class="px-2 py-2 text-[10px] font-black text-gray-900 dark:text-white uppercase {{ $indentClass }}">
                                @if($nivel > 0) <span class="text-indigo-500 mr-1">></span> @endif
                                {{ Str::limit($carpeta->vinculacion->contratista->razon_social ?? '-', 25) }}
                                <div class="text-[8px] text-gray-400 font-mono italic">{{ $carpeta->vinculacion->contratista->rut ?? '' }}</div>
                            </td>
                            <td class="px-2 py-2 text-center">
                                <div class="text-[9px] font-black text-gray-700 dark:text-gray-300 uppercase leading-tight">{{ Str::limit($carpeta->vinculacion->dependencia->nombre ?? '-', 15) }}</div>
                                <div class="text-[8px] text-blue-500 font-mono mt-0.5">CT: {{ $carpeta->vinculacion->numero_contrato ?? 'N/A' }}</div>
                                
                                {{-- BADGES DE INCIDENCIAS — usa colección precargada (sin N+1) --}}
                                @php $incCarpeta = $carpeta->incidencias ?? collect(); @endphp
                                @if($incCarpeta->isNotEmpty() || !empty($carpeta->fin_observaciones_json))
                                    <div class="flex flex-wrap gap-1 justify-center mt-1.5">
                                        @if($incCarpeta->where('tipo', 'observacion')->isNotEmpty() || !empty($carpeta->fin_observaciones_json))
                                            <span class="bg-blue-100 text-blue-700 border border-blue-200 text-[7px] font-black px-1.5 py-0.5 rounded uppercase">OBS</span>
                                        @endif
                                        @if($incCarpeta->where('es_retenible', true)->isNotEmpty())
                                            <span class="bg-red-100 text-red-700 border border-red-200 text-[7px] font-black px-1.5 py-0.5 rounded uppercase">🔒 RET</span>
                                        @elseif($incCarpeta->where('tipo', 'contingencia')->isNotEmpty())
                                            <span class="bg-amber-100 text-amber-700 border border-amber-200 text-[7px] font-black px-1.5 py-0.5 rounded uppercase">CONT</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-center">
                                <span class="bg-[#f1f5f9] text-[#475569] border border-[#cbd5e1] text-[9px] font-black px-3 py-1 rounded uppercase tracking-tighter shadow-sm">
                                    {{ strtoupper(substr($carpeta->nombre_mes, 0, 3)) }} {{ $carpeta->anio }}
                                </span>
                            </td>
                            <td class="px-2 py-2 text-center">
                                @switch($carpeta->estado_revision)
                                    @case('REVISADO')
                                        <div x-data="{ open: false }" class="relative inline-block">
                                            <span @mouseenter="open = true" @mouseleave="open = false" 
                                                  class="bg-blue-100 text-blue-700 border border-blue-200 text-[8px] font-black px-2 py-0.5 rounded-full uppercase {{ $carpeta->motivo_devolucion_auditor ? 'cursor-help ring-2 ring-amber-400 ring-offset-1' : '' }}">
                                                Por Auditar {{ $carpeta->motivo_devolucion_auditor ? '⚠' : '' }}
                                            </span>
                                            <template x-if="open && '{{ addslashes($carpeta->motivo_devolucion_auditor) }}'">
                                                <div class="absolute z-[60] bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-3 bg-amber-600 text-white text-[10px] rounded-xl shadow-2xl border border-amber-400 animate-in fade-in zoom-in duration-200">
                                                    <div class="font-black text-white mb-1 uppercase tracking-widest border-b border-amber-400 pb-1">Devuelto por Supervisor</div>
                                                    <div class="font-medium leading-relaxed italic">
                                                        "{{ $carpeta->motivo_devolucion_auditor }}"
                                                    </div>
                                                    <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-8 border-transparent border-t-amber-600"></div>
                                                </div>
                                            </template>
                                        </div>
                                        @break
                                    @case('AUDITANDO')
                                        <span class="bg-indigo-100 text-indigo-700 border border-indigo-200 text-[8px] font-black px-2 py-0.5 rounded-full uppercase animate-pulse shadow-sm">Auditando</span>
                                        @break
                                    @case('PARA_EMITIR')
                                        <span class="bg-emerald-100 text-emerald-700 border border-emerald-200 text-[8px] font-black px-2 py-0.5 rounded-full uppercase text-center block shadow-sm border-emerald-300">Auditado</span>
                                        @break
                                    @case('EMITIDO')
                                        <span class="bg-purple-100 text-purple-700 border border-purple-200 text-[8px] font-black px-2 py-0.5 rounded-full uppercase text-center block shadow-sm border-purple-300">Emitido</span>
                                        @break
                                    @case('EN_REVISION')
                                        <div x-data="{ open: false }" class="relative inline-block">
                                            <span @mouseenter="open = true" @mouseleave="open = false" 
                                                  class="bg-red-100 text-red-700 border border-red-200 text-[8px] font-black px-2 py-0.5 rounded-full uppercase italic cursor-help">
                                                Devuelto
                                            </span>
                                            <template x-if="open && '{{ addslashes($carpeta->motivo_devolucion) }}'">
                                                <div class="absolute z-[60] bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 p-3 bg-gray-900 text-white text-[10px] rounded-xl shadow-2xl border border-gray-700 animate-in fade-in zoom-in duration-200">
                                                    <div class="font-black text-red-400 mb-1 uppercase tracking-widest border-b border-gray-700 pb-1">Motivo de Devolución</div>
                                                    <div class="font-medium leading-relaxed italic text-gray-200 text-left">
                                                        "{{ $carpeta->motivo_devolucion }}"
                                                    </div>
                                                    <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-8 border-transparent border-t-gray-900"></div>
                                                </div>
                                            </template>
                                        </div>
                                        @break
                                    @case('EN_CARGA')
                                        <span class="bg-gray-100 text-gray-700 border border-gray-200 text-[8px] font-black px-2 py-0.5 rounded-full uppercase italic">R. Analista</span>
                                        @break
                                    @default
                                        <span class="bg-gray-100 text-gray-500 text-[8px] font-bold px-2 py-0.5 rounded-full">{{ $carpeta->estado_revision }}</span>
                                @endswitch
                            </td>
                            <td class="px-2 py-2 text-center">
                                <div class="text-[9px] font-bold text-gray-600 dark:text-gray-400">{{ $carpeta->analista->name ?? 'N/A' }}</div>
                            </td>
                             <td class="px-2 py-2 text-center">
                                @if($carpeta->tipo_envio == 'NORMAL')
                                    <span class="text-green-600 dark:text-green-400 text-[10px] font-black">✔ DnP</span>
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
                            <td class="px-2 py-2 text-center">
                                <div class="flex items-center gap-1.5 justify-center">
                                    <button wire:click="verDetalle({{ $carpeta->id }})" class="bg-blue-600 hover:bg-blue-700 text-white text-[9px] font-black px-3 py-1.5 rounded shadow shadow-blue-500/30 flex items-center gap-1.5 transition-all active:scale-95">VER DOCS</button>
                                    @if($carpeta->estado_revision === 'EMITIDO')
                                        <button disabled class="bg-gray-400 text-white text-[9px] font-black px-3 py-1.5 rounded shadow flex items-center gap-1.5 cursor-not-allowed uppercase">EMITIDO</button>
                                    @elseif(in_array($carpeta->estado_revision, ['REVISADO', 'AUDITANDO', 'PARA_EMITIR']))
                                        <button wire:click="abrirModalCierre({{ $carpeta->id }})" class="{{ in_array($carpeta->estado_revision, ['REVISADO', 'AUDITANDO']) ? 'bg-amber-600 hover:bg-amber-700 border-amber-400' : 'bg-gray-600 hover:bg-gray-700 border-gray-400' }} text-white text-[9px] font-black px-3 py-1.5 rounded shadow border-b-2 flex items-center gap-1.5 transition-all active:scale-95 uppercase">{{ $carpeta->estado_revision === 'PARA_EMITIR' ? 'Ver Cierre' : 'Auditar' }}</button>
                                    @else
                                        <div x-data="{ open: false }" class="relative inline-block">
                                            <button disabled @mouseenter="open = true" @mouseleave="open = false" 
                                                    class="bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-[9px] font-black px-3 py-1.5 rounded shadow border-b-2 border-gray-400 flex items-center gap-1.5 cursor-help uppercase">
                                                {{ $carpeta->estado_revision === 'EN_REVISION' ? 'DEVUELTO' : 'POR ANALISTA' }}
                                            </button>
                                            <template x-if="open && '{{ addslashes($carpeta->motivo_devolucion) }}'">
                                                <div class="absolute z-[60] bottom-full right-0 mb-2 w-64 p-3 bg-gray-900 text-white text-[10px] rounded-xl shadow-2xl border border-gray-700 animate-in fade-in zoom-in duration-200">
                                                    <div class="font-black text-red-400 mb-1 uppercase tracking-widest border-b border-gray-700 pb-1 text-center">Motivo de Devolución</div>
                                                    <div class="font-medium leading-relaxed italic text-gray-200 text-left">
                                                        "{{ $carpeta->motivo_devolucion }}"
                                                    </div>
                                                    <div class="absolute top-full right-4 -mt-1 border-8 border-transparent border-t-gray-900"></div>
                                                </div>
                                            </template>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-12 text-center text-gray-400 text-[11px] font-black uppercase tracking-widest">
                                No se encontraron registros de auditoria bajo estos criterios
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
            {{ $carpetas->links() }}
        </div>
    </div>

    <!-- MODAL DETALLE (DOCUMENTOS) -->
    @if($carpeta_detalle_id && $carpetaDetalle && !$showModalCierre)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity" wire:click="cerrarDetalle"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-5xl max-h-[95vh] flex flex-col border-4 border-indigo-500/20">

                    <div class="bg-[#1a3560] text-white px-5 py-3 rounded-t-lg flex justify-between items-start flex-shrink-0">
                        <div>
                            <h2 class="text-sm font-black uppercase tracking-tight">
                                {{ $carpetaDetalle->vinculacion->contratista->razon_social ?? '-' }}
                                <span class="text-blue-300 ml-2 font-mono text-[11px]">ID: {{ $carpetaDetalle->vinculacion->id_registro ?? '-' }}</span>
                            </h2>
                            <p class="text-[10px] text-blue-200 mt-0.5 uppercase">
                                <span class="font-bold text-white/60">PERIODO:</span> {{ strtoupper($carpetaDetalle->nombre_mes) }} {{ $carpetaDetalle->anio }}
                                &nbsp;·&nbsp;
                                <span class="font-bold text-white/60">PRINCIPAL:</span> {{ $carpetaDetalle->vinculacion->unidadOrganizacional->mandante->razon_social ?? '-' }}
                                &nbsp;·&nbsp;
                                <span class="font-bold text-white/60">LUGAR:</span> {{ $carpetaDetalle->vinculacion->dependencia->nombre ?? '-' }}
                                &nbsp;·&nbsp;
                                <span class="font-bold text-white/60">CT:</span> {{ $carpetaDetalle->vinculacion->numero_contrato ?? 'N/A' }}
                            </p>
                        </div>
                        <button wire:click="cerrarDetalle" class="text-white/50 hover:text-white font-black text-xs uppercase px-2 py-1 rounded-lg border border-white/20 transition-all">Cerrar</button>
                    </div>

                    <div class="overflow-y-auto p-5 flex-1 space-y-4">
                        @if($carpetaDetalle->motivo_devolucion_auditor)
                            <div class="mb-5 bg-amber-50 border-2 border-amber-200 rounded-2xl p-4 shadow-sm animate-in slide-in-from-top-2 duration-500">
                                <div class="flex items-start gap-3">
                                    <div class="bg-amber-500 text-white p-2 rounded-xl shadow-lg shadow-amber-500/20">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-black text-amber-700 uppercase tracking-widest mb-1">PERIODO DEVUELTO POR SUPERVISOR / EMISOR</h4>
                                        <p class="text-[11px] font-bold text-amber-900 leading-relaxed italic">
                                            "{{ $carpetaDetalle->motivo_devolucion_auditor }}"
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="grid grid-cols-1 gap-4">
                            @foreach($requisitosPorClasif as $clasificacion => $requisitos)
                                <div class="border dark:border-gray-700 rounded-lg overflow-hidden shadow-sm">
                                    <div class="bg-gray-50 dark:bg-gray-700/50 px-3 py-1.5 text-[10px] font-black text-[#1a3560] dark:text-blue-300 uppercase tracking-wide border-b dark:border-gray-700">
                                        {{ $clasificacion }}
                                    </div>
                                    <div class="divide-y dark:divide-gray-700">
                                        @foreach($requisitos as $requisito)
                                            @php $doc = $documentosPorRequisito[$requisito->id] ?? null; @endphp
                                            <div class="flex items-center justify-between p-2.5">
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        @if($doc)
                                                            <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                                            <span class="text-[11px] font-bold text-gray-700 dark:text-gray-200">{{ $requisito->nombre }}</span>
                                                        @else
                                                            <div class="w-2 h-2 rounded-full bg-red-400"></div>
                                                            <span class="text-[11px] font-bold text-gray-400 dark:text-gray-500 italic">{{ $requisito->nombre }} (Sin Carga)</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                @if($doc && $doc->path)
                                                    <div class="flex gap-2 ml-4">
                                                        <a href="{{ route('archivo.publico', ['filePath' => $doc->path, 'name' => $doc->nombre_original ?? 'doc.pdf']) }}" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white text-[9px] font-black px-2 py-0.5 rounded transition-colors uppercase group flex items-center gap-1" title="Visualizar documento">
                                                            <svg class="w-3 h-3 text-white/70 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                            Ver
                                                        <a href="{{ route('archivo.publico', ['filePath' => $doc->path, 'download' => 1, 'name' => $doc->nombre_original ?? 'doc.pdf']) }}" class="bg-gray-600 hover:bg-gray-700 text-white text-[9px] font-black px-2 py-0.5 rounded transition-colors uppercase group flex items-center gap-1" title="Descargar documento">
                                                            <svg class="w-3 h-3 text-white/70 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                            Descargar
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- NOMINA -->
                        <div class="mt-6 border-t dark:border-gray-700 pt-4">
                            <h3 class="text-[11px] font-black text-gray-700 dark:text-white uppercase mb-3 flex items-center gap-2">NOMINA DE TRABAJADORES ({{ $trabajadoresPeriodo->count() }})</h3>
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg border dark:border-gray-700 overflow-hidden">
                                <table class="w-full text-[10px] border-collapse">
                                    <thead class="bg-gray-200 dark:bg-gray-700 font-black text-gray-500 text-center uppercase border-b dark:border-gray-600">
                                        <tr>
                                            <th class="px-3 py-1.5 text-left tracking-widest">RUT</th>
                                            <th class="px-3 py-1.5 text-left tracking-widest">Nombre Completo</th>
                                            <th class="px-3 py-1.5 text-left tracking-widest">Cargo</th>
                                            <th class="px-3 py-1.5 tracking-widest">F. Ingreso</th>
                                            <th class="px-3 py-1.5 tracking-widest">F. Contrato</th>
                                            <th class="px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 tracking-widest">NUEVO</th>
                                            <th class="px-3 py-1.5 tracking-widest">Estado Verificado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y dark:divide-gray-700">
                                        @php
                                            $pStart = \Carbon\Carbon::create($carpetaDetalle->anio, $carpetaDetalle->mes, 1)->startOfMonth();
                                            $pEnd   = $pStart->copy()->endOfMonth();
                                        @endphp
                                        @forelse($trabajadoresPeriodo as $vt)
                                            @php
                                                $snapRut = $vt->snapshot_rut ?: ($vt->vinculacion->trabajador->rut ?? '-');
                                                $snapNombre = $vt->snapshot_nombres ?: ($vt->vinculacion->trabajador->nombre_completo ?? '-');
                                                $snapCargo = $vt->snapshot_cargo ?: ($vt->vinculacion->cargoMandante->nombre_cargo ?? '-');
                                                $fiIngreso = $vt->snapshot_fecha_ingreso ?: ($vt->vinculacion->fecha_ingreso_vinculacion ?? null);
                                                $fiContrato = $vt->snapshot_fecha_contrato ?: ($vt->vinculacion->fecha_contrato ?? null);

                                                $esNuevo   = $fiIngreso && \Carbon\Carbon::parse($fiIngreso)->between($pStart, $pEnd);
                                                $estado    = $vt->estado_revision;
                                                // Definición de colores del semáforo
                                                $colorCls = "bg-gray-100 text-gray-700 border-gray-200"; // Default
                                                if ($estado === 'PENDIENTE') {
                                                    $colorCls = "bg-amber-100 text-amber-700 border-amber-200";
                                                } elseif (in_array($estado, ['VERIFICADO', 'VALIDADO'])) {
                                                    $colorCls = "bg-green-100 text-green-700 border-green-200";
                                                } elseif ($estado === 'FINIQUITADO') {
                                                    $colorCls = "bg-red-100 text-red-700 border-red-200";
                                                } elseif ($estado === 'MOVIDO' || $estado === 'PRESENTE_OTRA_VINCULACION') {
                                                    $colorCls = "bg-blue-100 text-blue-700 border-blue-200";
                                                } elseif ($estado === 'CESACION_PRINCIPAL' || $estado === 'BAJA_MANDANTE') {
                                                    $colorCls = "bg-purple-100 text-purple-700 border-purple-200";
                                                }
                                            @endphp
                                            <tr class="hover:bg-blue-50/50 dark:hover:bg-blue-900/10 transition-colors">
                                                <td class="px-3 py-2">
                                                    <div class="flex flex-col">
                                                        <span class="font-mono text-blue-600 font-bold tracking-tighter">{{ $snapRut }}</span>
                                                        @if($vt->tipo_registro === 'ARRASTRE')
                                                            <span class="text-[7px] bg-orange-100 text-orange-700 px-1 rounded w-fit font-black uppercase">Arrastre</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2">
                                                    <div class="font-black uppercase text-gray-800 dark:text-gray-200 leading-tight">{{ $snapNombre }}</div>
                                                </td>
                                                <td class="px-3 py-2 text-gray-600 dark:text-gray-400 uppercase leading-tight">
                                                    {{ $snapCargo }}
                                                </td>
                                                <td class="px-3 py-2 text-center text-gray-700 dark:text-gray-300 font-bold">
                                                    {{ $fiIngreso ? \Carbon\Carbon::parse($fiIngreso)->format('d/m/Y') : '-' }}
                                                </td>
                                                <td class="px-3 py-2 text-center text-gray-700 dark:text-gray-300 font-bold">
                                                    {{ $fiContrato ? \Carbon\Carbon::parse($fiContrato)->format('d/m/Y') : '-' }}
                                                </td>
                                                <td class="px-3 py-2 text-center bg-emerald-50/30 dark:bg-emerald-900/10">
                                                    @if($esNuevo)
                                                        <span class="inline-flex items-center gap-0.5 bg-emerald-100 text-emerald-800 text-[8px] font-black px-2 py-0.5 rounded-full uppercase border border-emerald-200">
                                                            ✨ NUEVO
                                                        </span>
                                                    @else
                                                        <span class="text-gray-300 dark:text-gray-600">—</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-center">
                                                    <span class="{{ $colorCls }} text-[8px] font-black px-2 py-0.5 rounded-full uppercase italic border">{{ $estado }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="7" class="p-4 text-center text-gray-400 italic font-bold">No hay trabajadores registrados para este periodo.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- OBSERVACIONES (solo en detalle) -->
                        <div class="mt-6 border-t dark:border-gray-700 pt-4">
                            <label class="text-[10px] font-black text-gray-600 dark:text-gray-400 uppercase block mb-1 tracking-wider">Observaciones Generales de la Auditoria</label>
                            <textarea wire:change="guardarObservacionAuditor({{ $carpetaDetalle->id }}, $event.target.value)"
                                      class="w-full text-xs p-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all font-bold"
                                      rows="2" placeholder="Detalle cualquier observacion relevante...">{{ $carpetaDetalle->observaciones_auditor }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================ -->
    <!-- MODAL DE CIERRE (AUDITORIA FINALIZACIÓN)                      -->
    <!-- ============================================================ -->
    @if($showModalCierre && $carpetaDetalle)
        @php
            $obsIds = array_map('intval', $observacionesSeleccionadas ?? []);
        @endphp
        <div class="fixed inset-0 z-[60] overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/80 backdrop-blur-md transition-opacity" wire:click="cerrarModalCierre"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-4xl bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden flex flex-col max-h-[95vh] border-4 border-[#1a3560]/30 shadow-indigo-900/40">

                    <!-- HEADER -->
                    <div class="bg-[#1a3560] text-white px-6 py-4 shrink-0 shadow-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="flex items-center gap-3 mb-1">
                                    <span class="bg-white/20 text-white text-[10px] font-black px-2 py-0.5 rounded uppercase tracking-widest {{ $esBloqueado ? '' : 'animate-pulse' }}">CIERRE DE AUDITORIA</span>
                                    <span class="text-[11px] text-blue-200 font-bold uppercase">
                                        {{ strtoupper($carpetaDetalle->nombre_mes) }} {{ $carpetaDetalle->anio }}
                                    </span>
                                    @if($esBloqueado)
                                        <span class="bg-emerald-500 text-white text-[8px] font-black px-2 py-0.5 rounded-full uppercase">✓ APROBADO</span>
                                    @endif
                                </div>
                                <h2 class="text-lg font-black uppercase tracking-tight leading-tight">
                                    {{ $carpetaDetalle->vinculacion->contratista->razon_social ?? '-' }}
                                    <span class="text-blue-300 ml-2">ID: {{ $carpetaDetalle->vinculacion->id_registro ?? '-' }}</span>
                                </h2>
                                <p class="text-[11px] text-blue-300 font-bold uppercase mt-1 leading-relaxed">
                                    <span class="text-white/60">PRINCIPAL:</span> {{ $carpetaDetalle->vinculacion->unidadOrganizacional->mandante->razon_social ?? '-' }}
                                    &nbsp;·&nbsp;
                                    <span class="text-white/60">LUGAR:</span> {{ $carpetaDetalle->vinculacion->dependencia->nombre ?? '-' }}
                                    &nbsp;·&nbsp;
                                    <span class="text-white/60">CT:</span> {{ $carpetaDetalle->vinculacion->numero_contrato ?? 'N/A' }}
                                </p>
                            </div>
                            <button wire:click="cerrarModalCierre" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition-all text-white font-black text-[10px] uppercase px-4 ring-1 ring-white/20 shrink-0 ml-4">X CERRAR</button>
                        </div>
                    </div>

                    <!-- BODY -->
                    <div class="p-6 overflow-y-auto flex-1 space-y-5 bg-gray-50 dark:bg-gray-900 shadow-inner">

                        {{-- BANNER SOLO LECTURA --}}
                        @if($esBloqueado)
                            <div class="flex items-center gap-3 bg-amber-50 border-2 border-amber-300 text-amber-800 px-4 py-3 rounded-xl font-black text-[11px] uppercase tracking-wide">
                                🔒 PERIODO AUDITADO — SOLO LECTURA. Para modificar datos, use "ABRIR PERIODO".
                            </div>
                        @endif

                        {{-- DOTACION --}}
                        <div class="grid grid-cols-3 gap-3">
                            <div class="bg-emerald-600 p-3 rounded-lg border-b-4 border-emerald-800 shadow-md">
                                <label class="text-[9px] font-black text-white/70 uppercase block text-center mb-1">TRAB. NUEVOS</label>
                                <input type="number" wire:model.defer="aud_contratados_periodo" {{ $esBloqueado ? 'disabled' : '' }} class="w-full text-center text-2xl font-black text-white bg-black/10 border-none rounded-lg focus:ring-1 focus:ring-white disabled:opacity-70">
                            </div>
                            <div class="bg-rose-600 p-3 rounded-lg border-b-4 border-rose-800 shadow-md">
                                <label class="text-[9px] font-black text-white/70 uppercase block text-center mb-1">TRAB. BAJAS</label>
                                <input type="number" wire:model.defer="aud_desvinculados_periodo" {{ $esBloqueado ? 'disabled' : '' }} class="w-full text-center text-2xl font-black text-white bg-black/10 border-none rounded-lg focus:ring-1 focus:ring-white disabled:opacity-70">
                            </div>
                            <div class="bg-indigo-600 p-3 rounded-lg border-b-4 border-indigo-800 shadow-md">
                                <label class="text-[9px] font-black text-white/70 uppercase block text-center mb-1">TOTAL VIGENTES</label>
                                <input type="number" wire:model.defer="aud_total_vigentes" {{ $esBloqueado ? 'disabled' : '' }} class="w-full text-center text-2xl font-black text-white bg-black/10 border-none rounded-lg focus:ring-1 focus:ring-white disabled:opacity-70">
                            </div>
                        </div>

                        {{-- TRABAJADORES REVISADOS + RECEPCION --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div class="rounded-lg overflow-hidden border border-[#1a3560]/30 shadow-md">
                                <div class="bg-[#1a3560] text-white text-[9px] font-black uppercase text-center py-1.5 tracking-widest">TRABAJADORES REVISADOS</div>
                                <div class="p-3 bg-white dark:bg-gray-800 flex items-center justify-center gap-3">
                                    <label class="text-[9px] font-black text-gray-500 uppercase">N° Total :</label>
                                    <input type="number" wire:model.defer="aud_trabajadores_revisados" min="0" {{ $esBloqueado ? 'disabled' : '' }}
                                           class="w-24 text-center font-black text-lg bg-gray-50 dark:bg-gray-700 border-2 border-[#1a3560]/30 rounded-lg py-1 focus:border-[#1a3560] focus:ring-0 disabled:opacity-70">
                                </div>
                            </div>
                            <div class="rounded-lg overflow-hidden border border-[#1a3560]/30 shadow-md">
                                <div class="bg-[#1a3560] text-white text-[9px] font-black uppercase text-center py-1.5 tracking-widest">RECEPCION DOCUMENTACION</div>
                                <div class="p-3 bg-white dark:bg-gray-800 flex items-center justify-center">
                                    <span class="font-mono font-black text-[#1a3560] dark:text-blue-300 text-lg bg-blue-50 dark:bg-blue-900/30 px-4 py-1 rounded-lg border border-blue-200 dark:border-blue-700 shadow-inner">
                                        {{ $carpetaDetalle->fecha_envio ? $carpetaDetalle->fecha_envio->format('d/m/Y') : '—' }}
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
                                <input type="number" wire:model.defer="aud_remuneraciones_pagadas" {{ $esBloqueado ? 'disabled' : '' }} class="w-48 text-left font-mono font-bold text-xs border border-gray-300 px-2 py-0.5 outline-none focus:border-blue-500 bg-white disabled:opacity-75">
                            </div>
                        </div>

                        <!-- COTIZACIONES PREVISIONALES (Diseño Plano 1:1) -->
                        <div class="border border-gray-300 mb-0 border-t-0">
                            <div class="bg-[#003a5c] text-white text-center py-1 font-black text-[11px] uppercase tracking-widest">COTIZACIONES PREVISIONALES</div>
                            <div class="bg-gray-50 px-4 py-1.5 flex items-center border-t border-gray-300">
                                <span class="w-1/2 text-[10px] font-bold text-gray-700 uppercase">Cotizaciones Pagadas</span>
                                <span class="px-2 font-bold text-gray-700">:</span>
                                <input type="number" wire:model.defer="aud_cotizaciones_pagadas" {{ $esBloqueado ? 'disabled' : '' }} class="w-48 text-left font-mono font-bold text-xs border border-gray-300 px-2 py-0.5 outline-none focus:border-blue-500 bg-white disabled:opacity-75">
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
                                <input type="number" wire:model.defer="aud_aviso_previo_trabajadores" {{ $esBloqueado ? 'disabled' : '' }} class="w-48 text-left font-mono font-bold text-xs border border-gray-300 px-2 py-0.5 outline-none bg-white disabled:opacity-75">
                            </div>
                            <div class="bg-gray-50 px-4 py-1 flex items-center border-t border-gray-200">
                                <span class="w-1/2 text-[10px] font-bold text-gray-700 uppercase">Total pagado</span>
                                <span class="px-2 font-bold text-gray-700">:</span>
                                <input type="number" wire:model.defer="aud_aviso_previo_total" {{ $esBloqueado ? 'disabled' : '' }} class="w-48 text-left font-mono font-bold text-xs border border-gray-300 px-2 py-0.5 outline-none bg-white disabled:opacity-75">
                            </div>

                            <!-- Año de Servicio -->
                            <div class="bg-[#fcc01a] text-black px-4 py-0.5 font-black text-[10px] uppercase border-t border-gray-300">Año de Servicio</div>
                            <div class="bg-white px-4 py-1 flex items-center border-t border-gray-100">
                                <span class="w-1/2 text-[10px] font-bold text-gray-700 uppercase">Trabajadores con pago</span>
                                <span class="px-2 font-bold text-gray-700">:</span>
                                <input type="number" wire:model.defer="aud_anio_servicio_trabajadores" {{ $esBloqueado ? 'disabled' : '' }} class="w-48 text-left font-mono font-bold text-xs border border-gray-300 px-2 py-0.5 outline-none bg-white disabled:opacity-75">
                            </div>
                            <div class="bg-gray-50 px-4 py-1 flex items-center border-t border-gray-200">
                                <span class="w-1/2 text-[10px] font-bold text-gray-700 uppercase">Total pagado</span>
                                <span class="px-2 font-bold text-gray-700">:</span>
                                <input type="number" wire:model.defer="aud_anio_servicio_total" {{ $esBloqueado ? 'disabled' : '' }} class="w-48 text-left font-mono font-bold text-xs border border-gray-300 px-2 py-0.5 outline-none bg-white disabled:opacity-75">
                            </div>

                            <!-- Feriado -->
                            <div class="bg-[#fcc01a] text-black px-4 py-0.5 font-black text-[10px] uppercase border-t border-gray-300">Feriado</div>
                            <div class="bg-white px-4 py-1 flex items-center border-t border-gray-100">
                                <span class="w-1/2 text-[10px] font-bold text-gray-700 uppercase">Trabajadores con pago</span>
                                <span class="px-2 font-bold text-gray-700">:</span>
                                <input type="number" wire:model.defer="aud_feriado_trabajadores" {{ $esBloqueado ? 'disabled' : '' }} class="w-48 text-left font-mono font-bold text-xs border border-gray-300 px-2 py-0.5 outline-none bg-white disabled:opacity-75">
                            </div>
                            <div class="bg-gray-50 px-4 py-1 flex items-center border-t border-gray-200">
                                <span class="w-1/2 text-[10px] font-bold text-gray-700 uppercase">Total pagado</span>
                                <span class="px-2 font-bold text-gray-700">:</span>
                                <input type="number" wire:model.defer="aud_feriado_total" {{ $esBloqueado ? 'disabled' : '' }} class="w-48 text-left font-mono font-bold text-xs border border-gray-300 px-2 py-0.5 outline-none bg-white disabled:opacity-75">
                            </div>
                        </div>
                                          {{-- ===================================================================== --}}
                        {{-- SECCIÓN: INCIDENCIAS (Observaciones y Contingencias)                    --}}
                        {{-- ===================================================================== --}}
                        <div class="border border-gray-300 border-t-0 overflow-hidden">

                            {{-- Header de la sección --}}
                            <div class="bg-[#1a3560] text-white px-4 py-2 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="text-[10px] font-black uppercase tracking-widest">
                                        ⚠ OBSERVACIONES Y CONTINGENCIAS
                                    </span>
                                    @if(count($incidencias) > 0)
                                        <span class="bg-white/20 text-white text-[8px] font-black px-2 py-0.5 rounded-full">
                                            {{ count($incidencias) }} ítems
                                        </span>
                                    @endif
                                </div>
                                @if(!$esBloqueado)
                                    <button wire:click="abrirModalNuevaIncidencia"
                                            class="flex items-center gap-1.5 bg-amber-400 hover:bg-amber-300 text-black text-[10px] font-black px-3 py-1.5 rounded-lg transition-all shadow-sm uppercase">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                        AGREGAR
                                    </button>
                                @else
                                    <span class="text-[9px] font-black text-white/40 uppercase italic">Solo lectura</span>
                                @endif
                            </div>

                            {{-- Lista de incidencias como acordeón --}}
                            @if(count($incidencias) === 0)
                                <div class="bg-white px-4 py-6 text-center text-[10px] text-gray-400 italic uppercase tracking-widest">
                                    Sin observaciones ni contingencias registradas para esta carpeta.
                                </div>
                            @else
                                <div class="divide-y divide-gray-200">
                                    @foreach($incidencias as $inc)
                                        @php
                                            $isOpen = $incidenciaExpandida == $inc['codigo'];
                                            $colorMap = [
                                                'blue'  => ['badge' => 'bg-blue-100 text-blue-800 border-blue-200',  'row' => 'bg-blue-50/40',  'bar' => 'bg-blue-600'],
                                                'red'   => ['badge' => 'bg-red-100 text-red-800 border-red-200',    'row' => 'bg-red-50/30',   'bar' => 'bg-red-600'],
                                                'amber' => ['badge' => 'bg-amber-100 text-amber-800 border-amber-200','row' => 'bg-amber-50/30','bar' => 'bg-amber-500'],
                                            ];
                                            $c = $colorMap[$inc['color_badge']] ?? $colorMap['blue'];
                                        @endphp
                                        <div class="{{ $c['row'] }} {{ $isOpen ? 'shadow-inner' : '' }}">

                                            {{-- Fila del código (clickeable) --}}
                                            <div wire:click="toggleIncidencia({{ $inc['codigo'] }})"
                                                 class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-black/5 transition-colors select-none">

                                                {{-- Barra de color izq --}}
                                                <div class="w-1 h-8 rounded-full {{ $c['bar'] }} shrink-0"></div>

                                                {{-- Código --}}
                                                <span class="font-mono font-black text-[14px] text-gray-800 tracking-tight w-20 shrink-0">
                                                    {{ $inc['codigo'] }}
                                                </span>

                                                {{-- Badge tipo --}}
                                                <span class="text-[8px] font-black uppercase border px-2 py-0.5 rounded-full {{ $c['badge'] }} shrink-0">
                                                    {{ $inc['label_tipo'] }}
                                                </span>

                                                {{-- Clasificación --}}
                                                <span class="text-[10px] font-black text-gray-700 uppercase flex-1 truncate">
                                                    {{ $inc['clasificacion'] }}
                                                </span>

                                                {{-- Alcance --}}
                                                <span class="text-[8px] font-bold text-gray-500 shrink-0 italic">
                                                    @if($inc['aplica_empresa'])
                                                        🏢 Empresa
                                                    @elseif($inc['trabajador'])
                                                        👤 {{ $inc['trabajador']['nombre'] ?? '-' }}
                                                    @endif
                                                </span>

                                                {{-- Monto si tiene --}}
                                                @if($inc['monto'] > 0)
                                                    <span class="text-[9px] font-black text-rose-700 font-mono shrink-0">
                                                        ${{ number_format($inc['monto'], 0, ',', '.') }}
                                                    </span>
                                                @endif

                                                {{-- Flecha acordeón --}}
                                                <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform {{ $isOpen ? 'rotate-180' : '' }}"
                                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </div>

                                            {{-- Detalle expandido --}}
                                            @if($isOpen)
                                                <div class="px-6 pb-3 pt-1 bg-white/60 border-t border-gray-200">
                                                    <div class="text-[10px] text-gray-700 leading-relaxed font-medium mb-2">
                                                        {{ $inc['causal'] }}
                                                    </div>
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex flex-wrap gap-2">
                                                            @if($inc['trabajador'])
                                                                <span class="bg-gray-100 border border-gray-200 text-gray-600 text-[8px] font-black px-2 py-0.5 rounded-full uppercase">
                                                                    RUT: {{ $inc['trabajador']['rut'] }}
                                                                </span>
                                                            @endif
                                                            @if($inc['monto'])
                                                                <span class="bg-rose-100 border border-rose-200 text-rose-700 text-[8px] font-black px-2 py-0.5 rounded-full uppercase">
                                                                    Monto: ${{ number_format($inc['monto'], 0, ',', '.') }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        @if(!$esBloqueado)
                                                            <button wire:click="eliminarIncidencia({{ $inc['id'] }})"
                                                                    onclick="return confirm('¿Eliminar incidencia {{ $inc['codigo'] }}?')"
                                                                    class="text-[9px] font-black text-red-500 hover:text-red-700 hover:bg-red-50 px-2 py-1 rounded transition-all uppercase flex items-center gap-1">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                                Eliminar
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                    </div>{{-- /body --}}

                    <!-- FOOTER ACCIONES -->
                    <div class="bg-[#f1f5f9] dark:bg-[#0f172a] px-8 py-5 flex justify-between items-center border-t border-gray-200 dark:border-gray-700 shadow-2xl shrink-0">
                        @if(!$esBloqueado)
                            <button wire:click="abrirModalRechazo"
                                    class="bg-red-600 hover:bg-black text-white text-[11px] font-black px-8 py-4 rounded-2xl transition-all shadow-xl shadow-red-500/20 active:scale-95 uppercase tracking-tighter">
                                DEVOLVER AL ANALISTA
                            </button>
                        @else
                            <div class="text-[10px] font-black text-emerald-700 uppercase italic">✓ Auditoria completada el {{ $carpetaDetalle->fecha_auditoria?->format('d/m/Y') ?? '-' }}</div>
                        @endif

                        <div class="flex gap-4 items-center">
                            <button wire:click="cerrarModalCierre" class="text-[11px] font-black text-gray-500 hover:text-gray-800 dark:hover:text-white uppercase transition-colors mr-2 italic">
                                Cerrar
                            </button>
                            @if(!$esBloqueado)
                                <button wire:click="guardarProgreso"
                                        class="bg-emerald-600 hover:bg-emerald-800 text-white text-[11px] font-black px-8 py-4 rounded-2xl transition-all shadow-2xl shadow-emerald-500/30 active:scale-95 uppercase">
                                    GUARDAR
                                </button>
                                <button wire:click="finalizarPeriodo"
                                        onclick="return confirm('¿Está seguro de FINALIZAR PERIODO?\n\nPasará a Listo para Emitir y ya no podrá efectuar cambios en las incidencias ni montos.')"
                                        class="bg-[#1a3560] hover:bg-indigo-900 text-white text-[12px] font-black px-10 py-4 rounded-2xl transition-all shadow-2xl shadow-indigo-900/50 active:scale-95 uppercase flex items-center gap-2">
                                    FINALIZAR
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </button>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    @endif

    {{-- ================================================================ --}}
    {{-- MODAL: NUEVA INCIDENCIA (Observación o Contingencia)              --}}
    {{-- ================================================================ --}}
    @if($showModalNuevaIncidencia && $carpetaDetalle)
        @php
            $tipoActual    = $nuevaIncidencia['tipo'] ?? 'observacion';
            $subtipoActual = $nuevaIncidencia['subtipo'] ?? null;

            // Clasificaciones según tipo/subtipo
            if ($tipoActual === 'observacion') {
                $clasifs = \App\Models\CarpetaTrabajadorContingencia::clasificacionesObservacion();
            } elseif ($subtipoActual === 'retenible') {
                $clasifs = \App\Models\CarpetaTrabajadorContingencia::clasificacionesContingenciaRetenible();
            } else {
                $clasifs = \App\Models\CarpetaTrabajadorContingencia::clasificacionesContingenciaNoRetenible();
            }

            // Catálogo filtrado
            $catalogoFiltrado = $tipoActual === 'observacion' ? $catalogoObservaciones : $catalogoContingencias;
        @endphp
        <div class="fixed inset-0 z-[80] overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" wire:click="cerrarModalNuevaIncidencia"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border-4 border-[#1a3560]/30">

                    {{-- Header --}}
                    <div class="bg-[#1a3560] text-white px-6 py-4 shrink-0">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="text-[8px] font-black uppercase tracking-widest text-blue-300">NUEVA INCIDENCIA</div>
                                <h3 class="text-sm font-black uppercase tracking-tight mt-0.5">
                                    {{ $carpetaDetalle->vinculacion->contratista->razon_social ?? '-' }}
                                </h3>
                                <p class="text-[9px] text-blue-300 mt-0.5 font-bold uppercase">
                                    {{ strtoupper($carpetaDetalle->nombre_mes) }} {{ $carpetaDetalle->anio }}
                                </p>
                            </div>
                            <button wire:click="cerrarModalNuevaIncidencia"
                                    class="bg-white/10 hover:bg-white/20 text-white font-black text-xs uppercase px-4 py-2 rounded-xl transition-all ring-1 ring-white/20">
                                CANCELAR
                            </button>
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="overflow-y-auto flex-1 p-5 space-y-4">

                        {{-- 1. TIPO: Observación / Contingencia --}}
                        <div>
                            <label class="text-[9px] font-black uppercase text-gray-500 tracking-widest block mb-2">1. TIPO DE ÍTEM</label>
                            <div class="flex gap-3">
                                <label class="flex-1 flex items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all {{ $nuevaIncidencia['tipo'] === 'observacion' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-gray-50 hover:border-gray-300' }}">
                                    <input type="radio" wire:model.live="nuevaIncidencia.tipo" value="observacion" class="text-blue-600 focus:ring-blue-500">
                                    <div>
                                        <div class="text-[11px] font-black text-gray-800 uppercase">Observación</div>
                                        <div class="text-[8px] text-gray-500">Puede aplicar a empresa o trabajadores</div>
                                    </div>
                                </label>
                                <label class="flex-1 flex items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all {{ $nuevaIncidencia['tipo'] === 'contingencia' ? 'border-red-500 bg-red-50' : 'border-gray-200 bg-gray-50 hover:border-gray-300' }}">
                                    <input type="radio" wire:model.live="nuevaIncidencia.tipo" value="contingencia" class="text-red-600 focus:ring-red-500">
                                    <div>
                                        <div class="text-[11px] font-black text-gray-800 uppercase">Contingencia</div>
                                        <div class="text-[8px] text-gray-500">Siempre por trabajador</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- 2. SUBTIPO (solo contingencias) --}}
                        @if($nuevaIncidencia['tipo'] === 'contingencia')
                            <div>
                                <label class="text-[9px] font-black uppercase text-gray-500 tracking-widest block mb-2">2. TIPO DE CONTINGENCIA</label>
                                <div class="flex gap-3">
                                    <label class="flex-1 flex items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all {{ $nuevaIncidencia['subtipo'] === 'retenible' ? 'border-red-600 bg-red-50' : 'border-gray-200 bg-gray-50 hover:border-gray-300' }}">
                                        <input type="radio" wire:model.live="nuevaIncidencia.subtipo" value="retenible" class="text-red-600 focus:ring-red-500">
                                        <div>
                                            <div class="text-[11px] font-black text-red-700 uppercase">🔒 Retenible</div>
                                            <div class="text-[8px] text-gray-500">Rem · Cot · AP · AS · Fer · RD · Fin</div>
                                        </div>
                                    </label>
                                    <label class="flex-1 flex items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all {{ $nuevaIncidencia['subtipo'] === 'no_retenible' ? 'border-amber-500 bg-amber-50' : 'border-gray-200 bg-gray-50 hover:border-gray-300' }}">
                                        <input type="radio" wire:model.live="nuevaIncidencia.subtipo" value="no_retenible" class="text-amber-600 focus:ring-amber-500">
                                        <div>
                                            <div class="text-[11px] font-black text-amber-700 uppercase">NO Retenible</div>
                                            <div class="text-[8px] text-gray-500">AS · AP · Otras</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        @endif

                        {{-- 3. CLASIFICACIÓN --}}
                        <div>
                            <label class="text-[9px] font-black uppercase text-gray-500 tracking-widest block mb-1.5">
                                {{ $nuevaIncidencia['tipo'] === 'contingencia' ? '3.' : '2.' }} CLASIFICACIÓN <span class="text-red-500">*</span>
                            </label>
                            <select wire:model.live="nuevaIncidencia.clasificacion"
                                    class="w-full text-[11px] font-bold px-3 py-2 rounded-lg border-2 border-gray-200 focus:border-blue-500 focus:ring-0 bg-white dark:bg-gray-900 dark:text-white dark:border-gray-600">
                                <option value="">-- Seleccione clasificación --</option>
                                @foreach($clasifs as $cl)
                                    <option value="{{ $cl }}">{{ $cl }}</option>
                                @endforeach
                            </select>
                            @error('nuevaIncidencia.clasificacion')
                                <p class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 4. TEXTO DE LA INCIDENCIA --}}
                        <div>
                            <label class="text-[9px] font-black uppercase text-gray-500 tracking-widest block mb-1.5">
                                {{ $nuevaIncidencia['tipo'] === 'contingencia' ? '4.' : '3.' }} TEXTO DE LA INCIDENCIA <span class="text-red-500">*</span>
                            </label>

                            {{-- Botón para abrir modal del catálogo --}}
                            @if(!empty($catalogoFiltrado))
                                <div class="mb-2 flex items-center gap-2">
                                    <button type="button"
                                            wire:click="abrirModalCatalogo"
                                            class="flex items-center gap-2 bg-[#1a3560] hover:bg-indigo-900 text-white text-[10px] font-black px-4 py-2 rounded-lg transition-all shadow-sm uppercase">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10"/></svg>
                                        📋 SELECCIONAR DEL CATÁLOGO
                                        <span class="bg-white/20 text-white text-[8px] px-1.5 py-0.5 rounded-full">{{ count($catalogoFiltrado) }} ítems</span>
                                    </button>
                                    @if($nuevaIncidencia['catalogo_item_id'])
                                        <span class="flex items-center gap-1 text-[9px] font-black text-emerald-600 bg-emerald-50 border border-emerald-200 px-2 py-1 rounded-lg">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            Del catálogo
                                        </span>
                                        <button type="button"
                                                wire:click="$set('nuevaIncidencia.catalogo_item_id', null)"
                                                class="text-[9px] text-gray-400 hover:text-red-500 font-black uppercase transition-colors">
                                            ✕ quitar
                                        </button>
                                    @endif
                                </div>
                            @endif

                            {{-- Textarea editable --}}
                            <textarea wire:model.defer="nuevaIncidencia.causal"
                                      rows="4"
                                      placeholder="El texto aparecerá aquí al seleccionar del catálogo, o escríbalo directamente..."
                                      class="w-full text-[11px] font-medium px-3 py-2 rounded-lg border-2 border-gray-200 focus:border-blue-500 focus:ring-0 bg-white dark:bg-gray-900 dark:text-white resize-none leading-relaxed"></textarea>

                            @error('nuevaIncidencia.causal')
                                <p class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 5. MONTO — Solo para observaciones que aplican a TODA la empresa --}}
                        @if($nuevaIncidencia['tipo'] === 'observacion' && $nuevaIncidencia['aplica_empresa'])
                        <div>
                            <label class="text-[9px] font-black uppercase text-gray-500 tracking-widest block mb-1.5">
                                4. MONTO DE LA OBSERVACIÓN (opcional)
                            </label>
                            <div class="relative w-48">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-sm">$</span>
                                <input type="number" wire:model.defer="nuevaIncidencia.monto" placeholder="0" min="0"
                                       class="w-full text-right text-[11px] font-black bg-white dark:bg-gray-900 border-2 border-gray-200 focus:border-blue-500 focus:ring-0 rounded-lg py-2 pr-3 pl-8">
                            </div>
                        </div>
                        @endif

                        {{-- 6. ALCANCE --}}
                        <div>
                            <label class="text-[9px] font-black uppercase text-gray-500 tracking-widest block mb-2">
                                {{ $nuevaIncidencia['tipo'] === 'contingencia' ? '6.' : '5.' }}
                                @if($nuevaIncidencia['tipo'] === 'observacion')
                                    ALCANCE DE LA OBSERVACIÓN
                                @else
                                    TRABAJADORES AFECTADOS <span class="text-red-500">*</span>
                                @endif
                            </label>

                            @if($nuevaIncidencia['tipo'] === 'observacion')
                                {{-- Toggle empresa vs trabajadores --}}
                                <div class="flex gap-3 mb-3">
                                    <label class="flex items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all flex-1 {{ $nuevaIncidencia['aplica_empresa'] ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-gray-50 hover:border-gray-300' }}">
                                        <input type="radio" wire:model.live="nuevaIncidencia.aplica_empresa" value="1"
                                               class="text-blue-600 focus:ring-blue-500">
                                        <div>
                                            <div class="text-[11px] font-black text-gray-800">🏢 Toda la empresa</div>
                                            <div class="text-[8px] text-gray-500">Se genera un solo código</div>
                                        </div>
                                    </label>
                                    <label class="flex items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all flex-1 {{ !$nuevaIncidencia['aplica_empresa'] ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 bg-gray-50 hover:border-gray-300' }}">
                                        <input type="radio" wire:model.live="nuevaIncidencia.aplica_empresa" value="0"
                                               class="text-indigo-600 focus:ring-indigo-500">
                                        <div>
                                            <div class="text-[11px] font-black text-gray-800">👤 Trabajadores específicos</div>
                                            <div class="text-[8px] text-gray-500">Un código por trabajador</div>
                                        </div>
                                    </label>
                                </div>
                            @endif

                            {{-- Lista de trabajadores con monto individual por fila --}}
                            @if(!$nuevaIncidencia['aplica_empresa'] || $nuevaIncidencia['tipo'] === 'contingencia')
                                
                                {{-- CARGA POR CUADRO DE CODIFICACIÓN --}}
                                <div class="mb-4 bg-emerald-50 border-2 border-emerald-200 rounded-xl p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="text-[9px] font-black text-emerald-800 uppercase tracking-widest flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            CARGA DESDE CUADRO DE CODIFICACIÓN (EXCEL)
                                        </label>
                                        <button wire:click="procesarCodificacion" type="button" class="bg-emerald-600 hover:bg-emerald-700 text-white text-[9px] font-black px-3 py-1.5 rounded-lg shadow-sm transition-all uppercase flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            PROCESAR CODIFICACIÓN
                                        </button>
                                    </div>
                                    @if(session()->has('success_codificacion'))
                                        <div class="mb-2 text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-1 rounded">{{ session('success_codificacion') }}</div>
                                    @endif
                                    @if(session()->has('error_codificacion'))
                                        <div class="mb-2 text-[10px] font-bold text-red-700 bg-red-100 px-2 py-1 rounded">{{ session('error_codificacion') }}</div>
                                    @endif
                                    <textarea wire:model.defer="textoCodificacion" rows="3" class="w-full text-[10px] font-mono p-2 rounded border border-emerald-300 focus:ring-emerald-500 focus:border-emerald-500 bg-white" placeholder="Pegue aquí el contenido: RUT,PATERNO,MATERNO,NOMBRES,VALOR|..."></textarea>
                                </div>

                                <div class="border-2 border-gray-200 rounded-xl overflow-hidden">
                                    {{-- Encabezado de la tabla --}}
                                    <div class="bg-gray-50 px-3 py-1.5 border-b border-gray-200 grid grid-cols-12 gap-2 items-center">
                                        <div class="col-span-1"></div>
                                        <div class="col-span-7 text-[8px] font-black uppercase text-gray-500 tracking-widest">Trabajador</div>
                                        <div class="col-span-4 text-[8px] font-black uppercase text-blue-600 tracking-widest text-right pr-2">Monto individual</div>
                                    </div>
                                    <div class="max-h-60 overflow-y-auto divide-y divide-gray-100">
                                        @forelse($trabajadoresPeriodo as $vt)
                                            @php
                                                $t          = $vt->vinculacion->trabajador ?? null;
                                                $isSelected = in_array((string)$vt->id, array_map('strval', $nuevaIncidencia['trabajadores_ids'] ?? []));
                                            @endphp
                                            <div class="grid grid-cols-12 gap-2 items-center px-3 py-2 {{ $isSelected ? 'bg-blue-50' : 'bg-white hover:bg-gray-50' }} transition-colors">
                                                {{-- Checkbox --}}
                                                <div class="col-span-1 flex items-center justify-center">
                                                    <input type="checkbox"
                                                           wire:model.live="nuevaIncidencia.trabajadores_ids"
                                                           value="{{ $vt->id }}"
                                                           class="w-4 h-4 rounded text-blue-600 border-gray-300 focus:ring-blue-500">
                                                </div>
                                                {{-- Nombre + RUT --}}
                                                <div class="col-span-7 min-w-0">
                                                    <div class="text-[10px] font-black text-gray-800 uppercase truncate">{{ $t?->nombre_completo ?? '-' }}</div>
                                                    <div class="text-[8px] font-mono text-gray-400">{{ $t?->rut ?? '-' }}</div>
                                                </div>
                                                {{-- Campo monto individual --}}
                                                <div class="col-span-4 flex items-center gap-1 justify-end">
                                                    <span class="text-gray-400 text-xs font-bold shrink-0">$</span>
                                                    <input type="number"
                                                           wire:model.defer="nuevaIncidencia.montos_trabajadores.{{ $vt->id }}"
                                                           placeholder="0"
                                                           min="0"
                                                           {{ !$isSelected ? 'disabled' : '' }}
                                                           class="w-full text-right text-[10px] font-mono font-bold rounded-lg py-1 px-2 outline-none transition-all
                                                                  {{ $isSelected
                                                                      ? 'border-2 border-blue-300 bg-white focus:border-blue-500'
                                                                      : 'border border-gray-100 bg-gray-50 text-gray-300 cursor-not-allowed' }}">
                                                </div>
                                            </div>
                                        @empty
                                            <div class="px-3 py-4 text-center text-gray-400 text-[10px] italic">Sin trabajadores en este período.</div>
                                        @endforelse
                                    </div>
                                </div>

                                @if(count($nuevaIncidencia['trabajadores_ids'] ?? []) > 0)
                                    <p class="text-[9px] text-blue-600 font-black mt-1">
                                        ✓ {{ count($nuevaIncidencia['trabajadores_ids']) }} trabajador(es) —
                                        se generarán {{ count($nuevaIncidencia['trabajadores_ids']) }} código(s) independientes
                                    </p>
                                @endif
                                @error('nuevaIncidencia.trabajadores_ids')
                                    <p class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                    </div>{{-- /body --}}

                    {{-- Footer --}}
                    <div class="shrink-0 bg-gray-50 dark:bg-gray-900 px-6 py-4 border-t dark:border-gray-700 flex justify-between items-center">
                        <div class="text-[9px] text-gray-400 italic">
                            @if($nuevaIncidencia['aplica_empresa'] && $nuevaIncidencia['tipo'] === 'observacion')
                                Se generará 1 código para toda la empresa.
                            @elseif(count($nuevaIncidencia['trabajadores_ids'] ?? []) > 0)
                                @php
                                    $totalMontos = collect($nuevaIncidencia['montos_trabajadores'] ?? [])
                                        ->only(array_map('strval', $nuevaIncidencia['trabajadores_ids'] ?? []))
                                        ->sum();
                                @endphp
                                {{ count($nuevaIncidencia['trabajadores_ids']) }} código(s) —
                                Total: <span class="font-black text-rose-600">$ {{ number_format($totalMontos, 0, ',', '.') }}</span>
                            @else
                                Seleccione los trabajadores afectados e ingrese el monto de cada uno.
                            @endif
                        </div>
                        <div class="flex gap-3">
                            <button wire:click="cerrarModalNuevaIncidencia"
                                    class="text-[10px] font-black text-gray-500 hover:text-gray-800 uppercase px-4 py-2 transition-colors">
                                CANCELAR
                            </button>
                            <button wire:click="guardarNuevaIncidencia"
                                    class="bg-[#1a3560] hover:bg-indigo-900 text-white text-[11px] font-black px-8 py-3 rounded-xl transition-all shadow-lg uppercase flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                AGREGAR INCIDENCIA
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    @endif

{{-- ================================================================ --}}
{{-- MODAL SECUNDARIO: CATÁLOGO DE TEXTOS (z-90)                      --}}
{{-- ================================================================ --}}
@if($showModalCatalogo)
    @php
        $tipoLabel    = $nuevaIncidencia['tipo'] === 'observacion' ? 'OBSERVACIONES' : 'CONTINGENCIAS';
        $colorHeader  = $nuevaIncidencia['tipo'] === 'observacion' ? 'bg-[#1a3560]' : 'bg-[#a21caf]';
        $colorHover   = $nuevaIncidencia['tipo'] === 'observacion' ? 'hover:bg-blue-50 hover:border-blue-300' : 'hover:bg-purple-50 hover:border-purple-300';
        $colorActive  = $nuevaIncidencia['tipo'] === 'observacion' ? 'bg-blue-50 border-blue-400' : 'bg-purple-50 border-purple-400';
        $catalogoModal = $nuevaIncidencia['tipo'] === 'observacion' ? $catalogoObservaciones : $catalogoContingencias;
    @endphp
    <div class="fixed inset-0 z-[90] overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="cerrarModalCatalogo"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-3xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh] border-4 border-white/20">

                {{-- Header --}}
                <div class="{{ $colorHeader }} text-white px-6 py-4 shrink-0">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-[8px] font-black uppercase tracking-widest text-white/60">CATÁLOGO DE TEXTOS</div>
                            <h3 class="text-sm font-black uppercase tracking-tight mt-0.5">
                                {{ $tipoLabel }}
                                <span class="text-white/50 font-normal text-xs ml-2">— {{ count($catalogoModal) }} ítems disponibles</span>
                            </h3>
                            <p class="text-[9px] text-white/60 mt-0.5 font-medium">
                                Haga click en un ítem para seleccionarlo. El texto se podrá editar después.
                            </p>
                        </div>
                        <button wire:click="cerrarModalCatalogo"
                                class="bg-white/10 hover:bg-white/20 text-white font-black text-xs uppercase px-4 py-2 rounded-xl transition-all ring-1 ring-white/20 shrink-0 ml-4">
                            CERRAR
                        </button>
                    </div>
                </div>

                {{-- Lista de ítems --}}
                <div class="overflow-y-auto flex-1 p-4 space-y-2 bg-gray-50 dark:bg-gray-900">
                    @if(empty($catalogoModal))
                        <div class="text-center py-16 text-gray-400 text-[11px] italic uppercase tracking-widest">
                            No hay ítems en el catálogo de {{ strtolower($tipoLabel) }}.
                        </div>
                    @else
                        @foreach($catalogoModal as $item)
                            @php $isSelected = (int)($nuevaIncidencia['catalogo_item_id'] ?? 0) === (int)$item['id']; @endphp
                            <div wire:click="seleccionarItemCatalogo({{ $item['id'] }})"
                                 class="flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all
                                        {{ $isSelected
                                            ? $colorActive . ' shadow-sm'
                                            : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 ' . $colorHover
                                        }}">

                                {{-- Indicador seleccionado --}}
                                <div class="shrink-0 mt-0.5">
                                    @if($isSelected)
                                        <div class="w-5 h-5 rounded-full bg-blue-500 flex items-center justify-center">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                    @else
                                        <div class="w-5 h-5 rounded-full border-2 border-gray-300"></div>
                                    @endif
                                </div>

                                {{-- Texto completo --}}
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] font-{{ $isSelected ? 'black' : 'medium' }} text-gray-800 dark:text-gray-200 leading-relaxed">
                                        {{ $item['texto'] }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                {{-- Footer --}}
                <div class="shrink-0 bg-white dark:bg-gray-800 px-6 py-4 border-t dark:border-gray-700 flex justify-between items-center">
                    <div class="text-[9px] text-gray-400 italic">
                        @if($nuevaIncidencia['catalogo_item_id'])
                            ✓ Ítem seleccionado — puede editar el texto en el formulario.
                        @else
                            Haga click en un ítem para seleccionarlo.
                        @endif
                    </div>
                    <button wire:click="cerrarModalCatalogo"
                            class="{{ $colorHeader }} hover:opacity-90 text-white text-[11px] font-black px-8 py-3 rounded-xl transition-all shadow-lg uppercase">
                        @if($nuevaIncidencia['catalogo_item_id'])
                            ✓ USAR ESTE TEXTO
                        @else
                            CANCELAR
                        @endif
                    </button>
                </div>

            </div>
        </div>
    </div>
@endif

    {{-- ================================================================ --}}
    {{-- MODAL: MOTIVO DE DEVOLUCIÓN AL ANALISTA                           --}}
    {{-- ================================================================ --}}
    @if($showModalRechazo)
        <div class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/80 backdrop-blur-md" wire:click="cerrarModalRechazo"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col border-4 border-red-600/30">
                    
                    {{-- Header --}}
                    <div class="bg-red-600 text-white px-6 py-4 shrink-0">
                        <h3 class="text-lg font-black uppercase tracking-tight">DEVOLVER AL ANALISTA</h3>
                        <p class="text-[10px] text-red-100 font-bold uppercase mt-1">Indique el motivo de la devolución (Obligatorio)</p>
                    </div>

                    {{-- Body --}}
                    <div class="p-6 space-y-4">
                        <div class="bg-amber-50 border-l-4 border-amber-400 p-3 mb-4">
                            <p class="text-[10px] text-amber-800 font-bold leading-tight uppercase">
                                ⚠️ ATENCIÓN: Esta acción borrará todas las observaciones y contingencias registradas.
                            </p>
                        </div>

                        <div>
                            <label class="text-[9px] font-black uppercase text-gray-500 tracking-widest block mb-1.5">MOTIVO DE DEVOLUCIÓN</label>
                            <textarea wire:model.defer="motivoRechazo" 
                                      rows="5" 
                                      placeholder="Escriba aquí el motivo detallado para que el analista pueda corregir..."
                                      class="w-full text-xs font-bold p-3 rounded-xl border-2 border-gray-200 focus:border-red-500 focus:ring-0 dark:bg-gray-900 dark:text-white resize-none leading-relaxed"></textarea>
                            @error('motivoRechazo')
                                <p class="text-red-500 text-[9px] font-black mt-1 uppercase">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-t dark:border-gray-700 flex justify-between items-center">
                        <button wire:click="cerrarModalRechazo" 
                                class="text-[10px] font-black text-gray-500 hover:text-gray-800 uppercase px-4 py-2 transition-colors">
                            CANCELAR
                        </button>
                        <button wire:click="rechazarAuditoria" 
                                class="bg-red-600 hover:bg-black text-white text-[11px] font-black px-8 py-3 rounded-xl transition-all shadow-lg uppercase">
                            CONFIRMAR DEVOLUCIÓN
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
