<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Mandante;
use App\Models\SolicitudVinculacion;
use Illuminate\Support\Facades\Log;

new class extends Component
{
    public $contratistaInfo = null;
    public ?string $ovalUrl = null;
    public bool $showNuevaSolicitudModal = false;
    public $mandantesParaSolicitar = [];
    public $mandanteIdParaSolicitud = null;

    public string $countryCode;
    public string $countryName;

    public array $rutasDeListadosUniversales = [
        'gestion.listados.hub',
        'gestion.mandantes',
        'gestion.unidades-organizacionales-mandante',
        'gestion.dependencias',
    ];

    public array $rutasDeGestionCriticidad = [
        'gestion.criticidad.general',
        'gestion.criticidad.excepciones',
    ];

    public array $rutasDeCargasMasivas = [
        'gestion.importar.contratistas',
        'gestion.importar.trabajadores',
        'gestion.importar.vehiculos',
        'gestion.importar.documentos',
        'gestion.importar.dotacion-anterior',
        'gestion.sincronizar.documentos',
    ];

    public array $rutasDeConfiguracion = [
        'gestion.listados.hub',
        'gestion.mandantes',
        'gestion.unidades-organizacionales-mandante',
        'gestion.dependencias',
        'gestion.reglas-documentales',
        'gestion.criticidad.general',
        'gestion.criticidad.excepciones',
        'gestion.asignacion-automatica',
        'gestion.importar.contratistas',
        'gestion.importar.trabajadores',
        'gestion.importar.vehiculos',
        'gestion.importar.documentos',
        'gestion.importar.dotacion-anterior',
        'gestion.popups',
    ];

    public array $rutasDeGestionContratistas = [
        'gestion.contratistas',
        'gestion.operaciones-globales',
        'gestion.solicitudes-vinculacion',
        'gestion.supervision-global',
        'gestion.supervision-detalle',
        'gestion.excepciones',
    ];

    public array $rutasDeGestionContratistasMandante = [
        'mandante.gestion-contratistas',
        'mandante.gestion-entidades',
    ];

    public bool $contratistaVerifica = false;

    public function mount(): void
    {
        if (Auth::check() && Auth::user()->hasRole('Contratista_Admin')) {
            $user = Auth::user();
            $user->load('contratista');
            $this->contratistaInfo = $user->contratista;

            // Determinar si este contratista tiene al menos una vinculación con verifica=true
            if ($this->contratistaInfo) {
                $this->contratistaVerifica = \App\Models\ContratistaUnidadOrganizacional::where('contratista_id', $this->contratistaInfo->id)
                    ->where('verifica', true)
                    ->exists();
            }

            if ($this->contratistaInfo && $this->contratistaInfo->tieneAccesoOval()) {
                $mandanteOval = $this->contratistaInfo->mandantesAprobados()
                    ->where('tiene_oval', true)
                    ->whereNotNull('oval_cod')
                    ->first();

                // Solo intentar conexión si OVAL está configurado en .env
                $ovalHost = config('database.connections.mysql_oval.host');
                $ovalPassword = config('database.connections.mysql_oval.password');

                if ($mandanteOval && $ovalHost && $ovalPassword) {
                    try {
                        $rut = $this->contratistaInfo->rut;
                        $idPrinc = $mandanteOval->oval_cod;

                        $ovalUser = DB::connection('mysql_oval')->selectOne(
                            "select id_u, random from usuarios where replace(rut,'.','') = ? and id_princ = ? and activar = 1",
                            [$rut, $idPrinc]
                        );

                        if ($ovalUser) {
                            $this->ovalUrl = "https://www.oval.cl/oval_30/bypass_30.aspx?elid={$ovalUser->id_u}&rand={$ovalUser->random}";
                        } else {
                            $this->ovalUrl = "error" . $rut . $idPrinc;
                        }
                    } catch (\Exception $e) {
                        Log::error('Error conectando a OVAL en navigation: ' . $e->getMessage());
                    }
                }
            }
        }

        $this->countryCode = strtolower(config('pais.code', 'cl'));
        $supportedCountries = config('pais.supported', []);
        $this->countryName = $supportedCountries[$this->countryCode] ?? 'País';
    }

    public function isConfiguracionActive(): bool
    {
        return in_array(Route::currentRouteName(), $this->rutasDeConfiguracion);
    }

    public function isGestionCriticidadActive(): bool
    {
        return in_array(Route::currentRouteName(), $this->rutasDeGestionCriticidad);
    }

    public function isListadosUniversalesActive(): bool
    {
        return in_array(Route::currentRouteName(), $this->rutasDeListadosUniversales);
    }

    public function isGestionContratistasActive(): bool
    {
        return in_array(Route::currentRouteName(), $this->rutasDeGestionContratistas);
    }

    public function isCargasMasivasActive(): bool
    {
        return in_array(Route::currentRouteName(), $this->rutasDeCargasMasivas);
    }

    public function isGestionContratistasMandanteActive(): bool
    {
        return in_array(Route::currentRouteName(), $this->rutasDeGestionContratistasMandante);
    }

    public function isContratistaPanelActive(): bool
    {
        return request()->routeIs('contratista.panel-operacion');
    }

    public function abrirModalNuevaSolicitud()
    {
        $contratista = Auth::user()->contratista;
        if (!$contratista) return;

        $mandantesConSolicitudIds = $contratista->solicitudesVinculacion()
            ->whereIn('estado', ['APROBADA', 'PENDIENTE', 'PENDIENTE_VINCULACION_CONTRATISTA'])
            ->pluck('mandante_id');

        $this->mandantesParaSolicitar = Mandante::where('is_active', true)
            ->whereNotIn('id', $mandantesConSolicitudIds)
            ->orderBy('razon_social')
            ->get();

        $this->mandanteIdParaSolicitud = null;
        $this->showNuevaSolicitudModal = true;
    }

    public function crearNuevaSolicitud()
    {
        $this->validate(['mandanteIdParaSolicitud' => 'required|exists:mandantes,id']);

        $contratista = Auth::user()->contratista;
        if (!$contratista) {
            session()->flash('error_nueva_solicitud', 'No se pudo identificar al contratista actual.');
            return;
        }

        try {
            SolicitudVinculacion::create([
                'contratista_id' => $contratista->id,
                'mandante_id' => $this->mandanteIdParaSolicitud,
                'tipo_solicitud' => 'CONTRATISTA',
                'estado' => 'PENDIENTE',
                'contratista_padre_id' => null,
            ]);

            session()->flash('status', 'Solicitud de incorporación enviada exitosamente.');
            $this->showNuevaSolicitudModal = false;
            $this->redirect(request()->header('Referer'));
        } catch (\Exception $e) {
            Log::error("Error al crear nueva solicitud para contratista {$contratista->id}: " . $e->getMessage());
            session()->flash('error_nueva_solicitud', 'Ocurrió un error inesperado al enviar la solicitud.');
        }
    }
}; ?>

