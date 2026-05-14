<div class="p-4 bg-gray-100 dark:bg-gray-900 min-h-screen">
    
    <!-- TITULO -->
    <div class="mb-4">
        <h1 class="text-xl font-bold text-gray-700 dark:text-white uppercase tracking-tighter">
            REV. COMPLEMENTARIOS
        </h1>
        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-black">
            Revisión y cierre de incidencias subsanadas por el contratista
        </p>
    </div>

    <!-- MENSAJES -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4 text-sm font-bold flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="bg-amber-100 border border-amber-400 text-amber-700 px-4 py-2 rounded mb-4 text-sm font-bold flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            {{ session('warning') }}
        </div>
    @endif

    <!-- CONTADORES -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <button wire:click="$set('estado', 'EN_REVISION')" class="group relative overflow-hidden bg-blue-600 p-4 rounded-xl shadow-lg transition-all {{ $estado === 'EN_REVISION' ? 'ring-4 ring-blue-300' : 'hover:scale-[1.02]' }}">
            <div class="relative z-10 flex justify-between items-center">
                <div class="text-left">
                    <div class="text-3xl font-black text-white leading-none">{{ $contPending }}</div>
                    <div class="text-[10px] font-black text-blue-100 uppercase tracking-widest mt-1">Pendientes Revisión</div>
                </div>
                <div class="bg-white/20 p-2 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
            </div>
            <div class="absolute -right-2 -bottom-2 text-white/10 group-hover:scale-110 transition-transform">
                <svg class="w-20 h-20" fill="currentColor" viewBox="0 0 24 24"><path d="M19 5H5v14h14V5zM5 3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2H5z"/></svg>
            </div>
        </button>

        <button wire:click="$set('estado', 'REVISADOS')" class="group relative overflow-hidden bg-amber-600 p-4 rounded-xl shadow-lg transition-all {{ $estado === 'REVISADOS' ? 'ring-4 ring-amber-300' : 'hover:scale-[1.02]' }}">
            <div class="relative z-10 flex justify-between items-center">
                <div class="text-left">
                    <div class="text-3xl font-black text-white leading-none">{{ $contClosed }}</div>
                    <div class="text-[10px] font-black text-amber-100 uppercase tracking-widest mt-1">Revisados</div>
                </div>
                <div class="bg-white/20 p-2 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="absolute -right-2 -bottom-2 text-white/10 group-hover:scale-110 transition-transform">
                <svg class="w-20 h-20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>
            </div>
        </button>
    </div>

    <!-- FILTROS AVANZADOS -->
    <div class="bg-[#1a3560] p-4 rounded-lg shadow-lg mb-4 border-b-4 border-blue-400">
        <div class="text-white text-[10px] font-black uppercase mb-3 border-b border-white/20 pb-2 tracking-widest flex items-center justify-between">
            <span>FILTROS DE BUSQUEDA AVANZADA</span>
            <button wire:click="$set('mandante_id', ''); $set('contratista_id', ''); $set('dependencia_id', ''); $set('numero_contrato', ''); $set('searchFolio', ''); $set('anio', ''); $set('mes', ''); $set('tipo_item', '');" class="text-blue-300 hover:text-white transition-colors">LIMPIAR TODO</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-9 gap-3">
            
            <!-- BUSCADOR FOLIO -->
            <div class="md:col-span-2 lg:col-span-2">
                <label class="text-blue-200 text-[9px] font-black uppercase block mb-1">ID / RUT / Nombre / Folio / Código</label>
                <div class="relative">
                    <input type="text" wire:model.live.debounce.500ms="searchFolio" placeholder="Reg: 1010, RUT o Nombre..." class="w-full text-[11px] pl-8 pr-2 py-1.5 rounded border-0 bg-white dark:bg-gray-800 dark:text-white font-bold">
                    <div class="absolute left-2.5 top-2 text-gray-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>
            </div>

            <!-- PRINCIPAL (MANDANTE) -->
            <div class="lg:col-span-1">
                <label class="text-blue-200 text-[9px] font-black uppercase block mb-1">Principal</label>
                <select wire:model.live="mandante_id" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-800 dark:text-white font-bold">
                    <option value="">-- Todos --</option>
                    @foreach($mandantes as $m)
                        <option value="{{ $m->id }}">{{ $m->razon_social }}</option>
                    @endforeach
                </select>
            </div>

            <!-- CONTRATISTA -->
            <div class="lg:col-span-1">
                <label class="text-blue-200 text-[9px] font-black uppercase block mb-1">Contratista</label>
                <select wire:model.live="contratista_id" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-800 dark:text-white font-bold" {{ !$mandante_id ? 'disabled' : '' }}>
                    <option value="">-- Todos --</option>
                    @foreach($contratistas as $c)
                        <option value="{{ $c->id }}">{{ $c->razon_social }}</option>
                    @endforeach
                </select>
            </div>

            <!-- LUGAR -->
            <div class="lg:col-span-1">
                <label class="text-blue-200 text-[9px] font-black uppercase block mb-1">Lugar</label>
                <select wire:model.live="dependencia_id" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-800 dark:text-white font-bold" {{ !$mandante_id ? 'disabled' : '' }}>
                    <option value="">-- Todos --</option>
                    @foreach($dependencias as $dep)
                        <option value="{{ $dep->id }}">{{ $dep->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <!-- N CONTRATO -->
            <div class="lg:col-span-1">
                <label class="text-blue-200 text-[9px] font-black uppercase block mb-1">Contrato</label>
                <select wire:model.live="numero_contrato" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-800 dark:text-white font-bold" {{ !$contratista_id ? 'disabled' : '' }}>
                    <option value="">-- Todos --</option>
                    @foreach($contratos as $nc)
                        <option value="{{ $nc }}">{{ $nc }}</option>
                    @endforeach
                </select>
            </div>

            <!-- TIPO INCIDENCIA -->
            <div class="lg:col-span-1">
                <label class="text-blue-200 text-[9px] font-black uppercase block mb-1">Tipo Ítem</label>
                <select wire:model.live="tipo_item" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-800 dark:text-white font-bold border-l-4 border-amber-400">
                    <option value="">-- Todos --</option>
                    <option value="OBS">Observación</option>
                    <option value="CONT-RET">Cont. Retenible</option>
                    <option value="CONT-NRET">Cont. No Retenible</option>
                </select>
            </div>

            <!-- PERIODO -->
            <div class="lg:col-span-1 flex gap-1">
                <div class="flex-1">
                    <label class="text-blue-200 text-[9px] font-black uppercase block mb-1">Año</label>
                    <select wire:model.live="anio" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-800 dark:text-white font-bold">
                        <option value="">--</option>
                        @for($y = date('Y'); $y >= 2024; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="flex-1">
                    <label class="text-blue-200 text-[9px] font-black uppercase block mb-1">Mes</label>
                    <select wire:model.live="mes" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-800 dark:text-white font-bold">
                        <option value="">--</option>
                        @foreach(['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'] as $idx => $mNombre)
                            <option value="{{ $idx + 1 }}">{{ $mNombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- ESTADO -->
            <div class="lg:col-span-1">
                <label class="text-blue-200 text-[9px] font-black uppercase block mb-1">Estado</label>
                <select wire:model.live="estado" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-800 dark:text-white font-bold">
                    <option value="TODOS">Todos</option>
                    <option value="EN_REVISION">Revisión</option>
                    <option value="REVISADOS">Revisados</option>
                </select>
            </div>
        </div>
    </div>

    <!-- LISTA DE SOLICITUDES -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden border dark:border-gray-700">
        <div class="bg-gray-900 text-white px-4 py-3 flex justify-between items-center border-b border-gray-700">
            <h2 class="text-xs font-black uppercase tracking-widest flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                Panel de Complementarios ({{ $certificados->total() }})
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-3 py-3 text-center text-[9px] font-black text-gray-500 uppercase w-16">Período</th>
                        <th class="px-3 py-3 text-left text-[9px] font-black text-gray-500 uppercase w-36">Folio Certificado</th>
                        <th class="px-3 py-3 text-left text-[9px] font-black text-gray-500 uppercase">Empresa / Principal / Lugar</th>
                        <th class="px-3 py-3 text-left text-[9px] font-black text-gray-500 uppercase">Solicitudes Complementarias</th>
                        <th class="px-3 py-3 text-center text-[9px] font-black text-gray-500 uppercase w-24">Estado Global</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($certificados as $cert)
                        @php
                            $mesLabels = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
                            $mesShort  = strtoupper($mesLabels[($cert->mes ?? 1) - 1]);

                            $allItems       = $cert->solicitudesComplementarias->flatMap(fn($sc) => $sc->items);
                            $totalItems     = $allItems->count();
                            $totalResueltos = $allItems->where('estado_auditor', 'TOTAL')->count();
                            $hayActivas     = $cert->solicitudesComplementarias->whereIn('estado', ['ENVIADO', 'EN_REVISION'])->count() > 0;

                            $borderColor = $hayActivas ? 'border-blue-500' : ($totalResueltos === $totalItems && $totalItems > 0 ? 'border-emerald-500' : 'border-amber-500');
                        @endphp
                        <tr class="hover:bg-blue-50/50 transition-colors border-l-4 {{ $borderColor }} align-top">
                            <td class="px-3 py-4 text-center">
                                <span class="bg-indigo-50 text-indigo-700 px-2 py-1.5 rounded font-black text-[9px] border border-indigo-200 shadow-sm block w-14 mx-auto">
                                    {{ $mesShort }}<br>{{ $cert->anio }}
                                </span>
                            </td>
                            <td class="px-3 py-4">
                                <span class="bg-gray-900 text-yellow-400 px-2 py-1 rounded font-mono font-black text-[10px] border border-gray-700 shadow-inner block w-fit">
                                    {{ $cert->folio }}
                                </span>
                                <div class="text-[8px] text-gray-400 font-bold mt-1 uppercase tracking-tighter">
                                    {{ $cert->solicitudesComplementarias->count() }} SC(s)
                                </div>
                            </td>
                            <td class="px-3 py-4">
                                <div class="text-[10px] font-black text-gray-800 uppercase leading-tight">{{ $cert->vinculacion->contratista->razon_social ?? '-' }}</div>
                                <div class="text-[8px] text-gray-400 font-bold uppercase mt-1">{{ $cert->vinculacion->unidadOrganizacional->mandante->razon_social ?? '-' }}</div>
                                <div class="text-[8px] text-blue-500 font-black uppercase mt-0.5">📍 {{ $cert->vinculacion->dependencia->nombre ?? '-' }}</div>
                            </td>
                            <!-- HISTORIAL DE COMPLEMENTARIOS -->
                            <td class="px-3 py-3">
                                <div class="space-y-2">
                                    @foreach($cert->solicitudesComplementarias as $sc)
                                        @php
                                            $scEstadoClass = match($sc->estado) {
                                                'ENVIADO'     => 'bg-amber-100 text-amber-700 border-amber-300',
                                                'EN_REVISION' => 'bg-blue-100 text-blue-700 border-blue-300',
                                                'SOLUCIONADO' => 'bg-emerald-100 text-emerald-700 border-emerald-300',
                                                'RECHAZADO'   => 'bg-rose-100 text-rose-700 border-rose-300',
                                                default       => 'bg-gray-100 text-gray-500 border-gray-300',
                                            };
                                            $scEstadoLabel = match($sc->estado) {
                                                'RECHAZADO'   => 'REVISADO',
                                                default       => str_replace('_', ' ', $sc->estado),
                                            };
                                            $btnLabel = in_array($sc->estado, ['SOLUCIONADO', 'RECHAZADO', 'EMITIDO']) ? 'Ver' : 'Revisar';
                                        @endphp
                                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-2.5 flex items-start gap-3">
                                            <div class="flex-shrink-0 flex flex-col gap-1 min-w-[130px]">
                                                <span class="bg-gray-900 text-yellow-500 font-mono font-black text-[9px] px-1.5 py-0.5 rounded border border-gray-700">{{ $sc->folio }}</span>
                                                <span class="text-[8px] font-black uppercase px-1.5 py-0.5 rounded border {{ $scEstadoClass }} w-fit">
                                                    {{ $scEstadoLabel }}
                                                </span>
                                                @if($sc->auditor)
                                                    <span class="text-[8px] text-blue-600 font-black uppercase">👤 {{ Str::limit($sc->auditor->name, 15) }}</span>
                                                @else
                                                    <span class="text-[8px] text-amber-600 font-black uppercase">⚠️ Sin auditor</span>
                                                @endif
                                            </div>
                                            <div class="flex flex-wrap gap-1 flex-1">
                                                @foreach($sc->items as $item)
                                                    @php
                                                        $est = $item->estado_auditor ?? 'PENDIENTE';
                                                        $codClass = match($est) {
                                                            'TOTAL'     => 'bg-emerald-600 text-white',
                                                            'PARCIAL'   => 'bg-blue-600 text-white',
                                                            'RECHAZADO' => 'bg-rose-600 text-white',
                                                            default     => 'bg-gray-700 text-gray-200',
                                                        };
                                                        $codIcon = match($est) { 'TOTAL' => '✅', 'PARCIAL' => '🔵', 'RECHAZADO' => '🔴', default => '⚪' };
                                                    @endphp
                                                    <span class="text-[8px] font-black px-1.5 py-0.5 rounded {{ $codClass }}" title="{{ $est }}">
                                                        {{ $codIcon }} {{ $item->contingencia->codigo ?? 'S/C' }}
                                                    </span>
                                                @endforeach
                                            </div>
                                            <div class="flex-shrink-0">
                                                <button wire:click="verDetalle({{ $sc->id }})" 
                                                        class="bg-[#003a5c] hover:bg-black text-white text-[8px] font-black px-3 py-1.5 rounded flex items-center gap-1 transition-all active:scale-95 uppercase">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                    {{ $btnLabel }}
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <!-- ESTADO GLOBAL -->
                            <td class="px-3 py-4 text-center align-middle">
                                @if($totalItems > 0)
                                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                                        <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ round(($totalResueltos/$totalItems)*100) }}%"></div>
                                    </div>
                                    <div class="text-[9px] font-black text-gray-600 mt-1">{{ $totalResueltos }}/{{ $totalItems }} TOTAL</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-gray-400 text-[11px] font-black uppercase tracking-widest italic">
                                No se encontraron solicitudes que coincidan con los filtros
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700">
            {{ $certificados->links() }}
        </div>
    </div>

    <!-- MODAL DE REVISIÓN -->
    @if($solicitud_detalle_id && $solicitudDetalle)
        @php
            $esSoloLectura = in_array($solicitudDetalle->estado, ['SOLUCIONADO', 'RECHAZADO', 'EMITIDO']);
        @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity shadow-2xl" wire:click="cerrarDetalle"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col border-4 border-[#003a5c]">
                    
                    <!-- Header Modal -->
                    <div class="bg-gradient-to-r from-[#003a5c] to-[#005c8a] text-white px-6 py-4 flex justify-between items-center flex-shrink-0 shadow-lg border-b border-white/10">
                        <div class="flex items-center gap-4">
                            <div class="bg-white/10 p-2.5 rounded-xl border border-white/20 shadow-inner">
                                <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                            </div>
                            <div class="flex flex-col">
                                <h2 class="text-[17px] font-black tracking-tight leading-none mb-1 flex items-center gap-2 uppercase">
                                    REVISIÓN SOLICITUD <span class="text-yellow-400 font-mono tracking-tighter">{{ $solicitudDetalle->folio }}</span>
                                </h2>
                                <div class="flex flex-col gap-1 text-[10px] font-black text-blue-100 tracking-wider uppercase opacity-90 mt-1">
                                    <div class="flex items-center gap-2.5">
                                        <span>EMPRESA: {{ $solicitudDetalle->vinculacion->contratista->razon_social ?? '-' }}</span>
                                        <span class="text-white/40">|</span>
                                        <span>PRINCIPAL: {{ $solicitudDetalle->vinculacion->unidadOrganizacional->mandante->razon_social ?? '-' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2.5">
                                        <span>📍 LUGAR: {{ $solicitudDetalle->vinculacion->dependencia->nombre ?? '-' }}</span>
                                        <span class="text-white/40">|</span>
                                        <span>CT: {{ $solicitudDetalle->vinculacion->numero_contrato ?? 'S/C' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2.5 text-yellow-200">
                                        <span>CERT. ORIGEN: {{ $solicitudDetalle->carpeta->folio ?? '-' }}</span>
                                        <span class="text-white/40">|</span>
                                        <span>PERIODO: {{ isset($solicitudDetalle->carpeta) ? strtoupper($solicitudDetalle->carpeta->nombre_mes) . ' ' . $solicitudDetalle->carpeta->anio : '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button wire:click="cerrarDetalle" class="hover:bg-white/20 p-2 rounded-xl transition-all hover:rotate-90 duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="overflow-y-auto p-6 space-y-8 bg-gray-50/50">
                        @if(session('error_modal'))
                            <div class="bg-red-100 text-red-700 px-4 py-2 rounded-xl text-xs font-black mb-4 border-2 border-red-200 shadow-sm animate-pulse">
                                ⚠️ {{ session('error_modal') }}
                            </div>
                        @endif

                        <!-- TABLA DE CÓDIGOS CON DECISIONES -->
                        <div class="bg-white p-5 rounded-2xl border-2 border-gray-100 shadow-xl">
                            <div class="overflow-hidden rounded-xl border border-gray-200 shadow-sm mt-2">
                                <table class="w-full text-[10px]">
                                    <thead class="bg-gray-100 text-gray-700 font-black uppercase text-center">
                                        <tr>
                                            <th class="py-2.5 px-3 text-left">Código / Trabajador</th>
                                            <th class="py-2.5 px-3 w-[450px]">ESTADO SOLUCION</th>
                                            <th class="py-2.5 px-3">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($solicitudDetalle->items as $item)
                                            @php
                                                $ic = $item->contingencia;
                                                $tr = $ic?->carpetaTrabajador?->vinculacion?->trabajador;
                                            @endphp
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="py-3 px-3">
                                                    <div class="font-black text-[12px] {{ $ic->tipo === 'observacion' ? 'text-yellow-600' : 'text-red-600' }} font-mono flex items-center flex-wrap gap-2">
                                                        <span>CÓD. {{ $ic->codigo }}</span>
                                                        @if($ic->tipo === 'observacion')
                                                            <span class="text-[9px] bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded uppercase">OBSERVACIÓN</span>
                                                        @else
                                                            <span class="text-[9px] {{ $ic->es_retenible ? 'bg-red-100 text-red-800 border border-red-200' : 'bg-amber-100 text-amber-800 border border-amber-200' }} px-1.5 py-0.5 rounded uppercase">
                                                                {{ $ic->es_retenible ? 'CONTINGENCIA RETENIBLE' : 'CONTINGENCIA NO RETENIBLE' }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="text-[9px] font-black text-gray-800 uppercase mt-1">{{ $tr?->nombre_completo ?? 'EMPRESA PRINCIPAL' }}</div>
                                                    <div class="text-[8px] text-gray-400 font-bold italic line-clamp-1 mt-0.5">{{ $ic->causal }}</div>
                                                    <div class="mt-1 text-[9px] font-black text-[#003a5c]">ORIGINAL: ${{ number_format($ic->monto, 0, ',', '.') }}</div>
                                                </td>
                                                <td class="py-3 px-3">
                                                    <div class="flex flex-col gap-3">
                                                        <div class="flex items-center gap-4">
                                                            <label class="flex items-center gap-1.5 {{ $esSoloLectura ? 'cursor-not-allowed opacity-70' : 'cursor-pointer group' }}">
                                                                <input type="radio" wire:model.live="pre_datos_items.{{ $item->id }}.estado" value="TOTAL" class="text-emerald-500 focus:ring-emerald-500" {{ $esSoloLectura ? 'disabled' : '' }}>
                                                                <span class="text-[9px] font-black uppercase {{ !$esSoloLectura ? 'group-hover:text-emerald-600' : '' }} {{ ($pre_datos_items[$item->id]['estado'] ?? '') === 'TOTAL' ? 'text-emerald-600' : 'text-gray-400' }}">SOLUCION TOTAL</span>
                                                            </label>
                                                            <label class="flex items-center gap-1.5 {{ $esSoloLectura ? 'cursor-not-allowed opacity-70' : 'cursor-pointer group' }}">
                                                                <input type="radio" wire:model.live="pre_datos_items.{{ $item->id }}.estado" value="PARCIAL" class="text-blue-500 focus:ring-blue-500" {{ $esSoloLectura ? 'disabled' : '' }}>
                                                                <span class="text-[9px] font-black uppercase {{ !$esSoloLectura ? 'group-hover:text-blue-600' : '' }} {{ ($pre_datos_items[$item->id]['estado'] ?? '') === 'PARCIAL' ? 'text-blue-600' : 'text-gray-400' }}">Solución Parcial</span>
                                                            </label>
                                                            <label class="flex items-center gap-1.5 {{ $esSoloLectura ? 'cursor-not-allowed opacity-70' : 'cursor-pointer group' }}">
                                                                <input type="radio" wire:model.live="pre_datos_items.{{ $item->id }}.estado" value="RECHAZADO" class="text-rose-500 focus:ring-rose-500" {{ $esSoloLectura ? 'disabled' : '' }}>
                                                                <span class="text-[9px] font-black uppercase {{ !$esSoloLectura ? 'group-hover:text-rose-600' : '' }} {{ ($pre_datos_items[$item->id]['estado'] ?? '') === 'RECHAZADO' ? 'text-rose-600' : 'text-gray-400' }}">NO SOLUCIONADO</span>
                                                            </label>
                                                        </div>

                                                        @if(($pre_datos_items[$item->id]['estado'] ?? '') === 'PARCIAL')
                                                            <div class="bg-blue-50 p-2.5 rounded-lg border border-blue-100 flex items-center gap-3 animate-fadeIn">
                                                                <div class="flex-1">
                                                                    <label class="text-[8px] font-black text-blue-700 uppercase block mb-1">Monto Solucionado ($)</label>
                                                                    <input type="number" wire:model.blur="pre_datos_items.{{ $item->id }}.monto_solucionado" 
                                                                           class="w-full text-[11px] px-2 py-1 rounded border-gray-200 font-black text-blue-900 {{ $esSoloLectura ? 'bg-gray-100 cursor-not-allowed' : '' }}" 
                                                                           placeholder="Ingresar valor..." {{ $esSoloLectura ? 'disabled' : '' }}>
                                                                </div>
                                                                <div class="flex-1">
                                                                    <label class="text-[8px] font-black text-gray-500 uppercase block mb-1">Saldo Pendiente</label>
                                                                    <div class="text-[11px] font-black text-rose-600 bg-white px-2 py-1 rounded border border-rose-100">
                                                                        @php 
                                                                            $mOriginal = $ic->monto;
                                                                            $mSol = (float)($pre_datos_items[$item->id]['monto_solucionado'] ?? 0);
                                                                            $saldo = $mOriginal - $mSol;
                                                                        @endphp
                                                                        ${{ number_format($saldo, 0, ',', '.') }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        <div class="relative">
                                                            <textarea wire:model.blur="pre_datos_items.{{ $item->id }}.observaciones" 
                                                                      class="w-full text-[10px] p-2 bg-gray-50 border-gray-200 rounded-lg font-bold {{ $esSoloLectura ? 'cursor-not-allowed opacity-80' : '' }}" 
                                                                      rows="1" placeholder="Comentario opcional del auditor..." {{ $esSoloLectura ? 'disabled' : '' }}></textarea>
                                                        </div>
                                                        
                                                        @if(!$esSoloLectura)
                                                            <button wire:click="guardarRevisionItem({{ $item->id }})" class="self-end text-[9px] font-black text-white bg-[#003a5c] px-3 py-1 rounded hover:bg-black uppercase transition-all">
                                                                Guardar Ítem
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="py-3 px-3 text-center">
                                                    @if(($item->estado_auditor ?? 'PENDIENTE') !== 'PENDIENTE')
                                                        <span class="bg-emerald-500 text-white px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-tighter">LISTO</span>
                                                    @else
                                                        <span class="bg-gray-200 text-gray-500 px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-tighter border border-gray-300">PTE.</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- EVIDENCIA CONSOLIDADA -->
                        <div class="bg-white p-5 rounded-2xl border-2 border-gray-100 shadow-xl">
                            <h3 class="text-[11px] font-black text-gray-700 uppercase mb-4 flex items-center gap-2 tracking-widest border-b border-gray-100 pb-2">
                                <span class="bg-gray-700 text-white p-1 rounded">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </span>
                                EVIDENCIA ENVIADA POR CONTRATISTA
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @php
                                    $requisitos = \App\Models\RequisitoVerificacion::orderBy('id')->get();
                                @endphp
                                @foreach($requisitos as $req)
                                    @php
                                        $docsReq = $solicitudDetalle->documentos->where('requisito_verificacion_id', $req->id);
                                    @endphp
                                    @if($docsReq->count() > 0)
                                        <div class="bg-gray-50 p-4 rounded-xl border-t-4 border-blue-400 shadow-sm group">
                                            <div class="text-[10px] font-black text-[#003a5c] uppercase mb-2 leading-tight tracking-tight">{{ $req->nombre }}</div>
                                            <div class="space-y-2">
                                                @foreach($docsReq as $doc)
                                                    <div class="flex items-center justify-between bg-white p-2.5 rounded-lg border border-gray-200 shadow-sm hover:border-blue-300 transition-all">
                                                        <div class="flex items-center gap-2 truncate">
                                                            <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"/></svg>
                                                            <span class="text-[9px] font-black text-gray-500 truncate" title="{{ $doc->nombre_original }}">{{ $doc->nombre_original }}</span>
                                                        </div>
                                                        <div class="flex gap-1 shrink-0 ml-2">
                                                            <a href="{{ route('archivo.publico', ['filePath' => $doc->path, 'name' => $doc->nombre_original]) }}" 
                                                               target="_blank" 
                                                               class="bg-[#003a5c] text-white px-3 py-1 rounded text-[8px] font-black uppercase hover:bg-black transition-all shadow-sm">
                                                                Ver PDF
                                                            </a>
                                                            <a href="{{ route('archivo.publico', ['filePath' => $doc->path, 'download' => 1, 'name' => $doc->nombre_original]) }}" 
                                                               class="bg-gray-600 text-white px-3 py-1 rounded text-[8px] font-black uppercase hover:bg-gray-800 transition-all shadow-sm">
                                                                Descargar
                                                            </a>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <!-- OBSERVACIONES GLOBALES AUDITOR -->
                        <div class="bg-amber-50 p-5 rounded-2xl border-2 border-amber-200 shadow-xl">
                            <label class="text-[10px] font-black text-amber-700 uppercase block mb-2 tracking-widest flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"/><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1H8a3 3 0 00-3 3v10a1 1 0 01-1 1H4a2 2 0 01-2-2V6z" clip-rule="evenodd"/></svg>
                                COMENTARIO FINAL DE CIERRE (FOLIO)
                            </label>
                            <textarea wire:model.defer="observaciones_auditor" class="w-full text-[11px] p-3 rounded-xl border-amber-200 focus:ring-amber-500 font-bold bg-white/80 {{ $esSoloLectura ? 'cursor-not-allowed opacity-80' : '' }}" rows="3" placeholder="Resumen del proceso de auditoría para este folio..." {{ $esSoloLectura ? 'disabled' : '' }}></textarea>
                        </div>
                    </div>

                    <div class="bg-white px-8 py-5 flex justify-between items-center border-t border-gray-200 shrink-0 shadow-[0_-4px_10px_rgba(0,0,0,0.05)]">
                        <div>
                            @if(!$esSoloLectura)
                                <div class="text-[11px] text-gray-500 font-black uppercase italic tracking-tighter">
                                    ✅ Los códigos sin procesar se asumirán automáticamente como NO SOLUCIONADOS.
                                </div>
                            @elseif(($solicitudDetalle->estado ?? '') === 'RECHAZADO')
                                <div class="text-[11px] text-rose-500 font-black uppercase italic tracking-tighter">
                                    🔴 El proceso complementario ha sido revisado previamente (Histórico).
                                </div>
                            @elseif(($solicitudDetalle->estado ?? '') === 'SOLUCIONADO')
                                <div class="text-[11px] text-emerald-500 font-black uppercase italic tracking-tighter">
                                    🟢 El proceso complementario fue aprobado y solucionado (Histórico).
                                </div>
                            @endif
                        </div>
                        <div class="flex gap-3">
                            <button wire:click="cerrarDetalle" class="bg-gray-100 hover:bg-gray-200 text-gray-500 text-[11px] font-black px-6 py-3 rounded-xl transition-all shadow-sm uppercase">
                                {{ $esSoloLectura ? 'Cerrar Vista' : 'Cancelar' }}
                            </button>
                            @if(!$esSoloLectura)
                                <button wire:click="finalizarRevisionGlobal" 
                                        class="bg-emerald-600 hover:bg-emerald-700 text-white text-[12px] font-black px-10 py-3 rounded-xl transition-all shadow-lg active:scale-95 uppercase flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    FINALIZAR Y ENVIAR CIERRE
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
