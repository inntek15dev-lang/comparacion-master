<?php

namespace App\Livewire\Contratista;

use App\Models\ContratistaUnidadOrganizacional;
use App\Models\CarpetaVerificacion;
use App\Models\CalendarioVerificacion;
use App\Models\DocumentoVerificacion;
use App\Models\RequisitoVerificacion;
use App\Models\ExclusionVerificacionPeriodo;
use App\Models\SolicitudComplementariaItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Carbon\Carbon;

class VerificacionLegacy extends Component
{
    use WithFileUploads;

    public $vinculaciones = [];
    public $vinculacion_seleccionada_id;
    public $periodos = [];
    public $filtro_id_registro = '';
    public $filtro_ia = '';
    public $estado_plazo = '';
    public $modal_confirmacion_visible = false;
    public $declaracion_aceptada = false;

    public $anio_seleccionado;
    public $mes_seleccionado;
    public $carpeta_actual;
    public $inicio_global = null;

    // --- NAVEGACIÓN LEGACY ---
    public $tab_principal = 'inicio';
    public $tab_secundario = 'informacion';
    public $mandante_dashboard_id;
    public $grid_solicitudes = []; // Para la grilla de 12 meses

    // --- COMPLEMENTARIOS ---
    public $grid_complementarios = [];
    public $mes_complementario_seleccionado = null;
    public $incidencias_mes_central = [
        'retenibles'    => [],
        'no_retenibles' => [],
        'observaciones' => []
    ];
    public $incidencias_seleccionadas = []; // Checkboxes
    public $bandeja_complementarios = []; // Panel lateral derecho (1 fila por solicitud consolidada)

    // --- MODAL PAQUETE (flujo consolidado) ---
    public $solicitud_activa_id = null;
    public $modal_paquete_abierto = false;
    public $items_paquete = [];
    public $paquete_folio_complementario = null;
    public $paquete_folio_certificado = null;
    public $paquete_lugar_contrato = null;
    public $archivos = [];
    public $documentos_ya_cargados = []; // Documentos previamente cargados en el borrador
    public $requisitos_agrupados_complementario = [];
    public $solo_lectura_modal = false;

    public function mount()
    {
        if (request()->query('tab') === 'solicitudes') {
            $this->cambiarTabPrincipal('solicitudes');
        }

        $this->cargarVinculaciones();

        if (count($this->vinculaciones) > 0) {
            $primeraVinculacion = $this->vinculaciones->first();
            $this->vinculacion_seleccionada_id = $primeraVinculacion->id;
            $this->mandante_dashboard_id = $primeraVinculacion->unidadOrganizacionalMandante->mandante_id;
            $this->anio_seleccionado = date('Y');
            $this->cargarCalendariosLegacy();
            $this->cargarGridSolicitudes();
        }
    }

    public function cambiarTabPrincipal($tab)
    {
        $this->tab_principal = $tab;
        if ($tab === 'inicio')
            $this->tab_secundario = 'informacion';
        if ($tab === 'solicitudes') {
            $this->tab_secundario = 'cumplimiento';
            $this->cargarGridSolicitudes();
        }
    }

    public function cambiarTabSecundario($tab)
    {
        $this->tab_secundario = $tab;
        if ($tab === 'calendario_recepcion') {
            $this->cargarCalendariosLegacy();
        }
        if ($tab === 'cumplimiento') {
            $this->cargarGridSolicitudes();
        }
        if ($tab === 'complementario') {
            $this->cargarGridComplementarios();
            $this->mes_complementario_seleccionado = null;
            $this->incidencias_mes_central = ['retenibles' => [], 'no_retenibles' => [], 'observaciones' => []];
            $this->cargarBandejaComplementarios();
        }
    }

    public function updatedVinculacionSeleccionadaId($id)
    {
        $vinc = $this->vinculaciones->firstWhere('id', $id);
        if ($vinc) {
            $this->mandante_dashboard_id = $vinc->unidadOrganizacionalMandante->mandante_id;
        }
        $this->cargarCalendariosLegacy();
        $this->cargarGridSolicitudes();
        if ($this->tab_secundario === 'complementario') {
            $this->cargarGridComplementarios();
            $this->mes_complementario_seleccionado = null;
            $this->incidencias_mes_central = ['retenibles' => [], 'no_retenibles' => [], 'observaciones' => []];
            $this->cargarBandejaComplementarios();
        }
    }

    public function updatedAnioSeleccionado()
    {
        $this->cargarCalendariosLegacy();
        $this->cargarGridSolicitudes();
        if ($this->tab_secundario === 'complementario') {
            $this->cargarGridComplementarios();
            $this->mes_complementario_seleccionado = null;
            $this->incidencias_mes_central = ['retenibles' => [], 'no_retenibles' => [], 'observaciones' => []];
            $this->cargarBandejaComplementarios();
        }
    }

    public function anteriorAnio()
    {
        $this->anio_seleccionado--;
        $this->updatedAnioSeleccionado();
    }

