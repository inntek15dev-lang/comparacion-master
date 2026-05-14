<div class="legacy-wrapper p-0 bg-white min-h-screen font-sans text-gray-800">
    <!-- HEADER ESTILO LEGACY -->
    <div class="bg-white border-b-2 border-[#004b75] px-6 py-2 grid grid-cols-3 items-center shadow-sm">
        <div class="flex justify-start">
            <!-- LOGO OVAL LEGACY -->
            <img src="{{ asset('logo_oval.png') }}" alt="OVAL" class="h-12 w-auto">
        </div>
        
        <div class="flex flex-col items-center gap-1">
            <!-- RAZÓN SOCIAL Y SELECTOR CENTRADOS -->
            <div class="text-[20px] font-black text-[#004b75] uppercase leading-tight text-center">
                {{ auth()->user()->contratista->razon_social ?? 'S/R' }}
            </div>

            <div class="flex flex-col items-center gap-0.5 mt-1">
                <label class="text-[11px] font-black text-gray-500 uppercase tracking-tighter text-center">
                    SELECCIONE LA VINCULACIÓN A OPERAR
                </label>
                <select wire:model.live="vinculacion_seleccionada_id" class="bg-gray-100 border border-gray-300 rounded text-[11px] py-1.5 px-6 font-bold text-[#004b75] focus:ring-1 focus:ring-[#004b75]/50 outline-none cursor-pointer min-w-[400px]">
                    @foreach($vinculaciones as $v)
                        <option value="{{ $v->id }}">
                            {{ $v->unidadOrganizacionalMandante->mandante->razon_social ?? 'N/A' }} 
                            [ID: {{ $v->id_registro }}] | 
                            {{ $v->dependencia->nombre ?? 'N/A' }} | 
                            CONTRATO: {{ $v->numero_contrato ?? 'S/N' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex justify-end">
            <!-- ESPACIO PARA SIMETRÍA O BOTONES FUTUROS -->
        </div>
    </div>
>

    <!-- PESTAÑAS PRINCIPALES (NAV NIVEL 1) -->
    <div class="bg-gray-100 px-6 flex items-end gap-1 pt-2 border-b border-gray-300">
        @php
            $tabs = [
                'inicio' => 'INICIO',
                'solicitudes' => 'SOLICITUDES',
                'verificacion' => 'VERIFICACIÓN',
                'herramientas' => 'HERRAMIENTAS'
            ];
        @endphp
        @foreach($tabs as $key => $label)
            <button wire:click="cambiarTabPrincipal('{{ $key }}')" 
                    class="px-6 py-2 text-[11px] font-bold transition-all rounded-t-lg border-t border-l border-r 
                    {{ $tab_principal === $key ? 'bg-[#004b75] text-white border-[#004b75] shadow-[0_-2px_5px_rgba(0,0,0,0.1)]' : 'bg-gray-300 text-gray-600 border-gray-400 hover:bg-gray-200 hover:text-[#004b75]' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <!-- PESTAÑAS SECUNDARIAS (NAV NIVEL 2) -->
    <div class="bg-[#004b75] px-6 py-1.5 flex gap-1.5 border-b border-white/20 shadow-inner">
        @if($tab_principal === 'inicio')
            <button wire:click="cambiarTabSecundario('informacion')" 
                    class="px-4 py-1 text-[9px] font-black uppercase tracking-wider transition-all border border-white/30 rounded-md
                    {{ $tab_secundario === 'informacion' ? 'bg-white text-[#004b75] shadow-sm' : 'text-white hover:bg-white/10' }}">
                INFORMACIÓN
            </button>
            <button wire:click="cambiarTabSecundario('calendario_recepcion')" 
                    class="px-4 py-1 text-[9px] font-black uppercase tracking-wider transition-all border border-white/30 rounded-md
                    {{ $tab_secundario === 'calendario_recepcion' ? 'bg-white text-[#004b75] shadow-sm' : 'text-white hover:bg-white/10' }}">
                CALENDARIO RECEPCIÓN
            </button>
        @elseif($tab_principal === 'solicitudes')
            <button wire:click="cambiarTabSecundario('cumplimiento')" 
                    class="px-4 py-1 text-[9px] font-black uppercase tracking-wider transition-all border border-white/30 rounded-md
                    {{ $tab_secundario === 'cumplimiento' ? 'bg-white text-[#004b75] shadow-sm' : 'text-white hover:bg-white/10' }}">
                CUMPLIMIENTO
            </button>
            <button wire:click="cambiarTabSecundario('complementario')" 
                    class="px-4 py-1 text-[9px] font-black uppercase tracking-wider transition-all border border-white/30 rounded-md
                    {{ $tab_secundario === 'complementario' ? 'bg-white text-[#004b75] shadow-sm' : 'text-white hover:bg-white/10' }}">
                COMPLEMENTARIO
            </button>
            <button wire:click="cambiarTabSecundario('historial')" 
                    class="px-4 py-1 text-[9px] font-black uppercase tracking-wider transition-all border border-white/30 rounded-md
                    {{ $tab_secundario === 'historial' ? 'bg-white text-[#004b75] shadow-sm' : 'text-white hover:bg-white/10' }}">
                HISTORIAL
            </button>
        @endif
    </div>

    <div class="p-6">
        @if($tab_principal === 'inicio')
            @if($tab_secundario === 'informacion')
                <!-- VISTA INFORMACION -->
                <div class="max-w-6xl mx-auto space-y-6">
                    <div class="bg-[#fff3cd] border border-[#ffeeba] p-6 text-[#856404] text-[13px] font-bold leading-relaxed shadow-sm rounded-md">
                        En este sitio hay información confidencial. Utilizamos cifrado con el objetivo de mantener la privacidad de los datos de los usuarios mientras están en tránsito. Restringimos el acceso a la información personal para que solo pueda acceder quién corresponda. El INN podrá acceder a esta información en una eventual auditoría de acreditación como organismo verificador.
                    </div>
                </div>
            @elseif($tab_secundario === 'calendario_recepcion')
                <!-- VISTA CALENDARIO RECEPCION -->
                <div class="max-w-6xl mx-auto space-y-4">
                    <div class="bg-[#004b75] text-white p-3 text-center text-[11px] font-black uppercase tracking-[0.2em] shadow-md flex justify-center items-center gap-6 rounded-t-md">
                        FECHAS DE RECEPCIÓN CUMPLIMIENTO
                        <select wire:model.live="anio_seleccionado" wire:change="cargarCalendariosLegacy" class="bg-white text-[#004b75] border-none text-[11px] py-1 px-6 rounded-md font-black min-w-[100px] cursor-pointer hover:bg-gray-100 transition-colors">
                            <option value="2025">2025</option>
                            <option value="2026">2026</option>
                            <option value="2027">2027</option>
                        </select>
                    </div>

                    <div class="overflow-x-auto shadow-lg border border-gray-300">
                        <table class="min-w-full text-center border-collapse">
                            <thead>
                                <tr class="bg-[#004b75] text-white text-[9px] font-black uppercase tracking-wider">
                                    <th class="border border-white/20 p-2 w-32">PERIODO</th>
                                    <th colspan="2" class="border border-white/20 p-2">DENTRO DE PLAZO</th>
                                    <th class="border border-white/20 p-2 w-32">EMISIÓN<br>DENTRO DE PLAZO</th>
                                    <th colspan="2" class="border border-white/20 p-2">FUERA DE PLAZO</th>
                                    <th class="border border-white/20 p-2 w-32">EMISIÓN<br>FUERA DE PLAZO</th>
                                </tr>
                            </thead>
                            <tbody class="text-[10px] font-bold">
                                @forelse($calendarios_legacy as $cal)
                                    <tr class="border-b border-gray-300 h-8">
                                        <td class="bg-gray-100 border border-gray-300 uppercase px-2">{{ $this->getNombrePeriodoCal($cal->mes, $cal->anio) }}</td>
                                        <td class="bg-[#28a745] text-white border border-gray-300 px-2">{{ $cal->fecha_apertura ? $cal->fecha_apertura->format('d-m-Y') : '-' }}</td>
                                        <td class="bg-[#28a745] text-white border border-gray-300 px-2">{{ $cal->fecha_cierre ? $cal->fecha_cierre->format('d-m-Y') : '-' }}</td>
                                        <td class="bg-[#1e7e34] text-white border border-gray-300 px-2">{{ $cal->fecha_emision ? $cal->fecha_emision->format('d-m-Y') : '-' }}</td>
                                        <td class="bg-[#dc3545] text-white border border-gray-300 px-2">
                                            {{ $cal->fecha_cierre ? $cal->fecha_cierre->copy()->addDay()->format('d-m-Y') : '-' }}
                                        </td>
                                        <td class="bg-[#dc3545] text-white border border-gray-300 px-2">
                                            {{ $cal->fecha_cierre_fuera_plazo ? $cal->fecha_cierre_fuera_plazo->format('d-m-Y') : '-' }}
                                        </td>
                                        <td class="bg-[#bd2130] text-white border border-gray-300 px-2">{{ $cal->fecha_emision_fuera_plazo ? $cal->fecha_emision_fuera_plazo->format('d-m-Y') : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="p-8 text-gray-400 italic">No hay calendarios configurados para este año.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    <div class="mt-8 text-center space-y-2">
                        <p class="text-[11px] font-bold text-gray-700">Recuerde que para que su solicitud de cumplimiento quede dentro de plazo debe:</p>
                        <p class="text-[11px] text-gray-600 px-12 leading-relaxed">
                            - Presentar solicitud de cumplimiento del periodo correspondiente hasta la fecha de corte. En caso de presentar solicitud de periodos anteriores (atrasados), todos quedaran fuera de plazo.
                        </p>
                    </div>
                </div>
            @endif
        @elseif($tab_principal === 'solicitudes')
            @if($tab_secundario === 'cumplimiento')
                <!-- VISTA CUMPLIMIENTO (TARJETAS 4x3 ESTILO LEGACY ORIGINAL) -->
                <div class="max-w-6xl mx-auto space-y-6 animate-in fade-in duration-500">
                    
                    <!-- LEYENDA SUPERIOR ESTILO LEGACY -->
                    <div class="flex justify-center mb-8 w-full">
                        <div class="w-full border-2 border-gray-400 rounded-lg shadow-sm overflow-hidden flex text-[13px] font-black uppercase tracking-tight">
                            <div class="flex-1 flex justify-center items-center text-center px-2 py-3 bg-[#e9ecef] border-r border-gray-400 text-gray-500">No se puede iniciar periodo</div>
                            <div class="flex-1 flex justify-center items-center text-center px-2 py-3 bg-white border-r border-gray-400 text-gray-700">Periodo No Iniciado</div>
                            <div class="flex-1 flex justify-center items-center text-center px-2 py-3 bg-[#8ed973] border-r border-gray-400 text-[#003a5c]">Periodo Iniciado</div>
                            <div class="flex-1 flex justify-center items-center text-center px-2 py-3 bg-[#3b82f6] border-r border-gray-400 text-white">Periodo de Verif. Iniciado</div>
                            <div class="flex-1 flex justify-center items-center text-center px-2 py-3 bg-[#003a5c] text-white">Certificado Emitido</div>
                        </div>
                    </div>

                    <!-- CABECERA DE GRILLA CON SELECTOR -->
                    <div class="text-center mb-6 relative">
                        <div class="flex items-center justify-center gap-4">
                            <button wire:click="anteriorAnio" class="text-[#004b75] hover:scale-110 transition-transform"><svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M11.707 5.293L7.414 9.586a1 1 0 010 1.414l4.293 4.293a1 1 0 11-1.414 1.414L5.293 10.707a1 1 0 010-1.414l5-5a1 1 0 011.414 1.414z"/></svg></button>
                            <h2 class="text-[14px] font-black text-[#004b75] uppercase tracking-wider">
                                Solicitudes de certificación año {{ $anio_seleccionado }}
                            </h2>
                            <button wire:click="siguienteAnio" class="text-[#004b75] hover:scale-110 transition-transform"><svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M8.293 14.707l4.293-4.293a1 1 0 000-1.414l-4.293-4.293a1 1 0 00-1.414 1.414L11.586 10l-4.707 4.707a1 1 0 001.414 1.414z"/></svg></button>
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-4 px-10">
                        @foreach($grid_solicitudes as $mes => $data)
                            @if($data['estado'] !== 'BLOQUEADO')
                                <a href="{{ route('contratista.verificacion-legacy-carga', ['v' => $vinculacion_seleccionada_id, 'a' => $anio_seleccionado, 'm' => $data['mes']]) }}" 
                                   class="relative group transition-all duration-200 hover:shadow-xl hover:-translate-y-0.5 rounded-xl border-2 {{ $data['color'] }} p-4 shadow-md h-32 flex flex-col items-center justify-center border-gray-400 no-underline">
                                    <div class="text-[14px] font-black uppercase mb-2 tracking-tight {{ $data['text_color'] }}">
                                        {{ $data['nombre'] }} {{ $anio_seleccionado }}
                                    </div>
                                    <div class="text-[11px] font-bold text-center leading-tight {{ $data['text_color'] }} opacity-90">
                                        {{ $data['subtitulo'] }}
                                    </div>
                                    <div class="absolute inset-0 border-2 border-transparent group-hover:border-[#004b75]/40 rounded-xl pointer-events-none"></div>
                                </a>
                            @else
                                <div class="relative rounded-xl border-2 {{ $data['color'] }} p-4 shadow-sm h-32 flex flex-col items-center justify-center border-gray-300 cursor-not-allowed">
                                    <div class="text-[14px] font-black uppercase mb-2 tracking-tight {{ $data['text_color'] }}">
                                        {{ $data['nombre'] }} {{ $anio_seleccionado }}
                                    </div>
                                    <div class="text-[11px] font-bold text-center leading-tight {{ $data['text_color'] }} opacity-90">
                                        {{ $data['subtitulo'] }}
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @elseif($tab_secundario === 'complementario')
                <!-- VISTA COMPLEMENTARIO -->
                <div class="max-w-7xl mx-auto flex gap-6 animate-in fade-in duration-500 items-start">
                    
                    <!-- CONTENIDO PRINCIPAL (GRILLA O VISTA CENTRAL) -->
                    <div class="flex-1 bg-white border border-gray-300 rounded-md shadow-sm">
                        @if(!$mes_complementario_seleccionado)
                            <!-- GRILLA 12 MESES -->
                            <div class="p-4">
                                <div class="flex items-center justify-between mb-4 px-8">
                                    <button wire:click="anteriorAnio" class="text-[#00a2e8] hover:scale-110 transition-transform"><svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"><path d="M11.707 5.293L7.414 9.586a1 1 0 010 1.414l4.293 4.293a1 1 0 11-1.414 1.414L5.293 10.707a1 1 0 010-1.414l5-5a1 1 0 011.414 1.414z"/></svg></button>
                                    <h2 class="text-[17px] font-black text-gray-800 uppercase tracking-tighter text-center">
                                        Solicitudes de complementarios<br>año {{ $anio_seleccionado }}
                                    </h2>
                                    <button wire:click="siguienteAnio" class="text-[#00a2e8] hover:scale-110 transition-transform"><svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"><path d="M8.293 14.707l4.293-4.293a1 1 0 000-1.414l-4.293-4.293a1 1 0 00-1.414 1.414L11.586 10l-4.707 4.707a1 1 0 001.414 1.414z"/></svg></button>
                                </div>

                                <div class="grid grid-cols-4 gap-3">
                                    @foreach($grid_complementarios as $mes => $data)
                                        <button wire:click="seleccionarMesComplementario({{ $mes }})"
                                           class="relative rounded border border-gray-400 p-2 shadow-sm flex flex-col justify-between hover:shadow-md transition-all 
                                           {{ $data['color'] === 'bg-[#e9ecef]' ? 'bg-gray-100 opacity-60 cursor-not-allowed' : $data['color'] }}">
                                           
                                            <div class="text-[13px] font-black uppercase text-center w-full {{ $data['text_color'] }} border-b border-black/10 pb-1 mb-1">
                                                {{ $data['nombre'] }}
                                            </div>
                                            
                                            <table class="w-full text-center text-[9px] leading-tight {{ $data['text_color'] }} font-bold">
                                                <thead>
                                                    <tr>
                                                        <th class="w-1/3"></th>
                                                        <th class="w-2/9">Cant.</th>
                                                        <th class="w-2/9">Cor.</th>
                                                        <th class="w-2/9">Pend.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-left w-1/3 truncate" title="Contingencias Retenibles">C. Ret.</td>
                                                        <td>{{ $data['ret_cant'] }}</td>
                                                        <td>{{ $data['ret_cor'] }}</td>
                                                        <td>{{ $data['ret_pend'] }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-left w-1/3 truncate" title="Contingencias No Retenibles">C. No Ret.</td>
                                                        <td>{{ $data['noret_cant'] }}</td>
                                                        <td>{{ $data['noret_cor'] }}</td>
                                                        <td>{{ $data['noret_pend'] }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-left w-1/3 truncate" title="Observaciones">Observ.</td>
                                                        <td>{{ $data['observ_cant'] }}</td>
                                                        <td>{{ $data['observ_cor'] }}</td>
                                                        <td>{{ $data['observ_pend'] }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <!-- VISTA CENTRAL: DETALLE POR MES (TABS ROJO/NARANJA/AMARILLO) -->
                            <div class="p-0">
                                @php
                                    $allIncidencias = collect($incidencias_mes_central['retenibles'])
                                        ->concat($incidencias_mes_central['no_retenibles'])
                                        ->concat($incidencias_mes_central['observaciones']);
                                    
                                    $obsPendCount = collect($incidencias_mes_central['observaciones'])->where('subsanado', false)->count();
                                    $contPendCount = collect($incidencias_mes_central['retenibles'])->concat($incidencias_mes_central['no_retenibles'])->where('subsanado', false)->count();
                                    
                                    $obsTotalCount = count($incidencias_mes_central['observaciones']);
                                    $contTotalCount = count($incidencias_mes_central['retenibles']) + count($incidencias_mes_central['no_retenibles']);

                                    $montoTotalPendiente = collect($incidencias_mes_central['retenibles'])->concat($incidencias_mes_central['no_retenibles'])->where('subsanado', false)->sum('monto');
                                @endphp
                                <div class="bg-gray-100 p-3 flex justify-end items-center border-b border-gray-300">
                                    <button wire:click="cerrarCentralComplementario" class="text-sm font-bold text-gray-500 hover:text-gray-800 underline">Volver a Resumen</button>
                                </div>
                                <div class="p-4">
                                    <div class="flex justify-between items-center mb-6 px-4">
                                        <div class="text-[13px] font-bold text-gray-800 leading-snug flex-1 text-center">
                                            Detalle certificación {{ strtolower($grid_complementarios[$mes_complementario_seleccionado]['nombre']) }} de {{ $anio_seleccionado }}<br>
                                            Observaciones pendientes: {{ $obsPendCount }} / {{ $obsTotalCount }}<br>
                                            Contingencias pendientes: {{ $contPendCount }} / {{ $contTotalCount }}
                                            @if($contPendCount > 0)
                                                por un valor de $ {{ number_format($montoTotalPendiente, 0, ',', '.') }}
                                            @endif
                                        </div>
                                        <div class="flex-shrink-0">
                                            <button wire:click="consolidarEnSolicitud"
                                                    wire:loading.attr="disabled"
                                                    wire:target="consolidarEnSolicitud"
                                                    class="bg-[#3b82f6] hover:bg-blue-600 text-white font-bold py-2 px-4 rounded shadow-sm text-[12px] flex items-center gap-2 disabled:opacity-60">
                                                <span wire:loading.remove wire:target="consolidarEnSolicitud">Agregar Códigos Seleccionados</span>
                                                <span wire:loading wire:target="consolidarEnSolicitud">Procesando...</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- ACORDEON ROJO -->
                                    @if(count($incidencias_mes_central['retenibles']) > 0)
                                    @php $rPendCount = collect($incidencias_mes_central['retenibles'])->where('subsanado', false)->count(); @endphp
                                    <div class="border {{ $rPendCount > 0 ? 'border-red-500' : 'border-[#28a745]' }} rounded mb-4 overflow-hidden shadow-sm">
                                        <div class="{{ $rPendCount > 0 ? 'bg-red-500' : 'bg-[#28a745]' }} text-white text-center py-1 font-black text-[12px] uppercase">
                                            {{ $rPendCount > 0 ? 'CONTINGENCIAS RETENIBLES POR SOLUCIONAR' : 'CONTINGENCIAS RETENIBLES SOLUCIONADAS' }}
                                        </div>
                                        <table class="w-full text-center text-[10px]">
                                            <thead class="bg-gray-100 text-gray-700 font-bold border-b border-red-200">
                                                <tr>
                                                    <th class="py-1 px-1">Nº</th>
                                                    <th class="py-1 px-1">TRABS.</th>
                                                    <th class="py-1 px-1 w-3/5">DETALLE</th>
                                                    <th class="py-1 px-1">VALOR</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($incidencias_mes_central['retenibles'] as $index => $det)
                                                    <tr class="border-b border-gray-200">
                                                        <td class="py-2">{{ $index + 1 }}</td>
                                                        <td class="py-2">1</td>
                                                        <td class="py-2 text-left leading-tight">
                                                            <strong class="text-red-700 text-[11px] mb-1 inline-block">CÓDIGO ({{ $det['codigo'] }})</strong>
                                                            @if($det['historial_label'])
                                                                <span class="ml-2 text-[9px] bg-red-100 text-red-800 px-1.5 py-0.5 rounded border border-red-200 uppercase">{{ $det['historial_label'] }}</span>
                                                            @endif
                                                            <br>
                                                            <span class="text-gray-600 block mb-2">{{ $det['causal'] }}</span>
                                                            <div class="flex justify-between items-center border-t border-gray-300 pt-2 w-full">
                                                                <span class="font-bold text-gray-800 text-[11px]">{{ $det['trabajador_rut'] }} - {{ $det['trabajador_nombre'] }}</span>
                                                            </div>
                                                        </td>
                                                        <td class="py-2 font-black text-gray-800 text-[12px] align-bottom pb-3 text-right pr-4">
                                                            ${{ number_format($det['monto'] ?? 0, 0, ',', '.') }}
                                                            @if(($det['monto'] ?? 0) != ($det['monto_original'] ?? 0))
                                                                <div class="text-[9px] text-gray-400 font-normal italic mt-1 leading-none">Orig: ${{ number_format($det['monto_original'] ?? 0, 0, ',', '.') }}</div>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @include('livewire.contratista._codigo_accion_row', ['det' => $det])
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @endif

                                    <!-- ACORDEON NARANJA -->
                                    @if(count($incidencias_mes_central['no_retenibles']) > 0)
                                    @php $nrPendCount = collect($incidencias_mes_central['no_retenibles'])->where('subsanado', false)->count(); @endphp
                                    <div class="border {{ $nrPendCount > 0 ? 'border-orange-400' : 'border-[#28a745]' }} rounded mb-4 overflow-hidden shadow-sm">
                                        <div class="{{ $nrPendCount > 0 ? 'bg-orange-400' : 'bg-[#28a745]' }} text-white text-center py-1 font-black text-[12px] uppercase">
                                            {{ $nrPendCount > 0 ? 'CONTINGENCIAS NO RETENIBLES POR SOLUCIONAR' : 'CONTINGENCIAS NO RETENIBLES SOLUCIONADAS' }}
                                        </div>
                                        <table class="w-full text-center text-[10px]">
                                            <thead class="bg-gray-100 text-gray-700 font-bold border-b border-orange-200">
                                                <tr>
                                                    <th class="py-1 px-1">Nº</th>
                                                    <th class="py-1 px-1">TRABS.</th>
                                                    <th class="py-1 px-1 w-3/5">DETALLE</th>
                                                    <th class="py-1 px-1">VALOR</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($incidencias_mes_central['no_retenibles'] as $index => $det)
                                                    <tr class="border-b border-gray-200">
                                                        <td class="py-2">{{ $index + 1 }}</td>
                                                        <td class="py-2">1</td>
                                                        <td class="py-2 text-left leading-tight">
                                                            <strong class="text-orange-600 text-[11px] mb-1 inline-block">CÓDIGO ({{ $det['codigo'] }})</strong>
                                                            @if($det['historial_label'])
                                                                <span class="ml-2 text-[9px] bg-orange-100 text-orange-800 px-1.5 py-0.5 rounded border border-orange-200 uppercase">{{ $det['historial_label'] }}</span>
                                                            @endif
                                                            <br>
                                                            <span class="text-gray-600 block mb-2">{{ $det['causal'] }}</span>
                                                            <div class="flex justify-between items-center border-t border-gray-300 pt-2 w-full">
                                                                <span class="font-bold text-gray-800 text-[11px]">{{ $det['trabajador_rut'] }} - {{ $det['trabajador_nombre'] }}</span>
                                                            </div>
                                                        </td>
                                                        <td class="py-2 font-black text-gray-800 text-[12px] align-bottom pb-3 text-right pr-4">
                                                            ${{ number_format($det['monto'] ?? 0, 0, ',', '.') }}
                                                            @if(($det['monto'] ?? 0) != ($det['monto_original'] ?? 0))
                                                                <div class="text-[9px] text-gray-400 font-normal italic mt-1 leading-none">Orig: ${{ number_format($det['monto_original'] ?? 0, 0, ',', '.') }}</div>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @include('livewire.contratista._codigo_accion_row', ['det' => $det])
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @endif

                                    <!-- ACORDEON AMARILLO -->
                                    @if(count($incidencias_mes_central['observaciones']) > 0)
                                    @php $oPendCount = collect($incidencias_mes_central['observaciones'])->where('subsanado', false)->count(); @endphp
                                    <div class="border {{ $oPendCount > 0 ? 'border-yellow-400' : 'border-[#28a745]' }} rounded overflow-hidden shadow-sm">
                                        <div class="{{ $oPendCount > 0 ? 'bg-yellow-400 text-gray-800' : 'bg-[#28a745] text-white' }} text-center py-1 font-black text-[12px] uppercase">
                                            {{ $oPendCount > 0 ? 'OBSERVACIONES POR SOLUCIONAR' : 'OBSERVACIONES SOLUCIONADAS' }}
                                        </div>
                                        <table class="w-full text-center text-[10px]">
                                            <thead class="bg-gray-100 text-gray-700 font-bold border-b border-yellow-200">
                                                <tr>
                                                    <th class="py-1 px-1">Nº</th>
                                                    <th class="py-1 px-1">TRABS.</th>
                                                    <th class="py-1 px-1 w-3/5">DETALLE</th>
                                                    <th class="py-1 px-1">VALOR</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($incidencias_mes_central['observaciones'] as $index => $det)
                                                    <tr class="border-b border-gray-200">
                                                        <td class="py-2">{{ $index + 1 }}</td>
                                                        <td class="py-2">1</td>
                                                        <td class="py-2 text-left leading-tight">
                                                            <strong class="text-yellow-600 text-[11px] mb-1 inline-block">CÓDIGO ({{ $det['codigo'] }})</strong>
                                                            @if($det['historial_label'])
                                                                <span class="ml-2 text-[9px] bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded border border-yellow-200 uppercase">{{ $det['historial_label'] }}</span>
                                                            @endif
                                                            <br>
                                                            <span class="text-gray-600 block mb-2">{{ $det['causal'] }}</span>
                                                            <div class="flex justify-between items-center border-t border-gray-300 pt-2 w-full">
                                                                <span class="font-bold text-gray-800 text-[11px]">{{ $det['trabajador_rut'] }} - {{ $det['trabajador_nombre'] }}</span>
                                                            </div>
                                                        </td>
                                                        <td class="py-2 font-black text-gray-800 text-[12px] align-bottom pb-3">-</td>
                                                    </tr>
                                                    @include('livewire.contratista._codigo_accion_row', ['det' => $det])
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @endif

                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- BANDEJA PERSISTENTE DERECHA: SOLICITUDES CONSOLIDADAS (1 fila por paquete) -->
                    <div class="w-[340px] flex-shrink-0 bg-white border border-gray-300 rounded-md shadow-sm sticky top-4">
                        <div class="p-3 text-center border-b border-gray-300">
                            <h3 class="text-[15px] font-black text-[#004b75] uppercase leading-tight">Detalle de Solicitud</h3>
                            <div class="text-[12px] font-bold text-gray-700 flex justify-between px-2 mt-1">
                                <span>En Bandeja</span>
                                <span>: {{ count($bandeja_complementarios) }}</span>
                            </div>
                        </div>
                        <div class="divide-y divide-gray-200 max-h-[600px] overflow-y-auto">
                            @if(empty($bandeja_complementarios))
                                <div class="py-8 px-4 text-center text-[11px] text-gray-500 italic">
                                    No hay solicitudes complementarias activas. Seleccione Códigos en los meses para agregarlos acá.
                                </div>
                            @else
                                @foreach($bandeja_complementarios as $sol)
                                    <div class="p-3 hover:bg-gray-50 transition-colors">
                                        <div class="flex justify-between items-start mb-1.5 border-b border-gray-100 pb-2">
                                            <div class="w-full">
                                                <div class="flex justify-between items-center w-full">
                                                    <div class="text-[11px] font-black text-[#004b75] uppercase leading-none">{{ $sol['mes_nombre'] }} {{ $sol['anio'] }}</div>
                                                    <div>
                                                        @if($sol['visual'] === 'sin_docs')
                                                            <span class="text-[8px] bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-black border border-gray-300">SIN DOCS</span>
                                                        @elseif($sol['visual'] === 'borrador')
                                                            <span class="text-[8px] bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-black border border-amber-300">BORRADOR</span>
                                                        @elseif($sol['visual'] === 'enviado')
                                                            <span class="text-[8px] bg-[#004b75] text-white px-2 py-0.5 rounded-full font-black">EN REVISIÓN</span>
                                                        @elseif($sol['visual'] === 'rechazado')
                                                            <span class="text-[8px] bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-black border border-red-300">REVISADO</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="text-[10px] text-gray-800 font-black uppercase mt-1.5 flex items-center gap-1.5">
                                                    <span class="bg-gray-900 leading-tight text-yellow-400 font-mono tracking-tighter px-1.5 py-0.5 rounded shadow-inner">
                                                        {{ $sol['folio_sc'] }}
                                                    </span>
                                                    <span class="text-[#004b75] font-mono tracking-tighter">{{ $sol['folio'] }}</span>
                                                    <span class="text-gray-300">|</span>
                                                    <span class="text-gray-600 font-bold">{{ $sol['n_documentos'] }} doc(s)</span>
                                                </div>
                                                <div class="text-[9px] text-gray-500 font-bold uppercase overflow-hidden whitespace-nowrap text-ellipsis mt-1 py-1 px-1.5 bg-gray-50 rounded border border-gray-100" title="{{ $sol['lugar'] }} - CT: {{ $sol['contrato'] }}">
                                                    📍 {{ $sol['lugar'] }} <span class="text-gray-400 font-normal ml-1">CT: {{ $sol['contrato'] }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Píldoras de Códigos con Tooltip -->
                                        <div class="flex flex-wrap gap-1 mt-2 mb-1">
                                            @foreach($sol['detalles_codigos'] as $dc)
                                                @php
                                                    $bgClass = $dc['tipo'] === 'observacion' ? 'bg-yellow-100 text-yellow-800 border-yellow-200' : 
                                                              ($dc['subtipo'] === 'retenible' ? 'bg-red-100 text-red-800 border-red-200' : 'bg-orange-100 text-orange-800 border-orange-200');
                                                    $montoFmt = $dc['monto'] > 0 ? ' - $' . number_format($dc['monto'], 0, ',', '.') : '';
                                                @endphp
                                                <span class="text-[9px] font-black px-1.5 py-0.5 rounded cursor-help {{ $bgClass }} border transition-all hover:brightness-95" 
                                                      title="{{ $dc['causal'] }}{{ $montoFmt }}">
                                                    {{ $dc['codigo'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                        @if($sol['visual'] === 'rechazado' && !empty($sol['observaciones']))
                                            <div class="mt-1 p-1.5 bg-red-50 border border-red-100 rounded text-[9px] text-red-700 italic leading-tight">
                                                <strong>Obs. Auditor:</strong> {{ $sol['observaciones'] }}
                                            </div>
                                        @endif
                                        <button wire:click="abrirModalPaquete({{ $sol['solicitud_id'] }})"
                                                class="mt-2 w-full py-1.5 text-[10px] font-black uppercase rounded border transition-all
                                                @if($sol['visual'] === 'rechazado')
                                                    bg-red-500 text-white border-red-600 hover:bg-red-600
                                                @elseif(in_array($sol['visual'], ['sin_docs', 'borrador']))
                                                    bg-[#004b75] text-white border-[#003a5c] hover:bg-[#003a5c]
                                                @else
                                                    bg-white text-gray-500 border-gray-300 hover:bg-gray-100
                                                @endif">
                                            @if($sol['visual'] === 'rechazado') 👁️ VER COMPLEMENTARIO
                                            @elseif($sol['visual'] === 'borrador') 📂 Retomar / Enviar
                                            @elseif($sol['visual'] === 'sin_docs') 📁 Cargar Documentos
                                            @else 👁️ VER COMPLEMENTARIO
                                            @endif
                                        </button>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                <!-- MODAL PAQUETE COMPLEMENTARIO (flujo consolidado 1 solicitud → N códigos) -->
                @if($modal_paquete_abierto)
                    <div class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/40 backdrop-blur-sm transition-opacity">
                        <div class="bg-white rounded-lg shadow-2xl w-full max-w-3xl border-2 border-[#004b75] flex flex-col max-h-[90vh] overflow-hidden">

                            <!-- Header Modal -->
                            <div class="bg-gradient-to-r from-[#004b75] to-[#015a8c] px-6 py-4 flex justify-between items-center text-white flex-shrink-0 border-b border-white/10 shadow-lg">
                                <div class="flex items-center gap-4">
                                    <div class="bg-white/10 p-2.5 rounded-xl backdrop-blur-md border border-white/20 shadow-inner">
                                        <svg class="w-6 h-6 text-yellow-400 drop-shadow-sm" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <h2 class="text-[17px] font-black tracking-tight leading-none mb-1.5 flex items-center gap-2">
                                            SOLICITUD <span class="text-yellow-400 font-mono tracking-tighter">{{ $paquete_folio_complementario }}</span>
                                            @if($solo_lectura_modal)
                                                <span class="text-[9px] bg-red-400/20 text-red-200 px-2 py-0.5 rounded-full border border-red-400/20 ml-2">SOLO LECTURA</span>
                                            @endif
                                        </h2>
                                        <div class="flex items-center gap-2.5 text-[10px] font-black text-blue-100 tracking-wider uppercase opacity-90">
                                            <span class="bg-[#003a5c] px-2 py-0.5 rounded text-white border border-white/10">{{ strtoupper($grid_complementarios[$mes_complementario_seleccionado]['nombre'] ?? '?') }} {{ $anio_seleccionado }}</span>
                                            <span class="text-white/40">|</span>
                                            <span class="flex items-center gap-1">CERT: <span class="text-white font-mono">{{ $paquete_folio_certificado }}</span></span>
                                            <span class="text-white/40">|</span>
                                            <span class="text-white">📍 {{ $paquete_lugar_contrato }}</span>
                                        </div>
                                    </div>
                                </div>
                                <button wire:click="cerrarModalPaquete" class="hover:bg-white/20 p-2 rounded-xl transition-all hover:rotate-90 duration-300 group">
                                    <svg class="w-5 h-5 text-white/70 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>

                            <div class="p-6 space-y-5 overflow-y-auto custom-scrollbar flex-grow">
                                @if(session()->has('error_paquete'))
                                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded text-[13px] font-bold text-center">
                                        {{ session('error_paquete') }}
                                    </div>
                                @endif

                                <!-- Tabla de códigos incluidos en el paquete -->
                                <div>
                                    <div class="bg-[#004b75] px-4 py-1.5 text-[11px] font-black text-white uppercase rounded-t tracking-wider">
                                        CÓDIGOS INCLUIDOS EN ESTE COMPLEMENTARIO
                                    </div>
                                    <table class="w-full text-[10px] border border-gray-300 rounded-b overflow-hidden">
                                        <thead class="bg-gray-100 text-gray-700 font-bold">
                                            <tr>
                                                <th class="py-1.5 px-2 text-left border-b border-gray-200 w-20">Código</th>
                                                <th class="py-1.5 px-2 text-left border-b border-gray-200">Trabajador</th>
                                                <th class="py-1.5 px-2 text-center border-b border-gray-200 w-16">Tipo</th>
                                                <th class="py-1.5 px-2 text-right border-b border-gray-200 w-20">Valor</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($items_paquete as $item)
                                                <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                                    <td title="{{ $item['causal'] }}" class="py-1.5 px-2 font-black cursor-help {{ $item['tipo'] === 'observacion' ? 'text-yellow-600' : (($item['subtipo'] ?? '') === 'retenible' ? 'text-red-600' : 'text-orange-500') }}">
                                                        {{ $item['codigo'] }}
                                                    </td>
                                                    <td class="py-1.5 px-2 text-gray-700">
                                                        <span class="font-bold">{{ $item['trabajador_rut'] }}</span>
                                                        <span class="text-gray-500 ml-1">{{ $item['trabajador_nombre'] }}</span>
                                                    </td>
                                                    <td class="py-1.5 px-2 text-center">
                                                        @if($item['tipo'] === 'observacion')
                                                            <span class="bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded text-[9px] font-bold">OBS</span>
                                                        @elseif(($item['subtipo'] ?? '') === 'retenible')
                                                            <span class="bg-red-100 text-red-800 px-1.5 py-0.5 rounded text-[9px] font-bold">RET</span>
                                                        @else
                                                            <span class="bg-orange-100 text-orange-800 px-1.5 py-0.5 rounded text-[9px] font-bold">NO RET</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-1.5 px-2 text-right font-black text-gray-800">
                                                        {{ $item['monto_original'] ? '$' . number_format($item['monto_original'], 0, ',', '.') : '–' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Sección de documentos (aplican al paquete completo) -->
                                <div class="space-y-3">
                                    <div class="bg-gray-50 px-4 py-2 text-[11px] font-black text-[#004b75] uppercase border border-gray-200 rounded tracking-wider">
                                        DOCUMENTOS COMPLEMENTARIOS (aplican a todos los códigos)
                                    </div>
                                    @foreach($requisitos_agrupados_complementario as $catNombre => $reqs)
                                        <div class="border border-gray-200 rounded-lg overflow-hidden shadow-sm">
                                            <div class="bg-gray-100 px-4 py-1.5 text-[11px] font-black text-[#004b75] uppercase border-b border-gray-200 tracking-wider">
                                                {{ $catNombre }}
                                            </div>
                                            <div class="bg-white divide-y divide-gray-100">
                                                @foreach($reqs as $req)
                                                    <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                                                        <div>
                                                            <div class="text-[12px] font-black text-gray-800 uppercase">{{ $req['nombre'] }}</div>
                                                            <div class="text-[10px] text-gray-500 italic">{{ $req['descripcion'] }}</div>
                                                        </div>
                                                        <div class="flex flex-col gap-2">
                                                            @if(!$solo_lectura_modal)
                                                            <div class="flex items-center gap-3">
                                                                <label class="cursor-pointer bg-blue-50 hover:bg-blue-100 border border-blue-200 px-3 py-1.5 rounded text-[10px] font-bold text-blue-700 uppercase transition-all shadow-sm flex items-center gap-2">
                                                                    <svg wire:loading.remove wire:target="archivos.{{ $req['id'] }}" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                                    <span wire:loading.remove wire:target="archivos.{{ $req['id'] }}">+ CARGAR PDF</span>
                                                                    <div wire:loading wire:target="archivos.{{ $req['id'] }}" class="flex items-center gap-2">
                                                                        <svg class="animate-spin h-3 w-3 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                                        <span>SUBIENDO...</span>
                                                                    </div>
                                                                    <input type="file" wire:model.live="archivos.{{ $req['id'] }}" multiple accept=".pdf" class="hidden">
                                                                </label>
                                                                @if(isset($archivos[$req['id']]) && count($archivos[$req['id']]) > 0)
                                                                    <span class="text-[10px] font-black text-green-600 bg-green-50 px-2 py-1 rounded border border-green-200 shadow-sm animate-pulse">
                                                                        ✓ {{ count($archivos[$req['id']]) }} LISTO{{ count($archivos[$req['id']]) > 1 ? 'S' : '' }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            @endif
                                                            @if(isset($archivos[$req['id']]))
                                                                <div class="space-y-1.5 mt-2">
                                                                    @foreach($archivos[$req['id']] as $file)
                                                                        <div class="flex items-center text-[10px] bg-amber-50 p-2 rounded border border-amber-200 shadow-sm animate-pulse">
                                                                            <svg class="w-4 h-4 text-amber-500 mr-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L14 2.586A2 2 0 0012.586 2H9z"/></svg>
                                                                            <span class="truncate text-amber-900 font-black uppercase tracking-tighter" title="{{ $file->getClientOriginalName() }}">
                                                                                Pte. Carga: {{ $file->getClientOriginalName() }}
                                                                            </span>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                            
                                                            {{-- LISTAR DOCUMENTOS YA CARGADOS (Borrador persistido) --}}
                                                            @if(isset($documentos_ya_cargados[$req['id']]) && count($documentos_ya_cargados[$req['id']]) > 0)
                                                                <div class="space-y-1.5 mt-3 p-2.5 bg-[#004b75]/5 rounded-xl border-2 border-dashed border-[#004b75]/20 shadow-inner">
                                                                    <div class="text-[9px] font-black uppercase text-[#004b75] mb-2 flex items-center gap-1.5 ml-1">
                                                                        <span class="w-1.5 h-1.5 bg-[#004b75] rounded-full"></span>
                                                                        PERSISTIDO EN BORRADOR
                                                                    </div>
                                                                    @foreach($documentos_ya_cargados[$req['id']] as $docGuardado)
                                                                        <div class="flex items-center justify-between text-[10px] bg-white p-2 rounded-lg border border-gray-200 hover:border-[#004b75]/40 group transition-all shadow-sm">
                                                                            <div class="flex items-center truncate">
                                                                                <div class="bg-blue-50 p-1.5 rounded-md mr-3 group-hover:bg-[#004b75]/10 transition-colors">
                                                                                    <svg class="w-4 h-4 text-[#004b75]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
                                                                                </div>
                                                                                <span class="truncate max-w-[180px] text-gray-800 font-black uppercase tracking-tight" title="{{ $docGuardado['nombre_archivo'] }}">
                                                                                    {{ $docGuardado['nombre_archivo'] }}
                                                                                </span>
                                                                            </div>
                                                                            @if(!$solo_lectura_modal)
                                                                            <button wire:click="eliminarDocumentoPaquete({{ $docGuardado['id'] }})"
                                                                                    class="bg-gray-50 text-gray-400 hover:bg-red-50 hover:text-red-600 p-1.5 rounded-lg transition-all opacity-0 group-hover:opacity-100 shadow-sm border border-transparent hover:border-red-100" title="Eliminar documento">
                                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                                            </button>
                                                                            @endif
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Footer Modal: GUARDAR BORRADOR + FINALIZAR Y ENVIAR -->
                            <div class="bg-gray-100 px-6 py-4 border-t border-gray-300 flex justify-between items-center flex-shrink-0">
                                <div class="text-[11px] text-gray-500 font-bold italic">
                                    💾 Guarde para volver más tarde o Envíe cuando esté listo.
                                </div>
                                <div class="flex gap-2">
                                    <button wire:click="cerrarModalPaquete"
                                            class="px-4 py-2 bg-white border border-gray-400 text-gray-700 text-[12px] font-bold rounded shadow-sm hover:bg-gray-50 focus:outline-none transition-all">
                                        @if($solo_lectura_modal) CERRAR @else CANCELAR @endif
                                    </button>
                                    @if(!$solo_lectura_modal)
                                    <button wire:click="guardarBorradorComplementario"
                                            wire:loading.attr="disabled"
                                            wire:target="archivos, guardarBorradorComplementario"
                                            class="px-4 py-2 bg-gray-700 text-white text-[12px] font-black uppercase rounded shadow hover:bg-gray-800 transition-all flex items-center gap-2">
                                        <span wire:loading.remove wire:target="guardarBorradorComplementario">💾 GUARDAR</span>
                                        <span wire:loading wire:target="guardarBorradorComplementario">GUARDANDO...</span>
                                    </button>
                                    <button wire:click="abrirModalConfirmacionComplementario"
                                            wire:loading.attr="disabled"
                                            wire:target="archivos, abrirModalConfirmacionComplementario"
                                            class="px-4 py-2 bg-[#8ed973] border border-[#7bc85f] text-[#003a5c] text-[13px] font-black uppercase rounded shadow-lg hover:brightness-105 transition-all flex items-center gap-2">
                                        <span wire:loading.remove wire:target="abrirModalConfirmacionComplementario">🚀 FINALIZAR Y ENVIAR</span>
                                        <span wire:loading wire:target="abrirModalConfirmacionComplementario">PROCESANDO...</span>
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        @endif
    </div>

    <!-- ESTILOS ADICIONALES -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@400;700&display=swap');
        .legacy-wrapper, .legacy-wrapper * { font-family: 'Roboto Condensed', sans-serif !important; }
        
        /* Ajustes específicos para que la grilla no se rompa */
        .legacy-wrapper select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23004b75' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 0.8em;
            padding-right: 1.5rem;
        }
    </style>
    <!-- MODAL DE DECLARACIÓN DE VERACIDAD (INN) -->
    @if($modal_confirmacion_visible)
        <div class="fixed inset-0 z-[1001] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
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
                                <button wire:click="finalizarYEnviarComplementario" 
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
</div>
