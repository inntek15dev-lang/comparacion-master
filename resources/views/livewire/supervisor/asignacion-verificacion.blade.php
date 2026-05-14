<div class="p-4 bg-gray-100 dark:bg-gray-900 min-h-screen">
    <!-- TITULO -->
    <div class="mb-4">
        <h1 class="text-xl font-bold text-gray-700 dark:text-white uppercase tracking-tighter">
            SUPERVISOR VERIF.
        </h1>
        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-black">
            Periodos enviados por contratistas pendientes de revisión
        </p>
    </div>

    <!-- PANEL SUPERIOR: Metricas globales + Resumen por Analista y Auditor -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-4">

        <!-- Tarjeta: Pendientes -->
        <div class="bg-amber-500 text-white p-4 rounded-lg shadow flex items-center gap-4">
            <div class="bg-white/20 rounded-full p-3 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="text-4xl font-black leading-none">{{ $totalPendientes }}</div>
                <div class="text-[10px] font-bold uppercase mt-1 text-white/80">Pendientes de Asignar</div>
            </div>
        </div>

        <!-- Tarjeta: Asignados -->
        <div class="bg-blue-600 text-white p-4 rounded-lg shadow flex items-center gap-4">
            <div class="bg-white/20 rounded-full p-3 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="text-4xl font-black leading-none">{{ $totalAsignados }}</div>
                <div class="text-[10px] font-bold uppercase mt-1 text-white/80">Periodos Asignados</div>
            </div>
        </div>

        <!-- Tarjeta: Carga por Analista -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden lg:col-span-1">
            <div class="bg-[#1a3560] text-white px-4 py-2 flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="text-[10px] font-black uppercase tracking-wide">Carga por Analista</span>
                <span class="ml-auto text-[9px] text-white/60 font-bold">(Periodos activos)</span>
            </div>
            @if($resumenAnalistas->isEmpty())
                <div class="px-4 py-6 text-center text-gray-400 text-[11px]">
                    Sin analistas asignados actualmente
                </div>
            @else
                <div class="overflow-y-auto" style="max-height:160px;">
                    <table class="w-full text-[10px]">
                        <thead class="sticky top-0 bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                            <tr>
                                <th class="px-3 py-1.5 text-left font-black text-gray-600 dark:text-gray-300 uppercase text-[9px]">Analista</th>
                                <th class="px-2 py-1.5 text-center font-black text-gray-600 dark:text-gray-300 uppercase text-[9px]" title="Empresas contratistas unicas asignadas">Empresas</th>
                                <th class="px-2 py-1.5 text-center font-black text-gray-600 dark:text-gray-300 uppercase text-[9px]" title="Periodos totales asignados">Periodos</th>
                                <th class="px-2 py-1.5 text-center font-black text-gray-600 dark:text-gray-300 uppercase text-[9px]" title="Suma de trabajadores vinculados en sus contratos">Dotacion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($resumenAnalistas as $fila)
                                <tr class="hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                                    <td class="px-3 py-1.5 font-bold text-gray-800 dark:text-white uppercase text-[9px] leading-tight">
                                        {{ Str::limit($fila['analista']->name ?? '-', 22) }}
                                    </td>
                                    <td class="px-2 py-1.5 text-center">
                                        <span class="bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-black px-2 py-0.5 rounded-full text-[10px]">
                                            {{ $fila['empresas'] }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-1.5 text-center">
                                        <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 font-black px-2 py-0.5 rounded-full text-[10px]">
                                            {{ $fila['carpetas'] }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-1.5 text-center">
                                        <span class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-black px-2 py-0.5 rounded-full text-[10px]">
                                            {{ $fila['dotacion_total'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-3 py-1.5 bg-gray-50 dark:bg-gray-750 border-t border-gray-200 dark:border-gray-700 text-[9px] text-gray-400 text-right font-bold">
                    {{ $resumenAnalistas->count() }} analista(s) con carga activa
                </div>
            @endif
        </div>

        <!-- Tarjeta: Carga por Auditor -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden lg:col-span-1">
            <div class="bg-[#3e1a60] text-white px-4 py-2 flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="text-[10px] font-black uppercase tracking-wide">Carga por Auditor</span>
                <span class="ml-auto text-[9px] text-white/60 font-bold">(Periodos activos)</span>
            </div>
            @if($resumenAuditores->isEmpty())
                <div class="px-4 py-6 text-center text-gray-400 text-[11px]">
                    Sin auditores asignados actualmente
                </div>
            @else
                <div class="overflow-y-auto" style="max-height:160px;">
                    <table class="w-full text-[10px]">
                        <thead class="sticky top-0 bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                            <tr>
                                <th class="px-3 py-1.5 text-left font-black text-gray-600 dark:text-gray-300 uppercase text-[9px]">Auditor</th>
                                <th class="px-2 py-1.5 text-center font-black text-gray-600 dark:text-gray-300 uppercase text-[9px]" title="Empresas contratistas unicas asignadas">Empresas</th>
                                <th class="px-2 py-1.5 text-center font-black text-gray-600 dark:text-gray-300 uppercase text-[9px]" title="Periodos totales asignados">Periodos</th>
                                <th class="px-2 py-1.5 text-center font-black text-gray-600 dark:text-gray-300 uppercase text-[9px]" title="Suma de trabajadores vinculados en sus contratos">Dotacion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($resumenAuditores as $fila)
                                <tr class="hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors">
                                    <td class="px-3 py-1.5 font-bold text-gray-800 dark:text-white uppercase text-[9px] leading-tight">
                                        {{ Str::limit($fila['auditor']->name ?? '-', 22) }}
                                    </td>
                                    <td class="px-2 py-1.5 text-center">
                                        <span class="bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-black px-2 py-0.5 rounded-full text-[10px]">
                                            {{ $fila['empresas'] }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-1.5 text-center">
                                        <span class="bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 font-black px-2 py-0.5 rounded-full text-[10px]">
                                            {{ $fila['carpetas'] }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-1.5 text-center">
                                        <span class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-black px-2 py-0.5 rounded-full text-[10px]">
                                            {{ $fila['dotacion_total'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-3 py-1.5 bg-gray-50 dark:bg-gray-750 border-t border-gray-200 dark:border-gray-700 text-[9px] text-gray-400 text-right font-bold">
                    {{ $resumenAuditores->count() }} auditor(es) con carga activa
                </div>
            @endif
        </div>

    </div>


    <!-- MENSAJES -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- PANEL DETALLE DOCUMENTOS (MODAL) -->
    @if($carpetaDetalle)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" wire:click="cerrarDetalle"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div x-data="{ showDates: localStorage.getItem('supervisor_ver_fechas') === 'true' }" 
                     class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-6xl max-h-[95vh] flex flex-col">
                    <div class="{{ $modo_edicion ? 'bg-[#004d40]' : 'bg-[#004b75]' }} text-white px-5 py-3 flex justify-between items-start flex-shrink-0 rounded-t-xl">
                        <div>
                            <h2 class="text-sm font-black uppercase tracking-tight flex items-center gap-2">
                                {{ $carpetaDetalle->vinculacion->contratista->razon_social ?? '-' }}
                                @if($modo_edicion)
                                    <span class="bg-blue-600 text-white text-[9px] px-2 py-0.5 rounded-full font-black animate-pulse">MODO EDICIÓN</span>
                            @endif
                        </h2>
                        <p class="text-[10px] text-white/70 mt-0.5">
                            <span class="font-bold">PERIODO:</span> {{ strtoupper($carpetaDetalle->nombre_mes) }} {{ $carpetaDetalle->anio }}
                            &nbsp;|&nbsp;
                            <span class="font-bold">PRINCIPAL:</span> {{ $carpetaDetalle->vinculacion->unidadOrganizacionalMandante->mandante->razon_social ?? '-' }}
                            &nbsp;|&nbsp;
                            <span class="font-bold">LUGAR:</span> {{ $carpetaDetalle->vinculacion->dependencia->nombre ?? '-' }}
                            &nbsp;|
                            <span class="font-bold">CONTRATO:</span> {{ $carpetaDetalle->vinculacion->numero_contrato ?? 'N/A' }}
                        </p>
                        <p class="text-[9px] text-white/50 mt-0.5">
                            @if($carpetaDetalle->analista) Analista: <span class="font-bold text-white/70">{{ $carpetaDetalle->analista->name }}</span> @endif
                            @if($carpetaDetalle->auditor) &nbsp;· Auditor: <span class="font-bold text-white/70">{{ $carpetaDetalle->auditor->name }}</span> @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if($carpetaDetalle->tipo_envio == 'NORMAL')
                            <span class="bg-green-500 text-white text-[9px] font-black px-2 py-1 rounded">✔ DnP</span>
                        @else
                            <span class="bg-red-500 text-white text-[9px] font-black px-2 py-1 rounded">⚠ FdP</span>
                        @endif
                        <button wire:click="cerrarDetalle" class="bg-white/20 hover:bg-white/30 text-white text-[10px] font-bold px-3 py-1.5 rounded transition-colors tooltip" title="Cerrar modal">
                            ✕ Cerrar
                        </button>
                    </div>
                </div>
                
                <div class="p-4 overflow-y-auto flex-1 bg-gray-50 dark:bg-gray-900 rounded-b-lg scrollbar-thin scrollbar-thumb-gray-400 dark:scrollbar-thumb-gray-600">
                    @if(session('success_modal'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-2 text-[11px] font-bold mb-4">
                            {{ session('success_modal') }}
                        </div>
                    @endif
                    @if(session('error_modal'))
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-2 text-[11px] font-bold mb-4">
                            {{ session('error_modal') }}
                        </div>
                    @endif
                <h3 class="text-[11px] font-black text-gray-700 dark:text-white uppercase mb-3 border-b pb-2">
                    📋 Documentación ({{ $documentosPorRequisito->count() }} / {{ $requisitosPorClasif->flatten()->count() }} cargados)
                </h3>
                @foreach($requisitosPorClasif as $clasificacion => $requisitos)
                    <div class="mb-4">
                        <div class="bg-teal-50 dark:bg-teal-900/20 border-l-4 border-teal-500 px-3 py-1.5 mb-2 rounded-r">
                            <span class="text-[10px] font-black text-teal-700 dark:text-teal-300 uppercase">🏷 {{ $clasificacion }}</span>
                            <span class="text-[9px] text-teal-500 ml-2">({{ $requisitos->filter(fn($r) => isset($documentosPorRequisito[$r->id]))->count() }}/{{ $requisitos->count() }})</span>
                        </div>
                        <div class="space-y-1.5 pl-2">
                            @foreach($requisitos as $requisito)
                                @php 
                                    $docsCargados = $documentosPorRequisito[$requisito->id] ?? collect(); 
                                    $hayCargados = $docsCargados->count() > 0;
                                @endphp
                                <div class="p-2.5 rounded border mb-2
                                    {{ $hayCargados ? 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-700' : 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-700' }}">
                                    <!-- Cabecera del Requisito -->
                                    <div class="flex items-center gap-2 mb-1">
                                        @if($hayCargados)
                                            <svg class="w-3.5 h-3.5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        @else
                                            <svg class="w-3.5 h-3.5 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        @endif
                                        <span class="text-[11px] font-bold text-gray-800 dark:text-white">{{ $requisito->nombre }}</span>
                                    </div>
                                    
                                    <!-- Lista de Archivos Cargados -->
                                    <div class="ml-5 mt-1 space-y-1">
                                        @if($hayCargados)
                                            @foreach($docsCargados as $docCargado)
                                                <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-1.5 rounded border border-gray-100 dark:border-gray-700">
                                                    <div class="text-[9px] text-gray-600 dark:text-gray-300 truncate">
                                                        · {{ $docCargado->nombre_original ?? basename($docCargado->path ?? '') }}
                                                    </div>
                                                    @if($docCargado->path)
                                                        <div class="flex gap-1.5 ml-3 shrink-0">
                                                            <a href="{{ route('archivo.publico', ['filePath' => $docCargado->path, 'name' => $docCargado->nombre_original ?? basename($docCargado->path ?? '')]) }}" target="_blank"
                                                               class="flex items-center gap-1 bg-teal-600 hover:bg-teal-700 text-white text-[9px] font-bold px-2 py-0.5 rounded">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>Ver
                                                            </a>
                                                            <a href="{{ route('archivo.publico', ['filePath' => $docCargado->path, 'download' => 1, 'name' => $docCargado->nombre_original ?? basename($docCargado->path ?? '')]) }}"
                                                               class="flex items-center gap-1 bg-gray-600 hover:bg-gray-700 text-white text-[9px] font-bold px-2 py-0.5 rounded">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>Descargar
                                                            </a>
                                                            @if($modo_edicion)
                                                                <button wire:click="eliminarDocumento({{ $docCargado->id }})"
                                                                        onclick="confirm('¿Seguro que deseas eliminar este documento?') || event.stopImmediatePropagation()"
                                                                        class="flex items-center gap-1 bg-red-600 hover:bg-red-700 text-white text-[9px] font-bold px-2 py-0.5 rounded">
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg> Eliminar
                                                                </button>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="text-[9px] text-red-400 italic">Sin documento</div>
                                        @endif

                                        @if($modo_edicion)
                                            <div class="mt-2 bg-white dark:bg-gray-800 p-2 rounded border border-blue-200 dark:border-blue-800 flex items-center justify-between">
                                                <input type="file" wire:model="archivos.{{ $requisito->id }}" multiple accept=".pdf" class="text-[9px] text-gray-600 dark:text-gray-300 w-full" id="file_{{ $requisito->id }}_{{ now()->timestamp }}">
                                                <div wire:loading wire:target="archivos.{{ $requisito->id }}" class="text-[9px] text-blue-600 dark:text-blue-400 font-bold ml-2 shrink-0">
                                                    Subiendo...
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                @if($carpetaDetalle->observaciones_analista)
                    <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 rounded">
                        <p class="text-[9px] font-black text-blue-600 uppercase mb-1">📝 Obs. Analista</p>
                        <p class="text-[11px] text-gray-700 dark:text-gray-300">{{ $carpetaDetalle->observaciones_analista }}</p>
                    </div>
                @endif

                <!-- SECCIÓN: NÓMINA DE TRABAJADORES -->
                <div class="mt-6 border-t pt-4">
                    <h3 class="text-[11px] font-black text-gray-700 dark:text-white uppercase mb-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            👥 Nómina de Trabajadores ({{ $trabajadoresPeriodo->count() }})
                            <span class="text-[9px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-bold">INTERACTIVA</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button wire:click="exportarDotacion({{ $carpetaDetalle->id }})" 
                                    class="flex items-center gap-1.5 bg-green-600 hover:bg-green-700 text-white text-[9px] font-black px-2.5 py-1 rounded shadow-sm transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                EXPORTAR EXCEL
                            </button>
                        </div>
                    </h3>

                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-[10px] border-collapse">
                            <thead class="bg-gray-100 dark:bg-gray-800">
                                <tr>
                                    <th class="px-3 py-2 text-left font-black text-gray-600 dark:text-gray-300 uppercase tracking-widest text-[9px]">RUT</th>
                                    <th class="px-3 py-2 text-left font-black text-gray-600 dark:text-gray-300 uppercase tracking-widest text-[9px]">Nombre Completo</th>
                                    <th class="px-3 py-2 text-left font-black text-gray-600 dark:text-gray-300 uppercase tracking-widest text-[9px]">Cargo</th>
                                    <th class="px-2 py-2 text-center font-black text-gray-600 dark:text-gray-300 uppercase tracking-widest text-[9px]">F. Ingreso</th>
                                    <th class="px-2 py-2 text-center font-black text-gray-600 dark:text-gray-300 uppercase tracking-widest text-[9px]">F. Contrato</th>
                                    <th class="px-2 py-2 text-center font-black text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/10 uppercase tracking-widest text-[9px]">Nuevo</th>
                                    <th class="px-3 py-2 text-center font-black text-gray-600 dark:text-gray-300 uppercase tracking-widest text-[9px]" style="min-width: 200px;">Estado / Acción</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                                @php
                                    $pStart = \Carbon\Carbon::create($carpetaDetalle->anio, $carpetaDetalle->mes, 1)->startOfMonth();
                                    $pEnd   = $pStart->copy()->endOfMonth();
                                @endphp
                                @forelse($trabajadoresPeriodo as $vt)
                                    @php
                                        $vinculacion = $vt->vinculacion;
                                        $snapRut = $vt->snapshot_rut ?: ($vinculacion->trabajador->rut ?? '-');
                                        $snapNombre = $vt->snapshot_nombres ?: ($vinculacion->trabajador->nombre_completo ?? '-');
                                        $snapCargo = $vt->snapshot_cargo ?: ($vinculacion->cargoMandante->nombre_cargo ?? '-');
                                        $fiIngreso = $vt->snapshot_fecha_ingreso ?: ($vinculacion->fecha_ingreso_vinculacion ?? null);
                                        $fiContrato = $vt->snapshot_fecha_contrato ?: ($vinculacion->fecha_contrato ?? null);

                                        $destinosPosibles = ($vt->estado_revision === 'MOVIDO') ? $this->getDestinosPosibles($vt->trabajador_vinculacion_id) : collect();
                                        $esNuevo   = $fiIngreso && \Carbon\Carbon::parse($fiIngreso)->between($pStart, $pEnd);
                                    @endphp
                                    <tr class="hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-colors">
                                        <td class="px-3 py-2">
                                            <div class="flex flex-col">
                                                <span class="font-mono text-blue-600 dark:text-blue-400 text-[10px] font-bold">{{ $snapRut }}</span>
                                                @if($vt->tipo_registro === 'ARRASTRE')
                                                    <span class="text-[7px] bg-orange-100 text-orange-700 px-1 rounded w-fit font-black uppercase mt-0.5">Arrastre</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 font-black text-gray-800 dark:text-white uppercase text-[10px] leading-tight">
                                            {{ $snapNombre }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="text-gray-600 dark:text-gray-400 font-bold leading-tight uppercase text-[9px]">
                                                {{ $snapCargo }}
                                            </div>
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <span class="font-bold text-gray-600 dark:text-gray-400 text-[9px]">
                                                {{ $fiIngreso ? \Carbon\Carbon::parse($fiIngreso)->format('d/m/Y') : '-' }}
                                            </span>
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <span class="font-bold text-gray-600 dark:text-gray-400 text-[9px]">
                                                {{ $fiContrato ? \Carbon\Carbon::parse($fiContrato)->format('d/m/Y') : '-' }}
                                            </span>
                                        </td>
                                        <td class="px-2 py-2 text-center bg-emerald-50/20 dark:bg-emerald-900/10">
                                            @if($esNuevo)
                                                <span class="text-[8px] bg-emerald-100 text-emerald-700 border border-emerald-300 px-2 py-0.5 rounded-full font-black uppercase">✨ Nuevo</span>
                                            @else
                                                <span class="text-gray-300 dark:text-gray-600">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <div class="flex flex-col gap-1 items-center">
                                                @php
                                                    $estado = $vt->estado_revision;
                                                    // Semáforo de colores para el select
                                                    $selectColorCls = "bg-gray-100 text-gray-700";
                                                    if ($estado === 'PENDIENTE') $selectColorCls = "bg-amber-100 text-amber-700 border-amber-300";
                                                    elseif ($estado === 'VERIFICADO') $selectColorCls = "bg-green-100 text-green-700 border-green-300";
                                                    elseif ($estado === 'FINIQUITADO') $selectColorCls = "bg-red-100 text-red-700 border-red-300";
                                                    elseif ($estado === 'MOVIDO') $selectColorCls = "bg-blue-100 text-blue-700 border-blue-300";
                                                    elseif ($estado === 'BAJA_MANDANTE') $selectColorCls = "bg-purple-100 text-purple-700 border-purple-300";
                                                @endphp
                                                <select 
                                                    wire:change="cambiarEstadoTrabajadorPeriodo({{ $vt->id }}, $event.target.value)"
                                                    class="text-[9px] font-black uppercase rounded py-1 px-2 w-full max-w-[220px] {{ $selectColorCls }}"
                                                >
                                                    <option value="PENDIENTE" {{ $estado === 'PENDIENTE' ? 'selected' : '' }}>1. ACTIVO (PENDIENTE)</option>
                                                    <option value="VERIFICADO" {{ $estado === 'VERIFICADO' ? 'selected' : '' }}>2. VERIFICADO ✅</option>
                                                    <option value="FINIQUITADO" {{ $estado === 'FINIQUITADO' ? 'selected' : '' }}>3. FINIQUITADO</option>
                                                    <option value="MOVIDO" {{ $estado === 'MOVIDO' ? 'selected' : '' }}>4. MOVIDO A OTRA VINCULACIÓN</option>
                                                    <option value="BAJA_MANDANTE" {{ $estado === 'BAJA_MANDANTE' ? 'selected' : '' }}>5. BAJA POR PRINCIPAL</option>
                                                </select>

                                                @if($vt->estado_revision === 'MOVIDO')
                                                    @if($destinosPosibles->count() > 0)
                                                        <select 
                                                            wire:change="cambiarEstadoTrabajadorPeriodo({{ $vt->id }}, 'MOVIDO', $event.target.value)"
                                                            class="text-[9px] font-bold rounded border-blue-300 bg-blue-50 text-blue-800 py-1 px-2 w-full max-w-[220px]"
                                                        >
                                                            <option value="">-- SELECCIONAR DESTINO --</option>
                                                            @foreach($destinosPosibles as $dest)
                                                                <option value="{{ $dest->id }}" {{ $vt->destino_trabajador_vinculacion_id == $dest->id ? 'selected' : '' }}>
                                                                    {{ $dest->unidadOrganizacional->nombre_unidad ?? 'S/U' }} - {{ $dest->dependencia->nombre ?? 'S/L' }} ({{ $dest->numero_contrato ?? 'S/C' }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        <div class="text-[8px] bg-red-600 text-white font-black px-2 py-1 rounded animate-pulse uppercase">
                                                            ⚠️ TRABAJADOR NO REGISTRA OTRAS VINCULACIONES ACTIVAS
                                                        </div>
                                                    @endif
                                                @endif

                                                @if($vt->estado_revision === 'BAJA_MANDANTE')
                                                    <div class="text-[8px] bg-purple-600 text-white font-black px-2 py-1 rounded animate-pulse uppercase">
                                                        ⚠️ SUBIR RESPALDO EN "OTROS DOCUMENTOS"
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-[11px] uppercase font-bold italic">
                                            No se han cargado trabajadores para este periodo.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

    <!-- FILTROS -->
    <div class="bg-[#004b75] p-4 rounded-lg shadow mb-4">
        <div class="text-white text-[10px] font-black uppercase mb-3 border-b border-white/30 pb-2">
            🔍 FILTROS DE BÚSQUEDA
        </div>
        <div class="grid grid-cols-1 md:grid-cols-7 gap-3">
            <!-- Principal/Mandante -->
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
                        <option value="{{ $c->id }}">{{ $c->razon_social }}</option>
                    @endforeach
                </select>
            </div>

            <!-- ID_REGISTRO -->
            <div>
                <label class="text-white/70 text-[9px] font-bold uppercase block mb-1">ID_REGISTRO</label>
                <input type="text" wire:model.live="filtro_id_registro" placeholder="Buscar ID..." class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-700 dark:text-white">
            </div>

            <!-- Año -->
            <div>
                <label class="text-white/70 text-[9px] font-bold uppercase block mb-1">AÑO</label>
                <select wire:model.live="anio" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-700 dark:text-white">
                    @for($y = date('Y'); $y >= 2024; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <!-- Periodo (Ex Mes) -->
            <div>
                <label class="text-white/70 text-[9px] font-bold uppercase block mb-1">PERIODO</label>
                <select wire:model.live="mes" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-700 dark:text-white">
                    <option value="">-- Todos --</option>
                    @foreach(['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $i => $nombre)
                        <option value="{{ $i + 1 }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Rango de Envío -->
            <div class="md:col-span-2">
                <label class="text-white/70 text-[9px] font-bold uppercase block mb-1">RANGO FECHA ENVÍO</label>
                <div class="flex gap-2">
                    <input type="date" wire:model.live="fecha_envio_desde" class="w-full text-[11px] px-2 py-1 rounded border-0 bg-white dark:bg-gray-700 dark:text-white">
                    <input type="date" wire:model.live="fecha_envio_hasta" class="w-full text-[11px] px-2 py-1 rounded border-0 bg-white dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <!-- Envío (Ex Estado Plazo) -->
            <div>
                <label class="text-white/70 text-[9px] font-bold uppercase block mb-1">ENVÍO</label>
                <select wire:model.live="estado_plazo" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-700 dark:text-white">
                    <option value="">-- Todos --</option>
                    <option value="NORMAL">✓ Dentro de Plazo</option>
                    <option value="FUERA_PLAZO">⚠ Fuera de Plazo</option>
                </select>
            </div>

            <!-- Estado Revisión -->
            <div>
                <label class="text-white/70 text-[9px] font-bold uppercase block mb-1">ESTADO REVISIÓN</label>
                <select wire:model.live="estado_revision" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-700 dark:text-white">
                    <option value="">-- Todos --</option>
                    <option value="PENDIENTE_ASIGNAR">Pendiente Asignar</option>
                    <option value="ASIGNADO">Asignado</option>
                    <option value="EN_REVISION">En Revisión</option>
                    <option value="REVISADO">Revisado</option>
                    <option value="PARA_EMITIR">Para Emitir</option>
                    <option value="EMITIDO">Emitido</option>
                </select>
            </div>

            <!-- Filtro Contingencias -->
            <div>
                <label class="text-white/70 text-[9px] font-bold uppercase block mb-1">INCIDENCIAS</label>
                <select wire:model.live="filtro_contingencia" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-700 dark:text-white">
                    <option value="">-- Todas --</option>
                    <option value="OBSERVACIONES">Con Observaciones</option>
                    <option value="RETENIBLES">Contingencias Retenibles</option>
                    <option value="NO_RETENIBLES">Contingencias No Ret. (Administrativas)</option>
                </select>
            </div>

            <!-- Analista -->
            <div>
                <label class="text-white/70 text-[9px] font-bold uppercase block mb-1">ANALISTA</label>
                <select wire:model.live="analista_id" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-700 dark:text-white">
                    <option value="">-- Todos --</option>
                    @foreach($analistas as $analista)
                        <option value="{{ $analista->id }}">{{ \Illuminate\Support\Str::limit($analista->name, 20) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Auditor -->
            <div>
                <label class="text-white/70 text-[9px] font-bold uppercase block mb-1">AUDITOR</label>
                <select wire:model.live="auditor_id" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-700 dark:text-white">
                    <option value="">-- Todos --</option>
                    @foreach($auditores as $auditor)
                        <option value="{{ $auditor->id }}">{{ \Illuminate\Support\Str::limit($auditor->name, 20) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- IA -->
            <div>
                <label class="text-white/70 text-[9px] font-bold uppercase block mb-1">FILTRO IA</label>
                <select wire:model.live="filtro_ia" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-700 dark:text-white">
                    <option value="">-- Todos --</option>
                    <option value="IA_OK">🤖 IA OK (Procesado)</option>
                    <option value="IA_PENDIENTE">⌛ IA Pendiente</option>
                </select>
            </div>
        </div>

        <div class="mt-3 flex justify-end gap-2">
            <button wire:click="limpiarFiltros" class="bg-gray-500 hover:bg-gray-600 text-white text-[10px] font-bold px-4 py-1.5 rounded uppercase">
                🗑️ Limpiar Filtros
            </button>
            <button wire:click="descargarDocumentosFiltrados" wire:loading.attr="disabled" class="bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-bold px-4 py-1.5 rounded uppercase flex items-center gap-1 transition-all disabled:opacity-50">
                <svg wire:loading.remove wire:target="descargarDocumentosFiltrados" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <svg wire:loading wire:target="descargarDocumentosFiltrados" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span>Descargar Filtrados</span>
            </button>
        </div>
    </div>

    <!-- TABLA DE RESULTADOS -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="bg-[#003a5c] text-white px-4 py-2 text-[10px] font-black uppercase">
            📋 LISTADO DE PERIODOS ENVIADOS ({{ $carpetas->total() }} registros)
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase w-10">N°</th>
                        <th class="px-2 py-2 text-left text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">ID</th>
                        <th class="px-2 py-2 text-left text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Principal</th>
                        <th class="px-2 py-2 text-left text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">RUT</th>
                        <th class="px-2 py-2 text-left text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Contratista</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Lugar/Contrato</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Periodo</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Fecha Envío</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase" title="Dotación">Dotación</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Envío</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Emitido</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Analista Anterior</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Analista</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Auditor Anterior</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Auditor</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-blue-600 dark:text-blue-400 uppercase">IA</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($carpetas as $index => $carpeta)
                        @php
                            $correlativoJerarquico = $carpeta->correlativo_jerarquico ?? ($carpetas->firstItem() + $index);
                            $correlativoArray = explode('.', (string)$correlativoJerarquico);
                            $numeroBase = (int) $correlativoArray[0];

                            // ¿Este grupo tiene jerarquía?
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
                        <tr wire:key="carpeta-{{ $carpeta->id }}" class="{{ $fondoClase }} hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors border-l-4 {{ $nivel > 0 ? 'border-blue-400' : 'border-transparent' }}">
                            <td class="px-2 py-1 text-center text-[10px] {{ $nivel > 0 ? 'font-black text-blue-600' : 'text-gray-400' }}">
                                {{ $correlativoJerarquico }}
                            </td>

                            <!-- ID_REGISTRO -->
                            <td class="px-2 py-1 text-[10px] font-bold text-blue-700 dark:text-blue-400">
                                {{ $carpeta->vinculacion->id_registro ?? '-' }}
                            </td>
                            
                            <!-- PRINCIPAL -->
                            <td class="px-2 py-1 text-[10px] font-bold text-gray-700 dark:text-gray-300">
                                {{ Str::limit($carpeta->vinculacion->unidadOrganizacionalMandante->mandante->razon_social ?? '-', 20) }}
                            </td>

                            <!-- RUT -->
                            <td class="px-2 py-1 text-[10px] font-mono text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                {{ $carpeta->vinculacion->contratista->rut ?? '-' }}
                            </td>
                            
                            <!-- CONTRATISTA -->
                            <td class="px-2 py-1 text-[10px] font-bold text-gray-900 dark:text-white uppercase leading-tight {{ $indentClass }}">
                                @if($nivel > 0) <span class="text-blue-500 mr-1">└</span> @endif
                                {{ Str::limit($carpeta->vinculacion->contratista->razon_social ?? '-', 25) }}
                                <div class="text-[9px] text-gray-500 font-normal">
                                    {{ $carpeta->vinculacion->unidadOrganizacionalMandante->nombre_unidad ?? '' }}
                                </div>
                            </td>
                            
                            <!-- LUGAR (Dependencia) / CONTRATO -->
                            <td class="px-2 py-1 text-center">
                                <span class="block text-[9px] font-bold text-gray-700 dark:text-gray-300 uppercase text-xs">
                                    {{ Str::limit($carpeta->vinculacion->dependencia->nombre ?? '-', 15) }}
                                </span>
                                <span class="block text-[8px] font-mono text-blue-600 dark:text-blue-400 mt-0.5">
                                    CT: {{ $carpeta->vinculacion->numero_contrato ?? 'N/A' }}
                                </span>
                                {{-- BADGES DE INCIDENCIAS --}}
                                @if(!empty($carpeta->fin_observaciones_json) || \App\Models\CarpetaTrabajadorContingencia::whereIn('carpeta_verificacion_trabajador_id', $carpeta->trabajadoresVerificados()->pluck('id'))->exists())
                                    <div class="flex flex-wrap gap-1 justify-center mt-1.5">
                                        @if(!empty($carpeta->fin_observaciones_json))
                                            <span class="bg-blue-100 text-blue-700 border border-blue-200 text-[7px] font-black px-1.5 py-0.5 rounded uppercase">OBS</span>
                                        @endif
                                        @if(\App\Models\CarpetaTrabajadorContingencia::whereIn('carpeta_verificacion_trabajador_id', $carpeta->trabajadoresVerificados()->pluck('id'))->where('es_retenible', true)->exists())
                                            <span class="bg-red-100 text-red-700 border border-red-200 text-[7px] font-black px-1.5 py-0.5 rounded uppercase">🔒 RET</span>
                                        @elseif(\App\Models\CarpetaTrabajadorContingencia::whereIn('carpeta_verificacion_trabajador_id', $carpeta->trabajadoresVerificados()->pluck('id'))->exists())
                                            <span class="bg-amber-100 text-amber-700 border border-amber-200 text-[7px] font-black px-1.5 py-0.5 rounded uppercase">CONT</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            
                            <!-- PERIODO -->
                            <td class="px-2 py-1 text-center">
                                <span class="bg-gray-100 text-gray-700 border border-gray-300 text-[9px] font-black px-1.5 py-0.5 rounded uppercase whitespace-nowrap">
                                    {{ $getNombreMes($carpeta->mes) }} {{ $carpeta->anio }}
                                </span>
                            </td>

                            <!-- FECHA ENVÍO -->
                            <td class="px-2 py-1 text-center">
                                <span class="text-[10px] font-mono text-gray-600 dark:text-gray-300">
                                    {{ $carpeta->fecha_envio ? $carpeta->fecha_envio->format('d/m/Y') : '-' }}
                                </span>
                            </td>

                            <!-- DOTACIÓN -->
                            <td class="px-2 py-1 text-center text-[10px] font-mono text-gray-600 dark:text-gray-400">
                                {{ $carpeta->vinculacion ? $carpeta->vinculacion->trabajadores()->count() : 0 }}
                            </td>

                            <!-- ENVÍO -->
                            <td class="px-2 py-1 text-center">
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
                            </td>

                            <!-- EMITIDO -->
                            <td class="px-2 py-1 text-center">
                                @if($carpeta->estado_revision === 'EMITIDO')
                                    <span class="text-purple-700 dark:text-purple-400 text-[9px] font-black block uppercase">✓ Sí</span>
                                    <span class="text-[8px] font-mono text-purple-600 dark:text-purple-300">
                                        {{ $carpeta->fecha_emision ? $carpeta->fecha_emision->format('d/m/y H:i') : '-' }}
                                    </span>
                                @else
                                    <span class="text-gray-300 dark:text-gray-600 text-[9px] font-black uppercase">Pend.</span>
                                @endif
                            </td>

                            <!-- ANALISTA ANTERIOR -->
                            <td class="px-2 py-1 text-center">
                                @if($carpeta->historial_revision->isNotEmpty() && $carpeta->historial_revision->whereNotNull('analista_id')->count() > 0)
                                    <div class="flex flex-col gap-0.5 items-center">
                                        @foreach($carpeta->historial_revision as $historial)
                                            @if($historial->analista)
                                                <span class="text-[8px] font-bold text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 px-1 py-0.5 rounded border border-gray-200 dark:border-gray-700 whitespace-nowrap">
                                                    {{ Str::limit($historial->analista->name, 12) }} ({{ $historial->mes }}-{{ substr($historial->anio, 2) }})
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-[8px] text-gray-400 italic">Sin historial</span>
                                @endif
                            </td>

                            <!-- ANALISTA -->
                            <td class="px-2 py-1 text-center">
                                @if($carpeta->analista)
                                    <div class="flex flex-col items-center">
                                        <span class="text-blue-700 dark:text-blue-300 font-bold text-[9px] uppercase leading-tight bg-blue-50 dark:bg-blue-900/10 px-2 py-1 rounded border border-blue-100 dark:border-blue-800">
                                            {{ strtoupper(Str::limit($carpeta->analista->name, 15)) }}
                                        </span>
                                        <button wire:click="quitarAnalista({{ $carpeta->id }})" 
                                                wire:loading.attr="disabled"
                                                class="text-[8px] text-gray-400 hover:text-red-500 mt-1 uppercase font-bold tracking-tighter disabled:opacity-50">Cambiar</button>
                                    </div>
                                @else
                                    <div class="flex items-center justify-center gap-1 group">
                                        <select wire:model="analistas_seleccionados.{{ $carpeta->id }}" class="text-[10px] h-6 py-0 pl-1 pr-6 rounded border border-gray-300 bg-white focus:border-blue-500 focus:ring-0 cursor-pointer shadow-sm text-blue-700 font-bold min-w-[140px]">
                                            <option value="">Seleccione Analista...</option>
                                            @foreach($analistas as $analista)
                                                <option value="{{ $analista->id }}">{{ Str::limit($analista->name, 15) }}</option>
                                            @endforeach
                                        </select>
                                        <button wire:click="asignarAnalista({{ $carpeta->id }})"
                                                wire:loading.attr="disabled"
                                                class="bg-blue-600 hover:bg-blue-700 text-white w-6 h-6 rounded flex items-center justify-center shadow-sm transition-transform hover:scale-105 disabled:opacity-50"
                                                title="Confirmar Analista">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        </button>
                                    </div>
                                @endif
                            </td>

                            <!-- AUDITOR ANTERIOR -->
                            <td class="px-2 py-1 text-center">
                                @if($carpeta->historial_revision->isNotEmpty() && $carpeta->historial_revision->whereNotNull('auditor_id')->count() > 0)
                                    <div class="flex flex-col gap-0.5 items-center">
                                        @foreach($carpeta->historial_revision as $historial)
                                            @if($historial->auditor)
                                                <span class="text-[8px] font-bold text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 px-1 py-0.5 rounded border border-gray-200 dark:border-gray-700 whitespace-nowrap">
                                                    {{ Str::limit($historial->auditor->name, 12) }} ({{ $historial->mes }}-{{ substr($historial->anio, 2) }})
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-[8px] text-gray-400 italic">Sin historial</span>
                                @endif
                            </td>

                            <!-- AUDITOR -->
                            <td class="px-2 py-1 text-center">
                                @if($carpeta->auditor)
                                    <div class="flex flex-col items-center">
                                        <span class="text-purple-700 dark:text-purple-300 font-bold text-[9px] uppercase leading-tight bg-purple-50 dark:bg-purple-900/10 px-2 py-1 rounded border border-purple-100 dark:border-purple-800">
                                            {{ strtoupper(Str::limit($carpeta->auditor->name, 15)) }}
                                        </span>
                                        <button wire:click="quitarAuditor({{ $carpeta->id }})" 
                                                wire:loading.attr="disabled"
                                                class="text-[8px] text-gray-400 hover:text-red-500 mt-1 uppercase font-bold tracking-tighter disabled:opacity-50">Cambiar</button>
                                    </div>
                                @else
                                    <div class="flex items-center justify-center gap-1 group">
                                        <select wire:model="auditores_seleccionados.{{ $carpeta->id }}" class="text-[10px] h-6 py-0 pl-1 pr-6 rounded border border-gray-300 bg-white focus:border-purple-500 focus:ring-0 cursor-pointer shadow-sm text-purple-700 font-bold min-w-[140px]">
                                            <option value="">Seleccione Auditor...</option>
                                            @foreach($auditores as $auditor)
                                                <option value="{{ $auditor->id }}">{{ Str::limit($auditor->name, 15) }}</option>
                                            @endforeach
                                        </select>
                                        <button wire:click="asignarAuditor({{ $carpeta->id }})"
                                                wire:loading.attr="disabled"
                                                class="bg-purple-600 hover:bg-purple-700 text-white w-6 h-6 rounded flex items-center justify-center shadow-sm transition-transform hover:scale-105 disabled:opacity-50"
                                                title="Confirmar Auditor">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        </button>
                                    </div>
                                @endif
                            </td>

                            <!-- IA EXTRAIDO -->
                            <td class="px-2 py-1 text-center">
                                @if($carpeta->ia_datos_extraidos)
                                    <span class="bg-blue-600 text-white text-[8px] font-black px-1.5 py-0.5 rounded uppercase animate-pulse shadow-sm" title="Datos extraídos externamente (IA/Excel)">IA OK</span>
                                @else
                                    <span class="text-gray-300 dark:text-gray-600 font-black text-[8px]">—</span>
                                @endif
                            </td>

                            <!-- ACCIONES -->
                            <td class="px-2 py-1 text-center">
                                <div class="flex flex-col gap-1 items-center">
                                    <button wire:click="verDetalle({{ $carpeta->id }})"
                                            class="inline-flex items-center justify-center gap-1 bg-teal-600 hover:bg-teal-700 text-white text-[9px] font-black px-3 py-1 rounded shadow-sm w-full uppercase">
                                        Ver
                                    </button>
                                    @if($carpeta->estado_revision !== 'EMITIDO')
                                        @if(Auth::user()->hasAnyRole(['OVAL_Admin', 'Verifica_Supervisor', 'Verifica_Emisor', 'ASEM_Admin']) && $carpeta->estado_revision !== 'PARA_EMITIR')
                                            <button wire:click="activarEdicion({{ $carpeta->id }})"
                                                    class="inline-flex items-center justify-center gap-1 bg-blue-600 hover:bg-blue-700 text-white text-[9px] font-black px-3 py-1 rounded shadow-sm w-full uppercase"
                                                    title="Activar Modo Edición para cargar o borrar documentos chanchamente">
                                                Editar
                                            </button>
                                        @endif
                                        @if(in_array($carpeta->estado_revision, ['PENDIENTE_ASIGNAR', 'ASIGNADO', 'EN_REVISION', 'REVISADO']))
                                            <button wire:click="revertirEnvio({{ $carpeta->id }})"
                                                    onclick="confirm('¿Estás seguro de ABRIR este periodo? \n\nEl contratista podrá rectificar documentos y se eliminarán las asignaciones de Analista y Auditor.') || event.stopImmediatePropagation()"
                                                    class="inline-flex items-center justify-center gap-1 bg-orange-500 hover:bg-orange-600 text-white text-[8px] font-bold px-2 py-1 rounded shadow-sm w-full uppercase"
                                                    title="Abrir Periodo / Habilitar Rectificación">
                                                Abrir
                                            </button>
                                        @endif
                                    @endif
                                    @if(in_array($carpeta->estado_revision, ['PARA_EMITIR', 'EMITIDO']))
                                        <a href="{{ route('verificacion.certificado.visor', $carpeta->id) }}"
                                           target="_blank"
                                           class="inline-flex items-center justify-center gap-1 bg-[#1a3560] hover:bg-blue-900 text-white text-[9px] font-black px-3 py-1 rounded shadow-sm w-full uppercase"
                                           title="Previsualizar / Ver Certificado">
                                            {{ $carpeta->estado_revision === 'EMITIDO' ? 'VER CERTIFICADO' : 'VER PDF' }}
                                        </a>
                                    @endif
                                    @if($carpeta->estado_revision === 'PARA_EMITIR' && Auth::user()->hasAnyRole(['Verifica_Supervisor', 'Verifica_Emisor', 'ASEM_Admin']))
                                        <div class="flex flex-col gap-1 w-full">
                                            <button wire:click="emitirCertificado({{ $carpeta->id }})"
                                                    onclick="confirm('¿Estás seguro de EMITIR definitivamente este certificado? \n\nEsta acción es irreversible, enviará el certificado al Contratista y bloqueará permanentemente el periodo.') || event.stopImmediatePropagation()"
                                                    class="inline-flex items-center justify-center gap-1 bg-red-600 hover:bg-red-700 text-white text-[9px] font-black px-3 py-1 rounded shadow-sm w-full uppercase"
                                                    title="Emitir Certificado Final">
                                                EMITIR
                                            </button>
                                            <button wire:click="abrirModalDevolverAuditor({{ $carpeta->id }})"
                                                    class="inline-flex items-center justify-center gap-1 bg-amber-500 hover:bg-amber-600 text-white text-[9px] font-black px-3 py-1 rounded shadow-sm w-full uppercase"
                                                    title="Devolver al Auditor para corrección">
                                                DEVOLVER AL AUDITOR
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="px-4 py-12 text-center text-gray-400 dark:text-gray-600">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                    <p class="text-[11px] font-bold uppercase">No se encontraron asignaciones pendientes</p>
                                    <p class="text-[9px]">Intente ajustar los filtros de búsqueda</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-750 border-t border-gray-200 dark:border-gray-700">
            {{ $carpetas->links() }}
        </div>
    </div>

    {{-- ================================================================ --}}
    {{-- MODAL: DEVOLVER AL AUDITOR                                        --}}
    {{-- ================================================================ --}}
    @if($showModalDevolverAuditor)
        <div class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/80 backdrop-blur-md" wire:click="cerrarModalDevolverAuditor"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col border-4 border-amber-500/30">
                    
                    {{-- Header --}}
                    <div class="bg-amber-500 text-white px-6 py-4 shrink-0">
                        <h3 class="text-lg font-black uppercase tracking-tight">DEVOLVER AL AUDITOR</h3>
                        <p class="text-[10px] text-amber-100 font-bold uppercase mt-1">Indique el motivo de la devolución (Obligatorio)</p>
                    </div>

                    {{-- Body --}}
                    <div class="p-6 space-y-4">
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-3 mb-4">
                            <p class="text-[10px] text-blue-800 font-bold leading-tight uppercase">
                                ℹ️ El periodo volverá al estado "POR AUDITAR" y el auditor podrá ver su observación.
                            </p>
                        </div>

                        <div>
                            <label class="text-[9px] font-black uppercase text-gray-500 tracking-widest block mb-1.5">MOTIVO DE DEVOLUCIÓN</label>
                            <textarea wire:model.defer="motivoDevolverAuditor" 
                                      rows="5" 
                                      placeholder="Escriba aquí por qué devuelve este periodo al auditor..."
                                      class="w-full text-xs font-bold p-3 rounded-xl border-2 border-gray-200 focus:border-amber-500 focus:ring-0 dark:bg-gray-900 dark:text-white resize-none leading-relaxed"></textarea>
                            @error('motivoDevolverAuditor')
                                <p class="text-red-500 text-[9px] font-black mt-1 uppercase">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-t dark:border-gray-700 flex justify-between items-center">
                        <button wire:click="cerrarModalDevolverAuditor" 
                                class="text-[10px] font-black text-gray-500 hover:text-gray-800 uppercase px-4 py-2 transition-colors">
                            CANCELAR
                        </button>
                        <button wire:click="devolverAlAuditor" 
                                class="bg-amber-500 hover:bg-black text-white text-[11px] font-black px-8 py-3 rounded-xl transition-all shadow-lg uppercase">
                            CONFIRMAR DEVOLUCIÓN
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
