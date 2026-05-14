<x-app-layout>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Encabezado -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-8 h-8 mr-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Importador de Históricos OVAL
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Sube el archivo Excel generado por la IA con los datos de certificados históricos para incorporarlos al sistema.
                </p>
            </div>

            <!-- Contenedor Principal -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                
                <div class="p-8">
                    <!-- Alertas -->
                    @if (session('success'))
                        <div class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 rounded-lg p-4 flex items-start">
                            <svg class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <div>
                                <h3 class="font-bold">¡Importación Completada!</h3>
                                <p class="text-sm mt-1">{{ session('success') }}</p>
                            </div>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-6 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 rounded-lg p-4 flex items-start">
                            <svg class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div>
                                <h3 class="font-bold">Error en la importación</h3>
                                <p class="text-sm mt-1">{{ session('error') }}</p>
                            </div>
                        </div>
                    @endif

                    <!-- Instrucciones y Descarga Plantilla (Livewire) -->
                    @livewire('admin.descargar-plantilla-historica')

                    <!-- Formulario de Subida -->
                    <form action="{{ route('oval.importador-historico.procesar') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <!-- Drag & Drop Area -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sube tu archivo Excel o CSV</label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-xl hover:border-indigo-500 dark:hover:border-indigo-400 transition-colors bg-gray-50 dark:bg-gray-800/50 relative group cursor-pointer" id="drop-zone">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 group-hover:text-indigo-500 transition-colors" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600 dark:text-gray-400 justify-center">
                                            <label for="archivo_excel" class="relative cursor-pointer rounded-md font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                                <span>Sube un archivo</span>
                                                <input id="archivo_excel" name="archivo_excel" type="file" class="sr-only" accept=".csv,.xlsx,.xls" required>
                                            </label>
                                            <p class="pl-1">o arrástralo aquí</p>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-500" id="file-name-display">
                                            XLSX, XLS, CSV hasta 10MB
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Opciones -->
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                                <h3 class="text-sm font-semibold tracking-wide text-gray-500 uppercase mb-4">Opciones de Ejecución</h3>
                                
                                <div class="space-y-4">
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input id="is_dry_run" name="is_dry_run" type="checkbox" value="1" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="is_dry_run" class="font-medium text-gray-700 dark:text-gray-200">Simular (Dry-Run)</label>
                                            <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">Verifica qué se importará sin guardar nada en la Base de Datos.</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input id="forzar" name="forzar" type="checkbox" value="1" class="focus:ring-red-500 h-4 w-4 text-red-600 border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="forzar" class="font-medium text-red-700 dark:text-red-400">Forzar Sobreescritura</label>
                                            <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">Borrará la carpeta existente si se encuentra una duplicada para el mismo mes/año.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-8">
                                    <button type="submit" class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                        <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                        Procesar Importación
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Zona de Resultados (si existen) -->
                @if (session('resultado'))
                    @php $res = session('resultado'); @endphp
                    <div class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 p-8">
                        
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                                <svg class="w-6 h-6 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Resultados del Procesamiento
                            </h2>
                            <div class="flex space-x-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 border border-green-200 dark:border-green-800">
                                    {{ $res['exitosos'] }} Exitosos
                                </span>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 border border-red-200 dark:border-red-800">
                                    {{ $res['fallidos'] }} Fallidos
                                </span>
                            </div>
                        </div>

                        <div class="space-y-4">
                            @foreach ($res['detalles'] as $detalle)
                                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border {{ $detalle['exito'] ? 'border-l-4 border-l-green-500 border-gray-200 dark:border-gray-700' : 'border-l-4 border-l-red-500 border-gray-200 dark:border-gray-700' }} p-5">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                                Certificado {{ $detalle['periodo'] }}
                                            </h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $detalle['contratista'] }}</p>
                                        </div>
                                        @if($detalle['carpeta_id'])
                                            <a href="{{ route('verificacion.certificado.visor', $detalle['carpeta_id']) }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium inline-flex items-center">
                                                Ver Carpeta #{{ $detalle['carpeta_id'] }}
                                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                            </a>
                                        @endif
                                    </div>
                                    
                                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 font-mono text-sm text-gray-700 dark:text-gray-300 space-y-1">
                                        @foreach ($detalle['mensajes'] as $msg)
                                            <div class="flex items-start">
                                                @if(str_starts_with($msg, '❌'))
                                                    <span class="text-red-500 mr-2">❌</span> <span class="text-red-700 dark:text-red-400">{{ substr($msg, 3) }}</span>
                                                @elseif(str_starts_with($msg, '✅'))
                                                    <span class="text-green-500 mr-2">✅</span> <span>{{ substr($msg, 3) }}</span>
                                                @elseif(str_starts_with($msg, '⚠'))
                                                    <span class="text-yellow-500 mr-2">⚠</span> <span class="text-yellow-700 dark:text-yellow-400">{{ substr($msg, 3) }}</span>
                                                @else
                                                    <span class="text-gray-400 mr-2">•</span> <span>{{ $msg }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                    </div>
                @endif
            </div>

            {{-- ══════════════════════════════════════════════════════════════ --}}
            {{-- SECCIÓN 2: IMPORTADOR MASIVO DE PDFs HISTÓRICOS               --}}
            {{-- ══════════════════════════════════════════════════════════════ --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden mt-8">

                <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Importar PDFs Históricos</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Sube los archivos PDF siguiendo la convención de nombre. El sistema los enlazará automáticamente.
                        </p>
                    </div>
                </div>

                <div class="p-8">

                    {{-- Convención de nombre --}}
                    <div class="mb-6 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 rounded-xl p-5">
                        <h3 class="text-sm font-bold text-indigo-800 dark:text-indigo-300 mb-3 uppercase tracking-wide flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Convención de Nombre Obligatoria
                        </h3>
                        <code class="block text-sm font-mono font-bold text-indigo-700 dark:text-indigo-300 bg-white dark:bg-gray-900 rounded-lg px-4 py-3 border border-indigo-200 dark:border-indigo-700 mb-3">
                            {MANDANTE_ID}_{ID_REGISTRO}_{AAAAMM}_{LUGAR}_{UO}_{NUM_CONTRATO}_{COD_DOC}<span class="text-amber-500">_SUFIJO</span>.pdf
                            <span class="text-gray-400 font-normal text-xs ml-2">(SUFIJO opcional)</span>
                        </code>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-xs">
                            <div class="flex items-start gap-1.5">
                                <span class="mt-0.5 w-2 h-2 rounded-full bg-amber-400 flex-shrink-0"></span>
                                <span class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-200">MANDANTE_ID</strong> — ID numérico del mandante o <code>X</code></span>
                            </div>
                            <div class="flex items-start gap-1.5">
                                <span class="mt-0.5 w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></span>
                                <span class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-200">ID_REGISTRO</strong> — Obligatorio</span>
                            </div>
                            <div class="flex items-start gap-1.5">
                                <span class="mt-0.5 w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></span>
                                <span class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-200">AAAAMM</strong> — Ej: <code>202410</code></span>
                            </div>
                            <div class="flex items-start gap-1.5">
                                <span class="mt-0.5 w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></span>
                                <span class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-200">LUGAR</strong> — Sin espacios</span>
                            </div>
                            <div class="flex items-start gap-1.5">
                                <span class="mt-0.5 w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></span>
                                <span class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-200">UO</strong> — Sin espacios</span>
                            </div>
                            <div class="flex items-start gap-1.5">
                                <span class="mt-0.5 w-2 h-2 rounded-full bg-amber-400 flex-shrink-0"></span>
                                <span class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-200">NUM_CONTRATO</strong> — (o <code>X</code>)</span>
                            </div>
                            <div class="flex items-start gap-1.5">
                                <span class="mt-0.5 w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></span>
                                <span class="text-gray-600 dark:text-gray-400"><strong class="text-gray-800 dark:text-gray-200">COD_DOC</strong> — Ej: D2, D36, D9</span>
                            </div>
                            <div class="flex items-start gap-1.5 col-span-2 md:col-span-3 border-t border-indigo-100 dark:border-indigo-800 pt-2 mt-1">
                                <span class="mt-0.5 w-2 h-2 rounded-full bg-amber-400 flex-shrink-0"></span>
                                <span class="text-gray-600 dark:text-gray-400"><strong class="text-amber-600 dark:text-amber-400">SUFIJO</strong> — <em>Opcional.</em> Permite subir múltiples docs del mismo código. Ej: <code>D2_1.pdf</code>, <code>D2_2.pdf</code>, <code>D2_LIQ.pdf</code></span>
                            </div>
                        </div>
                        <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-3 font-mono">
                            Ej: <strong>100004_40059_202410_LANDESLUGAR_PLANTA_X_D2.pdf</strong><br>
                            Ej con sufijo: <strong>100004_40059_202410_LANDESLUGAR_PLANTA_X_D2_1.pdf</strong> / <strong>...D2_2.pdf</strong>
                            <span class="text-gray-400 ml-2">(usa X si no sabes el mandante_id o número de contrato)</span>
                        </p>
                    </div>

                    @if(session('success_pdfs'))
                        <div class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 rounded-lg p-4 flex items-center gap-3">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span class="font-bold text-sm">{{ session('success_pdfs') }}</span>
                        </div>
                    @endif

                    <form action="{{ route('oval.importador-historico.procesar-pdfs') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                            {{-- Zona Drag & Drop --}}
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Archivos PDF (múltiple selección permitida)
                                </label>
                                <div class="mt-1 flex justify-center px-6 pt-8 pb-8 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-xl hover:border-red-400 dark:hover:border-red-500 transition-colors bg-gray-50 dark:bg-gray-800/50 group cursor-pointer" id="pdf-drop-zone">
                                    <div class="space-y-2 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 group-hover:text-red-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        <div class="flex text-sm text-gray-600 dark:text-gray-400 justify-center">
                                            <label for="pdfs_input" class="relative cursor-pointer rounded-md font-medium text-red-600 dark:text-red-400 hover:text-red-500 focus-within:outline-none">
                                                <span>Selecciona PDFs</span>
                                                <input id="pdfs_input" name="pdfs[]" type="file" class="sr-only" accept=".pdf" multiple required>
                                            </label>
                                            <p class="pl-1">o arrástralos aquí</p>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-500" id="pdf-file-display">
                                            Solo archivos .pdf — máx. 100MB por archivo
                                        </p>
                                    </div>
                                </div>

                                {{-- Lista de archivos seleccionados --}}
                                <div id="pdf-file-list" class="mt-3 space-y-1 hidden"></div>
                            </div>

                            {{-- Opciones --}}
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                                <h3 class="text-sm font-semibold tracking-wide text-gray-500 uppercase mb-4">Opciones</h3>
                                <div class="space-y-4">
                                    <div class="flex items-start gap-3">
                                        <input id="pdf_dry_run" name="dry_run" type="checkbox" value="1"
                                               class="mt-0.5 h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                        <div>
                                            <label for="pdf_dry_run" class="text-sm font-medium text-gray-700 dark:text-gray-200">Simular (Dry-Run)</label>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Verifica sin guardar nada en disco ni en BD.</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <input id="pdf_forzar" name="forzar" type="checkbox" value="1"
                                               class="mt-0.5 h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                        <div>
                                            <label for="pdf_forzar" class="text-sm font-medium text-red-700 dark:text-red-400">Forzar Sobreescritura</label>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Reemplaza el PDF si ya existe en la carpeta.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-8">
                                    <button type="submit"
                                            class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                        Importar PDFs
                                    </button>
                                </div>
                            </div>

                        </div>
                    </form>

                    {{-- Resultados PDFs --}}
                    @if(session('resultado_pdfs'))
                        @php $rp = session('resultado_pdfs'); @endphp
                        <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-8">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Resultados de la Importación PDF
                                <span class="ml-auto flex gap-2">
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 font-bold">{{ count($rp['ok']) }} OK</span>
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 font-bold">{{ count($rp['skip']) }} Omitidos</span>
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 font-bold">{{ count($rp['error']) }} Errores</span>
                                </span>
                            </h3>

                            <div class="space-y-1.5 font-mono text-xs">
                                @foreach($rp['ok'] as $r)
                                    <div class="flex items-start gap-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg px-3 py-2">
                                        <span class="text-green-600 flex-shrink-0">✅</span>
                                        <span class="font-bold text-green-800 dark:text-green-300 truncate">{{ $r['archivo'] }}</span>
                                        <span class="ml-auto text-green-600 dark:text-green-400 whitespace-nowrap">{{ $r['msg'] }}</span>
                                    </div>
                                @endforeach
                                @foreach($rp['skip'] as $r)
                                    <div class="flex items-start gap-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg px-3 py-2">
                                        <span class="text-amber-600 flex-shrink-0">⏭</span>
                                        <span class="font-bold text-amber-800 dark:text-amber-300 truncate">{{ $r['archivo'] }}</span>
                                        <span class="ml-auto text-amber-600 dark:text-amber-400 whitespace-nowrap">{{ $r['msg'] }}</span>
                                    </div>
                                @endforeach
                                @foreach($rp['error'] as $r)
                                    <div class="flex items-start gap-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg px-3 py-2">
                                        <span class="text-red-600 flex-shrink-0">❌</span>
                                        <span class="font-bold text-red-800 dark:text-red-300 truncate">{{ $r['archivo'] }}</span>
                                        <span class="ml-auto text-red-600 dark:text-red-400 whitespace-nowrap">{{ $r['msg'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ── Utilidad: actualizar lista visual de PDFs ───────────────────
            const pdfDisplay  = document.getElementById('pdf-file-display');
            const pdfFileList = document.getElementById('pdf-file-list');

            function updatePdfList(files) {
                if (!files || files.length === 0) return;
                pdfDisplay.textContent = files.length + ' archivo(s) seleccionado(s)';
                pdfDisplay.style.color = '#dc2626'; // red-600
                pdfFileList.classList.remove('hidden');
                pdfFileList.innerHTML = '';
                Array.from(files).forEach(f => {
                    const div = document.createElement('div');
                    div.className = 'flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded px-3 py-1.5';
                    div.innerHTML = '<svg class="w-3 h-3 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>'
                        + '<span class="font-mono truncate">' + f.name + '</span>'
                        + '<span class="ml-auto text-gray-400">(' + (f.size / 1024).toFixed(0) + ' KB)</span>';
                    pdfFileList.appendChild(div);
                });
            }

            // ── Función genérica de drop zone ───────────────────────────────
            function setupDropZone(zone, input, display, isMultiple) {
                if (!zone || !input) return;

                // Prevenir comportamiento nativo del browser en todos los eventos drag
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    zone.addEventListener(eventName, e => {
                        e.preventDefault();
                        e.stopPropagation();
                    });
                });

                // Feedback visual al arrastrar (inline styles, no clases Tailwind dinámicas)
                ['dragenter', 'dragover'].forEach(eventName => {
                    zone.addEventListener(eventName, () => {
                        zone.style.borderColor   = '#6366f1'; // indigo-500
                        zone.style.backgroundColor = 'rgba(99,102,241,0.08)';
                    });
                });
                ['dragleave', 'drop'].forEach(eventName => {
                    zone.addEventListener(eventName, () => {
                        zone.style.borderColor   = '';
                        zone.style.backgroundColor = '';
                    });
                });

                // Handler de DROP: asignar archivos via DataTransfer API
                zone.addEventListener('drop', function(e) {
                    const droppedFiles = e.dataTransfer.files;
                    if (!droppedFiles || droppedFiles.length === 0) return;

                    try {
                        const dt = new DataTransfer();
                        const limit = isMultiple ? droppedFiles.length : 1;
                        for (let i = 0; i < limit; i++) {
                            dt.items.add(droppedFiles[i]);
                        }
                        input.files = dt.files;
                    } catch (err) {
                        // Fallback para browsers sin DataTransfer API
                        input.files = droppedFiles;
                    }

                    // Disparar evento 'change' manualmente (la asignación programática no lo dispara)
                    input.dispatchEvent(new Event('change'));

                    // Para el drop zone de Excel (no múltiple), actualizar display también
                    if (!isMultiple && input.files[0]) {
                        display.textContent = 'Archivo: ' + input.files[0].name;
                        display.style.color = '#4f46e5';
                        display.style.fontWeight = 'bold';
                    }
                });
            }

            // ── Excel drop zone ────────────────────────────────────────────
            const fileInput  = document.getElementById('archivo_excel');
            const fileDisplay = document.getElementById('file-name-display');
            const dropZone   = document.getElementById('drop-zone');

            if (fileInput && dropZone) {
                fileInput.addEventListener('change', function(e) {
                    if (e.target.files.length > 0) {
                        fileDisplay.textContent = 'Archivo: ' + e.target.files[0].name;
                        fileDisplay.style.color = '#4f46e5';
                        fileDisplay.style.fontWeight = 'bold';
                    }
                });
                dropZone.addEventListener('click', function(e) {
                    if (e.target === fileInput || e.target.closest('label')) return;
                    fileInput.click();
                });
                setupDropZone(dropZone, fileInput, fileDisplay, false);
            }

            // ── PDF drop zone ──────────────────────────────────────────────
            const pdfInput = document.getElementById('pdfs_input');
            const pdfDrop  = document.getElementById('pdf-drop-zone');

            if (pdfInput && pdfDrop) {
                pdfInput.addEventListener('change', function(e) {
                    updatePdfList(e.target.files);
                });
                pdfDrop.addEventListener('click', function(e) {
                    // Si el click viene de dentro de un <label>, la label ya maneja el file dialog
                    if (e.target === pdfInput || e.target.closest('label')) return;
                    pdfInput.click();
                });
                setupDropZone(pdfDrop, pdfInput, pdfDisplay, true);
            }

        });
    </script>
</x-app-layout>