    public function siguienteAnio()
    {
        $this->anio_seleccionado++;
        $this->updatedAnioSeleccionado();
    }

    public function cargarCalendariosLegacy()
    {
        if (!$this->mandante_dashboard_id)
            return;

        $this->calendarios_legacy = CalendarioVerificacion::where('mandante_id', $this->mandante_dashboard_id)
            ->where('anio', $this->anio_seleccionado ?: date('Y'))
            ->orderBy('mes', 'asc')
            ->get();
    }

    public function cargarGridSolicitudes()
    {
        if (!$this->vinculacion_seleccionada_id)
            return;

        $vinculacion = $this->vinculaciones->firstWhere('id', $this->vinculacion_seleccionada_id);
        if (!$vinculacion)
            return;

        $anio = $this->anio_seleccionado ?: date('Y');
        $mandanteId = $vinculacion->unidadOrganizacional->mandante_id;
        $this->grid_solicitudes = [];

        // ================================================================
        // GUARDIA #4 removida del nivel global.
        // El check ahora es TEMPORAL (por mes) dentro del loop.
        // ================================================================

        for ($m = 1; $m <= 12; $m++) {
            $fechaMes = Carbon::create($anio, $m, 1)->startOfMonth();
            $inicioMesNomina = $fechaMes->copy();
            $finMesNomina = $fechaMes->copy()->endOfMonth();
            $fechaInicioGlobal = $this->inicio_global ? Carbon::parse($this->inicio_global) : null;

            // BUSQUEDA DE CALENDARIO (Usa el mes configurado para verificar esta nómina: m+1)
            $dbMesCal = $m + 1;
            $dbAnioCal = $anio;
            if ($dbMesCal > 12) {
                $dbMesCal = 1;
                $dbAnioCal = (int)$anio + 1;
            }

            $cal = CalendarioVerificacion::where('mandante_id', $mandanteId)
                ->where('anio', $dbAnioCal)
                ->where('mes', $dbMesCal)
                ->first();

            // 1. Verificar si está antes del inicio global
            $antesDeInicio = false;
            if ($fechaInicioGlobal && $inicioMesNomina < $fechaInicioGlobal->copy()->subMonth()) {
                $antesDeInicio = true;
            }

            // 2. Verificar Vigencia
            $fueraVigencia = false;
            if ($finMesNomina->lt($vinculacion->fecha_inicio_verifica)) $fueraVigencia = true;
            if ($vinculacion->fecha_fin_verifica && $inicioMesNomina->gt($vinculacion->fecha_fin_verifica)) $fueraVigencia = true;

            // 3. Verificar Exclusión Manual (No Informado por Mandante)
            $fechaCalendarioStr = $inicioMesNomina->copy()->addMonth()->startOfMonth()->toDateString();
            $excluido = \App\Models\ExclusionVerificacionPeriodo::where('contratista_unidad_organizacional_id', $vinculacion->id)
                ->where('periodo', $fechaCalendarioStr)
                ->exists();

            // 4. Verificar Calendario (Futuro o S/C)
            $isFutureOCerrado = false;
            if (!$cal) {
                // S/C = Futuro por defecto
                $isFutureOCerrado = true;
            } else {
                $hoyStr = Carbon::now()->toDateString();
                $aperturaStr = $cal->fecha_apertura ? $cal->fecha_apertura->format('Y-m-d') : '9999-99-99';
                if ($hoyStr < $aperturaStr) {
                    $isFutureOCerrado = true;
                }
            }

            if ($antesDeInicio || $fueraVigencia || $excluido) {
                $this->grid_solicitudes[$m] = [
                    'mes' => $m,
                    'nombre' => $this->getNombreMes($m),
                    'estado' => 'BLOQUEADO',
                    'subtitulo' => 'Periodo no habilitado',
                    'color' => 'bg-[#e9ecef]', 
                    'text_color' => 'text-gray-400 font-bold',
                    'carpeta_id' => null
                ];
            } elseif ($isFutureOCerrado) {
                $this->grid_solicitudes[$m] = [
                    'mes' => $m,
                    'nombre' => $this->getNombreMes($m),
                    'estado' => 'BLOQUEADO',
                    'subtitulo' => 'No se puede iniciar periodo',
                    'color' => 'bg-[#e9ecef]', 
                    'text_color' => 'text-gray-500 font-bold',
                    'carpeta_id' => null
                ];
            } else {
                // ── GUARDIA #4 TEMPORAL POR MES ──────────────────────────────────────
                // Buscar la carpeta (período ya iniciado)
                $carpeta = CarpetaVerificacion::where('contratista_unidad_organizacional_id', $vinculacion->id)
                    ->where('anio', $anio)
                    ->where('mes', $m)
                    ->first();

                $estado = 'PENDIENTE';
                $subtitulo = 'Periodo no iniciado';
                $color = 'bg-white';
                $text_color = 'text-gray-700 font-bold';
                $carpetaId = null;

                if ($carpeta) {
                    // Período ya existe — mostrar su estado real sin restricción
                    $carpetaId = $carpeta->id;
                    if ($carpeta->estado_revision === 'EMITIDO') {
                        $estado = 'EMITIDO';
                        $subtitulo = 'Certificado emitido';
                        $color = 'bg-[#003a5c]';
                        $text_color = 'text-white font-bold';
                    } elseif ($carpeta->estado === 'ENVIADO') {
                        $estado = 'ENVIADO';
                        $subtitulo = 'Periodo en revisión';
                        $color = 'bg-[#3b82f6]';
                        $text_color = 'text-white font-bold';
                    } elseif ($carpeta->estado === 'EN PROGRESO' || $carpeta->documentos()->exists()) {
                        $estado = 'EN_PROGRESO';
                        $subtitulo = 'Periodo iniciado';
                        $color = 'bg-[#8ed973]';
                        $text_color = 'text-[#003a5c] font-bold';
                    }
                } else {
                    // Período NO iniciado → verificar si había workers DURANTE ESTE MES
                    // Lógica idéntica a inicializarNominaVerificada() para consistencia.
                    $tieneTrabajadoresEnMes = \App\Models\TrabajadorVinculacion::whereHas('trabajador', function ($q) use ($vinculacion) {
                            $q->where('contratista_id', $vinculacion->contratista_id);
                        })
                        ->where('unidad_organizacional_mandante_id', $vinculacion->unidad_organizacional_mandante_id)
                        ->where('dependencia_id', $vinculacion->dependencia_id)
                        ->where('fecha_ingreso_vinculacion', '<=', $finMesNomina)
                        ->where(function ($sq) use ($inicioMesNomina) {
                            $sq->whereNull('fecha_desactivacion')
                               ->orWhere('fecha_desactivacion', '>=', $inicioMesNomina);
                        })
                        ->exists();

                    if (!$tieneTrabajadoresEnMes) {
                        $estado = 'BLOQUEADO';
                        $subtitulo = 'Sin trabajadores vinculados, no puede iniciar periodo';
                        $color = 'bg-amber-50';
                        $text_color = 'text-amber-700 font-bold';
                    }
                }

                $this->grid_solicitudes[$m] = [
                    'mes' => $m,
                    'nombre' => $this->getNombreMes($m),
                    'estado' => $estado,
                    'subtitulo' => $subtitulo,
                    'color' => $color,
                    'text_color' => $text_color,
                    'carpeta_id' => $carpetaId
                ];
            }
        }
    }

