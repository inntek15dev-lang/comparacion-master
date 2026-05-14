<div class="py-6">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Encabezado --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Gestión de Popups</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Administra los mensajes emergentes para los usuarios del sistema.</p>
            </div>
            <button wire:click="abrirModalParaCrear" class="btn-primary flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nuevo Popup
            </button>
        </div>

        {{-- Mensajes de sesión --}}
        @if (session()->has('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        {{-- Filtros --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar por título</label>
                    <input type="text" wire:model.live.debounce.300ms="filtroTitulo" 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                           placeholder="Título del popup...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Estado</label>
                    <select wire:model.live="filtroEstado" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="todos">Todos</option>
                        <option value="activos">Activos</option>
                        <option value="inactivos">Inactivos</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Vigencia</label>
                    <select wire:model.live="filtroVigencia" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="todos">Todos</option>
                        <option value="vigentes">Vigentes</option>
                        <option value="programados">Programados</option>
                        <option value="expirados">Expirados</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Principal</label>
                    <select wire:model.live="filtroMandante" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="todos">Todos</option>
                        <option value="global">Solo Globales (ASEM)</option>
                        @foreach($mandantesDisponibles as $id => $razon_social)
                            <option value="{{ $id }}">{{ $razon_social }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Tabla de Popups --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Título</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Principal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Roles Destino</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Max. Vistas</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aceptación</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Vigencia</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($popups as $popup)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $popup->titulo }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $popup->fecha_inicio->format('d/m/Y') }} 
                                        @if($popup->fecha_fin) - {{ $popup->fecha_fin->format('d/m/Y') }} @else (Sin fecha fin) @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($popup->mandante)
                                        <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ $popup->mandante->razon_social }}</span>
                                    @else
                                        <span class="text-sm text-gray-500 dark:text-gray-400 italic">Global (ASEM)</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($popup->roles_destino ?? [] as $rol)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                                {{ $rol }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="text-sm text-gray-900 dark:text-white">
                                        {{ $popup->max_visualizaciones == 0 ? '∞' : $popup->max_visualizaciones }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($popup->requiere_aceptacion)
                                        <button wire:click="verRegistroAceptaciones({{ $popup->id }})"
                                                class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-900 dark:text-amber-200 dark:hover:bg-amber-800 transition-colors cursor-pointer"
                                                title="Ver registro de aceptaciones">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            Ver Registro
                                        </button>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500 text-xs">No</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @php $vigencia = $popup->estado_vigencia; @endphp
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                        @if($vigencia === 'Vigente') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($vigencia === 'Programado') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300
                                        @endif">
                                        {{ $vigencia }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button wire:click="confirmarAlternarEstado({{ $popup->id }})"
                                            wire:confirm="{{ $popup->is_active ? '¿Desactivar este popup?' : '¿Activar este popup?' }}"
                                            class="inline-flex items-center px-2 py-1 rounded text-xs font-medium transition-colors
                                                {{ $popup->is_active 
                                                    ? 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900 dark:text-green-200' 
                                                    : 'bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900 dark:text-red-200' }}">
                                        {{ $popup->is_active ? 'Activo' : 'Inactivo' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button wire:click="abrirModalParaEditar({{ $popup->id }})" 
                                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                title="Editar">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button wire:click="eliminarPopup({{ $popup->id }})" 
                                                wire:confirm="¿Está seguro de eliminar este popup? Esta acción no se puede deshacer."
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                title="Eliminar">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No hay popups</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Comienza creando un nuevo popup para tus usuarios.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Paginación --}}
            @if($popups->hasPages())
                <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
                    {{ $popups->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modal de Crear/Editar --}}
    @if ($mostrarModal)
        <div class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="cerrarModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <form wire:submit.prevent="guardarPopup">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    {{ empty($popupActual->id) ? 'Crear Nuevo Popup' : 'Editar Popup' }}
                                </h3>
                                <button type="button" wire:click="cerrarModal" class="text-gray-400 hover:text-gray-500">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>

                            <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
                                {{-- Título --}}
                                <div>
                                    <label for="titulo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título del Popup <span class="text-red-500">*</span></label>
                                    <input type="text" id="titulo" wire:model="titulo" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @error('titulo') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                {{-- Principal (Mandante) Destino --}}
                                <div>
                                    <label for="mandante_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asignar a Principal (Opcional)</label>
                                    <select id="mandante_id" wire:model="mandante_id"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="">Global (Todos los contratistas/usuarios)</option>
                                        @foreach($mandantesDisponibles as $id => $razon_social)
                                            <option value="{{ $id }}">{{ $razon_social }}</option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Si seleccionas un Principal, este popup solo será visible para los usuarios vinculados a dicho Principal.</p>
                                    @error('mandante_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                {{-- Contenido --}}
                                <div>
                                    <label for="contenido" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contenido del Popup <span class="text-red-500">*</span></label>
                                    <textarea id="contenido" wire:model="contenido" rows="5"
                                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                              placeholder="Escribe aquí el mensaje que verán los usuarios..."></textarea>
                                    @error('contenido') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                {{-- Archivo de contenido --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">O sube un archivo de texto (.txt, .html)</label>
                                    @if($archivoContenidoExistente)
                                        <div class="flex items-center gap-2 mt-1 p-2 bg-gray-100 dark:bg-gray-700 rounded">
                                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <span class="text-sm text-gray-600 dark:text-gray-300 flex-1">{{ basename($archivoContenidoExistente) }}</span>
                                            <button type="button" wire:click="eliminarArchivoExistente" class="text-red-500 hover:text-red-700" title="Eliminar archivo">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    @else
                                        <input type="file" wire:model="archivoContenido" accept=".txt,.html"
                                               class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900 dark:file:text-indigo-200">
                                    @endif
                                    @error('archivoContenido') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                {{-- Roles Destino --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Roles que verán este popup <span class="text-red-500">*</span></label>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                        @foreach($rolesDisponibles as $rol)
                                            <label class="flex items-center p-2 rounded border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors">
                                                <input type="checkbox" wire:model="roles_destino" value="{{ $rol }}" 
                                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $rol }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('roles_destino') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                {{-- Configuración de visualización --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="max_visualizaciones" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Máximo de visualizaciones</label>
                                        <input type="number" id="max_visualizaciones" wire:model="max_visualizaciones" min="0" max="100"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <p class="text-xs text-gray-500 mt-1">0 = Ilimitado</p>
                                        @error('max_visualizaciones') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="tipo_interaccion" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de interacción</label>
                                        <select id="tipo_interaccion" wire:model="tipo_interaccion"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <option value="solo_cerrar">Solo cerrar (X)</option>
                                            <option value="requiere_click">Requiere hacer click en botón</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Aceptación de condiciones --}}
                                <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model.live="requiere_aceptacion" 
                                               class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                                        <span class="ml-2 text-sm font-medium text-amber-800 dark:text-amber-200">Requiere aceptar condiciones</span>
                                    </label>
                                    @if($requiere_aceptacion)
                                        <div class="mt-3">
                                            <label for="texto_aceptacion" class="block text-sm font-medium text-amber-800 dark:text-amber-200">Texto del checkbox de aceptación <span class="text-red-500">*</span></label>
                                            <input type="text" id="texto_aceptacion" wire:model="texto_aceptacion" 
                                                   placeholder="Ej: Acepto los términos y condiciones"
                                                   class="mt-1 block w-full rounded-md border-amber-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-700 dark:border-amber-600 dark:text-white">
                                            @error('texto_aceptacion') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                        </div>
                                    @endif
                                </div>

                                {{-- URL destino --}}
                                <div>
                                    <label for="url_destino" class="block text-sm font-medium text-gray-700 dark:text-gray-300">URL de destino (opcional)</label>
                                    <input type="url" id="url_destino" wire:model="url_destino" 
                                           placeholder="https://ejemplo.com/pagina"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <p class="text-xs text-gray-500 mt-1">Si se especifica, el usuario será redirigido al hacer click en el botón de acción.</p>
                                    @error('url_destino') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                {{-- Fechas --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="fecha_inicio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha de inicio <span class="text-red-500">*</span></label>
                                        <input type="date" id="fecha_inicio" wire:model="fecha_inicio"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        @error('fecha_inicio') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="fecha_fin" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha de fin (opcional)</label>
                                        <input type="date" id="fecha_fin" wire:model="fecha_fin"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <p class="text-xs text-gray-500 mt-1">Dejar vacío para sin fecha de expiración.</p>
                                        @error('fecha_fin') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Estado activo --}}
                                <div>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model="is_active" 
                                               class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                        <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Popup activo</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Footer del modal --}}
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-3">
                            <button type="submit" class="btn-primary w-full sm:w-auto" wire:loading.attr="disabled">
                                <span wire:loading.remove>{{ empty($popupActual->id) ? 'Crear Popup' : 'Guardar Cambios' }}</span>
                                <span wire:loading>Guardando...</span>
                            </button>
                            <button type="button" wire:click="cerrarModal" class="btn-secondary w-full sm:w-auto mt-3 sm:mt-0">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal de Registro de Aceptaciones --}}
    @if ($mostrarModalRegistro && $popupRegistro)
        <div class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-registro-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="cerrarModalRegistro"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-registro-title">
                                    Registro de Aceptaciones
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Popup: <span class="font-medium">{{ $popupRegistro->titulo }}</span>
                                </p>
                            </div>
                            <button type="button" wire:click="cerrarModalRegistro" class="text-gray-400 hover:text-gray-500">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="max-h-[400px] overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg">
                            @if($this->aceptaciones->count() > 0)
                                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Usuario</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Contratista</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Fecha Aceptación</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($this->aceptaciones as $aceptacion)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 h-8 w-8 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center">
                                                            <span class="text-sm font-medium text-indigo-600 dark:text-indigo-300">
                                                                {{ strtoupper(substr($aceptacion->user->name ?? 'U', 0, 1)) }}
                                                            </span>
                                                        </div>
                                                        <div class="ml-3">
                                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                                {{ $aceptacion->user->name ?? 'Usuario eliminado' }}
                                                            </div>
                                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                                {{ $aceptacion->user->email ?? '-' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    @if($aceptacion->user && $aceptacion->user->contratista)
                                                        <div class="text-sm text-gray-900 dark:text-white">
                                                            {{ $aceptacion->user->contratista->razon_social }}
                                                        </div>
                                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                                            RUT: {{ $aceptacion->user->contratista->rut }}
                                                        </div>
                                                    @else
                                                        <span class="text-xs text-gray-400 dark:text-gray-500">Sin contratista</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                                    <div class="text-sm text-gray-900 dark:text-white">
                                                        {{ $aceptacion->ultima_visualizacion->format('d/m/Y') }}
                                                    </div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $aceptacion->ultima_visualizacion->format('H:i') }} hrs
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        <span class="font-semibold">Total de aceptaciones:</span> {{ $this->aceptaciones->count() }}
                                    </p>
                                </div>
                            @else
                                <div class="text-center py-12">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Sin aceptaciones</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Aún nadie ha aceptado este popup.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-3">
                        <button type="button" wire:click="cerrarModalRegistro" class="btn-secondary w-full sm:w-auto">
                            Cerrar
                        </button>
                        @if($this->aceptaciones->count() > 0)
                        <button type="button" wire:click="exportarAceptacionesExcel" 
                                class="inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors w-full sm:w-auto">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Exportar Excel
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
