<div class="p-6 bg-white dark:bg-gray-800 shadow-md rounded-lg">

    {{-- Título --}}
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">
            Importar Verificaciones Históricas
        </h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Carga masiva de resultados de la <strong>Dotación Anterior</strong> al sistema.
            Los datos importados sirven como base para el primer período de verificación.
        </p>
    </div>

    {{-- Flash de error global --}}
    @if (session()->has('error'))
        <div class="mb-4 px-4 py-3 bg-red-100 border border-red-400 text-red-700 rounded-md dark:bg-red-700 dark:text-red-100">
            {{ session('error') }}
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- SECCIÓN: CARGA DEL ARCHIVO                                        --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

        {{-- Columna Izquierda: Instrucciones + Formulario --}}
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">

            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Paso 1: Preparar el Archivo
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                Descargue la plantilla Excel. Complete las columnas obligatorias (marcadas con * en rojo).
            </p>
            <div class="mb-4 p-3 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded-md text-xs text-amber-800 dark:text-amber-300">
                <strong>⚠️ Regla clave:</strong> Las retenciones son período-específicas y <strong>NO se arrastran</strong>.
                Este módulo registra el historial; las retenciones del nuevo período se calculan desde cero.
            </div>
            <button wire:click="downloadTemplate" class="btn-secondary w-full sm:w-auto">
                <x-icons.download class="h-5 w-5 inline-block mr-2"/>
                Descargar Plantilla (.xlsx)
            </button>

            <hr class="my-6 border-gray-200 dark:border-gray-700">

            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Paso 2: Subir y Procesar
            </h3>
            <form wire:submit.prevent="import">
                <div class="mb-4">
                    <input type="file" wire:model="file"
                        class="block w-full text-sm text-gray-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-full file:border-0
                            file:text-sm file:font-semibold
                            file:bg-indigo-50 file:text-indigo-700
                            hover:file:bg-indigo-100
                            dark:file:bg-indigo-900/50 dark:file:text-indigo-300 dark:hover:file:bg-indigo-900"
                    />
                    @error('file')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn-primary w-full sm:w-auto"
                    wire:loading.attr="disabled" wire:target="file,import">
                    <div wire:loading.remove wire:target="import">
                        <x-icons.upload class="h-5 w-5 inline-block mr-2"/>
                        Importar Verificaciones Históricas
                    </div>
                    <div wire:loading wire:target="import">
                        <x-icons.spinner class="h-5 w-5 inline-block mr-2 animate-spin"/>
                        Procesando...
                    </div>
                </button>
            </form>
        </div>

        {{-- Columna Derecha: Resultados --}}
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Resultados de la Importación
            </h3>

            {{-- Spinner de carga --}}
            <div wire:loading wire:target="import" class="text-center py-8">
                <x-icons.spinner class="h-12 w-12 text-indigo-500 animate-spin mx-auto"/>
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    Analizando y validando el archivo...
                </p>
            </div>

            {{-- Estado vacío --}}
            @if (!$importing && !$importFinished)
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <x-icons.clipboard-list class="h-12 w-12 mx-auto mb-4"/>
                    Los resultados aparecerán aquí.
                </div>
            @endif

            {{-- Resultados disponibles --}}
            @if ($importFinished)
                <div class="space-y-4">

                    {{-- Resumen general --}}
                    @php
                        $hasErrors = ($importResults['failure_count'] ?? 0) > 0;
                        $hasSuccess = ($importResults['success_count'] ?? 0) > 0 || ($importResults['updated_count'] ?? 0) > 0;
                        $colorClass = $hasErrors ? 'bg-yellow-50 dark:bg-yellow-900/50' : 'bg-green-50 dark:bg-green-900/50';
                        $textClass  = $hasErrors ? 'text-yellow-800 dark:text-yellow-200' : 'text-green-800 dark:text-green-200';
                        $bodyClass  = $hasErrors ? 'text-yellow-700 dark:text-yellow-300' : 'text-green-700 dark:text-green-300';
                    @endphp
                    <div class="p-4 rounded-lg {{ $colorClass }}">
                        <h4 class="font-semibold text-lg {{ $textClass }}">Proceso Finalizado</h4>
                        <div class="mt-2 text-sm {{ $bodyClass }} space-y-1">
                            <p>
                                <span class="font-bold">{{ $importResults['success_count'] ?? 0 }}</span>
                                registros nuevos creados.
                            </p>
                            <p>
                                <span class="font-bold">{{ $importResults['updated_count'] ?? 0 }}</span>
                                registros actualizados (ya existían).
                            </p>
                            <p>
                                <span class="font-bold">{{ $importResults['failure_count'] ?? 0 }}</span>
                                filas con errores.
                            </p>
                        </div>
                    </div>

                    {{-- Detalle de errores --}}
                    @if (!empty($importResults['failures']))
                        <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/50">
                            <h4 class="font-semibold text-red-800 dark:text-red-200">
                                Detalle de Errores
                            </h4>
                            <div class="mt-2 max-h-56 overflow-y-auto text-sm text-red-700 dark:text-red-300 space-y-2">
                                @foreach($importResults['failures'] as $failure)
                                    <div class="border-t border-red-200 dark:border-red-800 pt-1">
                                        <p>
                                            <span class="font-semibold">
                                                Fila {{ is_array($failure) ? $failure['row'] : $failure->row() }}:
                                            </span>
                                            {{ is_array($failure) ? $failure['errors'] : implode(', ', $failure->errors()) }}
                                        </p>
                                        <p class="text-xs text-red-500 dark:text-red-400">
                                            Columna: {{ is_array($failure) ? $failure['attribute'] : $failure->attribute() }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Botón nueva importación --}}
                    <div class="pt-2">
                        <button wire:click="resetImport" class="btn-secondary w-full">
                            Realizar otra importación
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- SECCIÓN: PREVIEW + CONFIRMACIÓN DEL SNAPSHOT                       --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if ($mostrarPreviewSnapshot && !$snapshotEjecutado)
        <div class="mt-8 border-2 border-indigo-300 dark:border-indigo-700 rounded-lg p-6 bg-indigo-50 dark:bg-indigo-900/20">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 text-3xl">🗂️</div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-indigo-900 dark:text-indigo-200">
                        Paso 3: Snapshot de Dotación Anterior
                    </h3>
                    <p class="mt-1 text-sm text-indigo-700 dark:text-indigo-300">
                        El sistema detectó que puede crear la dotación inicial en
                        <strong>{{ $previewSnapshot['total_carpetas'] ?? 0 }} carpeta(s)</strong> de verificación,
                        incorporando un total de
                        <strong>{{ $previewSnapshot['total_trabajadores'] ?? 0 }} trabajador(es)</strong>
                        con tipo <code class="bg-indigo-100 dark:bg-indigo-800 px-1 rounded text-xs">DOTACION_ANTERIOR</code>.
                    </p>
                    <p class="mt-1 text-xs text-indigo-500 dark:text-indigo-400">
                        ⚠️ Esto <strong>NO genera retenciones</strong>. Sólo marca la presencia histórica del trabajador.
                        Las retenciones del nuevo período se calculan desde cero.
                    </p>

                    {{-- Detalle del preview --}}
                    @if (!empty($previewSnapshot['detalles']))
                        <div class="mt-4 max-h-48 overflow-y-auto">
                            <table class="w-full text-xs text-left text-gray-600 dark:text-gray-400">
                                <thead>
                                    <tr class="text-indigo-800 dark:text-indigo-300 font-semibold border-b border-indigo-200 dark:border-indigo-700">
                                        <th class="py-1 pr-4">ID_REGISTRO</th>
                                        <th class="py-1 pr-4">Carpeta #</th>
                                        <th class="py-1 pr-4">Período</th>
                                        <th class="py-1">Trabajadores</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($previewSnapshot['detalles'] as $d)
                                        <tr class="border-b border-indigo-100 dark:border-indigo-800">
                                            <td class="py-1 pr-4 font-mono">{{ $d['id_registro'] }}</td>
                                            <td class="py-1 pr-4">#{{ $d['carpeta_id'] }}</td>
                                            <td class="py-1 pr-4">{{ $d['periodo'] }}</td>
                                            <td class="py-1 font-semibold text-indigo-700 dark:text-indigo-300">{{ $d['trabajadores'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="mt-5 flex gap-3">
                        <button wire:click="ejecutarSnapshot" class="btn-primary"
                            wire:loading.attr="disabled" wire:target="ejecutarSnapshot">
                            <div wire:loading.remove wire:target="ejecutarSnapshot">
                                ✅ Confirmar y Aplicar Snapshot
                            </div>
                            <div wire:loading wire:target="ejecutarSnapshot">
                                <x-icons.spinner class="h-4 w-4 inline-block mr-1 animate-spin"/>
                                Aplicando...
                            </div>
                        </button>
                        <button wire:click="$set('mostrarPreviewSnapshot', false)" class="btn-secondary">
                            Omitir por ahora
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- SECCIÓN: RESULTADO DEL SNAPSHOT                                    --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if ($snapshotEjecutado && !empty($snapshotResults))
        <div class="mt-6 border border-green-300 dark:border-green-700 rounded-lg p-6 bg-green-50 dark:bg-green-900/20">
            <h3 class="text-lg font-semibold text-green-800 dark:text-green-200 mb-3">
                ✅ Snapshot de Dotación Anterior Aplicado
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                <div class="text-center">
                    <p class="text-3xl font-bold text-green-700 dark:text-green-300">
                        {{ $snapshotResults['carpetas_procesadas'] ?? 0 }}
                    </p>
                    <p class="text-green-600 dark:text-green-400">Carpetas procesadas</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-green-700 dark:text-green-300">
                        {{ $snapshotResults['trabajadores_creados'] ?? 0 }}
                    </p>
                    <p class="text-green-600 dark:text-green-400">Trabajadores en snapshot</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">
                        {{ $snapshotResults['carpetas_omitidas'] ?? 0 }}
                    </p>
                    <p class="text-amber-600 dark:text-amber-400">Carpetas omitidas</p>
                </div>
            </div>

            @if (!empty($snapshotResults['advertencias']))
                <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded-md">
                    <p class="text-xs font-semibold text-amber-700 dark:text-amber-300 mb-1">Advertencias:</p>
                    <ul class="text-xs text-amber-600 dark:text-amber-400 list-disc list-inside space-y-1 max-h-40 overflow-y-auto">
                        @foreach($snapshotResults['advertencias'] as $adv)
                            <li>{{ $adv }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    @push('styles')
    <style>
        .label-form    { @apply block text-sm font-medium text-gray-700 dark:text-gray-300; }
        .input-field   { @apply mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200; }
        .error-message { @apply text-red-500 text-xs mt-1; }
        .btn-primary   { @apply inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed; }
        .btn-secondary { @apply inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150; }
    </style>
    @endpush
</div>