    public function cargarGridComplementarios()
    {
        if (!$this->vinculacion_seleccionada_id) return;

        $vinculacion = $this->vinculaciones->firstWhere('id', $this->vinculacion_seleccionada_id);
        if (!$vinculacion) return;

        $anio = $this->anio_seleccionado ?: date('Y');
        $this->grid_complementarios = [];

        for ($m = 1; $m <= 12; $m++) {
            $fechaMes = Carbon::create($anio, $m, 1)->startOfMonth();
            $inicioMesNomina = $fechaMes->copy();
            $finMesNomina = $fechaMes->copy()->endOfMonth();
            $fechaInicioGlobal = $this->inicio_global ? Carbon::parse($this->inicio_global) : null;

            $antesDeInicio = $fechaInicioGlobal && $inicioMesNomina < $fechaInicioGlobal->copy()->subMonth();
            $fueraVigencia = $finMesNomina->lt($vinculacion->fecha_inicio_verifica) ||
                             ($vinculacion->fecha_fin_verifica && $inicioMesNomina->gt($vinculacion->fecha_fin_verifica));
            
            $fechaCalendarioStr = $inicioMesNomina->copy()->addMonth()->startOfMonth()->toDateString();
            $excluido = ExclusionVerificacionPeriodo::where('contratista_unidad_organizacional_id', $vinculacion->id)
                ->where('periodo', $fechaCalendarioStr)
                ->exists();

            if ($antesDeInicio || $fueraVigencia || $excluido) {
                $this->grid_complementarios[$m] = [
                    'mes' => $m,
                    'nombre' => $this->getNombreMes($m),
                    'color' => 'bg-[#e9ecef]',
                    'text_color' => 'text-gray-400 font-bold',
                    'ret_cant' => '-', 'ret_cor' => '-', 'ret_pend' => '-',
                    'noret_cant' => '-', 'noret_cor' => '-', 'noret_pend' => '-',
                    'observ_cant' => '-', 'observ_cor' => '-', 'observ_pend' => '-',
                    'carpeta_id' => null
                ];
                continue;
            }

            $carpeta = CarpetaVerificacion::where('contratista_unidad_organizacional_id', $vinculacion->id)
                ->where('anio', $anio)
                ->where('mes', $m)
                ->with(['incidencias.solicitudComplementaria'])
                ->first();

            if (!$carpeta || $carpeta->estado_revision !== 'EMITIDO') {
                $this->grid_complementarios[$m] = [
                    'mes' => $m,
                    'nombre' => $this->getNombreMes($m),
                    'color' => 'bg-[#e9ecef]',
                    'text_color' => 'text-gray-400 font-bold',
                    'ret_cant' => '-', 'ret_cor' => '-', 'ret_pend' => '-',
                    'noret_cant' => '-', 'noret_cor' => '-', 'noret_pend' => '-',
                    'observ_cant' => '-', 'observ_cor' => '-', 'observ_pend' => '-',
                    'carpeta_id' => null
                ];
                continue;
            }

            $incidencias = $carpeta->incidencias;
            
            // Separación de listas
            $retenibles = $incidencias->filter(fn($i) => $i->tipo === 'contingencia' && $i->subtipo === 'retenible');
            $noRetenibles = $incidencias->filter(fn($i) => $i->tipo === 'contingencia' && $i->subtipo === 'no_retenible');
            $observaciones = $incidencias->filter(fn($i) => $i->tipo === 'observacion');

            $rCant = $retenibles->count();
            $rCor = $retenibles->filter(fn($i) => $i->estado_subsanacion === 'SUBSANADO')->count();
            $rPend = $rCant - $rCor;

            $nrCant = $noRetenibles->count();
            $nrCor = $noRetenibles->filter(fn($i) => $i->estado_subsanacion === 'SUBSANADO')->count();
            $nrPend = $nrCant - $nrCor;

            $oCant = $observaciones->count();
            $oCor = $observaciones->filter(fn($i) => $i->estado_subsanacion === 'SUBSANADO')->count();
            $oPend = $oCant - $oCor;

            $color = 'bg-[#28a745]'; // Verde (Por defecto si todo está o si no hay nada, pero aquí carpeta existe)
            $text_color = 'text-white font-bold';

            // Jerarquía de colores basada SOLO EN PENDIENTES
            if ($rPend > 0) {
                $color = 'bg-[#dc3545]'; // Rojo (Pendiente Retenible)
            } elseif ($nrPend > 0) {
                $color = 'bg-[#fd7e14]'; // Naranja (Pendiente No Retenible)
            } elseif ($oPend > 0) {
                $color = 'bg-[#ffc107]'; // Amarillo (Pendiente Observación)
                $text_color = 'text-gray-800 font-bold';
            }

            $this->grid_complementarios[$m] = [
                'mes' => $m,
                'nombre' => $this->getNombreMes($m),
                'color' => $color,
                'text_color' => $text_color,
                'ret_cant' => $rCant,     'ret_cor' => $rCor,     'ret_pend' => $rPend,
                'noret_cant' => $nrCant,  'noret_cor' => $nrCor,  'noret_pend' => $nrPend,
                'observ_cant' => $oCant,  'observ_cor' => $oCor,  'observ_pend' => $oPend,
                'carpeta_id' => $carpeta->id
            ];
        }
    }

