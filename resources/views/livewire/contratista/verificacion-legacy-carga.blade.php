<div class="p-4 bg-gray-100 dark:bg-gray-900 min-h-screen">
    <div class="mb-4 flex items-center gap-4">
        <a href="{{ route('contratista.verificacion-legacy', ['tab' => 'solicitudes']) }}" 
           class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-[#8ed973] to-[#b4eba0] hover:from-[#7fce5d] hover:to-[#a2e088] text-[#003a5c] font-bold rounded-lg shadow-md transition-all duration-300 transform hover:-translate-x-1 border border-white/40 text-xs">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            VOLVER
        </a>
        <h1 class="text-xl font-bold text-gray-700 dark:text-white uppercase tracking-tighter">Envío de Solicitud y Carga de Documentos Cumplimiento Laboral</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        
        @if(!$is_from_legacy)
            <!-- PANEL IZQUIERDO: VINCULACIONES Y PERIODOS (COL 1-3) -->
            <div class="lg:col-span-3 space-y-4">
            <!-- SELECCION EMPRESA -->
            <div class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="bg-[#004b75] text-white px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest border-b border-gray-400 flex justify-between items-center">
                    Vinculaciones
                    <select wire:model.live="anio_seleccionado" wire:change="cargarVinculaciones" class="bg-white text-[#004b75] border-none text-[10px] py-1 px-4 rounded font-black cursor-pointer ml-2 min-w-[80px]">
                        <option value="2024">2024</option>
                        <option value="2025">2025</option>
                        <option value="2026">2026</option>
                    </select>
                </div>
                <!-- FILTRO ID_REGISTRO -->
                <div class="px-2 py-1.5 bg-gray-50 border-b">
                    <input type="text" wire:model.live="filtro_id_registro" wire:keyup="cargarVinculaciones" placeholder="Filtrar por ID_REGISTRO..." class="w-full text-[10px] px-2 py-1 rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
                <div class="p-2 space-y-2">
                    @foreach($vinculaciones as $index => $v)
                        @php
                            $bgColor = $loop->even ? 'bg-slate-50 dark:bg-gray-700' : 'bg-amber-50/30 dark:bg-gray-700/80';
                            $darkBorder = 'dark:border-gray-600';
                            if ($vinculacion_seleccionada_id == $v->id) {
                                $bgColor = 'bg-blue-100 dark:bg-blue-900/50 border-blue-500';
                                $darkBorder = 'dark:border-blue-400';
                            }
                        @endphp
                        <button wire:click="detallarPeriodos({{ $v->id }})" 
                                class="w-full text-left px-4 py-3 text-[10px] transition-all border-2 rounded-lg {{ $bgColor }} {{ $darkBorder }} {{ $vinculacion_seleccionada_id == $v->id ? 'border-blue-500 shadow-md' : 'border-gray-200 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 hover:shadow-sm' }}">
                            
                            <!-- PRINCIPAL - destacado con fondo -->
                            <div class="bg-sky-600 dark:bg-sky-700 text-white font-black text-[13px] uppercase mb-2 px-2 py-1.5 rounded -mx-2 -mt-1 flex justify-between items-center">
                                <span>{{ $v->unidadOrganizacionalMandante->mandante->razon_social }}</span>
                                <span class="bg-white/20 px-2 py-0.5 rounded text-[10px] font-bold">ID: {{ $v->id_registro }}</span>
                            </div>
                            
                            <!-- Lista vertical para los datos (sin cortar textos) -->
                            <div class="space-y-1.5">
                                <div class="flex flex-wrap items-baseline gap-1">
                                    <span class="text-gray-500 dark:text-gray-300 text-[9px] font-bold">LUGAR DE TRABAJO/DEPARTAMENTO:</span>
                                    <span class="text-gray-800 dark:text-white uppercase font-semibold text-[11px]">{{ $v->dependencia->nombre ?? 'S/L' }}</span>
                                </div>
                                <div class="flex flex-wrap items-baseline gap-1">
                                    <span class="text-gray-500 dark:text-gray-300 text-[9px] font-bold">UNIDAD OPERATIVA/UO:</span>
                                    <span class="text-gray-800 dark:text-white uppercase font-semibold text-[11px]">{{ $v->unidadOrganizacionalMandante->nombre_unidad ?? 'N/A' }}</span>
                                </div>
                                <div class="flex flex-wrap items-baseline gap-1">
                                    <span class="text-gray-500 dark:text-gray-300 text-[9px] font-bold">N° CONTRATO:</span>
                                    <span class="text-amber-700 dark:text-yellow-400 uppercase font-black text-[11px]">{{ $v->numero_contrato ?? 'S/N' }}</span>
                                </div>
                                <div class="flex flex-wrap items-baseline gap-1">
                                    <span class="text-gray-500 dark:text-gray-300 text-[9px] font-bold">TIPO CONTRATO:</span>
                                    <span class="text-blue-700 dark:text-cyan-300 uppercase font-semibold text-[11px]">{{ $v->tipoContrato->nombre ?? 'N/A' }}</span>
                                </div>
                                <div class="flex flex-wrap items-baseline gap-1 mt-1 border-t border-gray-200 dark:border-gray-600 pt-1">
                                    <span class="text-purple-600 dark:text-purple-300 text-[9px] font-bold">VIGENCIA:</span>
                                    <span class="text-gray-800 dark:text-white font-bold text-[10px]">
                                        {{ $v->fecha_inicio_verifica ? \Carbon\Carbon::parse($v->fecha_inicio_verifica)->format('d/m/Y') : 'Inicio N/A' }} 
                                        - 
                                        {{ $v->fecha_fin_verifica ? \Carbon\Carbon::parse($v->fecha_fin_verifica)->format('d/m/Y') : 'Indefinido' }}
                                    </span>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- SELECCION PERIODO -->
            @if($vinculacion_seleccionada_id)
                <div class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 shadow-sm overflow-hidden animate-in fade-in duration-500">
                    <div class="bg-gray-100 dark:bg-gray-700 px-3 py-1 text-[9px] font-bold uppercase text-gray-500 border-b">
                        Periodos Disponibles ({{ $anio_seleccionado }})
                    </div>
                    <div class="p-2 max-h-[600px] overflow-y-auto custom-scrollbar space-y-1">
                        @forelse($periodos as $p)
                            @php
                                $isEnviado = $p['estado'] === 'ENVIADO';
                                $isInProg = $p['estado'] === 'EN PROGRESO';
                                $isEmitido = ($p['estado_revision'] ?? '') === 'EMITIDO';
                                $isBloqueado = ($p['estado_plazo'] ?? '') === 'S/C';
                                $isSel = ($mes_seleccionado == $p['mes'] && $anio_seleccionado == $p['anio']);
                                
                                $bgClass = $isEmitido ? 'bg-[#003a5c]' : ($isEnviado ? 'bg-[#3b82f6]' : ($isInProg ? 'bg-[#8ed973]' : 'bg-white dark:bg-gray-800'));
                                $borderCls = $isSel ? 'border-2 border-amber-500' : 'border border-gray-200';
                                $textClass = ($isEmitido || $isEnviado) ? 'text-white' : ($isInProg ? 'text-[#003a5c]' : 'text-gray-600 dark:text-gray-300');
                                $subTextClass = ($isEmitido || $isEnviado) ? 'text-white/60' : ($isInProg ? 'text-[#003a5c]/80' : 'text-gray-500 dark:text-gray-400');
                            @endphp

                            <button wire:click="seleccionarPeriodo({{ $p['anio'] }}, {{ $p['mes'] }})"
                                    class="w-full text-left p-3 rounded transition-all duration-200 focus:outline-none {{ $bgClass }} {{ $borderCls }} {{ $isBloqueado ? 'opacity-40 grayscale cursor-not-allowed group' : '' }}"
                                    {{ $isBloqueado ? 'disabled' : '' }}>
                                
                                <!-- FILA 1: Mes + Estado de Envío -->
                                <div class="flex justify-between items-center mb-2 {{ $isEmitido ? 'bg-blue-900/40' : ($isEnviado ? 'bg-blue-800/50' : ($isInProg ? 'bg-white/30' : 'bg-sky-600 dark:bg-sky-700')) }} text-white px-2 py-1.5 rounded -mx-1 -mt-1">
                                    <span class="font-black text-[11px]">REMUNERACIONES {{ strtoupper($p['periodo']) }}</span>
                                    <div class="flex items-center gap-1">
                                        @if($p['fuera_vigencia'] ?? false)
                                            <span class="text-[7px] text-amber-900 bg-amber-300 px-1.5 py-0.5 rounded font-black shadow-sm">⚠️ FUERA VIGENCIA</span>
                                        @endif

                                        @if($p['estado_revision'] === 'EMITIDO')
                                            <span class="text-[8px] text-purple-700 dark:text-purple-300 font-black bg-purple-100 dark:bg-purple-900/50 px-2 py-0.5 rounded shadow-sm">📄 EMITIDO</span>
                                        @elseif($isEnviado)
                                            <span class="text-[8px] text-[#3b82f6] font-black bg-white px-2 py-0.5 rounded shadow-sm">✓ EN REVISIÓN</span>
                                        @elseif($isInProg)
                                            <span class="text-[8px] text-[#003a5c] font-black bg-white px-2 py-0.5 rounded shadow-sm">◐ INICIADO</span>
                                        @else
                                            <span class="text-[8px] text-gray-400 dark:text-gray-400 font-black bg-gray-100 dark:bg-gray-600 px-2 py-0.5 rounded shadow-sm">○ PENDIENTE</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- FILA 2: Estado de Plazo -->
                                <div class="mb-2">
                                    @if($p['estado_plazo'] == 'S/C')
                                        <div class="flex items-center gap-1.5 {{ ($isEnviado || $isInProg) ? 'text-white/60' : 'text-gray-400 dark:text-gray-500' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ ($isEnviado || $isInProg) ? 'bg-white/40' : 'bg-gray-400' }}"></span>
                                            <span class="text-[9px] uppercase font-bold tracking-tight">NO HABILITADO</span>
                                        </div>
                                    @elseif($p['estado_plazo'] == 'DENTRO_PLAZO')
                                        <div class="flex items-center gap-1.5 {{ ($isEnviado || $isInProg) ? 'text-white' : 'text-emerald-600 dark:text-emerald-400' }}">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span class="text-[9px] uppercase font-bold tracking-tight">ESTÁS DENTRO DE PLAZO</span>
                                        </div>
                                    @elseif($p['estado_plazo'] == 'FUERA_PLAZO')
                                        <span class="text-[8px] {{ ($isEnviado || $isInProg) ? 'bg-white text-red-600' : 'bg-amber-50 text-amber-600' }} font-black px-2 py-0.5 rounded">⚠ ESTÁS FUERA DE PLAZO</span>
                                    @elseif($p['estado_plazo'] == 'FUTURO')
                                        <span class="text-[8px] {{ ($isEnviado || $isInProg) ? 'bg-white text-blue-600' : 'bg-blue-50 text-blue-600' }} font-black px-2 py-0.5 rounded">● PRÓXIMAMENTE</span>
                                    @elseif($p['estado_plazo'] == 'VENCIDO')
                                        <span class="text-[8px] {{ ($isEnviado || $isInProg) ? 'bg-white text-red-600' : 'bg-red-50 text-red-600' }} font-black px-2 py-0.5 rounded">✖ ESTÁS FUERA DE PLAZO</span>
                                    @endif

                                    @if($p['ia_datos_extraidos'])
                                        <span class="text-[8px] text-blue-700 dark:text-blue-300 font-black bg-blue-100 dark:bg-blue-900/50 px-2 py-0.5 rounded ml-2 border border-blue-300 animate-pulse">🤖 IA OK</span>
                                    @endif
                                </div>

                                <!-- RESUMEN DE INCIDENCIAS (Solo si hay dades) -->
                                @if(isset($p['counts']) && ($p['counts']['observaciones'] > 0 || $p['counts']['retenibles'] > 0 || $p['counts']['no_retenibles'] > 0))
                                    <div class="mb-2 flex flex-wrap gap-1">
                                        @if($p['counts']['observaciones'] > 0)
                                            <span class="bg-blue-100 text-blue-700 text-[7px] font-black px-1.5 py-0.5 rounded uppercase">OBS: {{ $p['counts']['observaciones'] }}</span>
                                        @endif
                                        @if($p['counts']['retenibles'] > 0)
                                            <span class="bg-red-100 text-red-700 text-[7px] font-black px-1.5 py-0.5 rounded uppercase">RET: {{ $p['counts']['retenibles'] }}</span>
                                        @endif
                                        @if($p['counts']['no_retenibles'] > 0)
                                            <span class="bg-amber-100 text-amber-700 text-[7px] font-black px-1.5 py-0.5 rounded uppercase">NO RET: {{ $p['counts']['no_retenibles'] }}</span>
                                        @endif
                                    </div>
                                @endif
                                
                                <!-- FILA 3: Fechas de Carga y Emisión -->
                                <div class="text-[8px] space-y-0.5 border-t border-gray-200 dark:border-gray-600 pt-2 mt-1">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 dark:text-gray-200 font-bold">📅 CARGA DENTRO DE PLAZO:</span>
                                        <span class="text-gray-700 dark:text-white">{{ $p['fecha_apertura'] ?? '-' }} al {{ $p['fecha_cierre'] ?? '-' }}</span>
                                    </div>
                                    @if($p['fecha_emision'])
                                        <div class="flex justify-between">
                                            <span class="text-green-600 dark:text-green-300 font-bold">📋 EMISIÓN DENTRO DE PLAZO:</span>
                                            <span class="text-green-700 dark:text-green-300 font-bold">{{ $p['fecha_emision'] }}</span>
                                        </div>
                                    @endif
                                    @if($p['fecha_cierre_fuera_plazo'])
                                        <div class="flex justify-between mt-1 pt-1 border-t border-amber-200 dark:border-amber-600">
                                            <span class="text-amber-600 dark:text-amber-300 font-bold">⚠️ CARGA FUERA DE PLAZO:</span>
                                            <span class="text-amber-700 dark:text-amber-300">{{ $p['fecha_cierre_fuera_plazo'] }}</span>
                                        </div>
                                    @endif
                                    @if($p['fecha_emision_fuera_plazo'])
                                        <div class="flex justify-between">
                                            <span class="text-amber-600 dark:text-amber-300 font-bold">📋 EMISIÓN FUERA DE PLAZO:</span>
                                            <span class="text-amber-700 dark:text-amber-300 font-bold">{{ $p['fecha_emision_fuera_plazo'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            </button>
                        @empty
                            <div class="p-8 text-center bg-gray-50 dark:bg-gray-800 rounded border-2 border-dashed border-gray-200 dark:border-gray-700">
                                <div class="text-gray-400 mb-2">
                                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2-2-2 0 002-2V7a2-2-2 0 00-2-2H5a2-2-2 0 00-2 2v12a2-2-2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <p class="text-[11px] font-black uppercase text-gray-500 tracking-widest">No hay periodos para {{ $anio_seleccionado }}</p>
                                <p class="text-[9px] text-gray-400 mt-1 uppercase">Verifique la vigencia de su vinculación.</p>
                            </div>
                        @endforelse
                </div>
            @endif
        </div>
    @endif

        <!-- PANEL DERECHO: DETALLE DE CARGA (siempre full-width en Legacy Carga) -->
        <div class="lg:col-span-12">
            @if($mes_seleccionado)
                    @if (session()->has('upload_status'))
                        <div class="bg-blue-600 text-white px-4 py-2 text-[11px] font-black uppercase tracking-wider animate-in slide-in-from-top-1">
                            {{ session('upload_status') }}
                        </div>
                    @endif
                    <!-- CABECERA PRINCIPAL -->
                    <div class="bg-[#004b75] text-white">
                        @php $v_actual = $vinculaciones->firstWhere('id', $vinculacion_seleccionada_id); @endphp
                        
                        <!-- FILA 1: Principal + Botón Enviar -->
                        <div class="flex justify-between items-center px-4 py-2 border-b border-white/10">
                            <div class="text-base font-black uppercase tracking-tight flex items-center gap-3">
                                {{ $v_actual->unidadOrganizacionalMandante->mandante->razon_social }}
                                <span class="bg-white/20 px-2 py-0.5 rounded text-[10px] font-bold">ID REGISTRO: {{ $v_actual->id_registro }}</span>
                            </div>
                            @if($carpeta_actual && $carpeta_actual->estado_revision === 'EMITIDO')
                                <a href="{{ route('verificacion.certificado.visor', $carpeta_actual->id) }}" target="_blank" class="bg-purple-600 hover:bg-purple-700 text-white font-black text-[11px] px-5 py-2 rounded shadow-lg transition-all uppercase tracking-wider flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    VER / DESCARGAR CERTIFICADO
                                </a>
                            @elseif($carpeta_actual && $carpeta_actual->estado !== 'ENVIADO')
                                @php 
                                    $errorMsg = $this->verificarBloqueoSecuencial(); 
                                    $faltantesCount = 0;
                                    $v_actual = $vinculaciones->firstWhere('id', $vinculacion_seleccionada_id);
                                    if ($v_actual) {
                                        $mandanteId = $v_actual->unidadOrganizacionalMandante->mandante_id ?? $v_actual->unidadOrganizacional->mandante_id ?? null;
                                        if ($mandanteId) {
                                            $reqObligatorios = \App\Models\RequisitoVerificacion::where('mandante_id', $mandanteId)
                                                ->where('is_active', true)
                                                ->where('es_obligatorio', true)
                                                ->get();
                                            foreach ($reqObligatorios as $rO) {
                                                $tiene = \App\Models\DocumentoVerificacion::where('carpeta_verificacion_id', $carpeta_actual->id)
                                                    ->where('requisito_verificacion_id', $rO->id)
                                                    ->exists();
                                                if (!$tiene) $faltantesCount++;
                                            }
                                        }
                                    }
                                @endphp
                                <button wire:click="abrirModalConfirmacion" 
                                        {!! ($errorMsg || $faltantesCount > 0) ? 'disabled title="' . ($errorMsg ?? 'Faltan documentos obligatorios') . '" class="bg-gray-400 text-white font-black text-[11px] px-5 py-2 rounded shadow opacity-60 cursor-not-allowed uppercase tracking-wider"' : 'class="bg-green-500 hover:bg-green-600 text-white font-black text-[11px] px-5 py-2 rounded shadow-lg transition-all uppercase tracking-wider"' !!}>
                                    ✓ ENVIAR PERIODO
                                </button>
                            @elseif($carpeta_actual && $carpeta_actual->estado === 'ENVIADO')
                                <div class="flex items-center gap-2">
                                    @if($carpeta_actual->tipo_envio === 'NORMAL')
                                        <div class="bg-green-500 text-white font-black text-[10px] px-4 py-1.5 rounded uppercase">✅ ENVIADO EN PLAZO</div>
                                    @elseif($carpeta_actual->tipo_envio === 'FUERA_PLAZO')
                                        <div class="bg-amber-500 text-white font-black text-[10px] px-4 py-1.5 rounded uppercase">⚠️ FUERA DE PLAZO</div>
                                    @else
                                        <div class="bg-green-600 text-white font-black text-[10px] px-4 py-1.5 rounded uppercase">✅ PERIODO ENVIADO</div>
                                    @endif
                                </div>
                            @endif
                        </div>
                        
                        <!-- FILA 2: Datos de Vinculación en línea -->
                        <div class="px-4 py-2 bg-[#003a5c] text-[10px] font-bold flex flex-wrap gap-x-6 gap-y-1">
                            <div><span class="text-white/50">LUGAR/DEPENDENCIA:</span> <span class="text-white">{{ $v_actual->dependencia->nombre ?? 'N/A' }}</span></div>
                            <div><span class="text-white/50">UNIDAD OPERATIVA UO:</span> <span class="text-white">{{ $v_actual->unidadOrganizacionalMandante->nombre_unidad ?? 'N/A' }}</span></div>
                            <div><span class="text-white/50">N° CONTRATO:</span> <span class="text-white">{{ $v_actual->numero_contrato ?? 'S/N' }}</span></div>
                            <div><span class="text-white/50">TIPO CONTRATO:</span> <span class="text-white">{{ $v_actual->tipoContrato->nombre ?? 'N/A' }}</span></div>
                        </div>
                        
                        <!-- FILA 3: Contratista + Periodo + Fechas -->
                        <div class="px-4 py-3 flex justify-between items-center">
                            <!-- IZQUIERDA: Contratista y Periodo -->
                            <div>
                                <div class="text-white text-[24px] font-black uppercase tracking-tight">
                                    <span class="text-white/60 text-[14px]">RAZÓN SOCIAL:</span> {{ $v_actual->contratista->razon_social }}
                                </div>
                                <div class="bg-amber-500 text-white font-black text-sm px-4 py-1 rounded mt-2 inline-block uppercase tracking-wide">
                                    @php
                                        $periodoNombre = \Carbon\Carbon::create($anio_seleccionado, $mes_seleccionado, 1);
                                    @endphp
                                    REMUNERACIONES {{ strtoupper($periodoNombre->monthName) }} {{ $periodoNombre->year }}
                                </div>
                            </div>
                            
                            <!-- DERECHA: Estado de Plazo y Fechas ORDENADAS -->
                            @if($periodoActual)
                                <div class="text-right">
                                    <!-- Estado del Plazo -->
                                    @if($periodoActual['estado_plazo'] == 'DENTRO_PLAZO')
                                        <div class="bg-green-500 text-white font-black text-[11px] px-4 py-1 rounded inline-block mb-2">● ESTÁS DENTRO DE PLAZO</div>
                                    @elseif($periodoActual['estado_plazo'] == 'FUERA_PLAZO')
                                        <div class="bg-amber-500 text-white font-black text-[11px] px-4 py-1 rounded inline-block mb-2">⚠️ ESTÁS FUERA DE PLAZO</div>
                                    @elseif($periodoActual['estado_plazo'] == 'FUTURO')
                                        <div class="bg-gray-500 text-white font-black text-[11px] px-4 py-1 rounded inline-block mb-2">📅 PRÓXIMAMENTE</div>
                                    @else
                                        <div class="bg-red-600 text-white font-black text-[11px] px-4 py-1 rounded inline-block mb-2">✕ ESTÁS FUERA DE PLAZO</div>
                                    @endif
                                    
                                    <!-- FECHAS ORGANIZADAS EN DOS COLUMNAS -->
                                    <div class="grid grid-cols-2 gap-3 text-[9px] font-bold">
                                        <!-- COLUMNA 1: PLAZO NORMAL -->
                                        <div class="bg-green-900/30 rounded p-2 border border-green-700">
                                            <div class="text-green-300 font-black text-[8px] uppercase mb-1 border-b border-green-700 pb-1">📗 DENTRO DE PLAZO</div>
                                            <div class="space-y-0.5">
                                                <div class="flex justify-between gap-2">
                                                    <span class="text-white/60">CARGA:</span>
                                                    <span class="text-white">{{ $periodoActual['fecha_apertura'] ?? '-' }} al {{ $periodoActual['fecha_cierre'] ?? '-' }}</span>
                                                </div>
                                                <div class="flex justify-between gap-2">
                                                    <span class="text-white/60">EMISIÓN:</span>
                                                    <span class="text-green-300">{{ $periodoActual['fecha_emision'] ?? '-' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- COLUMNA 2: FUERA DE PLAZO -->
                                        <div class="bg-amber-900/30 rounded p-2 border border-amber-700">
                                            <div class="text-amber-300 font-black text-[8px] uppercase mb-1 border-b border-amber-700 pb-1">⚠️ FUERA DE PLAZO</div>
                                            <div class="space-y-0.5">
                                                <div class="flex justify-between gap-2">
                                                    <span class="text-white/60">CARGA:</span>
                                                    <span class="text-white">{{ $periodoActual['fecha_cierre_fuera_plazo'] ?? 'N/A' }}</span>
                                                </div>
                                                <div class="flex justify-between gap-2">
                                                    <span class="text-white/60">EMISIÓN:</span>
                                                    <span class="text-amber-300">{{ $periodoActual['fecha_emision_fuera_plazo'] ?? 'N/A' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    @php
                        $hasIncidences = false;
                        if ($periodoActual && isset($periodoActual['counts'])) {
                            $hasIncidences = ($periodoActual['counts']['observaciones'] > 0 || $periodoActual['counts']['retenibles'] > 0 || $periodoActual['counts']['no_retenibles'] > 0);
                        }
                        // En el Legacy, siempre queremos ver la nómina y los documentos, 
                        // pero si está EMITIDO mostramos el mensaje de éxito arriba.
                        $isEmitido = $carpeta_actual->estado_revision === 'EMITIDO';
                    @endphp

                    @if($isEmitido)
                        <!-- MENSAJE PERIODO FINALIZADO -->
                        <div class="mb-4 bg-white dark:bg-gray-800 border-2 border-purple-200 dark:border-purple-900 rounded-xl overflow-hidden shadow-lg animate-in zoom-in duration-300">
                            <div class="p-8 flex flex-col items-center justify-center bg-gradient-to-b from-purple-50 to-white dark:from-purple-900/10 dark:to-gray-800">
                                <div class="text-6xl mb-4 drop-shadow-md">📜</div>
                                <h3 class="text-xl font-black text-purple-800 dark:text-purple-400 uppercase tracking-tighter">Certificación Finalizada</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 font-bold uppercase mt-2 text-center max-w-md">
                                    Este periodo ha sido emitido exitosamente. Puede descargar su certificado y revisar el detalle de la auditoría y nómina abajo.
                                </p>
                                <div class="mt-6 flex gap-4">
                                    <a href="{{ route('verificacion.certificado.visor', $carpeta_actual->id) }}" target="_blank" class="bg-purple-600 hover:bg-purple-700 text-white font-black text-xs px-8 py-3 rounded-xl shadow-lg transition-all transform hover:scale-105 uppercase tracking-widest flex items-center gap-3">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        DESCARGAR CERTIFICADO OFICIAL
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-6">
                        <!-- TABLA DE REQUISITOS AGRUPADOS -->
                    <div class="p-0 overflow-x-auto mt-4">
                        <table class="min-w-full border-collapse bg-white">
                            <tbody>
                                @foreach($requisitos as $clasNombre => $reqs)
                                    <!-- Separador de Categoría -->
                                    <tr class="bg-[#004b75] dark:bg-sky-800 border-b border-[#003a5c] dark:border-sky-900 shadow-sm">
                                        <td colspan="2" class="px-4 py-1.5 text-[10px] font-black text-white uppercase tracking-widest italic">
                                            {{ $clasNombre }}
                                        </td>
                                    </tr>

                                    @foreach($reqs as $req)
                                        <tr class="border-b border-gray-200 dark:border-gray-700">
                                            <!-- Grupo / Nombre Requisito -->
                                            <td class="bg-gray-50/50 dark:bg-gray-700 w-1/3 p-4 border-r border-gray-200 dark:border-gray-700 align-top">
                                                <div class="text-[11px] font-black text-gray-800 dark:text-gray-100 uppercase leading-snug">
                                                    {{ $req->nombre }}
                                                    @if($req->es_obligatorio)
                                                        <span class="ml-2 bg-amber-100 text-amber-800 text-[8px] font-black px-2 py-0.5 rounded-full border border-amber-300 tracking-widest inline-block transform -translate-y-0.5">
                                                            ⭐ OBLIGATORIO
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-[9px] text-gray-500 dark:text-gray-400 italic mt-1">{{ $req->descripcion }}</div>
                                            </td>
                                            
                                            <!-- Zona de Archivos y Carga -->
                                            <td class="p-4 align-top dark:bg-gray-800">
                                                <div class="flex flex-col gap-3">
                                                    <!-- Listado de documentos existentes -->
                                                    <div class="space-y-1">
                                                        @php $docs = $documentosCargados->where('requisito_verificacion_id', $req->id); @endphp
                                                        @forelse($docs as $doc)
                                                            <div class="flex items-center justify-between gap-3 text-[10px] bg-white dark:bg-gray-700 border border-gray-100 dark:border-gray-600 p-2 rounded shadow-sm">
                                                                <div class="flex items-center gap-2">
                                                                    <a href="{{ $doc->url }}" target="_blank" class="flex items-center gap-1.5 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 text-[9px] font-black px-2 py-1 rounded transition-colors group" title="Ver documento">
                                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                                        <span>Ver</span>
                                                                    </a>
                                                                    <a href="{{ $doc->url }}&download=1" class="flex items-center gap-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-[9px] font-black px-2 py-1 rounded transition-colors group" title="Descargar documento">
                                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                                        <span>Descargar</span>
                                                                    </a>
                                                                    <span class="text-gray-400 dark:text-gray-500 text-[8px] truncate max-w-[120px] italic ml-1" title="{{ $doc->nombre_original }}">{{ $doc->nombre_original }}</span>
                                                                </div>
                                                                
                                                                @if($carpeta_actual->estado !== 'ENVIADO')
                                                                    <button wire:click="eliminarDocumento({{ $doc->id }})" 
                                                                            wire:confirm="¿Desea borrar este archivo?"
                                                                            class="text-red-700 dark:text-red-400 hover:text-white hover:bg-red-600 border border-red-200 dark:border-red-700 font-black px-3 py-1 rounded-md uppercase text-[8px] transition-all flex items-center gap-1 shadow-sm">
                                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                                        ELIMINAR
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        @empty
                                                            <div class="text-[9px] text-gray-300 dark:text-gray-500 font-bold uppercase italic tracking-tighter">Sin documentos cargados</div>
                                                        @endforelse
                                                    </div>

                                                    <!-- Botón para subir (Solo si no está enviado Y está habilitado) -->
                                                    @if($carpeta_actual->estado !== 'ENVIADO')
                                                        @if($periodoActual && $periodoActual['puede_cargar'])
                                                            <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700 flex items-center gap-4">
                                                                <label class="cursor-pointer bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 border border-gray-300 dark:border-gray-500 px-3 py-1 rounded text-[9px] font-bold text-gray-700 dark:text-gray-200 uppercase transition-all shadow-sm flex items-center gap-2">
                                                                    <svg wire:loading.remove wire:target="archivos.{{ $req->id }}" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                                    <span wire:loading.remove wire:target="archivos.{{ $req->id }}">+ Seleccionar PDF</span>
                                                                    
                                                                    <!-- SPINNER DE CARGA -->
                                                                    <div wire:loading wire:target="archivos.{{ $req->id }}" class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                                                                        <svg class="animate-spin h-3 w-3 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                                        </svg>
                                                                        <span>Subiendo documentos...</span>
                                                                    </div>

                                                                    <input type="file" wire:model.live="archivos.{{ $req->id }}" multiple accept=".pdf" class="hidden">
                                                                </label>
                                                            </div>
                                                        @elseif($periodoActual && $periodoActual['estado_plazo'] == 'FUTURO')
                                                            <div class="mt-2 text-[8px] font-bold text-gray-400 dark:text-gray-500 uppercase italic flex items-center gap-1">
                                                                📅 Habilitado desde el {{ $periodoActual['fecha_apertura'] }}
                                                            </div>
                                                        @endif
                                                    @else
                                                        <div class="mt-2 text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase italic">⚠️ Carga bloqueada por envío oficial</div>
                                                    @endif
                                                    
                                                    @error('archivos.'.$req->id.'.*') 
                                                        <div class="text-[8px] font-black text-red-600 dark:text-red-400 uppercase italic">{{ $message }}</div> 
                                                    @enderror
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>


                        <!-- NÓMINA DE TRABAJADORES -->
                        @php
                            $pStart = \Carbon\Carbon::create($anio_seleccionado, $mes_seleccionado, 1)->startOfMonth();
                            $pEnd   = $pStart->copy()->endOfMonth();
                            $countNuevos = $trabajadoresVinculados->filter(function($tv) use ($pStart, $pEnd) {
                                $fi = $tv->vinculacion->fecha_ingreso_vinculacion ?? null;
                                return $fi && \Carbon\Carbon::parse($fi)->between($pStart, $pEnd);
                            })->count();
                        @endphp
                        <div class="border-t-2 border-[#004b75] mt-4">
                            <div class="bg-[#004b75] text-white px-4 py-2">
                                <h3 class="text-xs font-black uppercase tracking-widest flex items-center gap-2 flex-wrap">
                                    👷 NÓMINA DE TRABAJADORES A VERIFICAR
                                    <span class="bg-white/20 px-2 py-0.5 rounded text-[10px]">{{ $trabajadoresVinculados->count() }} personas</span>
                                    @if($countNuevos > 0)
                                        <span class="bg-emerald-400 text-emerald-900 font-black px-2 py-0.5 rounded text-[10px] flex items-center gap-1">
                                            ✨ {{ $countNuevos }} NUEVO{{ $countNuevos > 1 ? 'S' : '' }} EN EL PERIODO
                                        </span>
                                    @endif
                                </h3>
                                <p class="text-[9px] text-white/70 mt-0.5">Informe el estado de desvinculación o movimiento para este periodo</p>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full border-collapse">
                                    <thead class="bg-gray-100 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase tracking-widest border-b border-gray-200 dark:border-gray-600">RUT</th>
                                            <th class="px-3 py-2 text-left text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase tracking-widest border-b border-gray-200 dark:border-gray-600">Nombre Completo</th>
                                            <th class="px-3 py-2 text-left text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase tracking-widest border-b border-gray-200 dark:border-gray-600">Cargo</th>
                                            <th class="px-3 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase tracking-widest border-b border-gray-200 dark:border-gray-600">F. Ingreso</th>
                                            <th class="px-3 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase tracking-widest border-b border-gray-200 dark:border-gray-600">F. Contrato</th>
                                            <th class="px-3 py-2 text-center text-[9px] font-black text-emerald-700 dark:text-emerald-400 uppercase tracking-widest border-b border-gray-200 dark:border-gray-600 bg-emerald-50 dark:bg-emerald-900/20">NUEVO</th>
                                            <th class="px-3 py-2 text-left text-[9px] font-black text-[#004b75] uppercase tracking-widest border-b border-gray-200 dark:border-gray-600 bg-blue-50/50">Resultado Auditoría</th>
                                            <th class="px-3 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase tracking-widest border-b border-gray-200 dark:border-gray-600">Estado / Desvincular</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($trabajadoresVinculados as $tv)
                                            @php
                                                // ── RESOLUCIÓN DE DATOS: Vinculación real o Snapshot histórico ──
                                                $esHistorico = is_null($tv->trabajador_vinculacion_id);
                                                $fiIngreso = $tv->vinculacion?->fecha_ingreso_vinculacion
                                                    ?? ($tv->snapshot_fecha_ingreso ? \Carbon\Carbon::parse($tv->snapshot_fecha_ingreso) : null);
                                                $esNuevo   = $fiIngreso && \Carbon\Carbon::parse($fiIngreso)->between($pStart, $pEnd);
                                                $estadoActual = $tv->estado_revision;
                                                if ($estadoActual === 'BAJA_MANDANTE') $estadoActual = 'CESACION_PRINCIPAL';
                                                // Valores de display (vinculación real tiene prioridad; si null, usa snapshot)
                                                $displayRut    = $tv->vinculacion?->trabajador?->rut ?? $tv->snapshot_rut ?? '-';
                                                $displayNombre = $tv->vinculacion?->trabajador?->nombre_completo ?? $tv->snapshot_nombres ?? '-';
                                                $displayCargo  = $tv->vinculacion?->cargoMandante?->nombre_cargo ?? $tv->snapshot_cargo ?? 'Sin cargo';
                                                $displayFechaContrato = $tv->vinculacion?->fecha_contrato
                                                    ?? ($tv->snapshot_fecha_contrato ? \Carbon\Carbon::parse($tv->snapshot_fecha_contrato) : null);
                                            @endphp
                                            <tr class="{{ $loop->even ? 'bg-gray-50 dark:bg-gray-700' : 'bg-white dark:bg-gray-800' }} hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors border-b border-gray-100 dark:border-gray-700 {{ $tv->tipo_registro === 'ARRASTRE' ? 'border-l-4 border-l-orange-400' : ($esNuevo ? 'border-l-4 border-l-emerald-400' : '') }}">
                                                {{-- RUT + TIPO --}}
                                                <td class="px-3 py-2">
                                                    <div class="flex flex-col">
                                                        <span class="text-[10px] font-black text-gray-800 dark:text-gray-200">{{ $displayRut }}</span>
                                                        @if($tv->tipo_registro === 'ARRASTRE')
                                                            <span class="text-[7px] bg-orange-100 text-orange-700 px-1 rounded w-fit font-black uppercase">Arrastre</span>
                                                        @endif
                                                        @if($esHistorico)
                                                            <span class="text-[7px] bg-gray-100 text-gray-500 dark:bg-gray-600 dark:text-gray-300 px-1 rounded w-fit font-black uppercase">Histórico</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                {{-- NOMBRE --}}
                                                <td class="px-3 py-2 text-[11px] font-bold text-gray-900 dark:text-white uppercase">
                                                    {{ $displayNombre }}
                                                </td>
                                                {{-- CARGO --}}
                                                <td class="px-3 py-2 text-[10px] text-gray-600 dark:text-gray-400">
                                                    {{ $displayCargo }}
                                                </td>
                                                {{-- F. INGRESO --}}
                                                <td class="px-3 py-2 text-center text-[9px] font-bold text-gray-700 dark:text-gray-300">
                                                    {{ $fiIngreso ? \Carbon\Carbon::parse($fiIngreso)->format('d/m/Y') : '-' }}
                                                </td>
                                                {{-- F. CONTRATO --}}
                                                <td class="px-3 py-2 text-center text-[9px] font-bold text-gray-700 dark:text-gray-300">
                                                    {{ $displayFechaContrato ? \Carbon\Carbon::parse($displayFechaContrato)->format('d/m/Y') : '-' }}
                                                </td>
                                                {{-- NUEVO --}}
                                                <td class="px-3 py-2 text-center bg-emerald-50/40 dark:bg-emerald-900/10">
                                                    @if($esNuevo)
                                                        <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 font-black text-[8px] px-2 py-0.5 rounded-full uppercase tracking-wide border border-emerald-300 dark:border-emerald-700">
                                                            ✨ NUEVO
                                                        </span>
                                                    @else
                                                        <span class="text-gray-300 dark:text-gray-600 text-[10px]">—</span>
                                                    @endif
                                                </td>
                                                {{-- RESULTADO AUDITORIA --}}
                                                <td class="px-3 py-2 bg-blue-50/20 dark:bg-blue-900/10">
                                                    @php
                                                        $contingencias = \App\Models\CarpetaTrabajadorContingencia::where('carpeta_verificacion_trabajador_id', $tv->id)->get();
                                                    @endphp
                                                    @forelse($contingencias as $cont)
                                                        <div class="mb-1.5 last:mb-0 border-l-2 {{ $cont->es_retenible ? 'border-red-500 bg-red-50' : 'border-amber-500 bg-amber-50' }} dark:bg-gray-700 p-1 rounded-r shadow-sm">
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-[8px] font-black {{ $cont->es_retenible ? 'text-red-700' : 'text-amber-700' }} uppercase tracking-tighter">
                                                                    {{ $cont->es_retenible ? 'Retenible' : 'Observación' }} ({{ $cont->codigo }})
                                                                </span>
                                                                @if($cont->subsanado)
                                                                    <span class="text-[7px] bg-green-500 text-white px-1 rounded font-black uppercase">SOLUCIONADO</span>
                                                                @else
                                                                    <span class="text-[7px] bg-gray-400 text-white px-1 rounded font-black uppercase">PENDIENTE</span>
                                                                @endif
                                                            </div>
                                                            <div class="text-[9px] text-gray-600 dark:text-gray-400 leading-tight mt-0.5">{{ $cont->causal }}</div>
                                                            @if($cont->monto > 0)
                                                                <div class="text-[9px] font-black text-gray-800 dark:text-white mt-0.5">Monto: ${{ number_format($cont->monto, 0, ',', '.') }}</div>
                                                            @endif
                                                        </div>
                                                    @empty
                                                        @if(($periodoActual['estado_revision'] ?? '') === 'EMITIDO' || $tv->estado_revision === 'EMITIDO' || ($carpeta_actual && $carpeta_actual->estado_revision === 'EMITIDO'))
                                                            <span class="text-[9px] text-green-600 font-bold uppercase tracking-widest italic">✓ Sin observaciones</span>
                                                        @else
                                                            <span class="text-[9px] text-gray-400 font-bold uppercase italic tracking-tighter">Pendiente revisión</span>
                                                        @endif
                                                    @endforelse
                                                </td>
                                                {{-- ESTADO / ACCION --}}
                                                <td class="px-3 py-2">
                                                    @php
                                                        // ── FINIQUITADO BLOQUEADO: vino de una carpeta ya ENVIADA del mismo período ──
                                                        $desvinculacionBloqueada = false;
                                                        if (in_array($estadoActual, ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD']) && $carpeta_actual->estado !== 'ENVIADO') {
                                                            // Buscar si hay otra carpeta del mismo período, ENVIADA, que tiene este trabajador con el mismo estado
                                                            $vincIdsDelTrabajador = \App\Models\TrabajadorVinculacion::where('trabajador_id', $tv->vinculacion?->trabajador_id)->pluck('id');
                                                            $desvinculacionBloqueada = \App\Models\CarpetaVerificacionTrabajador::join('carpetas_verificacion', 'carpetas_verificacion.id', '=', 'carpetas_verificacion_trabajadores.carpeta_verificacion_id')
                                                                ->where('carpetas_verificacion.anio', $carpeta_actual->anio)
                                                                ->where('carpetas_verificacion.mes', $carpeta_actual->mes)
                                                                ->where('carpetas_verificacion.estado', 'ENVIADO')
                                                                ->where('carpetas_verificacion_trabajadores.carpeta_verificacion_id', '!=', $carpeta_actual->id)
                                                                ->whereIn('carpetas_verificacion_trabajadores.trabajador_vinculacion_id', $vincIdsDelTrabajador)
                                                                ->where('carpetas_verificacion_trabajadores.estado_revision', $estadoActual)
                                                                ->exists();
                                                        }
                                                    @endphp
                                                    <div class="flex flex-col gap-1 items-center">
                                                        <select
                                                            wire:change="cambiarEstadoTrabajadorPeriodo({{ $tv->id }}, $event.target.value)"
                                                            @if($carpeta_actual->estado === 'ENVIADO' || $desvinculacionBloqueada) disabled @endif
                                                            class="text-[9px] font-black uppercase rounded border-gray-300 dark:bg-gray-800 dark:border-gray-600 py-1 px-2 w-full max-w-[240px]
                                                            @if($estadoActual === 'PENDIENTE') bg-green-50 text-green-700
                                                            @elseif($estadoActual === 'FINIQUITADO') bg-red-50 text-red-700
                                                            @elseif($estadoActual === 'CESACION_PRINCIPAL') bg-purple-50 text-purple-700
                                                            @elseif($estadoActual === 'RECONOCIMIENTO_ANTIGUEDAD') bg-orange-50 text-orange-700
                                                            @elseif($estadoActual === 'PRESENTE_OTRA_VINCULACION') bg-blue-50 text-blue-700
                                                            @elseif($estadoActual === 'LICENCIA_MEDICA') bg-teal-50 text-teal-700
                                                            @endif"
                                                        >
                                                            <option value="PENDIENTE"                   {{ $estadoActual === 'PENDIENTE'                   ? 'selected' : '' }}>1. ACTIVO</option>
                                                            <option value="FINIQUITADO"                 {{ $estadoActual === 'FINIQUITADO'                 ? 'selected' : '' }}>2. FINIQUITADO</option>
                                                            <option value="CESACION_PRINCIPAL"          {{ $estadoActual === 'CESACION_PRINCIPAL'          ? 'selected' : '' }}>3. CESACIÓN EN LA PRINCIPAL</option>
                                                            <option value="RECONOCIMIENTO_ANTIGUEDAD"   {{ $estadoActual === 'RECONOCIMIENTO_ANTIGUEDAD'   ? 'selected' : '' }}>4. RECONOCIMIENTO ANTIGÜEDAD</option>
                                                            <option value="PRESENTE_OTRA_VINCULACION"  {{ $estadoActual === 'PRESENTE_OTRA_VINCULACION'  ? 'selected' : '' }}>5. PRESENTE EN OTRA VINCULACIÓN</option>
                                                            <option value="LICENCIA_MEDICA"            {{ $estadoActual === 'LICENCIA_MEDICA'            ? 'selected' : '' }}>6. LICENCIA MÉDICA</option>
                                                        </select>

                                                        {{-- ── AVISOS CONTEXTUALES POR ESTADO ── --}}

                                                        @if(in_array($estadoActual, ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD']))
                                                            @if($desvinculacionBloqueada)
                                                                <span class="text-[8px] bg-gray-100 text-gray-600 font-black px-2 py-1 rounded uppercase mt-1 border border-gray-300 text-center block w-full max-w-[240px] leading-tight" title="Estado confirmado en un período ya enviado — no se puede modificar">
                                                                    🔒 {{ $estadoActual }} (confirmado en otro período)
                                                                </span>
                                                            @else
                                                                @if($estadoActual === 'FINIQUITADO')
                                                                    <span class="text-[8px] bg-red-100 text-red-700 font-black px-2 py-1 rounded uppercase mt-1 border border-red-300 text-center block w-full max-w-[240px] leading-tight">
                                                                        📄 SUBIR FINIQUITO en la sección de documentos
                                                                    </span>
                                                                @elseif($estadoActual === 'CESACION_PRINCIPAL')
                                                                    <span class="text-[8px] bg-purple-100 text-purple-700 font-black px-2 py-1 rounded uppercase mt-1 border border-purple-300 text-center block w-full max-w-[240px] leading-tight">
                                                                        📄 SUBIR ANEXO DE CONTRATO (cesación en principal)
                                                                    </span>
                                                                @elseif($estadoActual === 'RECONOCIMIENTO_ANTIGUEDAD')
                                                                    <span class="text-[8px] bg-orange-100 text-orange-700 font-black px-2 py-1 rounded uppercase mt-1 border border-orange-300 text-center block w-full max-w-[240px] leading-tight">
                                                                        📄 SUBIR CONTRATO con cláusula de antigüedad (empresa destino)
                                                                    </span>
                                                                @endif
                                                            @endif
                                                        @elseif($estadoActual === 'LICENCIA_MEDICA')
                                                            <span class="text-[8px] bg-teal-100 text-teal-700 font-black px-2 py-1 rounded uppercase mt-1 border border-teal-300 text-center block w-full max-w-[240px] leading-tight">
                                                                📄 SUBIR LICENCIA MÉDICA en "Otros Documentos"
                                                            </span>
                                                        @endif

                                                        {{-- ── FECHA para FINIQUITADO / CESACION / RECONOCIMIENTO ── --}}
                                                        @if(in_array($estadoActual, ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD']))
                                                            <div class="mt-1 w-full max-w-[240px] bg-red-50 dark:bg-red-900/20 p-1.5 rounded border border-red-200 dark:border-red-800 animate-in fade-in">
                                                                <label class="text-[8px] font-black text-red-700 dark:text-red-400 uppercase italic mb-1 block">
                                                                    @if($estadoActual === 'FINIQUITADO') F. Finiquito Legal:
                                                                    @elseif($estadoActual === 'CESACION_PRINCIPAL') F. Término en Principal:
                                                                    @else F. Reconocimiento (Nuevo Contrato):
                                                                    @endif
                                                                </label>
                                                                <input type="date"
                                                                    class="text-[9px] w-full rounded border-red-300 dark:bg-gray-800 dark:text-gray-200 py-1"
                                                                    value="{{ $tv->vinculacion?->fecha_finiquito ? \Carbon\Carbon::parse($tv->vinculacion->fecha_finiquito)->format('Y-m-d') : '' }}"
                                                                    wire:change="actualizarFechaFiniquito({{ $tv->id }}, $event.target.value)"
                                                                    @if($carpeta_actual->estado === 'ENVIADO' || $desvinculacionBloqueada) disabled @endif
                                                                >
                                                            </div>
                                                        @endif

                                                        {{-- ── SELECTOR DESTINO para PRESENTE_OTRA_VINCULACION ── --}}
                                                        @if($estadoActual === 'PRESENTE_OTRA_VINCULACION')
                                                            @php $destinos = $this->getDestinosPosibles($tv->trabajador_vinculacion_id); @endphp
                                                            @if($destinos->isNotEmpty())
                                                                <div class="w-full max-w-[240px] animate-in slide-in-from-top-1 mt-1">
                                                                    <label class="text-[7px] font-black text-blue-700 uppercase mb-0.5 block italic">Vinculación activa donde se encuentra:</label>
                                                                    <select
                                                                        wire:change="cambiarEstadoTrabajadorPeriodo({{ $tv->id }}, 'PRESENTE_OTRA_VINCULACION', $event.target.value)"
                                                                        @if($carpeta_actual->estado === 'ENVIADO') disabled @endif
                                                                        class="text-[8px] font-bold uppercase rounded border-blue-300 bg-blue-50 dark:bg-gray-800 py-0.5 px-1 w-full"
                                                                    >
                                                                        <option value="">-- SELECCIONAR VINCULACIÓN --</option>
                                                                        @foreach($destinos as $dest)
                                                                            <option value="{{ $dest->id }}" {{ $tv->destino_trabajador_vinculacion_id == $dest->id ? 'selected' : '' }}>
                                                                                {{ $dest->unidadOrganizacionalMandante?->nombre_unidad ?? 'S/U' }} – {{ $dest->dependencia?->nombre ?? 'S/D' }} (Cont. {{ $dest->numero_contrato ?? 'S/N' }})

                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            @else
                                                                <span class="text-[8px] bg-amber-100 text-amber-700 font-black px-2 py-1 rounded uppercase mt-1 border border-amber-300 text-center block w-full max-w-[240px] leading-tight animate-pulse">
                                                                    ⚠️ Sin otras vinculaciones activas — estado revertido a ACTIVO
                                                                </span>
                                                            @endif
                                                        @endif

                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500 text-[10px] font-bold uppercase italic">
                                                    No hay trabajadores registrados para este periodo
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <!-- BOTON ENVIAR AL FINAL -->
                    @if($carpeta_actual && $carpeta_actual->estado_revision === 'EMITIDO')
                        <div class="p-6 bg-purple-50 dark:bg-purple-900/20 border-t border-purple-200 dark:border-purple-700 flex flex-col items-center">
                            <div class="text-purple-800 dark:text-purple-300 font-black text-xs uppercase tracking-widest flex items-center gap-2 mb-1">
                                🎉 CERTIFICADO EMITIDO
                            </div>
                            <div class="text-[10px] text-purple-600 dark:text-purple-400 font-bold uppercase mb-3">El periodo ha sido auditado y su certificado está listo</div>
                        </div>
                    @elseif($carpeta_actual && $carpeta_actual->estado !== 'ENVIADO')
                        @php 
                            $errorMsg = $this->verificarBloqueoSecuencial(); 
                            $obligatoriosFaltantes = [];
                            $v_actual = $vinculaciones->firstWhere('id', $vinculacion_seleccionada_id);
                            if ($v_actual) {
                                $mandanteId = $v_actual->unidadOrganizacionalMandante->mandante_id ?? $v_actual->unidadOrganizacional->mandante_id ?? null;
                                if ($mandanteId) {
                                    $reqObligatorios = \App\Models\RequisitoVerificacion::where('mandante_id', $mandanteId)
                                        ->where('is_active', true)
                                        ->where('es_obligatorio', true)
                                        ->get();
                                    foreach ($reqObligatorios as $rO) {
                                        $tiene = \App\Models\DocumentoVerificacion::where('carpeta_verificacion_id', $carpeta_actual->id)
                                            ->where('requisito_verificacion_id', $rO->id)
                                            ->exists();
                                        if (!$tiene) $obligatoriosFaltantes[] = $rO->nombre;
                                    }
                                }
                            }
                        @endphp
                        <div class="p-6 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-700 flex flex-col items-center justify-center">
                            @if(!empty($obligatoriosFaltantes))
                                <div class="mb-4 w-full max-w-md bg-red-50 border border-red-200 rounded-lg p-4">
                                    <h4 class="text-red-800 font-bold text-[11px] mb-2 uppercase flex items-center gap-2">
                                        <span>⚠️</span> Documentos Obligatorios Pendientes
                                    </h4>
                                    <ul class="space-y-1">
                                        @foreach($obligatoriosFaltantes as $of)
                                            <li class="text-[10px] text-red-600 font-bold flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> {{ $of }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <button wire:click="abrirModalConfirmacion" 
                                    {!! ($errorMsg || !empty($obligatoriosFaltantes)) ? 'disabled title="' . ($errorMsg ?? 'Faltan documentos obligatorios') . '" class="bg-gray-400 text-white font-black text-xs px-10 py-3 rounded shadow opacity-60 cursor-not-allowed uppercase tracking-widest flex items-center gap-3"' : 'class="bg-green-600 hover:bg-green-700 text-white font-black text-xs px-10 py-3 rounded shadow-lg transition-all transform hover:scale-105 uppercase tracking-widest flex items-center gap-3"' !!}>
                                <span>🚀 ENVIAR PERIODO OFICIAL</span>
                            </button>
                        </div>
                    @elseif($carpeta_actual && $carpeta_actual->estado === 'ENVIADO')
                        <div class="p-6 bg-amber-50 dark:bg-amber-900/20 border-t border-gray-200 dark:border-gray-700 flex flex-col items-center">
                            <div class="text-amber-800 dark:text-amber-300 font-black text-xs uppercase tracking-widest flex items-center gap-2 mb-1">
                                🔒 PERIODO BLOQUEADO
                            </div>
                            <div class="text-[10px] text-amber-600 dark:text-amber-400 font-bold uppercase">Este periodo ya se encuentra en proceso de revisión por ASEM</div>
                        </div>
                    @endif
                </div>
            @else
                <!-- ESTADO INICIAL / VACIO -->
                <div class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 border-dashed p-20 flex flex-col items-center justify-center opacity-30">
                    <div class="bg-gray-100 dark:bg-gray-700 p-6 rounded-full border border-gray-200 dark:border-gray-600 mb-4 text-4xl">📄</div>
                    <div class="text-center">
                        <h3 class="text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Escritorio de Trabajo</h3>
                        <p class="text-[9px] text-gray-400 dark:text-gray-500 font-bold italic mt-1">Seleccione una vinculación y periodo para gestionar documentos</p>
                    </div>
                </div>
            @endif
    <!-- CIERRES TRASLADADOS AL FINAL -->
    <!-- ESTILOS ADICIONALES PARA EL SCROLLBAR CLASICO -->
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #004b75; border-radius: 10px; }
        .dark .custom-scrollbar::-webkit-scrollbar-track { background: #374151; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #60a5fa; }
        .dark .bg-gray-750 { background-color: #1f2937; }
    </style>
    <!-- MODAL DE DECLARACIÓN DE VERACIDAD (INN) -->
    @if($modal_confirmacion_visible)
        <div class="fixed inset-0 z-[999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <div class="inline-block align-bottom bg-[#004b75] rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border-2 border-white/20 font-sans">
                    <div class="p-8">
                        <div class="text-white text-[13px] font-medium leading-relaxed space-y-4 text-justify">
                            <p>
                                El solicitante declara bajo juramento que, la información y los antecedentes que está proporcionando, tanto en esta solicitud como en los documentos que se adjuntan, son veraces y completos, asumiendo desde ya toda la responsabilidad tanto civil, laboral y penal que se genere, en caso de detectarse perjurio.
                            </p>
                            <p>
                                A su vez, el solicitante declara que ha sido notificado que la normativa que regula el proceso de verificación dice relación con la Ley 20.123, su reglamento, el artículo 183-C del Código del Trabajo y en los artículos 14 y siguientes del DS. Nº 319/2006 del Ministerio del Trabajo y Previsión Social que contiene el Reglamento sobre Acreditación del Cumplimiento de Obligaciones Laborales y Previsionales, según consta de Resolución Nº342 de fecha 3 de febrero de 2008, de la Subsecretaría del Trabajo y Previsión Social, y la Circular 148 de fecha 29 de diciembre de 2006, de la Dirección del Trabajo.
                            </p>
                            <p>
                                Por último, el solicitante declara que ha sido notificado del objetivo y alcance del proceso de verificación laboral, en el cual el primero de ellos dice relación con la verificación del cumplimiento de obligaciones laborales y previsionales mediante la verificación del pago de cotizaciones, remuneraciones e indemnizaciones; y el alcance comprende el listado de trabajadores completo que presenta el cliente, junto con toda la documentación asociada de estos, por lo que el nivel de aseguramiento es un 100%.
                            </p>
                        </div>

                        <div class="mt-8 flex flex-col items-center gap-6">
                            <label class="flex items-center gap-4 cursor-pointer group bg-white/10 p-4 rounded-xl border border-white/20 hover:bg-white/20 transition-all">
                                <input type="checkbox" wire:model.live="declaracion_aceptada" class="w-6 h-6 rounded border-white/30 bg-white/20 text-green-500 focus:ring-0 focus:ring-offset-0 cursor-pointer">
                                <span class="text-white font-black uppercase tracking-wider text-[14px]">Acepto declaración de veracidad de la información</span>
                            </label>

                            <div class="flex gap-4 w-full">
                                <button wire:click="$set('modal_confirmacion_visible', false)" 
                                        class="flex-1 bg-white/10 hover:bg-white/20 text-white font-black py-4 rounded uppercase tracking-widest border border-white/30 transition-all">
                                    CANCELAR CIERRE DE PROCESO
                                </button>
                                <button wire:click="enviarPeriodo" 
                                        @if(!$declaracion_aceptada) disabled class="flex-1 bg-gray-500 text-white/50 font-black py-4 rounded uppercase tracking-widest cursor-not-allowed"
                                        @else class="flex-1 bg-white text-[#004b75] hover:bg-gray-100 font-black py-4 rounded uppercase tracking-widest shadow-xl transition-all" @endif>
                                    CONFIRMAR CIERRE DE PROCESO
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div></div></div>
