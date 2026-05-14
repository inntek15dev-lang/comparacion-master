<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
            
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">REGISTRO DE ACTIVIDAD (AUDITORÍA)</h2>
                <div class="flex space-x-2">
                    <button wire:click="descargarReporte" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded shadow transition ease-in-out duration-150 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Descargar Reporte
                    </button>
                    <button wire:click="runExportNow" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow transition ease-in-out duration-150">
                        Generar Reporte Ahora
                    </button>
                </div>
            </div>

            @if (session()->has('message'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 shadow-sm" role="alert">
                    <p>{{ session('message') }}</p>
                </div>
            @endif

            <!-- Configuración -->
            <div class="bg-gray-50 rounded-lg p-6 mb-8 border border-gray-200">
                <h3 class="text-lg font-semibold mb-4 text-gray-700 border-b pb-2">Configuración del Sistema</h3>
                <form wire:submit.prevent="saveSettings">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Correos para reportes (separados por coma)</label>
                                <textarea wire:model="emails" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="admin1@example.com, admin2@example.com"></textarea>
                                @error('emails') <span class="text-red-500 text-xs italic">{{ $message }}</span> @enderror
                                <p class="text-xs text-gray-500 mt-1">Mínimo sugerido: 3 correos.</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Hora de envío automático (HH:mm)</label>
                                <input type="time" wire:model="export_time" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                @error('export_time') <span class="text-red-500 text-xs italic">{{ $message }}</span> @enderror
                            </div>

                            <div class="flex items-center mt-4">
                                <input type="checkbox" wire:model="enabled" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label class="ml-2 block text-sm text-gray-900 font-medium">Sistema de Auditoría Activado</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded shadow transition ease-in-out duration-150">
                            Guardar Configuración
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabla de Logs -->
            <div class="overflow-x-auto bg-white rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Fecha/Hora</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Usuario</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Acción / Descripción</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">IP / Módulo</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $log->causer->name ?? 'Sistema' }}</div>
                                    <div class="text-xs text-gray-500">{{ $log->causer->email ?? '' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ str_contains($log->description, 'Login') ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $log->description }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div>{{ $log->getExtraProperty('ip') }}</div>
                                    <div class="text-xs text-blue-600 font-mono">{{ $log->getExtraProperty('route') }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-gray-500 italic">
                                    No hay registros acumulados para el día de hoy.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $logs->links() }}
            </div>

        </div>
    </div>
</div>
