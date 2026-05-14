@php use Carbon\Carbon; use Illuminate\Support\Str; @endphp
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
    <div class="py-8">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-2xl rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="p-8">
                    @if (session()->has('message'))
                        <div class="bg-gradient-to-r from-green-50 to-green-100 border-l-4 border-green-500 text-green-800 px-6 py-4 rounded-lg relative mb-6 shadow-md" role="alert">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span class="font-medium">{{ session('message') }}</span>
                            </div>
                        </div>
                    @endif
                    
                    @if (session()->has('warning'))
                        <div class="bg-gradient-to-r from-yellow-50 to-yellow-100 border-l-4 border-yellow-500 text-yellow-800 px-6 py-4 rounded-lg relative mb-6 shadow-md" role="alert">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                <span class="font-medium">{{ session('warning') }}</span>
                            </div>
                        </div>
                    @endif
                    
                    @if (session()->has('error'))
                        <div class="bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-500 text-red-800 px-6 py-4 rounded-lg relative mb-6 shadow-md" role="alert">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                                <span class="font-medium">{{ session('error') }}</span>
                            </div>
                        </div>
                    @endif

                    <div class="bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-900 rounded-xl p-6 mb-6 border border-gray-200 dark:border-gray-700 shadow-lg">
                        <div class="flex items-center mb-4 pb-3 border-b border-gray-300 dark:border-gray-600">
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">Filtros de Búsqueda</h2>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-4">
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Contratista</label>
                                <input wire:model="filtroContratista" type="text" placeholder="Buscar contratista..." class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Tipo Entidad</label>
                                <select wire:model="filtroEntidad" class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                                    <option value="">Todas las Entidades</option>
                                    <option value="App\Models\Contratista">Empresa</option>
                                    <option value="App\Models\Trabajador">Trabajador</option>
                                    <option value="App\Models\Vehiculo">Vehículo</option>
                                    <option value="App\Models\Maquinaria">Maquinaria</option>
                                    <option value="App\Models\Embarcacion">Embarcación</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Nombre Documento</label>
                                <input wire:model="filtroDocumento" type="text" placeholder="Buscar documento..." class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">ID Entidad</label>
                                <input wire:model="filtroIdEntidad" type="text" placeholder="Rut / Cedula / Nit / Patente-Placa" class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Tipo Contratista</label>
                                <select wire:model="filtroTipoContratista" class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                                    <option value="todos">Todos los Tipos</option>
                                    <option value="contratistas">Solo Contratistas</option>
                                    <option value="subcontratistas">Solo Sub-Contratistas</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Estado Validación</label>
                                <select wire:model="filtroEstado" class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                                    <option value="">Todos los Estados</option>
                                    <option value="Sin Asignar">Sin Asignar</option>
                                    <option value="Asignado">Asignado</option>
                                    <option value="Devuelto">Devuelto</option>
                                    <option value="Pendiente Validación Mandante">Pendiente Validación Principal</option>
                                    <option value="Revisado">Revisado (Finalizado)</option>
                                    <option value="Asignar-Revalidar">Revalidar (Sin Asignar)</option>
                                    <option value="Asignado-Revalidar">Revalidar (Asignado)</option>
                                    <option value="Revisado-Revalidado">Revisado (Por Revalidación)</option>
                                    <option value="Archivado">Archivado</option>
                                    <option value="Archivado-Revalidado">Archivado (Por Revalidación)</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Resultado</label>
                                <select wire:model="filtroResultado" class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                                    <option value="">Todos los Resultados</option>
                                    <option value="Aprobado">Aprobado</option>
                                    <option value="Rechazado">Rechazado</option>
                                </select>
                            </div>

                            @if(Auth::user()->hasRole('Mandante_Admin'))
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Validador</label>
                                <select wire:model="filtroValidador" class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                                    <option value="">Todos los Validadores</option>
                                    @foreach ($validadores as $validador)
                                        <option value="{{ $validador->id }}">{{ $validador->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Responsabilidad / Flujo</label>
                                <select wire:model="filtroResponsabilidad" class="w-full px-4 py-2.5 bg-indigo-50 dark:bg-indigo-900/20 border-2 border-indigo-200 dark:border-indigo-700 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 font-bold text-indigo-700 dark:text-indigo-300">
                                    <option value="todos">Cualquier Responsabilidad</option>
                                    <option value="pendientes">Pendientes de Mi Acción (Validar)</option>
                                    <option value="exclusivos">Solo Mi Gestión (Exclusivos)</option>
                                    <option value="gestionados">Ya Gestionados por Mí</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Vigencia</label>
                                <select wire:model="filtroVigencia" class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                                    <option value="">Todos los Estados</option>
                                    <option value="Vigente">Vigente</option>
                                    <option value="Vigente-Modificado">Vigente (Modificado)</option>
                                    <option value="Vencido">Vencido</option>
                                    <option value="Vencido-Modificado">Vencido (Modificado)</option>
                                    <option value="Por Periodo">Por Periodo</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">F. Carga Desde</label>
                                <div class="flex gap-2">
                                    <input wire:model="filtroFechaCargaDesde" type="date" class="flex-1 px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                                    @if($filtroFechaCargaDesde || $filtroFechaCargaHasta)
                                    <button wire:click="borrarFechasCarga" class="px-3 py-2.5 bg-red-100 hover:bg-red-200 dark:bg-red-900/50 dark:hover:bg-red-900 text-red-700 dark:text-red-300 rounded-lg font-semibold transition-all duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    @endif
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">F. Carga Hasta</label>
                                <input wire:model="filtroFechaCargaHasta" type="date" class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">F. Val. Desde</label>
                                <div class="flex gap-2">
                                    <input wire:model="filtroFechaDesde" type="date" class="flex-1 px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                                    @if($filtroFechaDesde || $filtroFechaHasta)
                                    <button wire:click="borrarFechasValidacion" class="px-3 py-2.5 bg-red-100 hover:bg-red-200 dark:bg-red-900/50 dark:hover:bg-red-900 text-red-700 dark:text-red-300 rounded-lg font-semibold transition-all duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    @endif
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">F. Val. Hasta</label>
                                <input wire:model="filtroFechaHasta" type="date" class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-300 dark:border-gray-600">
                            <x-primary-button wire:click="buscar" wire:loading.attr="disabled" class="flex-1 sm:flex-none px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Buscar
                            </x-primary-button>
                            <x-danger-button wire:click="resetearFiltros" wire:loading.attr="disabled" class="flex-1 sm:flex-none px-8 py-3 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Resetear
                            </x-danger-button>
                        </div>
                    </div>

                    @if(Auth::user()->hasRole('Mandante_Admin'))
                    <div class="mb-6 p-6 bg-gradient-to-br from-gray-50 to-indigo-50 dark:from-gray-800 dark:to-indigo-900/20 rounded-xl border border-gray-200 dark:border-gray-700 shadow-lg">
                        <div class="flex items-center mb-4 pb-3 border-b border-gray-300 dark:border-gray-600">
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Acciones Masivas</h3>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div class="bg-white dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Asignar Validador
                                </h4>
                                <div class="space-y-3">
                                    <select wire:model.live="validadorSeleccionado" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                        <option value="">Seleccionar validador...</option>
                                        @foreach ($validadores as $validador)
                                            <option value="{{ $validador->id }}">{{ $validador->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="flex gap-2">
                                        <x-primary-button wire:click="asignarSeleccionados" wire:loading.attr="disabled" :disabled="!count($documentosSeleccionados) || !$validadorSeleccionado" class="flex-1 justify-center py-2 text-sm">
                                            Asignar ({{ count($documentosSeleccionados) }})
                                        </x-primary-button>
                                        <x-secondary-button wire:click="desasignarSeleccionados" wire:loading.attr="disabled" :disabled="!count($documentosSeleccionados)" class="px-4 py-2 text-sm">
                                            Desasignar
                                        </x-secondary-button>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Revalidar Documentos
                                </h4>
                                <div class="space-y-3">
                                    <input type="text" wire:model.live="motivoRevalidacionMasiva" placeholder="Motivo de revalidación..." class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-red-500">
                                    <x-danger-button wire:click="revalidarSeleccionados" wire:loading.attr="disabled" :disabled="!count($seleccionParaRevalidar) || !$motivoRevalidacionMasiva" class="w-full justify-center py-2 text-sm">
                                        Revalidar ({{ count($seleccionParaRevalidar) }})
                                    </x-danger-button>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Acciones Rápidas
                                </h4>
                                <div class="grid grid-cols-2 gap-2">
                                    <button wire:click="abrirModalModificarVencimiento" wire:loading.attr="disabled" :disabled="!count($seleccionParaModificar)" class="px-3 py-2 bg-orange-500 hover:bg-orange-600 disabled:opacity-50 text-white text-xs font-semibold rounded-lg transition-all duration-200 flex items-center justify-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Modificar Venc. ({{ count($seleccionParaModificar) }})
                                    </button>
                                    <button wire:click="abrirModalNotificacion" class="px-3 py-2 bg-cyan-600 hover:bg-cyan-700 disabled:opacity-50 text-white text-xs font-semibold rounded-lg transition-all duration-200 flex items-center justify-center gap-1" wire:loading.attr="disabled" :disabled="!isset($documentos) || $documentos->total() === 0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        Notificar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <div class="rounded-xl overflow-hidden shadow-xl border border-gray-200 dark:border-gray-700">
                        <div class="overflow-x-auto max-h-[75vh]">
                            <table class="min-w-full divide-y-2 divide-gray-300 dark:divide-gray-600">
                                <thead class="bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 sticky top-0 z-20">
                                    <tr>
                                         <th scope="col" class="p-4 text-center text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider sticky left-0 bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 z-30" style="min-width: 100px;">
                                             <div class="flex flex-col items-center gap-2">
                                                 @if(!$esSoloLectura && Auth::user()->hasRole('Mandante_Admin'))
                                                 <div class="flex flex-col items-center">
                                                     <input type="checkbox" wire:model.live="seleccionarTodos" title="Seleccionar todos para Asignación" class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                                     <span class="text-[9px] mt-1">Asignar</span>
                                                 </div>
                                                 @endif
                                             </div>
                                         </th>
                                         <th scope="col" class="px-4 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Acciones</th>
                                         <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider sticky bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 z-20" style="left: 100px;">Nº</th>
                                         <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider sticky bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 z-20" style="left: 150px;"># ID</th>
                                         <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider sticky bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 z-20" style="left: 210px;">Contratista</th>
                                         <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">RUT Contratista</th>
                                         <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Documento</th>
                                         <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Entidad</th>
                                         <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">ID Entidad</th>
                                         <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Estado Validación</th>
                                         <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Resultado</th>
                                         <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors" wire:click="sortBy('fecha_validacion')">
                                             <div class="flex items-center gap-1">
                                                 F. Validación
                                                 @if($sortField === 'fecha_validacion')
                                                     <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                         @if($sortDirection === 'asc')
                                                             <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                         @else
                                                             <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                         @endif
                                                     </svg>
                                                 @endif
                                             </div>
                                         </th>
                                         <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">F. Vencimiento</th>
                                         <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Vigencia</th>
                                         <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Validador</th>
                                         <th scope="col" class="p-4 text-center text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider" style="min-width: 100px;">
                                             <div class="flex flex-col items-center gap-2">
                                                 @if(!$esSoloLectura && Auth::user()->hasRole('Mandante_Admin'))
                                                 <div class="flex flex-col items-center">
                                                     <input type="checkbox" wire:model.live="seleccionarTodosRevalidar" title="Seleccionar todos los elegibles para Revalidación" class="w-5 h-5 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                                     <span class="text-[9px] mt-1">Revalidar</span>
                                                 </div>
                                                 @endif
                                             </div>
                                         </th>
                                         <th scope="col" class="p-4 text-center text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider" style="min-width: 100px;">
                                             <div class="flex flex-col items-center gap-2">
                                                 @if(!$esSoloLectura && Auth::user()->hasRole('Mandante_Admin'))
                                                 <div class="flex flex-col items-center">
                                                     <input type="checkbox" wire:model.live="seleccionarTodosModificar" title="Seleccionar todos los elegibles para Editar Vencimiento" class="w-5 h-5 text-orange-600 border-gray-300 rounded focus:ring-orange-500">
                                                     <span class="text-[9px] mt-1">Venc.</span>
                                                 </div>
                                                 @endif
                                             </div>
                                         </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y-2 divide-gray-300 dark:divide-gray-600">
                                    @forelse ($documentos as $key => $documento)
                                        @php
                                            $isRevisadoActivo = $documento->resultado_validacion && !in_array($documento->estado_validacion, ['Archivado', 'Archivado-Revalidado']);
                                            $displayState = $documento->estado_validacion;
                                            if ($isRevisadoActivo && !in_array($displayState, ['Revisado-Revalidado'])) {
                                                $displayState = 'Revisado';
                                            }
                                            
                                            $baseRowClass = '';
                                            if ($documento->estado_validacion == 'Devuelto') {
                                                $baseRowClass = 'bg-yellow-50 dark:bg-yellow-900/20 border-b-2 border-yellow-200 dark:border-yellow-800';
                                            } elseif (in_array($documento->estado_validacion, ['Archivado', 'Archivado-Revalidado'])) {
                                                $baseRowClass = 'opacity-50 bg-gray-100 dark:bg-gray-900/30 border-b-2 border-gray-300 dark:border-gray-700';
                                            } else {
                                                $baseRowClass = ($loop->even ? 'bg-orange-50 dark:bg-orange-900/10' : 'bg-white dark:bg-gray-800') . ' border-b-2 border-gray-300 dark:border-gray-600';
                                            }
                                            
                                            $rowClasses = 'hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors ' . $baseRowClass;
                                            
                                            if ($documento->estado_validacion == 'Devuelto') {
                                                $stickyClasses = 'sticky z-10 bg-yellow-50 dark:bg-yellow-900/20';
                                            } elseif (in_array($documento->estado_validacion, ['Archivado', 'Archivado-Revalidado'])) {
                                                $stickyClasses = 'sticky z-10 bg-gray-100 dark:bg-gray-900/30';
                                            } else {
                                                $stickyClasses = 'sticky z-10 ' . ($loop->even ? 'bg-orange-50 dark:bg-orange-900/10' : 'bg-white dark:bg-gray-800');
                                            }
                                        @endphp
                                        
                                        <tr wire:key="doc-{{ $documento->id }}" class="{{ $rowClasses }}">
                                            <td class="p-4 text-center {{ $stickyClasses }}" style="left: 0;">
                                                <div class="flex flex-col items-center gap-2">
                                                    @if(!$esSoloLectura && Auth::user()->hasRole('Mandante_Admin'))
                                                    <input type="checkbox" wire:model.live="documentosSeleccionados" value="{{ $documento->id }}" title="Marcar para Asignación" class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" @if($documento->resultado_validacion) disabled @endif>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                                @if($documento->estado_validacion === 'Pendiente Validación Mandante' && !$esSoloLectura)
                                                    <a href="{{ route('document.revisar', $documento->id) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition-all duration-200 shadow hover:shadow-md gap-1">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        VALIDAR
                                                    </a>
                                                @else
                                                    <span class="text-gray-400 text-xs">---</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100 {{ $stickyClasses }}" style="left: 100px;">{{ $documentos->firstItem() + $key }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 {{ $stickyClasses }}" style="left: 150px;">{{ $documento->id }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm {{ $stickyClasses }}" style="left: 210px;">
                                                @if($documento->contratista)
                                                    @php $padre = $documento->contratista->contratistaPadreAprobado->first(); @endphp
                                                    @if($padre)
                                                        <div class="text-xs">
                                                            <span class="font-semibold text-indigo-600 dark:text-indigo-400">Sub-Contratista de:</span>
                                                            <span class="block text-gray-700 dark:text-gray-300">{{ $padre->razon_social }}</span>
                                                        </div>
                                                    @else
                                                        <span class="text-gray-700 dark:text-gray-300">{{ $documento->contratista->razon_social }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-gray-500">N/A</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $documento->contratista->rut ?? 'N/A' }}</td>
                                            <td class="px-4 py-4 text-sm">
                                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $documento->nombre_documento_snapshot }}</div>
                                                @if($documento->estado_validacion === 'Devuelto' && $documento->observacion_interna_asem)
                                                    <div class="text-xs text-yellow-700 dark:text-yellow-300 mt-1 p-2 bg-yellow-100 dark:bg-yellow-900/50 rounded-md border border-yellow-300 dark:border-yellow-700" title="{{ $documento->observacion_interna_asem }}">
                                                        <strong>Motivo Dev:</strong> {{ Str::limit($documento->observacion_interna_asem, 50) }}
                                                    </div>
                                                @endif
                                                @if(!empty($documento->motivo_revalidacion))
                                                    <div class="text-xs text-purple-700 dark:text-purple-300 mt-1 p-2 bg-purple-100 dark:bg-purple-900/50 rounded-md border border-purple-300 dark:border-purple-700" title="{{ $documento->motivo_revalidacion }}">
                                                        <strong>Motivo Rev:</strong> {{ Str::limit($documento->motivo_revalidacion, 50) }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ class_basename($documento->entidad_type) }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                                @if($documento->entidad)
                                                    @if($documento->entidad instanceof \App\Models\Vehiculo) 
                                                        <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $documento->entidad->patente_letras }} {{ $documento->entidad->patente_numeros }}</span>
                                                    @elseif($documento->entidad instanceof \App\Models\Trabajador)
                                                        <div class="font-bold text-gray-900 dark:text-gray-100">{{ $documento->entidad->rut }}</div>
                                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $documento->entidad->nombres }} {{ $documento->entidad->apellido_paterno }} {{ $documento->entidad->apellido_materno }}</div>
                                                    @elseif($documento->entidad instanceof \App\Models\Maquinaria) 
                                                        <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $documento->entidad->identificador_letras }} {{ $documento->entidad->identificador_numeros }}</span>
                                                    @elseif($documento->entidad instanceof \App\Models\Embarcacion) 
                                                        <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $documento->entidad->matricula_letras }} {{ $documento->entidad->matricula_numeros }}</span>
                                                    @elseif($documento->entidad instanceof \App\Models\Contratista) 
                                                        <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $documento->entidad->rut }}</span>
                                                    @else 
                                                        <span class="text-gray-500">N/A</span>
                                                    @endif
                                                @else 
                                                    <span class="text-gray-500">N/A</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                                <span @class(['px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full',
                                                    'bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-100' => in_array($documento->estado_validacion, ['Sin Asignar']),
                                                    'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' => str_contains($documento->estado_validacion, 'Asignado-'),
                                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $documento->estado_validacion == 'Devuelto',
                                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $isRevisadoActivo || $documento->estado_validacion === 'Revisado-Revalidado' || $documento->estado_validacion === 'Revisado',
                                                    'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' => str_contains($documento->estado_validacion, 'Revalidar') && !str_contains($documento->estado_validacion, 'Asignado-'),
                                                    'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200' => $documento->estado_validacion == 'Pendiente Validación Principal',
                                                    'bg-gray-500 text-white' => in_array($documento->estado_validacion, ['Archivado', 'Archivado-Revalidado']),
                                                ])>
                                                    {{ $displayState }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $documento->resultado_validacion == 'Aprobado' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }} {{ $documento->resultado_validacion == 'Rechazado' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}">
                                                    {{ $documento->resultado_validacion ?? '---' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm bg-teal-50 dark:bg-teal-900/30 font-medium text-gray-900 dark:text-gray-100">{{ $documento->fecha_validacion ? Carbon::parse($documento->fecha_validacion)->format('d-m-Y H:i') : '---' }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $documento->fecha_vencimiento_formateada }}
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        @php
                                                            $vigenciaClass = '';
                                                            if (str_contains($documento->estado_vigencia, 'Vigente')) $vigenciaClass = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                                                            elseif (str_contains($documento->estado_vigencia, 'Vencido')) $vigenciaClass = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                                                            else $vigenciaClass = 'bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-100';
                                                            
                                                            $finalClass = str_contains($documento->estado_vigencia, '-Modificado') 
                                                                        ? $vigenciaClass . ' ring-2 ring-orange-400 dark:ring-orange-500'
                                                                        : $vigenciaClass;
                                                        @endphp
                                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $finalClass }}">
                                                            {{ $documento->estado_vigencia ?? '---' }}
                                                        </span>
                                                        @if($documento->ruta_justificativo_modificacion)
                                                        <a href="{{ $documento->justificativo_url }}" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline font-medium">
                                                            Ver
                                                        </a>
                                                        @endif
                                                    </div>
                                                    @if($documento->es_vencimiento_modificado && $documento->motivo_modificacion_vencimiento)
                                                        <div class="text-xs text-orange-700 dark:text-orange-300 mt-1 italic" title="{{ $documento->motivo_modificacion_vencimiento }}">
                                                            {{ Str::limit($documento->motivo_modificacion_vencimiento, 35) }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                                {{ $documento->validadorMandante->name ?? '---' }}
                                            </td>
                                            <td class="p-4 text-center">
                                                <div class="flex flex-col items-center gap-2">
                                                    @if(!$esSoloLectura && Auth::user()->hasRole('Mandante_Admin'))
                                                    <input type="checkbox" wire:model.live="seleccionParaRevalidar" value="{{ $documento->id }}" title="Marcar para Revalidación" class="w-5 h-5 text-red-600 border-gray-300 rounded focus:ring-red-500" @if(!$isRevisadoActivo || !$documento->valida_solo_mandante_snapshot) disabled @endif>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="p-4 text-center">
                                                <div class="flex flex-col items-center gap-2">
                                                    @if(!$esSoloLectura && Auth::user()->hasRole('Mandante_Admin'))
                                                    <input type="checkbox" wire:model.live="seleccionParaModificar" value="{{ $documento->id }}" title="Marcar para Editar Vencimiento" class="w-5 h-5 text-orange-600 border-gray-300 rounded focus:ring-orange-500" @if(!$isRevisadoActivo) disabled @endif>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="17" class="px-6 py-12 text-center">
                                                <div class="flex flex-col items-center justify-center space-y-3">
                                                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    @if(!$busquedaRealizada)
                                                        <p class="text-gray-500 dark:text-gray-400 text-lg">Utilice los filtros y presione <strong>"Buscar"</strong> para mostrar los documentos.</p>
                                                    @else
                                                        <p class="text-gray-500 dark:text-gray-400 text-lg">No hay documentos que coincidan con los filtros aplicados.</p>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        @if($documentos) 
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                                {{ $documentos->links() }} 
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @if ($showModificarVencimientoModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 backdrop-blur-sm" wire:keydown.escape.window="cerrarModalModificarVencimiento">
            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-2xl w-full max-w-2xl border-2 border-indigo-200 dark:border-indigo-700" @click.away="cerrarModalModificarVencimiento">
                <div class="flex items-center mb-6 pb-4 border-b-2 border-indigo-200 dark:border-indigo-700">
                    <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Editar Vencimiento</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ count($seleccionParaModificar) }} documentos seleccionados</p>
                    </div>
                </div>
                
                <div class="space-y-6" x-data="{ tipo: @entangle('tipoModificacion').live }">
                    <fieldset class="bg-gray-50 dark:bg-gray-700/50 p-5 rounded-xl border border-gray-200 dark:border-gray-600">
                        <legend class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-3 px-2">Tipo de Modificación</legend>
                        <div class="space-y-3">
                            <label class="flex items-center p-4 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all duration-200">
                                <input type="radio" wire:model.live="tipoModificacion" value="fecha_fija" name="tipo_mod" class="focus:ring-indigo-500 h-5 w-5 text-indigo-600 border-gray-300">
                                <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">Asignar Fecha Fija</span>
                            </label>
                            <label class="flex items-center p-4 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all duration-200">
                                <input type="radio" wire:model.live="tipoModificacion" value="sumar_dias" name="tipo_mod" class="focus:ring-indigo-500 h-5 w-5 text-indigo-600 border-gray-300">
                                <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">Sumar / Restar Días</span>
                            </label>
                        </div>
                    </fieldset>
                    
                    <div x-show="tipo === 'fecha_fija'" x-transition class="bg-indigo-50 dark:bg-indigo-900/20 p-5 rounded-xl border border-indigo-200 dark:border-indigo-700">
                        <label for="fechaFija" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Nueva Fecha de Vencimiento</label>
                        <input type="date" id="fechaFija" wire:model="fechaFija" class="w-full px-4 py-3 rounded-lg shadow-sm border-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        @error('fechaFija') <span class="text-red-500 text-xs mt-2 flex items-center gap-1"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>{{ $message }}</span> @enderror
                    </div>
                    
                    <div x-show="tipo === 'sumar_dias'" x-transition class="bg-indigo-50 dark:bg-indigo-900/20 p-5 rounded-xl border border-indigo-200 dark:border-indigo-700">
                        <label for="diasASumar" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Días a Sumar o Restar</label>
                        <input type="number" id="diasASumar" wire:model="diasASumar" placeholder="Ej: 365 para sumar un año, -30 para restar un mes" class="w-full px-4 py-3 rounded-lg shadow-sm border-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                         @error('diasASumar') <span class="text-red-500 text-xs mt-2 flex items-center gap-1"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700/50 p-5 rounded-xl border border-gray-200 dark:border-gray-600">
                        <label for="motivoModificacion" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Motivo de la Modificación (Obligatorio)</label>
                        <textarea id="motivoModificacion" wire:model="motivoModificacion" rows="3" class="w-full px-4 py-3 rounded-lg shadow-sm border-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Describa el motivo de la modificación..."></textarea>
                        @error('motivoModificacion') <span class="text-red-500 text-xs mt-2 flex items-center gap-1"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700/50 p-5 rounded-xl border border-gray-200 dark:border-gray-600">
                        <label for="justificativo" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Archivo Justificativo (Opcional)</label>
                        <input type="file" id="justificativo" wire:model="justificativoModificacion" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                        <div wire:loading wire:target="justificativoModificacion" class="text-sm text-indigo-600 dark:text-indigo-400 mt-2 flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Cargando archivo...
                        </div>
                        @error('justificativoModificacion') <span class="text-red-500 text-xs mt-2 flex items-center gap-1"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>{{ $message }}</span> @enderror
                    </div>
                </div>
                
                <div class="mt-8 flex justify-end gap-4 pt-6 border-t-2 border-gray-200 dark:border-gray-700">
                    <x-secondary-button wire:click="cerrarModalModificarVencimiento" class="px-6 py-3">
                        Cancelar
                    </x-secondary-button>
                    <x-primary-button wire:click="confirmarModificacionVencimiento" wire:loading.attr="disabled" wire:target="confirmarModificacionVencimiento, justificativoModificacion" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700">
                        <span wire:loading.remove wire:target="confirmarModificacionVencimiento">Confirmar Modificación</span>
                        <span wire:loading wire:target="confirmarModificacionVencimiento" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Procesando...
                        </span>
                    </x-primary-button>
                </div>
            </div>
        </div>
    @endif

    @if ($showAuditoriaModal && $documentoAuditoria)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 backdrop-blur-sm p-4" wire:keydown.escape.window="cerrarModalAuditoria">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-7xl max-h-[95vh] flex flex-col border-2 border-indigo-200 dark:border-indigo-700" @click.away="cerrarModalAuditoria">
            <div class="flex-shrink-0 flex justify-between items-center bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6 rounded-t-2xl">
                <div class="flex items-center gap-3">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <div>
                        <h3 class="text-2xl font-bold text-white">
                            Vista de Documento
                        </h3>
                        <p class="text-indigo-100 text-sm">ID: {{ $documentoAuditoria->id }}</p>
                    </div>
                </div>
                <button wire:click="cerrarModalAuditoria" class="text-white hover:bg-white/20 rounded-full p-2 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="flex-grow grid grid-cols-1 lg:grid-cols-2 gap-6 overflow-hidden p-8">
                <div class="flex flex-col h-full">
                    <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Documento Cargado
                    </h4>
                    <div class="border-2 border-gray-300 dark:border-gray-600 rounded-xl overflow-hidden flex-grow shadow-lg">
                        <iframe src="{{ $documentoAuditoria->url }}" class="w-full h-full" frameborder="0"></iframe>
                    </div>
                </div>

                <div class="flex flex-col space-y-4 text-sm overflow-y-auto pr-2">
                    
                    <div class="bg-gradient-to-br from-gray-50 to-indigo-50 dark:from-gray-700/50 dark:to-indigo-900/20 p-5 rounded-xl border-2 border-gray-200 dark:border-gray-600 space-y-3">
                        <h4 class="text-md font-bold text-gray-800 dark:text-gray-200 border-b-2 border-indigo-200 dark:border-indigo-700 pb-2 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Información Principal
                        </h4>
                        <p><strong class="text-gray-700 dark:text-gray-300 w-32 inline-block">Principal:</strong> <span class="text-gray-900 dark:text-gray-100">{{ $documentoAuditoria->mandante->razon_social ?? 'N/A' }}</span></p>
                        <p><strong class="text-gray-700 dark:text-gray-300 w-32 inline-block">Contratista:</strong> <span class="text-gray-900 dark:text-gray-100">{{ $documentoAuditoria->contratista->razon_social ?? 'N/A' }} ({{ $documentoAuditoria->contratista->rut ?? 'N/A' }})</span></p>
                    </div>

                    <div class="bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-700/50 dark:to-blue-900/20 p-5 rounded-xl border-2 border-gray-200 dark:border-gray-600 space-y-3">
                        <h4 class="text-md font-bold text-gray-800 dark:text-gray-200 border-b-2 border-blue-200 dark:border-blue-700 pb-2 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Entidad Asociada
                        </h4>
                        @if($documentoAuditoria->entidad)
                            <p><strong class="text-gray-700 dark:text-gray-300 w-32 inline-block">Trabajador:</strong> <span class="text-gray-900 dark:text-gray-100">{{ $documentoAuditoria->entidad->nombre_completo ?? 'N/A' }}</span></p>
                            <p><strong class="text-gray-700 dark:text-gray-300 w-32 inline-block">ID:</strong> <span class="text-gray-900 dark:text-gray-100">{{ $documentoAuditoria->entidad->rut ?? $documentoAuditoria->entidad->identificador_completo ?? 'N/A' }}</span></p>
                            @if($cargoAuditoria)
                                <p><strong class="text-gray-700 dark:text-gray-300 w-32 inline-block">Cargo:</strong> <span class="text-gray-900 dark:text-gray-100">{{ $cargoAuditoria }}</span></p>
                            @endif
                        @endif
                    </div>
                    
                    <div class="bg-gradient-to-br from-gray-50 to-purple-50 dark:from-gray-700/50 dark:to-purple-900/20 p-5 rounded-xl border-2 border-gray-200 dark:border-gray-600 space-y-3">
                        <h4 class="text-md font-bold text-gray-800 dark:text-gray-200 border-b-2 border-purple-200 dark:border-purple-700 pb-2 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Detalles del Documento
                        </h4>
                        <p><strong class="text-gray-700 dark:text-gray-300 w-32 inline-block">Nombre:</strong> <span class="text-gray-900 dark:text-gray-100">{{ $documentoAuditoria->nombre_documento_snapshot }}</span></p>
                        @if ($documentoAuditoria->periodo)
                            <p><strong class="text-gray-700 dark:text-gray-300 w-32 inline-block">Periodo:</strong> <span class="text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::createFromFormat('Y-m', $documentoAuditoria->periodo)->translatedFormat('F \d\e Y') }}</span></p>
                        @endif
                        <p><strong class="text-gray-700 dark:text-gray-300 w-32 inline-block">Fecha Carga:</strong> <span class="text-gray-900 dark:text-gray-100">{{ $documentoAuditoria->created_at->format('d-m-Y H:i') }}</span></p>
                    </div>

                    <div class="bg-gradient-to-br from-gray-50 to-teal-50 dark:from-gray-700/50 dark:to-teal-900/20 p-5 rounded-xl border-2 border-gray-200 dark:border-gray-600 space-y-3">
                        <h4 class="text-md font-bold text-gray-800 dark:text-gray-200 border-b-2 border-teal-200 dark:border-teal-700 pb-2 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            Guía de Regla
                        </h4>
                        @if ($documentoAuditoria->reglaDocumental?->observacionDocumento)
                            <div>
                                <strong class="font-semibold text-gray-700 dark:text-gray-300">Observación para Validador:</strong>
                                <p class="text-gray-600 dark:text-gray-400 italic mt-1 p-3 bg-white dark:bg-gray-800 rounded-lg">{{ $documentoAuditoria->reglaDocumental->observacionDocumento->titulo }}</p>
                            </div>
                        @endif
                        @if ($documentoAuditoria->reglaDocumental?->formatoDocumento?->ruta_archivo)
                            <div>
                                <strong class="font-semibold text-gray-700 dark:text-gray-300">Formato:</strong>
                                <p><a href="{{ Storage::disk('public')->url($documentoAuditoria->reglaDocumental->formatoDocumento->ruta_archivo) }}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline font-medium">Ver Formato de Ejemplo</a></p>
                            </div>
                        @endif
                    </div>

                    @if(!empty($documentoAuditoria->criterios_snapshot))
                        <div class="bg-gradient-to-br from-gray-50 to-orange-50 dark:from-gray-700/50 dark:to-orange-900/20 p-5 rounded-xl border-2 border-gray-200 dark:border-gray-600 space-y-3">
                            <h4 class="text-md font-bold text-gray-800 dark:text-gray-200 border-b-2 border-orange-200 dark:border-orange-700 pb-2 mb-3 flex items-center gap-2">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 7h6m-6 4h6" />
                                </svg>
                                Criterios de Evaluación
                            </h4>
                            <div class="space-y-3 max-h-40 overflow-y-auto pr-2">
                                @foreach($documentoAuditoria->criterios_snapshot as $criterioData)
                                    <div class="flex items-start bg-white dark:bg-gray-800 p-3 rounded-lg">
                                        <span class="font-bold text-orange-600 dark:text-orange-400 mr-3 text-lg">•</span>
                                        <div>
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $criterioData['criterio'] ?? 'Criterio no definido' }}</span>
                                            @if(!empty($criterioData['sub_criterio']))
                                                <span class="block text-blue-600 dark:text-blue-400 font-semibold mt-1">{{ $criterioData['sub_criterio'] }}</span>
                                            @endif
                                            @if(!empty($criterioData['aclaracion']))
                                                <p class="text-xs text-gray-500 dark:text-gray-400 italic mt-1">{{ $criterioData['aclaracion'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="bg-gradient-to-br from-gray-50 to-green-50 dark:from-gray-700/50 dark:to-green-900/20 p-5 rounded-xl border-2 border-gray-200 dark:border-gray-600 space-y-3">
                        <h4 class="text-md font-bold text-gray-800 dark:text-gray-200 border-b-2 border-green-200 dark:border-green-700 pb-2 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Detalles de la Revisión Original
                        </h4>
                        <p><strong class="text-gray-700 dark:text-gray-300">Resultado:</strong> 
                            <span class="ml-2 px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $documentoAuditoria->resultado_validacion == 'Aprobado' ? 'bg-green-100 text-green-800 dark:bg-green-800/50 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-800/50 dark:text-red-200' }}">
                                {{ $documentoAuditoria->resultado_validacion }}
                            </span>
                        </p>
                        <p><strong class="text-gray-700 dark:text-gray-300">Validador:</strong> 
                            <span class="text-gray-900 dark:text-gray-100">
                                @if($documentoAuditoria->validadorAsem)
                                    {{ $documentoAuditoria->validadorAsem->name }} (ASEM)
                                @elseif($documentoAuditoria->validadorMandante)
                                    {{ $documentoAuditoria->validadorMandante->name }} (Principal)
                                @else
                                    Sistema
                                @endif
                            </span>
                        </p>
                        <p><strong class="text-gray-700 dark:text-gray-300">Fecha Validación:</strong> <span class="text-gray-900 dark:text-gray-100">{{ $documentoAuditoria->fecha_validacion ? $documentoAuditoria->fecha_validacion->format('d-m-Y H:i:s') : 'N/A' }}</span></p>
                        <div>
                            <p class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Observaciones de la Revisión:</p>
                            <div class="p-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 max-h-24 overflow-y-auto text-xs text-gray-700 dark:text-gray-300">
                                {{ $documentoAuditoria->observacion_rechazo ?: 'Sin observaciones.' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-shrink-0 px-8 py-6 flex justify-end gap-4 border-t-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 rounded-b-2xl">
                <x-secondary-button wire:click="cerrarModalAuditoria" class="px-6 py-3">
                    Cerrar
                </x-secondary-button>
            </div>
        </div>
    </div>
    @endif
    
    @if ($showNotificacionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 backdrop-blur-sm" wire:keydown.escape.window="cerrarModalNotificacion">
            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-2xl w-full max-w-2xl border-2 border-cyan-200 dark:border-cyan-700" @click.away="cerrarModalNotificacion">
                <div class="flex items-center mb-6 pb-4 border-b-2 border-cyan-200 dark:border-cyan-700">
                    <svg class="w-8 h-8 text-cyan-600 dark:text-cyan-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Confirmar Notificación Masiva</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Enviar notificaciones a contratistas</p>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <div class="bg-cyan-50 dark:bg-cyan-900/20 p-5 rounded-xl border-2 border-cyan-200 dark:border-cyan-700">
                        <label for="mensajeNotificacion" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Mensaje para el Contratista:</label>
                        <textarea id="mensajeNotificacion" wire:model.defer="mensajeNotificacion" rows="5" class="w-full px-4 py-3 rounded-lg shadow-sm border-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-cyan-500 focus:border-transparent" placeholder="Escriba el mensaje que se enviará a los contratistas..."></textarea>
                        @error('mensajeNotificacion') <span class="text-red-500 text-xs mt-2 flex items-center gap-1"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>{{ $message }}</span> @enderror
                    </div>

                    <div class="bg-gradient-to-r from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 p-6 rounded-xl text-center border-2 border-indigo-300 dark:border-indigo-700">
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Documentos a Notificar</p>
                                <p class="text-4xl font-bold text-indigo-600 dark:text-indigo-400">{{ $conteoNotificacion['total'] }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Contratistas</p>
                                <p class="text-4xl font-bold text-purple-600 dark:text-purple-400">{{ $conteoNotificacion['contratistas'] }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 dark:border-yellow-600 p-4 rounded-r-lg">
                        <div class="flex">
                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <div class="text-xs text-yellow-700 dark:text-yellow-300">
                                <strong>Nota:</strong> Esta acción se procesará en segundo plano. Los correos pueden tardar unos minutos en enviarse. Se enviará un correo por cada contratista afectado, listando todos sus documentos con observaciones.
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 flex justify-end gap-4 pt-6 border-t-2 border-gray-200 dark:border-gray-700">
                    <x-secondary-button wire:click="cerrarModalNotificacion" class="px-6 py-3">
                        Cancelar
                    </x-secondary-button>
                    <x-primary-button wire:click="despacharNotificaciones" class="px-6 py-3 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700" wire:loading.attr="disabled" wire:target="despacharNotificaciones">
                        <span wire:loading.remove wire:target="despacharNotificaciones">Confirmar y Enviar</span>
                        <span wire:loading wire:target="despacharNotificaciones" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Despachando...
                        </span>
                    </x-primary-button>
                </div>
            </div>
        </div>
    @endif
</div>