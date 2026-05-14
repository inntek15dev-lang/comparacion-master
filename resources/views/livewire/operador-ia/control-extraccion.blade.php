<div class="p-4 bg-gray-100 dark:bg-gray-900 min-h-screen">
    <!-- TITULO -->
    <div class="mb-4">
        <h1 class="text-xl font-bold text-gray-700 dark:text-white uppercase tracking-tighter">
            Control de Extracción de Datos (IA/Excel)
        </h1>
        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-black">
            Perfil: Operador IA - Gestión de carga externa de información
        </p>
    </div>

    <!-- FILTROS -->
    <div class="bg-[#1a3560] p-4 rounded-lg shadow mb-4">
        <div class="text-white text-[10px] font-black uppercase mb-3 border-b border-white/30 pb-2">
            🔍 FILTROS DE BÚSQUEDA
        </div>
        <div class="grid grid-cols-1 md:grid-cols-9 gap-3">
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

            <!-- Envío -->
            <div>
                <label class="text-white/70 text-[9px] font-bold uppercase block mb-1">ENVÍO</label>
                <select wire:model.live="estado_plazo" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-700 dark:text-white font-bold">
                    <option value="">-- Todos --</option>
                    <option value="NORMAL">✓ Dentro de Plazo</option>
                    <option value="FUERA_PLAZO">⚠ Fuera de Plazo</option>
                </select>
            </div>

            <!-- Datos IA -->
            <div>
                <label class="text-white/70 text-[9px] font-bold uppercase block mb-1">DATOS IA</label>
                <select wire:model.live="filtro_ia" class="w-full text-[11px] px-2 py-1.5 rounded border-0 bg-white dark:bg-gray-700 dark:text-white">
                    <option value="">-- Todos --</option>
                    <option value="IA_OK">🤖 IA OK</option>
                    <option value="IA_PENDIENTE">⌛ PENDIENTE</option>
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
        </div>

        <div class="mt-3 flex justify-end gap-2 items-center">
            <div wire:loading wire:target="descargarDocumentosFiltrados" class="text-white text-[10px] font-black uppercase flex items-center gap-2 mr-2">
                <svg class="animate-spin h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Generando ZIP...
            </div>

            <button wire:click="descargarDocumentosFiltrados" 
                    wire:loading.attr="disabled"
                    class="bg-teal-600 hover:bg-teal-700 text-white text-[10px] font-black px-4 py-1.5 rounded uppercase flex items-center gap-2 transition-all shadow-lg active:scale-95 disabled:opacity-50">
                📦 Descargar ZIP (Filtrado)
            </button>

            <button wire:click="limpiarFiltros" class="bg-gray-500 hover:bg-gray-600 text-white text-[10px] font-bold px-4 py-1.5 rounded uppercase">
                🗑️ Limpiar Filtros
            </button>
        </div>
    </div>

    <!-- TABLA DE RESULTADOS -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="bg-[#003a5c] text-white px-4 py-2 text-[10px] font-black uppercase">
            📋 PERIODOS PARA PROCESAMIENTO IA ({{ $carpetas->total() }} registros)
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase w-10">N°</th>
                        <th class="px-2 py-2 text-left text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Filial</th>
                        <th class="px-2 py-2 text-left text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">ID_REG</th>
                        <th class="px-2 py-2 text-left text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">RUT</th>
                        <th class="px-2 py-2 text-left text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Contratista</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Lugar/Contrato</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Periodo</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">ENVÍO</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-blue-600 dark:text-blue-400 uppercase">Datos IA</th>
                        <th class="px-2 py-2 text-center text-[9px] font-black text-gray-600 dark:text-gray-300 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-[10px]">
                    @forelse($carpetas as $index => $carpeta)
                        @php
                            $correlativoJerarquico = $carpeta->correlativo_jerarquico ?? ($carpetas->firstItem() + $index);
                            $correlativoArray = explode('.', (string)$correlativoJerarquico);
                            $nivel = count($correlativoArray) - 1;
                            $indentClass = $nivel > 0 ? 'pl-' . ($nivel * 4) : '';
                            $fondoClase = $loop->even ? 'bg-gray-50 dark:bg-gray-750' : 'bg-white dark:bg-gray-800';
                        @endphp
                        <tr wire:key="carpeta-{{ $carpeta->id }}" class="{{ $fondoClase }} hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors border-l-4 {{ $nivel > 0 ? 'border-blue-400' : 'border-transparent' }}">
                            <td class="px-2 py-1 text-center font-bold {{ $nivel > 0 ? 'text-blue-600' : 'text-gray-400' }}">
                                {{ $correlativoJerarquico }}
                            </td>
                            <td class="px-2 py-1 font-bold text-gray-700 dark:text-gray-300">
                                {{ Str::limit($carpeta->vinculacion->unidadOrganizacionalMandante->mandante->razon_social ?? '-', 20) }}
                            </td>
                            <td class="px-2 py-1 font-bold text-blue-700 dark:text-blue-400">
                                {{ $carpeta->vinculacion->id_registro ?? '-' }}
                            </td>
                            <td class="px-2 py-1 font-mono text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                {{ $carpeta->vinculacion->contratista->rut ?? '-' }}
                            </td>
                            <td class="px-2 py-1 font-bold text-gray-900 dark:text-white uppercase leading-tight {{ $indentClass }}">
                                @if($nivel > 0) <span class="text-blue-500 mr-1">└</span> @endif
                                {{ Str::limit($carpeta->vinculacion->contratista->razon_social ?? '-', 25) }}
                            </td>
                            <td class="px-2 py-1 text-center">
                                <span class="block font-bold text-gray-700 dark:text-gray-300 uppercase">
                                    {{ Str::limit($carpeta->vinculacion->dependencia->nombre ?? '-', 15) }}
                                </span>
                                <span class="block text-[8px] font-mono text-blue-600 dark:text-blue-400 mt-0.5">
                                    CT: {{ $carpeta->vinculacion->numero_contrato ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-2 py-1 text-center">
                                <span class="bg-gray-100 text-gray-700 border border-gray-300 text-[9px] font-black px-1.5 py-0.5 rounded uppercase whitespace-nowrap">
                                    {{ $getNombreMes($carpeta->mes) }} {{ $carpeta->anio }}
                                </span>
                            </td>
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
                                <div class="text-[8px] font-mono text-gray-500 mt-0.5">
                                    {{ $carpeta->fecha_envio ? $carpeta->fecha_envio->format('d/m/Y') : '-' }}
                                </div>
                            </td>
                            <td class="px-2 py-1 text-center">
                                <div class="flex items-center justify-center">
                                    <input type="checkbox" 
                                           wire:click="toggleExtraido({{ $carpeta->id }})" 
                                           {{ $carpeta->ia_datos_extraidos ? 'checked' : '' }}
                                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer">
                                </div>
                            </td>
                            <td class="px-2 py-1 text-center">
                                <button wire:click="verDetalle({{ $carpeta->id }})" class="bg-[#004b75] hover:bg-[#003a5c] text-white text-[9px] font-black px-3 py-1 rounded uppercase transition-colors shadow-sm">
                                    Ver Docs
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-gray-400 text-[11px] uppercase font-bold italic">
                                No hay periodos enviados que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($carpetas->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $carpetas->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL VER DOCUMENTOS -->
    @if($carpetaDetalle)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" wire:click="cerrarDetalle"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
                    <div class="bg-[#004b75] text-white px-5 py-3 flex justify-between items-start flex-shrink-0 rounded-t-xl">
                        <div>
                            <h2 class="text-sm font-black uppercase tracking-tight">
                                {{ $carpetaDetalle->vinculacion->contratista->razon_social ?? '-' }}
                            </h2>
                            <p class="text-[10px] text-white/70 mt-0.5 uppercase">
                                PERIODO: {{ $getNombreMes($carpetaDetalle->mes) }} {{ $carpetaDetalle->anio }} | CONTRATO: {{ $carpetaDetalle->vinculacion->numero_contrato ?? 'N/A' }}
                            </p>
                        </div>
                        <button wire:click="cerrarDetalle" class="text-white hover:text-gray-300 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    
                    <div class="p-6 overflow-y-auto flex-1 bg-gray-50 dark:bg-gray-900 rounded-b-lg">
                        <div class="space-y-4">
                            @foreach($requisitosPorClasif as $clasificacion => $requisitos)
                                <div>
                                    <h3 class="text-[10px] font-black text-teal-700 dark:text-teal-400 uppercase border-b border-teal-200 dark:border-teal-800 pb-1 mb-2">
                                        {{ $clasificacion }}
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @foreach($requisitos as $req)
                                            @php $docs = $documentosPorRequisito[$req->id] ?? collect(); @endphp
                                            <div class="p-3 border rounded-lg bg-white dark:bg-gray-800 {{ $docs->count() > 0 ? 'border-green-200 shadow-sm' : 'border-red-100 opacity-60' }}">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-[10px] font-bold text-gray-700 dark:text-gray-200 uppercase">{{ $req->nombre }}</span>
                                                    @if($docs->count() > 0)
                                                        <span class="bg-green-100 text-green-700 text-[8px] font-black px-1.5 py-0.5 rounded">CARGADO</span>
                                                    @else
                                                        <span class="bg-red-50 text-red-400 text-[8px] font-black px-1.5 py-0.5 rounded">SIN DOC</span>
                                                    @endif
                                                </div>
                                                <div class="space-y-1">
                                                    @foreach($docs as $doc)
                                                        <div class="flex items-center justify-between gap-2 p-1 bg-gray-50 dark:bg-gray-750 rounded border border-gray-100 dark:border-gray-700">
                                                            <span class="text-[9px] text-gray-500 truncate">{{ $doc->nombre_original }}</span>
                                                            <div class="flex items-center gap-1">
                                                                <a href="{{ route('archivo.publico', ['filePath' => $doc->path]) }}" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white text-[8px] font-black px-2 py-0.5 rounded whitespace-nowrap transition-colors" title="Ver en el navegador">VER</a>
                                                                <a href="{{ route('archivo.publico', ['filePath' => $doc->path, 'download' => 1]) }}" class="bg-teal-600 hover:bg-teal-700 text-white text-[8px] font-black px-2 py-0.5 rounded whitespace-nowrap transition-colors" title="Descargar archivo">DESCARGAR</a>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
