<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\TipoCondicionPersonal;

class TrabajadorVinculacion extends Pivot
{
    protected $table = 'trabajador_vinculaciones';

    public $incrementing = true;

    protected $fillable = [
        'trabajador_id',
        'unidad_organizacional_mandante_id',
        'cargo_mandante_id',
        'dependencia_id',
        'numero_contrato',
        'tipo_contrato_id',
        'tipo_condicion_personal_id',
        'fecha_ingreso_vinculacion',
        'fecha_contrato',
        'is_active',
        'fecha_desactivacion',
        'fecha_finiquito',
        'motivo_desactivacion',
        'porcentaje_cumplimiento',
        'estado_acceso',
    ];

    protected $casts = [
        'fecha_ingreso_vinculacion' => 'date',
        'fecha_contrato' => 'date',
        'fecha_desactivacion' => 'date',
        'fecha_finiquito' => 'date',
        'is_active' => 'boolean',
        'porcentaje_cumplimiento' => 'integer',
        'estado_acceso' => 'array',
    ];

    public function tipoContrato(): BelongsTo
    {
        return $this->belongsTo(\App\Models\TipoContrato::class, 'tipo_contrato_id');
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class);
    }

    public function unidadOrganizacionalMandante(): BelongsTo
    {
        return $this->belongsTo(UnidadOrganizacionalMandante::class, 'unidad_organizacional_mandante_id');
    }

    public function cargoMandante(): BelongsTo
    {
        return $this->belongsTo(CargoMandante::class);
    }

    public function tipoCondicionPersonal(): BelongsTo
    {
        return $this->belongsTo(TipoCondicionPersonal::class);
    }

    public function dependencia(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class, 'dependencia_id');
    }

    /**
     * Condiciones personales asignadas a esta vinculación (multi-condición).
     */
    public function condicionesPersonales(): BelongsToMany
    {
        return $this->belongsToMany(
            TipoCondicionPersonal::class,
            'trabajador_vinculacion_condicion_personal',
            'trabajador_vinculacion_id',
            'tipo_condicion_personal_id'
        );
    }
}