    public function seleccionarMesComplementario($mes)
    {
        $this->mes_complementario_seleccionado = $mes;
        $this->incidencias_mes_central = ['retenibles' => [], 'no_retenibles' => [], 'observaciones' => []];
        $this->incidencias_seleccionadas = [];

        $gridData = $this->grid_complementarios[$mes] ?? null;
        if (!$gridData || !$gridData['carpeta_id']) return;

        $carpetaId = $gridData['carpeta_id'];

        // ── BLOQUEO INTELIGENTE POR CÓDIGO ────────────────────────────────────
        //
        // Reglas aprobadas:
        // 1. BLOQUEADO PERMANENTE: El código tiene estado_auditor = 'TOTAL' en
        //    cualquier SC ya cerrada (SOLUCIONADO). Está resuelto para siempre.
        //
        // 2. BLOQUEADO TEMPORALMENTE: El código está en una SC ACTIVA
        //    (estado = ENVIADO o EN_REVISION). No puede estar en dos SCs activas.
        //
        // 3. DISPONIBLE CON SALDO: El código tuvo estado_auditor = 'PARCIAL' o
        //    'RECHAZADO' en una SC ya cerrada. Puede incluirse en nueva SC.
        //    El monto disponible es: monto_original - monto_solucionado (para PARCIAL)
        //    o monto_original completo (para RECHAZADO).
        //
        // 4. DISPONIBLE LIBRE: Código que nunca ha sido incluido en ninguna SC.
        // ─────────────────────────────────────────────────────────────────────

        // Cargar todos los items históricos de SCs de esta carpeta en memoria
        $itemsHistorico = \App\Models\SolicitudComplementariaItem::whereHas('solicitud', function($q) use ($carpetaId) {
                $q->where('carpeta_verificacion_id', $carpetaId);
            })
            ->with('solicitud')
            ->get()
            ->groupBy('carpeta_trabajador_contingencia_id'); // [contingencia_id => Collection<items>]

        // IDs de códigos en SC ACTIVA (bloqueados temporalmente)
        $codigosEnScActiva = \App\Models\SolicitudComplementariaItem::whereHas('solicitud', function($q) use ($carpetaId) {
                $q->where('carpeta_verificacion_id', $carpetaId)
                  ->whereIn('estado', ['CREADO', 'ENVIADO', 'EN_REVISION']);
            })
            ->pluck('carpeta_trabajador_contingencia_id')
            ->toArray();

        // IDs de códigos con TOTAL en SC cerrada (bloqueados permanentemente)
        $codigosResueltosTotales = \App\Models\SolicitudComplementariaItem::where('estado_auditor', 'TOTAL')
            ->whereHas('solicitud', function($q) use ($carpetaId) {
                $q->where('carpeta_verificacion_id', $carpetaId)
                  ->whereIn('estado', ['SOLUCIONADO', 'RECHAZADO', 'EMITIDO']);
            })
            ->pluck('carpeta_trabajador_contingencia_id')
            ->toArray();

        $carpeta = CarpetaVerificacion::with(['incidencias.carpetaTrabajador.vinculacion.trabajador'])->find($carpetaId);
        if (!$carpeta) return;

        foreach ($carpeta->incidencias as $incidencia) {
            $contingenciaId = $incidencia->id;
            $trabajadorReal = $incidencia->carpetaTrabajador->vinculacion->trabajador ?? null;

            // — Determinar estado de bloqueo —
            $bloqueadoPermanente = in_array($contingenciaId, $codigosResueltosTotales);
            $bloqueadoActivo     = in_array($contingenciaId, $codigosEnScActiva);

            // — Historial para códigos disponibles con saldo —
            $historialItems = $itemsHistorico[$contingenciaId] ?? collect();
            $ultimoItemCerrado = $historialItems
                ->filter(fn($i) => in_array($i->solicitud->estado ?? '', ['SOLUCIONADO', 'RECHAZADO', 'EMITIDO']))
                ->sortByDesc('id')
                ->first();

            // Calcular monto disponible
            $montoOriginal    = $incidencia->monto ?? 0;
            $montoSolucionado = 0;
            $historialLabel   = null; // etiqueta para mostrar en UI

            if ($ultimoItemCerrado) {
                $estadoAnterior = $ultimoItemCerrado->estado_auditor ?? null;
                if ($estadoAnterior === 'PARCIAL') {
                    $montoSolucionado = $ultimoItemCerrado->monto_solucionado ?? 0;
                    $montoOriginal    = $montoOriginal - $montoSolucionado; // saldo por pagar
                    $historialLabel   = "SOLUCIÓN PARCIAL";
                } elseif ($estadoAnterior === 'RECHAZADO') {
                    $historialLabel   = "NO SOLUCIONADO";
                }
            }

            $item = [
                'id'                  => $incidencia->id,
                'codigo'              => $incidencia->codigo,
                'monto'               => $montoOriginal,           // Monto que aplica en esta nueva SC
                'monto_original'      => $incidencia->monto ?? 0,  // Siempre el original del certificado
                'monto_solucionado'   => $montoSolucionado,
                'trabajador_rut'      => $trabajadorReal?->rut ?? 'N/A',
                'trabajador_nombre'   => $trabajadorReal
                    ? trim($trabajadorReal->nombres . ' ' . $trabajadorReal->apellido_paterno . ' ' . $trabajadorReal->apellido_materno)
                    : 'Empresa Principal',
                'causal'              => $incidencia->causal,
                'historial_label'     => $historialLabel,          // Info de SC anterior (PARCIAL/RECHAZADO)
                'bloqueado_permanente'=> $bloqueadoPermanente,     // TOTAL resuelto → nunca más
                'bloqueado_activo'    => $bloqueadoActivo,         // En SC activa → temporal
                'en_solicitud'        => $bloqueadoActivo,         // compatibilidad UI
                'enviado'             => $bloqueadoActivo || $bloqueadoPermanente,
                'subsanado'           => $incidencia->estado_subsanacion === 'SUBSANADO' || $bloqueadoPermanente,
            ];

            if ($incidencia->tipo === 'contingencia' && $incidencia->subtipo === 'retenible') {
                $this->incidencias_mes_central['retenibles'][] = $item;
            } elseif ($incidencia->tipo === 'contingencia' && $incidencia->subtipo === 'no_retenible') {
                $this->incidencias_mes_central['no_retenibles'][] = $item;
            } elseif ($incidencia->tipo === 'observacion') {
                $this->incidencias_mes_central['observaciones'][] = $item;
            }
        }
    }

