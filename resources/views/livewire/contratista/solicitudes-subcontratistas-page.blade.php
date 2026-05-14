<div>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-2xl rounded-2xl border-2 border-blue-300 overflow-hidden ring-4 ring-blue-100">
                <div class="p-8">
                    <h3 class="text-2xl font-bold text-blue-900 mb-6">Gestionar Solicitudes de Sub-Contratistas</h3>
                    @if (session()->has('message_sub'))
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ session('message_sub') }}</span>
                        </div>
                    @endif
                    @if (session()->has('error_sub'))
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ session('error_sub') }}</span>
                        </div>
                    @endif

                    <div class="overflow-x-auto shadow-lg rounded-lg border border-blue-300">
                        <table class="min-w-full divide-y divide-blue-300">
                            <thead class="bg-blue-800">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Sub-Contratista</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">RUT/NIT/RUC/CNPJ</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Vincular a Unidad Operativa</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-blue-200">
                                @forelse ($solicitudesSubcontratistas as $solicitud)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $solicitud->contratista->razon_social }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $solicitud->contratista->rut }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <select wire:model="unidadOrganizacionalParaVincular.{{ $solicitud->id }}" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                                <option value="">-- Seleccione U.O. --</option>
                                                @foreach ($vinculacionesDisponibles as $v)
                                                    <option value="{{ $v['id_seleccion'] }}">{{ $v['texto_visible'] }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button wire:click="aprobarYVincularSubcontratista({{ $solicitud->id }})"
                                                    wire:loading.attr="disabled"
                                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50">
                                                Aprobar y Vincular
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No hay solicitudes de sub-contratistas pendientes.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>