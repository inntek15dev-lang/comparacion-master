<div class="p-4 bg-gray-100 dark:bg-gray-900 min-h-screen">

    <!-- TITULO -->
    <div class="mb-4">
        <h1 class="text-xl font-bold text-gray-700 dark:text-white uppercase tracking-tighter flex items-center gap-2">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Descarga Masiva de Documentos
        </h1>
        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-black">
            Extraer documentación por Principal, Tipo de Documento y rango de fechas o periodos
        </p>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 text-sm font-bold flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4 text-sm font-bold flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            {{ session('warning') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm font-bold flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-[#004b75] text-white px-5 py-3 border-b border-blue-800">
            <h2 class="text-sm font-black uppercase tracking-wide">🔍 Parámetros de Descarga</h2>
        </div>

        <div class="p-5">
            <!-- 0. MODO DE DESCARGA -->
            <div class="mb-8 p-4 bg-blue-50 dark:bg-blue-900/10 rounded-xl border border-blue-100 dark:border-blue-800/30 flex items-center gap-6">
                <span class="text-[11px] font-black uppercase text-blue-800 dark:text-blue-400 tracking-widest">¿Qué desea descargar?</span>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="radio" wire:model.live="modo_descarga" value="documentos" class="w-4 h-4 text-blue-600 focus:ring-0 focus:ring-offset-0">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase group-hover:text-blue-600 transition-colors">Documentos de Verificación</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="radio" wire:model.live="modo_descarga" value="certificados" class="w-4 h-4 text-blue-600 focus:ring-0 focus:ring-offset-0">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase group-hover:text-blue-600 transition-colors">Certificados de Cumplimiento</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                <!-- Principal -->
                <div>
                    <label class="text-gray-700 dark:text-gray-300 text-[11px] font-black uppercase tracking-wider block mb-1.5">
                        1. Seleccione Principal (Mandante)
                    </label>
                    <select wire:model.live="mandante_id" class="w-full text-sm p-2.5 rounded-lg border border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 bg-gray-50 dark:bg-gray-700 dark:text-white transition-colors">
                        <option value="">-- Seleccionar --</option>
                        @foreach($mandantes as $m)
                            <option value="{{ $m->id }}">{{ $m->razon_social }}</option>
                        @endforeach
                    </select>
                    @error('mandante_id') <span class="text-xs font-bold text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Lugar de Trabajo -->
                <div>
                    <label class="text-gray-700 dark:text-gray-300 text-[11px] font-black uppercase tracking-wider block mb-1.5">
                        2. Lugar de Trabajo
                    </label>
                    <select wire:model.live="lugar_id" class="w-full text-sm p-2.5 rounded-lg border border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 bg-gray-50 dark:bg-gray-700 dark:text-white transition-colors" {{ !$mandante_id ? 'disabled' : '' }}>
                        <option value="">-- Todos los Lugares --</option>
                        @foreach($lugares as $l)
                            <option value="{{ $l->id }}">{{ $l->nombre_jerarquico }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Contratista -->
                <div>
                    <label class="text-gray-700 dark:text-gray-300 text-[11px] font-black uppercase tracking-wider block mb-1.5">
                        3. Contratista
                    </label>
                    <select wire:model.live="contratista_id" class="w-full text-sm p-2.5 rounded-lg border border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 bg-gray-50 dark:bg-gray-700 dark:text-white transition-colors" {{ !$mandante_id ? 'disabled' : '' }}>
                        <option value="">-- Todos los Contratistas --</option>
                        @foreach($contratistas as $c)
                            <option value="{{ $c->id }}">{{ $c->razon_social }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Contrato -->
                <div>
                    <label class="text-gray-700 dark:text-gray-300 text-[11px] font-black uppercase tracking-wider block mb-1.5">
                        4. N° Contrato
                    </label>
                    <select wire:model.live="contrato_id" class="w-full text-sm p-2.5 rounded-lg border border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 bg-gray-50 dark:bg-gray-700 dark:text-white transition-colors" {{ !$mandante_id ? 'disabled' : '' }}>
                        <option value="">-- Todos los Contratos --</option>
                        @foreach($contratos as $con)
                            <option value="{{ $con }}">{{ $con }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Requisito (Solo si es modo documentos) -->
                @if($modo_descarga === 'documentos')
                <div>
                    <label class="text-gray-700 dark:text-gray-300 text-[11px] font-black uppercase tracking-wider block mb-1.5">
                        5. Tipo de Documento
                    </label>
                    <select wire:model.live="requisito_id" class="w-full text-sm p-2.5 rounded-lg border border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 bg-gray-50 dark:bg-gray-700 dark:text-white transition-colors" {{ !$mandante_id ? 'disabled' : '' }}>
                        <option value="">-- Seleccionar --</option>
                        @php $currentClasif = ''; @endphp
                        @foreach($requisitos as $r)
                            @if($currentClasif != ($r->clasificacion->nombre ?? 'OTRO'))
                                @if($currentClasif != '') </optgroup> @endif
                                @php $currentClasif = $r->clasificacion->nombre ?? 'OTRO'; @endphp
                                <optgroup label="{{ $currentClasif }}">
                            @endif
                            <option value="{{ $r->id }}">{{ $r->nombre }}</option>
                        @endforeach
                        @if($currentClasif != '') </optgroup> @endif
                    </select>
                    @error('requisito_id') <span class="text-xs font-bold text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>
                @endif
            </div>

            <!-- Tipo de Filtro -->
            <div class="mb-4">
                <label class="text-gray-700 dark:text-gray-300 text-[11px] font-black uppercase tracking-wider block mb-2">
                    @if($modo_descarga === 'documentos') 6. @else 5. @endif Criterio de Selección
                </label>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 cursor-pointer p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors {{ $tipo_filtro == 'periodo' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                        <input type="radio" wire:model.live="tipo_filtro" value="periodo" class="text-blue-600 focus:ring-blue-500 w-4 h-4">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase">Mes/Año</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors {{ $tipo_filtro == 'rango_fecha' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                        <input type="radio" wire:model.live="tipo_filtro" value="rango_fecha" class="text-blue-600 focus:ring-blue-500 w-4 h-4">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase">Rango de Recepción</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors {{ $tipo_filtro == 'plazo' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                        <input type="radio" wire:model.live="tipo_filtro" value="plazo" class="text-blue-600 focus:ring-blue-500 w-4 h-4">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase">Por Plazo</span>
                    </label>
                </div>
            </div>

            <!-- Campos Dinámicos -->
            <div class="bg-gray-50 dark:bg-gray-750 p-4 rounded-lg border border-gray-100 dark:border-gray-600 mb-6">
                
                @if($tipo_filtro === 'periodo')
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="text-gray-600 dark:text-gray-400 text-[10px] font-bold uppercase block mb-1">Año</label>
                            <select wire:model.live="anio" class="w-full text-sm p-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white">
                                @for($y = date('Y'); $y >= 2024; $y--)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                            @error('anio') <span class="text-[10px] text-red-500 block mt-1 font-bold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-gray-600 dark:text-gray-400 text-[10px] font-bold uppercase block mb-1">Mes</label>
                            <select wire:model.live="mes" class="w-full text-sm p-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white">
                                <option value="">-- Seleccionar --</option>
                                @foreach(['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $i => $nombre)
                                    <option value="{{ $i + 1 }}">{{ $nombre }}</option>
                                @endforeach
                            </select>
                            @error('mes') <span class="text-[10px] text-red-500 block mt-1 font-bold">{{ $message }}</span> @enderror
                        </div>
                    </div>

                @elseif($tipo_filtro === 'rango_fecha')
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-gray-600 dark:text-gray-400 text-[10px] font-bold uppercase block mb-1">Desde</label>
                            <input type="date" wire:model.live="fecha_desde" class="w-full text-sm p-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white">
                            @error('fecha_desde') <span class="text-[10px] text-red-500 block mt-1 font-bold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-gray-600 dark:text-gray-400 text-[10px] font-bold uppercase block mb-1">Hasta</label>
                            <input type="date" wire:model.live="fecha_hasta" class="w-full text-sm p-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white">
                            @error('fecha_hasta') <span class="text-[10px] text-red-500 block mt-1 font-bold">{{ $message }}</span> @enderror
                        </div>
                    </div>

                @elseif($tipo_filtro === 'plazo')
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-gray-600 dark:text-gray-400 text-[10px] font-bold uppercase block mb-1">Tipo de Envío</label>
                            <select wire:model.live="tipo_envio" class="w-full text-sm p-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white">
                                <option value="">-- Seleccionar --</option>
                                <option value="NORMAL">Dentro de Plazo</option>
                                <option value="FUERA_PLAZO">Fuera de Plazo</option>
                            </select>
                            @error('tipo_envio') <span class="text-[10px] text-red-500 block mt-1 font-bold">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif
            </div>

            <!-- Resumen de Busqueda Oculto si no hay filtros completos -->
            @if($preview_listo)
                <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 mb-4 rounded-r-lg flex items-center justify-between">
                    <div>
                        <h3 class="text-[11px] font-black uppercase text-blue-800 dark:text-blue-300 mb-1">Resumen de Extracción</h3>
                        <p class="text-xs text-blue-600 dark:text-blue-400">
                            Se recopilaron <strong class="text-blue-900 dark:text-blue-100 text-lg">{{ $preview_documentos }}</strong> documentos
                            pertenecientes a <strong class="text-blue-900 dark:text-blue-100 text-lg">{{ $preview_contratistas }}</strong> contratistas.
                        </p>
                    </div>
                    @if($preview_documentos == 0)
                        <div class="text-[10px] bg-red-100 text-red-600 font-bold px-2 py-1 rounded shadow-sm border border-red-200">No hay resultados</div>
                    @else
                        <div class="text-[10px] bg-green-100 text-green-700 font-bold px-2 py-1 rounded shadow-sm border border-green-200">Listo para armar ZIP</div>
                    @endif
                </div>
            @endif

            <!-- Actions -->
            <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                <button wire:click="generarDescarga" 
                        wire:loading.attr="disabled"
                        @if(!$preview_listo || $preview_documentos == 0) disabled @endif
                        class="bg-[#004b75] hover:bg-[#003859] text-white px-6 py-2.5 rounded-lg shadow-md font-black uppercase text-xs flex items-center justify-center gap-2 transition-all w-full md:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                    
                    <span wire:loading.remove wire:target="generarDescarga" class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Generar Archivo ZIP
                    </span>
                    
                    <span wire:loading wire:target="generarDescarga" class="flex items-center gap-2">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Recopilando documentos...
                    </span>
                </button>
            </div>
            
        </div>
    </div>
</div>
