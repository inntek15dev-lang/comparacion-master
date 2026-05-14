<div class="p-6 bg-white dark:bg-gray-800 shadow-md rounded-lg">

    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">
            Importar Dotación Anterior (Trabajadores)
        </h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Carga masiva de trabajadores que formarán la base de la primera carpeta de verificación del sistema.
            Soporta trabajadores activos, nuevos y finiquitados.
        </p>
    </div>

    @if (session()->has('error'))
        <div class="mb-4 px-4 py-3 bg-red-100 border border-red-400 text-red-700 rounded-md">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        {{-- Columna Izquierda --}}
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Paso 1: Excel con Dotación
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Seleccione el Principal/Mandante que usará. La plantilla incluirá en un listado interno sus Lugares y Contratos válidos.
                <br>La fecha de finiquito es obligatoria para los reportados como "Finiquitado".
            </p>

            <div class="mb-4">
                <label for="mandante_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Principal / Mandante *
                </label>
                <select id="mandante_id" wire:model="mandante_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm sm:text-sm">
                    <option value="">-- Seleccione un Mandante --</option>
                    @foreach($mandantes as $mandante)
                        <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                    @endforeach
                </select>
                @error('mandante_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label for="filtro_contratista_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Contratista (Opcional)
                </label>
                <select id="filtro_contratista_id" wire:model="filtro_contratista_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm sm:text-sm">
                    <option value="">-- Todas las Contratistas --</option>
                    @foreach($contratistas as $contratista)
                        <option value="{{ $contratista->id }}">{{ $contratista->razon_social }}</option>
                    @endforeach
                </select>
                <span class="text-xs text-gray-500">Seleccione si desea limitar la descarga a una contratista específica.</span>
            </div>

            <div class="mb-6">
                <label for="filtro_periodo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Período a descargar (Opcional)
                </label>
                <input type="text" id="filtro_periodo" wire:model="filtro_periodo" placeholder="YYYY-MM (ej: 2024-11)" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm sm:text-sm" />
                <span class="text-xs text-gray-500">
                    - Si lo deja vacío: Se generará la plantilla vacía con dos ejemplos ficticios.<br>
                    - Si ingresa un mes: <strong>Descargará la dotación real verificada en ese mes.</strong> Útil para arrastrar trabajadores al mes siguiente.
                </span>
                @error('filtro_periodo') <br><span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <button wire:click="downloadTemplate" class="inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-md hover:bg-gray-50 transition w-full sm:w-auto">
                <x-icons.download class="h-5 w-5 inline-block mr-2"/>
                Descargar Plantilla
            </button>

            <hr class="my-6 border-gray-200 dark:border-gray-700">

            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Paso 2: Subir y Procesar
            </h3>
            <form wire:submit.prevent="import">
                <div class="mb-4">
                    <input type="file" wire:model="file" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/50 dark:file:text-indigo-300" />
                    @error('file') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition w-full sm:w-auto disabled:opacity-50" wire:loading.attr="disabled" wire:target="file,import">
                    <div wire:loading.remove wire:target="import">
                        <x-icons.upload class="h-5 w-5 inline-block mr-2"/>
                        Cargar Dotación
                    </div>
                    <div wire:loading wire:target="import">
                        <x-icons.spinner class="h-5 w-5 inline-block mr-2 animate-spin"/> Procesando...
                    </div>
                </button>
            </form>
        </div>

        {{-- Columna Derecha --}}
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Resultados
            </h3>

            <div wire:loading wire:target="import" class="text-center py-8">
                <x-icons.spinner class="h-12 w-12 text-indigo-500 animate-spin mx-auto"/>
            </div>

            @if (!$importing && !$importFinished)
                <div class="text-center py-8 text-gray-500">
                    <x-icons.clipboard-list class="h-12 w-12 mx-auto mb-4"/>
                    Resultados aparecerán aquí.
                </div>
            @endif

            @if ($importFinished)
                <div class="space-y-4">
                    @php
                        $hasErrors = ($importResults['failure_count'] ?? 0) > 0;
                        $colorClass = $hasErrors ? 'bg-yellow-50 text-yellow-800' : 'bg-green-50 text-green-800';
                    @endphp
                    <div class="p-4 rounded-lg {{ $colorClass }}">
                        <h4 class="font-semibold text-lg">Proceso Finalizado</h4>
                        <ul class="mt-2 text-sm space-y-1">
                            <li><span class="font-bold">{{ $importResults['activos'] ?? 0 }}</span> creados como DOTACIÓN ANTERIOR (Activo)</li>
                            <li><span class="font-bold">{{ $importResults['nuevos'] ?? 0 }}</span> creados como VIGENTE (Nuevo)</li>
                            <li><span class="font-bold">{{ $importResults['finiquitados'] ?? 0 }}</span> marcados como FINIQUITADO</li>
                            <li><span class="font-bold">{{ $importResults['movidos'] ?? 0 }}</span> marcados como MOVIDO (se verificó su traslado)</li>
                            <li><span class="font-bold">{{ $importResults['omitidos'] ?? 0 }}</span> omitidos (ya estaban en la carpeta)</li>
                            <li><span class="font-bold">{{ $importResults['failure_count'] ?? 0 }}</span> errores de validación presentados</li>
                        </ul>
                    </div>

                    @if (!empty($importResults['failures']))
                        <div class="p-4 rounded-lg bg-red-50">
                            <h4 class="font-semibold text-red-800">Errores de Fila</h4>
                            <div class="mt-2 text-sm text-red-700 max-h-48 overflow-y-auto space-y-2">
                                @foreach($importResults['failures'] as $f)
                                    <div class="border-t border-red-200 pt-1">
                                        <p><span class="font-semibold">Fila {{ is_array($f) ? $f['row'] : $f->row() }}:</span> {{ is_array($f) ? $f['errors'] : implode(', ', $f->errors()) }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="pt-2">
                        <button wire:click="resetImport" class="btn-secondary w-full border border-gray-300 rounded px-4 py-2 font-semibold">
                            Cargar otro archivo
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
