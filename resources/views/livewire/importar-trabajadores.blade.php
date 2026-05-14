<div class="p-6 bg-white dark:bg-gray-800 shadow-md rounded-lg">
    <h2 class="text-2xl font-semibold text-gray-800 dark:text-white mb-6">Importación Masiva de Trabajadores</h2>

    @if (session()->has('error'))
        <div class="mb-4 px-4 py-3 bg-red-100 border border-red-400 text-red-700 rounded-md dark:bg-red-700 dark:text-red-100 dark:border-red-600">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Columna de Instrucciones y Carga -->
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Paso 1: Preparar el Archivo</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Seleccione el Principal y el Contratista (o <strong>TODAS</strong>) para descargar la plantilla.
                Si ya hay trabajadores registrados, <strong>se exportarán pre-rellenados</strong> para que puedas modificar y re-importar (cargo, UO, lugar, datos personales).
                Las columnas con asterisco (*) son obligatorias.
            </p>

            <div class="space-y-4 mb-4">
                <div>
                    <label class="label-form">Principal *</label>
                    <select wire:model.live="mandante_id" class="input-field">
                        <option value="">Seleccione Principal...</option>
                        @foreach($mandantes as $mandante)
                            <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                        @endforeach
                    </select>
                    @error('mandante_id') <span class="error-message">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="label-form">Contratista *</label>
                    <select wire:model.live="contratista_id" class="input-field" {{ empty($mandante_id) ? 'disabled' : '' }}>
                        <option value="">Seleccione Contratista...</option>
                        @if (!empty($contratistas) && count($contratistas) > 0)
                            <option value="todas">— TODAS LAS CONTRATISTAS —</option>
                        @endif
                        @foreach($contratistas as $contratista)
                            <option value="{{ $contratista->id }}">{{ $contratista->razon_social }}</option>
                        @endforeach
                    </select>
                    @error('contratista_id') <span class="error-message">{{ $message }}</span> @enderror
                </div>
            </div>

            <button wire:click="downloadTemplate" class="btn-secondary w-full sm:w-auto {{ empty($mandante_id) || empty($contratista_id) ? 'opacity-50 cursor-not-allowed' : '' }}" {{ empty($mandante_id) || empty($contratista_id) ? 'disabled' : '' }}>
                <x-icons.download class="h-5 w-5 inline-block mr-2"/>
                Descargar Plantilla
            </button>

            <hr class="my-6 border-gray-200 dark:border-gray-700">

            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Paso 2: Subir y Procesar</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Una vez que su archivo esté completo, súbalo aquí para iniciar el proceso de importación de las fichas de los trabajadores.
            </p>
            <form wire:submit.prevent="import">
                <div class="mb-4">
                    <input type="file" wire:model="file" class="block w-full text-sm text-gray-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-full file:border-0
                        file:text-sm file:font-semibold
                        file:bg-indigo-50 file:text-indigo-700
                        hover:file:bg-indigo-100 dark:file:bg-indigo-900/50 dark:file:text-indigo-300 dark:hover:file:bg-indigo-900
                    "/>
                    @error('file') <span class="error-message">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="btn-primary w-full sm:w-auto" wire:loading.attr="disabled" wire:target="file,import">
                    <div wire:loading.remove wire:target="import">
                        <x-icons.upload class="h-5 w-5 inline-block mr-2"/>
                        Importar Trabajadores
                    </div>
                    <div wire:loading wire:target="import">
                        <x-icons.spinner class="h-5 w-5 inline-block mr-2 animate-spin"/>
                        Procesando...
                    </div>
                </button>
            </form>
        </div>

        <!-- Columna de Resultados -->
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Resultados de la Importación</h3>
            
            <div wire:loading wire:target="import" class="text-center py-8">
                <x-icons.spinner class="h-12 w-12 text-indigo-500 animate-spin mx-auto"/>
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">Analizando y procesando el archivo. Esto puede tardar unos momentos...</p>
            </div>

            @if (!$importing && !$importFinished)
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <x-icons.clipboard-list class="h-12 w-12 mx-auto mb-4"/>
                    Los resultados del proceso de importación aparecerán aquí.
                </div>
            @endif

            @if ($importFinished)
                <div class="space-y-4">
                    <div class="p-4 rounded-lg {{ $importResults['failure_count'] > 0 ? 'bg-yellow-50 dark:bg-yellow-900/50' : 'bg-green-50 dark:bg-green-900/50' }}">
                        <h4 class="font-semibold text-lg {{ $importResults['failure_count'] > 0 ? 'text-yellow-800 dark:text-yellow-200' : 'text-green-800 dark:text-green-200' }}">
                            Proceso Finalizado
                        </h4>
                        <div class="mt-2 flex items-center text-sm {{ $importResults['failure_count'] > 0 ? 'text-yellow-700 dark:text-yellow-300' : 'text-green-700 dark:text-green-300' }}">
                            <div class="flex-1">
                                <p><span class="font-bold">{{ $importResults['success_count'] }}</span> trabajadores <strong>creados</strong> exitosamente.</p>
                                @if (($importResults['update_count'] ?? 0) > 0)
                                    <p class="text-blue-700 dark:text-blue-300"><span class="font-bold">{{ $importResults['update_count'] }}</span> trabajadores <strong>actualizados</strong> (datos + vinculación).</p>
                                @endif
                                @if (($importResults['warning_count'] ?? 0) > 0)
                                    <p class="text-orange-700 dark:text-orange-300"><span class="font-bold">{{ $importResults['warning_count'] }}</span> trabajadores enviados a Reserva (cuota excedida).</p>
                                @endif
                                <p><span class="font-bold">{{ $importResults['failure_count'] }}</span> filas con errores.</p>
                            </div>
                        </div>
                    </div>

                    @if (!empty($importResults['warnings']))
                        <div class="p-4 rounded-lg bg-orange-50 dark:bg-orange-900/50">
                            <h4 class="font-semibold text-orange-800 dark:text-orange-200">⚠️ Trabajadores en Reserva (Cuota Excedida)</h4>
                            <p class="text-xs text-orange-600 dark:text-orange-400 mt-1 mb-2">Estos trabajadores fueron creados correctamente, pero sin asignación de Lugar/U.O./Cargo porque se superó la cuota. Encuéntralos usando el filtro "VER TRABAJADORES EN RESERVA".</p>
                            <div class="mt-2 max-h-60 overflow-y-auto text-sm text-orange-700 dark:text-orange-300 space-y-2">
                                @foreach($importResults['warnings'] as $warning)
                                    <div class="border-t border-orange-200 dark:border-orange-800 pt-1">
                                        <p><span class="font-semibold">Fila {{ $warning['row'] }}:</span> {{ $warning['trabajador'] }}</p>
                                        <p class="text-xs text-orange-600 dark:text-orange-400">{{ $warning['motivo'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (!empty($importResults['failures']))
                        <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/50">
                            <h4 class="font-semibold text-red-800 dark:text-red-200">Detalle de Errores</h4>
                            <div class="mt-2 max-h-60 overflow-y-auto text-sm text-red-700 dark:text-red-300 space-y-2">
                                @foreach($importResults['failures'] as $failure)
                                    <div class="border-t border-red-200 dark:border-red-800 pt-1">
                                        <p><span class="font-semibold">Fila {{ is_array($failure) ? $failure['row'] : $failure->row() }}:</span> {{ is_array($failure) ? $failure['errors'] : implode(', ', $failure->errors()) }}</p>
                                        <p class="text-xs text-red-600 dark:text-red-400"> (Error en columna: {{ is_array($failure) ? $failure['attribute'] : $failure->attribute() }})</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="pt-4">
                        <button wire:click="resetImport" class="btn-secondary w-full">
                            Realizar otra importación
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('styles')
    <style>
        .label-form { @apply block text-sm font-medium text-gray-700 dark:text-gray-300; }
        .input-field { @apply mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200; }
        .input-error { @apply border-red-500 dark:border-red-500; }
        .error-message { @apply text-red-500 text-xs mt-1; }
        .btn-primary { @apply inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed; }
        .btn-secondary { @apply inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150; }
    </style>
    @endpush
</div>