<?php

namespace App\Livewire\Supervisor;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\CarpetaVerificacion;
use App\Models\RequisitoVerificacion;
use App\Models\Mandante;
use App\Models\Contratista;
use App\Models\User;
use App\Exports\DotacionPeriodoExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\DescargaContextualTrait;

use Livewire\Attributes\Title;

use App\Traits\ValidatesFileUpload;

#[Title('SUPERVISOR VERIF.')]
class AsignacionVerificacion extends Component
{
    use WithPagination, WithFileUploads, DescargaContextualTrait, ValidatesFileUpload;

    // Filtros
    public $mandante_id = '';
    public $contratista_id = '';
    public $anio = '';
    public $mes = '';
    public $fecha_envio_desde = '';
    public $fecha_envio_hasta = '';
    public $estado_plazo = ''; // DENTRO_PLAZO, FUERA_PLAZO
    public $estado_revision = ''; // PENDIENTE_ASIGNAR, ASIGNADO, etc.
    public $filtro_contingencia = ''; // OBSERVACIONES, CONTINGENCIAS, RETENIBLES, NO_RETENIBLES
    public $analista_id = ''; // Filtro por analista
    public $auditor_id = ''; // Filtro por auditor
    public $filtro_id_registro = '';
    public $filtro_ia = ''; // IA_OK, IA_PENDIENTE

    // Para asignación
    public $carpeta_seleccionada_id = null;
    public $analistas_seleccionados = [];
    public $auditores_seleccionados = [];

    // Para ver documentos
    public $carpeta_detalle_id = null;
    public $modo_edicion = false;
    public $archivos = [];
    public $trabajadores_periodo = []; // Snapshot de trabajadores para el periodo
    public $showModalDevolverAuditor = false;
    public $motivoDevolverAuditor = '';
    public $carpeta_devolver_id = null;

    protected $queryString = [
        'mandante_id' => ['except' => ''],
        'contratista_id' => ['except' => ''],
        'anio' => ['except' => ''],
        'mes' => ['except' => ''],
        'fecha_envio_desde' => ['except' => ''],
        'fecha_envio_hasta' => ['except' => ''],
        'estado_plazo' => ['except' => ''],
        'estado_revision' => ['except' => ''],
        'analista_id' => ['except' => ''],
        'auditor_id' => ['except' => ''],
        'filtro_id_registro' => ['except' => ''],
        'filtro_ia' => ['except' => ''],
    ];

    public function mount()
    {
        $this->anio = date('Y');
    }

    public function asignarAuditor($carpetaId)
    {
        $auditorId = $this->auditores_seleccionados[$carpetaId] ?? null;

        if (!$auditorId) {
            session()->flash('error', 'Debe seleccionar un auditor.');
            return;
        }

        $carpeta = CarpetaVerificacion::find($carpetaId);
        if (!$carpeta || $carpeta->estado_revision === 'EMITIDO') {
            session()->flash('error', 'Carpeta no encontrada o ya emitida.');
            return;
        }

        $carpeta->update([
            'auditor_id' => $auditorId,
            'fecha_auditoria' => null, 
        ]);

        session()->flash('success', 'Auditor asignado correctamente.');
        
        // Limpiar selección
        unset($this->auditores_seleccionados[$carpetaId]);
    }

    public function updatingMandanteId()
    {
        $this->resetPage();
        $this->contratista_id = '';
    }

    public function updatingContratistaId()
    {
        $this->resetPage();
    }

    public function updatingAnio()
    {
        $this->resetPage();
    }

    public function updatingMes()
    {
        $this->resetPage();
    }

    public function updatingEstadoPlazo()
    {
        $this->resetPage();
    }

    public function updatingEstadoRevision()
    {
        $this->resetPage();
    }

    public function updatingAnalistaId()
    {
        $this->resetPage();
    }

    public function updatingAuditorId()
    {
        $this->resetPage();
    }

    public function updatingFiltroIdRegistro()
    {
        $this->resetPage();
    }

    public function verDetalle($carpetaId)
    {
        $this->carpeta_detalle_id = $carpetaId;
        $this->modo_edicion = false;
        $this->archivos = [];
    }

    public function activarEdicion($carpetaId)
    {
        $carpeta = CarpetaVerificacion::find($carpetaId);
        if ($carpeta && in_array($carpeta->estado_revision, ['PARA_EMITIR', 'EMITIDO'])) {
            session()->flash('error', 'No se puede editar un periodo en estado ' . $carpeta->estado_revision . '.');
            return;
        }
        $this->carpeta_detalle_id = $carpetaId;
        $this->modo_edicion = true;
        $this->archivos = [];
    }

    public function cerrarDetalle()
    {
        $this->carpeta_detalle_id = null;
        $this->modo_edicion = false;
        $this->archivos = [];
        $this->trabajadores_periodo = [];
    }

