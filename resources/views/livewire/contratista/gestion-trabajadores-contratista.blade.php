<div>
    <div class="py-0">
        <div class="max-w-full mx-auto">
            <div class="bg-transparent dark:bg-transparent overflow-hidden">

                @if (session()->has('message_trabajador') || session()->has('success') || session()->has('message_vinculacion'))
                    <div class="alert-success mb-4">
                        {{ session('message_trabajador') ?? (session('success') ?? session('message_vinculacion')) }}
                    </div>
                @endif
                @if (session()->has('error_trabajador') || session()->has('error') || session()->has('error_vinculacion'))
                    <div class="alert-danger mb-4">
                        {{ session('error_trabajador') ?? (session('error') ?? session('error_vinculacion')) }}
                    </div>
                @endif

                @if ($vistaActual === 'listado_trabajadores')
                    @if ($contratistaId)
                        <div class="mb-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                            <div class="lg:col-span-2">
                                <label for="searchTrabajador" class="label-form">Buscar Trabajador</label>
                                <input wire:model.live.debounce.300ms="searchTrabajador" id="searchTrabajador"
                                    type="text" placeholder="Buscar por RUT/CEDULA o Nombre del Trabajador..."
                                    class="input-field w-full">
                            </div>
                            <div>
                                <label for="filtroEstado" class="label-form">Filtrar por Estado</label>
                                <select wire:model.live="filtroEstado" id="filtroEstado" class="input-field w-full">
                                    <option value="activos">Sólo Activos</option>
                                    <option value="en_reserva">📦 En Reserva (Finiquitados/Desvinculados)</option>
                                    <option value="todos">Todos (Activos + Reserva)</option>
                                </select>
                            </div>
                            {{-- Filtros de N° Contrato y Tipo Contrato removidos - ya están en el panel superior --}}
                            <div class="lg:col-span-2 text-right flex items-end justify-end">
                                <button wire:click="abrirModalNuevoTrabajador" class="btn-primary"
                                    wire:loading.attr="disabled"
                                    @if (!$lugarDeTrabajoId || !is_numeric($lugarDeTrabajoId)) disabled title="Debe seleccionar un Lugar de Trabajo/Departamento específico para agregar un trabajador." @endif>
                                    <span wire:loading.remove wire:target="abrirModalNuevoTrabajador">
                                        <x-icons.plus class="w-5 h-5 mr-1 inline-block" /> Agregar Nuevo Trabajador
                                    </span>
                                    <span wire:loading wire:target="abrirModalNuevoTrabajador">
                                        <x-icons.spinner class="w-5 h-5 mr-1 inline-block" /> Abriendo...
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4 text-sm font-semibold text-gray-600 dark:text-gray-300">
                            Mostrando {{ $vinculacionesPaginadas->count() }} de {{ $totalAsignaciones }} asignaciones
                            ({{ $totalTrabajadoresUnicos }} trabajadores únicos).
                        </div>

                        <div class="relative">
                            <div wire:loading.flex
                                class="absolute inset-0 bg-white dark:bg-gray-800 bg-opacity-75 dark:bg-opacity-75 flex flex-col items-center justify-center z-50 rounded-lg">
                                <x-icons.spinner class="h-16 w-16 text-blue-600" />
                                <p class="mt-4 text-lg font-semibold text-blue-700 dark:text-blue-300">Trabajando para
                                    usted...</p>
                            </div>

                            <div
                                class="overflow-x-auto shadow-lg rounded-lg border border-gray-400 dark:border-gray-600">
                                <div class="max-h-[70vh] overflow-y-auto">
                                    <table class="min-w-full border-collapse">
                                        <thead class="bg-gray-200 dark:bg-gray-700">
                                            <tr class="border-b-2 border-gray-500 dark:border-gray-600">
                                                <th
                                                    class="table-header sticky top-0 z-30 bg-gray-200 dark:bg-gray-700 w-12 text-center left-0 border-r border-gray-400 dark:border-gray-500">
                                                    #</th>
                                                {{-- <th class="table-header sticky top-0 z-30 bg-gray-200 dark:bg-gray-700 w-16 text-center border-r border-gray-400 dark:border-gray-500" style="left: 48px;">ID</th> --}}
                                                <th class="table-header sticky top-0 z-30 bg-gray-200 dark:bg-gray-700 border-r border-gray-400 dark:border-gray-500"
                                                    style="left: 48px; min-width: 9rem; width: 9rem;">RUT / CEDULA</th>
                                                <th class="table-header sticky top-0 z-30 bg-gray-200 dark:bg-gray-700 border-r border-gray-400 dark:border-gray-500"
                                                    style="left: 176px; min-width: 14rem; width: 14rem;">Trabajador</th>

                                                <th
                                                    class="table-header sticky top-0 z-20 bg-gray-200 dark:bg-gray-700 border-r border-gray-400 dark:border-gray-500"
                                                    style="min-width: 11rem;">
                                                    Cargo</th>
                                                @if(!$sinAcreditacion)
                                                    <th
                                                        class="table-header sticky top-0 z-20 bg-gray-200 dark:bg-gray-700 w-32 text-center border-r border-gray-400 dark:border-gray-500">
                                                        % Cumplimiento</th>
                                                    <th
                                                        class="table-header sticky top-0 z-20 bg-gray-200 dark:bg-gray-700 w-32 text-center border-r border-gray-400 dark:border-gray-500">
                                                        Acceso</th>
                                                @endif
                                                <th
                                                    class="table-header sticky top-0 z-20 bg-gray-200 dark:bg-gray-700 w-28 text-center border-r border-gray-400 dark:border-gray-500">
                                                    Estado</th>

                                                @if (!empty($documentosMaestros))
                                                    @foreach ($documentosMaestros as $doc)
                                                        <th class="table-header sticky top-0 z-20 bg-gray-200 dark:bg-gray-700 w-16 text-center border-r border-gray-400 dark:border-gray-500"
                                                            title="{{ \App\Models\NombreDocumento::find($doc['nombre_documento_id'])->nombre ?? 'Documento' }}">
                                                            {{ $doc['numero'] }}
                                                        </th>
                                                    @endforeach
                                                @endif

                                                <th
                                                    class="table-header sticky top-0 z-20 bg-gray-200 dark:bg-gray-700 w-32 max-w-[8rem] border-r border-gray-400 dark:border-gray-500">
                                                    Lugar de Trabajo/Departamento</th>
                                                <th
                                                    class="table-header sticky top-0 z-20 bg-gray-200 dark:bg-gray-700 w-32 max-w-[8rem] border-r border-gray-400 dark:border-gray-500">
                                                    U.O.</th>
                                                <th
                                                    class="table-header sticky top-0 z-20 bg-gray-200 dark:bg-gray-700 w-28 max-w-[7rem] border-r border-gray-400 dark:border-gray-500">
                                                    N° Contrato</th>

                                                <th
                                                    class="table-header sticky top-0 z-30 bg-gray-200 dark:bg-gray-700 w-40 text-center right-0">
                                                    Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-800">
                                            @forelse ($vinculacionesPaginadas as $vinculacion)
                                                @php
                                                    $trab = $vinculacion->trabajador;

                                                    $estadoAccesoProtegido = $estadosPreCalculados[$vinculacion->id][
                                                        'estado_acceso'
                                                    ] ?? ['habilitado' => false, 'motivo' => 'Estado no calculado'];
                                                    $porcentajeCumplimientoProtegido =
                                                        $estadosPreCalculados[$vinculacion->id][
                                                            'porcentaje_cumplimiento'
                                                        ] ?? 0;
                                                @endphp
                                                <tr wire:key="vinculacion-{{ $vinculacion->id }}"
                                                    class="group even:bg-gray-100 dark:even:bg-gray-900/50 hover:bg-blue-100 dark:hover:bg-blue-900/20 border-b border-gray-400 dark:border-gray-600">

                                                    <td
                                                        class="table-cell text-center sticky left-0 z-30 bg-white dark:bg-gray-800 group-even:bg-gray-100 dark:group-even:bg-gray-900/50 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/20 border-r border-gray-400 dark:border-gray-600">
                                                        {{ ($vinculacionesPaginadas->currentPage() - 1) * $vinculacionesPaginadas->perPage() + $loop->iteration }}
                                                    </td>
                                                    <td class="table-cell sticky z-30 bg-white dark:bg-gray-800 group-even:bg-gray-100 dark:group-even:bg-gray-900/50 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/20 border-r border-gray-400 dark:border-gray-600"
                                                        style="left: 48px;">{{ $trab->rut }}</td>
                                                    <td class="table-cell font-semibold sticky z-30 bg-white dark:bg-gray-800 group-even:bg-gray-100 dark:group-even:bg-gray-900/50 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/20 border-r border-gray-400 dark:border-gray-600"
                                                        style="left: 176px;">{{ $trab->apellido_paterno }}
                                                        {{ $trab->apellido_materno }}, {{ $trab->nombres }}</td>

                                                    <td
                                                        class="table-cell border-r border-gray-400 dark:border-gray-600">
                                                        {{ $vinculacion->cargoMandante->nombre_cargo ?? 'N/A' }}</td>

                                                    @if(!$sinAcreditacion)
                                                        <td
                                                            class="table-cell text-center font-semibold border-r border-gray-400 dark:border-gray-600 {{ $porcentajeCumplimientoProtegido < 100 ? 'text-orange-600 dark:text-orange-400' : 'text-green-600 dark:text-green-400' }}">
                                                            {{ $porcentajeCumplimientoProtegido }}%
                                                        </td>

                                                        <td
                                                            class="table-cell text-center text-sm border-r border-gray-400 dark:border-gray-600">
                                                            @php
                                                                $puedeExcepcionar = Auth::user()->hasRole('ASEM_Admin') || Auth::user()->hasRole('Mandante_Admin');
                                                            @endphp
                                                            
                                                            @if ($estadoAccesoProtegido['habilitado'] ?? false)
                                                                @if ($puedeExcepcionar)
                                                                    @if ($estadoAccesoProtegido['es_excepcion'] ?? false)
                                                                        @php
                                                                            $fechaVencimiento = isset($estadoAccesoProtegido['valido_hasta']) && $estadoAccesoProtegido['valido_hasta'] 
                                                                                ? \Carbon\Carbon::parse($estadoAccesoProtegido['valido_hasta'])->format('d/m/Y')
                                                                                : 'Sin vencimiento';
                                                                            $tooltipText = "Vence: {$fechaVencimiento} | Click para revertir excepción manual";
                                                                        @endphp
                                                                        <button 
                                                                            wire:click="revertirAnulacionManual({{ $trab->id }}, {{ $vinculacion->id }})"
                                                                            wire:confirm="¿Desea revertir la excepción manual y volver al estado calculado por el sistema?"
                                                                            class="cursor-pointer hover:bg-green-100 dark:hover:bg-green-800 px-2 py-1 rounded transition-colors"
                                                                            title="{{ $tooltipText }}">
                                                                            <span class="font-semibold text-green-600 dark:text-green-400">
                                                                                HABILITADO
                                                                                <span class="text-xs block mt-1">(MANUAL)</span>
                                                                            </span>
                                                                        </button>
                                                                    @else
                                                                        <button 
                                                                            wire:click="abrirModalAnulacion({{ $trab->id }}, {{ $vinculacion->id }}, 'RESTRINGIR')"
                                                                            class="cursor-pointer hover:bg-red-100 dark:hover:bg-red-800 px-2 py-1 rounded transition-colors"
                                                                            title="Click para restringir acceso manualmente">
                                                                            <span class="font-semibold text-green-600 dark:text-green-400">
                                                                                HABILITADO
                                                                            </span>
                                                                        </button>
                                                                    @endif
                                                                @else
                                                                    <span
                                                                        class="font-semibold text-green-600 dark:text-green-400"
                                                                        title="{{ $estadoAccesoProtegido['motivo'] ?? 'Estado no calculado' }}">
                                                                        HABILITADO
                                                                        @if ($estadoAccesoProtegido['es_excepcion'] ?? false)
                                                                            <span class="text-xs block mt-1">(MANUAL)</span>
                                                                        @endif
                                                                    </span>
                                                                @endif
                                                            @else
                                                                @if ($puedeExcepcionar)
                                                                    <button 
                                                                        wire:click="abrirModalAnulacion({{ $trab->id }}, {{ $vinculacion->id }}, 'HABILITAR')"
                                                                        class="cursor-pointer hover:bg-green-100 dark:hover:bg-green-800 px-2 py-1 rounded transition-colors"
                                                                        title="Click para habilitar acceso manualmente">
                                                                        <span class="inline-flex items-center justify-center font-bold text-2xl text-red-500">
                                                                            ✕
                                                                        </span>
                                                                    </button>
                                                                @else
                                                                    <span
                                                                        class="inline-flex items-center justify-center font-bold text-2xl text-red-500"
                                                                        title="{{ $estadoAccesoProtegido['motivo'] ?? 'Estado no calculado' }}">
                                                                        ✕
                                                                    </span>
                                                                @endif
                                                            @endif
                                                        </td>
                                                    @endif

                                                    <td
                                                        class="table-cell text-center border-r border-gray-400 dark:border-gray-600">
                                                        @php
                                                            $esDesvinculado = !$vinculacion->is_active && $vinculacion->motivo_desactivacion;
                                                            $esPresenteOtra = str_contains($vinculacion->motivo_desactivacion ?? '', 'PRESENTE EN OTRA');
                                                        @endphp
                                                        @if ($esDesvinculado)
                                                            @if($esPresenteOtra)
                                                                <span class="inline-flex flex-col items-center gap-0.5">
                                                                    <span class="status-badge" style="background:#eab308;color:#fff;font-size:9px;padding:2px 6px;border-radius:4px;font-weight:900;letter-spacing:.04em;" title="Anulado por estar presente en otra">
                                                                        ⚠️ ANULADO
                                                                    </span>
                                                                    @if($vinculacion->fecha_desactivacion)
                                                                        <span class="text-[9px] text-yellow-700 font-bold">
                                                                            {{ \Carbon\Carbon::parse($vinculacion->fecha_desactivacion)->format('d-m-Y') }}
                                                                        </span>
                                                                    @endif
                                                                </span>
                                                            @else
                                                                <span class="inline-flex flex-col items-center gap-0.5">
                                                                    <span class="status-badge" style="background:#7c3aed;color:#fff;font-size:9px;padding:2px 6px;border-radius:4px;font-weight:900;letter-spacing:.04em;" title="Trabajador finiquitado/desvinculado — en reserva en el sistema">
                                                                        📦 EN RESERVA
                                                                    </span>
                                                                    @if ($vinculacion->fecha_finiquito)
                                                                        <span class="text-[9px] text-purple-600 font-bold">
                                                                            {{ \Carbon\Carbon::parse($vinculacion->fecha_finiquito)->format('d-m-Y') }}
                                                                        </span>
                                                                    @endif
                                                                </span>
                                                            @endif
                                                        @else
                                                            <span class="status-badge status-active" style="cursor: default;">
                                                                Activo
                                                            </span>
                                                        @endif
                                                    </td>

                                                    @if (!empty($documentosMaestros))
                                                        @foreach ($documentosMaestros as $doc)
                                                            <td
                                                                class="table-cell text-center border-r border-gray-400 dark:border-gray-600">
                                                                @php
                                                                    $estadosDeVinculacion =
                                                                        $estadosDocumentosPorVinculacion[
                                                                            $vinculacion->id
                                                                        ] ?? null;
                                                                    $estado = $estadosDeVinculacion
                                                                        ? $estadosDeVinculacion->get(
                                                                            $doc['nombre_documento_id'],
                                                                        )
                                                                        : null;
                                                                    $simbolo = '-';
                                                                    $title = 'No Cargado';
                                                                    $textColorClass = 'text-gray-500';

                                                                    if ($estado) {
                                                                        $textColorClass = match ($estado) {
                                                                            'Aprobado',
                                                                            'Aprobado-Modificado'
                                                                                => 'text-green-500',
                                                                            'Rechazado' => 'text-red-500',
                                                                            'Vencido' => 'text-orange-500',
                                                                            'Pendiente Validación' => 'text-blue-500',
                                                                            'En Revisión' => 'text-purple-500',
                                                                            default => 'text-gray-500',
                                                                        };
                                                                        $simbolo = match ($estado) {
                                                                            'Aprobado', 'Aprobado-Modificado' => 'A',
                                                                            'Rechazado' => 'R',
                                                                            'Vencido' => 'V',
                                                                            'Pendiente Validación' => 'P',
                                                                            'En Revisión' => 'ER',
                                                                            default => '-',
                                                                        };
                                                                        $title = $estado;
                                                                    } else {
                                                                        $simbolo = 'N/A';
                                                                        $title = 'No Aplica';
                                                                        $textColorClass = 'text-gray-400';
                                                                    }
                                                                @endphp
                                                                <span class="font-bold text-lg {{ $textColorClass }}"
                                                                    title="{{ $title }}">
                                                                    @if ($simbolo === 'A')
                                                                        <x-icons.check class="w-6 h-6 inline-block" />
                                                                    @else
                                                                        {{ $simbolo }}
                                                                    @endif
                                                                </span>
                                                            </td>
                                                        @endforeach
                                                    @endif

                                                    <td class="table-cell w-32 max-w-[8rem] truncate overflow-hidden text-ellipsis border-r border-gray-400 dark:border-gray-600"
                                                        title="{{ $vinculacion->dependencia->nombre_jerarquico ?? 'EN RESERVA' }}">
                                                        {{ $vinculacion->dependencia->nombre_jerarquico ?? 'EN RESERVA' }}
                                                    </td>
                                                    <td class="table-cell w-32 max-w-[8rem] truncate overflow-hidden text-ellipsis border-r border-gray-400 dark:border-gray-600"
                                                        title="{{ $vinculacion->unidadOrganizacionalMandante?->nombre_jerarquico ?? 'N/A' }}">
                                                        {{ $vinculacion->unidadOrganizacionalMandante?->nombre_jerarquico ?? 'N/A' }}
                                                    </td>
                                                    <td class="table-cell w-28 max-w-[7rem] truncate overflow-hidden text-ellipsis border-r border-gray-400 dark:border-gray-600 text-center"
                                                        title="{{ $vinculacion->numero_contrato ?? '-' }}">
                                                        {{ $vinculacion->numero_contrato ?? '-' }}
                                                    </td>

                                                    <td
                                                        class="table-cell text-center whitespace-nowrap sticky right-0 z-10 bg-white dark:bg-gray-800 group-even:bg-gray-100 dark:group-even:bg-gray-900/50 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/20">
                                                        @php
                                                            $mandanteContexto =
                                                                $vinculacion->unidadOrganizacionalMandante?->mandante;
                                                            $contextoCompleto =
                                                                ($mandanteContexto?->razon_social ?? 'N/A') .
                                                                ' - ' .
                                                                ($vinculacion->unidadOrganizacionalMandante
                                                                    ->nombre_jerarquico ??
                                                                    'N/A');
                                                        @endphp
                                                        @if(!$sinAcreditacion)
                                                            <button
                                                                wire:click="abrirModalCargaDocumentos({{ $trab->id }}, {{ $mandanteContexto?->id ?? 0 }}, {{ $vinculacion->unidad_organizacional_mandante_id ?? 0 }}, '{{ $contextoCompleto }}', {{ $vinculacion->id }})"
                                                                class="action-button-info" title="Gestionar Documentos">
                                                                <x-icons.document-text class="inline-block" />
                                                            </button>
                                                        @endif

                                                        <button
                                                                wire:click="abrirModalEditarTrabajador({{ $trab->id }})"
                                                                class="action-button-edit"
                                                                title="Editar Ficha Trabajador"><x-icons.edit
                                                                    class="inline-block" /></button>

                                                        @if($vinculacion->is_active)
                                                            <button
                                                                wire:click="abrirModalDesactivacion({{ $vinculacion->id }})"
                                                                class="bg-red-600 hover:bg-red-700 text-white font-bold text-[10px] px-2 py-1 rounded shadow-sm ms-1"
                                                                title="Gestionar Desvinculación">
                                                                🚫 Desvincular
                                                            </button>
                                                        @elseif(in_array($vinculacion->motivo_desactivacion, ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD']))
                                                            {{-- Finiquitado: mostrar botón Revertir (solo funciona si hay período abierto, validado en PHP) --}}
                                                            @if(!in_array($vinculacion->id, $vinculacionesBloqueadasReversion ?? []))
                                                                <button
                                                                    wire:click="revertirFiniquitoMaestro({{ $vinculacion->id }})"
                                                                    wire:confirm="¿Desea revertir el finiquito de este trabajador? Solo es posible si el período de nómina aún NO ha sido enviado."
                                                                    class="bg-amber-500 hover:bg-amber-600 text-white font-bold text-[10px] px-2 py-1 rounded shadow-sm ms-1"
                                                                    title="Revertir Finiquito (solo si el período no ha sido enviado)">
                                                                    ↩ Revertir
                                                                </button>
                                                            @endif
                                                        @elseif($vinculacion->dependencia_id)
                                                            <button
                                                                wire:click="reactivarVinculacion({{ $vinculacion->id }})"
                                                                wire:confirm="¿Desea reactivar esta vinculación y volverla a poner en estado ACTIVO?"
                                                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] px-2 py-1 rounded shadow-sm ms-1"
                                                                title="Reactivar Vinculación / Quitar de Reserva">
                                                                🔓 Reactivar
                                                            </button>
                                                        @endif

                                                        <button
                                                            wire:click="seleccionarTrabajadorParaVinculaciones({{ $trab->id }})"
                                                            class="action-button-link"
                                                            title="Ver Todas las Vinculaciones"><x-icons.link
                                                                class="inline-block" /></button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="{{ 9 + count($documentosMaestros) }}"
                                                        class="table-cell text-center">
                                                        No se encontraron trabajadores para los filtros seleccionados.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            @if ($vinculacionesPaginadas->hasPages())
                                <div class="mt-4">
                                    {{ $vinculacionesPaginadas->links(data: ['scrollTo' => false]) }}
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                            Por favor, seleccione una Principal y una pestaña para comenzar a gestionar los
                            trabajadores.
                        </div>
                    @endif
                @endif

                @if ($vistaActual === 'listado_vinculaciones' && $trabajadorSeleccionado)
                    <div class="mb-4 p-4 border dark:border-gray-700 rounded-md">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                            Trabajador: <span class="font-normal">{{ $trabajadorSeleccionado->nombre_completo }}
                                RUT/NUIP/DNI/CEDULA/CPF {{ $trabajadorSeleccionado->rut }}</span>
                        </h3>
                        @php
                        @endphp
                        <button wire:click="abrirModalEditarTrabajador({{ $trabajadorSeleccionado->id }})"
                            class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200 mt-1 inline-flex items-center">
                            <x-icons.edit class="w-4 h-4 mr-1 inline-block" /> Editar Ficha de este Trabajador
                        </button>
                    </div>

                    <div class="mb-4 flex flex-col sm:flex-row justify-between items-center">
                        <button wire:click="irAListadoTrabajadores" class="btn-secondary mb-2 sm:mb-0">
                            <x-icons.arrow-left class="w-5 h-5 mr-1 inline-block" /> Volver a Listado de Asignaciones
                        </button>
                        <button wire:click="abrirModalNuevaVinculacion" class="btn-primary">
                            <x-icons.plus class="w-5 h-5 mr-1 inline-block" /> Agregar Vinculación (UO + Lugar de
                            Trabajo)
                        </button>
                    </div>

                    <div class="overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="table-header">Lugar de Trabajo/Departamento</th>
                                    <th class="table-header">Vinculación (Principal / UO)</th>
                                    <th class="table-header">N° Contrato</th>
                                    <th class="table-header">Cargo</th>
                                    <th class="table-header">Condición Personal</th>
                                    <th class="table-header text-center">F. Ingreso Vinc.</th>
                                    <th class="table-header text-center">Estado</th>
                                    <th class="table-header text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($vinculacionesPaginadas ?? [] as $vinc)
                                    <tr wire:key="vinculacion-{{ $vinc->id }}" class="table-row-hover">
                                        <td class="table-cell font-medium text-blue-700 dark:text-blue-400">
                                            {{ $vinc->dependencia->nombre_jerarquico ?? 'NO ASIGNADO' }}</td>
                                        <td class="table-cell">
                                            {{ $vinc->unidadOrganizacionalMandante?->mandante?->razon_social ?? 'N/A Mandante' }}
                                            / <br>
                                            {{ $vinc->unidadOrganizacionalMandante?->nombre_jerarquico ?? 'N/A UO' }}
                                        </td>
                                        <td class="table-cell text-center font-semibold">
                                            {{ $vinc->numero_contrato ?? '-' }}</td>
                                        <td class="table-cell">
                                            {{ $vinc->cargoMandante?->nombre_cargo ?? 'N/A Cargo' }}</td>
                                        <td class="table-cell">
                                            {{ $vinc->tipoCondicionPersonal?->nombre ?? 'Sin Condición' }}</td>
                                        <td class="table-cell text-center">
                                            {{ $vinc->fecha_ingreso_vinculacion ? \Carbon\Carbon::parse($vinc->fecha_ingreso_vinculacion)->format('d-m-Y') : 'N/A' }}
                                        </td>
                                        <td class="table-cell text-center">
                                            @php
                                                $esDesvinculadoDetalle = !$vinc->is_active && $vinc->motivo_desactivacion;
                                                $esPresenteOtraDetalle = str_contains($vinc->motivo_desactivacion ?? '', 'PRESENTE EN OTRA');
                                            @endphp
                                            @if ($esDesvinculadoDetalle)
                                                @if($esPresenteOtraDetalle)
                                                    <span class="inline-flex flex-col items-center gap-0.5">
                                                        <span class="status-badge" style="background:#eab308;color:#fff;font-size:9px;padding:2px 6px;border-radius:4px;font-weight:900;" title="{{ $vinc->motivo_desactivacion }}">
                                                            ⚠️ ANULADO
                                                        </span>
                                                        @if($vinc->fecha_desactivacion)
                                                            <span class="text-[8px] text-yellow-600 font-bold">
                                                                {{ \Carbon\Carbon::parse($vinc->fecha_desactivacion)->format('d-m-Y') }}
                                                            </span>
                                                        @else
                                                            <span class="text-[8px] text-yellow-600 font-bold max-w-[100px] truncate" title="{{ $vinc->motivo_desactivacion }}">Duplicidad</span>
                                                        @endif
                                                    </span>
                                                @else
                                                    <span class="inline-flex flex-col items-center gap-0.5">
                                                        <span class="status-badge" style="background:#7c3aed;color:#fff;font-size:9px;padding:2px 6px;border-radius:4px;font-weight:900;"
                                                            title="Desvinculado — trabajador en reserva">📦 EN RESERVA</span>
                                                        @if ($vinc->fecha_finiquito)
                                                            <span class="text-[9px] text-purple-600 dark:text-purple-400 font-semibold">
                                                                F.Fin: {{ \Carbon\Carbon::parse($vinc->fecha_finiquito)->format('d-m-Y') }}
                                                            </span>
                                                        @elseif ($vinc->fecha_desactivacion)
                                                            <span class="text-[9px] text-gray-500 dark:text-gray-400">
                                                                Desact: {{ \Carbon\Carbon::parse($vinc->fecha_desactivacion)->format('d-m-Y') }}
                                                            </span>
                                                        @endif
                                                    </span>
                                                @endif
                                            @else
                                                <span wire:click="toggleActivoVinculacion({{ $vinc->id }})"
                                                    class="status-badge {{ $vinc->is_active ? 'status-active' : 'status-inactive' }}">
                                                    {{ $vinc->is_active ? 'Activo' : 'Inactivo' }}
                                                </span>
                                                @if (!$vinc->is_active && $vinc->fecha_desactivacion)
                                                    <span class="text-xs block text-gray-500 dark:text-gray-400">Desact:
                                                        {{ \Carbon\Carbon::parse($vinc->fecha_desactivacion)->format('d/m/Y') }}</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="table-cell text-center whitespace-nowrap">
                                            <button wire:click="abrirModalEditarVinculacion({{ $vinc->id }})"
                                                class="action-button-edit" title="Editar Vinculación"><x-icons.edit
                                                    class="inline-block" /></button>
                                            
                                            @if($vinc->is_active)
                                                <button type="button" wire:click="abrirModalDesactivacion({{ $vinc->id }})"
                                                    class="bg-red-600 hover:bg-red-700 text-white font-bold text-[10px] px-2 py-1 rounded shadow-sm ms-1" title="Gestionar Desvinculación">
                                                    🚫 Desvincular
                                                </button>
                                            @elseif(in_array($vinc->motivo_desactivacion, ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD']))
                                                {{-- Finiquitado: mostrar botón Revertir (solo funciona si hay período abierto, validado en PHP) --}}
                                                @if(!in_array($vinc->id, $vinculacionesBloqueadasReversion ?? []))
                                                    <button type="button"
                                                        wire:click="revertirFiniquitoMaestro({{ $vinc->id }})"
                                                        wire:confirm="¿Desea revertir el finiquito de este trabajador? Solo es posible si el período de nómina aún NO ha sido enviado."
                                                        class="bg-amber-500 hover:bg-amber-600 text-white font-bold text-[10px] px-2 py-1 rounded shadow-sm ms-1"
                                                        title="Revertir Finiquito (solo si el período no ha sido enviado)">
                                                        ↩ Revertir
                                                    </button>
                                                @endif
                                            @elseif($vinc->dependencia_id)
                                                <button type="button" wire:click="reactivarVinculacion({{ $vinc->id }})"
                                                    wire:confirm="¿Desea reactivar esta vinculación y volverla a poner en estado ACTIVO?"
                                                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] px-2 py-1 rounded shadow-sm ms-1" title="Reactivar Vinculación / Quitar de Reserva">
                                                    🔓 Reactivar
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="table-cell text-center">No se encontraron
                                            vinculaciones para este trabajador.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($vinculacionesPaginadas && $vinculacionesPaginadas->hasPages())
                        <div class="mt-4">
                            {{ $vinculacionesPaginadas->links(data: ['scrollTo' => false]) }}
                        </div>
                    @endif
                @endif

                @if ($showModalFichaTrabajador)
                    <div class="fixed z-[100] inset-0 overflow-y-auto" aria-labelledby="modal-title-trabajador"
                        role="dialog" aria-modal="true">
                        <div
                            class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75"
                                aria-hidden="true" wire:click="cerrarModalFichaTrabajador"></div>
                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen"
                                aria-hidden="true">​</span>
                            <div
                                class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                                <form wire:submit.prevent="guardarTrabajador" id="formFichaTrabajador">
                                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                        <div class="sm:flex sm:items-start w-full">
                                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 section-title"
                                                    id="modal-title-trabajador">
                                                    {{ $trabajadorId ? 'Editar Ficha del Trabajador' : 'Agregar Nuevo Trabajador' }}
                                                    @if ($trabajadorSeleccionado && $trabajadorId)
                                                        <span
                                                            class="text-sm font-normal text-gray-500 dark:text-gray-400">-
                                                            {{ $trabajadorSeleccionado->nombre_completo }}</span>
                                                    @endif
                                                    @if ($lugarDeTrabajoId && is_numeric($lugarDeTrabajoId))
                                                        <p
                                                            class="text-xs font-normal text-gray-500 dark:text-gray-400">
                                                            Lugar de Trabajo/Departamento Asignado:
                                                            {{ \App\Models\Dependencia::find($lugarDeTrabajoId)->nombre ?? 'N/A' }}
                                                        </p>
                                                    @endif
                                                </h3>
                                                <div class="mt-4 space-y-4">
                                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                                        <div
                                                            class="lg:col-span-4 section-title !mt-0 !border-none !pb-0">
                                                            Datos Personales</div>
                                                        <div>
                                                            <label for="nombres" class="label-form">Nombres <span
                                                                    class="text-red-500">*</span></label>
                                                            <input type="text" wire:model.lazy="nombres"
                                                                id="nombres" class="input-field w-full">
                                                            @error('nombres')
                                                                <span class="error-message">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <label for="apellido_paterno" class="label-form">Primer
                                                                Apellido <span class="text-red-500">*</span></label>
                                                            <input type="text" wire:model.lazy="apellido_paterno"
                                                                id="apellido_paterno" class="input-field w-full">
                                                            @error('apellido_paterno')
                                                                <span class="error-message">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <label for="apellido_materno" class="label-form">Segundo
                                                                Apellido </label>
                                                            <input type="text" wire:model.lazy="apellido_materno"
                                                                id="apellido_materno" class="input-field w-full">
                                                            @error('apellido_materno')
                                                                <span class="error-message">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        @if($mostrarAvisoHistorial && !$trabajadorId)
                                                            <div class="lg:col-span-4 mb-4 p-4 bg-amber-50 border-l-4 border-amber-400 rounded-r-lg shadow-sm animate-pulse">
                                                                <div class="flex items-center">
                                                                    <div class="flex-shrink-0">
                                                                        <svg class="h-6 w-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                        </svg>
                                                                    </div>
                                                                    <div class="ml-3">
                                                                        <p class="text-sm font-bold text-amber-800">
                                                                            ⚠️ TRABAJADOR ENCONTRADO EN EL HISTÓRICO
                                                                        </p>
                                                                        <p class="text-xs text-amber-700 mt-1">
                                                                            Este RUT ya pertenece a un trabajador previo de su empresa. Los datos personales han sido precargados automáticamente para facilitar la re-contratación.
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        <div>
                                                            <label for="rut_trabajador"
                                                                class="label-form">RUT/NUIP/DNI/CEDULA/CPF<span
                                                                    class="text-red-500">*</span></label>
                                                            <input type="text" wire:model.blur="rut_trabajador"
                                                                id="rut_trabajador" class="input-field w-full"
                                                                placeholder="Ej: 12345678-9">
                                                            @error('rut_trabajador')
                                                                <span class="error-message">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <label for="nacionalidad_id"
                                                                class="label-form">Nacionalidad <span
                                                                    class="text-red-500">*</span></label>
                                                            <select wire:model="nacionalidad_id" id="nacionalidad_id"
                                                                class="input-field w-full">
                                                                <option value="">Seleccione...</option>
                                                                @foreach ($nacionalidades as $nac)
                                                                    <option value="{{ $nac->id }}">
                                                                        {{ $nac->nombre }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error('nacionalidad_id')
                                                                <span class="error-message">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <label for="tipo_permanencia_id"
                                                                class="label-form">Tipo Permanencia <span
                                                                    class="text-red-500">*</span></label>
                                                            <select wire:model="tipo_permanencia_id" id="tipo_permanencia_id"
                                                                class="input-field w-full">
                                                                <option value="">Seleccione...</option>
                                                                @foreach ($tiposPermanencias as $perm)
                                                                    <option value="{{ $perm->id }}">
                                                                        {{ $perm->nombre }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error('tipo_permanencia_id')
                                                                <span class="error-message">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <label for="fecha_nacimiento" class="label-form">Fecha de
                                                                Nacimiento</label>
                                                            <input type="date" wire:model.lazy="fecha_nacimiento"
                                                                id="fecha_nacimiento" class="input-field w-full">
                                                            @error('fecha_nacimiento')
                                                                <span class="error-message">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <label for="sexo_id" class="label-form">Género</label>
                                                            <select wire:model="sexo_id" id="sexo_id"
                                                                class="input-field w-full">
                                                                <option value="">Seleccione...</option>
                                                                @foreach ($sexos as $sexo)
                                                                    <option value="{{ $sexo->id }}">
                                                                        {{ $sexo->nombre }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error('sexo_id')
                                                                <span class="error-message">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <label for="email_trabajador"
                                                                class="label-form">Email</label>
                                                            <input type="email" wire:model.lazy="email_trabajador"
                                                                id="email_trabajador" class="input-field w-full">
                                                            @error('email_trabajador')
                                                                <span class="error-message">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <label for="celular_trabajador"
                                                                class="label-form">Celular</label>
                                                            <input type="text" wire:model.lazy="celular_trabajador"
                                                                id="celular_trabajador" class="input-field w-full">
                                                            @error('celular_trabajador')
                                                                <span class="error-message">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <label for="estado_civil_id" class="label-form">Estado
                                                                Civil</label>
                                                            <select wire:model="estado_civil_id" id="estado_civil_id"
                                                                class="input-field w-full">
                                                                <option value="">Seleccione...</option>
                                                                @foreach ($estadosCiviles as $ec)
                                                                    <option value="{{ $ec->id }}">
                                                                        {{ $ec->nombre }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error('estado_civil_id')
                                                                <span class="error-message">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <label for="nivel_educacional_id" class="label-form">Nivel
                                                                Educacional</label>
                                                            <select wire:model="nivel_educacional_id"
                                                                id="nivel_educacional_id" class="input-field w-full">
                                                                <option value="">Seleccione...</option>
                                                                @foreach ($nivelesEducacionales as $ne)
                                                                    <option value="{{ $ne->id }}">
                                                                        {{ $ne->nombre }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error('nivel_educacional_id')
                                                                <span class="error-message">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <label for="etnia_id" class="label-form">Etnia / Pueblo
                                                                Originario</label>
                                                            <select wire:model="etnia_id" id="etnia_id"
                                                                class="input-field w-full">
                                                                <option value="">Seleccione...</option>
                                                                @foreach ($etnias as $etnia)
                                                                    <option value="{{ $etnia->id }}">
                                                                        {{ $etnia->nombre }}</option>
                                                                @endforeach
                                                                <option value="">No pertenece / No informa
                                                                </option>
                                                            </select>
                                                            @error('etnia_id')
                                                                <span class="error-message">{{ $message }}</span>
                                                            @enderror
                                                        </div>

                                                        @if (!$trabajadorId)
                                                            <div
                                                                class="lg:col-span-4 section-title !mt-0 !border-none !pb-0">
                                                                Datos de Vinculación Inicial</div>
                                                            <div>
                                                                <label for="v_unidad_organizacional_mandante_id_nuevo"
                                                                    class="label-form">Unidad Operativa <span
                                                                        class="text-red-500">*</span></label>
                                                                <select id="v_unidad_organizacional_mandante_id_nuevo"
                                                                    wire:model.live="v_unidad_organizacional_mandante_id"
                                                                    class="input-field w-full">
                                                                    <option value="">Seleccione una U.O....
                                                                    </option>
                                                                    @foreach ($unidadesOrganizacionalesDisponibles as $uo)
                                                                        <option value="{{ $uo->id }}">
                                                                            {{ $uo->nombre_unidad }}</option>
                                                                    @endforeach
                                                                </select>
                                                                @error('v_unidad_organizacional_mandante_id')
                                                                    <span class="error-message">{{ $message }}</span>
                                                                @enderror
                                                            </div>
                                                            <div>
                                                                <label for="v_dependencia_id_nuevo" class="label-form">Lugar de Trabajo <span class="text-red-500">*</span></label>
                                                                <select id="v_dependencia_id_nuevo" wire:model.live="v_dependencia_id" class="input-field w-full">
                                                                    <option value="">Seleccione Lugar...</option>
                                                                    @foreach ($dependenciasDisponibles as $dep)
                                                                        <option value="{{ $dep->id }}">{{ $dep->nombre_jerarquico }}</option>
                                                                    @endforeach
                                                                </select>
                                                                @error('v_dependencia_id')
                                                                    <span class="error-message">{{ $message }}</span>
                                                                @enderror
                                                            </div>
                                                            <div>
                                                                <label for="v_numero_contrato_nuevo" class="label-form">N° de Contrato (Opcional)</label>
                                                                <select id="v_numero_contrato_nuevo" wire:model.live="v_numero_contrato" class="input-field w-full">
                                                                    <option value="sin_contrato">-- Sin Contrato --</option>
                                                                    @foreach ($contratosDisponibles as $contrato)
                                                                        @if(is_array($contrato))
                                                                            <option value="{{ $contrato['numero_contrato'] }}">{{ $contrato['numero_contrato'] }}</option>
                                                                        @else
                                                                            <option value="{{ $contrato }}">{{ $contrato }}</option>
                                                                        @endif
                                                                    @endforeach
                                                                </select>
                                                                <p class="text-[10px] text-gray-500 mt-1">Se listan los contratos ligados a la UO/Lugar.</p>
                                                            </div>
                                                            <div>
                                                                <label for="cargo_mandante_id_nuevo"
                                                                    class="label-form">Cargo en Principal <span
                                                                        class="text-red-500">*</span></label>
                                                                <select id="cargo_mandante_id_nuevo"
                                                                    wire:model.defer="cargo_mandante_id_nuevo"
                                                                    class="input-field w-full">
                                                                    <option value="">Seleccione un cargo...
                                                                    </option>
                                                                    @foreach ($cargosMandanteDisponibles as $cargo)
                                                                        <option value="{{ $cargo->id }}">
                                                                            {{ $cargo->nombre_cargo }}</option>
                                                                    @endforeach
                                                                </select>
                                                                @error('cargo_mandante_id_nuevo')
                                                                    <span class="error-message">{{ $message }}</span>
                                                                @enderror
                                                            </div>
                                                            <div class="lg:col-span-4">
                                                                @include('livewire._partials._multi_select_condicion', [
                                                                    'opciones'      => $tiposCondicionPersonal,
                                                                    'seleccionados' => $v_condiciones_personales_ids,
                                                                    'wireKey'       => 'v_condiciones_personales_ids',
                                                                    'label'         => 'Condición(es) Personal',
                                                                    'placeholder'   => 'Buscar condición personal...',
                                                                ])
                                                                @error('v_condiciones_personales_ids')
                                                                    <span class="error-message">{{ $message }}</span>
                                                                @enderror
                                                            </div>
                                                        @endif

                                                        <div class="lg:col-span-4 section-title !border-none !pb-0">
                                                            Domicilio del Trabajador</div>
                                                        <div>
                                                            <label for="direccion_calle_trab"
                                                                class="label-form">Dirección </label>
                                                            <input type="text" wire:model.lazy="direccion_calle"
                                                                id="direccion_calle_trab" class="input-field w-full">
                                                            @error('direccion_calle')
                                                                <span class="error-message">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <label for="direccion_numero_trab"
                                                                class="label-form">Depto</label>
                                                            <input type="text" wire:model.lazy="direccion_numero"
                                                                id="direccion_numero_trab" class="input-field w-full">
                                                            @error('direccion_numero')
                                                                <span class="error-message">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <label for="direccion_departamento_trab"
                                                                class="label-form">Barrio</label>
                                                            <input type="text"
                                                                wire:model.lazy="direccion_departamento"
                                                                id="direccion_departamento_trab"
                                                                class="input-field w-full">
                                                            @error('direccion_departamento')
                                                                <span class="error-message">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        <div> </div>
                                                        <div>
                                                            <label for="trabajador_region_id_modal"
                                                                class="label-form">Departamento</label>
                                                            <select wire:model.live="trabajador_region_id"
                                                                id="trabajador_region_id_modal"
                                                                class="input-field w-full">
                                                                <option value="">Seleccione Departamento...
                                                                </option>
                                                                @foreach ($regiones as $region)
                                                                    <option value="{{ $region->id }}">
                                                                        {{ $region->nombre }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error('trabajador_region_id')
                                                                <span class="error-message">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        <div>
                                                            <label for="trabajador_comuna_id_modal"
                                                                class="label-form">Municipio</label>
                                                            <select wire:model="trabajador_comuna_id"
                                                                id="trabajador_comuna_id_modal"
                                                                class="input-field w-full"
                                                                @if (empty($comunasDisponiblesTrabajador)) disabled @endif>
                                                                <option value="">Seleccione Municipio...</option>
                                                                @foreach ($comunasDisponiblesTrabajador as $comuna)
                                                                    <option value="{{ $comuna->id }}">
                                                                        {{ $comuna->nombre }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error('trabajador_comuna_id')
                                                                <span class="error-message">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        <div class="lg:col-span-4 section-title !border-none !pb-0">
                                                            Otros Datos</div>
                                                        <div>
                                                            <label for="fecha_ingreso_empresa"
                                                                class="label-form">Fecha Contrato</label>
                                                            <input type="date"
                                                                wire:model.lazy="fecha_ingreso_empresa"
                                                                id="fecha_ingreso_empresa" class="input-field w-full">
                                                            @error('fecha_ingreso_empresa')
                                                                <span class="error-message">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                        <div class="lg:col-span-3">
                                                            <label for="trabajador_is_active_modal"
                                                                class="label-form flex items-center mt-2">
                                                                <input type="checkbox"
                                                                    wire:model="trabajador_is_active"
                                                                    id="trabajador_is_active_modal"
                                                                    class="form-checkbox rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-indigo-400">
                                                                <span
                                                                    class="ms-2 text-sm text-gray-600 dark:text-gray-400">Trabajador
                                                                    Activo</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 flex justify-between">
                                        <div>
                                            @if ($trabajadorId)
                                                <button type="button"
                                                    wire:click="eliminarTrabajador({{ $trabajadorId }})"
                                                    wire:confirm="ADVERTENCIA: ESTA ACCIÓN ES IRREVERSIBLE.\n\n¿Está seguro de eliminar PERMANENTEMENTE la ficha de este trabajador y TODAS sus vinculaciones asociadas?"
                                                    class="btn-danger">
                                                    Eliminación Permanente
                                                </button>
                                            @endif
                                        </div>
                                        <div class="flex">
                                            <button type="button" wire:click="cerrarModalFichaTrabajador"
                                                class="btn-secondary mr-2">
                                                Cancelar
                                            </button>
                                            <button type="submit" class="btn-primary">
                                                {{ $trabajadorId ? 'Guardar Cambios' : 'Crear Trabajador' }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($showModalNuevaVinculacion)
                    <div class="fixed z-[100] inset-0 overflow-y-auto" aria-labelledby="modal-title-vinculacion"
                        role="dialog" aria-modal="true">
                        <div
                            class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75"
                                aria-hidden="true" wire:click="cerrarModalVinculacion"></div>
                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen"
                                aria-hidden="true">​</span>
                            <div
                                class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full min-h-[70vh]">
                                <form wire:submit.prevent="guardarVinculacion" id="formVinculacion">
                                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 section-title"
                                            id="modal-title-vinculacion">
                                            {{ $vinculacionId ? 'Editar Vinculación' : 'Agregar Nueva Vinculación' }}
                                            @if ($trabajadorSeleccionado)
                                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">-
                                                    para {{ $trabajadorSeleccionado->nombre_completo }}</span>
                                            @endif
                                        </h3>
                                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">

                                            <div class="md:col-span-2">
                                                <label for="v_mandante_id" class="label-form">Principal <span
                                                        class="text-red-500">*</span></label>
                                                <select wire:model.live="v_mandante_id" id="v_mandante_id"
                                                    class="input-field w-full">
                                                    <option value="">Seleccione Principal...</option>
                                                    @foreach ($mandantesDisponibles as $mandante)
                                                        <option value="{{ $mandante->id }}">
                                                            {{ $mandante->razon_social }}</option>
                                                    @endforeach
                                                </select>
                                                @error('v_mandante_id')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div>
                                                <label for="v_unidad_organizacional_mandante_id"
                                                    class="label-form">Unidad Operativa <span
                                                        class="text-red-500">*</span></label>
                                                <select wire:model.live="v_unidad_organizacional_mandante_id"
                                                    id="v_unidad_organizacional_mandante_id"
                                                    class="input-field w-full"
                                                    @if (empty($unidadesOrganizacionalesDisponibles)) disabled @endif>
                                                    <option value="">Seleccione U.O....</option>
                                                    @foreach ($unidadesOrganizacionalesDisponibles as $uo)
                                                        <option value="{{ $uo->id }}">
                                                            {{ $uo->nombre_jerarquico }}</option>
                                                    @endforeach
                                                </select>
                                                @error('v_unidad_organizacional_mandante_id')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div>
                                                <label for="v_dependencia_id" class="label-form">Lugar de
                                                    Trabajo</label>
                                                <select wire:model.live="v_dependencia_id" id="v_dependencia_id"
                                                    class="input-field w-full"
                                                    @if (empty($dependenciasDisponibles)) disabled @endif>
                                                    <option value="">Seleccione Lugar...</option>
                                                    @foreach ($dependenciasDisponibles as $dep)
                                                        <option value="{{ $dep->id }}">
                                                            {{ $dep->nombre_jerarquico }}</option>
                                                    @endforeach
                                                </select>
                                                @error('v_dependencia_id')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div>
                                                <label for="v_numero_contrato" class="label-form">N° Contrato</label>
                                                <select wire:model.live="v_numero_contrato" id="v_numero_contrato"
                                                    class="input-field w-full"
                                                    @if (empty($contratosDisponibles)) disabled @endif>
                                                    <option value="">-- Sin Contrato --</option>
                                                    @foreach ($contratosDisponibles as $contrato)
                                                        <option value="{{ $contrato['numero_contrato'] }}">
                                                            {{ $contrato['numero_contrato'] }} ({{ $contrato['tipo_contrato_nombre'] }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if (empty($contratosDisponibles) && $v_unidad_organizacional_mandante_id)
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">No hay contratos configurados para esta UO</span>
                                                @endif
                                            </div>

                                            <div>
                                                <label for="v_tipo_contrato_id" class="label-form">Tipo de Contrato</label>
                                                <select wire:model="v_tipo_contrato_id" id="v_tipo_contrato_id"
                                                    class="input-field w-full bg-gray-100 dark:bg-gray-700" disabled>
                                                    <option value="">-- N/A --</option>
                                                    @foreach ($tiposContratoDisponibles as $tipo)
                                                        <option value="{{ $tipo->id }}">
                                                            {{ $tipo->nombre }}</option>
                                                    @endforeach
                                                </select>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Se carga automáticamente al seleccionar contrato</span>
                                            </div>

                                            <div class="md:col-span-2">
                                                <label for="v_cargo_mandante_id" class="label-form">Cargo <span
                                                        class="text-red-500">*</span></label>
                                                <select wire:model="v_cargo_mandante_id" id="v_cargo_mandante_id"
                                                    class="input-field w-full @if($vinculacionId) bg-gray-100 cursor-not-allowed dark:bg-gray-800 @endif"
                                                    @if (empty($cargosMandanteDisponibles) || $vinculacionId) disabled @endif>
                                                    <option value="">Seleccione Cargo...</option>
                                                    @foreach ($cargosMandanteDisponibles as $cargo)
                                                        <option value="{{ $cargo->id }}">
                                                            {{ $cargo->nombre_cargo }}</option>
                                                    @endforeach
                                                </select>
                                                @if($vinculacionId)
                                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 leading-tight"><i class="fas fa-lock mr-1 text-gray-400"></i> El cargo no puede ser modificado una vez creada la vinculación. Un Validador debe gestionar los cambios de cargo vía Anexo de Contrato.</p>
                                                @endif
                                                @error('v_cargo_mandante_id')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div>
                                                <label for="v_fecha_ingreso_vinculacion" class="label-form">Fecha
                                                    Ingreso a Vinculación <span class="text-red-500">*</span></label>
                                                <input type="date" wire:model.lazy="v_fecha_ingreso_vinculacion"
                                                    id="v_fecha_ingreso_vinculacion" class="input-field w-full">
                                                @error('v_fecha_ingreso_vinculacion')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="md:col-span-2">
                                                @include('livewire._partials._multi_select_condicion', [
                                                    'opciones'      => $tiposCondicionPersonal,
                                                    'seleccionados' => $v_condiciones_personales_ids,
                                                    'wireKey'       => 'v_condiciones_personales_ids',
                                                    'label'         => 'Condición(es) Personal',
                                                    'placeholder'   => 'Buscar condición personal...',
                                                ])
                                                @error('v_condiciones_personales_ids')
                                                    <span class="error-message">{{ $message }}</span>
                                                @enderror
                                            </div>


                                            <div class="md:col-span-2">
                                                <label for="v_is_active" class="label-form flex items-center mt-2">
                                                    <input type="checkbox" wire:model.live="v_is_active"
                                                        id="v_is_active"
                                                        class="form-checkbox rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-indigo-400">
                                                    <span
                                                        class="ms-2 text-sm text-gray-600 dark:text-gray-400">Vinculación
                                                        Activa</span>
                                                </label>
                                            </div>

                                            @if (!$v_is_active)
                                                <div>
                                                    <label for="v_fecha_desactivacion" class="label-form">Fecha
                                                        Desactivación <span class="text-red-500">*</span></label>
                                                    <input type="date" wire:model.lazy="v_fecha_desactivacion"
                                                        id="v_fecha_desactivacion" class="input-field w-full">
                                                    @error('v_fecha_desactivacion')
                                                        <span class="error-message">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="md:col-span-2">
                                                    <label for="v_motivo_desactivacion" class="label-form">Motivo
                                                        Desactivación <span class="text-red-500">*</span></label>
                                                    <textarea wire:model.lazy="v_motivo_desactivacion" id="v_motivo_desactivacion" rows="2"
                                                        class="input-field w-full"></textarea>
                                                    @error('v_motivo_desactivacion')
                                                        <span class="error-message">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div
                                        class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                        <button type="submit" class="btn-primary sm:ms-3">
                                            {{ $vinculacionId ? 'Guardar Cambios' : 'Crear Vinculación' }}
                                        </button>
                                        <button type="button" wire:click="cerrarModalVinculacion"
                                            class="btn-secondary">
                                            Cancelar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
                
                {{-- ================== MODAL DE DESACTIVACIÓN / FINIQUITO ================== --}}
                @if ($showModalDesactivacion)
                    <div class="fixed z-[100] inset-0 overflow-y-auto" aria-labelledby="modal-desactivacion-title" role="dialog" aria-modal="true">
                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75"
                                aria-hidden="true" wire:click="$set('showModalDesactivacion', false)"></div>
                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
                            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                <form wire:submit.prevent="procesarDesactivacion">
                                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 section-title"
                                            id="modal-desactivacion-title">
                                            Desvincular Trabajador / Modificar Estado
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                            Seleccione el estado con el que este trabajador quedará inactivo en el sistema para esta vinculación.
                                        </p>
                                        
                                        <div class="mt-4">
                                            <label for="desactivacionContext" class="label-form">Estado de Desvinculación <span class="text-red-500">*</span></label>
                                            <select wire:model.live="desactivacionContext" id="desactivacionContext" class="input-field w-full" required>
                                                <option value="FINIQUITADO">2. FINIQUITADO</option>
                                                <option value="CESACION_PRINCIPAL">3. CESACIÓN EN LA PRINCIPAL</option>
                                                <option value="RECONOCIMIENTO_ANTIGUEDAD">4. RECONOCIMIENTO ANTIGÜEDAD</option>
                                                <option value="PRESENTE_EN_OTRA_VINCULACION">5. PRESENTE EN OTRA VINCULACIÓN</option>
                                            </select>
                                        </div>

                                        @if($desactivacionContext === 'PRESENTE_EN_OTRA_VINCULACION')
                                            <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700/30 rounded-lg">
                                                <p class="text-xs text-yellow-800 dark:text-yellow-400 mb-2 font-semibold">
                                                    Esta opción anulará la vinculación actual por duplicidad y requerirá que asocie la vinculación correcta.
                                                </p>
                                                <label for="vinculacion_correcta_id" class="label-form text-yellow-900 dark:text-yellow-500">
                                                    Seleccione el Contrato Correcto <span class="text-red-500">*</span>
                                                </label>
                                                <select wire:model.defer="vinculacion_correcta_id" id="vinculacion_correcta_id" class="input-field w-full border-yellow-300 focus:border-yellow-500 focus:ring-yellow-500" required>
                                                    <option value="">Seleccione el contrato en el que debe estar...</option>
                                                    @foreach($trabajadorSeleccionado->vinculaciones()->where('id', '!=', $vinculacionADesactivar)->get() as $otraVinc)
                                                        <option value="{{ $otraVinc->id }}">
                                                            Contrato: {{ $otraVinc->numero_contrato ?? 'S/N' }} (Lugar: {{ $otraVinc->dependencia->nombre_jerarquico ?? 'N/A' }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('vinculacion_correcta_id') <span class="error-message">{{ $message }}</span> @enderror
                                            </div>
                                        @else
                                            <div class="mt-4">
                                                <label for="v_fecha_finiquito" class="label-form">Fecha Real (Finiquito/Cese) <span class="text-red-500">*</span></label>
                                                <input type="date" wire:model.defer="v_fecha_finiquito" id="v_fecha_finiquito" class="input-field w-full" required>
                                                @error('v_fecha_finiquito') <span class="error-message">{{ $message }}</span> @enderror
                                            </div>
                                        @endif
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                        <button type="submit" class="btn-primary sm:ms-3 flex items-center justify-center">
                                            Confirmar Desvinculación
                                        </button>
                                        <button type="button" wire:click="$set('showModalDesactivacion', false)" class="btn-secondary">
                                            Cancelar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
                
            </div>
        </div>
    </div>

    {{-- ================== MODAL DE EXCEPCIONES (ANULACIÓN MANUAL) ================== --}}
    @if($showAnulacionModal && $recursoSeleccionado)
        <div class="fixed z-50 inset-0 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" wire:click="cerrarModalAnulacion"></div>
                <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-2xl sm:w-full z-10">
                    <form wire:submit.prevent="guardarAnulacionAcceso">
                        <div class="px-4 pt-5 pb-4 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                @if($accionAnulacion === 'HABILITAR')
                                    Confirmar Habilitación Manual
                                @else
                                    Confirmar Restricción Manual
                                @endif
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Está a punto de anular manualmente el estado de acceso para el trabajador: 
                                    <strong class="font-semibold">{{ $recursoSeleccionado->nombre_completo ?? ($recursoSeleccionado->nombres . ' ' . $recursoSeleccionado->apellido_paterno) }}</strong>.
                                </p>
                            </div>
                            <div class="mt-4">
                                <label for="justificacion" class="label-form">Justificación (Obligatorio - mínimo 20 caracteres)</label>
                                <textarea id="justificacion" wire:model.defer="justificacion" rows="4" class="input-field w-full"></textarea>
                                @error('justificacion') <span class="error-message">{{ $message }}</span> @enderror
                            </div>
                            <div class="mt-4">
                                <label for="valido_hasta" class="label-form">
                                    @if($accionAnulacion === 'HABILITAR')
                                        Vencimiento de la Habilitación (Opcional)
                                    @else
                                        Vencimiento de la Restricción (Opcional)
                                    @endif
                                </label>
                                <input type="date" id="valido_hasta" wire:model.defer="valido_hasta" class="input-field w-full">
                                @error('valido_hasta') <span class="error-message">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="{{ $accionAnulacion === 'HABILITAR' ? 'btn-primary' : 'btn-danger' }} sm:ml-3">
                                Confirmar
                            </button>
                            <button type="button" wire:click="cerrarModalAnulacion" class="btn-secondary">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    {{-- ================== FIN MODAL DE EXCEPCIONES ================== --}}

    {{-- ================== MODAL DE ERROR FINIQUITADO (BLOQUEO) ================== --}}
    @if($showErrorFiniquitoModal)
        <div class="fixed z-[100] inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-red-900 bg-opacity-75 transition-opacity backdrop-blur-sm" wire:click="$set('showErrorFiniquitoModal', false)" aria-hidden="true"></div>

                <!-- Center modal trick -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full sm:p-8 animate-in zoom-in-95 duration-200 border-2 border-red-500">
                    <div>
                        <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-red-100 dark:bg-red-900/30">
                            <svg class="h-12 w-12 text-red-600 dark:text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-5 text-center sm:mt-6">
                            <h3 class="text-2xl leading-6 font-black text-red-700 dark:text-red-400 uppercase tracking-wide" id="modal-title">
                                ¡Operación Bloqueada!
                            </h3>
                            <div class="mt-4 bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                                <p class="text-base text-gray-800 dark:text-gray-200 font-medium">
                                    No puede editar ni reactivar una vinculación que se encuentra <strong class="text-red-600 uppercase font-black">Finiquitada o Cesada</strong> u originó un <strong class="text-red-600 uppercase font-black">Reconocimiento de Antigüedad</strong>.
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-3 text-left">
                                    <strong>¿Cómo solucionarlo?</strong><br>
                                    <span class="block mt-1">• <strong>Si fue un error:</strong> Debe ir al período donde lo remitió y revertir el <em>Estado</em> a "1. Activo" desde allí.</span>
                                    <span class="block mt-1">• <strong>Si es un nuevo contrato:</strong> Debe usar el botón azul <strong class="text-blue-600">"+ Agregar Vinculación"</strong> para iniciarlo desde cero sin alterar el historial pasado.</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 sm:mt-8 text-center">
                        <button type="button" wire:click="$set('showErrorFiniquitoModal', false)" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-3 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:text-lg">
                            Entendido
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    {{-- ================== FIN MODAL DE ERROR FINIQUITADO ================== --}}

</div>
