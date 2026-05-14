<div class="p-6 bg-white dark:bg-gray-800 shadow-md rounded-lg">

    {{-- ─── TÍTULO ─── --}}
    <div class="flex items-center gap-3 mb-2">
        <div class="p-2 rounded-lg bg-violet-100 dark:bg-violet-900/40">
            <svg class="h-6 w-6 text-violet-600 dark:text-violet-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
        </div>
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">Sincronizar Documentos desde Sistema Obsoleto</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Trae documentos validados del sistema anterior aplicando lógica de calidad documental.</p>
        </div>
    </div>

    {{-- ─── BANNER INFORMATIVO ─── --}}
    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg">
        <p class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-2">⚖ Lógica de Calidad Documental (Motor de Decisión)</p>
        <div class="space-y-1 text-xs text-blue-700 dark:text-blue-300">
            <p>✅ <strong>Entrante Aprobado + Vigente</strong> → Siempre <strong>gana</strong>. El documento existente pasa a Archivado.</p>
            <p>⏭ <strong>Entrante Rechazado o Vencido vs. Existente Aprobado + Vigente</strong> → <strong>Pierde</strong>. El entrante se archiva; el existente queda intacto.</p>
            <p>✅ <strong>Entrante Rechazado/Vencido vs. Existente también Malo o sin documento previo</strong> → El entrante <strong>gana</strong> (es lo mejor disponible).</p>
        </div>
    </div>

    {{-- ─── ALERTAS SESSION ─── --}}
    @if (session()->has('error'))
        <div class="mb-4 px-4 py-3 bg-red-100 border border-red-400 text-red-700 rounded-md dark:bg-red-700 dark:text-red-100 dark:border-red-600">
            {{ session('error') }}
        </div>
    @endif
    @if (session()->has('message'))
        <div class="mb-4 px-4 py-3 bg-green-100 border border-green-400 text-green-700 rounded-md dark:bg-green-700 dark:text-green-100 dark:border-green-600">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

        {{-- ─── COLUMNA IZQUIERDA: Pasos ─── --}}
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Paso 1: Preparar los Archivos</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                1. Suba los PDFs al repositorio de sincronización.<br>
                2. Descargue y complete la planilla Excel.<br>
                3. El motor de decisión determinará automáticamente qué queda activo.
            </p>

            <div class="space-y-4 mb-6">
                {{-- Contador de archivos en carpeta sync --}}
                <div class="flex items-center justify-between p-4 bg-violet-50 dark:bg-violet-900/30 rounded-lg border border-violet-100 dark:border-violet-800">
                    <div>
                        <p class="text-xs font-semibold text-violet-600 dark:text-violet-400 uppercase tracking-wider">Repositorio Sincronización</p>
                        <p class="text-2xl font-bold text-violet-900 dark:text-violet-100">
                            {{ number_format($totalArchivosFisicos) }}
                            <span class="text-sm font-normal">archivos físicos</span>
                        </p>
                    </div>
                    <div class="flex flex-col gap-2">
                        <button type="button" onclick="openUploadModalSync()"
                            class="inline-flex items-center justify-center px-3 py-1.5 bg-violet-600 text-white text-xs font-bold rounded hover:bg-violet-700 transition ease-in-out duration-150">
                            Subir Archivos
                        </button>
                        @if($totalArchivosFisicos > 0)
                            <button type="button" wire:click="clearTemporalFolder"
                                wire:confirm="¿Está seguro de vaciar la carpeta de sincronización?"
                                class="text-xs text-red-600 hover:text-red-700 dark:text-red-400 font-medium">
                                Vaciar repositorio
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Selector de Principal --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Principal <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="mandante_id"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm">
                        <option value="">-- Seleccione Principal --</option>
                        @foreach($mandantes as $mandante)
                            <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Botón descargar plantilla --}}
            <button type="button" wire:click="downloadTemplate"
                @if(!$mandante_id) disabled @endif
                class="inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition ease-in-out duration-150 w-full disabled:opacity-50 disabled:cursor-not-allowed mb-6">
                <svg class="h-5 w-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Descargar Plantilla de Sincronización
            </button>

            <hr class="my-6 border-gray-200 dark:border-gray-700">

            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Paso 2: Ejecutar Sincronización</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Suba el Excel completado. El sistema aplicará la lógica de calidad fila por fila.
            </p>
            <form wire:submit.prevent="import">
                <div class="mb-4">
                    <input type="file" wire:model="file"
                        class="block w-full text-sm text-gray-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-full file:border-0
                            file:text-sm file:font-semibold
                            file:bg-violet-50 file:text-violet-700
                            hover:file:bg-violet-100 dark:file:bg-violet-900/50 dark:file:text-violet-300"
                    />
                    @error('file') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <button type="submit"
                    class="inline-flex items-center justify-center px-4 py-2 bg-violet-600 text-white font-semibold rounded-md hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 transition ease-in-out duration-150 disabled:opacity-50 w-full sm:w-auto"
                    wire:loading.attr="disabled" wire:target="file,import">
                    <div wire:loading.remove wire:target="import">
                        <svg class="h-5 w-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Sincronizar Documentos
                    </div>
                    <div wire:loading wire:target="import">
                        <svg class="h-5 w-5 inline-block mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Aplicando lógica de calidad...
                    </div>
                </button>
            </form>
        </div>

        {{-- ─── COLUMNA DERECHA: Resultados ─── --}}
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Resultados de la Sincronización</h3>

            <div wire:loading wire:target="import" class="text-center py-8">
                <svg class="h-12 w-12 text-violet-500 animate-spin mx-auto" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">Evaluando calidad documental y procesando...</p>
            </div>

            @if (!$importing && !$importFinished)
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="h-12 w-12 mx-auto mb-4 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Los resultados del motor de decisión aparecerán aquí.
                </div>
            @endif

            @if ($importFinished && isset($importResults['vivos']))
                <div class="space-y-4">
                    {{-- Resumen de 4 contadores --}}
                    <div class="grid grid-cols-2 gap-3">
                        {{-- Documentos vivos --}}
                        <div class="p-3 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700">
                            <p class="text-xs text-green-600 dark:text-green-400 font-semibold uppercase">✅ Quedaron Activos</p>
                            <p class="text-3xl font-bold text-green-800 dark:text-green-200">{{ $importResults['vivos'] ?? 0 }}</p>
                            <p class="text-xs text-green-600 dark:text-green-400">documentos en estado Revisado</p>
                        </div>
                        {{-- Documentos viejos archivados --}}
                        <div class="p-3 rounded-lg bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700">
                            <p class="text-xs text-amber-600 dark:text-amber-400 font-semibold uppercase">📦 Viejos Archivados</p>
                            <p class="text-3xl font-bold text-amber-800 dark:text-amber-200">{{ $importResults['archivados'] ?? 0 }}</p>
                            <p class="text-xs text-amber-600 dark:text-amber-400">superados por el entrante</p>
                        </div>
                        {{-- Entrantes descartados --}}
                        <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700">
                            <p class="text-xs text-blue-600 dark:text-blue-400 font-semibold uppercase">⏭ Entrantes Descartados</p>
                            <p class="text-3xl font-bold text-blue-800 dark:text-blue-200">{{ $importResults['descartados'] ?? 0 }}</p>
                            <p class="text-xs text-blue-600 dark:text-blue-400">el existente era mejor (quedó vivo)</p>
                        </div>
                        {{-- Errores --}}
                        <div class="p-3 rounded-lg {{ count($importResults['failures'] ?? []) > 0 ? 'bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700' : 'bg-gray-50 dark:bg-gray-700/30 border border-gray-200 dark:border-gray-700' }}">
                            <p class="text-xs font-semibold uppercase {{ count($importResults['failures'] ?? []) > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">❌ Errores de Fila</p>
                            <p class="text-3xl font-bold {{ count($importResults['failures'] ?? []) > 0 ? 'text-red-800 dark:text-red-200' : 'text-gray-600 dark:text-gray-300' }}">{{ count($importResults['failures'] ?? []) }}</p>
                            <p class="text-xs {{ count($importResults['failures'] ?? []) > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">filas no procesadas</p>
                        </div>
                    </div>

                    {{-- Lista de errores --}}
                    @if (!empty($importResults['failures']))
                        <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/50 max-h-60 overflow-y-auto">
                            <h4 class="font-semibold text-red-800 dark:text-red-200 mb-2">Detalle de errores:</h4>
                            @foreach($importResults['failures'] as $failure)
                                <p class="text-xs text-red-700 dark:text-red-300 py-0.5">
                                    <strong>Fila {{ $failure['row'] }}:</strong> {{ $failure['errors'] }}
                                </p>
                            @endforeach
                        </div>
                    @endif

                    <button type="button" wire:click="resetImport"
                        class="px-4 py-2 border border-violet-300 dark:border-violet-600 rounded-md text-sm font-medium text-violet-700 dark:text-violet-300 hover:bg-violet-50 dark:hover:bg-violet-900/30 w-full transition">
                        Nueva Sincronización
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- ─── MODAL DROPZONE (carpeta sync) ─── --}}
    <div id="uploadModalSync" wire:ignore class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-4xl w-full p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-xl font-bold dark:text-white text-gray-900">Repositorio de Sincronización</h3>
                        <p class="text-xs text-violet-600 dark:text-violet-400 font-medium mt-1">
                            Carpeta: <code>storage/app/public/importar_documentos_sincronizacion/</code>
                        </p>
                    </div>
                    <button type="button" onclick="closeUploadModalSync()" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6 italic">
                    Arrastre aquí los PDFs del sistema obsoleto que desea sincronizar.
                    Esta carpeta es independiente de la del importador masivo.
                </p>

                <div id="sync-dropzone"
                    class="border-2 border-dashed border-violet-400 rounded-lg p-12 text-center bg-violet-50/20 dark:bg-violet-900/10 cursor-pointer">
                    <div class="text-violet-500 mb-4 flex justify-center">
                        <svg class="h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <span class="text-lg font-medium dark:text-gray-300">Haga clic o arrastre sus documentos aquí.</span>
                </div>

                <div class="mt-6 flex items-center justify-between">
                    <p id="sync-upload-status" class="text-sm font-semibold text-violet-600"></p>
                    <button type="button" onclick="closeUploadModalSync()"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded-lg font-bold transition duration-200">
                        Finalizar y Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Dropzone.autoDiscover = false;

            var syncDropzoneEl = document.getElementById('sync-dropzone');
            if (syncDropzoneEl && !syncDropzoneEl.dropzone) {
                window.mySyncDropzone = new Dropzone('#sync-dropzone', {
                    url: '{{ route("gestion.sincronizar.documentos.fisicos") }}',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    maxFilesize: 50,
                    parallelUploads: 5,
                    timeout: 0,
                    acceptedFiles: '.pdf,.jpg,.jpeg,.png',
                    init: function () {
                        this.on('success', function () {
                            var status = document.getElementById('sync-upload-status');
                            status.innerText = 'Procesados: ' + this.getUploadedFiles().length + ' archivos.';
                        });
                        this.on('queuecomplete', function () {
                            document.getElementById('sync-upload-status').innerText = '¡Carga completada!';
                            Livewire.dispatch('actualizarConteoEventSync');
                        });
                    }
                });
            }

            window.openUploadModalSync = function () {
                document.getElementById('uploadModalSync').classList.remove('hidden');
            };
            window.closeUploadModalSync = function () {
                document.getElementById('uploadModalSync').classList.add('hidden');
                if (window.mySyncDropzone) {
                    window.mySyncDropzone.removeAllFiles(true);
                }
                Livewire.dispatch('actualizarConteoEventSync');
            };
        });
    </script>
</div>