    public function limpiarFiltros()
    {
        $this->reset(['mandante_id', 'contratista_id', 'mes', 'fecha_envio_desde', 'fecha_envio_hasta', 'estado_plazo', 'estado_revision', 'analista_id', 'auditor_id', 'filtro_id_registro']);
        $this->anio = date('Y');
        $this->resetPage();
    }

    public function descargarDocumentosFiltrados()
    {
        $query = CarpetaVerificacion::select('carpetas_verificacion.*')
            ->join('contratista_unidad_organizacional as cuo', 'cuo.id', '=', 'carpetas_verificacion.contratista_unidad_organizacional_id')
            ->leftJoin('unidades_organizacionales_mandante as uo', 'uo.id', '=', 'cuo.unidad_organizacional_mandante_id')
            ->leftJoin('dependencias as dep', 'dep.id', '=', 'cuo.dependencia_id')
            ->where('carpetas_verificacion.estado', 'ENVIADO')
            ->has('vinculacion');

        if ($this->mandante_id) {
            $query->whereHas('vinculacion.unidadOrganizacional', function ($q) {
                $q->where('mandante_id', $this->mandante_id);
            });
        }
        if ($this->contratista_id) {
            $query->whereHas('vinculacion', function ($q) {
                $q->where('contratista_id', $this->contratista_id);
            });
        }
        if ($this->anio) {
            $query->where('carpetas_verificacion.anio', $this->anio);
        }
        if ($this->mes) {
            $query->where('carpetas_verificacion.mes', $this->mes);
        }
        if ($this->filtro_id_registro) {
            $query->where('cuo.id_registro', 'LIKE', '%' . $this->filtro_id_registro . '%');
        }
        if ($this->estado_plazo) {
            $query->where('carpetas_verificacion.tipo_envio', $this->estado_plazo);
        }
        if ($this->estado_revision) {
            $query->where('carpetas_verificacion.estado_revision', $this->estado_revision);
        }
        if ($this->filtro_contingencia) {
            if ($this->filtro_contingencia === 'OBSERVACIONES') {
                $query->where(function($q) {
                    $q->whereNotNull('carpetas_verificacion.fin_observaciones_json')
                      ->orWhereHas('trabajadoresVerificados', function($q2) {
                          $q2->whereNotNull('observaciones');
                      });
                });
            } elseif ($this->filtro_contingencia === 'RETENIBLES') {
                $query->whereHas('trabajadoresVerificados.contingencias', function($q) {
                    $q->where('es_retenible', true);
                });
            } elseif ($this->filtro_contingencia === 'NO_RETENIBLES') {
                $query->whereHas('trabajadoresVerificados.contingencias', function($q) {
                    $q->where('es_retenible', false);
                });
            }
        }
        if ($this->analista_id) {
            $query->where('carpetas_verificacion.analista_id', $this->analista_id);
        }
        if ($this->auditor_id) {
            $query->where('carpetas_verificacion.auditor_id', $this->auditor_id);
        }
        if ($this->fecha_envio_desde) {
            $query->whereDate('carpetas_verificacion.fecha_envio', '>=', $this->fecha_envio_desde);
        }
        if ($this->fecha_envio_hasta) {
            $query->whereDate('carpetas_verificacion.fecha_envio', '<=', $this->fecha_envio_hasta);
        }
        if ($this->filtro_ia) {
            $query->where('carpetas_verificacion.ia_datos_extraidos', $this->filtro_ia === 'IA_OK');
        }

        $carpetasId = $query->pluck('carpetas_verificacion.id')->toArray();
        return $this->procesarDescargaContextual($carpetasId, "Asignacion_Supervisor");
    }

    public function asignarAnalista($carpetaId)
    {
        $analistaId = $this->analistas_seleccionados[$carpetaId] ?? null;

        if (!$analistaId) {
            session()->flash('error', 'Debe seleccionar un analista.');
            return;
        }

        $carpeta = CarpetaVerificacion::find($carpetaId);
        if ($carpeta && $carpeta->estado_revision !== 'EMITIDO') {
            $carpeta->update([
                'analista_id' => $analistaId,
                'supervisor_id' => auth()->id(),
                'fecha_asignacion' => now(),
                'estado_revision' => 'ASIGNADO',
            ]);

            session()->flash('success', 'Analista asignado correctamente.');
            unset($this->analistas_seleccionados[$carpetaId]);
        }
    }

    public function quitarAnalista($carpetaId)
    {
        $carpeta = CarpetaVerificacion::find($carpetaId);
        if ($carpeta && $carpeta->estado_revision !== 'EMITIDO') {
            $carpeta->update([
                'analista_id' => null,
                'supervisor_id' => null,
                'fecha_asignacion' => null,
                'estado_revision' => 'PENDIENTE_ASIGNAR',
            ]);
            unset($this->analistas_seleccionados[$carpetaId]);
            session()->flash('success', 'Asignación de analista eliminada.');
        }
    }

