<div>
    {{-- Manejo de mensajes de sesión --}}
    @if (session()->has('message')) <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert"><p>{{ session('message') }}</p></div> @endif
    @if (session()->has('info')) <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4" role="alert"><p>{{ session('info') }}</p></div> @endif
    @if (session()->has('error')) <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert"><p>{{ session('error') }}</p></div> @endif

    <div class="p-4 sm:p-6 lg:p-8">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4">Revisar Documento</h2>

        @if ($documento)
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                
                <div class="lg:col-span-3 h-[calc(100vh-12rem)]">
                    <div class="bg-gray-200 dark:bg-gray-900 w-full h-full rounded-lg shadow">
                         @if ($documento->is_encrypted || Str::endsWith(strtolower($documento->ruta_archivo ?? ''), '.pdf'))
                            @if ($pdfUrl) <iframe src="{{ $pdfUrl }}" width="100%" height="100%" frameborder="0"></iframe> @else <div class="flex items-center justify-center h-full text-gray-500">No se pudo cargar la vista previa del documento.</div> @endif
                        @else
                            <div class="flex flex-col items-center justify-center h-full text-center p-4">
                                <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300">Vista Previa no Disponible</h3>
                                <p class="text-gray-500 mt-2">Este archivo no es un PDF ({{ $documento->mime_type }}). Por favor, descárguelo para revisarlo.</p>
                                <a href="{{ $pdfUrl }}" target="_blank" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500">Descargar Archivo ({{ $documento->nombre_original_archivo }})</a>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="lg:col-span-1 space-y-6 h-[calc(100vh-12rem)] overflow-y-auto pr-2">
                    
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                        <h3 class="font-bold text-lg mb-2 text-gray-900 dark:text-gray-100 border-b pb-2">Principal</h3> <p class="text-sm text-gray-600 dark:text-gray-400">{{ $documento->mandante->razon_social ?? 'N/A' }}</p>
                        <h3 class="font-bold text-lg mt-3 mb-2 text-gray-900 dark:text-gray-100 border-b pb-2">Contratista</h3> <p class="text-sm text-gray-600 dark:text-gray-400">{{ $documento->contratista->razon_social ?? 'N/A' }} ({{ $documento->contratista->rut }})</p>
                        <h3 class="font-bold text-lg mt-3 mb-2 text-gray-900 dark:text-gray-100 border-b pb-2">Entidad</h3>
                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <div><strong>{{ str_replace('App\\Models\\', '', $documento->entidad_type) }}:</strong> {{ $documento->entidad->nombre_completo ?? ($documento->entidad->patente_completa ?? ($documento->entidad->identificador_completo ?? 'N/A')) }}</div>
                            <div><strong>ID:</strong> 
                                @if ($documento->entidad instanceof \App\Models\Trabajador)
                                    {{ $documento->entidad->rut ?? 'N/A' }}
                                @else
                                    {{ $documento->entidad->identificador_completo ?? ($documento->entidad->patente_completa ?? 'N/A') }}
                                @endif
                            </div>
                            @if($cargoActualTrabajador)
                                <div><strong>Cargo:</strong> <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $cargoActualTrabajador }}</span></div>
                            @endif

                            <!-- ================== INICIO DE LA MODIFICACIÓN (CAMPOS DINÁMICOS TRABAJADOR) ================== -->
                            @if ($documento->entidad_type === \App\Models\Trabajador::class && $documento->entidad)
                                @if ($permisosRegla['ver_nacionalidad'])
                                    <div class="flex items-center">
                                        <strong class="w-32">Nacionalidad:</strong>
                                        @if ($permisosRegla['modificar_nacionalidad'] && !$isReadOnly && !$decision)
                                            <select wire:model="trabajadorData.nacionalidad_id" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm text-sm p-1">
                                                <option value="">Seleccione...</option>
                                                @foreach($listaNacionalidades as $nacionalidad)
                                                    <option value="{{ $nacionalidad->id }}">{{ $nacionalidad->nombre }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $documento->entidad->nacionalidad->nombre ?? 'No especificada' }}</span>
                                        @endif
                                    </div>
                                @endif

                                @if ($permisosRegla['ver_fecha_nacimiento'])
                                    <div class="flex items-center">
                                        <strong class="w-32">Fec. Nacimiento:</strong>
                                        @if ($permisosRegla['modificar_fecha_nacimiento'] && !$isReadOnly && !$decision)
                                            <input type="date" wire:model="trabajadorData.fecha_nacimiento" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm text-sm p-1">
                                        @else
                                            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $documento->entidad->fecha_nacimiento ? $documento->entidad->fecha_nacimiento->format('d-m-Y') : 'No especificada' }}</span>
                                        @endif
                                    </div>
                                @endif
                            @endif
                            <!-- ================== FIN DE LA MODIFICACIÓN ================================================= -->
                        </div>
                        <h3 class="font-bold text-lg mt-3 mb-2 text-gray-900 dark:text-gray-100 border-b pb-2">Documento</h3>
                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <p>{{ $documento->nombre_documento_snapshot ?? 'N/A' }}</p>
                            <p><strong class="font-semibold text-gray-700 dark:text-gray-300">Cargado:</strong> {{ $documento->created_at->format('d-m-Y H:i') }}</p>
                            @if($documento->tipo_vencimiento_snapshot === 'POR PERIODO' && $documento->periodo)
                                <p>
                                    <strong class="font-semibold text-gray-700 dark:text-gray-300">Período del Documento:</strong> 
                                    <span class="font-bold text-blue-600 dark:text-blue-400">{{ \Carbon\Carbon::parse($documento->periodo . '-01')->translatedFormat('F \d\e Y') }}</span>
                                </p>
                            @endif
                        </div>
                    </div>

                    @if($esAnexoDeContrato && $contratoObjetivo)
                        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg shadow border-2 border-purple-400 dark:border-purple-600">
                            <h3 class="font-extrabold text-lg mb-3 text-purple-800 dark:text-purple-200 border-b-2 border-purple-300 dark:border-purple-500 pb-2">Panel de Operaciones del Anexo</h3>
                            <div class="space-y-4 text-sm">
                                
                                <div>
                                    <label for="nuevaFechaVencimiento" class="block font-semibold text-gray-700 dark:text-gray-300">1. Modificar Vigencia del Contrato</label>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Vencimiento actual: <span class="font-bold">{{ $contratoObjetivo->fecha_vencimiento ? $contratoObjetivo->fecha_vencimiento->format('d-m-Y') : 'No definido' }}</span></p>
                                    <input type="date" id="nuevaFechaVencimiento" wire:model.live="nuevaFechaVencimientoContrato" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm text-sm" {{ $isReadOnly || $decision ? 'disabled' : '' }}>
                                </div>

                                <div>
                                    <label for="cargoSeleccionado" class="block font-semibold text-gray-700 dark:text-gray-300">2. Actualizar Cargo del Trabajador</label>
                                    <select id="cargoSeleccionado" wire:model.live="cargoSeleccionado" class="block w-full mt-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm text-sm" {{ $isReadOnly || $decision ? 'disabled' : '' }}>
                                        <option value="">-- No cambiar cargo --</option>
                                        @foreach($cargosDisponibles as $cargo)
                                            <option value="{{ $cargo->id }}">{{ $cargo->nombre_cargo }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-semibold text-gray-700 dark:text-gray-300">3. Enviar Documentos a Revalidación</label>
                                    <div class="mt-1 p-2 border rounded-md max-h-40 overflow-y-auto bg-white dark:bg-gray-800">
                                        @forelse($documentosRevalidablesDelTrabajador as $docRevalidable)
                                            <label class="flex items-center text-xs">
                                                <input type="checkbox" wire:model.live="documentosSeleccionadosParaRevalidar.{{ $docRevalidable->id }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ $isReadOnly || $decision ? 'disabled' : '' }}>
                                                <span class="ml-2">{{ $docRevalidable->nombre_documento_snapshot }}</span>
                                                <span class="ml-auto font-bold {{ $docRevalidable->resultado_validacion === 'Aprobado' ? 'text-green-600' : 'text-red-600' }}">{{ $docRevalidable->resultado_validacion }}</span>
                                            </label>
                                        @empty
                                            <p class="text-xs text-gray-500">No hay otros documentos activos para este trabajador.</p>
                                        @endforelse
                                    </div>
                                </div>

                                @if($contratoObjetivo->resultado_validacion === 'Rechazado')
                                <div>
                                    <label class="block font-semibold text-red-600 dark:text-red-400">4. Subsanar Contrato Rechazado</label>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Marque los criterios del contrato original que este anexo corrige:</p>
                                    <div class="mt-1 p-2 border border-red-300 dark:border-red-500 rounded-md max-h-40 overflow-y-auto bg-red-50 dark:bg-red-900/30">
                                        @forelse($motivosRechazoContrato as $index => $motivo)
                                            <label class="flex items-center text-xs">
                                                <input type="checkbox" wire:model.live="criteriosSubsanados.{{ $index }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ $isReadOnly || $decision ? 'disabled' : '' }}>
                                                <span class="ml-2 text-gray-700 dark:text-gray-200">{{ $motivo }}</span>
                                            </label>
                                        @empty
                                            <p class="text-xs text-gray-500 dark:text-gray-400">El contrato no tiene motivos de rechazo registrados en la observación.</p>
                                        @endforelse
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                        <h3 class="font-bold text-lg mb-2 text-gray-900 dark:text-gray-100 border-b pb-2">Guía de Regla</h3>
                        <div class="space-y-3 mt-3 text-sm">
                            @if ($documento->reglaDocumental?->observacionesDocumento && $documento->reglaDocumental->observacionesDocumento->isNotEmpty())
                                <div>
                                    <strong class="font-semibold text-gray-700 dark:text-gray-300">Observaciones para Validador:</strong>
                                    <ul class="list-disc list-inside mt-1 space-y-1">
                                        @foreach($documento->reglaDocumental->observacionesDocumento as $obs)
                                            <li class="text-gray-600 dark:text-gray-400 italic text-xs">{{ $obs->titulo }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @if ($documento->reglaDocumental?->formatosDocumento && $documento->reglaDocumental->formatosDocumento->isNotEmpty())
                                <div>
                                    <strong class="font-semibold text-gray-700 dark:text-gray-300">Formatos:</strong>
                                    <div class="mt-1 space-y-1">
                                        @foreach($documento->reglaDocumental->formatosDocumento as $fmt)
                                            @if($fmt->ruta_archivo)
                                                <p><a href="{{ Storage::disk('public')->url($fmt->ruta_archivo) }}" target="_blank" class="text-blue-500 hover:underline inline-flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                                    {{ $fmt->nombre }}
                                                </a></p>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                             @if ($documento->reglaDocumental?->documentosRelacionados && $documento->reglaDocumental->documentosRelacionados->isNotEmpty())
                                <div>
                                    <strong class="font-semibold text-gray-700 dark:text-gray-300">Doc. Relacionados:</strong>
                                    <div class="mt-1 space-y-2">
                                        @foreach($documento->reglaDocumental->documentosRelacionados as $docRel)
                                            <div class="p-2 border rounded-md dark:border-gray-600">
                                                <p class="font-medium dark:text-gray-200 text-xs">{{ $docRel->nombre }}</p>
                                                @php
                                                    $estadoDocRel = $documentosRelacionadosCargados[$docRel->id] ?? null;
                                                @endphp
                                                @if ($estadoDocRel)
                                                    <p class="text-xs">Estado: <span @class(['font-bold', 'text-green-600' => $estadoDocRel->resultado_validacion == 'Aprobado', 'text-red-600' => $estadoDocRel->resultado_validacion == 'Rechazado', 'text-blue-600' => is_null($estadoDocRel->resultado_validacion)])>{{ $estadoDocRel->resultado_validacion ?? $estadoDocRel->estado_validacion }}</span></p>
                                                    <a href="{{ route('document.revisar', ['documentoId' => $estadoDocRel->id]) }}" target="_blank" class="text-blue-500 hover:underline text-xs">Ver Último Documento Activo →</a>
                                                @else
                                                    <p class="italic text-gray-500 dark:text-gray-400 text-[10px]">No se encontró un documento activo de este tipo para la entidad.</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($documento->reglaDocumental?->mostrar_historico_documento && !empty($historicoDocumentos))
                                <div class="border-t dark:border-gray-600 pt-3">
                                    <strong class="font-semibold text-gray-700 dark:text-gray-300">Historial del Documento:</strong>
                                    <div class="mt-2 space-y-2 max-h-40 overflow-y-auto pr-2">
                                        @foreach($historicoDocumentos as $docHistorico)
                                            <div class="p-2 border rounded-md dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50">
                                                <div class="flex justify-between items-center">
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">Cargado: {{ $docHistorico->created_at->format('d-m-Y') }}</p>
                                                    <span @class(['px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full',
                                                        'bg-green-100 text-green-800' => $docHistorico->resultado_validacion == 'Aprobado',
                                                        'bg-red-100 text-red-800' => $docHistorico->resultado_validacion == 'Rechazado',
                                                    ])>
                                                        {{ $docHistorico->resultado_validacion }}
                                                    </span>
                                                </div>
                                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                                    Validador: {{ $docHistorico->validadorAsem->name ?? $docHistorico->validadorMandante->name ?? 'N/A' }}
                                                </p>
                                                <a href="{{ route('document.revisar', ['documentoId' => $docHistorico->id]) }}" target="_blank" class="text-xs text-blue-500 hover:underline">Ver Documento →</a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                        <h3 class="font-bold text-lg mb-2 text-gray-900 dark:text-gray-100 border-b pb-2">Checklist Revisión
                            @if($usuarioAutenticado?->isMandante()) <span class="text-indigo-500">(Principal)</span> @else <span class="text-blue-500">(oval)</span> @endif
                        </h3>
                        <div class="space-y-4 mt-3">

                            @if(!$isReadOnly)
                                @if($usuarioAutenticado?->isAsem())
                                    @if($documento->valida_emision_snapshot)
                                    <div class="space-y-2 p-2 border rounded-md @if($errors->has('fechaEmisionValidador') || $errors->has('confirmaFechaEmision')) border-red-400 @else border-gray-300 dark:border-gray-600 @endif">
                                        <label for="fechaEmision" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha Emisión:</label>
                                        <input type="date" id="fechaEmision" wire:model.live="fechaEmisionValidador" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm text-sm" {{ $isReadOnly || $decision ? 'disabled' : '' }}>
                                        <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 mt-2">
                                            <input type="checkbox" wire:model.live="confirmaFechaEmision" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ $isReadOnly || $decision ? 'disabled' : '' }}>
                                            <span class="ml-2">Confirmo la fecha de emisión</span>
                                        </label>
                                        @error('fechaEmisionValidador') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                        @error('confirmaFechaEmision') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    @endif
                                    @if($documento->valida_vencimiento_snapshot)
                                    <div class="space-y-2 p-2 border rounded-md @if($errors->has('fechaVencimientoValidador') || $errors->has('confirmaFechaVencimiento')) border-red-400 @else border-gray-300 dark:border-gray-600 @endif">
                                        <label for="fechaVencimiento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha Vencimiento:</label>
                                        
                                        <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 mb-2">
                                            <input type="checkbox" wire:model.live="vencimientoIndefinido" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ $isReadOnly || $decision ? 'disabled' : '' }}>
                                            <span class="ml-2 font-semibold">Vencimiento Indefinido</span>
                                        </label>
                                        <input type="date" id="fechaVencimiento" wire:model.live="fechaVencimientoValidador" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm text-sm disabled:bg-gray-200 dark:disabled:bg-gray-700" {{ ($isReadOnly || $decision || $vencimientoIndefinido) ? 'disabled' : '' }}>
                                        <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 mt-2">
                                            <input type="checkbox" wire:model.live="confirmaFechaVencimiento" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ ($isReadOnly || $decision || $vencimientoIndefinido) ? 'disabled' : '' }}>
                                            <span class="ml-2">Confirmo la fecha de vencimiento</span>
                                        </label>

                                        @error('fechaVencimientoValidador') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                        @error('confirmaFechaVencimiento') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    @endif
                                @else
                                    @if($documento->valida_vencimiento_snapshot)
                                    <div class="space-y-2 p-2 border rounded-md border-gray-300 dark:border-gray-600">
                                        <p class="text-sm text-gray-600 dark:text-gray-400"><strong>Fecha Emisión (Revisada por Oval):</strong> {{ $documento->fecha_emision ? $documento->fecha_emision->format('d-m-Y') : 'N/A' }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400"><strong>Fecha Vencimiento (Revisada por Oval):</strong> {{ $documento->fecha_vencimiento ? $documento->fecha_vencimiento->format('d-m-Y') : 'INDEFINIDO' }}</p>
                                        <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 mt-2">
                                            <input type="checkbox" wire:model.live="confirmaFechaVencimiento" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ $isReadOnly || $decision ? 'disabled' : '' }}>
                                            <span class="ml-2">Confirmo haber revisado la vigencia</span>
                                        </label>
                                        @error('confirmaFechaVencimiento') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    @endif
                                @endif

                                @forelse ($criteriosParaMostrar as $index => $criterioData)
                                    <div class="border-b border-gray-100 dark:border-gray-700/50 pb-3 last:border-0">
                                        @if(!empty($criterioData['sub_criterios']))
                                            {{-- Criterio CON sub-criterios: el padre es solo visual (no interactivo) --}}
                                            @php
                                                $todosSubs = collect($criterioData['sub_criterios'])->every(
                                                    fn($sub) => !empty($subCriteriosSeleccionados[$index][$sub['id']])
                                                );
                                            @endphp
                                            <div class="flex items-start text-sm text-gray-700 dark:text-gray-300">
                                                <span class="mt-0.5 shrink-0">
                                                    @if($todosSubs)
                                                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    @else
                                                        <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    @endif
                                                </span>
                                                <div class="ml-2 flex-1">
                                                    <span class="font-bold">{{ $criterioData['criterio'] ?? 'Criterio no definido' }}</span>
                                                    <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5 italic">Marque todos los ítems presentes:</p>
                                                    <div class="mt-2 space-y-1.5 border-l-2 border-indigo-200 dark:border-indigo-900/50 pl-3">
                                                        @foreach($criterioData['sub_criterios'] as $sub)
                                                            <label class="flex items-center text-[11px] text-gray-600 dark:text-gray-400 cursor-pointer hover:text-indigo-500 transition">
                                                                <input type="checkbox"
                                                                    wire:model.live="subCriteriosSeleccionados.{{ $index }}.{{ $sub['id'] }}"
                                                                    class="rounded w-3.5 h-3.5 border-gray-300 text-indigo-500 shadow-sm focus:ring-indigo-500"
                                                                    {{ $isReadOnly || $decision ? 'disabled' : '' }}>
                                                                <span class="ml-2">{{ $sub['nombre'] }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>

                                                    @if(!empty($criterioData['aclaracion']))
                                                        <div class="mt-1 flex gap-1 items-start bg-amber-50 dark:bg-amber-900/10 p-1.5 rounded border border-amber-100 dark:border-amber-900/30">
                                                            <svg class="w-3.5 h-3.5 text-amber-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                            <p class="text-[10px] text-amber-700 dark:text-amber-400 leading-tight">{{ $criterioData['aclaracion'] }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            {{-- Criterio SIN sub-criterios: checkbox normal interactivo --}}
                                            <label class="flex items-start text-sm text-gray-700 dark:text-gray-300 cursor-pointer group">
                                                <input type="checkbox"
                                                    wire:model.live="criteriosCumplidos.{{ $index }}"
                                                    value="1"
                                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 mt-1"
                                                    {{ $isReadOnly || $decision ? 'disabled' : '' }}>
                                                <div class="ml-2 flex-1">
                                                    <span class="font-bold group-hover:text-indigo-600 transition-colors">{{ $criterioData['criterio'] ?? 'Criterio no definido' }}</span>

                                                    @if(!empty($criterioData['sub_criterio']))
                                                        <span class="block text-blue-600 dark:text-blue-400 font-semibold text-xs mt-0.5">{{ $criterioData['sub_criterio'] }}</span>
                                                    @endif

                                                    @if(!empty($criterioData['aclaracion']))
                                                        <div class="mt-1 flex gap-1 items-start bg-amber-50 dark:bg-amber-900/10 p-1.5 rounded border border-amber-100 dark:border-amber-900/30">
                                                            <svg class="w-3.5 h-3.5 text-amber-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                            <p class="text-[10px] text-amber-700 dark:text-amber-400 leading-tight">{{ $criterioData['aclaracion'] }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </label>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay un checklist específico para esta etapa de validación.</p>
                                @endforelse
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Este documento ya ha sido procesado. La vista es de solo lectura.</p>
                                
                                {{-- CRITERIOS DE EVALUACIÓN (Solo Lectura / Auditoría) --}}
                                @if(!empty($criteriosParaMostrar))
                                    <div class="mt-4 border border-gray-700/20 dark:border-gray-700 rounded-xl overflow-hidden bg-gray-50 dark:bg-[#1a1c23]">
                                        <div class="bg-gray-100 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                                            <svg class="w-5 h-5 text-orange-500 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                            <h4 class="font-bold text-gray-800 dark:text-gray-200">Criterios de Evaluación</h4>
                                        </div>
                                        <div class="p-4">
                                            <ul class="space-y-3">
                                                @foreach($criteriosParaMostrar as $criterioData)
                                                    <li class="flex items-start gap-2 bg-white dark:bg-gray-800/50 p-3 rounded-lg border border-gray-200 dark:border-gray-700/50 shadow-sm">
                                                        <div class="mt-1 shrink-0">
                                                            <div class="w-2 h-2 rounded-full bg-orange-500"></div>
                                                        </div>
                                                        <div class="flex-1">
                                                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $criterioData['criterio'] ?? 'Criterio no definido' }}</span>
                                                            @if(!empty($criterioData['sub_criterio']))
                                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 italic">{{ $criterioData['sub_criterio'] }}</p>
                                                            @endif
                                                            @if(!empty($criterioData['sub_criterios']))
                                                                <ul class="mt-2 space-y-1 pl-2 border-l border-orange-200 dark:border-orange-900/50">
                                                                    @foreach($criterioData['sub_criterios'] as $sub)
                                                                        <li class="text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1 before:content-['-'] before:text-gray-400">
                                                                            {{ $sub['nombre'] ?? '' }}
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                    @if(!$isReadOnly)
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow space-y-4">
                            @if(!$decision)
                                <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100 border-b pb-2">Decisión</h3>
                                <div class="flex space-x-2">
                                    <button wire:click="seleccionarDecision('Aprobado')" @if(!$puedeAprobar) disabled title="Debe marcar todos los criterios y confirmar fechas para aprobar" @endif class="flex-1 justify-center inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 disabled:opacity-50 disabled:cursor-not-allowed">Aceptar</button>
                                    <button wire:click="seleccionarDecision('Rechazado')" @if(!$puedeRechazar) disabled title="No puede rechazar si todos los criterios están cumplidos" @endif class="flex-1 justify-center inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 disabled:opacity-50 disabled:cursor-not-allowed">Rechazar</button>
                                </div>
                            @endif
                            @if($decision)
                                <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100 border-b pb-2">Confirmar Decisión: <span class="{{ $decision == 'Aprobado' ? 'text-green-500' : 'text-red-500' }}">{{ $decision }}</span></h3>
                                @if($decision == 'Rechazado')
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Motivos de Rechazo (Automático):</label>
                                        <div class="p-3 border rounded-md border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 space-y-1 max-h-40 overflow-y-auto">
                                            @forelse($motivosRechazoCalculados as $motivo) <p class="text-sm text-gray-800 dark:text-gray-200">- {{ $motivo }}</p> @empty <p class="text-sm text-yellow-600 dark:text-yellow-400">No hay motivos de rechazo definidos para los criterios no cumplidos.</p> @endforelse
                                        </div>
                                    </div>
                                @endif
                                <div class="space-y-2">
                                    <label for="observacionValidador" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observación para el Contratista (Opcional):</label>
                                    <textarea id="observacionValidador" wire:model.defer="observacionValidador" rows="3" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm" placeholder="Ej: Falta solo el casco de seguridad..."></textarea>
                                </div>
                                @error('decision') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                <div class="flex space-x-2">
                                    <button wire:click="procesarDecision" wire:loading.attr="disabled" class="flex-1 justify-center inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-500">
                                        <span wire:loading.remove>Confirmar</span> <span wire:loading>PROCESANDO...</span>
                                    </button>
                                    <button wire:click="resetDecision" wire:loading.attr="disabled" class="flex-1 justify-center inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500">Volver a Validar</button>
                                </div>
                            @endif
                            @if($usuarioAutenticado?->isAsem())
                            <hr class="dark:border-gray-600">
                            <div>
                                <label for="motivoDevolucion" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo de Devolución (a Admin):</label>
                                <textarea id="motivoDevolucion" wire:model.live="motivoDevolucion" rows="2" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm" placeholder="Explique por qué devuelve este documento..."></textarea>
                                @error('motivoDevolucion') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                <button wire:click="devolverAAdmin" wire:loading.attr="disabled" class="mt-2 w-full justify-center inline-flex items-center px-4 py-2 bg-yellow-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-400">Devolver Documento</button>
                            </div>
                            @endif
                        </div>
                    @else
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                             <h3 class="font-bold text-lg mb-2 text-gray-900 dark:text-gray-100 border-b pb-2">Estado Final</h3>
                             <p class="text-sm font-semibold {{ $documento->resultado_validacion == 'Aprobado' ? 'text-green-600' : 'text-red-600' }}">Estado: {{ $documento->resultado_validacion }}</p>
                             <p class="text-sm text-gray-600 dark:text-gray-400">Fecha Validación: {{ $documento->fecha_validacion ? $documento->fecha_validacion->format('d-m-Y H:i') : 'N/A' }}</p>
                             @if($documento->observacion_rechazo) <p class="text-sm text-gray-600 dark:text-gray-400 mt-2"><strong>Motivo del Rechazo:</strong> <br> {!! nl2br(e($documento->observacion_rechazo)) !!}</p> @endif
                        </div>

                        @if($usuarioAutenticado?->isAsem() && in_array($documento->estado_validacion, ['Revisado', 'Revisado-Revalidado']))
                            <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg shadow mt-4 border border-indigo-200 dark:border-indigo-800">
                                <h3 class="font-bold text-lg mb-2 text-indigo-900 dark:text-indigo-100 border-b border-indigo-200 dark:border-indigo-800 pb-2">Revalidación Individual</h3>
                                <div class="space-y-3 mt-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo para Revalidar:</label>
                                        <textarea wire:model.live="motivoRevalidacionIndividual" rows="2" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm text-sm" placeholder="Indique el motivo por el cual este documento debe ser auditado/revalidado..."></textarea>
                                        @error('motivoRevalidacionIndividual') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <label class="flex items-center text-sm text-gray-700 dark:text-gray-300">
                                        <input type="checkbox" wire:model.live="marcarComoErrorValidador" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2">Marcar como error del validador inicial</span>
                                    </label>
                                    <button wire:click="iniciarRevalidacionIndividual" wire:loading.attr="disabled" class="w-full justify-center inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 transition-colors mt-2">
                                        Enviar a Revalidación
                                    </button>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow text-center">
                <h3 class="text-lg font-bold text-gray-700 dark:text-gray-300">Documento no encontrado</h3>
                <p class="text-gray-500 dark:text-gray-400 mt-2">No se ha podido cargar el documento solicitado.</p>
                <a href="{{ route('asem.panel-validacion') }}" wire:navigate class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-500">Volver al Panel</a>
            </div>
        @endif
    </div>
</div>