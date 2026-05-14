<?php

namespace App\Livewire\Supervisor;

use App\Models\CarpetaVerificacion;
use App\Models\Contratista;
use App\Models\Mandante;
use App\Models\SolicitudComplementaria;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;

#[Title('SUPERVISOR COMPL.')]
class AsignacionComplementaria extends Component
{
    use WithPagination;

    // Modal: SC seleccionada para detalle
    public $solicitud_detalle_id = null;

    // Filtros
    public $mandante_id = '';
    public $contratista_id = '';
    public $dependencia_id = '';
    public $numero_contrato = '';
    public $searchFolio = '';
    public $anio = '';
    public $mes = '';
    public $estado = '';

    // Asignación por SC: [sc_id => auditor_id]
    public $auditores_seleccionados = [];

    protected $queryString = [
        'mandante_id'    => ['except' => ''],
        'contratista_id' => ['except' => ''],
        'dependencia_id' => ['except' => ''],
        'numero_contrato'=> ['except' => ''],
        'searchFolio'    => ['except' => ''],
        'anio'           => ['except' => ''],
        'mes'            => ['except' => ''],
        'estado'         => ['except' => ''],
    ];

    public function mount()
    {
        $this->anio = date('Y');
    }

    public function updatedMandanteId()   { $this->contratista_id = ''; $this->dependencia_id = ''; $this->numero_contrato = ''; $this->resetPage(); }
    public function updatedContratistaId(){ $this->dependencia_id = ''; $this->numero_contrato = ''; $this->resetPage(); }
    public function updatedDependenciaId(){ $this->resetPage(); }
    public function updatedNumeroContrato(){ $this->resetPage(); }
    public function updatedSearchFolio()  { $this->resetPage(); }
    public function updatedAnio()         { $this->resetPage(); }
    public function updatedMes()          { $this->resetPage(); }
    public function updatedEstado()       { $this->resetPage(); }

    public function asignarAuditor($solicitudId)
    {
        $auditorId = $this->auditores_seleccionados[$solicitudId] ?? null;
        if (!$auditorId) {
            session()->flash('error', 'Debe seleccionar un auditor.');
            return;
        }
        $sol = SolicitudComplementaria::find($solicitudId);
        if ($sol && $sol->estado === 'EMITIDO') {
            session()->flash('error', 'No se puede reasignar auditor a un complementario ya emitido.');
            return;
        }
        if ($sol) {
            $sol->update(['auditor_id' => $auditorId, 'estado' => 'EN_REVISION']);
            session()->flash('success', 'Auditor asignado. SC en revisión.');
            unset($this->auditores_seleccionados[$solicitudId]);
        }
    }

    public function quitarAuditor($solicitudId)
    {
        $sol = SolicitudComplementaria::find($solicitudId);
        if ($sol) {
            if ($sol->estado === 'EMITIDO') {
                session()->flash('error', 'No se puede quitar el auditor a un complementario ya emitido.');
                return;
            }
            $sol->update(['auditor_id' => null, 'estado' => 'ENVIADO']);
            session()->flash('success', 'Asignación eliminada. SC vuelve a ENVIADO.');
        }
    }

    public function verDetalle($id)
    {
        $this->solicitud_detalle_id = $id;
    }

    public function cerrarDetalle()
    {
        $this->solicitud_detalle_id = null;
        $this->motivo_devolucion = '';
    }

    public $motivo_devolucion = '';

    public function emitirSC($solicitudId)
    {
        $sol = SolicitudComplementaria::find($solicitudId);
        if ($sol && in_array($sol->estado, ['SOLUCIONADO', 'RECHAZADO'])) {
            $sol->update(['estado' => 'EMITIDO']);
            session()->flash('success', 'Complementario emitido correctamente.');
            $this->cerrarDetalle();
        }
    }

    public function devolverAlAuditor($solicitudId)
    {
        $this->validate([
            'motivo_devolucion' => 'required|min:10'
        ], [
            'motivo_devolucion.required' => 'Debe ingresar un motivo para la devolución.',
            'motivo_devolucion.min' => 'El motivo debe tener al menos 10 caracteres.'
        ]);

        $sol = SolicitudComplementaria::find($solicitudId);
        if ($sol && in_array($sol->estado, ['SOLUCIONADO', 'RECHAZADO'])) {
            $sol->update([
                'estado' => 'EN_REVISION',
                'motivo_devolucion' => $this->motivo_devolucion
            ]);
            session()->flash('success', 'Solicitud devuelta al auditor correctamente.');
            $this->cerrarDetalle();
        }
    }

    public function devolverAlAuditorRapido($solicitudId, $motivo)
    {
        if (strlen(trim($motivo)) < 10) {
            session()->flash('error', 'El motivo de devolución debe tener al menos 10 caracteres.');
            return;
        }

        $sol = SolicitudComplementaria::find($solicitudId);
        if ($sol && in_array($sol->estado, ['SOLUCIONADO', 'RECHAZADO'])) {
            $sol->update([
                'estado' => 'EN_REVISION',
                'motivo_devolucion' => trim($motivo)
            ]);
            session()->flash('success', 'Solicitud devuelta al auditor correctamente.');
        }
    }

    public function limpiarFiltros()
    {
        $this->reset(['mandante_id','contratista_id','dependencia_id','numero_contrato','searchFolio','mes','estado']);
        $this->anio = date('Y');
        $this->resetPage();
    }

