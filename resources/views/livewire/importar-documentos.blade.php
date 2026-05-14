<div class="p-6 bg-white dark:bg-gray-800 shadow-md rounded-lg">
    <h2 class="text-2xl font-semibold text-gray-800 dark:text-white mb-6">Importación Masiva de Documentos</h2>

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
        <!-- Columna de Instrucciones y Carga -->
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Paso 1: Preparar el Archivo</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                1. Suba los archivos físicos (PDFs) al repositorio del sistema.
                <br>2. Descargue y complete la plantilla Excel.
            </p>

            <div class="space-y-4 mb-6">
                <!-- Contador de archivos -->
                <div class="flex items-center justify-between p-4 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg border border-indigo-100 dark:border-indigo-800">
                    <div>
                        <p class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Repositorio Temporal</p>
                        <p class="text-2xl font-bold text-indigo-900 dark:text-indigo-100">{{ number_format($totalArchivosFisicos) }} <span class="text-sm font-normal">archivos físicos</span></p>
                    </div>
                    <div class="flex flex-col gap-2">
                        <button type="button" onclick="openUploadModal()" class="inline-flex items-center justify-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-bold rounded hover:bg-indigo-700 transition ease-in-out duration-150">
                            Subir Archivos
                        </button>
                        @if($totalArchivosFisicos > 0)
                            <button type="button" wire:click="clearTemporalFolder" wire:confirm="¿Está seguro de vaciar el repositorio temporal?" class="text-xs text-red-600 hover:text-red-700 dark:text-red-400 font-medium">
                                Vaciar repositorio
                            </button>
                        @endif
                    </div>
                </div>

                <div class="space-y-3 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Principal <span class="text-red-500">*</span></label>
                        <select wire:model.live="mandante_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">-- Seleccione Principal --</option>
                            @foreach($mandantes as $mandante)
                                <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($mandante_id)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contratista (Opcional)</label>
                        <select wire:model.live="contratista_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">-- Todos los contratistas --</option>
                            @foreach($contratistas as $contratista)
                                <option value="{{ $contratista['id'] }}">{{ $contratista['display'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    @if($mandante_id)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre de Documento (Opcional)</label>
                        <select wire:model.defer="regla_documental_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">-- Todos los documentos --</option>
                            @foreach($reglas as $regla)
                                <option value="{{ $regla->id }}">{{ $regla->nombreDocumento->nombre ?? 'Documento sin nombre' }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>

                <button type="button" wire:click="downloadTemplate" 
                        @if(!$mandante_id) disabled @endif
                        class="inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition ease-in-out duration-150 w-full disabled:opacity-50 disabled:cursor-not-allowed">
                    <x-icons.download class="h-5 w-5 inline-block mr-2"/>
                    Descargar Plantilla
                </button>
            </div>

            <hr class="my-6 border-gray-200 dark:border-gray-700">

            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Paso 2: Subir y Procesar</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Una vez que su archivo Excel esté completo, súbalo aquí para registrar los documentos en el sistema.
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
                    @error('file') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition ease-in-out duration-150 disabled:opacity-50 w-full sm:w-auto" wire:loading.attr="disabled" wire:target="file,import">
                    <div wire:loading.remove wire:target="import">
                        <x-icons.upload class="h-5 w-5 inline-block mr-2"/>
                        Importar Documentos
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
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">Analizando y procesando el archivo...</p>
            </div>

            @if (!$importing && !$importFinished)
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <x-icons.clipboard-list class="h-12 w-12 mx-auto mb-4"/>
                    Los resultados del proceso de importación aparecerán aquí.
                </div>
            @endif

            @if ($importFinished && isset($importResults['failure_count']))
                <div class="space-y-4">
                    <div class="p-4 rounded-lg {{ ($importResults['failure_count'] ?? 0) > 0 ? 'bg-yellow-50 dark:bg-yellow-900/50' : 'bg-green-50 dark:bg-green-900/50' }}">
                        <h4 class="font-semibold text-lg {{ ($importResults['failure_count'] ?? 0) > 0 ? 'text-yellow-800 dark:text-yellow-200' : 'text-green-800 dark:text-green-200' }}">
                            Proceso Finalizado
                        </h4>
                        <div class="mt-2 text-sm">
                            <p><span class="font-bold">{{ $importResults['success_count'] ?? 0 }}</span> documentos registrados.</p>
                            <p><span class="font-bold">{{ $importResults['failure_count'] ?? 0 }}</span> filas con errores.</p>
                        </div>
                    </div>

                    @if (!empty($importResults['failures']))
                        <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/50 max-h-60 overflow-y-auto">
                            <h4 class="font-semibold text-red-800 dark:text-red-200 mb-2">Errores detectados:</h4>
                            @foreach($importResults['failures'] as $failure)
                                <p class="text-xs text-red-700 dark:text-red-300">Fila {{ $failure['row'] }}: {{ $failure['errors'] }}</p>
                            @endforeach
                        </div>
                    @endif

                    <button type="button" wire:click="resetImport" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 w-full">
                        Nueva Importación
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Dropzone (Simple implementation to avoid JS errors) -->
    <div id="uploadModal" wire:ignore class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-4xl w-full p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold dark:text-white text-gray-900">Repositorio de Archivos Físicos</h3>
                    <button type="button" onclick="closeUploadModal()" class="text-gray-400 hover:text-gray-600">
                         <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6 italic">Arrastre aquí los miles de PDFs que desea migrar.</p>

                <div id="document-dropzone" class="border-2 border-dashed border-indigo-400 rounded-lg p-12 text-center bg-indigo-50/20 dark:bg-indigo-900/10 cursor-pointer">
                    <div class="text-indigo-500 mb-4 flex justify-center">
                        <x-icons.upload class="h-16 w-16"/>
                    </div>
                    <span class="text-lg font-medium dark:text-gray-300">Haga clic o arrastre sus documentos aquí.</span>
                </div>
                
                <div class="mt-6 flex items-center justify-between">
                    <p id="upload-status" class="text-sm font-semibold text-indigo-600"></p>
                    <button type="button" onclick="closeUploadModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded-lg font-bold transition duration-200">
                        Finalizar y Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Dropzone.autoDiscover = false;
            
            var dropzoneElement = document.getElementById("document-dropzone");
            if (dropzoneElement && !dropzoneElement.dropzone) {
                window.myDropzone = new Dropzone("#document-dropzone", { 
                    url: "{{ route('gestion.importar.documentos.fisicos') }}",
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    maxFilesize: 50,
                    parallelUploads: 5,
                    timeout: 0,
                    acceptedFiles: ".pdf,.jpg,.jpeg,.png",
                    init: function() {
                        this.on("success", function(file) {
                            var status = document.getElementById('upload-status');
                            status.innerText = "Procesados: " + this.getUploadedFiles().length + " archivos.";
                        });
                        this.on("queuecomplete", function() {
                            document.getElementById('upload-status').innerText = "¡Carga industrial completada!";
                            Livewire.dispatch('actualizarConteoEvent');
                        });
                    }
                });
            }

            window.openUploadModal = function() {
                document.getElementById('uploadModal').classList.remove('hidden');
            };

            window.closeUploadModal = function() {
                document.getElementById('uploadModal').classList.add('hidden');
                if (window.myDropzone) {
                    window.myDropzone.removeAllFiles(true);
                }
                Livewire.dispatch('actualizarConteoEvent');
            };
        });
    </script>
</div>