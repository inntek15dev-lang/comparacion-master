<?php

namespace App\Livewire\Auditor;

use App\Models\CarpetaVerificacion;
use App\Models\CarpetaTrabajadorContingencia;
use App\Models\CatalogoAuditoriaItem;
use App\Models\RequisitoVerificacion;
use App\Models\Mandante;
use App\Models\Contratista;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Traits\DescargaContextualTrait;

use Livewire\Attributes\Title;

#[Title('AUDITAR PERIODOS')]
class MisAuditorias extends Component
{
    use WithPagination;
    use DescargaContextualTrait;

    // --- FILTROS ---
    public $mandante_id    = '';
    public $contratista_id = '';
    public $anio           = '';
    public $mes            = '';
    public $estado_revision = '';
    public $estado_plazo = '';

    public $carpeta_detalle_id = null;

    protected $queryString = [
        'mandante_id'     => ['except' => ''],
        'contratista_id'  => ['except' => ''],
        'anio'            => ['except' => ''],
        'mes'             => ['except' => ''],
        'estado_revision' => ['except' => ''],
        'estado_plazo'    => ['except' => ''],
    ];

    public function mount()
    {
        $this->anio = date('Y');
    }

    public function updatingMandanteId()    { $this->contratista_id = ''; $this->resetPage(); }
    public function updatingContratistaId() { $this->resetPage(); }
    public function updatingAnio()          { $this->resetPage(); }
    public function updatingMes()           { $this->resetPage(); }
    public function updatingEstadoRevision(){ $this->resetPage(); }
    public function updatingEstadoPlazo()   { $this->resetPage(); }

    // ============================================================
    // MODAL DETALLE (DOCUMENTOS)
    // ============================================================

    public function verDetalle($id)
    {
        $this->carpeta_detalle_id = $id;
    }

    public function cerrarDetalle()
    {
        $this->carpeta_detalle_id = null;
    }

    public function limpiarFiltros()
    {
        $this->reset(['mandante_id', 'contratista_id', 'mes', 'estado_revision']);
        $this->anio = date('Y');
        $this->resetPage();
    }

    public function descargarDocumentosFiltrados()
    {
        $user = Auth::user();

        $query = CarpetaVerificacion::select('carpetas_verificacion.*')
            ->join('contratista_unidad_organizacional as cuo', 'cuo.id', '=', 'carpetas_verificacion.contratista_unidad_organizacional_id')
            ->leftJoin('unidades_organizacionales_mandante as uo', 'uo.id', '=', 'cuo.unidad_organizacional_mandante_id')
            ->leftJoin('dependencias as dep', 'dep.id', '=', 'cuo.dependencia_id')
            ->where('carpetas_verificacion.auditor_id', $user->id);

        if ($this->mandante_id)   $query->whereHas('vinculacion.unidadOrganizacional', fn($q) => $q->where('mandante_id', $this->mandante_id));
        if ($this->contratista_id) $query->whereHas('vinculacion', fn($q) => $q->where('contratista_id', $this->contratista_id));
        if ($this->anio)            $query->where('carpetas_verificacion.anio', $this->anio);
        if ($this->mes)             $query->where('carpetas_verificacion.mes', $this->mes);
        if ($this->estado_revision) $query->where('carpetas_verificacion.estado_revision', $this->estado_revision);
        if ($this->estado_plazo) {
            if ($this->estado_plazo === 'FUERA_PLAZO')
                $query->whereIn('carpetas_verificacion.tipo_envio', ['FUERA_PLAZO', 'FUERA_PERIODO']);
            else
                $query->where('carpetas_verificacion.tipo_envio', $this->estado_plazo);
        }

        $carpetasId = $query->pluck('carpetas_verificacion.id')->toArray();
        return $this->procesarDescargaContextual($carpetasId, "Auditor");
    }

    // ============================================================
    // VARIABLES DE CIERRE AUDITOR
    // ============================================================

    public $showModalCierre = false;
    public $esBloqueado     = false;

