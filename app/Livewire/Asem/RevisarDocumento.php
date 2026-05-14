<?php

namespace App\Livewire\Asem;

use Livewire\Component;
use App\Models\DocumentoCargado;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\ReglaDocumental;
use App\Models\CargoMandante;
use App\Models\Trabajador;
use App\Models\TrabajadorVinculacion;
use App\Models\Nacionalidad;
use App\Jobs\ActualizarEstadoRecursoIndividual; // NUEVO

class RevisarDocumento extends Component
{
    public ?DocumentoCargado $documento = null;
    public array $documentosRelacionadosCargados = [];
    public ?string $pdfUrl = null;
    public $decision = null;
    public $isReadOnly = false;
    public $criteriosCumplidos = [];
    public $fechaVencimientoValidador;
    public $confirmaFechaVencimiento = false;
    public $fechaEmisionValidador;
    public $confirmaFechaEmision = false;
    public $motivoDevolucion;
    public $motivosRechazoCalculados = [];
    public array $criteriosParaMostrar = [];
    public ?User $usuarioAutenticado = null;

    public bool $esAnexoDeContrato = false;
    public ?DocumentoCargado $contratoObjetivo = null;
    public ?string $nuevaFechaVencimientoContrato = null;
    public $cargosDisponibles = [];
    public ?int $cargoSeleccionado = null;
    public $documentosRevalidablesDelTrabajador = [];
    public $documentosSeleccionadosParaRevalidar = [];
    public array $criteriosSubsanados = [];
    public array $motivosRechazoContrato = [];
    public ?string $cargoActualTrabajador = null;
    public ?string $observacionValidador = null;
    public array $subCriteriosSeleccionados = [];
    public $historicoDocumentos = [];
    
    public string $motivoRevalidacionIndividual = '';
    public bool $marcarComoErrorValidador = false;

    public bool $vencimientoIndefinido = false;

    public array $permisosRegla = [
        'ver_nacionalidad' => false,
        'modificar_nacionalidad' => false,
        'ver_fecha_nacimiento' => false,
        'modificar_fecha_nacimiento' => false,
    ];
    public array $trabajadorData = [
        'nacionalidad_id' => null,
        'fecha_nacimiento' => null,
    ];
    public $listaNacionalidades = [];

