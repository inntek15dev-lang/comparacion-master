<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Cumplimiento de Contratistas
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                <div class="mb-6 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Datos actualizados por última vez:</p>
                        <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $fechaCache }}</p>
                    </div>
                    <div>
                        @if (!$confirmingRecalculo)
                            <button wire:click="solicitarConfirmacionRecalculo" wire:loading.attr="disabled" class="btn-secondary">
                                <x-icons.refresh class="w-5 h-5 mr-2"/> Forzar Recálculo en Vivo
                            </button>
                        @else
                            <div class="flex items-center space-x-2">
                                <span class="text-sm font-medium text-yellow-700 dark:text-yellow-300">Esta operación puede tardar. ¿Continuar?</span>
                                <button wire:click="forzarRecalculoEnVivo" class="btn-danger text-xs">Sí, Recalcular</button>
                                <button wire:click="cancelarRecalculo" class="btn-secondary text-xs">No</button>
                            </div>
                        @endif
                    </div>
                </div>

                <div x-data="{ open: false }" class="mb-6 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">Opciones de Reporte</h3>
                        <button @click="open = !open" class="btn-secondary">
                            <x-icons.download class="w-5 h-5 mr-2"/>
                            <span x-show="!open">Mostrar Opciones</span>
                            <span x-show="open">Ocultar Opciones</span>
                        </button>
                    </div>
                    <div x-show="open" x-transition class="mt-4 pt-4 border-t border-gray-300 dark:border-gray-600">
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                            <div class="sm:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Seleccione Formatos:</label>
                                <div class="mt-2 flex space-x-4">
                                    <label class="flex items-center"><input type="checkbox" wire:model.defer="formatosExportacion" value="excel" class="form-checkbox"> <span class="ml-2">Excel</span></label>
                                    <label class="flex items-center"><input type="checkbox" wire:model.defer="formatosExportacion" value="pdf" class="form-checkbox"> <span class="ml-2">PDF</span></label>
                                    <label class="flex items-center"><input type="checkbox" wire:model.defer="formatosExportacion" value="html" class="form-checkbox"> <span class="ml-2">HTML Interactivo</span></label>
                                </div>
                                @error('formatosExportacion') <span class="error-message">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <button wire:click="exportarReportes" wire:loading.attr="disabled" class="btn-primary w-full">
                                    <span wire:loading.remove wire:target="exportarReportes">Generar Reportes</span>
                                    <span wire:loading wire:target="exportarReportes">Generando...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar por Razón Social o RUT/NIT/RUC/CNPJ del Contratista..." class="input-field w-full">
                </div>

                <div wire:loading.flex wire:target="forzarRecalculoEnVivo" class="fixed inset-0 bg-gray-900 bg-opacity-60 z-50 items-center justify-center">
                    <div class="text-center text-white">
                        <x-icons.spinner class="w-12 h-12 mx-auto mb-4"/>
                        <p class="text-lg font-semibold">Calculando datos en tiempo real...</p>
                        <p>Este proceso puede tardar varios minutos. Por favor, espere.</p>
                    </div>
                </div>

                <div class="overflow-x-auto shadow-md sm:rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="table-header text-center w-12">#</th>
                                <th class="table-header text-center w-20">ID</th>
                                <th wire:click="setSortBy('razon_social')" class="table-header cursor-pointer">Contratista <x-sort-icon field="razon_social" :sortField="$sortBy" :sortDirection="$sortDir" /></th>
                                
                                @if(in_array('EMPRESA', $entidadesControlables))
                                <th wire:click="setSortBy('cumplimiento_empresa')" class="table-header text-center cursor-pointer">Empresa (%) <x-sort-icon field="cumplimiento_empresa" :sortField="$sortBy" :sortDirection="$sortDir" /></th>
                                @endif
                                @if(in_array('PERSONA', $entidadesControlables))
                                <th wire:click="setSortBy('promedio_trabajadores')" class="table-header text-center cursor-pointer">Trabajadores (% / Total) <x-sort-icon field="promedio_trabajadores" :sortField="$sortBy" :sortDirection="$sortDir" /></th>
                                @endif
                                @if(in_array('VEHICULO', $entidadesControlables))
                                <th wire:click="setSortBy('promedio_vehiculos')" class="table-header text-center cursor-pointer">Vehículos (% / Total) <x-sort-icon field="promedio_vehiculos" :sortField="$sortBy" :sortDirection="$sortDir" /></th>
                                @endif
                                @if(in_array('MAQUINARIA', $entidadesControlables))
                                <th wire:click="setSortBy('promedio_maquinarias')" class="table-header text-center cursor-pointer">Maquinaria (% / Total) <x-sort-icon field="promedio_maquinarias" :sortField="$sortBy" :sortDirection="$sortDir" /></th>
                                @endif
                                @if(in_array('EMBARCACION', $entidadesControlables))
                                <th wire:click="setSortBy('promedio_embarcaciones')" class="table-header text-center cursor-pointer">Embarcaciones (% / Total) <x-sort-icon field="promedio_embarcaciones" :sortField="$sortBy" :sortDirection="$sortDir" /></th>
                                @endif
                                
                                <th class="table-header text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php 
                                $correlativoPrincipal = 0; 
                                $correlativoSub = 1;
                            @endphp
                            @forelse ($contratistasOrdenados as $item)
                                @php
                                    $esSubcontratista = !empty($item['contratista_padre_id']);
                                    $esContratistaPrincipal = !$esSubcontratista;

                                    $siguienteItem = $contratistasOrdenados->get($loop->index + 1);
                                    $siguienteEsSubDelActual = $siguienteItem && !empty($siguienteItem['contratista_padre_id']) && $siguienteItem['contratista_padre_id'] == $item['id'];
                                    $esPadreDeGrupo = $esContratistaPrincipal && $siguienteEsSubDelActual;

                                    if ($esContratistaPrincipal) {
                                        $correlativoPrincipal++;
                                        $correlativoSub = 1;
                                    }
                                @endphp
                                <tr @class([
                                    'hover:bg-gray-100 dark:hover:bg-gray-700',
                                    'border-b-2 border-gray-300 dark:border-gray-600',
                                    'bg-violet-50 dark:bg-violet-900/20' => $esPadreDeGrupo || $esSubcontratista,
                                    'bg-white dark:bg-gray-800' => !$esPadreDeGrupo && !$esSubcontratista,
                                ])>
                                    <td class="table-cell text-center border-r dark:border-gray-600">
                                        @if ($esSubcontratista)
                                            {{ $correlativoPrincipal }}.{{ $correlativoSub++ }}
                                        @else
                                            {{ $correlativoPrincipal }}
                                        @endif
                                    </td>
                                    <td class="table-cell text-center border-r dark:border-gray-600 font-mono text-xs">
                                        @if ($esSubcontratista)
                                            <span class="text-gray-500">{{ $item['contratista_padre_id'] }}</span> / <span>{{ $item['id'] }}</span>
                                        @else
                                            {{ $item['id'] }}
                                        @endif
                                    </td>
                                    <td class="table-cell font-medium border-r dark:border-gray-600 {{ $esSubcontratista ? 'pl-4' : '' }}">
                                        <div class="flex items-center">
                                            @if ($esSubcontratista)
                                                <div class="w-4 h-full mr-2 flex justify-center">
                                                    <span class="border-l-2 border-gray-300 dark:border-gray-600 h-full"></span>
                                                </div>
                                            @endif
                                            <div>
                                                <span class="text-gray-900 dark:text-gray-100">{{ $item['razon_social'] }}</span>
                                                <span class="block text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $item['rut'] }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    @if(in_array('EMPRESA', $entidadesControlables))
                                    <td class="table-cell text-center border-r dark:border-gray-600">
                                        @if(isset($item['cumplimiento_empresa']))
                                        <span class="font-semibold {{ $item['cumplimiento_empresa'] < 100 ? 'text-orange-600' : 'text-green-600' }}">
                                            {{ $item['cumplimiento_empresa'] }}%
                                        </span>
                                        @endif
                                    </td>
                                    @endif
                                    @if(in_array('PERSONA', $entidadesControlables))
                                    <td class="table-cell text-center border-r dark:border-gray-600">
                                        @if(isset($item['promedio_trabajadores']))
                                        <span class="font-semibold {{ $item['promedio_trabajadores']['promedio'] < 100 ? 'text-orange-600' : 'text-green-600' }}">
                                            {{ $item['promedio_trabajadores']['promedio'] }}%
                                        </span>
                                        <span class="text-gray-500 dark:text-gray-400"> ({{ $item['promedio_trabajadores']['total'] }})</span>
                                        @endif
                                    </td>
                                    @endif
                                    @if(in_array('VEHICULO', $entidadesControlables))
                                    <td class="table-cell text-center border-r dark:border-gray-600">
                                        @if(isset($item['promedio_vehiculos']))
                                        <span class="font-semibold {{ $item['promedio_vehiculos']['promedio'] < 100 ? 'text-orange-600' : 'text-green-600' }}">
                                            {{ $item['promedio_vehiculos']['promedio'] }}%
                                        </span>
                                        <span class="text-gray-500 dark:text-gray-400"> ({{ $item['promedio_vehiculos']['total'] }})</span>
                                        @endif
                                    </td>
                                    @endif
                                    @if(in_array('MAQUINARIA', $entidadesControlables))
                                    <td class="table-cell text-center border-r dark:border-gray-600">
                                        @if(isset($item['promedio_maquinarias']))
                                        <span class="font-semibold {{ $item['promedio_maquinarias']['promedio'] < 100 ? 'text-orange-600' : 'text-green-600' }}">
                                            {{ $item['promedio_maquinarias']['promedio'] }}%
                                        </span>
                                        <span class="text-gray-500 dark:text-gray-400"> ({{ $item['promedio_maquinarias']['total'] }})</span>
                                        @endif
                                    </td>
                                    @endif
                                    @if(in_array('EMBARCACION', $entidadesControlables))
                                    <td class="table-cell text-center border-r dark:border-gray-600">
                                        @if(isset($item['promedio_embarcaciones']))
                                        <span class="font-semibold {{ $item['promedio_embarcaciones']['promedio'] < 100 ? 'text-orange-600' : 'text-green-600' }}">
                                            {{ $item['promedio_embarcaciones']['promedio'] }}%
                                        </span>
                                        <span class="text-gray-500 dark:text-gray-400"> ({{ $item['promedio_embarcaciones']['total'] }})</span>
                                        @endif
                                    </td>
                                    @endif

                                    <td class="table-cell text-center">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <a href="{{ route('mandante.supervision-detalle', ['contratistaId' => $item['id']]) }}" class="text-blue-600 hover:text-blue-800 hover:underline text-xs font-semibold" title="Ver Matriz de Cumplimiento">
                                                EXCEPCIONES
                                            </a>
                                            <a href="{{ route('mandante.atalaya-documental', ['contratistaId' => $item['id']]) }}" class="text-blue-600 hover:text-blue-800 hover:underline text-xs font-semibold" title="Ver Atalaya Documental">
                                                DETALLE DOCUMENTOS
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 4 + count($entidadesControlables) }}" class="table-cell text-center">
                                        @if($calculandoEnVivo)
                                            Calculando...
                                        @else
                                            No hay datos de contratistas para mostrar. Intente un recálculo en vivo o ajuste su búsqueda.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($contratistasOrdenados->isNotEmpty())
                        <tfoot>
                            <tr class="bg-gray-100 dark:bg-gray-700 font-semibold text-gray-700 dark:text-gray-200 border-t-2 border-gray-300 dark:border-gray-600">
                                <td colspan="3" class="table-cell"></td>
                                @if(in_array('EMPRESA', $entidadesControlables))
                                    <td class="table-cell text-center border-l dark:border-gray-600">{{ $totales['total_empresas_texto'] }}</td>
                                @endif
                                @if(in_array('PERSONA', $entidadesControlables))
                                    <td class="table-cell text-center border-l dark:border-gray-600">{{ $totales['total_trabajadores'] }}</td>
                                @endif
                                @if(in_array('VEHICULO', $entidadesControlables))
                                    <td class="table-cell text-center border-l dark:border-gray-600">{{ $totales['total_vehiculos'] }}</td>
                                @endif
                                @if(in_array('MAQUINARIA', $entidadesControlables))
                                    <td class="table-cell text-center border-l dark:border-gray-600">{{ $totales['total_maquinarias'] }}</td>
                                @endif
                                @if(in_array('EMBARCACION', $entidadesControlables))
                                    <td class="table-cell text-center border-l dark:border-gray-600">{{ $totales['total_embarcaciones'] }}</td>
                                @endif
                                <td class="table-cell border-l dark:border-gray-600"></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>