    // Campos financieros
    public $aud_contratados_periodo      = 0;
    public $aud_desvinculados_periodo    = 0;
    public $aud_total_vigentes           = 0;
    public $aud_trabajadores_revisados   = 0;
    public $aud_remuneraciones_pagadas   = 0;
    public $aud_cotizaciones_pagadas     = 0;
    public $aud_liquido_total            = 0;
    public $aud_aviso_previo_trabajadores   = 0;
    public $aud_aviso_previo_total          = 0;
    public $aud_anio_servicio_trabajadores  = 0;
    public $aud_anio_servicio_total         = 0;
    public $aud_feriado_trabajadores        = 0;
    public $aud_feriado_total               = 0;
    public $showModalRechazo                = false;
    public $motivoRechazo                   = '';

    // ============================================================
    // INCIDENCIAS (nuevo sistema unificado)
    // ============================================================

    /** Lista de incidencias cargadas de la carpeta */
    public $incidencias = [];

    /** Código expandido en el acordeón */
    public $incidenciaExpandida = null;

    /** Control del modal de nueva incidencia */
    public $showModalNuevaIncidencia = false;

    /** Formulario de nueva incidencia */
    public $nuevaIncidencia = [
        'tipo'               => 'observacion',
        'subtipo'            => null,
        'clasificacion'      => '',
        'catalogo_item_id'   => null,
        'causal'             => '',
        'monto'              => null,        // Solo para observaciones a nivel empresa
        'aplica_empresa'     => true,
        'trabajadores_ids'   => [],
        'montos_trabajadores'=> [],          // [ctvId => monto] — un monto por trabajador
    ];

    /** Texto pegado desde el cuadro de codificación de Excel */
    public $textoCodificacion = '';

    /** Catálogos */
    public $catalogoObservaciones  = [];
    public $catalogoContingencias  = [];

    // ============================================================
    // MODAL CIERRE — abrir / cerrar
    // ============================================================

    public function abrirModalCierre($id = null)
    {
        if ($id) $this->carpeta_detalle_id = $id;

        $carpeta = CarpetaVerificacion::find($this->carpeta_detalle_id);
        if (!$carpeta) return;

        if ($carpeta->estado_revision === 'EMITIDO') {
            session()->flash('error', 'No se puede auditar un periodo ya EMITIDO.');
            return;
        }

        // Carga catálogos
        $this->catalogoObservaciones = CatalogoAuditoriaItem::where('is_active', true)
            ->where('tipo', 'observacion')->orderBy('texto')->get()->toArray();

        $this->catalogoContingencias = CatalogoAuditoriaItem::where('is_active', true)
            ->where('tipo', 'contingencia')->orderBy('texto')->get()->toArray();

        // Carga datos financieros
        $this->aud_contratados_periodo       = $carpeta->fin_contratados_periodo ?? 0;
        $this->aud_desvinculados_periodo     = $carpeta->fin_desvinculados_periodo ?? 0;
        $this->aud_total_vigentes            = $carpeta->fin_total_vigentes ?? 0;
        $this->aud_trabajadores_revisados    = $carpeta->fin_trabajadores_revisados ?? 0;
        $this->aud_remuneraciones_pagadas    = $carpeta->fin_remuneraciones_pagadas ?? 0;
        $this->aud_cotizaciones_pagadas      = $carpeta->fin_cotizaciones_pagadas ?? 0;
        $this->aud_liquido_total             = $carpeta->fin_liquido_total ?? 0;
        $this->aud_aviso_previo_trabajadores = $carpeta->fin_aviso_previo_trabajadores ?? 0;
        $this->aud_aviso_previo_total        = $carpeta->fin_aviso_previo_total ?? 0;
        $this->aud_anio_servicio_trabajadores= $carpeta->fin_anio_servicio_trabajadores ?? 0;
        $this->aud_anio_servicio_total       = $carpeta->fin_anio_servicio_total ?? 0;
        $this->aud_feriado_trabajadores      = $carpeta->fin_feriado_trabajadores ?? 0;
        $this->aud_feriado_total             = $carpeta->fin_feriado_total ?? 0;

        // Bloqueo
        $this->esBloqueado = in_array($carpeta->estado_revision, ['PARA_EMITIR', 'EMITIDO']);

        // Carga incidencias existentes
        $this->cargarIncidencias();

        // Avanzar a AUDITANDO si es la primera vez
        if ($carpeta->estado_revision === 'REVISADO') {
            $carpeta->update(['estado_revision' => 'AUDITANDO']);
        }

        $this->showModalCierre = true;
    }

