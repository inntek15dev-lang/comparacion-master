<?php

namespace App\Livewire\Auditor;

use App\Models\SolicitudComplementaria;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;

#[Title('REV. COMPLEMENTARIOS')]
class GestionComplementarios extends Component
{
    use WithPagination;

    public $solicitud_detalle_id = null;
    public $observaciones_auditor = '';
    public $pre_datos_items = []; // Para capturar estados de cada ítem en el modal

    // Filtros
    public $estado = 'EN_REVISION'; 
    public $mandante_id = '';
    public $contratista_id = '';
    public $dependencia_id = '';
    public $numero_contrato = '';
    public $searchFolio = '';
    public $anio = '';
    public $mes = '';
    public $tipo_item = '';
    
    public $auditores_disponibles = [];
    public $auditor_asignado_temp = []; // Para asignar desde la tabla

    protected $queryString = [
        'estado' => ['except' => ''],
        'mandante_id' => ['except' => ''],
        'contratista_id' => ['except' => ''],
        'dependencia_id' => ['except' => ''],
        'numero_contrato' => ['except' => ''],
        'searchFolio' => ['except' => ''],
        'anio' => ['except' => ''],
        'mes' => ['except' => ''],
        'tipo_item' => ['except' => ''],
    ];
    public function mount()
    {
        // Cargar ejecutores para asignación (solo tipo Asem)
        $this->auditores_disponibles = \App\Models\User::where('user_type', 'asem')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function updatedMandanteId()
    {
        $this->contratista_id = '';
        $this->dependencia_id = '';
        $this->numero_contrato = '';
        $this->resetPage();
    }

    public function updatedContratistaId()
    {
        $this->dependencia_id = '';
        $this->numero_contrato = '';
        $this->resetPage();
    }

    public function updatedDependenciaId() { $this->resetPage(); }
    public function updatedNumeroContrato() { $this->resetPage(); }
    public function updatedSearchFolio() { $this->resetPage(); }
    public function updatedAnio() { $this->resetPage(); }
    public function updatedMes() { $this->resetPage(); }
    public function updatedTipoItem() { $this->resetPage(); }

    public function updatingEstado()
    {
        $this->resetPage();
    }

    public function verDetalle($id)
    {
        $user = Auth::user();
        $solicitud = SolicitudComplementaria::with('items')->find($id);

        if (!$solicitud) return;

        // Guardia de seguridad estricto para Auditor
        if ($user->hasRole('Verifica_Auditor') && $solicitud->auditor_id !== $user->id) {
            session()->flash('error', 'Acceso denegado: Este folio complementario no le pertenece.');
            return;
        }

        $this->solicitud_detalle_id = $id;
        $this->observaciones_auditor = $solicitud->observaciones_auditor ?? '';
        
        // Inicializar datos de los ítems para el modal
        $this->pre_datos_items = [];
        foreach ($solicitud->items as $item) {
            $this->pre_datos_items[$item->id] = [
                'estado'             => $item->estado_auditor ?? 'PENDIENTE',
                'monto_solucionado'  => $item->monto_solucionado,
                'observaciones'      => $item->observaciones_auditor,
            ];
        }
    }

    public function asignarAuditor($solicitudId, $auditorId)
    {
        $sol = SolicitudComplementaria::find($solicitudId);
        if ($sol) {
            $sol->update(['auditor_id' => $auditorId]);
            session()->flash('success', 'Auditor asignado correctamente.');
        }
    }

    public function guardarRevisionItem($itemId)
    {
        $itemData = $this->pre_datos_items[$itemId] ?? null;
        if (!$itemData) return;

        $solicitud = SolicitudComplementaria::find($this->solicitud_detalle_id);
        if ($solicitud && in_array($solicitud->estado, ['SOLUCIONADO', 'RECHAZADO', 'EMITIDO'])) {
            session()->flash('error_modal', 'No se puede modificar un ítem de un complementario ya cerrado o emitido.');
            return;
        }

        $item = \App\Models\SolicitudComplementariaItem::find($itemId);
        if ($item) {
            $item->update([
                'estado_auditor'        => $itemData['estado'],
                'monto_solucionado'     => ($itemData['estado'] === 'PARCIAL') ? $itemData['monto_solucionado'] : null,
                'observaciones_auditor' => $itemData['observaciones'],
            ]);
            
            // Si es TOTAL, podemos marcar de una vez la contingencia si se desea,
            // pero mejor esperar al cierre global para consistencia.
        }
    }

    public function finalizarRevisionGlobal()
    {
        $solicitud = SolicitudComplementaria::with('items.contingencia')->find($this->solicitud_detalle_id);
        
        if ($solicitud && in_array($solicitud->estado, ['SOLUCIONADO', 'RECHAZADO', 'EMITIDO'])) {
            session()->flash('error_modal', 'Este complementario ya se encuentra cerrado o emitido y no puede finalizarse nuevamente.');
            return;
        }

        // Auto-guardar y asumir NO SOLUCIONADO (RECHAZADO) si no se seleccionó nada o si quedó pendiente
        foreach ($solicitud->items as $item) {
            $estadoLocal = $this->pre_datos_items[$item->id]['estado'] ?? 'PENDIENTE';
            $observacionesLocal = $this->pre_datos_items[$item->id]['observaciones'] ?? null;
            $montoSolucionadoLocal = $this->pre_datos_items[$item->id]['monto_solucionado'] ?? null;

            // Si el estado en DB es PENDIENTE o hubo un cambio no guardado en la UI
            if ($item->estado_auditor === 'PENDIENTE' || $estadoLocal !== $item->estado_auditor) {
                $estadoFinal = ($estadoLocal === 'PENDIENTE' || empty($estadoLocal)) ? 'RECHAZADO' : $estadoLocal;
                
                $item->update([
                    'estado_auditor'        => $estadoFinal,
                    'monto_solucionado'     => ($estadoFinal === 'PARCIAL') ? $montoSolucionadoLocal : null,
                    'observaciones_auditor' => $observacionesLocal,
                ]);
            }
        }

        // Refrescar relaciones después de la actualización masiva
        $solicitud->refresh();

        // Aplicar cambios a las contingencias reales según las decisiones
        foreach ($solicitud->items as $item) {
            if ($item->estado_auditor === 'TOTAL') {
                $item->contingencia->update(['estado_subsanacion' => 'SUBSANADO']);
            } 
            // La lógica parcial se manejará en el certificado y reporte (Monto original vs Monto Solucionado del Item)
        }

        // Determinar estado global: Si hay algún RECHAZADO -> RECHAZADO. Si todo TOTAL -> SOLUCIONADO.
        // Si hay PARCIAL -> SOLUCIONADO (Parcialmente)
        $hasRechazo = $solicitud->items->where('estado_auditor', 'RECHAZADO')->count() > 0;
        $hasParcial = $solicitud->items->where('estado_auditor', 'PARCIAL')->count() > 0;

        $nuevoEstado = 'SOLUCIONADO';
        if ($hasRechazo) $nuevoEstado = 'RECHAZADO';
        // Podríamos tener un estado específico para parcial si se desea:
        // if ($hasParcial && !$hasRechazo) $nuevoEstado = 'SOLUCIONADO_PARCIAL';

        $solicitud->update([
            'estado'                => $nuevoEstado,
            'fecha_revision'        => now(),
            'observaciones_auditor' => $this->observaciones_auditor,
        ]);

        session()->flash('success', 'La solicitud ha sido procesada y cerrada correctamente.');
        $this->cerrarDetalle();
    }

    public function cerrarDetalle()
    {
        $this->solicitud_detalle_id = null;
        $this->observaciones_auditor = '';
        $this->edicion_items = [];
    }

    public function render()

    {
        $user = Auth::user();

        // Extraemos la lógica de filtro en un Closure para aplicarlo tanto en whereHas como en with()
        $scFilter = function($sq) use ($user) {
            // Perfilamiento: Auditor ve solo lo suyo estrictamente
            if ($user->hasRole('Verifica_Auditor')) {
                $sq->where('auditor_id', $user->id);
            }

            // Estado de la SC
            if ($this->estado && $this->estado !== 'TODOS') {
                if ($this->estado === 'REVISADOS') {
                    $sq->whereIn('estado', ['SOLUCIONADO', 'RECHAZADO', 'EMITIDO']);
                } else {
                    $sq->where('estado', $this->estado);
                }
            }
            
            // Tipo de Ítem de la SC
            if ($this->tipo_item) {
                $ti = $this->tipo_item;
                $sq->whereHas('items.contingencia', function($q) use ($ti) {
                    if ($ti === 'OBS') {
                        $q->where('tipo', 'observacion');
                    } elseif ($ti === 'CONT-RET') {
                        $q->where('tipo', 'contingencia')->where('subtipo', 'retenible');
                    } elseif ($ti === 'CONT-NRET') {
                        $q->where('tipo', 'contingencia')->where('subtipo', 'no_retenible');
                    }
                });
            }
        };

        $query = \App\Models\CarpetaVerificacion::with([
            'solicitudesComplementarias' => function($sq) use ($scFilter) {
                // Pre-cargar relaciones críticas de las SCs y FILTRARLAS idénticamente
                $sq->orderBy('created_at', 'asc')->with(['auditor', 'items.contingencia']);
                $scFilter($sq);
            },
            'vinculacion.contratista',
            'vinculacion.unidadOrganizacional.mandante',
            'vinculacion.dependencia'
        ])
        ->whereHas('solicitudesComplementarias', $scFilter);

        // Filtros nivel de Carpeta (Estructura y Mes/Año)
        if ($this->mandante_id) {
            $query->whereHas('vinculacion.unidadOrganizacional', function($q) {
                $q->where('mandante_id', $this->mandante_id);
            });
        }

        if ($this->contratista_id) {
            $query->whereHas('vinculacion', function($q) {
                $q->where('contratista_id', $this->contratista_id);
            });
        }

        if ($this->dependencia_id) {
            $query->whereHas('vinculacion', function($q) {
                $q->where('dependencia_id', $this->dependencia_id);
            });
        }

        if ($this->numero_contrato) {
            $query->whereHas('vinculacion', function($q) {
                $q->where('numero_contrato', $this->numero_contrato);
            });
        }
        
        if ($this->anio) {
            $query->where('anio', $this->anio);
        }

        if ($this->mes) {
            $query->where('mes', $this->mes);
        }

        if ($this->searchFolio) {
            $search = $this->searchFolio;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('vinculacion', function($sq) use ($search) {
                      $sq->where('id_registro', 'like', "%{$search}%")
                         ->orWhere('numero_contrato', 'like', "%{$search}%");
                  })
                  ->orWhereHas('solicitudesComplementarias', function($sq) use ($search) {
                      $sq->where('folio', 'like', "%{$search}%");
                  })
                  ->orWhereHas('solicitudesComplementarias.items.contingencia.carpetaTrabajador.vinculacion.trabajador', function($sq) use ($search) {
                      $sq->where('rut', 'like', "%{$search}%")
                         ->orWhere('nombres', 'like', "%{$search}%")
                         ->orWhere('apellido_paterno', 'like', "%{$search}%")
                         ->orWhere('apellido_materno', 'like', "%{$search}%");
                  });
            });
        }