    /**
     * Consolida los códigos seleccionados en UNA SOLA SolicitudComplementaria
     * para la carpeta/certificado del mes activo.
     * Si ya existe una solicitud en CREADO/RECHAZADO, agrega los nuevos ítems.
     */
    public function consolidarEnSolicitud()
    {
        if (empty($this->incidencias_seleccionadas) || !$this->mes_complementario_seleccionado) return;

        $gridData = $this->grid_complementarios[$this->mes_complementario_seleccionado] ?? null;
        if (!$gridData || !$gridData['carpeta_id']) return;

        $carpetaId = $gridData['carpeta_id'];

        // Buscar una solicitud abierta (borrador o devuelta) donde podamos meter este ítem
        $solicitud = \App\Models\SolicitudComplementaria::where('carpeta_verificacion_id', $carpetaId)
            ->where('contratista_unidad_organizacional_id', $this->vinculacion_seleccionada_id)
            ->whereIn('estado', ['CREADO', 'RECHAZADO'])
            ->first();

        // Si no hay ninguna abierta, creamos una nueva, ya que el sistema soporta N solicitudes por certificado
        if (!$solicitud) {
            $solicitud = \App\Models\SolicitudComplementaria::create([
                'carpeta_verificacion_id'              => $carpetaId,
                'contratista_unidad_organizacional_id' => $this->vinculacion_seleccionada_id,
                'estado'                               => 'CREADO',
            ]);
        }

        // Agregar ítems sin duplicar
        foreach ($this->incidencias_seleccionadas as $incId) {
            \App\Models\SolicitudComplementariaItem::firstOrCreate([
                'solicitud_complementaria_id'        => $solicitud->id,
                'carpeta_trabajador_contingencia_id' => $incId,
            ]);
        }

        $this->incidencias_seleccionadas = [];
        $this->cargarBandejaComplementarios();
        $this->seleccionarMesComplementario($this->mes_complementario_seleccionado);
        $this->cargarGridComplementarios();

        $this->dispatch('notificar', [
            'mensaje' => 'Códigos agregados al complementario. Ahora cargue los documentos desde la bandeja.',
            'tipo'    => 'success',
        ]);
    }