    public function quitarAuditor($carpetaId)
    {
        $carpeta = CarpetaVerificacion::find($carpetaId);
        if ($carpeta && $carpeta->estado_revision !== 'EMITIDO') {
            $carpeta->update([
                'auditor_id' => null,
                'fecha_auditoria' => null,
            ]);
            unset($this->auditores_seleccionados[$carpetaId]);
            session()->flash('success', 'Asignación de auditor eliminada.');
        }
    }

    // ============================================================
    // DEVOLVER AL AUDITOR
    // ============================================================

    public function abrirModalDevolverAuditor($id)
    {
        $this->carpeta_devolver_id = $id;
        $this->motivoDevolverAuditor = '';
        $this->showModalDevolverAuditor = true;
    }

    public function cerrarModalDevolverAuditor()
    {
        $this->showModalDevolverAuditor = false;
        $this->motivoDevolverAuditor = '';
        $this->carpeta_devolver_id = null;
    }

    public function devolverAlAuditor()
    {
        $this->validate([
            'motivoDevolverAuditor' => 'required|string|min:5'
        ], [
            'motivoDevolverAuditor.required' => 'El motivo de devolución es obligatorio.',
            'motivoDevolverAuditor.min' => 'El motivo debe tener al menos 5 caracteres.'
        ]);

        $carpeta = CarpetaVerificacion::find($this->carpeta_devolver_id);
        if (!$carpeta || $carpeta->estado_revision !== 'PARA_EMITIR') {
            session()->flash('error', 'Periodo no válido para devolución.');
            return;
        }

        // Preparamos el mensaje de devolución para el Auditor
        $mensaje = "\n[DEVOLUCIÓN POR SUPERVISOR/EMISOR " . now()->format('d/m/Y H:i') . "]\nMOTIVO: " . $this->motivoDevolverAuditor . "\n" . str_repeat('-', 40) . "\n";
        
        // Actualizamos observaciones del auditor para que lo vea
        $nuevasObs = $mensaje . ($carpeta->observaciones_auditor ?? '');

        $carpeta->update([
            'estado_revision'      => 'REVISADO', // Vuelve al estado "Por Auditar" del auditor
            'fecha_auditoria'      => null,
            'observaciones_auditor' => $nuevasObs,
        ]);

        $this->cerrarModalDevolverAuditor();
        session()->flash('success', 'Periodo devuelto al Auditor correctamente.');
    }

