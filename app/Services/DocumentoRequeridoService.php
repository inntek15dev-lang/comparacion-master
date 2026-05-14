<?php

namespace App\Services;

use App\Models\ReglaDocumental;
use App\Models\TipoEntidadControlable;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\DocumentoCargado;
use App\Models\Trabajador;
use App\Models\Contratista;
use App\Models\Vehiculo;
use App\Models\Maquinaria;
use App\Models\Embarcacion;
use App\Models\TrabajadorVinculacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DocumentoRequeridoService
{
    private CriticidadDocumentoService $criticidadService;

    public function __construct(CriticidadDocumentoService $criticidadService)
    {
        $this->criticidadService = $criticidadService;
    }

    public function getReglasParaEntidadEnUO(int $mandanteId, ?int $unidadOrganizacionalId, string $nombreEntidad)
    {
        // Log::info("DocumentoRequeridoService: Buscando reglas para Mandante ID: {$mandanteId}, UO ID: {$unidadOrganizacionalId}, Entidad: {$nombreEntidad}");
        $tipoEntidad = TipoEntidadControlable::where('nombre_entidad', strtoupper($nombreEntidad))->first();
        if (!$tipoEntidad) {
            Log::warning("DocumentoRequeridoService: No se encontrÃƒÆ’Ã‚Â³ el TipoEntidadControlable para '{$nombreEntidad}'.");
            return collect();
        }
        $uoActual = $unidadOrganizacionalId ? UnidadOrganizacionalMandante::find($unidadOrganizacionalId) : null;
        $idsUoAplicables = $unidadOrganizacionalId ? [$unidadOrganizacionalId] : [];
        if ($uoActual) {
            $parentId = $uoActual->parent_id;
            while ($parentId) {
                $idsUoAplicables[] = $parentId;
                $ancestro = UnidadOrganizacionalMandante::find($parentId);
                $parentId = $ancestro ? $ancestro->parent_id : null;
            }
        }
        $query = ReglaDocumental::query()
            ->where('is_active', true)
            ->where('mandante_id', $mandanteId)
            ->where('tipo_entidad_controlada_id', $tipoEntidad->id)
            ->where(function ($query) use ($idsUoAplicables) {
                $query->whereHas('unidadesOrganizacionales', function ($subQuery) use ($idsUoAplicables) {
                    $subQuery->whereIn('unidad_organizacional_mandante_id', $idsUoAplicables);
                })
                ->orWhereDoesntHave('unidadesOrganizacionales');
            })
            ->with(['nombreDocumento', 'tipoVencimiento']);
        $reglas = $query->get();
        // Log::info("DocumentoRequeridoService: Se encontraron {$reglas->count()} reglas despuÃƒÆ’Ã‚Â©s de aplicar la lÃƒÆ’Ã‚Â³gica de herencia.");
        return $reglas;
    }

    // ================== INICIO DE LA MODIFICACIÃƒÆ’Ã¢â‚¬Å“N (NUEVO PARÃƒÆ’Ã‚ÂMETRO OPCIONAL) ==================
    public function obtenerEstadoDocumentosParaEntidad(Model $entidad, int $mandanteId, ?int $unidadOrganizacionalId = null, ?int $vinculacionId = null, ?int $vinculacionContratistaId = null): array
    // ================== FIN DE LA MODIFICACIÃƒÆ’Ã¢â‚¬Å“N ================================================
    {
        $nombreEntidad = match(get_class($entidad)) {
            'App\Models\Trabajador' => 'PERSONA',
            'App\Models\Vehiculo' => 'VEHICULO',
            'App\Models\Maquinaria' => 'MAQUINARIA',
            'App\Models\Embarcacion' => 'EMBARCACION',
            'App\Models\Contratista' => 'EMPRESA',
            default => null,
        };

        if (!$nombreEntidad) {
            return [];
        }

        $reglasCandidatas = $this->getReglasParaEntidadEnUO($mandanteId, $unidadOrganizacionalId, $nombreEntidad)
             ->load([
                'nombreDocumento', 'observacionDocumento', 'formatoDocumento', 'documentoRelacionado',
                'tipoVencimiento', 'criterios.criterioEvaluacion', 'criterios.subCriterios',
                'criterios.textoRechazo', 'criterios.aclaracionCriterio',
                'cargosAplica:id', 'nacionalidadesAplica:id', 'tiposVehiculoAplica:id', 'tiposMaquinariaAplica:id', 'tiposEmbarcacionAplica:id', 'tenenciasAplica:id',
                'condicionesEmpresaAplica:id', 'tiposEmpresaLegalAplica:id', 'condicionesPersonaAplica:id', 'condicionesVehiculoAplica:id'
            ]);

        // ================== INICIO: LÓGICA PERSEGUIDOR / POR VINCULACIÓN ==================
        // Cargamos TODOS los docs de la entidad una sola vez para eficiencia.
        $todosLosDocumentosCargados = DocumentoCargado::where('entidad_id', $entidad->id)
            ->where('entidad_type', get_class($entidad))
            ->whereNotIn('estado_validacion', ['Archivado', 'Archivado-Revalidado'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Colección A: comportamiento legacy (perseguidor) — busca cualquier doc de la entidad.
        $documentosCargadosAgrupados = $todosLosDocumentosCargados
            ->groupBy('regla_documental_id_origen');

        // Colección B: comportamiento por vinculación — solo docs con ese trabajador_vinculacion_id exacto.
        // SOLO se usa para Trabajadores con vinculacionId explícito.
        $documentosCargadosPorVinculacion = null;
        if ($vinculacionId && $entidad instanceof Trabajador) {
            $documentosCargadosPorVinculacion = $todosLosDocumentosCargados
                ->filter(fn($d) => $d->trabajador_vinculacion_id == $vinculacionId)
                ->groupBy('regla_documental_id_origen');
        }
        // ================== FIN: LÓGICA PERSEGUIDOR / POR VINCULACIÓN ==================

        $documentosFinales = [];
        $idsDocumentosAgregados = [];

        $identificadorEntidad = $this->_getIdentificadorNormalizado($entidad);

        // ================== PRE-CARGA: CONDICIONES ACTIVAS DEL TRABAJADOR Y EMPRESA ==================
        // Se cargan UNA SOLA VEZ antes del loop para filtrado eficiente de sub-criterios condicionales.
        // Para entidades que no son PERSONA, estas colecciones quedan vacías (comportamiento sin cambios).
        $idsCondicionesPersonaActivas = [];
        $idsCondicionesEmpresaActivas = [];

        if ($entidad instanceof Trabajador) {
            // Condiciones personales del trabajador en esta vinculación específica
            $vinculacionParaCondiciones = $vinculacionId
                ? TrabajadorVinculacion::find($vinculacionId)
                : $entidad->vinculaciones()
                    ->where('unidad_organizacional_mandante_id', $unidadOrganizacionalId)
                    ->where('is_active', true)
                    ->first();

            if ($vinculacionParaCondiciones) {
                $idsCondicionesPersonaActivas = DB::table('trabajador_vinculacion_condicion_personal')
                    ->where('trabajador_vinculacion_id', $vinculacionParaCondiciones->id)
                    ->pluck('tipo_condicion_personal_id')
                    ->toArray();
            }

            // Condiciones de empresa del contratista en esta UO
            $contratistaDeEntidad = $entidad->contratista ?? null;
            if ($contratistaDeEntidad) {
                $cuoId = $vinculacionContratistaId;
                if (!$cuoId) {
                    $cuoId = DB::table('contratista_unidad_organizacional')
                        ->where('contratista_id', $contratistaDeEntidad->id)
                        ->where('unidad_organizacional_mandante_id', $unidadOrganizacionalId)
                        ->value('id');
                }
                if ($cuoId) {
                    $idsCondicionesEmpresaActivas = DB::table('contratista_uo_tipo_condicion')
                        ->where('contratista_uo_id', $cuoId)
                        ->pluck('tipo_condicion_id')
                        ->toArray();
                }
            }
        }
        // ================== FIN PRE-CARGA CONDICIONES ==================


        foreach ($reglasCandidatas as $regla) {
            
            $forzarInclusionPorRut = false;
            
            // Filtro: RUT Individuales Excluidos
            if (!empty($regla->rut_excluidos)) {
                $rutsExcluidos = array_map(fn($rut) => $this->_normalizarIdentificador($rut), explode(';', $regla->rut_excluidos));
                if (in_array($identificadorEntidad, $rutsExcluidos)) {
                    continue;
                }
            }

            // Filtro: Contratistas Excluidos (excluye a TODOS los trabajadores/vehículos del contratista)
            if (!empty($regla->rut_contratistas_excluidos)) {
                $rutsContratistasExcluidos = array_map(fn($rut) => $this->_normalizarIdentificador($rut), explode(';', $regla->rut_contratistas_excluidos));
                $contratistaDeLaEntidad = ($entidad instanceof Contratista) ? $entidad : ($entidad->contratista ?? null);
                if ($contratistaDeLaEntidad) {
                    $rutContratistaEntidad = $this->_normalizarIdentificador($contratistaDeLaEntidad->rut);
                    if (in_array($rutContratistaEntidad, $rutsContratistasExcluidos)) {
                        continue;
                    }
                }
            }

            // Filtro: RUT Específicos (fuerza inclusión)
            if (!empty($regla->rut_especificos)) {
                $rutsEspecificos = array_map(fn($rut) => $this->_normalizarIdentificador($rut), explode(';', $regla->rut_especificos));
                if (in_array($identificadorEntidad, $rutsEspecificos)) {
                    $forzarInclusionPorRut = true;
                } else {
                    continue;
                }
            }

            if (!$forzarInclusionPorRut) {
                // ================== FILTRO: CONDICIÓN EMPRESA (MULTI-SELECCIÓN) ==================
                if ($regla->condicionesEmpresaAplica->isNotEmpty()) {
                    $contratistaDeLaEntidad = ($entidad instanceof Contratista) ? $entidad : $entidad->contratista;
                    if ($contratistaDeLaEntidad) {
                        if ($vinculacionContratistaId) {
                            $cuoId = $vinculacionContratistaId;
                        } else {
                            $cuoId = DB::table('contratista_unidad_organizacional')
                                ->where('contratista_id', $contratistaDeLaEntidad->id)
                                ->where('unidad_organizacional_mandante_id', $unidadOrganizacionalId)
                                ->value('id');
                        }

                        // Obtener IDs de condiciones asignadas a este CUO desde la tabla pivot
                        $idsCondicionesAsignadas = $cuoId
                            ? DB::table('contratista_uo_tipo_condicion')
                                ->where('contratista_uo_id', $cuoId)
                                ->pluck('tipo_condicion_id')
                                ->toArray()
                            : [];

                        $idsCondicionesEmpresaRegla = $regla->condicionesEmpresaAplica->pluck('id')->toArray();

                        // La entidad cumple si ALGUNA de sus condiciones coincide con las de la regla
                        if (empty($idsCondicionesAsignadas) || empty(array_intersect($idsCondicionesAsignadas, $idsCondicionesEmpresaRegla))) {
                            continue;
                        }
                    } else {
                        continue;
                    }
                }
                // ================== FIN FILTRO: CONDICIÓN EMPRESA ==================

                // ================== FILTRO: TIPO EMPRESA LEGAL ==================
                if ($regla->tiposEmpresaLegalAplica->isNotEmpty()) {
                    if ($entidad instanceof Contratista) {
                        $idsTiposEmpresaRegla = $regla->tiposEmpresaLegalAplica->pluck('id')->toArray();
                        if (!$entidad->tipo_empresa_legal_id || !in_array($entidad->tipo_empresa_legal_id, $idsTiposEmpresaRegla)) {
                            continue;
                        }
                    } else {
                        // Si la entidad no es Contratista pero la regla filtra por tipo de empresa, verificamos el contratista vinculado
                        $contratistaDeLaEntidad = $entidad->contratista ?? null;
                        if ($contratistaDeLaEntidad) {
                            $idsTiposEmpresaRegla = $regla->tiposEmpresaLegalAplica->pluck('id')->toArray();
                            if (!$contratistaDeLaEntidad->tipo_empresa_legal_id || !in_array($contratistaDeLaEntidad->tipo_empresa_legal_id, $idsTiposEmpresaRegla)) {
                                continue;
                            }
                        }
                    }
                }
                // ================== FIN FILTRO: TIPO EMPRESA LEGAL ==================
                
                // ================== INICIO DE LA MODIFICACIÃƒÆ’Ã¢â‚¬Å“N (LÃƒÆ’Ã¢â‚¬Å“GICA DE FILTRADO EXPANDIDA Y CORREGIDA) ==================
                if ($entidad instanceof Trabajador) {
                    // Si se nos da un ID de vinculaciÃƒÆ’Ã‚Â³n, lo usamos. Si no, buscamos la primera activa (comportamiento anterior).
                    $vinculacion = $vinculacionId 
                        ? TrabajadorVinculacion::find($vinculacionId)
                        : $entidad->vinculaciones()->where('unidad_organizacional_mandante_id', $unidadOrganizacionalId)->where('is_active', true)->first();
                    
                    if ($vinculacion && !$vinculacion->is_active) {
                        return [];
                    }
                    
                    if ($regla->condicionesPersonaAplica->isNotEmpty()) {
                        $idsCondicionesPersonaRegla = $regla->condicionesPersonaAplica->pluck('id')->toArray();

                        // Obtener IDs de condiciones personales desde la tabla pivot
                        $idsCondicionesPersonaAsignadas = $vinculacion
                            ? DB::table('trabajador_vinculacion_condicion_personal')
                                ->where('trabajador_vinculacion_id', $vinculacion->id)
                                ->pluck('tipo_condicion_personal_id')
                                ->toArray()
                            : [];

                        // La entidad cumple si ALGUNA de sus condiciones coincide con las de la regla
                        if (empty($idsCondicionesPersonaAsignadas) || empty(array_intersect($idsCondicionesPersonaAsignadas, $idsCondicionesPersonaRegla))) {
                            continue;
                        }
                    }
                    
                    $idsCargosRegla = $regla->cargosAplica->pluck('id')->toArray();
                    if (!empty($idsCargosRegla) && (!$vinculacion || !in_array($vinculacion->cargo_mandante_id, $idsCargosRegla))) {
                        continue;
                    }

                    $idsNacionalidadesRegla = $regla->nacionalidadesAplica->pluck('id')->toArray();
                    if (!empty($idsNacionalidadesRegla) && (!$entidad->nacionalidad_id || !in_array($entidad->nacionalidad_id, $idsNacionalidadesRegla))) {
                        continue;
                    }

                    $idsTiposPermanenciaRegla = $regla->tiposPermanenciaAplica->pluck('id')->toArray();
                    if (!empty($idsTiposPermanenciaRegla) && (!$entidad->tipo_permanencia_id || !in_array($entidad->tipo_permanencia_id, $idsTiposPermanenciaRegla))) {
                        continue;
                    }
                } elseif ($entidad instanceof Vehiculo) {
                    $idsTiposVehiculoRegla = $regla->tiposVehiculoAplica->pluck('id')->toArray();
                    if (!empty($idsTiposVehiculoRegla) && (!$entidad->tipo_vehiculo_id || !in_array($entidad->tipo_vehiculo_id, $idsTiposVehiculoRegla))) {
                        continue;
                    }

                    $idsTenenciasRegla = $regla->tenenciasAplica->pluck('id')->toArray();
                    if (!empty($idsTenenciasRegla) && (!$entidad->tenencia_vehiculo_id || !in_array($entidad->tenencia_vehiculo_id, $idsTenenciasRegla))) {
                        continue;
                    }

                    // Filtro: Sub-Tipo de Vehículo por Mandante
                    $idsSubTiposRegla = $regla->subTiposVehiculoAplica->pluck('id')->toArray();
                    if (!empty($idsSubTiposRegla)) {
                        // Buscar la asignación activa del vehículo en la UO para obtener su sub-tipo
                        $asignacion = \App\Models\VehiculoAsignacion::where('vehiculo_id', $entidad->id)
                            ->where('unidad_organizacional_mandante_id', $unidadOrganizacionalId)
                            ->where('is_active', true)
                            ->first();
                        $subTipoDeVinculacion = $asignacion?->sub_tipo_vehiculo_mandante_id;
                        if (!$subTipoDeVinculacion || !in_array($subTipoDeVinculacion, $idsSubTiposRegla)) {
                            continue;
                        }
                    }

                    // Filtro: Condiciones de Vehículo (Múltiples)
                    $idsCondicionesVehiculoRegla = $regla->condicionesVehiculoAplica->pluck('id')->toArray();
                    if (!empty($idsCondicionesVehiculoRegla)) {
                        $asignacion ??= \App\Models\VehiculoAsignacion::where('vehiculo_id', $entidad->id)
                            ->where('unidad_organizacional_mandante_id', $unidadOrganizacionalId)
                            ->where('is_active', true)
                            ->first();
                        
                        $condicionesDeVinculacion = $asignacion ? $asignacion->condiciones->pluck('id')->toArray() : [];
                        if (empty(array_intersect($condicionesDeVinculacion, $idsCondicionesVehiculoRegla))) {
                            continue;
                        }
                    }
                } elseif ($entidad instanceof Maquinaria) {
                    $idsTiposMaquinariaRegla = $regla->tiposMaquinariaAplica->pluck('id')->toArray();
                    if (!empty($idsTiposMaquinariaRegla) && (!$entidad->tipo_maquinaria_id || !in_array($entidad->tipo_maquinaria_id, $idsTiposMaquinariaRegla))) {
                        continue;
                    }

                    $idsTenenciasRegla = $regla->tenenciasAplica->pluck('id')->toArray();
                    if (!empty($idsTenenciasRegla) && (!$entidad->tenencia_vehiculo_id || !in_array($entidad->tenencia_vehiculo_id, $idsTenenciasRegla))) {
                        continue;
                    }
                } elseif ($entidad instanceof Embarcacion) {
                    $idsTiposEmbarcacionRegla = $regla->tiposEmbarcacionAplica->pluck('id')->toArray();
                    if (!empty($idsTiposEmbarcacionRegla) && (!$entidad->tipo_embarcacion_id || !in_array($entidad->tipo_embarcacion_id, $idsTiposEmbarcacionRegla))) {
                        continue;
                    }

                    $idsTenenciasRegla = $regla->tenenciasAplica->pluck('id')->toArray();
                    if (!empty($idsTenenciasRegla) && (!$entidad->tenencia_vehiculo_id || !in_array($entidad->tenencia_vehiculo_id, $idsTenenciasRegla))) {
                        continue;
                    }
                }
                // ================== FIN DE LA MODIFICACIÃƒÆ’Ã¢â‚¬Å“N ======================================================
            }

            if (!in_array($regla->nombre_documento_id, $idsDocumentosAgregados)) {

                // ================== BIFURCACIÓN PERSEGUIDOR/VINCULACIÓN ==================
                // Obtener la criticidad ANTES de seleccionar la colección de docs.
                $criticidad = $this->criticidadService->getParaEntidad($entidad, $regla->nombre_documento_id, $mandanteId);
                $esPerseguidor = $criticidad['es_perseguidor'];

                // Seleccionar la colección correcta:
                // - NO perseguidor + Trabajador + vinculacionId → colección filtrada por vinculación
                // - Perseguidor o cualquier otro caso → colección legacy (por entidad)
                $usarColeccionPorVinculacion = (
                    !$esPerseguidor
                    && $entidad instanceof Trabajador
                    && $documentosCargadosPorVinculacion !== null
                );

                $documentosParaEstaRegla = $usarColeccionPorVinculacion
                    ? $documentosCargadosPorVinculacion->get($regla->id)
                    : $documentosCargadosAgrupados->get($regla->id);
                // ======================================================================

                $docCargado = $documentosParaEstaRegla ? $documentosParaEstaRegla->first() : null;
                
                $estadoActual = 'No Cargado';
                $motivoRechazo = null;
                $estadoCadena = null;
                $siguientePeriodoRequerido = null;
                $motivoRechazoAnterior = null;
                $dentroDeGracia = false;
                $tieneReemplazoVigente = false;

                // ================== LOGICA DE REEMPLAZO VIGENTE (SEGURO CONTRA CAÍDAS) ==================
                $docVigenteAnterior = $documentosParaEstaRegla ? $documentosParaEstaRegla->first(function($d) {
                    return $d->resultado_validacion === 'Aprobado' && in_array($d->estadoVigencia, ['Vigente', 'Vigente-Modificado']);
                }) : null;
                if ($docVigenteAnterior) {
                    $tieneReemplazoVigente = true;
                }
                // =========================================================================================

                if ($regla->tipoVencimiento?->nombre === 'POR PERIODO') {
                    // También bifurcar la consulta POR PERIODO según perseguidor/vinculación
                    $queryPeriodico = DocumentoCargado::where('entidad_id', $entidad->id)
                        ->where('entidad_type', get_class($entidad))
                        ->where('regla_documental_id_origen', $regla->id)
                        ->whereNotNull('periodo');

                    if ($usarColeccionPorVinculacion) {
                        $queryPeriodico->where('trabajador_vinculacion_id', $vinculacionId);
                    }

                    $ultimoDocumentoPeriodico = $queryPeriodico
                        ->orderBy('periodo', 'desc')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if (!$ultimoDocumentoPeriodico) {
                        // Determinar fecha de inicio de operaciÃ³n para el periodo inicial
                        $fechaInicioOperacion = null;
                        if ($entidad instanceof \App\Models\Trabajador) {
                            $fechaInicioOperacion = $vinculacion ? $vinculacion->fecha_ingreso_vinculacion : null;
                        } elseif ($entidad instanceof \App\Models\Vehiculo || $entidad instanceof \App\Models\Maquinaria || $entidad instanceof \App\Models\Embarcacion) {
                            $asignacion = $entidad->vinculaciones()
                                ->where('unidad_organizacional_mandante_id', $unidadOrganizacionalId)
                                ->where('is_active', true)
                                ->first();
                            $fechaInicioOperacion = $asignacion ? ($asignacion->fecha_asignacion ?? $asignacion->created_at) : null;
                        } elseif ($entidad instanceof \App\Models\Contratista) {
                            $solicitud = DB::table('solicitudes_vinculacion')
                                ->where('contratista_id', $entidad->id)
                                ->where('mandante_id', $mandanteId)
                                ->where('estado', 'APROBADA')
                                ->latest('updated_at')
                                ->first();
                            $fechaInicioOperacion = $solicitud ? Carbon::parse($solicitud->updated_at) : null;
                        }

                        $estadoActual = 'No Cargado';
                        $estadoCadena = 'listo_para_cargar';
                        $siguientePeriodoRequerido = $this->_determinarPeriodoInicial($entidad, $regla, $fechaInicioOperacion);
                    } elseif ($ultimoDocumentoPeriodico->resultado_validacion === 'Aprobado') {
                        $periodoAprobado = Carbon::createFromFormat('Y-m', $ultimoDocumentoPeriodico->periodo)->startOfMonth();
                        $siguientePeriodoRequerido = $periodoAprobado->copy()->addMonth()->format('Y-m');
                        $estadoCadena = 'listo_para_cargar';
                    } elseif ($ultimoDocumentoPeriodico->resultado_validacion === 'Rechazado') {
                        $estadoActual = 'Rechazado';
                        $estadoCadena = 'requiere_correccion';
                        $siguientePeriodoRequerido = $ultimoDocumentoPeriodico->periodo;
                        $motivoRechazoAnterior = $ultimoDocumentoPeriodico->observacion_rechazo;
                        $motivoRechazo = $ultimoDocumentoPeriodico->observacion_rechazo;
                    } else {
                        $estadoCadena = 'pendiente_aprobacion';
                        $siguientePeriodoRequerido = $ultimoDocumentoPeriodico->periodo;
                        if ($ultimoDocumentoPeriodico->estado_validacion === 'Asignado') { $estadoActual = 'En Revisión'; }
                        else { $estadoActual = 'Pendiente Validación'; }
                    }

                    // ================== CÁLCULO DE GRACIA (MES VENCIDO) ==================
                    if ($siguientePeriodoRequerido) {
                        $diasGracia = $regla->dias_gracia_carga ?? 0;
                        // Math Mes Vencido: Plazo máximo para "2026-01" transcurre en "2026-02" + días de gracia
                        $fechaLimiteCarga = Carbon::createFromFormat('Y-m', $siguientePeriodoRequerido)
                            ->startOfMonth()
                            ->addMonth() // Salto estructural a mes vencido
                            ->addDays($diasGracia);

                        $dentroDeGracia = Carbon::today()->lte($fechaLimiteCarga);

                        if ($ultimoDocumentoPeriodico && $ultimoDocumentoPeriodico->resultado_validacion === 'Aprobado') {
                            $estadoActual = $dentroDeGracia ? 'Aprobado' : 'Vencido';
                        }
                    }
                    // ======================================================================
                    
                    $docCargado = $ultimoDocumentoPeriodico;
                } else {
                    if ($docCargado) {
                        if ($docCargado->resultado_validacion === 'Aprobado') {
                            $estadoActual = match($docCargado->estadoVigencia) {
                                'Vigente' => 'Aprobado',
                                'Vigente-Modificado' => 'Aprobado-Modificado',
                                default => $docCargado->estadoVigencia,
                            };
                        }
                        elseif ($docCargado->resultado_validacion === 'Rechazado') { 
                            $estadoActual = 'Rechazado'; 
                            $motivoRechazo = $docCargado->observacion_rechazo;
                        }
                        elseif ($docCargado->estado_validacion === 'Asignado') { 
                            $estadoActual = 'En Revisión'; 
                        }
                        elseif ($docCargado->estado_validacion === 'Pendiente Validación Mandante') { 
                            $estadoActual = 'Pendiente Validación Principal'; 
                        }
                        else { 
                            // Cualquier otro estado que no sea Aprobado/Rechazado/Archivado -> Es un documento Pendiente.
                            // Esto previene que documentos cargados masivamente o con estados intermedios queden varados como "No Cargado".
                            $estadoActual = 'Pendiente Validación'; 
                        }
                    }
                }

                // ($criticidad ya fue resuelta arriba en la bifurcación perseguidor/vinculación)

                $documentosFinales[] = [
                    'regla_documental_id_origen' => $regla->id,
                    'nombre_documento_id' => $regla->nombre_documento_id,
                    'nombre_documento_texto' => $regla->nombreDocumento?->nombre ?? 'Doc. Desconocido',
                    'observacion_documento_texto' => $regla->observacionDocumento?->titulo,
                    'estado_actual_documento' => $estadoActual,
                    'motivo_rechazo' => $motivoRechazo,
                    'archivo_cargado' => $docCargado,
                    'valida_emision' => (bool) $regla->valida_emision,
                    'valida_vencimiento' => (bool) $regla->valida_vencimiento,
                    'tipo_vencimiento_nombre' => $regla->tipoVencimiento?->nombre,
                    'dias_validez_documento' => $regla->dias_validez_documento,
                    'dias_gracia_carga' => $regla->dias_gracia_carga,
                    'criterios_evaluacion' => $regla->criterios->map(function ($c) use ($idsCondicionesPersonaActivas, $idsCondicionesEmpresaActivas) {
                        // ================== FILTRADO CONDICIONAL DE SUB-CRITERIOS (NIVEL DIOS) ==================
                        // Cada sub-criterio puede tener una condición de trabajador (tipo_condicion_personal_id)
                        // y/o una condición de empresa (tipo_condicion_id) en la tabla pivot.
                        //
                        // REGLA DE INCLUSIÓN:
                        //   - Si AMBOS campos son NULL → sub-criterio UNIVERSAL (siempre incluido)
                        //   - Si tiene condición personal → incluir si el trabajador tiene esa condición activa
                        //   - Si tiene condición empresa  → incluir si la empresa tiene esa condición activa
                        //   - Un sub-criterio puede satisfacerse por cualquiera de las dos vías (OR)
                        //
                        // DEDUPLICACIÓN: por sub_criterio_id (un EPP no se lista dos veces aunque
                        // lo cubran múltiples condiciones simultáneamente).
                        //
                        // COMPATIBILIDAD BACKWARD: si ningún sub-criterio tiene condición asignada
                        // (todo NULL), el comportamiento es idéntico al sistema anterior.
                        // =====================================================================================

                        $subCriteriosFiltrados = collect();
                        $idsYaAgregados = [];

                        foreach ($c->subCriterios as $sc) {
                            $condPersonalId = $sc->pivot->tipo_condicion_personal_id ?? null;
                            $condEmpresaId  = $sc->pivot->tipo_condicion_id ?? null;

                            $esUniversal     = ($condPersonalId === null && $condEmpresaId === null);
                            $cumplePersonal  = ($condPersonalId !== null && in_array($condPersonalId, $idsCondicionesPersonaActivas));
                            $cumpleEmpresa   = ($condEmpresaId  !== null && in_array($condEmpresaId,  $idsCondicionesEmpresaActivas));

                            if ($esUniversal || $cumplePersonal || $cumpleEmpresa) {
                                // Deduplicar: solo agregar si el sub-criterio no fue incluido ya
                                if (!in_array($sc->id, $idsYaAgregados)) {
                                    $subCriteriosFiltrados->push(['id' => $sc->id, 'nombre' => $sc->nombre]);
                                    $idsYaAgregados[] = $sc->id;
                                }
                            }
                        }

                        return [
                            'criterio'      => $c->criterioEvaluacion?->nombre_criterio,
                            'sub_criterios' => $subCriteriosFiltrados->values()->all(),
                            'texto_rechazo' => $c->textoRechazo?->titulo,
                            'aclaracion'    => $c->aclaracionCriterio?->titulo,
                        ];
                    })->all(),
                    'afecta_cumplimiento' => $criticidad['afecta_cumplimiento'],
                    'restringe_acceso' => $criticidad['restringe_acceso'],
                    'es_perseguidor' => $criticidad['es_perseguidor'],
                    'reemplaza_a_id' => $docCargado?->reemplaza_a_id,
                    'estado_cadena' => $estadoCadena,
                    'siguiente_periodo_requerido' => $siguientePeriodoRequerido,
                    'motivo_rechazo_anterior' => $motivoRechazoAnterior,
                    'dentro_de_gracia' => $dentroDeGracia,
                    'tiene_reemplazo_vigente' => $tieneReemplazoVigente,
                ];

                $idsDocumentosAgregados[] = $regla->nombre_documento_id;
            }
        }
        return $documentosFinales;
    }

    private function _determinarPeriodoInicial(Model $entidad, ReglaDocumental $regla, ?Carbon $fechaInicioOperacion = null): string
    {
        $fechaCreacionRegla = $regla->created_at;
        
        // Base de cálculo: si no viene fecha de operación específica, usamos el created_at de la entidad (fallback)
        $fechaReferenciaEntidad = $fechaInicioOperacion ?? $entidad->created_at;

        if (!$fechaReferenciaEntidad) {
            return $fechaCreacionRegla->format('Y-m');
        }

        // El periodo inicial es el máximo entre cuando nació la regla y cuando empezó la operación/existencia
        $fechaInicioObligacion = $fechaCreacionRegla->max($fechaReferenciaEntidad);
        
        return $fechaInicioObligacion->format('Y-m');
    }

    private function _getIdentificadorNormalizado(Model $entidad): string
    {
        $identificador = '';
        if ($entidad instanceof Trabajador || $entidad instanceof Contratista) {
            $identificador = $entidad->rut;
        } elseif (isset($entidad->identificador_unico)) {
            $identificador = $entidad->identificador_unico;
        }
        return $this->_normalizarIdentificador($identificador);
    }

    private function _normalizarIdentificador(string $identificador): string
    {
        return strtoupper(str_replace(['.', '-', ' '], '', $identificador));
    }
}