    public function cerrarModalCierre()
    {
        $this->showModalCierre = false;
        $this->incidenciaExpandida = null;
    }

    // ============================================================
    // INCIDENCIAS — cargar / acordeón
    // ============================================================

    public function cargarIncidencias()
    {
        $carpeta = CarpetaVerificacion::find($this->carpeta_detalle_id);
        if (!$carpeta) { $this->incidencias = []; return; }

        $this->incidencias = CarpetaTrabajadorContingencia::where('carpeta_verificacion_id', $carpeta->id)
            ->with(['carpetaTrabajador.vinculacion.trabajador', 'catalogoItem'])
            ->orderBy('codigo')
            ->get()
            ->map(function ($inc) {
                $trab = null;
                if ($inc->carpetaTrabajador) {
                    $t = $inc->carpetaTrabajador->vinculacion->trabajador ?? null;
                    $trab = $t ? [
                        'nombre' => $t->nombre_completo ?? ($t->nombres . ' ' . $t->apellidos),
                        'rut'    => $t->rut,
                    ] : null;
                }
                return [
                    'id'             => $inc->id,
                    'codigo'         => $inc->codigo,
                    'tipo'           => $inc->tipo,
                    'subtipo'        => $inc->subtipo,
                    'clasificacion'  => $inc->clasificacion,
                    'causal'         => $inc->causal,
                    'monto'          => $inc->monto,
                    'aplica_empresa' => $inc->aplica_empresa,
                    'trabajador'     => $trab,
                    'label_tipo'     => $inc->label_tipo,
                    'color_badge'    => $inc->color_badge,
                ];
            })->toArray();
    }

    public function toggleIncidencia($codigo)
    {
        $this->incidenciaExpandida = $this->incidenciaExpandida === $codigo ? null : $codigo;
    }

    // ============================================================
    // MODAL NUEVA INCIDENCIA
    // ============================================================

    public function abrirModalNuevaIncidencia()
    {
        $this->nuevaIncidencia = [
            'tipo'               => 'observacion',
            'subtipo'            => null,
            'clasificacion'      => '',
            'catalogo_item_id'   => null,
            'causal'             => '',
            'monto'              => null,
            'aplica_empresa'     => true,
            'trabajadores_ids'   => [],
            'montos_trabajadores'=> [],
        ];
        $this->showModalNuevaIncidencia = true;
    }

    public function cerrarModalNuevaIncidencia()
    {
        $this->showModalNuevaIncidencia = false;
        $this->textoCodificacion = '';
    }

    public function procesarCodificacion()
    {
        if (empty(trim($this->textoCodificacion))) {
            return;
        }

        $carpeta = CarpetaVerificacion::find($this->carpeta_detalle_id);
        if (!$carpeta) return;

        $trabajadores = $carpeta->trabajadoresVerificados()
            ->with(['vinculacion.trabajador'])
            ->get();

        // El texto viene separado por | continuamente
        $lineas = explode('|', $this->textoCodificacion);
        $count = 0;
        $noEncontrados = 0;

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;

            $datos = explode(',', $linea);
            if (count($datos) >= 5) {
                $rut = trim($datos[0]);
                $monto = (float) trim($datos[4]);

                $ctv = $trabajadores->first(function ($t) use ($rut) {
                    $rutTrabajador = $t->snapshot_rut ?: ($t->vinculacion->trabajador->rut ?? '');
                    // Comparamos sin puntos para ser más resilientes
                    return str_replace('.', '', $rutTrabajador) === str_replace('.', '', $rut);
                });

                if ($ctv) {
                    if (!in_array((string)$ctv->id, $this->nuevaIncidencia['trabajadores_ids'])) {
                        $this->nuevaIncidencia['trabajadores_ids'][] = (string)$ctv->id;
                    }
                    $this->nuevaIncidencia['montos_trabajadores'][$ctv->id] = $monto;
                    $count++;
                } else {
                    $noEncontrados++;
                }
            }
        }

        $this->textoCodificacion = ''; // Limpiamos después de procesar
        