    public function cerrarCentralComplementario()
    {
        $this->mes_complementario_seleccionado = null;
        $this->incidencias_mes_central = ['retenibles' => [], 'no_retenibles' => [], 'observaciones' => []];
        $this->incidencias_seleccionadas = [];
    }

    /**
     * Carga la bandeja de solicitudes consolidadas del contratista.
     * Cada fila = 1 SolicitudComplementaria (con N códigos adentro).
     * Muestra distinción visual: sin_docs / borrador / enviado / rechazado.
     */
    public function cargarBandejaComplementarios()
    {
        if (!$this->vinculacion_seleccionada_id) {
            $this->bandeja_complementarios = [];
            return;
        }

        $this->bandeja_complementarios = \App\Models\SolicitudComplementaria::with([
            'items.contingencia', 
            'documentos', 
            'carpeta', 
            'vinculacion.dependencia'
        ])
            ->where('contratista_unidad_organizacional_id', $this->vinculacion_seleccionada_id)
            ->whereNotNull('carpeta_verificacion_id')  // Solo nuevo flujo consolidado
            ->whereIn('estado', ['CREADO', 'ENVIADO', 'EN_REVISION', 'RECHAZADO'])
            ->whereHas('carpeta', function ($q) {
                $q->where('anio', $this->anio_seleccionado);
            })
            ->get()
            ->map(function ($solicitud) {
                $nDocs    = $solicitud->documentos->count();
                $nCodigos = $solicitud->items->count();
                $estado   = $solicitud->estado;

                // Estado visual para el panel
                if ($estado === 'CREADO' && $nDocs === 0) {
                    $visual = 'sin_docs';
                } elseif ($estado === 'CREADO' && $nDocs > 0) {
                    $visual = 'borrador';
                } elseif (in_array($estado, ['ENVIADO', 'EN_REVISION'])) {
                    $visual = 'enviado';
                } elseif ($estado === 'RECHAZADO') {
                    $visual = 'rechazado';
                } else {
                    $visual = 'otro';
                }

                return [
                    'solicitud_id'     => $solicitud->id,
                    'mes_nombre'       => $this->getNombreMes($solicitud->carpeta->mes ?? 1),
                    'anio'             => $solicitud->carpeta->anio ?? '',
                    'n_codigos'        => $nCodigos,
                    'n_documentos'     => $nDocs,
                    'estado_solicitud' => $estado,
                    'visual'           => $visual,
                    'observaciones'    => $solicitud->observaciones_auditor,
                    'folio'            => $solicitud->carpeta->folio ?? 'S/F',
                    'folio_sc'         => $solicitud->folio ?? 'S/F',
                    'lugar'            => $solicitud->vinculacion->dependencia->nombre ?? 'Sin Datos',
                    'contrato'         => $solicitud->vinculacion->numero_contrato ?? 'Sin Datos',
                    'detalles_codigos' => $solicitud->items->map(function($item) {
                         $c = $item->contingencia;
                         return [
                             'codigo'  => $c->codigo ?? 'S/C',
                             'causal'  => $c->causal ?? '',
                             'monto_original'=> $c->monto ?? 0,
                             'monto'   => $c->monto ?? 0,
                             'tipo'    => $c->tipo ?? '',
                             'subtipo' => $c->subtipo ?? '',
                         ];
                    })->toArray(),
                ];
            })
            ->toArray();
    }