    public function mount($documentoId)
    {
        $this->usuarioAutenticado = Auth::user();
        
        $documento = DocumentoCargado::with([
            'mandante', 'contratista', 'entidad', 'validadorAsem', 'validadorMandante',
            'reglaDocumental' => function ($query) {
                $query->with([
                    'formatosDocumento',
                    'observacionesDocumento',
                    'documentosRelacionados',
                    'criteriosAsem' => function($q) {
                        $q->with(['criterioEvaluacion', 'subCriterio', 'textoRechazo', 'aclaracionCriterio']);
                    },
                    'criteriosMandante' => function($q) {
                        $q->with(['criterioEvaluacion', 'subCriterio', 'textoRechazo', 'aclaracionCriterio']);
                    }
                ]);
            }
        ])->find($documentoId);

        if (!$documento) { abort(404, 'Documento no encontrado.'); }

        $this->autorizarAcceso($documento);
        
        $this->criteriosParaMostrar = $documento->criterios_snapshot ?? [];
        
        foreach ($this->criteriosParaMostrar as $index => $criterio) {
            if (!empty($criterio['sub_criterios'])) {
                foreach ($criterio['sub_criterios'] as $sub) {
                    $this->subCriteriosSeleccionados[$index][$sub['id']] = true;
                }
            }
        }
        
        if ($this->usuarioAutenticado->isAsem()) {
            $this->montajeParaAsem($documento);
        } elseif ($this->usuarioAutenticado->isMandante()) {
            $this->montajeParaMandante($documento);
        }
        
        $this->documento = $documento;
        $this->pdfUrl = $this->documento->url;
        
        $this->fechaVencimientoValidador = $this->documento->fecha_vencimiento?->format('Y-m-d');
        $this->fechaEmisionValidador = $this->documento->fecha_emision?->format('Y-m-d');

        if (is_null($this->documento->fecha_vencimiento) && $this->documento->valida_vencimiento_snapshot) {
            $this->vencimientoIndefinido = true;
        }

        if ($this->documento->reglaDocumental?->documentosRelacionados && $this->documento->reglaDocumental->documentosRelacionados->isNotEmpty()) {
            foreach ($this->documento->reglaDocumental->documentosRelacionados as $docRel) {
                $docCargado = DocumentoCargado::where('entidad_id', $this->documento->entidad_id)
                    ->where('entidad_type', $this->documento->entidad_type)
                    ->whereHas('reglaDocumental', function ($query) use ($docRel) {
                        $query->where('nombre_documento_id', $docRel->id);
                    })
                    ->whereNotIn('estado_validacion', ['Archivado', 'Archivado-Revalidado'])
                    ->latest('created_at')
                    ->first();
                
                if ($docCargado) {
                    $this->documentosRelacionadosCargados[$docRel->id] = $docCargado;
                }
            }
        }

        if ($this->documento->entidad_type === Trabajador::class) {
            $vinculacion = TrabajadorVinculacion::with('cargoMandante:id,nombre_cargo')
                ->where('trabajador_id', $this->documento->entidad_id)
                ->where('unidad_organizacional_mandante_id', $this->documento->unidad_organizacional_id)
                ->where('is_active', true)
                ->first();
            
            if ($vinculacion && $vinculacion->cargoMandante) {
                $this->cargoActualTrabajador = $vinculacion->cargoMandante->nombre_cargo;
            }

            if ($this->documento->reglaDocumental) {
                $this->permisosRegla = [
                    'ver_nacionalidad' => (bool)$this->documento->reglaDocumental->permite_ver_nacionalidad_trabajador,
                    'modificar_nacionalidad' => (bool)$this->documento->reglaDocumental->permite_modificar_nacionalidad_trabajador,
                    'ver_fecha_nacimiento' => (bool)$this->documento->reglaDocumental->permite_ver_fecha_nacimiento_trabajador,
                    'modificar_fecha_nacimiento' => (bool)$this->documento->reglaDocumental->permite_modificar_fecha_nacimiento_trabajador,
                ];
            }

            if ($this->documento->entidad) {
                $this->trabajadorData['nacionalidad_id'] = $this->documento->entidad->nacionalidad_id;
                $this->trabajadorData['fecha_nacimiento'] = $this->documento->entidad->fecha_nacimiento?->format('Y-m-d');
            }

            if ($this->permisosRegla['modificar_nacionalidad']) {
                $this->listaNacionalidades = Nacionalidad::orderBy('nombre')->get();
            }
        }

        if ($this->documento->reglaDocumental?->nombre_documento_id === 100002) {
            $this->esAnexoDeContrato = true;
            $this->localizarYPrepararContratoObjetivo();
        }

        if ($this->documento->reglaDocumental?->mostrar_historico_documento) {
            $this->historicoDocumentos = DocumentoCargado::with(['validadorAsem', 'validadorMandante'])
                ->where('entidad_id', $this->documento->entidad_id)
                ->where('entidad_type', $this->documento->entidad_type)
                ->where('nombre_documento_snapshot', $this->documento->nombre_documento_snapshot)
                ->whereIn('estado_validacion', ['Revisado', 'Revisado-Revalidado', 'Archivado', 'Archivado-Revalidado'])
                ->where('id', '!=', $this->documento->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }
    }

    public function updatedVencimientoIndefinido($value)
    {
        if ($value) {
            $this->fechaVencimientoValidador = null;
            $this->confirmaFechaVencimiento = false;
            $this->resetErrorBag(['fechaVencimientoValidador', 'confirmaFechaVencimiento']);
        }
    }

    private function localizarYPrepararContratoObjetivo()
    {
        $this->contratoObjetivo = DocumentoCargado::where('entidad_id', $this->documento->entidad_id)
            ->where('entidad_type', $this->documento->entidad_type)
            ->whereHas('reglaDocumental', function ($query) {
                $query->where('nombre_documento_id', 100001);
            })
            ->whereNotIn('estado_validacion', ['Archivado', 'Archivado-Revalidado'])
            ->latest('created_at')
            ->first();

        if ($this->contratoObjetivo) {
            $this->nuevaFechaVencimientoContrato = $this->contratoObjetivo->fecha_vencimiento?->format('Y-m-d');

            $this->cargosDisponibles = CargoMandante::where('mandante_id', $this->documento->mandante_id)
                ->where('is_active', true)
                ->orderBy('nombre_cargo')
                ->get();
            
            $this->documentosRevalidablesDelTrabajador = DocumentoCargado::where('entidad_id', $this->documento->entidad_id)
                ->where('entidad_type', $this->documento->entidad_type)
                ->whereIn('resultado_validacion', ['Aprobado', 'Rechazado'])
                ->whereNotIn('estado_validacion', ['Archivado', 'Archivado-Revalidado'])
                ->where('id', '!=', $this->contratoObjetivo->id)
                ->get();

            if ($this->contratoObjetivo->resultado_validacion === 'Rechazado') {
                $motivos = json_decode($this->contratoObjetivo->motivo_rechazo ?? '[]', true);

                if (is_array($motivos) && !empty($motivos)) {
                    $this->motivosRechazoContrato = $motivos;
                } elseif (!empty($this->contratoObjetivo->observacion_rechazo)) {
                    $texto = $this->contratoObjetivo->observacion_rechazo;
                    $texto = str_replace('Motivos de rechazo:', '', $texto);
                    $lineas = explode("\n", $texto);
                    $motivosParseados = [];
                    foreach ($lineas as $linea) {
                        $lineaLimpia = trim(ltrim(trim($linea), '-'));
                        if (!empty($lineaLimpia)) {
                            $motivosParseados[] = $lineaLimpia;
                        }
                    }
                    $this->motivosRechazoContrato = $motivosParseados;
                }
            }
        }
    }

    private function autorizarAcceso(DocumentoCargado $documento)
    {
        $user = $this->usuarioAutenticado;

        if ($user->isAsem() && in_array($documento->estado_validacion, ['Sin Asignar', 'Asignado', 'Devuelto', 'Asignar-Revalidar', 'Asignado-Revalidar'])) {
            return;
        }

        if ($user->isMandante() && $documento->mandante_id === $user->mandante_id) {
            $estadosVisiblesParaMandante = [
                'Pendiente Validación Mandante',
                'Revisado',
                'Revisado-Revalidado',
                'Archivado',
                'Archivado-Revalidado'
            ];
            if (in_array($documento->estado_validacion, $estadosVisiblesParaMandante)) {
                return;
            }
        }
        
        if ($user->hasRole('ASEM_Admin')) {
            return;
        }
        
        abort(403, 'USER DOES NOT HAVE THE RIGHT ROLES.');
    }
    
    private function montajeParaAsem(DocumentoCargado &$documento)
    {
        $estadosActivosAsem = ['Sin Asignar', 'Asignado', 'Devuelto', 'Asignar-Revalidar', 'Asignado-Revalidar'];
        if (in_array($documento->estado_validacion, $estadosActivosAsem) && $documento->asem_validador_id !== $this->usuarioAutenticado->id) {
            $nuevoEstado = 'Asignado';
            if (str_contains($documento->estado_validacion, 'Revalidar')) {
                $nuevoEstado = 'Asignado-Revalidar';
            }
            $documento->update(['asem_validador_id' => $this->usuarioAutenticado->id, 'estado_validacion' => $nuevoEstado]);
            $documento->refresh();
            $documento->load('reglaDocumental');
        }

        $estadosRevisablesAsem = ['Asignado', 'Asignado-Revalidar'];
        if ($documento->resultado_validacion !== null || !in_array($documento->estado_validacion, $estadosRevisablesAsem)) {
            $this->isReadOnly = true;
        }
    }
    
    private function montajeParaMandante(DocumentoCargado &$documento)
    {
        if ($documento->estado_validacion === 'Pendiente Validación Mandante' && is_null($documento->mandante_validador_id)) {
            $documento->update(['mandante_validador_id' => $this->usuarioAutenticado->id]);
            $documento->refresh();
        } elseif ($documento->estado_validacion === 'Pendiente Validación Mandante' && $documento->mandante_validador_id !== $this->usuarioAutenticado->id) {
            $this->isReadOnly = true;
            session()->flash('info', 'Este documento ya está siendo revisado por otro validador de su empresa.');
        }

        if ($documento->resultado_validacion !== null) {
            $this->isReadOnly = true;
        }
    }

    public function seleccionarDecision($tipo)
    {
        $this->resetErrorBag();
        $this->decision = $tipo;

        if ($tipo === 'Rechazado') {
            $this->motivosRechazoCalculados = [];

            foreach ($this->criteriosParaMostrar as $index => $criterio) {
                $tieneSubCriterios = !empty($criterio['sub_criterios']);
                $faltantes = [];

                if ($tieneSubCriterios) {
                    // Con sub-criterios: calcular cuáles están desmarcados
                    foreach ($criterio['sub_criterios'] as $sub) {
                        if (empty($this->subCriteriosSeleccionados[$index][$sub['id']])) {
                            $faltantes[] = $sub['nombre'];
                        }
                    }
                    // Se rechaza si hay sub-criterios faltantes
                    // (el padre se marca automáticamente si todos los sub-criterios están marcados)
                    if (!empty($faltantes)) {
                        $baseRechazo = !empty($criterio['texto_rechazo'])
                            ? $criterio['texto_rechazo']
                            : ("No cumple con: " . ($criterio['criterio'] ?? "Criterio " . ($index + 1)));

                        $baseRechazo .= ": falta " . implode(", ", $faltantes);
                        $this->motivosRechazoCalculados[] = $baseRechazo;
                    }
                } else {
                    // Sin sub-criterios: se rechaza si el padre está desmarcado
                    if (empty($this->criteriosCumplidos[$index])) {
                        $baseRechazo = !empty($criterio['texto_rechazo'])
                            ? $criterio['texto_rechazo']
                            : ("No cumple con: " . ($criterio['criterio'] ?? "Criterio " . ($index + 1)));

                        $this->motivosRechazoCalculados[] = $baseRechazo;
                    }
                }
            }
        }
    }

    public function resetDecision()
    {
        $this->decision = null;
        $this->motivosRechazoCalculados = [];
        $this->resetErrorBag();
    }
    
    public function procesarDecision()
    {
        if ($this->isReadOnly) return;

        if ($this->usuarioAutenticado->isAsem()) {
            if ($this->esAnexoDeContrato) {
                $this->procesarDecisionAnexo();
            } else {
                if ($this->decision === 'Aprobado') $this->aprobarDocumentoAsem();
                elseif ($this->decision === 'Rechazado') $this->rechazarDocumentoFinal();
            }
        } elseif ($this->usuarioAutenticado->isMandante()) {
            if ($this->decision === 'Aprobado') $this->aprobarDocumentoMandante();
            elseif ($this->decision === 'Rechazado') $this->rechazarDocumentoFinal();
        }
    }

    private function procesarDecisionAnexo()
    {
        DB::beginTransaction();
        try {
            if ($this->decision === 'Aprobado') {
                if ($this->nuevaFechaVencimientoContrato && $this->nuevaFechaVencimientoContrato != $this->contratoObjetivo->fecha_vencimiento?->format('Y-m-d')) {
                    $this->contratoObjetivo->update([
                        'fecha_vencimiento' => $this->nuevaFechaVencimientoContrato,
                        'es_vencimiento_modificado' => true,
                        'motivo_modificacion_vencimiento' => 'Actualizado por Anexo de Contrato ID: ' . $this->documento->id,
                    ]);
                }

                if ($this->cargoSeleccionado) {
                    TrabajadorVinculacion::where('trabajador_id', $this->documento->entidad_id)
                        ->where('unidad_organizacional_mandante_id', $this->documento->unidad_organizacional_id)
                        ->where('is_active', true)
                        ->update(['cargo_mandante_id' => $this->cargoSeleccionado]);
                }

                foreach ($this->documentosSeleccionadosParaRevalidar as $docId => $seleccionado) {
                    if ($seleccionado) {
                        $docParaRevalidar = DocumentoCargado::find($docId);
                        if ($docParaRevalidar) {
                            $docParaRevalidar->update(['estado_validacion' => 'Archivado-Revalidado']);
                            $clon = $docParaRevalidar->replicate(['resultado_validacion', 'asem_validador_id', 'fecha_validacion', 'observacion_interna_asem', 'observacion_rechazo', 'motivo_revalidacion', 'es_error_validador']);
                            $clon->estado_validacion = 'Asignar-Revalidar';
                            $clon->motivo_revalidacion = 'Revalidación solicitada por Anexo de Contrato ID: ' . $this->documento->id;
                            $clon->created_at = now();
                            $clon->updated_at = now();
                            $clon->save();
                        }
                    }
                }

                if ($this->contratoObjetivo->resultado_validacion === 'Rechazado') {
                    $motivosNoSubsanados = [];
                    foreach ($this->motivosRechazoContrato as $index => $motivo) {
                        if (empty($this->criteriosSubsanados[$index])) {
                            $motivosNoSubsanados[] = $motivo;
                        }
                    }

                    $this->contratoObjetivo->update(['estado_validacion' => 'Archivado']);
                    $nuevoContrato = $this->contratoObjetivo->replicate();
                    
                    if (empty($motivosNoSubsanados)) {
                        $nuevoContrato->resultado_validacion = 'Aprobado';
                        $nuevoContrato->observacion_rechazo = null;
                    } else {
                        $nuevoContrato->resultado_validacion = 'Rechazado';
                        $nuevoContrato->observacion_rechazo = "Motivos de rechazo:\n- " . implode("\n- ", $motivosNoSubsanados);
                    }
                    
                    $nuevoContrato->estado_validacion = 'Revisado';
                    $nuevoContrato->fecha_validacion = now();
                    $nuevoContrato->asem_validador_id = $this->usuarioAutenticado->id;
                    $nuevoContrato->created_at = now();
                    $nuevoContrato->updated_at = now();
                    $nuevoContrato->save();
                }

                $this->documento->update([
                    'resultado_validacion' => 'Aprobado',
                    'fecha_validacion' => now(),
                    'asem_validador_id' => $this->usuarioAutenticado->id,
                    'estado_validacion' => 'Archivado',
                    'observacion_validador' => $this->observacionValidador,
                ]);

                session()->flash('message', 'Anexo de Contrato APROBADO. Las acciones correspondientes han sido ejecutadas.');

            } elseif ($this->decision === 'Rechazado') {
                $this->rechazarDocumentoFinal();
                $this->documento->update(['estado_validacion' => 'Archivado']);
                session()->flash('message', 'Anexo de Contrato RECHAZADO y archivado.');
            }

            DB::commit();

            if ($this->documento->entidad) {
                ActualizarEstadoRecursoIndividual::dispatch($this->documento->entidad);
            }
            if ($this->contratoObjetivo && $this->contratoObjetivo->entidad) {
                ActualizarEstadoRecursoIndividual::dispatch($this->contratoObjetivo->entidad);
            }

            return $this->redirect(route('asem.panel-validacion'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al procesar Anexo de Contrato ID {$this->documento->id}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            session()->flash('error', 'Ocurrió un error inesperado al procesar el Anexo de Contrato.');
        }
    }
    
    private function aprobarDocumentoAsem()
    {
        $validationRules = [];
        $validationMessages = [];

        if ($this->documento->valida_emision_snapshot) {
            $validationRules['fechaEmisionValidador'] = 'required|date';
            $validationRules['confirmaFechaEmision'] = 'accepted';
            $validationMessages['fechaEmisionValidador.required'] = 'La fecha de emisión es obligatoria.';
            $validationMessages['confirmaFechaEmision.accepted'] = 'Debe confirmar la fecha de emisión.';
        }
        
        if ($this->documento->valida_vencimiento_snapshot) {
            $validationRules['fechaVencimientoValidador'] = 'required_if:vencimientoIndefinido,false|date|nullable';
            $validationRules['confirmaFechaVencimiento'] = 'accepted_if:vencimientoIndefinido,false';
            $validationMessages['fechaVencimientoValidador.required_if'] = 'La fecha de vencimiento es obligatoria si no es indefinido.';
            $validationMessages['confirmaFechaVencimiento.accepted_if'] = 'Debe confirmar la fecha de vencimiento.';
        }

        if (!empty($validationRules)) { $this->validate($validationRules, $validationMessages); }
        
        DB::beginTransaction();
        try {
            if ($this->documento->entidad_type === Trabajador::class && $this->documento->entidad) {
                $datosOriginales = [
                    'nacionalidad_id' => $this->documento->entidad->nacionalidad_id,
                    'fecha_nacimiento' => $this->documento->entidad->fecha_nacimiento?->format('Y-m-d'),
                ];
                $datosNuevos = $this->trabajadorData;

                $datosParaActualizar = [];
                if ($this->permisosRegla['modificar_nacionalidad'] && $datosNuevos['nacionalidad_id'] != $datosOriginales['nacionalidad_id']) {
                    $datosParaActualizar['nacionalidad_id'] = $datosNuevos['nacionalidad_id'];
                }
                if ($this->permisosRegla['modificar_fecha_nacimiento'] && $datosNuevos['fecha_nacimiento'] != $datosOriginales['fecha_nacimiento']) {
                    $datosParaActualizar['fecha_nacimiento'] = $datosNuevos['fecha_nacimiento'];
                }

                if (!empty($datosParaActualizar)) {
                    $this->documento->entidad->update($datosParaActualizar);
                }
            }

            $regla = $this->documento->reglaDocumental;
            $fechaVencimientoAGuardar = $this->vencimientoIndefinido ? null : $this->fechaVencimientoValidador;

            if ($regla && $regla->requiere_validacion_mandante) {
                $updateData = [
                    'estado_validacion' => 'Pendiente Validación Mandante',
                    'resultado_validacion' => null, 
                    'fecha_emision' => $this->fechaEmisionValidador,
                    'fecha_vencimiento' => $fechaVencimientoAGuardar,
                    'asem_validador_id' => $this->usuarioAutenticado->id,
                    'fecha_validacion_asem' => now(),
                    'observacion_validador' => $this->observacionValidador,
                ];
                $mensajeExito = 'Documento Pre-Aprobado y enviado a validación del mandante.';
            } else {
                $updateData = [
                    'resultado_validacion' => 'Aprobado',
                    'fecha_validacion' => now(),
                    'observacion_rechazo' => null,
                    'fecha_emision' => $this->fechaEmisionValidador,
                    'fecha_vencimiento' => $fechaVencimientoAGuardar,
                    'observacion_validador' => $this->observacionValidador,
                ];
                if ($this->documento->estado_validacion === 'Asignado-Revalidar') {
                    $updateData['estado_validacion'] = 'Revisado-Revalidado';
                } else {
                    $updateData['estado_validacion'] = 'Revisado';
                }
                $mensajeExito = 'Documento APROBADO correctamente.';
            }

            if ($this->documento->tipo_vencimiento_snapshot === 'DESDE EMISION' && $this->documento->valida_emision_snapshot) {
                $diasValidez = $regla->dias_validez_documento ?? 0;
                $updateData['fecha_vencimiento'] = Carbon::parse($this->fechaEmisionValidador)->addDays($diasValidez)->format('Y-m-d');
            }
    
            $this->documento->update($updateData);

            if ($this->documento->reemplaza_a_id) {
                $documentoOriginal = DocumentoCargado::find($this->documento->reemplaza_a_id);
                if ($documentoOriginal) {
                    $documentoOriginal->update(['estado_validacion' => 'Archivado']);
                }
            }

            DB::commit();

            if ($this->documento->entidad) {
                ActualizarEstadoRecursoIndividual::dispatch($this->documento->entidad);
            }

            // AUDITORÍA DETALLADA
            \App\Services\AuditService::log(
                'validacion-documento',
                "ASEM: [" . $mensajeExito . "] Documento: [" . ($this->documento->nombre_documento_snapshot ?? 'N/A') . "] para [" . $this->getNombreRecurso($this->documento->entidad) . "]",
                [
                    'documento_id' => $this->documento->id,
                    'resultado' => $updateData['resultado_validacion'] ?? 'Pendiente Mandante',
                    'recurso_id' => $this->documento->entidad_id,
                    'recurso_type' => $this->documento->entidad_type
                ]
            );

            session()->flash('message', $mensajeExito);
            return $this->redirect(route('asem.panel-validacion'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al aprobar documento (ASEM) ID {$this->documento->id}: " . $e->getMessage());
            session()->flash('error', 'Ocurrió un error inesperado al aprobar el documento.');
        }
    }
    
    private function aprobarDocumentoMandante()
    {
        $validationRules = [];
        if($this->documento->valida_vencimiento_snapshot) {
            $validationRules['confirmaFechaVencimiento'] = 'accepted';
        }

        if (!empty($validationRules)) {
             $this->validate($validationRules,['confirmaFechaVencimiento.accepted' => 'Debe confirmar que ha revisado la vigencia.']);
        }
        
        DB::beginTransaction();
        try {
            $this->documento->update([
                'resultado_validacion' => 'Aprobado',
                'fecha_validacion' => now(),
                'estado_validacion' => 'Revisado',
                'fecha_validacion_mandante' => now(),
                'observacion_validador' => $this->observacionValidador,
            ]);
            DB::commit();

            if ($this->documento->entidad) {
                ActualizarEstadoRecursoIndividual::dispatch($this->documento->entidad);
            }

            // AUDITORÍA DETALLADA
            \App\Services\AuditService::log(
                'validacion-documento',
                "MANDANTE: [Documento APROBADO] Documento: [" . ($this->documento->nombre_documento_snapshot ?? 'N/A') . "] para [" . $this->getNombreRecurso($this->documento->entidad) . "]",
                [
                    'documento_id' => $this->documento->id,
                    'resultado' => 'Aprobado',
                    'recurso_id' => $this->documento->entidad_id,
                    'recurso_type' => $this->documento->entidad_type
                ]
            );

            session()->flash('message', 'Documento APROBADO exitosamente.');
            return $this->redirect(route('mandante.panel-validacion'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al aprobar documento (Mandante) ID {$this->documento->id}: " . $e->getMessage());
            session()->flash('error', 'Ocurrió un error inesperado al aprobar el documento.');
        }
    }

    private function rechazarDocumentoFinal()
    {
        if (empty($this->motivosRechazoCalculados)) {
            $this->addError('decision', 'No se puede rechazar sin motivos. Desmarque al menos un criterio.');
            return;
        }

        DB::beginTransaction();
        try {
            if ($this->documento->entidad_type === Trabajador::class && $this->documento->entidad) {
                $datosOriginales = [
                    'nacionalidad_id' => $this->documento->entidad->nacionalidad_id,
                    'fecha_nacimiento' => $this->documento->entidad->fecha_nacimiento?->format('Y-m-d'),
                ];
                $datosNuevos = $this->trabajadorData;

                $datosParaActualizar = [];
                if ($this->permisosRegla['modificar_nacionalidad'] && $datosNuevos['nacionalidad_id'] != $datosOriginales['nacionalidad_id']) {
                    $datosParaActualizar['nacionalidad_id'] = $datosNuevos['nacionalidad_id'];
                }
                if ($this->permisosRegla['modificar_fecha_nacimiento'] && $datosNuevos['fecha_nacimiento'] != $datosOriginales['fecha_nacimiento']) {
                    $datosParaActualizar['fecha_nacimiento'] = $datosNuevos['fecha_nacimiento'];
                }

                if (!empty($datosParaActualizar)) {
                    $this->documento->entidad->update($datosParaActualizar);
                }
            }

            $motivoFinal = "- " . implode("\n- ", $this->motivosRechazoCalculados);
            $observacionFinal = "Motivos de rechazo:\n" . $motivoFinal;
    
            $updateData = [
                'resultado_validacion' => 'Rechazado',
                'fecha_validacion' => now(),
                'observacion_rechazo' => $observacionFinal,
                'observacion_validador' => $this->observacionValidador,
            ];
    
            if ($this->documento->reemplaza_a_id) {
                $updateData['estado_validacion'] = 'Archivado';
            } else {
                if ($this->documento->estado_validacion === 'Asignado-Revalidar') {
                    $updateData['estado_validacion'] = 'Revisado-Revalidado';
                } else {
                    $updateData['estado_validacion'] = 'Revisado';
                }
            }

            if ($this->usuarioAutenticado->isMandante()) {
                $updateData['fecha_validacion_mandante'] = now();
            }
    
            $this->documento->update($updateData);
            DB::commit();

            if ($this->documento->entidad) {
                ActualizarEstadoRecursoIndividual::dispatch($this->documento->entidad);
            }

            // AUDITORÍA DETALLADA
            \App\Services\AuditService::log(
                'validacion-documento',
                "RECHAZO: Documento: [" . ($this->documento->nombre_documento_snapshot ?? 'N/A') . "] para [" . $this->getNombreRecurso($this->documento->entidad) . "]. Motivos: " . implode(", ", $this->motivosRechazoCalculados),
                [
                    'documento_id' => $this->documento->id,
                    'resultado' => 'Rechazado',
                    'motivos' => $this->motivosRechazoCalculados,
                    'recurso_id' => $this->documento->entidad_id,
                    'recurso_type' => $this->documento->entidad_type
                ]
            );

            if (!$this->esAnexoDeContrato) {
                session()->flash('message', 'Documento RECHAZADO correctamente.');
                if ($this->usuarioAutenticado->isMandante()) {
                    return $this->redirect(route('mandante.panel-validacion'));
                } else {
                    return $this->redirect(route('asem.panel-validacion'));
                }
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al rechazar documento ID {$this->documento->id}: " . $e->getMessage());
            session()->flash('error', 'Ocurrió un error inesperado al rechazar el documento.');
        }
    }

    public function devolverAAdmin()
    {
        if ($this->isReadOnly || !$this->usuarioAutenticado->isAsem()) return;
        
        $this->validate(
            ['motivoDevolucion' => 'required|string|min:10'],
            ['motivoDevolucion.required' => 'Debe explicar el motivo.', 'motivoDevolucion.min' => 'El motivo debe tener al menos 10 caracteres.']
        );
        
        $this->documento->update([
            'estado_validacion' => 'Devuelto',
            'asem_validador_id' => null,
            'observacion_interna_asem' => ($this->documento->observacion_interna_asem ? $this->documento->observacion_interna_asem . "\n---\n" : '') . "DEVUELTO POR " . Auth::user()->name . " el " . now()->format('d-m-Y H:i') . ":\n" . $this->motivoDevolucion,
        ]);

        // AUDITORÍA DETALLADA
        \App\Services\AuditService::log(
            'devolucion-documento',
            "DEVOLUCIÓN: Documento: [" . ($this->documento->nombre_documento_snapshot ?? 'N/A') . "] para [" . $this->getNombreRecurso($this->documento->entidad) . "]. Motivo: " . $this->motivoDevolucion,
            [
                'documento_id' => $this->documento->id,
                'recurso_id' => $this->documento->entidad_id,
                'recurso_type' => $this->documento->entidad_type
            ]
        );
        
        session()->flash('message', 'Documento DEVUELTO al panel de asignación.');
        return $this->redirect(route('asem.panel-validacion'));
    }

    public function iniciarRevalidacionIndividual() {
        if (!$this->isReadOnly || !$this->usuarioAutenticado->isAsem()) { return; }
        
        $this->validate([ 'motivoRevalidacionIndividual' => 'required|string|min:10', ], [ 'motivoRevalidacionIndividual.required' => 'El motivo de revalidación es obligatorio.', 'motivoRevalidacionIndividual.min' => 'El motivo debe tener al menos 10 caracteres.', ]);
        
        try {
            DB::transaction(function () {
                $originalDoc = $this->documento;
                
                $updateDataOriginal = ['estado_validacion' => 'Archivado-Revalidado'];
                if ($this->marcarComoErrorValidador) {
                    $updateDataOriginal['es_error_validador'] = true;
                }
                $originalDoc->update($updateDataOriginal);

                $reglaActual = ReglaDocumental::with([ 'nombreDocumento', 'tipoVencimiento', 'observacionDocumento', 'formatoDocumento', 'criterios.criterioEvaluacion', 'criterios.subCriterio', 'criterios.textoRechazo', 'criterios.aclaracionCriterio' ])->find($originalDoc->regla_documental_id_origen);
                
                $nuevoDoc = $originalDoc->replicate([
                    'resultado_validacion', 'asem_validador_id', 'fecha_validacion', 
                    'observacion_interna_asem', 'observacion_rechazo', 'motivo_revalidacion',
                    'es_error_validador'
                ]);

                if ($reglaActual) {
                    $nuevoDoc->nombre_documento_snapshot = $reglaActual->nombreDocumento->nombre; $nuevoDoc->tipo_vencimiento_snapshot = $reglaActual->tipoVencimiento->nombre; $nuevoDoc->valida_emision_snapshot = $reglaActual->valida_emision; $nuevoDoc->valida_vencimiento_snapshot = $reglaActual->valida_vencimiento; $nuevoDoc->valor_nominal_snapshot = $reglaActual->valor_nominal_documento; $nuevoDoc->observacion_documento_snapshot = $reglaActual->observacionDocumento->observacion ?? null; $nuevoDoc->formato_documento_snapshot = $reglaActual->formatoDocumento->nombre ?? null; $nuevoDoc->documento_relacionado_id_snapshot = $reglaActual->documento_relacionado_id;
                    
                    $nuevoDoc->criterios_snapshot = $reglaActual->criterios->map(function ($criterioPivote) { 
                        return [ 
                            'criterio' => $criterioPivote->criterioEvaluacion->nombre_criterio ?? 'Criterio no encontrado', 
                            'texto_rechazo' => $criterioPivote->textoRechazo->texto_rechazo ?? null, 
                            'sub_criterio' => $criterioPivote->subCriterio->nombre ?? null, 
                            'aclaracion' => $criterioPivote->aclaracionCriterio->texto_aclaracion ?? null, 
                        ]; 
                    })->toArray();
                }
                $nuevoDoc->estado_validacion = 'Asignar-Revalidar'; $nuevoDoc->motivo_revalidacion = $this->motivoRevalidacionIndividual; $nuevoDoc->created_at = now(); $nuevoDoc->updated_at = now(); $nuevoDoc->save();
                Log::info("Revalidación Individual (desde RevisarDocumento) para Doc ID: {$originalDoc->id}. Nuevo Doc ID creado: {$nuevoDoc->id}. Regla actualizada aplicada. User ID: " . auth()->id());

                // AUDITORÍA DETALLADA
                \App\Services\AuditService::log(
                    'revalidacion-documento',
                    "REVALIDACIÓN: Documento: [" . ($originalDoc->nombre_documento_snapshot ?? 'N/A') . "] para [" . $this->getNombreRecurso($originalDoc->entidad) . "]. Motivo: " . $this->motivoRevalidacionIndividual,
                    [
                        'documento_id_original' => $originalDoc->id,
                        'documento_id_nuevo' => $nuevoDoc->id,
                        'recurso_id' => $originalDoc->entidad_id,
                        'recurso_type' => $originalDoc->entidad_type
                    ]
                );
            });

            if ($this->documento->entidad) {
                ActualizarEstadoRecursoIndividual::dispatch($this->documento->entidad);
            }

            session()->flash('message', 'El documento ha sido enviado a revalidación con las reglas actuales. Se ha creado una nueva solicitud.');
            return $this->redirect(route('gestion.gestion-general'));
        } catch (\Exception $e) { 
            session()->flash('error', 'Ocurrió un error al procesar la solicitud de revalidación.'); 
            Log::error('Error en iniciarRevalidacionIndividual en RevisarDocumento: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]); 
        }
    }
    
    public function render()
    {
        $hayAlgunIncumplimiento = false;
        $todoCumplido = true;

        foreach ($this->criteriosParaMostrar as $index => $criterio) {
            if (!empty($criterio['sub_criterios'])) {
                // Criterio con sub-criterios: se cumple si TODOS los sub-criterios están marcados
                foreach ($criterio['sub_criterios'] as $sub) {
                    if (empty($this->subCriteriosSeleccionados[$index][$sub['id']])) {
                        $hayAlgunIncumplimiento = true;
                        $todoCumplido = false;
                        break;
                    }
                }
            } else {
                // Criterio sin sub-criterios: se cumple si el padre está marcado
                if (empty($this->criteriosCumplidos[$index])) {
                    $hayAlgunIncumplimiento = true;
                    $todoCumplido = false;
                }
            }
        }

        $tieneCriterios = !empty($this->criteriosParaMostrar);
        $puedeAprobar = $tieneCriterios ? $todoCumplido : true;
        $puedeRechazar = $tieneCriterios ? $hayAlgunIncumplimiento : false;

        if ($this->usuarioAutenticado->isAsem()) {
            if($this->documento?->valida_vencimiento_snapshot && !$this->confirmaFechaVencimiento && !$this->vencimientoIndefinido) { $puedeAprobar = false; }
            if($this->documento?->valida_emision_snapshot && !$this->confirmaFechaEmision) { $puedeAprobar = false; }
        } else {
             if($this->documento?->valida_vencimiento_snapshot && !$this->confirmaFechaVencimiento) { $puedeAprobar = false; }
        }

        return view('livewire.asem.revisar-documento', [
            'puedeAprobar' => $puedeAprobar,
            'puedeRechazar' => $puedeRechazar
        ])->layout('layouts.app');
    }

    private function getNombreRecurso($entidad)
    {
        if (!$entidad) return 'N/A';
        
        return match(get_class($entidad)) {
            \App\Models\Contratista::class => $entidad->razon_social ?? 'N/A',
            \App\Models\Trabajador::class => trim(($entidad->nombres ?? '') . ' ' . ($entidad->apellido_paterno ?? '')),
            \App\Models\Vehiculo::class => ($entidad->patente_letras ?? '') . ($entidad->patente_numeros ?? ''),
            default => 'N/A'
        };
    }
}