        if ($count > 0) {
            $msg = "Se procesaron y seleccionaron $count trabajadores desde la codificación.";
            if ($noEncontrados > 0) {
                $msg .= " ($noEncontrados RUTs no encontrados en este periodo).";
            }
            session()->flash('success_codificacion', $msg);
        } else {
            session()->flash('error_codificacion', 'No se encontraron trabajadores coincidentes o el formato es incorrecto.');
        }
    }

    public function seleccionarTextoCatalogo($itemId, $texto)
    {
        $this->nuevaIncidencia['catalogo_item_id'] = $itemId;
        $this->nuevaIncidencia['causal'] = $texto;
    }

    /**
     * Hook reactivo: cuando cambia catalogo_item_id desde el select,
     * busca el texto del ítem y lo coloca en el textarea.
     */
    public function updatedNuevaIncidenciaCatalogoItemId($value)
    {
        if (!$value) return;
        $catalogo = array_merge($this->catalogoObservaciones, $this->catalogoContingencias);
        $item = collect($catalogo)->firstWhere('id', (int) $value);
        if ($item) {
            $this->nuevaIncidencia['causal'] = $item['texto'];
        }
    }

    /** Al cambiar tipo → resetear clasificación, catálogo, subtipo y alcance */
    public function updatedNuevaIncidenciaTipo($value)
    {
        $this->nuevaIncidencia['clasificacion']       = '';
        $this->nuevaIncidencia['catalogo_item_id']    = null;
        $this->nuevaIncidencia['causal']              = '';
        $this->nuevaIncidencia['subtipo']             = null;
        $this->nuevaIncidencia['aplica_empresa']      = $value === 'observacion' ? true : false;
        $this->nuevaIncidencia['trabajadores_ids']    = [];
        $this->nuevaIncidencia['montos_trabajadores'] = [];
    }

    /** Al cambiar subtipo → resetear clasificación */
    public function updatedNuevaIncidenciaSubtipo()
    {
        $this->nuevaIncidencia['clasificacion']       = '';
        $this->nuevaIncidencia['catalogo_item_id']    = null;
        $this->nuevaIncidencia['causal']              = '';
        $this->nuevaIncidencia['montos_trabajadores'] = [];
    }

    // ============================================================
    // MODAL SECUNDARIO: CATÁLOGO
    // ============================================================

    public $showModalCatalogo = false;

    public function abrirModalCatalogo()
    {
        $this->showModalCatalogo = true;
    }

    public function cerrarModalCatalogo()
    {
        $this->showModalCatalogo = false;
    }

    public function seleccionarItemCatalogo($itemId)
    {
        $this->nuevaIncidencia['catalogo_item_id'] = $itemId;
        $catalogo = array_merge($this->catalogoObservaciones, $this->catalogoContingencias);
        $item = collect($catalogo)->firstWhere('id', (int) $itemId);
        if ($item) {
            $this->nuevaIncidencia['causal'] = $item['texto'];
        }
        $this->cerrarModalCatalogo();
    }

    public function guardarNuevaIncidencia()
    {
        $carpeta = CarpetaVerificacion::find($this->carpeta_detalle_id);
        if (!$carpeta || $this->esBloqueado) return;

        // Validación
        $this->validate([
            'nuevaIncidencia.tipo'          => 'required|in:observacion,contingencia',
            'nuevaIncidencia.clasificacion' => 'required|string',
            'nuevaIncidencia.causal'        => 'required|string|min:5',
            'nuevaIncidencia.monto'         => 'nullable|numeric|min:0',
        ], [
            'nuevaIncidencia.clasificacion.required' => 'Debe seleccionar una clasificación.',
            'nuevaIncidencia.causal.required'        => 'El texto de la incidencia es obligatorio.',
            'nuevaIncidencia.causal.min'             => 'El texto debe tener al menos 5 caracteres.',
        ]);

        $tipo    = $this->nuevaIncidencia['tipo'];
        $subtipo = $tipo === 'contingencia' ? ($this->nuevaIncidencia['subtipo'] ?? 'no_retenible') : null;

        // Para contingencias siempre por trabajador
        if ($tipo === 'contingencia') {
            if (empty($this->nuevaIncidencia['trabajadores_ids'])) {
                $this->addError('nuevaIncidencia.trabajadores_ids', 'Seleccione al menos un trabajador.');
                return;
            }
        }

        // Si observación aplica a empresa → un solo código
        if ($tipo === 'observacion' && $this->nuevaIncidencia['aplica_empresa']) {
            $codigo = $carpeta->generarCodigoIncidencia();
            CarpetaTrabajadorContingencia::create([
                'carpeta_verificacion_id'          => $carpeta->id,
                'carpeta_verificacion_trabajador_id'=> null,
                'tipo'          => $tipo,
                'subtipo'       => $subtipo,
                'clasificacion' => $this->nuevaIncidencia['clasificacion'],
                'causal'        => $this->nuevaIncidencia['causal'],
                'monto'         => $this->nuevaIncidencia['monto'] ?: 0,
                'aplica_empresa'=> true,
                'codigo'        => $codigo,
                'es_retenible'  => false,
                'catalogo_item_id' => $this->nuevaIncidencia['catalogo_item_id'],
            ]);
        } else {
            // Por trabajador — un código y un monto INDEPENDIENTE por cada uno
            $trabajadoresIds = $this->nuevaIncidencia['trabajadores_ids'];

            foreach ($trabajadoresIds as $ctvId) {
                // Monto individual: cada trabajador puede tener un importe distinto
                $montoTrabajador = isset($this->nuevaIncidencia['montos_trabajadores'][$ctvId])
                    ? (float) $this->nuevaIncidencia['montos_trabajadores'][$ctvId]
                    : 0;

                $codigo = $carpeta->generarCodigoIncidencia();
                CarpetaTrabajadorContingencia::create([
                    'carpeta_verificacion_id'            => $carpeta->id,
                    'carpeta_verificacion_trabajador_id' => $ctvId,
                    'tipo'             => $tipo,
                    'subtipo'          => $subtipo,
                    'clasificacion'    => $this->nuevaIncidencia['clasificacion'],
                    'causal'           => $this->nuevaIncidencia['causal'],
                    'monto'            => $montoTrabajador,
                    'aplica_empresa'   => false,
                    'codigo'           => $codigo,
                    'es_retenible'     => $subtipo === 'retenible',
                    'catalogo_item_id' => $this->nuevaIncidencia['catalogo_item_id'],
                ]);
            }
        }

        $this->cargarIncidencias();
        $this->showModalNuevaIncidencia = false;
        session()->flash('success', 'Incidencia agregada correctamente.');
    }

    public function eliminarIncidencia($id)
    {
        if ($this->esBloqueado) return;
        CarpetaTrabajadorContingencia::where('id', $id)
            ->where('carpeta_verificacion_id', $this->carpeta_detalle_id)
            ->delete();
        $this->cargarIncidencias();
        if ($this->incidenciaExpandida) $this->incidenciaExpandida = null;
    }

    // ============================================================
    // GUARDAR / FINALIZAR
    // ============================================================

    public function guardarProgreso()
    {
        $this->persistirCierre(false);
    }

    public function finalizarPeriodo()
    {
        $this->persistirCierre(true);
    }

    private function persistirCierre($finalizar)
    {
        $carpeta = CarpetaVerificacion::find($this->carpeta_detalle_id);
        if (!$carpeta) return;

        if (in_array($carpeta->estado_revision, ['PARA_EMITIR', 'EMITIDO'])) {
            session()->flash('error', 'El periodo está bloqueado para cambios.');
            return;
        }

        $this->validate([
            'aud_remuneraciones_pagadas' => 'nullable|numeric|min:0',
            'aud_cotizaciones_pagadas'   => 'nullable|numeric|min:0',
            'aud_liquido_total'          => 'nullable|numeric|min:0',
        ]);

        $carpeta->update([
            'estado_revision'               => $finalizar ? 'PARA_EMITIR' : 'AUDITANDO',
            'fecha_auditoria'               => $finalizar ? now() : $carpeta->fecha_auditoria,
            'fin_contratados_periodo'       => $this->aud_contratados_periodo,
            'fin_desvinculados_periodo'     => $this->aud_desvinculados_periodo,
            'fin_total_vigentes'            => $this->aud_total_vigentes,
            'fin_trabajadores_revisados'    => $this->aud_trabajadores_revisados,
            'fin_remuneraciones_pagadas'    => $this->aud_remuneraciones_pagadas,
            'fin_cotizaciones_pagadas'      => $this->aud_cotizaciones_pagadas,
            'fin_liquido_total'             => $this->aud_liquido_total,
            'fin_aviso_previo_trabajadores' => $this->aud_aviso_previo_trabajadores,
            'fin_aviso_previo_total'        => $this->aud_aviso_previo_total,
            'fin_anio_servicio_trabajadores'=> $this->aud_anio_servicio_trabajadores,
            'fin_anio_servicio_total'       => $this->aud_anio_servicio_total,
            'fin_feriado_trabajadores'      => $this->aud_feriado_trabajadores,
            'fin_feriado_total'             => $this->aud_feriado_total,
        ]);

        $this->cerrarModalCierre();
        $this->carpeta_detalle_id = null;
        session()->flash('success', $finalizar ? 'Periodo finalizado (PARA EMITIR).' : 'Progreso de auditoría guardado.');
    }

    // ============================================================
    // RECHAZAR / DEVOLVER
    // ============================================================

    public function abrirModalRechazo()
    {
        $this->motivoRechazo = '';
        $this->showModalRechazo = true;
    }

    public function cerrarModalRechazo()
    {
        $this->showModalRechazo = false;
        $this->motivoRechazo = '';
    }

    public function rechazarAuditoria()
    {
        $this->validate([
            'motivoRechazo' => 'required|string|min:5'
        ], [
            'motivoRechazo.required' => 'El motivo de devolución es obligatorio.',
            'motivoRechazo.min' => 'El motivo debe tener al menos 5 caracteres.'
        ]);

        $carpeta = CarpetaVerificacion::find($this->carpeta_detalle_id);
        if (!$carpeta) return;

        if ($carpeta->estado_revision === 'EMITIDO') {
            session()->flash('error', 'No se puede rechazar un periodo ya EMITIDO.');
            return;
        }

        // Preparamos el mensaje de devolución
        $mensaje = "\n[DEVOLUCIÓN POR AUDITOR " . now()->format('d/m/Y H:i') . "]\nMOTIVO: " . $this->motivoRechazo . "\n" . str_repeat('-', 40) . "\n";
        
        // Actualizamos observaciones del analista para que lo vea
        $nuevasObs = $mensaje . ($carpeta->observaciones_analista ?? '');

        // Elimina todas las incidencias (según comportamiento previo solicitado por el usuario en el confirm)
        CarpetaTrabajadorContingencia::where('carpeta_verificacion_id', $carpeta->id)->delete();

        $carpeta->update([
            'estado_revision'        => 'EN_REVISION',
            'fecha_fin_revision'     => null,
            'fin_observaciones_json' => null,
            'observaciones_analista' => $nuevasObs,
        ]);

        $this->incidencias = [];
        $this->carpeta_detalle_id = null;
        $this->showModalRechazo = false;
        
        session()->flash('warning', 'Periodo devuelto al analista. Incidencias borradas.');
        $this->cerrarModalCierre();
    }

    // ============================================================
    // OBSERVACIÓN GENERAL DETALLE (modal ver docs)
    // ============================================================

    public function guardarObservacionAuditor($id, $obs)
    {
        $carpeta = CarpetaVerificacion::find($id);
        if ($carpeta) {
            $carpeta->update(['observaciones_auditor' => $obs]);
            session()->flash('success', 'Observación guardada.');
        }
    }

    // ============================================================
    // RENDER
    // ============================================================

    public function render()
    {
        $user = Auth::user();

        $totalAsignados  = CarpetaVerificacion::where('auditor_id', $user->id)->where('estado_revision', 'REVISADO')->count();
        $totalAuditando  = CarpetaVerificacion::where('auditor_id', $user->id)->where('estado_revision', 'AUDITANDO')->count();
        $totalParaEmitir = CarpetaVerificacion::where('auditor_id', $user->id)->where('estado_revision', 'PARA_EMITIR')->count();
        $totalEnRevision = CarpetaVerificacion::where('auditor_id', $user->id)->where('estado_revision', 'EN_REVISION')->count();

        $mandantes = Mandante::where('is_active', true)->orderBy('razon_social')->get();

        $contratistas = collect();
        if ($this->mandante_id) {
            $contratistas = Contratista::whereHas('unidadesOrganizacionalesMandante', function ($q) {
                $q->where('mandante_id', $this->mandante_id);
            })->orderBy('razon_social')->get();
        }

        $query = CarpetaVerificacion::select('carpetas_verificacion.*', 'sv.contratista_padre_id')
            ->join('contratista_unidad_organizacional as cuo', 'cuo.id', '=', 'carpetas_verificacion.contratista_unidad_organizacional_id')
            ->leftJoin('unidades_organizacionales_mandante as uo', 'uo.id', '=', 'cuo.unidad_organizacional_mandante_id')
            ->leftJoin('dependencias as dep', 'dep.id', '=', 'cuo.dependencia_id')
            ->leftJoin('solicitudes_vinculacion as sv', function ($join) {
                $join->on('sv.contratista_id', '=', 'cuo.contratista_id')
                     ->on('sv.mandante_id', '=', DB::raw('COALESCE(uo.mandante_id, dep.mandante_id)'))
                     ->where('sv.estado', '=', 'APROBADA')
                     ->where('sv.tipo_solicitud', '=', 'SUBCONTRATISTA');
            })
            ->with([
                'vinculacion.contratista',
                'vinculacion.unidadOrganizacional.mandante',
                'vinculacion.dependencia',
                'analista',
                'supervisor',
                'incidencias', // Precarga para evitar N+1 en badges de la tabla
            ])->where('carpetas_verificacion.auditor_id', $user->id);

        if ($this->mandante_id)    $query->whereHas('vinculacion.unidadOrganizacional', fn($q) => $q->where('mandante_id', $this->mandante_id));
        if ($this->contratista_id) $query->whereHas('vinculacion', fn($q) => $q->where('contratista_id', $this->contratista_id));
        if ($this->anio)            $query->where('carpetas_verificacion.anio', $this->anio);
        if ($this->mes)             $query->where('carpetas_verificacion.mes', $this->mes);
        if ($this->estado_revision) $query->where('carpetas_verificacion.estado_revision', $this->estado_revision);
        if ($this->estado_plazo) {
            if ($this->estado_plazo === 'FUERA_PLAZO')
                $query->whereIn('carpetas_verificacion.tipo_envio', ['FUERA_PLAZO', 'FUERA_PERIODO']);
            else
                $query->where('carpetas_verificacion.tipo_envio', $this->estado_plazo);
        }

        $carpetasBase = $query->get();
        $carpetas = $this->ordenarJerarquicamente($carpetasBase);

        $perPage     = 50;
        $currentPage = $this->getPage();
        $carpetas    = new \Illuminate\Pagination\LengthAwarePaginator(
            $carpetas->forPage($currentPage, $perPage),
            $carpetas->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Support\Facades\Request::url(), 'query' => \Illuminate\Support\Facades\Request::query()]
        );

        $carpetaDetalle         = null;
        $requisitosPorClasif    = collect();
        $documentosPorRequisito = collect();
        $trabajadoresPeriodo    = collect();
        $esBloqueado            = false;

        if ($this->carpeta_detalle_id) {
            $carpetaDetalle = CarpetaVerificacion::with([
                'vinculacion.contratista',
                'vinculacion.unidadOrganizacional.mandante',
                'vinculacion.dependencia',
                'documentos.requisito.clasificacion',
                'analista',
                'supervisor',
                'auditor',
            ])->find($this->carpeta_detalle_id);

            if ($carpetaDetalle) {
                $esBloqueado = in_array($carpetaDetalle->estado_revision, ['PARA_EMITIR', 'EMITIDO']);

                $trabajadoresPeriodo = $carpetaDetalle->trabajadoresVerificados()
                    ->with(['vinculacion.trabajador', 'vinculacion.cargoMandante'])
                    ->get();

                $mandanteId = $carpetaDetalle->vinculacion->unidadOrganizacional->mandante_id ?? null;

                if ($mandanteId) {
                    $requisitosPorClasif = RequisitoVerificacion::where('mandante_id', $mandanteId)
                        ->where('is_active', true)
                        ->with('clasificacion')
                        ->orderBy('nombre')
                        ->get()
                        ->groupBy(fn($r) => $r->clasificacion->nombre ?? 'Sin Clasificación');
                }

                $documentosPorRequisito = $carpetaDetalle->documentos->keyBy('requisito_verificacion_id');
            }
        }

        return view('livewire.auditor.mis-auditorias', [
            'carpetas'               => $carpetas,
            'mandantes'              => $mandantes,
            'contratistas'           => $contratistas,
            'carpetaDetalle'         => $carpetaDetalle,
            'trabajadoresPeriodo'    => $trabajadoresPeriodo,
            'requisitosPorClasif'    => $requisitosPorClasif,
            'documentosPorRequisito' => $documentosPorRequisito,
            'totalAsignados'         => $totalAsignados,
            'totalAuditando'         => $totalAuditando,
            'totalParaEmitir'        => $totalParaEmitir,
            'totalEnRevision'        => $totalEnRevision,
            'esBloqueado'            => $esBloqueado,
        ])->layout('layouts.app');
    }

    // ============================================================
    // ORDEN JERÁRQUICO (sin cambios)
    // ============================================================

    protected function ordenarJerarquicamente($collection)
    {
        $gruposPorPeriodo = $collection->groupBy(fn($item) =>
            $item->anio . '-' . str_pad($item->mes, 2, '0', STR_PAD_LEFT)
        )->sortKeys();

        $resultadoFinal = collect();
        $contadorRaicesGlobal = 1;

        $aplanarArbol = function ($items, $prefijo = '') use (&$aplanarArbol, &$resultadoFinal, &$contadorRaicesGlobal) {
            $subContador = 1;
            foreach ($items as $item) {
                if ($prefijo === '') {
                    $item->correlativo_jerarquico = (string) $contadorRaicesGlobal;
                    $contadorRaicesGlobal++;
                } else {
                    $item->correlativo_jerarquico = "$prefijo.$subContador";
                    $subContador++;
                }
                $resultadoFinal->push($item);
                if (isset($item->temporal_children) && $item->temporal_children->isNotEmpty()) {
                    $aplanarArbol($item->temporal_children, $item->correlativo_jerarquico);
                }
            }
        };

        foreach ($gruposPorPeriodo as $periodo => $carpetasDelPeriodo) {
            $byContratista = $carpetasDelPeriodo->groupBy(fn($c) => $c->vinculacion->contratista_id ?? 0);

            foreach ($carpetasDelPeriodo as $item) {
                $item->temporal_children    = collect();
                $item->is_attached_to_parent = false;
            }

            foreach ($carpetasDelPeriodo as $child) {
                if (empty($child->contratista_padre_id)) continue;

                $candidatos = $byContratista->get($child->contratista_padre_id);
                if (!$candidatos || $candidatos->isEmpty()) continue;

                $bestPadre = null;
                $bestScore = -1;

                foreach ($candidatos as $padre) {
                    $score = 0;
                    $vP = $padre->vinculacion;
                    $vC = $child->vinculacion;
                    if (!$vP || !$vC) continue;

                    if ($vP->unidad_organizacional_mandante_id == $vC->unidad_organizacional_mandante_id) $score += 10;
                    elseif ($vP->unidad_organizacional_mandante_id && $vC->unidad_organizacional_mandante_id) $score -= 50;

                    if ($vP->dependencia_id == $vC->dependencia_id) $score += 10;
                    elseif ($vP->dependencia_id && $vC->dependencia_id) $score -= 20;

                    if ($vC->numero_contrato && $vP->numero_contrato) {
                        $score += ($vC->numero_contrato == $vP->numero_contrato) ? 50 : -100;
                    }

                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestPadre = $padre;
                    }
                }

                if ($bestPadre && $bestScore > -50) {
                    $bestPadre->temporal_children->push($child);
                    $child->is_attached_to_parent = true;
                }
            }

            $raicesDelPeriodo = $carpetasDelPeriodo->filter(fn($item) => !$item->is_attached_to_parent);
            $raicesDelPeriodo = $raicesDelPeriodo->sortBy(fn($item) => $item->vinculacion->contratista->razon_social ?? '');
            $aplanarArbol($raicesDelPeriodo);
        }

        return $resultadoFinal;
    }
}
