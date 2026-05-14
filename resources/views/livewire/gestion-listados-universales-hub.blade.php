<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __("Gestión de Listados Universales") }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session()->has("success")) <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded dark:bg-green-700 dark:text-green-100 dark:border-green-600">{{ session("success") }}</div> @endif
            @if (session()->has("error")) <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded dark:bg-red-700 dark:text-red-100 dark:border-red-600">{{ session("error") }}</div> @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <p class="mb-6 text-lg">Panel de Control de Listados Universales.</p>
                    
                    @php($counter = 1)

                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6">
                        @foreach($listados as $item)
                            <a href="{{ route($item["ruta"]) }}" wire:navigate class="flex items-start space-x-4 p-4 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:shadow-md hover:border-blue-500 dark:hover:border-blue-400 transition-all duration-200 transform hover:-translate-y-1">
                                <span class="flex-shrink-0 h-8 w-8 flex items-center justify-center bg-blue-500 text-white font-bold rounded-full text-sm">{{ $counter }}</span>
                                <div>
                                    <h5 class="font-semibold text-lg text-gray-900 dark:text-gray-100">{{ $item["titulo"] }}</h5>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $item["desc"] }}</p>
                                </div>
                            </a>
                            @php($counter++)
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>