<div>
    <div class="p-6 bg-white border-b border-gray-200 shadow-sm sm:rounded-lg">
        
        <div class="mb-6 border-b border-gray-200">
            <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                <button 
                    wire:click="seleccionarInforme('tiempos')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200
                        {{ $informeActivo === 'tiempos' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Tiempos de Validación
                </button>

                <button 
                    wire:click="seleccionarInforme('rechazos')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200
                        {{ $informeActivo === 'rechazos' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Texto de Rechazos
                </button>
            </nav>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
            <div class="flex flex-wrap items-start gap-x-6 gap-y-4">
                
                <div class="min-w-max">
                    <label for="mandante" class="block text-sm font-medium text-gray-700">Principal</label>
                    <select wire:model.live="mandanteId" id="mandante" class="mt-1 block w-64 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="">Todos</option>
                        @foreach($mandantes as $mandante)
                            <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="min-w-max">
                    <label for="contratista" class="block text-sm font-medium text-gray-700">Contratista</label>
                    <select wire:model.live="contratistaId" id="contratista" class="mt-1 block w-64 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="">Todos</option>
                        @foreach($contratistas as $contratista)
                            <option value="{{ $contratista->id }}">{{ $contratista->razon_social }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="min-w-max">
                    <label for="nombreDocumento" class="block text-sm font-medium text-gray-700">Nombre Documento</label>
                    <select wire:model.live="nombreDocumento" id="nombreDocumento" class="mt-1 block w-64 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="">Todos</option>
                        @foreach($nombresDocumentos as $doc)
                            <option value="{{ $doc->nombre }}">{{ $doc->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- ================================================================== -->
                <!-- INICIO DE LA MODIFICACIÓN CANÓNICA: AÑADIR FILTRO VALIDADOR -->
                <!-- ================================================================== -->
                <div class="min-w-max">
                    <label for="validadorId" class="block text-sm font-medium text-gray-700">Validador</label>
                    <select wire:model.live="validadorId" id="validadorId" class="mt-1 block w-64 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="">Todos</option>
                        @foreach($validadores as $validador)
                            <option value="{{ $validador->id }}">{{ $validador->name }}</option>
                        @endforeach
                    </select>
                </div>
                <!-- ================================================================== -->
                <!-- FIN DE LA MODIFICACIÓN CANÓNICA -->
                <!-- ================================================================== -->

                <div class="min-w-max">
                    <label for="entidadType" class="block text-sm font-medium text-gray-700">Entidad</label>
                    <select wire:model.live="entidadType" id="entidadType" class="mt-1 block w-48 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="">Todas</option>
                        <option value="App\Models\Contratista">Empresa</option>
                        <option value="App\Models\Trabajador">Trabajador</option>
                        <option value="App\Models\Vehiculo">Vehículo</option>
                        <option value="App\Models\Maquinaria">Maquinaria</option>
                        <option value="App\Models\Embarcacion">Embarcación</option>
                    </select>
                </div>

                <div class="min-w-max">
                    <label for="resultadoValidacion" class="block text-sm font-medium text-gray-700">Resultado Validación</label>
                    <select wire:model.live="resultadoValidacion" id="resultadoValidacion" class="mt-1 block w-48 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" @if($informeActivo === 'rechazos') disabled @endif>
                        <option value="">Todos</option>
                        <option value="Aprobado">Aprobado</option>
                        <option value="Rechazado">Rechazado</option>
                    </select>
                </div>

                @if ($informeActivo === 'tiempos')
                <div x-data="{ open: false }" @click.away="open = false" class="relative min-w-max">
                    <label for="estadoValidacionBtn" class="block text-sm font-medium text-gray-700">Estado Validacion</label>
                    <button @click="open = !open" type="button" id="estadoValidacionBtn" class="mt-1 relative w-48 bg-white border border-gray-300 rounded-md shadow-sm pl-3 pr-10 py-2 text-left cursor-default focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <span class="flex items-center">
                            <span class="block truncate">
                                <span x-show="$wire.estadoValidacion.length === 0">Todos</span>
                                <span x-show="$wire.estadoValidacion.length > 0">
                                    <span x-text="$wire.estadoValidacion.length"></span> seleccionado(s)
                                </span>
                            </span>
                        </span>
                        <span class="ml-3 absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </button>
                    <div x-show="open" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute mt-1 w-full rounded-md bg-white shadow-lg z-10 border border-gray-200">
                        <div class="p-2 space-y-1">
                            @foreach($listaEstados as $key => $label)
                            <label class="flex items-center w-full px-2 py-1.5 text-sm rounded-md hover:bg-gray-100 cursor-pointer">
                                <input type="checkbox" wire:model.live="estadoValidacion" value="{{ $key }}" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-3 text-gray-700">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <div class="min-w-max">
                    <label for="fechaDesde" class="block text-sm font-medium text-gray-700">Validacion Desde</label>
                    <input type="date" wire:model.live="fechaDesde" id="fechaDesde" class="mt-1 block w-48 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div class="min-w-max">
                    <label for="fechaHasta" class="block text-sm font-medium text-gray-700">Validacion Hasta</label>
                    <input type="date" wire:model.live="fechaHasta" id="fechaHasta" class="mt-1 block w-48 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                
                <div class="min-w-max">
                    <label for="fechaCargaDesde" class="block text-sm font-medium text-gray-700">Carga Desde</label>
                    <input type="date" wire:model.live="fechaCargaDesde" id="fechaCargaDesde" class="mt-1 block w-48 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div class="min-w-max">
                    <label for="fechaCargaHasta" class="block text-sm font-medium text-gray-700">Carga Hasta</label>
                    <input type="date" wire:model.live="fechaCargaHasta" id="fechaCargaHasta" class="mt-1 block w-48 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </div>
            <div class="mt-4 flex justify-end items-center space-x-3">
                <!-- ================================================================== -->
                <!-- INICIO DE LA MODIFICACIÓN CANÓNICA: BOTÓN INFORME INTERACTIVO -->
                <!-- ================================================================== -->
                @if ($informeActivo === 'tiempos')
                <button wire:click="generarInformeInteractivo" wire:loading.attr="disabled" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2M9 7V3a1 1 0 011-1h4a1 1 0 011 1v4m-6 0h6"></path></svg>
                    Generar Informe Interactivo
                </button>
                @endif
                <!-- ================================================================== -->
                <!-- FIN DE LA MODIFICACIÓN CANÓNICA -->
                <!-- ================================================================== -->
                <button wire:click="exportarExcel" wire:loading.attr="disabled" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Exportar a Excel
                </button>
            </div>
        </div>

        @if ($informeActivo === 'tiempos')
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Doc.</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Documento</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Principal</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contratista</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recurso</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Validador</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Carga</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">FECHA DE VALIDACION</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiempo Validación (Hrs)</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RESULTADO VALIDACION</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ESTADO VALIDACION</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($datos as $documento)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $datos->firstItem() + $loop->index }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $documento->id }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $documento->nombre_documento_snapshot }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $documento->mandante->razon_social ?? 'N/A' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $documento->contratista->razon_social ?? 'N/A' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $this->getNombreRecurso($documento->entidad) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $documento->validadorAsem->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($documento->created_at)->format('d-m-Y H:i') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($documento->fecha_validacion)->format('d-m-Y H:i') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-indigo-600 text-center">
                                    {{ $this->calcularHorasValidacion($documento->created_at, $documento->fecha_validacion) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $documento->resultado_validacion == 'Aprobado' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $documento->resultado_validacion }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $documento->estado_validacion }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="px-6 py-12 text-center text-sm text-gray-500">No se encontraron documentos que coincidan con los filtros seleccionados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif ($informeActivo === 'rechazos')
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Doc.</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Documento</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mandante</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contratista</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recurso</th>
                            <!-- ================================================================== -->
                            <!-- INICIO DE LA MODIFICACIÓN CANÓNICA: AÑADIR COLUMNA VALIDADOR -->
                            <!-- ================================================================== -->
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Validador</th>
                            <!-- ================================================================== -->
                            <!-- FIN DE LA MODIFICACIÓN CANÓNICA -->
                            <!-- ================================================================== -->
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RECHAZO</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($datos as $fila)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $datos->firstItem() + $loop->index }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $fila->id }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $fila->nombre_documento_snapshot }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $fila->mandante->razon_social ?? 'N/A' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $fila->contratista->razon_social ?? 'N/A' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $this->getNombreRecurso($fila->entidad) }}</td>
                                <!-- ================================================================== -->
                                <!-- INICIO DE LA MODIFICACIÓN CANÓNICA: AÑADIR CELDA VALIDADOR -->
                                <!-- ================================================================== -->
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $fila->validadorAsem->name ?? 'N/A' }}</td>
                                <!-- ================================================================== -->
                                <!-- FIN DE LA MODIFICACIÓN CANÓNICA -->
                                <!-- ================================================================== -->
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $fila->texto_rechazo }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500">No se encontraron documentos rechazados que coincidan con los filtros seleccionados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <div class="mt-6">
            @if($datos)
                {{ $datos->links() }}
            @endif
        </div>
    </div>
</div>