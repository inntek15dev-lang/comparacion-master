<div class="py-6 px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white uppercase">VERIF. CONFIG.</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Gestione los requisitos documentales y el calendario de emisión por cada empresa principal.</p>
    </div>

    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
        <ul class="flex flex-wrap -mb-px text-sm font-bold text-center">
            <li class="mr-2">
                <button wire:click="setTab('requisitos')" 
                        class="inline-block p-4 border-b-2 rounded-t-lg transition-colors {{ $tab == 'requisitos' ? 'border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300' }}">
                    REQUISITOS DOCUMENTALES
                </button>
            </li>
            <li class="mr-2">
                <button wire:click="setTab('categorias')" 
                        class="inline-block p-4 border-b-2 rounded-t-lg transition-colors {{ $tab == 'categorias' ? 'border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300' }}">
                    CATEGORÍAS DE DOCUMENTOS
                </button>
            </li>
            <li class="mr-2">
                <button wire:click="setTab('calendario')" 
                        class="inline-block p-4 border-b-2 rounded-t-lg transition-colors {{ $tab == 'calendario' ? 'border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300' }}">
                    CONFIGURACION CALENDARIO
                </button>
            </li>
            <li class="mr-2">
                <button wire:click="setTab('consolidado')" 
                        class="inline-block p-4 border-b-2 rounded-t-lg transition-colors {{ $tab == 'consolidado' ? 'border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300' }}">
                    CONSOLIDADO FECHAS PERIODOS
                </button>
            </li>
            <li class="mr-2">
                <button wire:click="setTab('catalogo_auditoria')" 
                        class="inline-block p-4 border-b-2 rounded-t-lg transition-colors {{ $tab == 'catalogo_auditoria' ? 'border-rose-600 text-rose-600 dark:border-rose-500 dark:text-rose-400' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300' }}">
                    CATÁLOGO AUDITORÍA
                    @if($catalogoObsCount + $catalogoContCount > 0)
                        <span class="ml-1.5 bg-rose-100 text-rose-700 text-[8px] font-black px-1.5 py-0.5 rounded-full">
                            {{ $catalogoObsCount + $catalogoContCount }}
                        </span>
                    @endif
                </button>
            </li>
        </ul>
    </div>

    @if($tab == 'requisitos')
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Selección de Empresa Principal -->
            <div class="col-span-1 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 h-fit">
                <h3 class="text-xs font-bold mb-4 text-gray-400 uppercase tracking-widest border-b pb-2">Configuración</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Empresa Principal</label>
                        <select wire:model.live="mandante_id" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white text-xs py-2 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Seleccione Principal --</option>
                            @foreach($mandantes as $m)
                                <option value="{{ $m->id }}">{{ $m->razon_social }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($mandante_id)
                        <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                            <h4 class="text-[10px] font-bold text-blue-600 uppercase mb-3">{{ $requisitoActual ? 'Editar Documento' : 'Nuevo Documento' }}</h4>
                            <div class="space-y-3">
                                <div>
                                    <input type="text" wire:model="nuevo_requisito_nombre" placeholder="Nombre (ej: Liquidaciones)" 
                                           class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-xs py-2">
                                </div>
                                <div>
                                    <input type="text" wire:model="nuevo_requisito_codigo" placeholder="Código (ej: D1)" 
                                           class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-xs py-2">
                                </div>
                                <div>
                                    <select wire:model="clasificacion_id" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-xs py-2 shadow-sm">
                                        <option value="">-- Sin Clasificación (Otros) --</option>
                                        @foreach($clasificaciones as $clas)
                                            <option value="{{ $clas->id }}">{{ $clas->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <textarea wire:model="nuevo_requisito_descripcion" placeholder="Descripción / Instrucciones..." 
                                              class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-xs" rows="3"></textarea>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" wire:model="nuevo_requisito_es_obligatorio" id="nuevo_requisito_es_obligatorio" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 h-4 w-4">
                                    <label for="nuevo_requisito_es_obligatorio" class="ml-2 block text-[10px] font-bold text-gray-700 dark:text-gray-300 uppercase">Es Obligatorio para Envío</label>
                                </div>
                                <div class="flex gap-2">
                                    <button wire:click="guardarRequisito" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-[10px] transition-colors uppercase tracking-widest">
                                        {{ $requisitoActual ? 'GUARDAR CAMBIOS' : 'AGREGAR' }}
                                    </button>
                                    @if($requisitoActual)
                                        <button wire:click="cancelarEdicionRequisito" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-2 px-3 rounded text-[10px] transition-colors uppercase tracking-widest border border-gray-300">
                                            ✕
                                        </button>
                                    @endif
                                </div>
                            </div>
                            @if (session()->has('requisito_status'))
                                <div class="mt-2 text-[10px] text-center text-green-600 font-bold uppercase">{{ session('requisito_status') }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Listado de Requisitos -->
            <div class="col-span-1 md:col-span-3 bg-white dark:bg-gray-800 p-0 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-widest">Documentos Requeridos</h3>
                </div>
                @if(!$mandante_id)
                    <div class="text-center py-20 text-gray-400 uppercase text-xs font-bold tracking-widest">Seleccione una empresa principal para gestionar sus requisitos.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Código</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Documento</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Clasificación</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Instrucciones</th>
                                    <th class="px-6 py-3 text-center text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-32">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($requisitos as $req)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                                        <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200 font-bold uppercase">{{ $req->codigo ?: '-' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-800 dark:text-gray-200 font-extrabold uppercase">
                                            {{ $req->nombre }}
                                            @if($req->es_obligatorio)
                                                <span class="ml-2 bg-amber-100 text-amber-800 text-[8px] font-black px-2 py-0.5 rounded-full border border-amber-300 tracking-widest inline-block transform -translate-y-0.5">
                                                    ⭐ OBLIGATORIO
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-[9px] font-black {{ $req->clasificacion ? 'text-blue-600 bg-blue-50 border border-blue-100' : 'text-gray-400 bg-gray-50 border border-gray-100' }} px-2 py-0.5 rounded-full uppercase">
                                                {{ $req->clasificacion->nombre ?? 'OTROS' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-gray-600 dark:text-gray-400 leading-relaxed">{{ $req->descripcion ?: '-' }}</td>
                                        <td class="px-6 py-4 text-center whitespace-nowrap">
                                            <button wire:click="editarRequisito({{ $req->id }})" 
                                                    class="text-blue-500 hover:text-blue-700 font-black text-[9px] uppercase tracking-widest px-2 py-1 bg-blue-50 rounded-md border border-blue-100 hover:bg-blue-100 transition-all">
                                                EDITAR
                                            </button>
                                            <button wire:click="eliminarRequisito({{ $req->id }})" 
                                                    wire:confirm="¿Seguro que desea eliminar el documento '{{ $req->nombre }}'?"
                                                    class="text-red-500 hover:text-red-700 font-black text-[9px] uppercase tracking-widest px-2 py-1 bg-red-50 rounded-md border border-red-100 hover:bg-red-100 transition-all ml-1">
                                                BORRAR
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-400 italic text-xs uppercase tracking-widest">Sin requisitos definidos.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if($tab == 'calendario')
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Selección de Empresa Principal (Izquierda) -->
            <div class="col-span-1 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 h-fit">
                <h3 class="text-xs font-bold mb-4 text-gray-400 uppercase tracking-widest border-b pb-2">Configuración</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Empresa Principal</label>
                        <select wire:model.live="mandante_id" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white text-xs py-2 shadow-sm">
                            <option value="">-- Seleccione Principal --</option>
                            @foreach($mandantes as $m)
                                <option value="{{ $m->id }}">{{ $m->razon_social }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Año de Gestión</label>
                        <input type="number" wire:model.live="anio_seleccionado" wire:change="cargarCalendario" 
                               class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:text-white text-xs py-2 shadow-sm font-bold focus:ring-blue-500">
                    </div>

                    <!-- Información Global del Servicio -->
                    @if($inicio_global)
                        <div class="mt-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <h4 class="text-[10px] font-black text-green-700 dark:text-green-400 uppercase mb-2 tracking-widest">Hito de Inicio Global</h4>
                             <div class="text-[11px] font-bold text-gray-800 dark:text-gray-200 uppercase">
                                PERIODO REMUNERACIONES {{ strtoupper($inicio_global['periodo']) }}
                            </div>
                            <div class="text-[9px] text-green-600 font-bold uppercase mt-1">
                                PERIODO: {{ $inicio_global['periodo'] }}
                            </div>
                            <p class="text-[8px] text-gray-500 italic mt-2 leading-tight">
                                Este es el punto de partida oficial para todos los contratistas de esta principal QUE ESTABAN ACTIVOS EN {{ $inicio_global['mes'] }} DE {{ $inicio_global['anio'] }}.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Grid de Meses (Derecha) -->
            <div class="col-span-1 md:col-span-3">
                @if(!$mandante_id)
                    <div class="bg-white dark:bg-gray-800 p-20 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 text-center uppercase text-xs font-bold text-gray-400 tracking-widest">
                        Seleccione una empresa principal para configurar el calendario.
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach($meses as $num => $mes)
                            <div class="p-4 border rounded-xl transition-all border-l-4 {{ $mes['is_inicio'] ? 'bg-green-50 border-green-500 shadow-md ring-2 ring-green-100' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:shadow-lg' }} {{ !$mes['is_inicio'] && $mes['apertura'] ? 'border-l-blue-500' : '' }}">
                                <div class="text-[11px] font-black {{ $mes['is_inicio'] ? 'text-green-700' : 'text-blue-600 dark:text-blue-400' }} border-b pb-2 mb-4 uppercase tracking-tighter text-center flex flex-col items-center gap-1">
                                    <span>PERIODO REMUNERACIONES {{ strtoupper($mes['periodo']) }}</span>
                                    @if($mes['is_inicio'])
                                        <span class="bg-green-600 text-white text-[8px] px-2 py-0.5 rounded-full tracking-widest">PRIMER MES DE VERIFICACIÓN</span>
                                    @endif
                                </div>
                                <div class="space-y-3">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[8px] uppercase font-black text-gray-400 mb-1">Carga Desde</label>
                                            <input type="date" wire:model="meses.{{ $num }}.apertura" 
                                                   class="w-full rounded border-gray-200 dark:bg-gray-900 dark:text-white text-[10px] py-1 px-1">
                                        </div>
                                        <div>
                                            <label class="block text-[8px] uppercase font-black text-gray-400 mb-1">Carga Hasta</label>
                                            <input type="date" wire:model="meses.{{ $num }}.cierre" 
                                                   class="w-full rounded border-gray-200 dark:bg-gray-900 dark:text-white text-[10px] py-1 px-1">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[8px] uppercase font-black text-blue-500 mb-1">Fecha Emisión Certificado</label>
                                        <input type="date" wire:model="meses.{{ $num }}.emision" 
                                               class="w-full rounded border-blue-100 dark:bg-gray-900 dark:text-white text-[10px] py-1 px-2 font-bold text-blue-700">
                                    </div>
                                    <!-- CAMPOS FUERA DE PLAZO -->
                                    <div class="border-t border-amber-200 pt-2 mt-2 bg-amber-50/50 -mx-3 px-3 pb-2">
                                        <div class="text-[7px] uppercase font-black text-amber-600 mb-2 tracking-widest">⚠️ PERIODO FUERA DE PLAZO</div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-[8px] uppercase font-black text-amber-500 mb-1">Cierre F. Plazo</label>
                                                <input type="date" wire:model="meses.{{ $num }}.cierre_fuera_plazo" 
                                                       class="w-full rounded border-amber-200 bg-amber-50 dark:bg-gray-900 dark:text-white text-[10px] py-1 px-1 text-amber-700 font-bold">
                                            </div>
                                            <div>
                                                <label class="block text-[8px] uppercase font-black text-amber-500 mb-1">Emisión F. Plazo</label>
                                                <input type="date" wire:model="meses.{{ $num }}.emision_fuera_plazo" 
                                                       class="w-full rounded border-amber-200 bg-amber-50 dark:bg-gray-900 dark:text-white text-[10px] py-1 px-1 text-amber-700 font-bold">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex gap-1">
                                        <button wire:click="guardarMes({{ $num }})" 
                                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-black py-2 px-2 rounded-lg text-[9px] uppercase transition-all tracking-wider shadow-sm">
                                            GUARDAR
                                        </button>
                                        @if($mes['id'])
                                            <button wire:click="eliminarMes({{ $num }})" 
                                                    wire:confirm="¿Está seguro de eliminar este periodo? Los contratistas ya no tendrán que verificar este mes."
                                                    title="Eliminar Periodo (No aplica verificación)"
                                                    class="bg-white border-2 border-red-500 text-red-600 hover:bg-red-500 hover:text-white font-black py-2 px-2 rounded-lg text-[9px] uppercase transition-all">
                                                ✕
                                            </button>
                                        @endif
                                        @if($mes['id'] && !$mes['is_inicio'])
                                            <button wire:click="toggleInicio({{ $num }})" 
                                                    title="Marcar como mes de Inicio de Verificación"
                                                    class="bg-white border-2 border-green-500 text-green-600 hover:bg-green-500 hover:text-white font-black py-2 px-2 rounded-lg text-[9px] uppercase transition-all">
                                                ★
                                            </button>
                                        @endif
                                    </div>
                                    @if (session()->has("mes_status_$num"))
                                        <div class="text-[9px] text-center text-green-600 font-black uppercase italic animate-pulse">
                                            {{ session("mes_status_$num") }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if($tab == 'consolidado')
        <div class="bg-white dark:bg-gray-800 p-0 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-4 bg-indigo-900 text-white flex justify-between items-center">
                <h3 class="text-xs font-bold uppercase tracking-widest">Consolidado Fechas del Periodo - {{ $anio_seleccionado }}</h3>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold uppercase opacity-75">Año:</span>
                    <input type="number" wire:model.live="anio_seleccionado" class="bg-indigo-800 border-none rounded text-xs font-bold py-1 w-20 text-white">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Empresa Principal</th>
                            <th class="px-6 py-3 text-center text-[10px] font-black text-gray-500 uppercase tracking-widest">Mes Periodo</th>
                            <th class="px-6 py-3 text-center text-[10px] font-black text-gray-500 uppercase tracking-widest">Fecha Cierre Carga</th>
                            <th class="px-6 py-3 text-center text-[10px] font-black text-gray-500 uppercase tracking-widest">Fecha Emisión ASEM</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($consolidado as $item)
                            <tr class="hover:bg-indigo-50/30 transition-colors">
                                <td class="px-6 py-4 text-xs font-black text-gray-800 dark:text-white uppercase tracking-tighter">
                                    {{ $item->mandante->razon_social }}
                                </td>
                                <td class="px-6 py-4 text-center text-[11px] font-bold text-blue-600 uppercase">
                                    <div class="flex items-center justify-center gap-1">
                                        PERIODO REMUNERACIONES {{ strtoupper($item->nombre_periodo) }}
                                        @if($item->is_inicio)
                                            <span class="text-green-600" title="Mes de Inicio">★</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center text-xs font-medium text-gray-600 dark:text-gray-400">
                                    {{ $item->fecha_cierre->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($item->fecha_emision)
                                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full font-black text-[10px] uppercase tracking-tighter shadow-sm">
                                            {{ $item->fecha_emision->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 italic text-[10px] uppercase">No Definida</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-20 text-center text-gray-400 uppercase text-xs font-black tracking-widest italic opacity-50">
                                    Sin registros configurados para el año {{ $anio_seleccionado }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($tab == 'categorias')
        <div class="bg-white dark:bg-gray-800 p-0 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-4 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-widest">Gestión de Categorías de Verificación</h3>
                <button wire:click="abrirModalCategoria" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1.5 px-4 rounded text-[10px] uppercase tracking-widest transition-all">
                    NUEVA CATEGORÍA
                </button>
            </div>
            
            <div class="p-4 border-b border-gray-100 flex gap-4">
                <input type="text" wire:model.live="filtroCatNombre" placeholder="Buscar categoría..." class="flex-1 rounded-md border-gray-300 text-xs py-2 shadow-sm">
            </div>

            @if (session()->has('cat_status_list'))
                <div class="px-4 py-2 bg-green-50 text-green-700 text-[10px] font-bold uppercase">{{ session('cat_status_list') }}</div>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Descripción</th>
                            <th class="px-6 py-3 text-center text-[10px] font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-center text-[10px] font-bold text-gray-500 uppercase tracking-wider w-24">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($categoriasGestion as $cat)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                                <td class="px-6 py-4 text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-tighter">{{ $cat->nombre }}</td>
                                <td class="px-6 py-4 text-[10px] text-gray-500 dark:text-gray-400 italic">{{ $cat->descripcion ?: '-' }}</td>
                                <td class="px-6 py-4 text-center">
                                    <button wire:click="toggleStatusCategoria({{ $cat->id }})" 
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-widest {{ $cat->is_active ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
                                        {{ $cat->is_active ? 'ACTIVO' : 'INACTIVO' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <button wire:click="abrirModalCategoria({{ $cat->id }})" 
                                            class="text-blue-600 hover:text-blue-800 font-black text-[9px] uppercase tracking-widest px-3 py-1 bg-blue-50 rounded-md border border-blue-200 hover:bg-blue-100 transition-all">
                                        EDITAR
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-400 italic text-xs uppercase tracking-widest">No se encontraron categorías.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- MODAL DE CATEGORÍAS -->
    @if($mostrarModalCategoria)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-100">
                        <h3 class="text-lg leading-6 font-bold text-gray-900 uppercase tracking-tight">
                            {{ $clasificacionActual ? 'Editar Categoría' : 'Crear Nueva Categoría' }}
                        </h3>
                    </div>
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nombre</label>
                            <input type="text" wire:model="cat_nombre" class="w-full rounded-md border-gray-300 text-xs py-2 shadow-sm">
                            @error('cat_nombre') <span class="text-red-500 text-[10px] font-bold italic">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Descripción (Opcional)</label>
                            <textarea wire:model="cat_descripcion" class="w-full rounded-md border-gray-300 text-xs" rows="3"></textarea>
                            @error('cat_descripcion') <span class="text-red-500 text-[10px] font-bold italic">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" wire:model="cat_is_active" id="cat_is_active" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 h-4 w-4">
                            <label for="cat_is_active" class="ml-2 block text-xs font-bold text-gray-700 uppercase">Activo</label>
                        </div>
                        
                        @if (session()->has('cat_status'))
                            <div class="p-2 bg-green-50 text-green-700 text-[10px] font-bold uppercase text-center border border-green-100 rounded">{{ session('cat_status') }}</div>
                        @endif
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="guardarCategoria" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-[10px] font-black text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto uppercase tracking-widest">
                            GUARDAR
                        </button>
                        <button wire:click="$set('mostrarModalCategoria', false)" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-[10px] font-black text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto uppercase tracking-widest">
                            CANCELAR
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- TAB: CATÁLOGO AUDITORÍA                                       --}}
    {{-- ============================================================ --}}
    @if($tab == 'catalogo_auditoria')
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

            {{-- Panel Izquierdo: Formulario --}}
            <div class="col-span-1 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden h-fit">
                <div class="bg-[#1a3560] text-white px-4 py-3">
                    <div class="text-[10px] font-black uppercase tracking-widest">
                        {{ $cat_aud_item_id ? '✎ EDITAR ÍTEM' : '+ NUEVO ÍTEM' }}
                    </div>
                    <div class="text-[8px] text-blue-300 mt-0.5">Catálogo global de auditoría</div>
                </div>
                <div class="p-4 space-y-4">
                    <div>
                        <label class="block text-[9px] font-black text-gray-500 uppercase mb-1.5 tracking-wider">Tipo</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 p-2 rounded-lg border-2 cursor-pointer transition-all {{ $cat_aud_tipo === 'observacion' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}">
                                <input type="radio" wire:model.live="cat_aud_tipo" value="observacion" class="text-blue-600 focus:ring-blue-500">
                                <div>
                                    <div class="text-[9px] font-black uppercase text-blue-700">OBSERVACIÓN</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-2 p-2 rounded-lg border-2 cursor-pointer transition-all {{ $cat_aud_tipo === 'contingencia' ? 'border-rose-500 bg-rose-50' : 'border-gray-200 hover:border-gray-300' }}">
                                <input type="radio" wire:model.live="cat_aud_tipo" value="contingencia" class="text-rose-600 focus:ring-rose-500">
                                <div>
                                    <div class="text-[9px] font-black uppercase text-rose-700">CONTINGENCIA</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-gray-500 uppercase mb-1.5 tracking-wider">Texto del Ítem</label>
                        <textarea wire:model="cat_aud_texto"
                                  rows="4"
                                  placeholder="Ingrese el texto de la observación o contingencia..."
                                  class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-xs py-2 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none"></textarea>
                        @error('cat_aud_texto')
                            <span class="text-red-500 text-[9px] font-bold italic">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="cat_aud_active" id="cat_aud_active"
                               class="w-4 h-4 rounded text-blue-600 border-gray-300 focus:ring-blue-500">
                        <label for="cat_aud_active" class="text-[10px] font-black text-gray-600 uppercase">Activo</label>
                    </div>

                    <div class="flex gap-2 pt-2 border-t border-gray-100">
                        <button wire:click="guardarCatalogoItem"
                                class="flex-1 {{ $cat_aud_tipo === 'contingencia' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-blue-600 hover:bg-blue-700' }} text-white font-black py-2 px-4 rounded-lg text-[10px] uppercase tracking-widest transition-all shadow-sm">
                            {{ $cat_aud_item_id ? 'GUARDAR CAMBIOS' : 'AGREGAR AL CATÁLOGO' }}
                        </button>
                        @if($cat_aud_item_id)
                            <button wire:click="cancelarCatalogoItem"
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-black py-2 px-3 rounded-lg text-[10px] uppercase border border-gray-300 transition-all">
                                ✕
                            </button>
                        @endif
                    </div>

                    @if(session()->has('cat_aud_status'))
                        <div class="p-2 bg-green-50 border border-green-100 rounded-lg text-[10px] text-green-700 font-black uppercase text-center animate-pulse">
                            {{ session('cat_aud_status') }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Panel Derecho: Listado --}}
            <div class="col-span-1 md:col-span-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <h3 class="text-[10px] font-black text-gray-600 dark:text-gray-300 uppercase tracking-widest">Ítems del Catálogo</h3>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 border border-blue-100 text-[8px] font-black px-2 py-0.5 rounded-full uppercase">
                                OBS: {{ $catalogoObsCount }}
                            </span>
                            <span class="inline-flex items-center gap-1 bg-rose-50 text-rose-700 border border-rose-100 text-[8px] font-black px-2 py-0.5 rounded-full uppercase">
                                CONT: {{ $catalogoContCount }}
                            </span>
                        </div>
                    </div>
                    {{-- Filtro por tipo --}}
                    <div class="flex items-center gap-2">
                        <span class="text-[9px] font-black text-gray-400 uppercase">Filtrar:</span>
                        <select wire:model.live="cat_aud_filtro"
                                class="text-[10px] font-bold rounded border-gray-300 dark:bg-gray-700 dark:text-white py-1.5 px-2 shadow-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">— Todos —</option>
                            <option value="observacion">Observaciones</option>
                            <option value="contingencia">Contingencias</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-[9px] font-black text-gray-500 uppercase tracking-wider w-28">Tipo</th>
                                <th class="px-4 py-2.5 text-left text-[9px] font-black text-gray-500 uppercase tracking-wider">Texto del Ítem</th>
                                <th class="px-4 py-2.5 text-center text-[9px] font-black text-gray-500 uppercase tracking-wider w-20">Estado</th>
                                <th class="px-4 py-2.5 text-center text-[9px] font-black text-gray-500 uppercase tracking-wider w-32">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($catalogoItems as $item)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors {{ $cat_aud_item_id === $item->id ? 'bg-blue-50/50 dark:bg-blue-900/10' : '' }}">
                                    <td class="px-4 py-2.5">
                                        @if($item->tipo === 'observacion')
                                            <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 border border-blue-200 text-[8px] font-black px-2 py-0.5 rounded-full uppercase">
                                                ◉ OBS.
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 bg-rose-100 text-rose-700 border border-rose-200 text-[8px] font-black px-2 py-0.5 rounded-full uppercase">
                                                ⚠ CONT.
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-[11px] font-bold text-gray-700 dark:text-gray-200 leading-snug">
                                        {{ $item->texto }}
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        <button wire:click="toggleStatusCatalogoItem({{ $item->id }})"
                                                class="text-[8px] font-black px-2 py-0.5 rounded-full uppercase border transition-all {{ $item->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-gray-50 text-gray-400 border-gray-200 hover:bg-gray-100' }}">
                                            {{ $item->is_active ? 'ACTIVO' : 'INACT.' }}
                                        </button>
                                    </td>
                                    <td class="px-4 py-2.5 text-center whitespace-nowrap">
                                        <button wire:click="editarCatalogoItem({{ $item->id }})"
                                                class="text-blue-600 hover:text-blue-800 font-black text-[9px] uppercase px-2 py-1 bg-blue-50 rounded border border-blue-100 hover:bg-blue-100 transition-all">
                                            EDITAR
                                        </button>
                                        <button wire:click="eliminarCatalogoItem({{ $item->id }})"
                                                wire:confirm="¿Eliminar este ítem del catálogo? Las contingencias ya registradas mantendrán su texto."
                                                class="text-red-500 hover:text-red-700 font-black text-[9px] uppercase px-2 py-1 bg-red-50 rounded border border-red-100 hover:bg-red-100 transition-all ml-1">
                                            BORRAR
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-16 text-center text-gray-400 text-[10px] font-black uppercase tracking-widest italic">
                                        Sin ítems en el catálogo. Cree el primero con el formulario de la izquierda.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

</div>