        $certificados = $query->orderBy('created_at', 'desc')->paginate(15);


        // Datos para filtros
        $mandantes = \App\Models\Mandante::orderBy('razon_social')->get();
        
        $contratistas = [];
        $dependencias = [];
        $contratos = [];

        if ($this->mandante_id) {
            $contratistas = \App\Models\Contratista::whereHas('vinculaciones.unidadOrganizacional', function($q) {
                $q->where('mandante_id', $this->mandante_id);
            })->orderBy('razon_social')->get();

            // Si hay mandante, ya podemos sacar dependencias de sus UOs
            $dependenciasQuery = \App\Models\Dependencia::whereHas('vinculaciones.unidadOrganizacional', function($q) {
                $q->where('mandante_id', $this->mandante_id);
            });
            if ($this->contratista_id) {
                $dependenciasQuery->whereHas('vinculaciones', function($q) {
                    $q->where('contratista_id', $this->contratista_id);
                });
            }
            $dependencias = $dependenciasQuery->orderBy('nombre')->get();

        }

        if ($this->contratista_id) {
            $cq = \App\Models\ContratistaUnidadOrganizacional::where('contratista_id', $this->contratista_id);
            if ($this->mandante_id) {
                $cq->whereHas('unidadOrganizacional', fn($q) => $q->where('mandante_id', $this->mandante_id));
            }
            $contratos = $cq->whereNotNull('numero_contrato')->distinct()->pluck('numero_contrato');
        }