    // ---------------------------------------------------------------
    // MODAL PAQUETE — Flujo Consolidado (1 solicitud → N códigos)
    // ---------------------------------------------------------------

    /**
     * Abre el modal de paquete para una SolicitudComplementaria consolidada.
     * Precarga los ítems y los requisitos de documentos.
     */
    public function abrirModalPaquete($solicitudId)
    {
        $solicitud = \App\Models\SolicitudComplementaria::with([
            'items.contingencia.carpetaTrabajador.vinculacion.trabajador',
            'documentos',
            'carpeta',
            'vinculacion.dependencia'
        ])->find($solicitudId);

        if (!$solicitud) return;

        // Detectar si el modal debe ser de solo lectura (según el estado de la solicitud)
        $this->solo_lectura_modal = in_array($solicitud->estado, ['ENVIADO', 'EN_REVISION', 'SOLUCIONADO']);

        $this->solicitud_activa_id = $solicitudId;
        
        // Asignar Folios e Información adicional para la vista (Modal Header)
        $this->paquete_folio_complementario = $solicitud->folio ?? 'SC-' . str_pad($solicitud->id, 4, '0', STR_PAD_LEFT);
        $this->paquete_folio_certificado    = $solicitud->carpeta->folio ?? 'S/F';
        $this->paquete_lugar_contrato       = ($solicitud->vinculacion->dependencia->nombre ?? 'Sin Lugar') . ' CT: ' . ($solicitud->vinculacion->numero_contrato ?? 'S/C');

        $this->items_paquete = $solicitud->items->map(function ($item) {
            $contingencia   = $item->contingencia;
            $trabajadorReal = $contingencia?->carpetaTrabajador?->vinculacion?->trabajador;
            return [
                'id'                => $contingencia?->id,
                'codigo'            => $contingencia?->codigo,
                'tipo'              => $contingencia?->tipo,
                'subtipo'           => $contingencia?->subtipo,
                'causal'            => $contingencia?->causal,
                'monto_original'    => $contingencia?->monto,
                'monto'             => $contingencia?->monto,
                'trabajador_rut'    => $trabajadorReal?->rut ?? 'N/A',
                'trabajador_nombre' => $trabajadorReal
                    ? trim($trabajadorReal->nombres . ' ' . $trabajadorReal->apellido_paterno)
                    : 'Empresa Principal',
            ];
        })->toArray();

        // Cargar los documentos que ya estén persistidos (estado BORRADOR)
        $this->documentos_ya_cargados = [];
        foreach ($solicitud->documentos as $doc) {
            $this->documentos_ya_cargados[$doc->requisito_verificacion_id][] = [
                'id'             => $doc->id,
                'nombre_archivo' => $doc->nombre_original,
                'ruta'           => $doc->path,
            ];
        }

        $this->cargarRequisitosComplementario();
        $this->archivos = [];
        $this->modal_paquete_abierto = true;
    }

