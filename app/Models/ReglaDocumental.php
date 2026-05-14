<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\User;
use App\Models\TipoPermanencia;
use App\Models\TipoCondicionPersonal;
use App\Models\TipoCondicionVehiculo;

class ReglaDocumental extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'reglas_documentales';

    // ================== INICIO DE LA RECTIFICACIÓN CANÓNICA ==================
    protected $fillable = [
        'mandante_id', 'tipo_entidad_controlada_id', 'nombre_documento_id',
        'valor_nominal_documento',
        'condicion_fecha_ingreso_id', 'fecha_comparacion_ingreso', 'rut_especificos',
        'rut_excluidos', 'rut_contratistas_excluidos', 'observacion_documento_id', 'formato_documento_id',
        'documento_relacionado_id', 'tipo_vencimiento_id', 'dias_validez_documento', 'imc_meses_estimados',
        'dias_gracia_carga',
        'dias_aviso_vencimiento', 'valida_emision',
        'valida_vencimiento',
        'requiere_validacion_mandante',
        'valida_solo_mandante',
        'mostrar_historico_documento', 'permite_ver_nacionalidad_trabajador',
        'permite_modificar_nacionalidad_trabajador', 'permite_ver_fecha_nacimiento_trabajador',
        'permite_modificar_fecha_nacimiento_trabajador', 'is_active', 'created_by', 'updated_by',
    ];
    // ================== FIN DE LA RECTIFICACIÓN CANÓNICA ==================

    protected $casts = [
        'fecha_comparacion_ingreso' => 'date', 'valida_emision' => 'boolean',
        'valida_vencimiento' => 'boolean',
        'requiere_validacion_mandante' => 'boolean',
        'valida_solo_mandante' => 'boolean',
        'mostrar_historico_documento' => 'boolean', 'permite_ver_nacionalidad_trabajador' => 'boolean',
        'permite_modificar_nacionalidad_trabajador' => 'boolean', 'permite_ver_fecha_nacimiento_trabajador' => 'boolean',
        'permite_modificar_fecha_nacimiento_trabajador' => 'boolean', 'is_active' => 'boolean',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Regla Documental')
            ->logFillable()
            ->logExcept(['updated_by', 'created_by', 'created_at', 'updated_at'])
            ->logOnlyDirty()->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Regla {$this->traducirEvento($eventName)}");
    }

    private function traducirEvento(string $eventName): string
    {
        $traducciones = [
            'created' => 'creada',
            'updated' => 'actualizada (Atributos)', // Más específico
            'deleted' => 'eliminada',
            'Relaciones modificadas' => 'actualizada (Relaciones)', // Para nuestro log manual
        ];
        return $traducciones[$eventName] ?? $eventName;
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class , 'created_by');
    }
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class , 'updated_by');
    }
    public function mandante(): BelongsTo
    {
        return $this->belongsTo(Mandante::class , 'mandante_id');
    }
    public function tipoEntidadControlada(): BelongsTo
    {
        return $this->belongsTo(TipoEntidadControlable::class , 'tipo_entidad_controlada_id');
    }
    public function nombreDocumento(): BelongsTo
    {
        return $this->belongsTo(NombreDocumento::class , 'nombre_documento_id');
    }
    public function condicionesEmpresaAplica(): BelongsToMany
    {
        return $this->belongsToMany(TipoCondicion::class, 'regla_documental_tipo_condicion', 'regla_documental_id', 'tipo_condicion_id');
    }
    public function condicionesPersonaAplica(): BelongsToMany
    {
        return $this->belongsToMany(TipoCondicionPersonal::class, 'regla_documental_tipo_condicion_personal', 'regla_documental_id', 'tipo_condicion_personal_id');
    }
    public function condicionFechaIngreso(): BelongsTo
    {
        return $this->belongsTo(CondicionFechaIngreso::class , 'condicion_fecha_ingreso_id');
    }
    public function observacionDocumento(): BelongsTo
    {
        return $this->belongsTo(ObservacionDocumento::class , 'observacion_documento_id');
    }
    public function observacionesDocumento(): BelongsToMany
    {
        return $this->belongsToMany(ObservacionDocumento::class, 'regla_observacion_documento', 'regla_documental_id', 'observacion_documento_id');
    }

    public function formatoDocumento(): BelongsTo
    {
        return $this->belongsTo(FormatoDocumentoMuestra::class , 'formato_documento_id');
    }
    public function formatosDocumento(): BelongsToMany
    {
        return $this->belongsToMany(FormatoDocumentoMuestra::class, 'regla_formato_documento', 'regla_documental_id', 'formato_documento_id');
    }

    public function documentoRelacionado(): BelongsTo
    {
        return $this->belongsTo(NombreDocumento::class , 'documento_relacionado_id');
    }
    public function documentosRelacionados(): BelongsToMany
    {
        return $this->belongsToMany(NombreDocumento::class, 'regla_doc_relacionado', 'regla_documental_id', 'documento_relacionado_id');
    }
    public function tipoVencimiento(): BelongsTo
    {
        return $this->belongsTo(TipoVencimiento::class , 'tipo_vencimiento_id');
    }

    public function criterios(): HasMany
    {
        return $this->hasMany(ReglaDocumentalCriterio::class , 'regla_documental_id');
    }

    /**
     * Campos configurados para extracción IA (Módulo IA Acreditación).
     * NO afecta el flujo de acreditación normal.
     */
    public function iaCamposConfiguracion(): HasMany
    {
        return $this->hasMany(\App\Models\IaCampoConfiguracion::class, 'regla_documental_id');
    }

    public function iaCamposActivos(): HasMany
    {
        return $this->hasMany(\App\Models\IaCampoConfiguracion::class, 'regla_documental_id')
                    ->where('is_active', true)
                    ->orderBy('orden');
    }

    public function criteriosAsem(): HasMany
    {
        return $this->hasMany(ReglaDocumentalCriterio::class , 'regla_documental_id')->where('fuente_validacion', 'asem');
    }

    public function criteriosMandante(): HasMany
    {
        return $this->hasMany(ReglaDocumentalCriterio::class , 'regla_documental_id')->where('fuente_validacion', 'mandante');
    }

    public function unidadesOrganizacionales(): BelongsToMany
    {
        return $this->belongsToMany(UnidadOrganizacionalMandante::class , 'regla_documental_unidad_organizacional', 'regla_documental_id', 'unidad_organizacional_mandante_id');
    }
    public function cargosAplica(): BelongsToMany
    {
        return $this->belongsToMany(CargoMandante::class , 'regla_documental_cargo_mandante', 'regla_documental_id', 'cargo_mandante_id');
    }
    public function nacionalidadesAplica(): BelongsToMany
    {
        return $this->belongsToMany(Nacionalidad::class , 'regla_documental_nacionalidad', 'regla_documental_id', 'nacionalidad_id');
    }
    public function tiposPermanenciaAplica(): BelongsToMany
    {
        return $this->belongsToMany(TipoPermanencia::class , 'regla_documental_tipo_permanencia', 'regla_documental_id', 'tipo_permanencia_id');
    }
    public function tiposVehiculoAplica(): BelongsToMany
    {
        return $this->belongsToMany(TipoVehiculo::class , 'regla_documental_tipo_vehiculo', 'regla_documental_id', 'tipo_vehiculo_id');
    }
    public function tiposMaquinariaAplica(): BelongsToMany
    {
        return $this->belongsToMany(TipoMaquinaria::class , 'regla_documental_tipo_maquinaria', 'regla_documental_id', 'tipo_maquinaria_id');
    }
    public function tiposEmbarcacionAplica(): BelongsToMany
    {
        return $this->belongsToMany(TipoEmbarcacion::class , 'regla_documental_tipo_embarcacion', 'regla_documental_id', 'tipo_embarcacion_id');
    }
    public function tenenciasAplica(): BelongsToMany
    {
        return $this->belongsToMany(TenenciaVehiculo::class , 'regla_documental_tenencia_vehiculo', 'regla_documental_id', 'tenencia_vehiculo_id');
    }
    public function subTiposVehiculoAplica(): BelongsToMany
    {
        return $this->belongsToMany(SubTipoVehiculoMandante::class , 'regla_documental_sub_tipo_vehiculo_mandante', 'regla_documental_id', 'sub_tipo_vehiculo_mandante_id');
    }
    public function tiposEmpresaLegalAplica(): BelongsToMany
    {
        return $this->belongsToMany(TipoEmpresaLegal::class, 'regla_documental_tipo_empresa_legal', 'regla_documental_id', 'tipo_empresa_legal_id');
    }

    public function condicionesVehiculoAplica(): BelongsToMany
    {
        return $this->belongsToMany(TipoCondicionVehiculo::class, 'regla_cond_vehiculo', 'regla_documental_id', 'tipo_condicion_vehiculo_id');
    }

    /**
     * Accessor: Calcula el IMC (Índice Mensual de Carga) de la regla.
     * Prioridad: imc_meses_estimados (manual) > POR PERIODO (default 1 mes) > dias_validez_documento (auto).
     * Fórmula: 1 / meses_de_vigencia.
     */
    public function getImcAttribute(): ?float
    {
        // 1. Si tiene meses estimados manualmente, SIEMPRE usan esos (máxima prioridad)
        if ($this->imc_meses_estimados && $this->imc_meses_estimados > 0) {
            return round(1 / $this->imc_meses_estimados, 4);
        }

        // 2. Si el vencimiento es POR PERIODO y no hay valor manual, asumir 1 mes por defecto.
        if ($this->tipoVencimiento && strtoupper($this->tipoVencimiento->nombre) === 'POR PERIODO') {
            return 1.0000;
        }

        // 3. Si tiene dias_validez_documento, derivar automáticamente
        if ($this->dias_validez_documento && $this->dias_validez_documento > 0) {
            $meses = $this->dias_validez_documento / 30.44; // promedio de días por mes
            return round(1 / $meses, 4);
        }

        // 4. Sin datos de vigencia -> no se puede calcular
        return null;
    }

    /**
     * Calcula la cantidad de items activos que aplicarían a esta regla para el Mandante dado.
     * Soporta todas las entidades controladas.
     */
    public function contarAfectados(): int
    {
        if (!$this->tipoEntidadControlada) {
            return 0;
        }

        $entidadNombre = strtoupper($this->tipoEntidadControlada->nombre_entidad);
        $mandanteId = $this->mandante_id;

        // 1. Resolver UOs aplicables (la UO asignada y todos sus descendientes) - SIEMPRE PRIMERO
        $ruleUos = $this->unidadesOrganizacionales->pluck('id')->toArray();
        $applicableUoIds = [];

        if (empty($ruleUos)) {
            $applicableUoIds = \App\Models\UnidadOrganizacionalMandante::where('mandante_id', $mandanteId)->pluck('id')->toArray();
        } else {
            $allUos = \App\Models\UnidadOrganizacionalMandante::where('mandante_id', $mandanteId)->get(['id', 'parent_id']);
            $applicableUoIds = $ruleUos;
            
            $childrenMap = [];
            foreach ($allUos as $uo) {
                $childrenMap[$uo->parent_id][] = $uo->id;
            }

            $pending = $ruleUos;
            while (!empty($pending)) {
                $current = array_pop($pending);
                if (isset($childrenMap[$current])) {
                    foreach ($childrenMap[$current] as $childId) {
                        if (!in_array($childId, $applicableUoIds)) {
                            $applicableUoIds[] = $childId;
                            $pending[] = $childId;
                        }
                    }
                }
            }
        }

        if (empty($applicableUoIds)) {
            return 0;
        }

        // ==========================================
        // LÓGICA POR ENTIDAD
        // ==========================================

        if ($entidadNombre === 'EMPRESA') {
            $query = \App\Models\ContratistaUnidadOrganizacional::whereIn('contratista_unidad_organizacional.unidad_organizacional_mandante_id', $applicableUoIds)
                ->join('contratistas', 'contratista_unidad_organizacional.contratista_id', '=', 'contratistas.id')
                ->where('contratistas.is_active', true);

            // RUT Específicos
            if (!empty($this->rut_especificos)) {
                $rutsEspecificos = array_map(function($rut) {
                    return strtoupper(str_replace(['.', '-', ' '], '', trim($rut)));
                }, explode(';', $this->rut_especificos));
                
                $query->whereIn(\Illuminate\Support\Facades\DB::raw("REPLACE(REPLACE(UPPER(contratistas.rut), '-', ''), '.', '')"), $rutsEspecificos);
                return $query->distinct('contratistas.id')->count('contratistas.id');
            }

            // Filtro: RUT Excluidos
            if (!empty($this->rut_excluidos)) {
                $rutsExcluidos = array_map(function($rut) {
                    return strtoupper(str_replace(['.', '-', ' '], '', trim($rut)));
                }, explode(';', $this->rut_excluidos));
                
                $query->whereNotIn(\Illuminate\Support\Facades\DB::raw("REPLACE(REPLACE(UPPER(contratistas.rut), '-', ''), '.', '')"), $rutsExcluidos);
            }

            // Filtro: Condición Empresa (pivot multi-condición)
            $condicionesEmpresaIds = $this->condicionesEmpresaAplica->pluck('id')->toArray();
            if (!empty($condicionesEmpresaIds)) {
                $query->whereExists(function ($sub) use ($condicionesEmpresaIds) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('contratista_uo_tipo_condicion as cutc')
                        ->whereColumn('cutc.contratista_uo_id', 'contratista_unidad_organizacional.id')
                        ->whereIn('cutc.tipo_condicion_id', $condicionesEmpresaIds);
                });
            }

            // Filtro: Tipos de Empresa Legal
            $tiposEmpresaLegalIds = $this->tiposEmpresaLegalAplica->pluck('id')->toArray();
            if (!empty($tiposEmpresaLegalIds)) {
                $query->whereIn('contratistas.tipo_empresa_legal_id', $tiposEmpresaLegalIds);
            }

            return $query->distinct('contratistas.id')->count('contratistas.id');

        } elseif ($entidadNombre === 'PERSONA') {
            // 2. Construir la consulta principal para PERSONA
            $query = \App\Models\TrabajadorVinculacion::where('trabajador_vinculaciones.is_active', true)
                ->whereIn('trabajador_vinculaciones.unidad_organizacional_mandante_id', $applicableUoIds)
                ->join('trabajadores', 'trabajador_vinculaciones.trabajador_id', '=', 'trabajadores.id')
                ->whereNull('trabajadores.deleted_at');

            // RUT Específicos (Overriding rule)
            if (!empty($this->rut_especificos)) {
                $rutsEspecificos = array_map(function($rut) {
                    return strtoupper(str_replace('-', '', trim($rut)));
                }, explode(';', $this->rut_especificos));
                
                $query->whereIn(\Illuminate\Support\Facades\DB::raw("REPLACE(UPPER(trabajadores.rut), '-', '')"), $rutsEspecificos);
                
                return $query->distinct('trabajadores.id')->count('trabajadores.id');
            }

            // Filtro: RUT Excluidos (individuales)
            if (!empty($this->rut_excluidos)) {
                $rutsExcluidos = array_map(function($rut) {
                    return strtoupper(str_replace('-', '', trim($rut)));
                }, explode(';', $this->rut_excluidos));
                
                $query->whereNotIn(\Illuminate\Support\Facades\DB::raw("REPLACE(UPPER(trabajadores.rut), '-', '')"), $rutsExcluidos);
            }

            // Filtro: Contratistas Excluidos (todos sus trabajadores)
            if (!empty($this->rut_contratistas_excluidos)) {
                $rutsContratistasExcluidos = array_map(function($rut) {
                    return strtoupper(str_replace(['.', '-', ' '], '', trim($rut)));
                }, explode(';', $this->rut_contratistas_excluidos));

                // Resuelve los IDs de los contratistas excluidos para evitar joins adicionales
                $idsContratistasExcluidos = \App\Models\Contratista::whereIn(
                    \Illuminate\Support\Facades\DB::raw("REPLACE(REPLACE(UPPER(rut), '-', ''), '.', '')"),
                    $rutsContratistasExcluidos
                )->pluck('id')->toArray();

                if (!empty($idsContratistasExcluidos)) {
                    $query->whereNotIn('trabajadores.contratista_id', $idsContratistasExcluidos);
                }
            }

            // Filtro: Condición Persona (pivot multi-condición)
            $condicionesPersonaIds = $this->condicionesPersonaAplica->pluck('id')->toArray();
            if (!empty($condicionesPersonaIds)) {
                $query->whereExists(function ($sub) use ($condicionesPersonaIds) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('trabajador_vinculacion_condicion_personal')
                        ->whereColumn('trabajador_vinculacion_condicion_personal.trabajador_vinculacion_id', 'trabajador_vinculaciones.id')
                        ->whereIn('trabajador_vinculacion_condicion_personal.tipo_condicion_personal_id', $condicionesPersonaIds);
                });
            }

            // Filtro: Cargos
            $cargosIds = $this->cargosAplica->pluck('id')->toArray();
            if (!empty($cargosIds)) {
                $query->whereIn('trabajador_vinculaciones.cargo_mandante_id', $cargosIds);
            }

            // Filtro: Nacionalidad
            $nacionalidadesIds = $this->nacionalidadesAplica->pluck('id')->toArray();
            if (!empty($nacionalidadesIds)) {
                $query->whereIn('trabajadores.nacionalidad_id', $nacionalidadesIds);
            }

            // Filtro: Permanencia
            $permanenciasIds = $this->tiposPermanenciaAplica->pluck('id')->toArray();
            if (!empty($permanenciasIds)) {
                $query->whereIn('trabajadores.tipo_permanencia_id', $permanenciasIds);
            }

            // Filtro: Condición Empresa (pivot multi-condición)
            $condicionesEmpresaIds = $this->condicionesEmpresaAplica->pluck('id')->toArray();
            if (!empty($condicionesEmpresaIds)) {
                $query->whereExists(function ($sub) use ($applicableUoIds, $condicionesEmpresaIds) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('contratista_unidad_organizacional as cuo')
                        ->join('contratista_uo_tipo_condicion as cutc', 'cutc.contratista_uo_id', '=', 'cuo.id')
                        ->whereColumn('cuo.contratista_id', 'trabajadores.contratista_id')
                        ->whereIn('cuo.unidad_organizacional_mandante_id', $applicableUoIds)
                        ->whereIn('cutc.tipo_condicion_id', $condicionesEmpresaIds);
                });
            }

            return $query->distinct('trabajadores.id')->count('trabajadores.id');

        } elseif ($entidadNombre === 'VEHICULO') {
            $query = \App\Models\VehiculoAsignacion::where('vehiculo_asignaciones.is_active', true)
                ->whereIn('vehiculo_asignaciones.unidad_organizacional_mandante_id', $applicableUoIds)
                ->join('vehiculos', 'vehiculo_asignaciones.vehiculo_id', '=', 'vehiculos.id');

            $tiposVehiculoIds = $this->tiposVehiculoAplica->pluck('id')->toArray();
            if (!empty($tiposVehiculoIds)) {
                $query->whereIn('vehiculos.tipo_vehiculo_id', $tiposVehiculoIds);
            }

            $tenenciasIds = $this->tenenciasAplica->pluck('id')->toArray();
            if (!empty($tenenciasIds)) {
                $query->whereIn('vehiculos.tenencia_vehiculo_id', $tenenciasIds);
            }

            $condicionesEmpresaIds = $this->condicionesEmpresaAplica->pluck('id')->toArray();
            if (!empty($condicionesEmpresaIds)) {
                $query->whereExists(function ($sub) use ($applicableUoIds, $condicionesEmpresaIds) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('contratista_unidad_organizacional as cuo')
                        ->join('contratista_uo_tipo_condicion as cutc', 'cutc.contratista_uo_id', '=', 'cuo.id')
                        ->whereColumn('cuo.contratista_id', 'vehiculos.contratista_id')
                        ->whereIn('cuo.unidad_organizacional_mandante_id', $applicableUoIds)
                        ->whereIn('cutc.tipo_condicion_id', $condicionesEmpresaIds);
                });
            }

            // Filtro: Contratistas Excluidos (todos sus vehiculos)
            if (!empty($this->rut_contratistas_excluidos)) {
                $rutsContratistasExcluidos = array_map(function($rut) {
                    return strtoupper(str_replace(['.', '-', ' '], '', trim($rut)));
                }, explode(';', $this->rut_contratistas_excluidos));

                $contratistasExcluidosIds = \App\Models\Contratista::whereIn(
                    \Illuminate\Support\Facades\DB::raw("REPLACE(REPLACE(UPPER(rut), '-', ''), '.', '')"),
                    $rutsContratistasExcluidos
                )->pluck('id')->toArray();

                if (!empty($contratistasExcluidosIds)) {
                    $query->whereNotIn('vehiculos.contratista_id', $contratistasExcluidosIds);
                }
            }

            // Filtro: Sub-Tipos de Vehículo por Mandante
            $subTiposIds = $this->subTiposVehiculoAplica->pluck('id')->toArray();
            if (!empty($subTiposIds)) {
                $query->whereIn('vehiculo_asignaciones.sub_tipo_vehiculo_mandante_id', $subTiposIds);
            }

            // Filtro: Condiciones de Vehículo (Múltiples)
            $condicionesVehiculoIds = $this->condicionesVehiculoAplica->pluck('id')->toArray();
            if (!empty($condicionesVehiculoIds)) {
                $query->whereExists(function ($subQuery) use ($condicionesVehiculoIds) {
                    $subQuery->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('vehiculo_vinc_condicion')
                        ->whereColumn('vehiculo_vinc_condicion.vehiculo_asignacion_id', 'vehiculo_asignaciones.id')
                        ->whereIn('vehiculo_vinc_condicion.tipo_condicion_vehiculo_id', $condicionesVehiculoIds);
                });
            }

            return $query->distinct('vehiculos.id')->count('vehiculos.id');

        } elseif ($entidadNombre === 'MAQUINARIA') {
            $query = \App\Models\MaquinariaAsignacion::where('maquinaria_asignaciones.is_active', true)
                ->whereIn('maquinaria_asignaciones.unidad_organizacional_mandante_id', $applicableUoIds)
                ->join('maquinarias', 'maquinaria_asignaciones.maquinaria_id', '=', 'maquinarias.id');

            $tiposMaquinariaIds = $this->tiposMaquinariaAplica->pluck('id')->toArray();
            if (!empty($tiposMaquinariaIds)) {
                $query->whereIn('maquinarias.tipo_maquinaria_id', $tiposMaquinariaIds);
            }

            $tenenciasIds = $this->tenenciasAplica->pluck('id')->toArray();
            if (!empty($tenenciasIds)) {
                $query->whereIn('maquinarias.tenencia_maquinaria_id', $tenenciasIds);
            }

            $condicionesEmpresaIds = $this->condicionesEmpresaAplica->pluck('id')->toArray();
            if (!empty($condicionesEmpresaIds)) {
                $query->whereExists(function ($sub) use ($applicableUoIds, $condicionesEmpresaIds) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('contratista_unidad_organizacional as cuo')
                        ->join('contratista_uo_tipo_condicion as cutc', 'cutc.contratista_uo_id', '=', 'cuo.id')
                        ->whereColumn('cuo.contratista_id', 'maquinarias.contratista_id')
                        ->whereIn('cuo.unidad_organizacional_mandante_id', $applicableUoIds)
                        ->whereIn('cutc.tipo_condicion_id', $condicionesEmpresaIds);
                });
            }

            return $query->distinct('maquinarias.id')->count('maquinarias.id');

        } elseif ($entidadNombre === 'EMBARCACION') {
            $query = \App\Models\EmbarcacionAsignacion::where('embarcacion_asignaciones.is_active', true)
                ->whereIn('embarcacion_asignaciones.unidad_organizacional_mandante_id', $applicableUoIds)
                ->join('embarcaciones', 'embarcacion_asignaciones.embarcacion_id', '=', 'embarcaciones.id');

            $tiposEmbarcacionIds = $this->tiposEmbarcacionAplica->pluck('id')->toArray();
            if (!empty($tiposEmbarcacionIds)) {
                $query->whereIn('embarcaciones.tipo_embarcacion_id', $tiposEmbarcacionIds);
            }

            $tenenciasIds = $this->tenenciasAplica->pluck('id')->toArray();
            if (!empty($tenenciasIds)) {
                $query->whereIn('embarcaciones.tenencia_embarcacion_id', $tenenciasIds);
            }

            $condicionesEmpresaIds = $this->condicionesEmpresaAplica->pluck('id')->toArray();
            if (!empty($condicionesEmpresaIds)) {
                $query->whereExists(function ($sub) use ($applicableUoIds, $condicionesEmpresaIds) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('contratista_unidad_organizacional as cuo')
                        ->join('contratista_uo_tipo_condicion as cutc', 'cutc.contratista_uo_id', '=', 'cuo.id')
                        ->whereColumn('cuo.contratista_id', 'embarcaciones.contratista_id')
                        ->whereIn('cuo.unidad_organizacional_mandante_id', $applicableUoIds)
                        ->whereIn('cutc.tipo_condicion_id', $condicionesEmpresaIds);
                });
            }

            return $query->distinct('embarcaciones.id')->count('embarcaciones.id');
        }

        return 0;
    }
}