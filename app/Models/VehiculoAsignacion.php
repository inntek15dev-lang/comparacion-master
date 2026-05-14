<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\TipoCondicionVehiculo;

class VehiculoAsignacion extends Pivot
{
    protected $table = 'vehiculo_asignaciones';

    public $timestamps = true;

    protected $fillable = [
        'vehiculo_id',
        'unidad_organizacional_mandante_id',
        'dependencia_id',
        'sub_tipo_vehiculo_mandante_id',
        'fecha_asignacion',
        'fecha_desasignacion',
        'is_active',
        'motivo_desasignacion',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'fecha_asignacion' => 'date',
        'fecha_desasignacion' => 'date',
    ];

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function unidadOrganizacionalMandante(): BelongsTo
    {
        return $this->belongsTo(UnidadOrganizacionalMandante::class, 'unidad_organizacional_mandante_id');
    }

    // ================== INICIO DE LA MODIFICACIÓN ==================
    /**
     * Define la relación con el Lugar de Trabajo (Dependencia).
     */
    public function dependencia(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class, 'dependencia_id');
    }

    public function subTipoVehiculo(): BelongsTo
    {
        return $this->belongsTo(SubTipoVehiculoMandante::class, 'sub_tipo_vehiculo_mandante_id');
    }

    /**
     * Define la relación con las condiciones asignadas al vehículo en este proyecto.
     */
    public function condiciones(): BelongsToMany
    {
        return $this->belongsToMany(
            TipoCondicionVehiculo::class,
            'vehiculo_vinc_condicion',
            'vehiculo_asignacion_id',
            'tipo_condicion_vehiculo_id'
        )->withTimestamps();
    }
}