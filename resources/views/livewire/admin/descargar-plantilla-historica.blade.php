<div>
    <div class="mb-8 bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6 border border-blue-100 dark:border-blue-800/30">
        <div class="flex items-start mb-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-800 rounded-lg mr-4 flex-shrink-0">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div>
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Descarga de Plantilla Dinámica</h4>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Selecciona el mandante, contratista y el período. Se generará un CSV pre-llenado con todos los trabajadores activos correspondientes. Entrégale este CSV a la IA para que complete las contingencias donde corresponda.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            <!-- Mandante -->
            <div>
                <label for="mandante_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mandante</label>
                <select id="mandante_id" wire:model.live="mandante_id" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">-- Seleccionar --</option>
                    @foreach($mandantes as $mandante)
                        <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                    @endforeach
                </select>
                @error('mandante_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <!-- Contratista -->
            <div>
                <label for="contratista_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contratista</label>
                <select id="contratista_id" wire:model="contratista_id" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" @if(!$mandante_id) disabled @endif>
                    <option value="">-- Seleccionar --</option>
                    @if($mandante_id)
                        <option value="TODOS">TODOS LOS CONTRATISTAS</option>
                        @foreach($contratistas as $contratista)
                            <option value="{{ $contratista->id }}">{{ $contratista->razon_social }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- Mes -->
            <div>
                <label for="mes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mes</label>
                <select id="mes" wire:model="mes" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">{{ sprintf('%02d', $i) }}</option>
                    @endfor
                </select>
                @error('mes') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <!-- Año -->
            <div>
                <label for="anio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Año</label>
                <input type="number" id="anio" wire:model="anio" min="2000" max="2100" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                @error('anio') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <!-- UO Manual -->
            <div>
                <label for="unidad_organizacional" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">U.O. (Opcional)</label>
                <input type="text" id="unidad_organizacional" wire:model="unidad_organizacional" placeholder="Ej: LANDES U.O." class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <!-- Lugar Manual -->
            <div>
                <label for="lugar_trabajo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Lugar (Opcional)</label>
                <input type="text" id="lugar_trabajo" wire:model="lugar_trabajo" placeholder="Ej: LANDES LUGAR" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button wire:click="descargar" wire:loading.attr="disabled" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition-colors">
                <svg wire:loading.remove wire:target="descargar" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <svg wire:loading wire:target="descargar" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Descargar Plantilla Pre-llenada
            </button>
        </div>
    </div>
</div>
