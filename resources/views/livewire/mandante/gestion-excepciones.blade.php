<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Gestión Rápida de Excepciones
        </h2>
    </x-slot>

    <div class="py-12">
        {{-- ================== INICIO DE LA MODIFICACIÓN 1 ================== --}}
        {{-- Se eliminan las clases "max-w-7xl" y "mx-auto" para que el contenedor ocupe todo el ancho --}}
        <div class="px-4 sm:px-6 lg:px-8">
        {{-- =================== FIN DE LA MODIFICACIÓN 1 ==================== --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                
                <!-- SECCIÓN DE FILTROS -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label for="searchContratista" class="label-form">1. Busque un Contratista</label>
                        <input wire:model.live.debounce.300ms="searchContratista" id="searchContratista" type="text" placeholder="Razón Social o NIT..." class="input-field w-full">
                    </div>
                    <div>
                        <label for="filtroLugarTrabajoId" class="label-form">(Opcional) Filtrar por Lugar de Trabajo/Departamento</label>
                        <select wire:model.live="filtroLugarTrabajoId" id="filtroLugarTrabajoId" class="input-field w-full">
                            <option value="todos">-- Todos los Lugares de Trabajo --</option>
                            @foreach($lugaresTrabajoDisponibles as $lugar)
                                <option value="{{ $lugar->id }}">{{ $lugar->nombre_jerarquico }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filtroUoId" class="label-form">(Opcional) Filtrar por U.O.</label>
                        <select wire:model.live="filtroUoId" id="filtroUoId" class="input-field w-full" @if(count($unidadesOrganizacionalesDisponibles) == 0) disabled @endif>
                            <option value="todos">-- Todas las U.O. --</option>
                            @foreach($unidadesOrganizacionalesDisponibles as $uo)
                                <option value="{{ $uo->id }}">{{ $uo->nombre_jerarquico }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div wire:loading wire:target="buscarContextos" class="w-full text-center py-4">
                    <x-icons.spinner class="w-8 h-8 mx-auto text-indigo-600"/>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Buscando contextos...</p>
                </div>

                <div wire:loading.remove wire:target="buscarContextos">
                    @if(!is_null($contextosEncontrados))
                        <div class="overflow-x-auto shadow-md sm:rounded-lg mt-6">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="table-header">#</th>
                                        <th class="table-header">Contratista</th>
                                        <th class="table-header">Lugar de Trabajo/Departamento</th>
                                        <th class="table-header">U.O.</th>
                                        <th class="table-header text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse ($contextosEncontrados as $contexto)
                                        <tr class="table-row-hover">
                                            <td class="table-cell font-mono">{{ $loop->iteration }}</td>
                                            <td class="table-cell font-medium">
                                                {{ $contexto->contratista_razon_social }}
                                                <span class="block text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $contexto->contratista_rut }}</span>
                                            </td>
                                            <td class="table-cell">{{ \App\Models\Dependencia::find($contexto->dependencia_id)->nombre_jerarquico }}</td>
                                            <td class="table-cell">{{ \App\Models\UnidadOrganizacionalMandante::find($contexto->uo_id)->nombre_jerarquico }}</td>
                                            <td class="table-cell text-center">
                                                {{-- ================== INICIO DE LA MODIFICACIÓN 2 ================== --}}
                                                {{-- Se cambian las clases para que el texto sea azul como un enlace --}}
                                                <a href="{{ route('mandante.supervision-detalle', ['contratistaId' => $contexto->contratista_id, 'mandanteId' => $mandanteId, 'lugarDeTrabajoId' => $contexto->dependencia_id, 'uoId' => $contexto->uo_id]) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-semibold">
                                                    Gestionar Excepciones
                                                </a>
                                                {{-- =================== FIN DE LA MODIFICACIÓN 2 ==================== --}}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="table-cell text-center">
                                                @if(!empty($searchContratista))
                                                    No se encontraron contextos para el contratista "{{ $searchContratista }}" con los filtros seleccionados.
                                                @else
                                                    No se encontraron contratistas con operaciones activas.
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>