    public function render()
    {
        // ── QUERY PRINCIPAL: 1 FILA = 1 CERTIFICADO ──────────────────────────
        $query = CarpetaVerificacion::with([
            'solicitudesComplementarias.items.contingencia.carpetaTrabajador.vinculacion.trabajador',
            'solicitudesComplementarias.auditor',
            'vinculacion.contratista',
            'vinculacion.dependencia',
            'vinculacion.unidadOrganizacional.mandante',
        ])->whereHas('solicitudesComplementarias'); // Solo certs con al menos 1 SC

        // ── FILTROS ───────────────────────────────────────────────────────────
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

        // Filtro por estado: filtra los certificados que tengan SCs en ese estado
        if ($this->estado && $this->estado !== 'TODOS') {
            $query->whereHas('solicitudesComplementarias', function($q) {
                if ($this->estado === 'REVISADO') {
                    $q->whereIn('estado', ['SOLUCIONADO', 'RECHAZADO', 'EMITIDO']);
                } elseif ($this->estado === 'POR_ASIGNAR') {
                    $q->where('estado', 'ENVIADO');
                } elseif ($this->estado === 'ASIGNADO') {
                    $q->where('estado', 'EN_REVISION');
                } else {
                    $q->where('estado', $this->estado);
                }
            });
        }

        if ($this->searchFolio) {
            $s = $this->searchFolio;
            $query->where(function($q) use ($s) {
                $q->where('id', 'like', "%{$s}%")
                  ->orWhereHas('vinculacion', function($sq) use ($s) {
                      $sq->where('id_registro', 'like', "%{$s}%")
                         ->orWhere('numero_contrato', 'like', "%{$s}%");
                  })
                  ->orWhereHas('solicitudesComplementarias', function($sq) use ($s) {
                      $sq->where('folio', 'like', "%{$s}%");
                  })
                  ->orWhereHas('solicitudesComplementarias.items.contingencia', function($sq) use ($s) {
                      $sq->where('codigo', 'like', "%{$s}%");
                  })
                  ->orWhereHas('vinculacion.contratista', function($sq) use ($s) {
                      $sq->where('razon_social', 'like', "%{$s}%");
                  });
            });
        }

        $certificados = $query->orderBy('anio', 'desc')->orderBy('mes', 'desc')->paginate(10);

        // ── LISTAS PARA FILTROS ───────────────────────────────────────────────
        $mandantes = Mandante::orderBy('razon_social')->get();
        $contratistas = [];
        $dependencias = [];
        $contratos = [];

        if ($this->mandante_id) {
            $contratistas = Contratista::whereHas('vinculaciones.unidadOrganizacional', function($q) {
                $q->where('mandante_id', $this->mandante_id);
            })->orderBy('razon_social')->get();

            $dep = \App\Models\Dependencia::whereHas('vinculaciones.unidadOrganizacional', function($q) {
                $q->where('mandante_id', $this->mandante_id);
            });
            if ($this->contratista_id) {
                $dep->whereHas('vinculaciones', function($q) { $q->where('contratista_id', $this->contratista_id); });
            }
            $dependencias = $dep->orderBy('nombre')->get();
        }

        if ($this->contratista_id) {
            $cq = \App\Models\ContratistaUnidadOrganizacional::where('contratista_id', $this->contratista_id);
            if ($this->mandante_id) {
                $cq->whereHas('unidadOrganizacional', function($q) { $q->where('mandante_id', $this->mandante_id); });
            }
            $contratos = $cq->whereNotNull('numero_contrato')->distinct()->pluck('numero_contrato');
        }

        $auditores = User::role('Verifica_Auditor')->orderBy('name')->get();

        // ── CONTADORES ────────────────────────────────────────────────────────
        // Pendientes (Por Asignar + Asignados + Solucionados esperando emisión)
        $contPending = CarpetaVerificacion::whereHas('solicitudesComplementarias', function($q) {
            $q->whereIn('estado', ['ENVIADO', 'EN_REVISION', 'SOLUCIONADO']);
        })->count();

        // Revisados (Emitidos + Rechazados)
        $contRevisados = CarpetaVerificacion::whereHas('solicitudesComplementarias', function($q) {
            $q->whereIn('estado', ['EMITIDO', 'RECHAZADO']);
        })->whereDoesntHave('solicitudesComplementarias', function($q) {
            $q->whereIn('estado', ['ENVIADO', 'EN_REVISION', 'SOLUCIONADO']);
        })->count();

        // ── DETALLE MODAL ─────────────────────────────────────────────────────
        $solicitudDetalle = null;
        if ($this->solicitud_detalle_id) {
            $solicitudDetalle = SolicitudComplementaria::with([
                'vinculacion.contratista',
                'vinculacion.dependencia',
                'items.contingencia.carpetaTrabajador.vinculacion.trabajador',
                'carpeta',
                'documentos.requisito',
                'auditor',
            ])->find($this->solicitud_detalle_id);
        }

        return view('livewire.supervisor.asignacion-complementaria', [
            'certificados'    => $certificados,
            'mandantes'       => $mandantes,
            'contratistas'    => $contratistas,
            'dependencias'    => $dependencias,
            'contratos'       => $contratos,
            'auditores'       => $auditores,
            'solicitudDetalle'=> $solicitudDetalle,
            'contPending'     => $contPending,
            'contRevisados'   => $contRevisados,
        ])->layout('layouts.app');
    }
}