        // ── CONTADORES ────────────────────────────────────────────────────────
        $baseSC = \App\Models\SolicitudComplementaria::whereNotNull('carpeta_verificacion_id');
        if ($user->isAsem()) {
            $baseSC->where(fn($q) => $q->where('auditor_id', $user->id)->orWhereNull('auditor_id'));
        }
        $contPending  = (clone $baseSC)->where('estado', 'EN_REVISION')->count();
        $contClosed   = (clone $baseSC)->whereIn('estado', ['SOLUCIONADO', 'RECHAZADO', 'EMITIDO'])->count();

        // ── DETALLE MODAL ─────────────────────────────────────────────────────
        $solicitudDetalle = null;
        if ($this->solicitud_detalle_id) {
            $solicitudDetalle = \App\Models\SolicitudComplementaria::with([
                'vinculacion.contratista',
                'vinculacion.dependencia',
                'items.contingencia.carpetaTrabajador.vinculacion.trabajador',
                'carpeta',
                'documentos.requisito',
            ])->find($this->solicitud_detalle_id);
        }

        return view('livewire.auditor.gestion-complementarios', [
            'certificados'    => $certificados,
            'solicitudDetalle'=> $solicitudDetalle,
            'mandantes'       => $mandantes,
            'contratistas'    => $contratistas,
            'dependencias'    => $dependencias,
            'contratos'       => $contratos,
            'contPending'     => $contPending,
            'contClosed'      => $contClosed,
        ])->layout('layouts.app');
    }
}