<aside
    @click.away="sidebarOpen = false"
    @mouseenter="sidebarCollapsed = false"
    @mouseleave="sidebarCollapsed = true"
    class="fixed inset-y-0 left-0 z-40 flex flex-col bg-gradient-to-b from-indigo-700 via-indigo-800 to-blue-800 shadow-lg text-indigo-100 transition-all duration-300 ease-in-out -translate-x-full lg:translate-x-0"
    :class="{ 
        'w-64': !sidebarCollapsed, 
        'w-20': sidebarCollapsed,
        'translate-x-0': sidebarOpen
    }">

    <div class="flex items-center justify-center flex-shrink-0 h-16 px-4 border-b border-indigo-900/50">
        <div x-show="!sidebarCollapsed" x-cloak class="text-xl font-bold text-white transition-opacity duration-200 ease-in-out">
            <span>{{ config('app.name', 'Laravel') }}</span>
        </div>
        <div x-show="sidebarCollapsed" x-cloak class="text-xl font-bold text-white transition-opacity duration-200 ease-in-out">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
    </div>
    
    <!-- Toggle Modo Oscuro -->
    <div class="flex items-center justify-center px-4 py-2 border-b border-indigo-900/50">
        <button 
            @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)" 
            class="flex items-center justify-center w-full px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 transition-colors duration-200"
            :title="darkMode ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
        >
            <!-- Sol (modo claro activo) -->
            <svg x-show="darkMode" x-cloak class="w-5 h-5 text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
            </svg>
            <!-- Luna (modo oscuro activo) -->
            <svg x-show="!darkMode" x-cloak class="w-5 h-5 text-indigo-200" fill="currentColor" viewBox="0 0 20 20">
                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
            </svg>
            <span x-show="!sidebarCollapsed" x-cloak class="ml-2 text-sm text-white" x-text="darkMode ? 'Modo Claro' : 'Modo Oscuro'"></span>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto overflow-x-hidden p-2 space-y-1">

        @if(Auth::user() && Auth::user()->hasRole('ASEM_Validator') && !Auth::user()->hasRole('ASEM_Admin'))
        <x-sidebar.nav-link :href="route('asem.panel-validacion')" :active="request()->routeIs('asem.panel-validacion')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg></x-slot>
            VALIDACION DOCUMENTOS
        </x-sidebar.nav-link>
        @endif

        @if(Auth::user() && Auth::user()->hasRole('ASEM_Admin'))
        <x-sidebar.nav-link :href="route('gestion.gestion-general')" :active="request()->routeIs('gestion.gestion-general')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                </svg></x-slot>
            GESTION DOCUMENTOS
        </x-sidebar.nav-link>

        <x-sidebar.dropdown :active="$this->isConfiguracionActive()">
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg></x-slot>
            CONFIGURACION
            <x-slot name="content">
                <x-sidebar.nested-dropdown :active="$this->isListadosUniversalesActive()">
                    LISTADOS UNIVERSALES
                    <x-slot name="content">
                        <x-sidebar.dropdown-link :href="route('gestion.listados.hub')" wire:navigate>HUB PRINCIPAL</x-sidebar.dropdown-link>
                        <x-sidebar.dropdown-link :href="route('gestion.mandantes')" wire:navigate>PRINCIPALES</x-sidebar.dropdown-link>
                        <x-sidebar.dropdown-link :href="route('gestion.unidades-organizacionales-mandante')" wire:navigate>UNIDADES OPERATIVAS</x-sidebar.dropdown-link>
                        <x-sidebar.dropdown-link :href="route('gestion.dependencias')" wire:navigate>LUGARES DE TRABAJO/DEPARTAMENTOS</x-sidebar.dropdown-link>
                    </x-slot>
                </x-sidebar.nested-dropdown>
                <x-sidebar.dropdown-link :href="route('gestion.reglas-documentales')" wire:navigate>REGLAS DOCUMENTALES</x-sidebar.dropdown-link>
                <x-sidebar.nested-dropdown :active="$this->isGestionCriticidadActive()">
                    GESTIONAR %/ACCESO
                    <x-slot name="content">
                        <x-sidebar.dropdown-link :href="route('gestion.criticidad.general')" wire:navigate>%/ACCESO GENERAL</x-sidebar.dropdown-link>
                        {{-- <x-sidebar.dropdown-link :href="route('gestion.criticidad.excepciones')" wire:navigate>%/ACCESO EXCEPCIONES</x-sidebar.dropdown-link> --}}
                    </x-slot>
                </x-sidebar.nested-dropdown>
                <x-sidebar.dropdown-link :href="route('gestion.asignacion-automatica')" wire:navigate>ASIGNACION AUTOMATICA</x-sidebar.dropdown-link>
                <x-sidebar.nested-dropdown :active="$this->isCargasMasivasActive()">
                    CARGAS MASIVAS
                    <x-slot name="content">
                        <x-sidebar.dropdown-link :href="route('gestion.importar.contratistas')" wire:navigate>IMPORTAR CONTRATISTAS</x-sidebar.dropdown-link>
                        <x-sidebar.dropdown-link :href="route('gestion.importar.trabajadores')" wire:navigate>IMPORTAR TRABAJADORES</x-sidebar.dropdown-link>
                        <x-sidebar.dropdown-link :href="route('gestion.importar.vehiculos')" wire:navigate>IMPORTAR VEHÍCULOS</x-sidebar.dropdown-link>
                        <x-sidebar.dropdown-link :href="route('gestion.importar.documentos')" wire:navigate>IMPORTAR DOCUMENTOS</x-sidebar.dropdown-link>
                        <x-sidebar.dropdown-link :href="route('gestion.importar.verificaciones-historicas')" wire:navigate>IMPORTAR VERIFICACIONES HISTÓRICAS</x-sidebar.dropdown-link>
                        <x-sidebar.dropdown-link :href="route('gestion.importar.dotacion-anterior')" wire:navigate>IMPORTAR DOTACIÓN ANTERIOR</x-sidebar.dropdown-link>
                        {{-- ─── SINCRONIZACIÓN DESDE SISTEMA OBSOLETO ─── --}}
                        <x-sidebar.dropdown-link :href="route('gestion.sincronizar.documentos')" wire:navigate>
                            <span class="flex items-center gap-1">
                                <svg class="w-3 h-3 text-violet-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                SINCRONIZAR DOCS (LEGADO)
                            </span>
                        </x-sidebar.dropdown-link>
                    </x-slot>
                </x-sidebar.nested-dropdown>
                <x-sidebar.dropdown-link :href="route('gestion.popups')" wire:navigate>GESTIÓN DE POPUPS</x-sidebar.dropdown-link>
            </x-slot>
        </x-sidebar.dropdown>

        <x-sidebar.dropdown :active="$this->isGestionContratistasActive()">
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg></x-slot>
            GESTION CONTRATISTAS
            <x-slot name="content">
                <x-sidebar.dropdown-link :href="route('gestion.contratistas')" wire:navigate>LISTADO DE CONTRATISTAS</x-sidebar.dropdown-link>
                <x-sidebar.dropdown-link :href="route('gestion.operaciones-globales')" wire:navigate>GESTION DE ENTIDADES</x-sidebar.dropdown-link>
                <x-sidebar.dropdown-link :href="route('gestion.solicitudes-vinculacion')" wire:navigate>SOLICITUDES VINCULACION</x-sidebar.dropdown-link>
                <x-sidebar.dropdown-link :href="route('gestion.supervision-global')" wire:navigate>RESUMEN GENERAL</x-sidebar.dropdown-link>
                <x-sidebar.dropdown-link :href="route('gestion.excepciones')" wire:navigate>GESTIÓN DE EXCEPCIONES</x-sidebar.dropdown-link>
                <x-sidebar.dropdown-link :href="route('mandante.informar-contratistas')" wire:navigate>INFORMAR CONTRATISTAS</x-sidebar.dropdown-link>
            </x-slot>
        </x-sidebar.dropdown>



        <x-sidebar.nav-link :href="route('gestion.facturacion-mensual')" :active="request()->routeIs('gestion.facturacion-mensual')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0z"></path>
                </svg></x-slot>
            FACTURACION
        </x-sidebar.nav-link>
        <x-sidebar.nav-link :href="route('gestion.informes')" :active="request()->routeIs('gestion.informes')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg></x-slot>
            INFORMES
        </x-sidebar.nav-link>
        <x-sidebar.nav-link :href="route('gestion.usuarios')" :active="request()->routeIs('gestion.usuarios')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M15 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2"></path>
                </svg></x-slot>
            USUARIOS
        </x-sidebar.nav-link>
        <x-sidebar.nav-link :href="route('gestion.registro-actividad')" :active="request()->routeIs('gestion.registro-actividad')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg></x-slot>
            REGISTRO DE ACTIVIDAD
        </x-sidebar.nav-link>

        {{-- ─── IA ACREDITACIÓN ─── --}}
        <x-sidebar.nav-link :href="route('gestion.ia-acreditacion')" :active="request()->routeIs('gestion.ia-acreditacion')" wire:navigate>
            <x-slot name="icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </x-slot>
            IA ACREDITACIÓN
        </x-sidebar.nav-link>
        @endif

        @if(Auth::user() && Auth::user()->hasAnyRole(['OVAL_Admin', 'ASEM_Admin']))
        <x-sidebar.nav-link :href="route('oval.control-acceso')" :active="request()->routeIs('oval.control-acceso')" wire:navigate>
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </x-slot>
            {{ __('Control Acceso') }}
        </x-sidebar.nav-link>

        <x-sidebar.nav-link :href="route('oval.importador-historico')" :active="request()->routeIs('oval.importador-historico')" wire:navigate>
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                </svg>
            </x-slot>
            {{ __('Importar Históricos') }}
        </x-sidebar.nav-link>
        @endif

        @php
            $verifRoles = ['ASEM_Admin', 'OVAL_Admin', 'Verifica_Supervisor', 'Verifica_Emisor', 'Verifica_Analista', 'Verifica_Auditor', 'Operador_IA'];
        @endphp
        @if(Auth::user() && Auth::user()->hasAnyRole($verifRoles))
        <x-sidebar.dropdown :active="request()->routeIs(['gestion.verificacion', 'supervisor.*', 'analista.*', 'auditor.*', 'ia.*'])">
            <x-slot name="icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </x-slot>
            VERIFICACION LABORAL
            <x-slot name="content">
                @if(Auth::user()->hasAnyRole(['ASEM_Admin', 'OVAL_Admin']))
                    <x-sidebar.dropdown-link :href="route('gestion.verificacion')" wire:navigate>VERIF. CONFIG.</x-sidebar.dropdown-link>
                @endif

                @if(Auth::user()->hasAnyRole(['Verifica_Supervisor', 'Verifica_Emisor', 'ASEM_Admin']))
                    <x-sidebar.dropdown-link :href="route('supervisor.descarga-masiva')" wire:navigate>DESCARGA MASIVA</x-sidebar.dropdown-link>
                @endif

                @if(Auth::user()->hasAnyRole(['Verifica_Analista', 'ASEM_Admin']))
                    <x-sidebar.dropdown-link :href="route('analista.mis-asignaciones')" wire:navigate>ANALIZAR PERIODOS</x-sidebar.dropdown-link>
                @endif

                @if(Auth::user()->hasAnyRole(['Verifica_Auditor', 'ASEM_Admin']))
                    <x-sidebar.dropdown-link :href="route('auditor.mis-auditorias')" wire:navigate>AUDITAR PERIODOS</x-sidebar.dropdown-link>
                @endif

                @if(Auth::user()->hasAnyRole(['Verifica_Supervisor', 'Verifica_Emisor', 'ASEM_Admin']))
                    <x-sidebar.dropdown-link :href="route('supervisor.asignacion')" wire:navigate>SUPERVISOR VERIF.</x-sidebar.dropdown-link>
                    <x-sidebar.dropdown-link :href="route('supervisor.asignacion-complementaria')" wire:navigate>SUPERVISOR COMPL.</x-sidebar.dropdown-link>
                @endif

                @if(Auth::user()->hasAnyRole(['Verifica_Auditor', 'ASEM_Admin']))
                    <x-sidebar.dropdown-link :href="route('auditor.complementarios')" wire:navigate>REV. COMPLEMENTARIOS</x-sidebar.dropdown-link>
                @endif

                @if(Auth::user()->hasAnyRole(['Operador_IA', 'ASEM_Admin']))
                    <x-sidebar.dropdown-link :href="route('ia.extraccion')" wire:navigate>OPERADOR IA</x-sidebar.dropdown-link>
                @endif

                @if(Auth::user()->hasAnyRole(['ASEM_Admin', 'OVAL_Admin']))
                    {{-- Eliminado de aquí para ponerlo como link principal --}}
                @endif
            </x-slot>
        </x-sidebar.dropdown>
        @endif

        @if(Auth::user() && Auth::user()->hasAnyRole(['Contratista_Admin', 'Contratista_User', 'Subcontratista']))
        {{-- Ficha Empresa --}}
        @if(Auth::user()->hasAnyRole(['Contratista_Admin', 'Subcontratista']))
        <x-sidebar.nav-link :href="route('contratista.mi-ficha')" :active="request()->routeIs('contratista.mi-ficha')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg></x-slot>
            FICHA EMPRESA
        </x-sidebar.nav-link>
        @endif
        <x-sidebar.nav-link :href="route('contratista.panel-operacion')" :active="$this->isContratistaPanelActive()" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg></x-slot>
            GESTION DE ENTIDADES
        </x-sidebar.nav-link>
        {{-- REGLA #3: La pestaña "Verificación" moderna se oculta SIEMPRE para contratistas --}}
        {{-- Solo visible para roles que NO son contratista --}}

        {{-- REGLA #2: Verificación Legacy solo si la CUO del contratista tiene verifica=true --}}
        @if($contratistaVerifica)
        <x-sidebar.nav-link :href="route('contratista.verificacion-legacy')" :active="request()->routeIs('contratista.verificacion-legacy')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg></x-slot>
            VERIFICACIÓN LEGACY
        </x-sidebar.nav-link>
        @endif

        {{-- MOVIDO: Mis Vinculaciones disponible para Admin, User y Subcontratista --}}
        <x-sidebar.nav-link :href="route('contratista.mis-vinculaciones')" :active="request()->routeIs('contratista.mis-vinculaciones')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                </svg></x-slot>
            MIS VINCULACIONES
        </x-sidebar.nav-link>

        @if(Auth::user()->hasRole('Contratista_Admin'))
        <x-sidebar.nav-link :href="route('contratista.reporte-dotacion')" :active="request()->routeIs('contratista.reporte-dotacion')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg></x-slot>
            REPORTE DE DOTACION
        </x-sidebar.nav-link>



        @if(Auth::user()->contratista && Auth::user()->contratista->tieneAccesoOval() && $ovalUrl)
        <a href="{{ $ovalUrl }}" target="_blank" class="flex items-center p-2 text-base font-normal text-indigo-100 rounded-lg hover:bg-white/10 hover:text-white transition-colors duration-200 group">
            <span class="flex-shrink-0 w-6 h-6 text-indigo-200 group-hover:text-white transition-colors duration-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
            </span>
            <span class="ml-3 flex-1 whitespace-nowrap transition-opacity duration-200" x-show="!sidebarCollapsed" x-cloak>
                IR A OVAL
            </span>
        </a>
        @endif

        <x-sidebar.nav-link :href="route('contratista.gestion-usuarios')" :active="request()->routeIs('contratista.gestion-usuarios')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M15 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2"></path>
                </svg></x-slot>
            GESTIÓN DE USUARIOS
        </x-sidebar.nav-link>

        <x-sidebar.nav-link :href="route('contratista.mis-subcontratistas')" :active="request()->routeIs('contratista.mis-subcontratistas')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg></x-slot>
            MIS SUB-CONTRATISTAS
        </x-sidebar.nav-link>
        
        <x-sidebar.nav-link :href="route('contratista.solicitar-sub-contratista')" :active="request()->routeIs('contratista.solicitar-sub-contratista')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg></x-slot>
            SOLICITAR SUB-CONTRATISTA
        </x-sidebar.nav-link>
        @endif
        @endif

        @if(Auth::user() && Auth::user()->hasAnyRole(['Mandante_Admin', 'Mandante_Validator']))
        {{-- INICIO: ESTRUCTURA PARA Mandante_Admin y Mandante_Validator --}}
        
        @if(Auth::user()->hasRole('Mandante_Admin'))
        {{-- ⚡ DASHBOARD EJECUTIVO - Nivel Dios --}}
        <a href="{{ route('mandante.dashboard-ejecutivo') }}" wire:navigate
           class="flex items-center p-2 rounded-lg transition-colors duration-200 group relative
                  {{ request()->routeIs('mandante.dashboard-ejecutivo')
                      ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40'
                      : 'text-indigo-100 hover:bg-white/10 hover:text-white' }}">
            <span class="flex-shrink-0 w-6 h-6 text-amber-400 group-hover:text-amber-300 transition-colors duration-200 relative">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span class="absolute -top-1 -right-1 w-2 h-2 bg-amber-400 rounded-full animate-pulse"></span>
            </span>
            <span class="ml-3 flex-1 whitespace-nowrap font-black text-amber-300 text-[11px] uppercase tracking-wide transition-opacity duration-200" x-show="!sidebarCollapsed" x-cloak>
                ⚡ DASHBOARD EJECUTIVO
            </span>
        </a>

        <x-sidebar.nav-link :href="route('mandante.supervision')" :active="request()->routeIs('mandante.supervision')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg></x-slot>
            RESUMEN GENERAL
        </x-sidebar.nav-link>

        <x-sidebar.nav-link :href="route('mandante.gestion-contratistas')" :active="request()->routeIs('mandante.gestion-contratistas')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg></x-slot>
            LISTADO DE CONTRATISTAS
        </x-sidebar.nav-link>

        <x-sidebar.nav-link :href="route('mandante.solicitudes-vinculacion')" :active="request()->routeIs('mandante.solicitudes-vinculacion')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg></x-slot>
            SOLICITUDES VINCULACION
        </x-sidebar.nav-link>
        @endif

        @if(Auth::user()->hasRole('Mandante_Admin'))
        <x-sidebar.nav-link :href="route('mandante.gestion-general-documentos')" :active="request()->routeIs('mandante.gestion-general-documentos')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                </svg></x-slot>
            GESTION DOCUMENTOS
        </x-sidebar.nav-link>
        @else
        <x-sidebar.nav-link :href="route('mandante.panel-validacion')" :active="request()->routeIs('mandante.panel-validacion')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg></x-slot>
            GESTION DOCUMENTOS
        </x-sidebar.nav-link>
        @endif

        @if(Auth::user()->hasRole('Mandante_Admin'))
        <x-sidebar.nav-link :href="route('mandante.excepciones')" :active="request()->routeIs('mandante.excepciones')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg></x-slot>
            GESTIÓN DE EXCEPCIONES
        </x-sidebar.nav-link>

        <x-sidebar.nav-link :href="route('mandante.verificacion')" :active="request()->routeIs('mandante.verificacion')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg></x-slot>
            VERIFICACIÓN
        </x-sidebar.nav-link>

        <x-sidebar.nav-link :href="route('mandante.informar-contratistas')" :active="request()->routeIs('mandante.informar-contratistas')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg></x-slot>
            INFORMAR CONTRATISTAS
        </x-sidebar.nav-link>

        <x-sidebar.nav-link :href="route('mandante.gestion-usuarios')" :active="request()->routeIs('mandante.gestion-usuarios')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M15 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2"></path>
                </svg></x-slot>
            GESTIÓN DE USUARIOS
        </x-sidebar.nav-link>
        @endif
        {{-- FIN: ESTRUCTURA PARA Mandante_Admin y Mandante_Validator --}}
        @endif

        {{-- INICIO: MENÚ PARA Mandante_Ver (Solo lectura) --}}
        @if(Auth::user() && Auth::user()->hasRole('Mandante_Ver'))
        <x-sidebar.nav-link :href="route('mandante.supervision')" :active="request()->routeIs('mandante.supervision')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg></x-slot>
            RESUMEN GENERAL
        </x-sidebar.nav-link>

        <x-sidebar.nav-link :href="route('mandante.gestion-contratistas')" :active="request()->routeIs('mandante.gestion-contratistas')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg></x-slot>
            LISTADO DE CONTRATISTAS
        </x-sidebar.nav-link>

        <x-sidebar.nav-link :href="route('mandante.gestion-entidades')" :active="request()->routeIs('mandante.gestion-entidades')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg></x-slot>
            GESTIÓN DE ENTIDADES
        </x-sidebar.nav-link>

        <x-sidebar.nav-link :href="route('mandante.solicitudes-vinculacion')" :active="request()->routeIs('mandante.solicitudes-vinculacion')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg></x-slot>
            SOLICITUDES VINCULACION
        </x-sidebar.nav-link>

        <x-sidebar.nav-link :href="route('mandante.gestion-general-documentos')" :active="request()->routeIs('mandante.gestion-general-documentos')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                </svg></x-slot>
            GESTION DOCUMENTOS
        </x-sidebar.nav-link>

        <x-sidebar.nav-link :href="route('mandante.excepciones')" :active="request()->routeIs('mandante.excepciones')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg></x-slot>
            GESTIÓN DE EXCEPCIONES
        </x-sidebar.nav-link>

        <x-sidebar.nav-link :href="route('mandante.verificacion')" :active="request()->routeIs('mandante.verificacion')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg></x-slot>
            VERIFICACIÓN
        </x-sidebar.nav-link>

        <x-sidebar.nav-link :href="route('mandante.informar-contratistas')" :active="request()->routeIs('mandante.informar-contratistas')" wire:navigate>
            <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg></x-slot>
            INFORMAR CONTRATISTAS
        </x-sidebar.nav-link>
        {{-- FIN: MENÚ PARA Mandante_Ver --}}
        @endif

        @if(Auth::user() && Auth::user()->hasAnyRole(['Mandante_Admin', 'Mandante_Validator']))
        {{-- <x-sidebar.nav-link :href="route('mandante.panel-validacion')" :active="request()->routeIs('mandante.panel-validacion')" wire:navigate>
                <x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></x-slot>
                DOBLE VALIDACION
            </x-sidebar.nav-link> --}}
        @endif

    </nav>

    <div class="flex-shrink-0 border-t border-indigo-900/50 p-2">
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="w-full flex items-center p-2 rounded-lg hover:bg-white/10 transition-colors duration-200">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div x-show="!sidebarCollapsed" x-cloak class="ml-3 text-left flex-1 transition-opacity duration-200 ease-in-out">
                    <div class="font-medium text-white text-sm" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event->detail.name"></div>
                    @if(isset($contratistaInfo))
                    <div class="text-xs text-indigo-200 font-light -mt-1 truncate">{{ $contratistaInfo->razon_social }}</div>
                    @endif
                </div>
                <img x-show="!sidebarCollapsed" x-cloak src="https://flagcdn.com/w20/{{ $countryCode }}.png" srcset="https://flagcdn.com/w40/{{ $countryCode }}.png 2x" width="20" alt="{{ $countryName }}" class="ml-2 flex-shrink-0 transition-opacity duration-200 ease-in-out">
            </button>

            <div x-show="open && !sidebarCollapsed" x-cloak
                @click.away="open = false"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute left-0 bottom-full mb-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden"
                style="display: none;">
                <x-dropdown-link :href="route('profile')" wire:navigate>PERFIL</x-dropdown-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="!text-red-600 hover:!bg-red-50">
                        CERRAR SESION
                    </x-dropdown-link>
                </form>
            </div>
        </div>
    </div>

    @if ($showNuevaSolicitudModal)
    <div class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title-nueva-solicitud" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="$set('showNuevaSolicitudModal', false)"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form wire:submit.prevent="crearNuevaSolicitud">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title-nueva-solicitud">Solicitar Incorporación a Nueva Principal</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Seleccione La Principal al cual desea enviar una solicitud de vinculación. Esta solicitud será revisada y deberá ser aprobada para poder operar.</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6">
                            <label for="mandanteIdParaSolicitud" class="block text-sm font-medium text-gray-700">Mandantes Disponibles</label>
                            <select wire:model="mandanteIdParaSolicitud" id="mandanteIdParaSolicitud" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">-- Seleccione una Principal --</option>
                                @forelse ($mandantesParaSolicitar as $mandante)
                                <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                                @empty
                                <option value="" disabled>No hay nuevas Principales disponibles para solicitar.</option>
                                @endforelse
                            </select>
                            @error('mandanteIdParaSolicitud') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        @if (session()->has('error_nueva_solicitud'))
                        <div class="mt-4 text-sm text-red-600">{{ session('error_nueva_solicitud') }}</div>
                        @endif
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="btn-primary w-full sm:w-auto sm:ml-3" wire:loading.attr="disabled">Enviar Solicitud</button>
                        <button type="button" wire:click="$set('showNuevaSolicitudModal', false)" class="btn-secondary w-full mt-3 sm:mt-0 sm:w-auto">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</aside>