    private function getNombreMes($mes)
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        return $meses[$mes] ?? '';
    }

    public function revertirEnvio($carpetaId)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user->hasAnyRole(['OVAL_Admin', 'Verifica_Supervisor', 'Verifica_Emisor', 'ASEM_Admin'])) {
            return;
        }

        $carpeta = CarpetaVerificacion::find($carpetaId);
        
        // Regla de Negocio: No se puede abrir un certificado ya emitido.
        if ($carpeta && $carpeta->estado === 'ENVIADO' && $carpeta->estado_revision !== 'EMITIDO') {
            $carpeta->update([
                'estado' => 'EN PROGRESO', // Vuelve a estado inicial de carga para el contratista
                'fecha_envio' => null,
                'analista_id' => null,
                'auditor_id' => null,
                'fecha_asignacion' => null,
                'estado_revision' => 'PENDIENTE_ASIGNAR',
                'tipo_envio' => null,
                'fecha_emision_asignada' => null,
                'fecha_inicio_revision' => null,
                'fecha_fin_revision' => null,
                'fecha_auditoria' => null,
            ]);

            session()->flash('success', 'El periodo ha sido ABIERTO. El contratista ahora puede rectificarlo.');
        }
    }

    public function emitirCertificado($carpetaId)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user->hasAnyRole(['OVAL_Admin', 'Verifica_Supervisor', 'Verifica_Emisor', 'ASEM_Admin'])) {
            return;
        }

        $carpeta = CarpetaVerificacion::find($carpetaId);
        
        if ($carpeta && $carpeta->estado_revision === 'PARA_EMITIR') {
            // ── SELLADO FINAL EN PIEDRA ──
            // Antes de cerrar, refrescamos los snapshots con la información más actual del maestro.
            // Si el trabajador ya no existe (finiquitado), conservamos el snapshot preventivo.
            $trabajadores = $carpeta->trabajadoresVerificados()->with('vinculacion.trabajador', 'vinculacion.cargoMandante')->get();
            foreach ($trabajadores as $vt) {
                $vinc = $vt->vinculacion;
                if ($vinc && $vinc->trabajador) {
                    $vt->update([
                        'snapshot_rut'            => $vinc->trabajador->rut,
                        'snapshot_nombres'        => $vinc->trabajador->nombre_completo,
                        'snapshot_cargo'          => $vinc->cargoMandante?->nombre_cargo ?? 'CARGO NO REGISTRADO',
                        'snapshot_fecha_ingreso'  => $vinc->fecha_ingreso_vinculacion,
                        'snapshot_fecha_contrato' => $vinc->fecha_contrato,
                    ]);
                }
            }

            $carpeta->update([
                'estado_revision' => 'EMITIDO',
                'fecha_emision' => now(),
            ]);

            // Finalizar trabajadores: Todo lo que quedó pendiente se considera VERIFICADO al emitir
            $carpeta->trabajadoresVerificados()
                ->where('estado_revision', 'PENDIENTE')
                ->update(['estado_revision' => 'VERIFICADO']);

            // --- CONSOLIDACIÓN DE ANARQUÍA (Saneamiento Global) ---
            $desvinculados = $carpeta->trabajadoresVerificados()
                ->whereIn('estado_revision', ['FINIQUITADO', 'CESACION_PRINCIPAL', 'RECONOCIMIENTO_ANTIGUEDAD'])
                ->with('vinculacion')
                ->get();

            foreach ($desvinculados as $dv) {
                if ($dv->vinculacion && $dv->vinculacion->trabajador_id) {
                    $this->consolidarReserva($dv->vinculacion->trabajador_id);
                }
            }

            session()->flash('success', '¡Certificado EMITIDO exitosamente! El periodo ha sido cerrado y la dotación saneada.');
        }
    }

    /**
     * Saneamiento Global Agresivo: Asegura que un trabajador sólo tenga
     * registros útiles en la tabla operacional.
     * 1. Si está activo: Borra todas sus reservas (inactivas).
     * 2. Si es reserva: Deja exactamente UNA línea limpia (NULL en lugar/contrato).
     */
    private function consolidarReserva($trabajadorId)
    {
        $vinculaciones = \App\Models\TrabajadorVinculacion::where('trabajador_id', $trabajadorId)->get();
        if ($vinculaciones->isEmpty()) return;

        $activas = $vinculaciones->where('is_active', true);

        if ($activas->isNotEmpty()) {
            // El trabajador está ACTIVO. No necesita registros de reserva colgando.
            \App\Models\TrabajadorVinculacion::where('trabajador_id', $trabajadorId)
                ->where('is_active', false)
                ->delete();
        } else {
            // El trabajador NO tiene vinculaciones activas. Consolidamos en UNA sola reserva global.
            // Mantenemos la más reciente (mayor ID) como ancla.
            $anchor = $vinculaciones->sortByDesc('id')->first();
            
            $anchor->update([
                'unidad_organizacional_mandante_id' => null,
                'dependencia_id' => null,
                'numero_contrato' => null,
                'is_active' => false
            ]);

            // Purga física de todas las demás vinculaciones inactivas redundantes
            \App\Models\TrabajadorVinculacion::where('trabajador_id', $trabajadorId)
                ->where('id', '!=', $anchor->id)
                ->delete();
        }
    }

    public function eliminarDocumento($docId)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user->hasAnyRole(['OVAL_Admin', 'Verifica_Supervisor', 'Verifica_Emisor', 'ASEM_Admin'])) {
            return;
        }

        $doc = \App\Models\DocumentoVerificacion::with('carpeta')->find($docId);
        if ($doc) {
            if (in_array($doc->carpeta->estado_revision, ['PARA_EMITIR', 'EMITIDO'])) {
                session()->flash('error_modal', 'No se pueden eliminar documentos de un periodo en estado ' . $doc->carpeta->estado_revision . '.');
                return;
            }
            // Borrado seguro usando el Trait
            $this->deleteDocumentFile($doc->path, (bool)$doc->is_encrypted);
            $doc->delete();
            session()->flash('success_modal', 'Documento eliminado exitosamente.');
        }
    }

    public function updatedArchivos($value, $name)
    {
        $parts = explode('.', $name);
        if (count($parts) >= 1) {
            $requisitoId = end($parts);
            $this->subirArchivos($requisitoId);
        }
    }

    public function subirArchivos($requisitoId)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user->hasAnyRole(['OVAL_Admin', 'Verifica_Supervisor', 'Verifica_Emisor', 'ASEM_Admin'])) {
            return;
        }

        if (!isset($this->archivos[$requisitoId]) || empty($this->archivos[$requisitoId])) {
            return;
        }

        try {
            $this->validate([
                'archivos.' . $requisitoId . '.*' => $this->getFileValidationRule('verificacion'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if (isset($this->archivos[$requisitoId])) {
                foreach ($this->archivos[$requisitoId] as $file) {
                    $this->validateSecureFile($file, 'verificacion', 'SUPERVISOR_VERIFICACION');
                }
            }
            throw $e;
        }

        $carpeta = CarpetaVerificacion::with('vinculacion.contratista')->find($this->carpeta_detalle_id);
        if (!$carpeta || !$carpeta->vinculacion || in_array($carpeta->estado_revision, ['PARA_EMITIR', 'EMITIDO'])) {
            session()->flash('error_modal', 'No se pueden subir documentos a un periodo en estado ' . ($carpeta->estado_revision ?? 'DESCONOCIDO') . '.');
            return;
        }

        $idRegistro = $carpeta->vinculacion->id_registro ?: $carpeta->vinculacion->contratista->id;
        $mesPad = str_pad($carpeta->mes, 2, '0', STR_PAD_LEFT);
        $periodo = "{$mesPad}_{$carpeta->anio}";
        $requisito = RequisitoVerificacion::find($requisitoId);
        $codigoDoc = $requisito && $requisito->codigo ? $requisito->codigo : 'DOC';

        $existingCount = \App\Models\DocumentoVerificacion::where('carpeta_verificacion_id', $carpeta->id)
            ->where('requisito_verificacion_id', $requisitoId)
            ->count();

        foreach ($this->archivos[$requisitoId] as $file) {
            $existingCount++;
            $ext = $file->getClientOriginalExtension() ?: 'pdf';
            $nombreLimpio = strtoupper("{$idRegistro}-{$periodo}-{$codigoDoc}-{$existingCount}.{$ext}");

            // ── ESCUDO CRIPTOGRÁFICO ──────────────────────────────────────
            $storageResult = $this->encryptAndStoreFile($file, 'verificacion/' . $carpeta->id, 'SUPERVISOR_VERIFICACION');
            
            \App\Models\DocumentoVerificacion::create([
                'carpeta_verificacion_id' => $carpeta->id,
                'requisito_verificacion_id' => $requisitoId,
                'path' => $storageResult['ruta_archivo'],
                'nombre_original' => $nombreLimpio,
                'is_encrypted' => $storageResult['is_encrypted'],
            ]);
        }

        $this->archivos[$requisitoId] = [];
        session()->flash('success_modal', 'Documento(s) cargado(s) exitosamente.');
    }

    public function inicializarNominaVerificada($carpeta)
    {
        // 1. Obtener dotación actual (VIGENTE) según filtros históricos
        $pStart = \Carbon\Carbon::create($carpeta->anio, $carpeta->mes, 1)->startOfMonth();
        $pEnd   = $pStart->copy()->endOfMonth();

        $vigentes = \App\Models\TrabajadorVinculacion::where('unidad_organizacional_mandante_id', $carpeta->vinculacion->unidad_organizacional_mandante_id)
            ->where('dependencia_id', $carpeta->vinculacion->dependencia_id)
            ->where(function($q) use ($carpeta) {
                if ($carpeta->vinculacion->numero_contrato) {
                    $q->where('numero_contrato', $carpeta->vinculacion->numero_contrato);
                }
            })
            ->whereHas('trabajador', function ($q) use ($carpeta) {
                $q->where('contratista_id', $carpeta->vinculacion->contratista_id);
            })
            ->where('fecha_ingreso_vinculacion', '<=', $pEnd)
            ->where(function($sq) use ($pStart) {
                $sq->whereNull('fecha_desactivacion')
                   ->orWhere('fecha_desactivacion', '>=', $pStart);
            })
            ->get();

        foreach ($vigentes as $v) {
            $carpeta->trabajadoresVerificados()->create([
                'trabajador_vinculacion_id' => $v->id,
                'tipo_registro' => 'VIGENTE',
                'estado_revision' => 'PENDIENTE'
            ]);
        }

        // 2. Buscar último periodo verificado (AUDITADO o REVISADO)
        $ultimaCarpeta = \App\Models\CarpetaVerificacion::where('contratista_unidad_organizacional_id', $carpeta->contratista_unidad_organizacional_id)
            ->where('id', '!=', $carpeta->id)
            ->where(function($q) use ($carpeta) {
                $q->where('anio', '<', $carpeta->anio)
                  ->orWhere(function($sq) use ($carpeta) {
                      $sq->where('anio', $carpeta->anio)
                         ->where('mes', '<', $carpeta->mes);
                  });
            })
            ->whereIn('estado_revision', ['REVISADO', 'AUDITADO'])
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->with('trabajadoresVerificados')
            ->first();

        if ($ultimaCarpeta) {
            $baseAnterior = $ultimaCarpeta->trabajadoresVerificados()
                ->whereNotIn('estado_revision', ['FINIQUITADO', 'MOVIDO'])
                ->get();

            foreach ($baseAnterior as $tAnt) {
                if (!$vigentes->contains('id', $tAnt->trabajador_vinculacion_id)) {
                    $carpeta->trabajadoresVerificados()->create([
                        'trabajador_vinculacion_id' => $tAnt->trabajador_vinculacion_id,
                        'tipo_registro' => 'ARRASTRE',
                        'estado_revision' => 'PENDIENTE'
                    ]);
                }
            }
        }
    }

    public function cambiarEstadoTrabajadorPeriodo($id, $nuevoEstado, $destinoId = null)
    {
        $reg = \App\Models\CarpetaVerificacionTrabajador::find($id);
        if ($reg) {
            $reg->update([
                'estado_revision' => $nuevoEstado,
                'destino_trabajador_vinculacion_id' => ($nuevoEstado === 'MOVIDO') ? $destinoId : null
            ]);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Estado del trabajador actualizado.']);
        }
    }

    public function exportarDotacion($carpetaId)
    {
        $carpeta = CarpetaVerificacion::find($carpetaId);
        if (!$carpeta) return;

        $nombreArchivo = 'dotacion_' . ($carpeta->vinculacion->contratista->rut ?? 'sin-rut') . '_' . $carpeta->nombre_mes . '_' . $carpeta->anio . '.xlsx';

        return Excel::download(new DotacionPeriodoExport($carpetaId), $nombreArchivo);
    }

    public function getDestinosPosibles($trabajadorVinculacionId)
    {
        $vinculacionOrigen = \App\Models\TrabajadorVinculacion::find($trabajadorVinculacionId);
        if (!$vinculacionOrigen) return collect();

        return \App\Models\TrabajadorVinculacion::with(['unidadOrganizacional', 'dependencia'])
            ->where('trabajador_id', $vinculacionOrigen->trabajador_id)
            ->where('id', '!=', $trabajadorVinculacionId)
            ->whereNull('fecha_desactivacion')
            ->get();
    }

    public function render()
    {
        // Obtener mandantes para filtro
        $mandantes = Mandante::orderBy('razon_social')->get();

        // Obtener contratistas según mandante seleccionado
        $contratistas = collect();
        if ($this->mandante_id) {
            $contratistas = Contratista::whereHas('unidadesOrganizacionalesMandante', function ($q) {
                $q->where('mandante_id', $this->mandante_id);
            })->orderBy('razon_social')->get();
        }

        // Obtener analistas disponibles
        $analistas = User::role('Verifica_Analista')->orderBy('name')->get();
        // Obtener auditores disponibles
        $auditores = User::role('Verifica_Auditor')->orderBy('name')->get();

        // Query de carpetas enviadas
        $query = CarpetaVerificacion::select('carpetas_verificacion.*', 'sv.contratista_padre_id')
            ->join('contratista_unidad_organizacional as cuo', 'cuo.id', '=', 'carpetas_verificacion.contratista_unidad_organizacional_id')
            ->leftJoin('unidades_organizacionales_mandante as uo', 'uo.id', '=', 'cuo.unidad_organizacional_mandante_id')
            ->leftJoin('dependencias as dep', 'dep.id', '=', 'cuo.dependencia_id')
            ->leftJoin('solicitudes_vinculacion as sv', function($join) {
                $join->on('sv.contratista_id', '=', 'cuo.contratista_id')
                     ->on('sv.mandante_id', '=', \Illuminate\Support\Facades\DB::raw('COALESCE(uo.mandante_id, dep.mandante_id)'))
                     ->where('sv.estado', '=', 'APROBADA')
                     ->where('sv.tipo_solicitud', '=', 'SUBCONTRATISTA');
            })
            ->with([
                'vinculacion.contratista',
                'vinculacion.unidadOrganizacional.mandante',
                'vinculacion.dependencia',
                'analista',
                'supervisor',
                'auditor', 
            ])
            ->where('carpetas_verificacion.estado', 'ENVIADO')
            ->has('vinculacion');

        // Aplicar filtros
        if ($this->mandante_id) {
            $query->whereHas('vinculacion.unidadOrganizacional', function ($q) {
                $q->where('mandante_id', $this->mandante_id);
            });
        }

        if ($this->contratista_id) {
            $query->whereHas('vinculacion', function ($q) {
                $q->where('contratista_id', $this->contratista_id);
            });
        }

        if ($this->anio) {
            $query->where('carpetas_verificacion.anio', $this->anio);
        }

        if ($this->mes) {
            $query->where('carpetas_verificacion.mes', $this->mes);
        }

        if ($this->filtro_id_registro) {
            $query->where('cuo.id_registro', 'LIKE', '%' . $this->filtro_id_registro . '%');
        }

        if ($this->estado_plazo) {
            $query->where('carpetas_verificacion.tipo_envio', $this->estado_plazo);
        }

        if ($this->estado_revision) {
            $query->where('carpetas_verificacion.estado_revision', $this->estado_revision);
        }

        if ($this->filtro_contingencia) {
            if ($this->filtro_contingencia === 'OBSERVACIONES') {
                $query->where(function($q) {
                    $q->whereNotNull('carpetas_verificacion.fin_observaciones_json')
                      ->orWhereHas('trabajadoresVerificados', function($q2) {
                          $q2->whereNotNull('observaciones');
                      });
                });
            } elseif ($this->filtro_contingencia === 'RETENIBLES') {
                $query->whereHas('trabajadoresVerificados.contingencias', function($q) {
                    $q->where('es_retenible', true);
                });
            } elseif ($this->filtro_contingencia === 'NO_RETENIBLES') {
                $query->whereHas('trabajadoresVerificados.contingencias', function($q) {
                    $q->where('es_retenible', false);
                });
            }
        }

        if ($this->analista_id) {
            $query->where('carpetas_verificacion.analista_id', $this->analista_id);
        }

        if ($this->auditor_id) {
            $query->where('carpetas_verificacion.auditor_id', $this->auditor_id);
        }

        if ($this->fecha_envio_desde) {
            $query->whereDate('carpetas_verificacion.fecha_envio', '>=', $this->fecha_envio_desde);
        }

        if ($this->fecha_envio_hasta) {
            $query->whereDate('carpetas_verificacion.fecha_envio', '<=', $this->fecha_envio_hasta);
        }
        if ($this->filtro_ia) {
            $query->where('carpetas_verificacion.ia_datos_extraidos', $this->filtro_ia === 'IA_OK');
        }

        // Obtener carpetas
        $carpetasBase = $query->get();

        // Ordenamiento Jerárquico SIEMPRE (Global o por Filtro)
        $carpetas = $this->ordenarJerarquicamente($carpetasBase);

        // Paginación manual para colecciones jerárquicas
        $perPage = 50;
        $currentPage = $this->getPage();
        $carpetas = new \Illuminate\Pagination\LengthAwarePaginator(
            $carpetas->forPage($currentPage, $perPage),
            $carpetas->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Support\Facades\Request::url(), 'query' => \Illuminate\Support\Facades\Request::query()]
        );

        // Calcular contadores
        $totalPendientes = CarpetaVerificacion::where('estado', 'ENVIADO')
            ->where('estado_revision', 'PENDIENTE_ASIGNAR')
            ->count();

        $totalAsignados = CarpetaVerificacion::where('estado', 'ENVIADO')
            ->where('estado_revision', 'ASIGNADO')
            ->count();

        // Resumen de carga por analista (basado en lo filtrado)
        $carpetasConAnalista = $carpetasBase->whereNotNull('analista_id');

        $resumenAnalistas = $carpetasConAnalista
            ->groupBy('analista_id')
            ->map(function ($carpetasDelAnalista) {
                $analista = $carpetasDelAnalista->first()->analista;
                $empresasUnicas = $carpetasDelAnalista
                    ->pluck('vinculacion.contratista_id')
                    ->unique()
                    ->count();
                // Dotación total: suma de trabajadores vinculados por vinculación única
                $vinculacionesUnicas = $carpetasDelAnalista
                    ->pluck('vinculacion')
                    ->filter()
                    ->unique('id');
                $dotacionTotal = $vinculacionesUnicas->sum(
                    fn($v) => $v->trabajadores()->count()
                );
                return [
                    'analista'       => $analista,
                    'empresas'       => $empresasUnicas,
                    'carpetas'       => $carpetasDelAnalista->count(),
                    'dotacion_total' => $dotacionTotal,
                ];
            })
            ->sortByDesc('carpetas')
            ->values();

        // Resumen de carga por auditor (basado en lo filtrado)
        $carpetasConAuditor = $carpetasBase->whereNotNull('auditor_id');

        $resumenAuditores = $carpetasConAuditor
            ->groupBy('auditor_id')
            ->map(function ($carpetasDelAuditor) {
                $auditor = $carpetasDelAuditor->first()->auditor;
                $empresasUnicas = $carpetasDelAuditor
                    ->pluck('vinculacion.contratista_id')
                    ->unique()
                    ->count();
                // Dotación total: suma de trabajadores vinculados por vinculación única
                $vinculacionesUnicas = $carpetasDelAuditor
                    ->pluck('vinculacion')
                    ->filter()
                    ->unique('id');
                $dotacionTotal = $vinculacionesUnicas->sum(
                    fn($v) => $v->trabajadores()->count()
                );
                return [
                    'auditor'        => $auditor,
                    'empresas'       => $empresasUnicas,
                    'carpetas'       => $carpetasDelAuditor->count(),
                    'dotacion_total' => $dotacionTotal,
                ];
            })
            ->sortByDesc('carpetas')
            ->values();

        // Detalle de carpeta (Ver Docs)
        $carpetaDetalle         = null;
        $requisitosPorClasif    = collect();
        $documentosPorRequisito = collect();

        if ($this->carpeta_detalle_id) {
            $carpetaDetalle = CarpetaVerificacion::with([
                'vinculacion.contratista',
                'vinculacion.unidadOrganizacional.mandante',
                'vinculacion.dependencia',
                'documentos.requisito.clasificacion',
                'analista',
                'auditor',
            ])->find($this->carpeta_detalle_id);

            if ($carpetaDetalle) {
                $mandanteId = $carpetaDetalle->vinculacion->unidadOrganizacional->mandante_id ?? null;
                if ($mandanteId) {
                    $requisitosPorClasif = RequisitoVerificacion::where('mandante_id', $mandanteId)
                        ->where('is_active', true)
                        ->with('clasificacion')
                        ->orderBy('nombre')
                        ->get()
                        ->groupBy(fn ($r) => $r->clasificacion->nombre ?? 'Sin Clasificación');
                }
                $documentosPorRequisito = $carpetaDetalle->documentos->groupBy('requisito_verificacion_id');

                // Lógica de Nómina Verificada
                if ($carpetaDetalle->trabajadoresVerificados()->count() === 0) {
                    $this->inicializarNominaVerificada($carpetaDetalle);
                }

                $this->trabajadores_periodo = $carpetaDetalle->trabajadoresVerificados()
                    ->with(['vinculacion.trabajador', 'vinculacion.cargoMandante', 'destinoVinculacion.unidadOrganizacional'])
                    ->get();
            }
        }

        return view('livewire.supervisor.asignacion-verificacion', [
            'carpetas'               => $carpetas,
            'mandantes'              => $mandantes,
            'contratistas'           => $contratistas,
            'analistas'              => $analistas,
            'auditores'              => $auditores,
            'totalPendientes'        => $totalPendientes,
            'totalAsignados'         => $totalAsignados,
            'resumenAnalistas'       => $resumenAnalistas,
            'resumenAuditores'       => $resumenAuditores,
            'getNombreMes'           => fn($m) => $this->getNombreMes($m),
            'carpetaDetalle'         => $carpetaDetalle,
            'requisitosPorClasif'    => $requisitosPorClasif,
            'documentosPorRequisito' => $documentosPorRequisito,
            'trabajadoresPeriodo'    => $this->trabajadores_periodo,
        ])->layout('layouts.app');
    }

    protected function ordenarJerarquicamente($collection)
    {
        // 1. Agrupar por Periodo (Año-Mes)
        $gruposPorPeriodo = $collection->groupBy(fn($item) => 
            $item->anio . '-' . str_pad($item->mes, 2, '0', STR_PAD_LEFT)
        )->sortKeys();

        $resultadoFinal = collect();
        $contadorRaicesGlobal = 1;

        // Función recursiva para aplanar
        $aplanarArbol = function ($items, $prefijo = '') use (&$aplanarArbol, &$resultadoFinal, &$contadorRaicesGlobal) {
            $subContador = 1;
            foreach ($items as $item) {
                if ($prefijo === '') {
                    $item->correlativo_jerarquico = (string)$contadorRaicesGlobal;
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
            // Mapa de carpetas por Contratista ID para búsqueda flexible (Lógica SKILL)
            $byContratista = $carpetasDelPeriodo->groupBy(fn($c) => $c->vinculacion->contratista_id ?? 0);

            foreach ($carpetasDelPeriodo as $item) {
                $item->temporal_children = collect();
                $item->is_attached_to_parent = false;
            }

            // Construir árbol del periodo usando Lógica de la SKILL (Emparejamiento Flexible)
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

                    // Coincidencia de UO
                    if ($vP->unidad_organizacional_mandante_id == $vC->unidad_organizacional_mandante_id) $score += 10;
                    elseif ($vP->unidad_organizacional_mandante_id && $vC->unidad_organizacional_mandante_id) $score -= 50;
                    
                    // Coincidencia de Lugar (Dependencia)
                    if ($vP->dependencia_id == $vC->dependencia_id) $score += 10;
                    elseif ($vP->dependencia_id && $vC->dependencia_id) $score -= 20;
                    
                    // Coincidencia de Contrato
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

            // Aplanar raíces de este periodo
            $raicesDelPeriodo = $carpetasDelPeriodo->filter(fn($item) => !$item->is_attached_to_parent);
            $raicesDelPeriodo = $raicesDelPeriodo->sortBy(fn($item) => $item->vinculacion->contratista->razon_social ?? '');
            $aplanarArbol($raicesDelPeriodo);
        }

        return $resultadoFinal;
    }
}
