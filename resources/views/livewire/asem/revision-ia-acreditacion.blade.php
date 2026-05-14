<div class="p-6 space-y-6">

    {{-- ═══════════════════════════════════════════════════════════════
         CABECERA
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <span class="text-2xl">🤖</span> Revisión IA — Acreditación
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Extracción y validación automática de documentos de acreditación mediante Inteligencia Artificial.
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <select wire:model.live="modeloIaSeleccionado"
                    class="border-gray-300 rounded-lg text-sm font-medium focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-700 py-2">
                <option value="google/gemini-2.5-flash">🤖 Gemini 2.5 Flash (Google)</option>
                <option value="google/gemini-2.5-flash-lite">🤖 Gemini 2.5 Flash Lite (Rápido)</option>
                <option value="google/gemini-3.1-flash-lite-preview">🚀 Gemini 3.1 Flash Lite (Preview)</option>
                <option value="anthropic/claude-3-haiku">🟣 Claude 3 Haiku (Anthropic)</option>
                <option value="meta-llama/llama-3.2-11b-vision-instruct">🦙 Llama 3.2 11B Vision (Meta)</option>
                <option value="qwen/qwen2.5-vl-72b-instruct">🐉 Qwen 2.5 VL 72B (Alibaba)</option>
                <option value="nvidia/nemotron-nano-12b-v2-vl:free">🟩 NVIDIA Nemotron Nano (Free)</option>
                <option value="google/gemma-4-31b-it">💎 Gemma 4 31B (Google)</option>
                <option value="google/gemma-4-26b-a4b-it:free">💎 Gemma 4 26B A4B (Free)</option>
            </select>
            
            <button wire:click="abrirModalExcel"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition">
                📥 Subir Excel
            </button>
            @if(!empty($seleccionados))
            <button wire:click="enviarSeleccionadosAIa"
                    wire:confirm="¿Enviar {{ count($seleccionados) }} documentos a la IA?"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                🚀 Enviar {{ count($seleccionados) }} a IA
            </button>
            @endif
            <button wire:click="confirmarTodosAprobados"
                    wire:confirm="¿Confirmar todos los documentos con resultado APROBADO por la IA?"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                ✅ Confirmar todos APROBADOS
            </button>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         PESTAÑAS
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="flex gap-1 border-b border-gray-200">
        <button wire:click="$set('pestana','documentos')"
                class="px-5 py-2.5 text-sm font-medium rounded-t-lg transition
                       {{ $pestana === 'documentos'
                           ? 'bg-white border border-b-white border-gray-200 text-indigo-700'
                           : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
            📄 Documentos
        </button>
        <button wire:click="$set('pestana','configuracion')"
                class="px-5 py-2.5 text-sm font-medium rounded-t-lg transition
                       {{ $pestana === 'configuracion'
                           ? 'bg-white border border-b-white border-gray-200 text-indigo-700'
                           : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
            ⚙️ Configuración de campos por Regla
        </button>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         MENSAJES GLOBALES
    ═══════════════════════════════════════════════════════════════ --}}
    @if($mensajeExito)
    <div class="flex items-center gap-3 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
        <span>✅</span> <span>{{ $mensajeExito }}</span>
    </div>
    @endif
    @if($mensajeError)
    <div class="flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
        <span>❌</span> <span>{{ $mensajeError }}</span>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════
         PESTAÑA: CONFIGURACIÓN DE CAMPOS POR REGLA
    ═══════════════════════════════════════════════════════════════ --}}
    @if($pestana === 'configuracion')
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-6">

        <div>
            <h2 class="text-base font-semibold text-gray-900 mb-1">⚙️ Configuración de campos IA por Regla Documental</h2>
            <p class="text-sm text-gray-500">
                Selecciona qué columnas de <code class="bg-gray-100 px-1 rounded text-xs">documentos_cargados</code>
                debe extraer la IA para cada regla. La regla documental <strong>no se modifica</strong> en ningún caso.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-3xl">
            {{-- Filtro por Principal --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Filtro por Principal (Opcional)</label>
                <select wire:model.live="configMandanteId"
                        class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">— Todos los Principales —</option>
                    @foreach($mandantes as $m)
                    <option value="{{ $m->id }}">{{ $m->razon_social }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Selector de regla --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Regla Documental</label>
                <select wire:model.live="configReglaId"
                        class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">— Selecciona una regla —</option>
                    @foreach($reglas as $regla)
                    <option value="{{ $regla->id }}">{{ $regla->nombre_documento }} (ID: {{ $regla->id }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($configReglaId)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- Campos disponibles (columnas) --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                    Columnas a extraer
                    <span class="text-xs font-normal text-gray-400 ml-2">(documentos_cargados)</span>
                </h3>

                <div class="space-y-3">
                    @foreach($camposDisponibles as $campo)
                    <div class="flex items-start gap-4 p-4 rounded-lg border
                        {{ in_array($campo['campo_clave'], $configCamposActivos) ? 'bg-indigo-50 border-indigo-200' : 'bg-gray-50 border-gray-200' }}">

                        <div class="flex items-center pt-0.5">
                            <input type="checkbox"
                                   id="campo_{{ $campo['campo_clave'] }}"
                                   wire:model.live="configCamposActivos"
                                   value="{{ $campo['campo_clave'] }}"
                                   class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        </div>

                        <div class="flex-1">
                            <label for="campo_{{ $campo['campo_clave'] }}"
                                   class="text-sm font-semibold text-gray-900 cursor-pointer">
                                {{ $campo['etiqueta'] }}
                                <code class="ml-2 text-xs font-normal bg-gray-200 px-1.5 py-0.5 rounded text-gray-600">
                                    {{ $campo['campo_clave'] }}
                                </code>
                                @if($campo['mapea_columna'])
                                <span class="ml-1 text-xs text-indigo-500 block sm:inline mt-1 sm:mt-0">→ escribe en {{ $campo['mapea_columna'] }}</span>
                                @else
                                <span class="ml-1 text-xs text-gray-400 block sm:inline mt-1 sm:mt-0">→ solo comparación</span>
                                @endif
                            </label>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $campo['descripcion'] }}</p>
                        </div>

                        @if(in_array($campo['campo_clave'], $configCamposActivos))
                        <div class="flex items-center gap-2 text-xs text-gray-600 pt-1">
                            <input type="checkbox"
                                   id="req_{{ $campo['campo_clave'] }}"
                                   wire:model.live="configRequeridos"
                                   value="{{ $campo['campo_clave'] }}"
                                   class="h-3.5 w-3.5 text-red-500 border-gray-300 rounded">
                            <label for="req_{{ $campo['campo_clave'] }}" class="cursor-pointer">Requerido</label>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Criterios de la regla --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                    Criterios a extraer
                    <span class="text-xs font-normal text-gray-400 ml-2">(comparación con match)</span>
                </h3>

                @if($this->criteriosRegla->isEmpty())
                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-500 italic">
                    Esta regla no tiene criterios de evaluación configurados.
                </div>
                @else
                <div class="space-y-3">
                    @foreach($this->criteriosRegla as $rc)
                    @php 
                        $criterioId = (string)$rc->criterio_evaluacion_id; 
                        $isActive = in_array($criterioId, $configCriteriosActivos);
                        $pautaCustom = $configCriteriosValoresEsperados[$criterioId] ?? null;
                        $valoresEsperados = $pautaCustom ?: $rc->subCriterios->where('is_active', true)->pluck('nombre')->filter()->implode(', ');
                    @endphp
                    <div class="flex items-start gap-4 p-4 rounded-lg border
                        {{ $isActive ? 'bg-purple-50 border-purple-200' : 'bg-gray-50 border-gray-200' }}">

                        <div class="flex items-center pt-0.5">
                            <input type="checkbox"
                                   id="crit_{{ $criterioId }}"
                                   wire:model.live="configCriteriosActivos"
                                   value="{{ $criterioId }}"
                                   class="h-4 w-4 text-purple-600 border-gray-300 rounded">
                        </div>

                        <div class="flex-1 space-y-2">
                            <label for="crit_{{ $criterioId }}"
                                   class="text-sm font-semibold text-gray-900 cursor-pointer flex flex-wrap items-center gap-2">
                                {{ $rc->criterioEvaluacion?->nombre_criterio }}
                                <span class="text-xs font-normal bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded">Criterio</span>
                            </label>
                            
                            @if($rc->aclaracionCriterio)
                            <p class="text-xs text-gray-500"><span class="font-medium text-gray-700">Aclaración original:</span> {{ $rc->aclaracionCriterio->titulo }}</p>
                            @endif
                            
                            <p class="text-xs text-green-600 font-medium">
                                Pauta oficial del sistema: La IA debe responder "SÍ".
                            </p>

                            @if($isActive)
                            <div class="pt-2 border-t border-gray-200/60 mt-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    Instrucción para la IA (Texto a extraer)
                                </label>
                                <textarea wire:model.live.debounce.500ms="configCriteriosDescripciones.{{ $criterioId }}"
                                          rows="2"
                                          placeholder="Ej: Extraer el cargo indicado en el documento"
                                          class="w-full text-sm border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500 mb-2"></textarea>
                                          
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    Formato de Muestra (Opcional - Envía la imagen de referencia a la IA)
                                </label>
                                <select wire:model="configCriteriosFormatos.{{ $criterioId }}"
                                        class="w-full text-sm border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500 text-gray-600">
                                    <option value="">— Sin formato de muestra —</option>
                                    @foreach($this->formatosMuestra as $formato)
                                        <option value="{{ $formato->id }}">{{ $formato->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        </div>

                        @if($isActive)
                        <div class="flex items-center gap-2 text-xs text-gray-600 pt-1">
                            <input type="checkbox"
                                   id="req_crit_{{ $criterioId }}"
                                   wire:model.live="configCriteriosRequeridos"
                                   value="{{ $criterioId }}"
                                   class="h-3.5 w-3.5 text-red-500 border-gray-300 rounded">
                            <label for="req_crit_{{ $criterioId }}" class="cursor-pointer">Requerido</label>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- Botón guardar --}}
        <div class="flex items-center gap-4">
            <button wire:click="guardarConfiguracion"
                    class="px-5 py-2 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition">
                💾 Guardar configuración
            </button>
            @if($configMensaje)
            <span class="text-green-700 text-sm font-medium">{{ $configMensaje }}</span>
            @endif
            @if($configMensajeError)
            <span class="text-red-600 text-sm font-medium">{{ $configMensajeError }}</span>
            @endif
        </div>
        @endif

    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════
         PESTAÑA: DOCUMENTOS
    ═══════════════════════════════════════════════════════════════ --}}
    @if($pestana === 'documentos')
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 p-4 bg-gray-50 rounded-xl border border-gray-200">
        <input wire:model.live.debounce.400ms="busqueda"
               type="text"
               placeholder="Buscar documento o contratista..."
               class="col-span-1 sm:col-span-2 border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 w-full">

        <select wire:model.live="filtroEstadoIa"
                class="border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 w-full">
            <option value="">— Estado IA —</option>
            <option value="sin_procesar">Sin procesar</option>
            <option value="EXTRAIDO">Extraído</option>
            <option value="MATCH_CALCULADO">Match calculado</option>
            <option value="CONFIRMADO">Confirmado</option>
            <option value="RECHAZADO_OPERADOR">Rechazado por operador</option>
        </select>

        <select wire:model.live="filtroMatchResult"
                class="border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 w-full">
            <option value="">— Resultado match —</option>
            <option value="APROBADO">APROBADO</option>
            <option value="RECHAZADO">RECHAZADO</option>
            <option value="REVISION_MANUAL">REVISIÓN MANUAL</option>
        </select>

        <select wire:model.live="filtroMandante"
                class="border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 w-full">
            <option value="">— Principal —</option>
            @foreach($mandantes as $m)
            <option value="{{ $m->id }}">{{ $m->razon_social }}</option>
            @endforeach
        </select>

        <select wire:model.live="filtroFuenteIa"
                class="border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 w-full">
            <option value="">— Fuente IA —</option>
            <option value="API">API (Gemini)</option>
            <option value="EXCEL">Excel</option>
        </select>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         TABLA
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="overflow-x-auto bg-white rounded-xl border border-gray-200 shadow-sm">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 w-8">
                        <input type="checkbox" wire:model.live="seleccionarTodos" class="rounded border-gray-300">
                    </th>
                    <th class="px-4 py-3">Documento</th>
                    <th class="px-4 py-3">Entidad</th>
                    <th class="px-4 py-3">Contratista</th>
                    <th class="px-4 py-3 w-1/3">Extracción IA vs BD (Vista Águila)</th>
                    <th class="px-4 py-3 text-center">Tokens</th>
                    <th class="px-4 py-3 text-center">Estado / Match</th>
                    <th class="px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($documentos as $doc)
                @php $datoIa = $doc->datoExtraidoIa; @endphp
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3">
                        <input type="checkbox"
                               wire:model.live="seleccionados"
                               value="{{ $doc->id }}"
                               class="rounded border-gray-300">
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900 truncate max-w-xs">{{ $doc->nombre_documento_snapshot }}</div>
                        <div class="text-xs text-gray-400">ID: {{ $doc->id }} · {{ $doc->created_at->format('d/m/Y') }}</div>
                    </td>
                    {{-- Entidad --}}
                    <td class="px-4 py-3 align-top">
                        <div class="text-sm font-semibold text-gray-800">{{ $doc->entidad?->nombre_completo ?? $doc->entidad?->razon_social ?? '—' }}</div>
                        <div class="text-sm text-gray-600 mt-0.5"><span class="font-medium">RUT/ID:</span> {{ $doc->entidad?->rut ?? $doc->entidad?->patente ?? '—' }}</div>
                        
                        @php
                            $vinc = $doc->trabajadorVinculacion;
                            $esTrabajador = $doc->entidad_type === 'App\\Models\\Trabajador' && $doc->entidad;
                            if (!$vinc && $esTrabajador) {
                                $vinc = $doc->entidad->vinculaciones()->where('is_active', true)->first();
                            }
                        @endphp
                        @if($vinc && $vinc->cargoMandante)
                            <div class="text-xs text-gray-500 truncate max-w-xs mt-0.5"><span class="font-medium">Cargo:</span> {{ $vinc->cargoMandante->nombre_cargo ?? '—' }}</div>
                        @endif
                        @if($esTrabajador)
                            @if($doc->entidad->nacionalidad)
                                <div class="text-xs text-gray-500 truncate max-w-xs"><span class="font-medium">Nac:</span> {{ $doc->entidad->nacionalidad->nombre }}</div>
                            @endif
                            @if($doc->entidad->tipoPermanencia)
                                <div class="text-xs text-gray-500 truncate max-w-xs"><span class="font-medium">Permanencia:</span> {{ $doc->entidad->tipoPermanencia->nombre }}</div>
                            @endif
                        @endif
                    </td>

                    {{-- Contratista --}}
                    <td class="px-4 py-3 align-top">
                        <div class="text-sm font-semibold text-gray-800">{{ $doc->contratista?->razon_social ?? '—' }}</div>
                        <div class="text-xs text-gray-600 mt-0.5"><span class="font-medium">RUT:</span> {{ $doc->contratista?->rut ?? '—' }}</div>
                        <div class="text-xs text-gray-500 truncate max-w-xs mt-1"><span class="font-medium">Principal:</span> {{ $doc->mandante?->razon_social ?? '—' }}</div>
                    </td>

                    {{-- Extracción IA (Vista Águila) --}}
                    <td class="px-4 py-3 align-top">
                        @if($datoIa)
                            @if($datoIa->detalle_match)
                                <div class="space-y-1 text-xs">
                                    <div class="flex items-center text-[10px] text-gray-400 uppercase font-semibold mb-1 border-b pb-1">
                                        <div class="w-3/12">Campo</div>
                                        <div class="w-3/12 text-center">Extraído</div>
                                        <div class="w-3/12 text-center">BD</div>
                                        <div class="w-3/12 text-right text-indigo-500">Cumple IA</div>
                                    </div>
                                    @foreach($datoIa->detalle_match as $item)
                                        @php
                                            $nombreClave = $item['campo'];
                                            $esCriterio = str_starts_with($item['clave'], 'criterio_');
                                            if ($esCriterio) {
                                                $critId = str_replace('criterio_', '', $item['clave']);
                                                $crit = \App\Models\CriterioEvaluacion::find($critId);
                                                if ($crit) $nombreClave = $crit->nombre_criterio;
                                                $cumpleIa = $item['cumple_ia'] ?? '—';
                                            } else {
                                                $cumpleIa = '—';
                                            }
                                        @endphp
                                        <div class="flex items-center justify-between p-1.5 mb-1 {{ $item['ok'] ? 'bg-green-50/70 text-green-800' : 'bg-red-50/70 text-red-800' }} rounded border {{ $item['ok'] ? 'border-green-100' : 'border-red-100' }}">
                                            <span class="font-medium w-3/12 pr-1 break-words leading-tight" title="{{ $nombreClave }}">{{ $item['ok'] ? '✅' : '❌' }} {{ $nombreClave }}</span>
                                            <span class="font-mono w-3/12 text-center break-words whitespace-pre-wrap">{{ $item['extraido'] ?? '—' }}</span>
                                            <span class="w-3/12 text-center opacity-80 break-words whitespace-pre-wrap">{{ $esCriterio ? '—' : ($item['esperado'] ?? '—') }}</span>
                                            <span class="w-3/12 text-right font-bold text-indigo-700 uppercase">{{ $cumpleIa }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif($datoIa->datos_extraidos)
                                <div class="space-y-1 text-xs text-gray-600">
                                    <div class="flex items-center text-[10px] text-gray-400 uppercase font-semibold mb-1 border-b pb-1">
                                        <div class="w-4/12">Campo</div>
                                        <div class="w-4/12 text-center">Extraído</div>
                                        <div class="w-4/12 text-right text-indigo-500">Cumple IA</div>
                                    </div>
                                    @foreach($datoIa->datos_extraidos as $clave => $valor)
                                        @php
                                            if (str_ends_with($clave, '_cumple')) continue; // Lo mostramos junto al _extraido
                                            $nombreClave = str_replace('_extraido', '', $clave);
                                            $esCriterio = str_ends_with($clave, '_extraido');
                                            
                                            if ($esCriterio) {
                                                $critId = str_replace('criterio_', '', $nombreClave);
                                                $crit = \App\Models\CriterioEvaluacion::find($critId);
                                                if ($crit) $nombreClave = $crit->nombre_criterio;
                                                // clave is 'criterio_25_extraido', nombreClave is 'criterio_25' (before we rename it)
                                                $claveBase = str_replace('_extraido', '', $clave);
                                                $cumpleIa = $datoIa->datos_extraidos[$claveBase . '_cumple'] ?? '—';
                                            } else {
                                                $cumpleIa = '—';
                                            }
                                            
                                            $valorDisplay = is_array($valor) ? json_encode($valor) : ($valor ?? '—');
                                        @endphp
                                        <div class="flex justify-between border-b border-gray-400 last:border-0 py-1.5 items-center">
                                            <span class="font-medium text-gray-800 w-4/12 pr-2 break-words leading-tight" title="{{ $nombreClave }}">{{ $nombreClave }}:</span>
                                            <span class="font-mono w-4/12 text-center break-words whitespace-pre-wrap" title="{{ $valorDisplay }}">{{ $valorDisplay === 'no' || $valorDisplay === 'si' ? '—' : $valorDisplay }}</span>
                                            <span class="text-indigo-700 font-bold w-4/12 text-right uppercase">{{ $cumpleIa }}</span>
                                        </div>
                                    @endforeach
                                    <div class="text-[10px] text-gray-400 italic mt-1 text-right">Pendiente de Aceptar Análisis IA...</div>
                                </div>
                            @endif
                        @else
                            <div class="text-xs text-gray-400 italic mt-2">Esperando procesamiento IA...</div>
                        @endif
                    </td>

                    {{-- Tokens --}}
                    <td class="px-4 py-3 text-center align-top">
                        @if($datoIa)
                            <div class="font-mono text-gray-700 text-xs font-bold">{{ ($datoIa->tokens_entrada ?? 0) + ($datoIa->tokens_salida ?? 0) }}</div>
                            <div class="text-[10px] text-gray-400 mt-1 uppercase">{{ $datoIa->fuente }}</div>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>

                    {{-- Estado / Match --}}
                    <td class="px-4 py-3 text-center align-top space-y-2">
                        <div>
                            @if(!$datoIa)
                                <span class="px-2 py-1 bg-gray-100 text-gray-500 text-xs rounded-full">Sin procesar</span>
                            @elseif($datoIa->estado === 'CONFIRMADO')
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">Confirmado</span>
                            @elseif($datoIa->estado === 'MATCH_CALCULADO')
                                <span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">Match listo</span>
                            @elseif($datoIa->estado === 'EXTRAIDO')
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">Extraído</span>
                            @elseif($datoIa->estado === 'RECHAZADO_OPERADOR')
                                <span class="px-2 py-1 bg-orange-100 text-orange-700 text-xs rounded-full">Rechazado por operador</span>
                            @else
                                <span class="px-2 py-1 bg-gray-100 text-gray-500 text-xs rounded-full">{{ $datoIa->estado }}</span>
                            @endif
                        </div>
                        
                        @if($datoIa && $datoIa->match_calculado)
                        <div>
                            @if($datoIa->match_calculado === 'APROBADO')
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold bg-green-100 text-green-800">
                                    ✅ APROBADO
                                </span>
                            @elseif($datoIa->match_calculado === 'RECHAZADO')
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold bg-red-100 text-red-800">
                                    ❌ RECHAZADO
                                </span>
                            @elseif($datoIa->match_calculado === 'REVISION_MANUAL')
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold bg-yellow-100 text-yellow-800">
                                    ⚠️ REVISAR
                                </span>
                            @endif
                        </div>
                        @endif
                    </td>

                    {{-- Acciones --}}
                    <td class="px-4 py-3">
                        <div class="flex flex-col items-center justify-center gap-2">
                            <a href="{{ route('documento.seguro.descargar', ['id' => $doc->id]) }}" target="_blank" 
                               class="px-3 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition text-center w-full max-w-[120px]" title="Ver PDF Original">
                                📄 Ver Doc
                            </a>

                            <a href="{{ route('document.revisar', ['documentoId' => $doc->id]) }}" target="_blank" 
                               class="px-3 py-1 bg-yellow-50 text-yellow-700 border border-yellow-200 text-xs font-medium rounded-lg hover:bg-yellow-100 transition text-center w-full max-w-[120px]" title="Revisión humana clásica">
                                🕵️ Revisar Manual
                            </a>

                            @if(!$datoIa || $datoIa->estado === 'RECHAZADO_OPERADOR')
                            <button wire:click="enviarAIa({{ $doc->id }})"
                                    wire:loading.attr="disabled"
                                    class="px-3 py-1 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 w-full max-w-[120px]">
                                <span wire:loading.remove wire:target="enviarAIa({{ $doc->id }})">🚀 Enviar IA</span>
                                <span wire:loading wire:target="enviarAIa({{ $doc->id }})">⏳ Proc...</span>
                            </button>
                            @endif

                            @if($datoIa && $datoIa->estado === 'EXTRAIDO')
                            <button wire:click="calcularMatch({{ $doc->id }})"
                                    wire:loading.attr="disabled"
                                    class="px-3 py-1 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition disabled:opacity-50 w-full max-w-[120px]">
                                <span wire:loading.remove wire:target="calcularMatch({{ $doc->id }})">🤖 Aceptar Análisis IA</span>
                                <span wire:loading wire:target="calcularMatch({{ $doc->id }})">⏳ Calc...</span>
                            </button>
                            @endif

                            @if($datoIa && $datoIa->estado === 'MATCH_CALCULADO')
                            <button wire:click="confirmarMatchIndividual({{ $datoIa->id }})"
                                    wire:loading.attr="disabled"
                                    class="px-3 py-1 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition disabled:opacity-50 w-full max-w-[120px]">
                                <span wire:loading.remove wire:target="confirmarMatchIndividual({{ $datoIa->id }})">✅ Confirmar</span>
                                <span wire:loading wire:target="confirmarMatchIndividual({{ $datoIa->id }})">⏳ Guar...</span>
                            </button>
                            @endif

                            @if($datoIa && $datoIa->estado !== 'CONFIRMADO')
                            <button wire:click="revertirIa({{ $doc->id }})"
                                    wire:confirm="¿Seguro que desea eliminar esta extracción para enviar a la IA nuevamente?"
                                    class="px-3 py-1 bg-red-50 text-red-600 text-xs font-medium rounded hover:bg-red-100 transition w-full max-w-[120px]" title="Revertir y volver a extraer">
                                ↺ Revertir
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-16 text-center text-gray-400">
                        <div class="text-4xl mb-3">🤖</div>
                        <div class="text-lg font-medium">No hay documentos que procesar</div>
                        <div class="text-sm mt-1">Prueba cambiando los filtros o verifica que las reglas documentales tengan campos IA configurados.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $documentos->links() }}</div>
    @endif {{-- fin @if pestana === documentos --}}

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL DETALLE / CONFIRMACIÓN (siempre disponible)
    ═══════════════════════════════════════════════════════════════ --}}
    @if($modalDetalle && $detalleDocId)
    @php
        $docDetalle = \App\Models\DocumentoCargado::with(['datoExtraidoIa','contratista','mandante','entidad'])->find($detalleDocId);
        $iaDetalle  = $docDetalle?->datoExtraidoIa;
    @endphp
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col">

            {{-- Header modal --}}
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">🔍 Detalle IA — {{ $docDetalle?->nombre_documento_snapshot }}</h2>
                    <p class="text-sm text-gray-500">{{ $docDetalle?->contratista?->razon_social }} · {{ $docDetalle?->mandante?->razon_social }}</p>
                </div>
                <button wire:click="$set('modalDetalle', false)" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>

            {{-- Body modal --}}
            <div class="p-6 overflow-y-auto space-y-6 flex-1">

                @if(!$iaDetalle)
                <div class="text-center text-gray-400 py-12">
                    <div class="text-4xl mb-3">📭</div>
                    <p>Este documento aún no ha sido procesado por la IA.</p>
                </div>
                @else

                {{-- Info del proceso --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="text-xs text-gray-400 uppercase mb-1">Fuente</div>
                        <div class="font-semibold">{{ $iaDetalle->fuente === 'API' ? '🤖 API Gemini' : '📊 Excel' }}</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="text-xs text-gray-400 uppercase mb-1">Estado</div>
                        <div class="font-semibold">{{ $iaDetalle->estado }}</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="text-xs text-gray-400 uppercase mb-1">Tokens</div>
                        <div class="font-semibold">{{ ($iaDetalle->tokens_entrada ?? 0) + ($iaDetalle->tokens_salida ?? 0) }}</div>
                    </div>
                </div>

                {{-- Detalle campo a campo --}}
                @if($iaDetalle->detalle_match)
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Resultado del Match — Campo a Campo</h3>
                    <div class="space-y-2">
                        @foreach($iaDetalle->detalle_match as $item)
                        <div class="flex items-start gap-3 p-3 rounded-lg {{ $item['ok'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                            <span class="text-lg mt-0.5">{{ $item['ok'] ? '✅' : '❌' }}</span>
                            <div class="flex-1 text-sm">
                                <div class="font-medium {{ $item['ok'] ? 'text-green-800' : 'text-red-800' }}">{{ $item['campo'] }}</div>
                                @if($item['ok'])
                                    <div class="text-green-600 text-xs">{{ $item['extraido'] }}</div>
                                @else
                                    <div class="text-red-600 text-xs">
                                        Extraído: <strong>{{ $item['extraido'] ?? 'no encontrado' }}</strong>
                                        · Esperado: <strong>{{ $item['esperado'] ?? '—' }}</strong>
                                    </div>
                                @endif
                                @if(!empty($item['mensaje']))
                                    <div class="text-gray-400 text-xs mt-0.5">{{ $item['mensaje'] }}</div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Resultado global --}}
                @if($iaDetalle->match_calculado)
                <div class="p-4 rounded-xl text-center font-bold text-lg
                    {{ $iaDetalle->match_calculado === 'APROBADO' ? 'bg-green-100 text-green-800 border border-green-300' :
                       ($iaDetalle->match_calculado === 'RECHAZADO' ? 'bg-red-100 text-red-800 border border-red-300' : 'bg-yellow-100 text-yellow-800 border border-yellow-300') }}">
                    {{ $iaDetalle->match_calculado === 'APROBADO' ? '✅ APROBADO' :
                       ($iaDetalle->match_calculado === 'RECHAZADO' ? '❌ RECHAZADO' : '⚠️ REVISIÓN MANUAL') }}
                </div>
                @endif

                @endif {{-- fin if iaDetalle --}}
            </div>

            {{-- Footer modal --}}
            <div class="p-6 border-t border-gray-200 flex items-center justify-between gap-3">
                <button wire:click="$set('modalDetalle', false)"
                        class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                    Cerrar
                </button>
                @if($iaDetalle && $iaDetalle->estado === 'MATCH_CALCULADO')
                <div class="flex gap-2">
                    <button wire:click="rechazarResultadoIa({{ $iaDetalle->id }})"
                            class="px-4 py-2 bg-orange-100 text-orange-700 text-sm font-medium rounded-lg hover:bg-orange-200 transition">
                        🚫 Rechazar resultado IA
                    </button>
                    <button wire:click="confirmarMatchIndividual({{ $iaDetalle->id }})"
                            wire:confirm="¿Confirmar este resultado? Se escribirá en el documento y no se podrá deshacer desde aquí."
                            class="px-4 py-2 bg-green-600 text-white text-sm font-bold rounded-lg hover:bg-green-700 transition">
                        ✅ Confirmar y escribir resultado
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL EXCEL
    ═══════════════════════════════════════════════════════════════ --}}
    @if($modalExcel)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-900">📊 Importar datos IA desde Excel</h2>
                <button wire:click="$set('modalExcel', false)" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>

            <div class="p-6 space-y-5">
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                    <p class="font-semibold mb-1">📋 Formato del Excel (N filas)</p>
                    <ul class="list-disc list-inside space-y-1 text-xs">
                        <li>Columna A: <code class="bg-blue-100 px-1 rounded">documento_cargado_id</code> (obligatorio)</li>
                        <li>Columnas B..N: campos configurados en IA para cada regla documental</li>
                        <li>Cada fila = 1 documento</li>
                        <li>El sistema calcula el match automáticamente al importar</li>
                    </ul>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar archivo Excel (.xlsx)</label>
                    <input type="file" wire:model="archivoExcel" accept=".xlsx,.xls"
                           class="block w-full text-sm text-gray-600 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none p-2">
                    @error('archivoExcel') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                @if($excelProcesado && !empty($resultadosExcel))
                <div class="max-h-48 overflow-y-auto space-y-1">
                    @foreach($resultadosExcel as $r)
                    <div class="flex items-center gap-2 text-xs p-2 rounded
                        {{ $r['color'] === 'green' ? 'bg-green-50 text-green-800' :
                           ($r['color'] === 'red' ? 'bg-red-50 text-red-800' :
                           ($r['color'] === 'yellow' ? 'bg-yellow-50 text-yellow-800' : 'bg-gray-50 text-gray-600')) }}">
                        <span class="font-mono">ID {{ $r['doc_id'] }}</span>
                        <span>{{ $r['nombre'] ?? '' }}</span>
                        <span class="ml-auto font-bold">{{ $r['estado'] }}</span>
                        <span>{{ $r['mensaje'] }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="p-6 border-t border-gray-200 flex justify-between">
                <button wire:click="$set('modalExcel', false)"
                        class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                    Cerrar
                </button>
                <button wire:click="subirExcel"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 bg-emerald-600 text-white text-sm font-bold rounded-lg hover:bg-emerald-700 transition disabled:opacity-50">
                    <span wire:loading.remove wire:target="subirExcel">📤 Procesar Excel</span>
                    <span wire:loading wire:target="subirExcel">⏳ Procesando...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