    private function cargarRequisitosComplementario()
    {
        if (!$this->mandante_dashboard_id) return;

        $this->requisitos_agrupados_complementario = RequisitoVerificacion::with('clasificacion')
            ->where('mandante_id', $this->mandante_dashboard_id)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn($r) => $r->clasificacion->orden ?? 999)
            ->groupBy(fn($r) => $r->clasificacion->nombre ?? 'OTROS')
            ->toArray();
    }

    public function cerrarModalPaquete()
    {
        $this->reset([
            'solicitud_activa_id',
            'items_paquete',
            'modal_paquete_abierto',
            'archivos',
            'documentos_ya_cargados',
            'requisitos_agrupados_complementario',
            'solo_lectura_modal',
            'modal_confirmacion_visible',
            'declaracion_aceptada',
        ]);
    }

    /**
     * Elimina un documento que ya estaba cargado en el borrador.
     */
    public function eliminarDocumentoPaquete($documentoId)
    {
        $doc = \App\Models\DocumentoSolicitudComplementaria::find($documentoId);
        if ($doc && $doc->solicitud_complementaria_id == $this->solicitud_activa_id) {
            if ($doc->is_encrypted ?? false) {
                app(\App\Services\EncryptionService::class)->deleteEncrypted($doc->path);
            } else {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($doc->path);
            }
            $doc->delete();
            
            // Recargar para refrescar la interfaz
            $this->abrirModalPaquete($this->solicitud_activa_id);
            $this->cargarBandejaComplementarios(); // actualiza cuenta de docs en la bandeja
            
            $this->dispatch('notificar', [
                'mensaje' => 'Documento eliminado del borrador.',
                'tipo'    => 'info',
            ]);
        }
    }

    /**
     * Guarda los documentos cargados SIN enviar la solicitud.
     * El contratista puede retomar mañana y añadir más documentos.
     */
    public function guardarBorradorComplementario()
    {
        $solicitud = \App\Models\SolicitudComplementaria::find($this->solicitud_activa_id);
        if (!$solicitud) return;

        if (!empty($this->archivos)) {
            $this->validate(['archivos.*.*' => 'required|file|mimes:pdf|max:20480']);
            $this->_guardarArchivos($solicitud);
        }

        $this->cerrarModalPaquete();
        $this->cargarBandejaComplementarios();

        $this->dispatch('notificar', [
            'mensaje' => 'Documentos guardados. Puede retomar y enviar cuando lo desee.',
            'tipo'    => 'info',
        ]);
    }

    /**
     * Guarda los documentos y marca la solicitud como ENVIADA.
     * A partir de aquí el Supervisor debe asignar un Auditor.
     */
    public function abrirModalConfirmacionComplementario()
    {
        $solicitud = \App\Models\SolicitudComplementaria::find($this->solicitud_activa_id);
        if (!$solicitud) return;

        $this->declaracion_aceptada = false;
        $this->modal_confirmacion_visible = true;
    }

    public function finalizarYEnviarComplementario()
    {
        $solicitud = \App\Models\SolicitudComplementaria::find($this->solicitud_activa_id);
        if (!$solicitud) return;

        if (!$this->declaracion_aceptada) {
            session()->flash('error_paquete', '❌ Debe aceptar la declaración de veracidad antes de confirmar.');
            return;
        }

        $docsExistentes = $solicitud->documentos()->count();

        if (!empty($this->archivos)) {
            $this->validate(['archivos.*.*' => 'required|file|mimes:pdf|max:20480']);
            $this->_guardarArchivos($solicitud);
            $docsExistentes += count($this->archivos); // cuenta los recién subidos
        }

        if ($docsExistentes === 0) {
            session()->flash('error_paquete', 'Debe cargar al menos un documento antes de enviar.');
            return;
        }

        $solicitud->update([
            'estado'      => 'ENVIADO',
            'fecha_envio' => now(),
        ]);

        $this->cerrarModalPaquete();
        $this->cargarBandejaComplementarios();
        $this->cargarGridComplementarios();
        if ($this->mes_complementario_seleccionado) {
            $this->seleccionarMesComplementario($this->mes_complementario_seleccionado);
        }

        $this->dispatch('notificar', [
            'mensaje' => 'Solicitud complementaria enviada exitosamente. El supervisor asignará un auditor.',
            'tipo'    => 'success',
        ]);
    }

    /**
     * Persiste los archivos del array $this->archivos en storage y crea
     * los registros DocumentoSolicitudComplementaria asociados.
     */
    private function _guardarArchivos(\App\Models\SolicitudComplementaria $solicitud)
    {
        foreach ($this->archivos as $requisitoId => $fileGroup) {
            if (empty($fileGroup)) continue;

            foreach ($fileGroup as $archivo) {
                $nombreOriginal = $archivo->getClientOriginalName();
                $directorio     = "documentos_complementarios/{$solicitud->id}";

                // Encriptar y guardar en disk:local
                $service    = app(\App\Services\EncryptionService::class);
                $pathEnc    = $service->encryptAndStore($archivo, $directorio);

                \App\Models\DocumentoSolicitudComplementaria::create([
                    'solicitud_complementaria_id' => $solicitud->id,
                    'requisito_verificacion_id'   => $requisitoId,
                    'path'                        => $pathEnc,
                    'nombre_original'             => $nombreOriginal,
                    'is_encrypted'                => true,
                ]);
            }
        }
    }

    public function cargarVinculaciones()
    {
        $user = Auth::user();
        $contratista = $user->contratista;

        if (!$contratista) {
            $this->vinculaciones = [];
            return;
        }

        $query = ContratistaUnidadOrganizacional::where('contratista_id', $contratista->id)
            ->where('verifica', true)
            ->with(['unidadOrganizacionalMandante', 'dependencia', 'unidadOrganizacionalMandante.mandante', 'tipoContrato']);

        if ($this->filtro_id_registro) {
            $query->where('id_registro', 'LIKE', '%' . $this->filtro_id_registro . '%');
        }

        $this->vinculaciones = $query->get();
    }

    public function getNombreMes($mes)
    {
        $nombres = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre'
        ];
        return $nombres[$mes];
    }

    public function getNombrePeriodoCal($mes, $anio)
    {
        $mesPer = $mes - 1;
        $anioPer = $anio;
        if ($mesPer < 1) {
            $mesPer = 12;
            $anioPer = $anio - 1;
        }
        return $this->getNombreMes($mesPer) . ' ' . $anioPer;
    }

    public function render()
    {
        return view('livewire.contratista.verificacion-legacy')->layout('layouts.app');
    }
}
