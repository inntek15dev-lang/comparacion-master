<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Módulo de Inteligencia de Facturación') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    @if (session()->has('error'))
                        <div class="alert-danger mb-4">{{ session('error') }}</div>
                    @endif

                    {{-- Panel de Filtros --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border dark:border-gray-600 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 items-end">
                            <div>
                                <label for="mandanteId" class="label-form">Principal</label>
                                <select wire:model.live="mandanteId" id="mandanteId" class="input-field w-full">
                                    <option value="">-- Todas las Principales --</option>
                                    @foreach($mandantes as $mandante)
                                        <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                                    @endforeach
                                </select>
                                @error('mandanteId') <span class="error-message">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="lg:col-span-1">
                                <label for="contratistaId" class="label-form">Contratista (Opcional)</label>
                                <select wire:model="contratistaId" id="contratistaId" class="input-field w-full" @if(empty($contratistasDisponibles)) disabled @endif>
                                    <option value="">-- Todos los Contratistas --</option>
                                    @foreach($contratistasDisponibles as $contratista)
                                        <option value="{{ $contratista->id }}">{{ $contratista->razon_social }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="fechaDesde" class="label-form">Fecha Desde <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="fechaDesde" id="fechaDesde" class="input-field w-full">
                                @error('fechaDesde') <span class="error-message">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="fechaHasta" class="label-form">Fecha Hasta <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="fechaHasta" id="fechaHasta" class="input-field w-full">
                                @error('fechaHasta') <span class="error-message">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button wire:click="generarReporte" class="btn-primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="generarReporte">
                                    <x-icons.calculator class="w-5 h-5 mr-1 inline-block"/> Generar Reporte
                                </span>
                                <span wire:loading wire:target="generarReporte">
                                    <x-icons.spinner class="w-5 h-5 mr-1 inline-block"/> Calculando...
                                </span>
                            </button>
                        </div>
                    </div>

                    {{-- Panel de Resultados --}}
                    @if (!empty($resultados))
                        <div class="mt-8">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold">Resultados del Período</h3>
                                <div class="flex flex-wrap gap-2 justify-end">
                                    <div class="border-r pr-2 border-gray-300 dark:border-gray-600">
                                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">SOLO RESUMEN:</span>
                                        <div class="flex space-x-2 mt-1">
                                            <button wire:click="exportarResumenSolo('excel')" class="btn-secondary text-xs !py-1 !px-2 !bg-blue-100 !text-blue-800 hover:!bg-blue-200 dark:!bg-blue-800 dark:!text-blue-100 dark:hover:!bg-blue-700" wire:loading.attr="disabled" @if($resultados['total_general'] === 0) disabled @endif>
                                                <x-icons.excel class="w-4 h-4 mr-1"/> Excel
                                            </button>
                                            <button wire:click="exportarResumenSolo('pdf')" class="btn-secondary text-xs !py-1 !px-2 !bg-blue-100 !text-blue-800 hover:!bg-blue-200 dark:!bg-blue-800 dark:!text-blue-100 dark:hover:!bg-blue-700" wire:loading.attr="disabled" @if($resultados['total_general'] === 0) disabled @endif>
                                                <x-icons.pdf class="w-4 h-4 mr-1"/> PDF
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">RESUMEN Y DETALLE:</span>
                                        <div class="flex space-x-2 mt-1">
                                            <button wire:click="exportar('excel')" class="btn-secondary text-xs !py-1 !px-2 !bg-green-100 !text-green-800 hover:!bg-green-200 dark:!bg-green-800 dark:!text-green-100 dark:hover:!bg-green-700" wire:loading.attr="disabled" @if($resultados['total_general'] === 0) disabled @endif>
                                                <x-icons.excel class="w-4 h-4 mr-1"/> Excel
                                            </button>
                                            <button wire:click="exportar('pdf')" class="btn-secondary text-xs !py-1 !px-2 !bg-red-100 !text-red-800 hover:!bg-red-200 dark:!bg-red-800 dark:!text-red-100 dark:hover:!bg-red-700" wire:loading.attr="disabled" @if($resultados['total_general'] === 0) disabled @endif>
                                                <x-icons.pdf class="w-4 h-4 mr-1"/> PDF
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="overflow-x-auto shadow-md sm:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            @if($showMandanteColumn)
                                                <th class="table-header">Principal</th>
                                            @endif
                                            <th class="table-header">Razón Social Contratista</th>
                                            <th class="table-header">RUT/NIT/RUC/CNPJ Contratista</th>
                                            <th class="table-header text-center">N° Trabajadores Facturables</th>
                                            <th class="table-header text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @forelse ($resultados['resumen'] as $resumen)
                                            <tr class="table-row-hover">
                                                @if($showMandanteColumn)
                                                    <td class="table-cell font-semibold">{{ $resumen->mandante_nombre }}</td>
                                                @endif
                                                <td class="table-cell">{{ $resumen->razon_social }}</td>
                                                <td class="table-cell">{{ $resumen->rut_contratista }}</td>
                                                <td class="table-cell text-center font-bold text-lg">{{ $resumen->trabajadores_facturables }}</td>
                                                <td class="table-cell text-center">
                                                    <button wire:click="abrirModalDetalle({{ $resumen->contratista_id }}, {{ $resumen->mandante_id }})" class="btn-secondary text-xs !py-1 !px-3">
                                                        Ver Trabajadores
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ $showMandanteColumn ? '5' : '4' }}" class="table-cell text-center">No se encontraron trabajadores facturables para el período y los filtros seleccionados.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot class="bg-gray-100 dark:bg-gray-900">
                                        <tr class="font-bold text-gray-800 dark:text-white">
                                            <td class="px-6 py-3 text-right text-sm uppercase" colspan="{{ $showMandanteColumn ? '3' : '2' }}">Total General</td>
                                            <td class="px-6 py-3 text-center text-xl">{{ $resultados['total_general'] }}</td>
                                            <td class="px-6 py-3"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Detalle de Trabajadores --}}
    @if ($showModalDetalle)
        <div class="fixed z-20 inset-0 overflow-y-auto" aria-labelledby="modal-title-detalle" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="cerrarModalDetalle"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title-detalle">
                                    Detalle de Trabajadores Facturables
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Contratista: {{ $detalleContratista->razon_social ?? 'N/A' }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Principal: {{ $detalleContratista->mandante_nombre ?? 'N/A' }}
                                </p>
                                <div class="mt-4 max-h-96 overflow-y-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                                            <tr>
                                                <th class="table-header-sm">RUT/NUIP/DNI/CEDULA/CPF Trabajador</th>
                                                <th class="table-header-sm">Nombre Completo</th>
                                                <th class="table-header-sm">Fecha Creación</th>
                                                <th class="table-header-sm">Fecha Baja</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach ($detalleTrabajadores as $trabajador)
                                                <tr>
                                                    <td class="table-cell-sm">{{ $trabajador->rut }}</td>
                                                    <td class="table-cell-sm">{{ $trabajador->nombre_completo }}</td>
                                                    <td class="table-cell-sm">{{ \Carbon\Carbon::parse($trabajador->created_at)->format('d-m-Y') }}</td>
                                                    {{-- ================== INICIO DE LA RECTIFICACIÓN CANÓNICA ================== --}}
                                                    <td class="table-cell-sm">{{ $trabajador->deleted_at ? \Carbon\Carbon::parse($trabajador->deleted_at)->format('d-m-Y') : 'Activo' }}</td>
                                                    {{-- ================== FIN DE LA RECTIFICACIÓN CANÓNICA ==================== --}}
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse items-center">
                        <button type="button" wire:click="cerrarModalDetalle" class="btn-secondary ml-3">
                            Cerrar
                        </button>
                        <button wire:click="exportarDetalle('excel')" class="btn-secondary !bg-green-100 !text-green-800 hover:!bg-green-200 dark:!bg-green-800 dark:!text-green-100 dark:hover:!bg-green-700" wire:loading.attr="disabled">
                            <x-icons.excel class="w-4 h-4 mr-1"/> Exportar Detalle (Excel)
                        </button>
                        <button wire:click="exportarDetalle('pdf')" class="btn-secondary !bg-red-100 !text-red-800 hover:!bg-red-200 dark:!bg-red-800 dark:!text-red-100 dark:hover:!bg-red-700 mr-3" wire:loading.attr="disabled">
                            <x-icons.pdf class="w-4 h-4 mr-1"/> Exportar Detalle (PDF)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>