<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SubTipoVehiculoMandante extends Model
{
    protected $table = 'sub_tipos_vehiculo_mandante';

    protected $fillable = [
        'mandante_id',
        'tipo_vehiculo_id',
        'nombre',
        'descripcion',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * El mandante al que pertenece este sub-tipo.
     */
    public function mandante(): BelongsTo
    {
        return $this->belongsTo(Mandante::class);
    }

    /**
     * El tipo de vehículo al que está asociado este sub-tipo (opcional).
     */
    public function tipoVehiculo(): BelongsTo
    {
        return $this->belongsTo(TipoVehiculo::class, 'tipo_vehiculo_id');
    }

    /**
     * Las asignaciones de vehículos que tienen este sub-tipo.
     */
    public function vehiculoAsignaciones(): HasMany
    {
        return $this->hasMany(VehiculoAsignacion::class, 'sub_tipo_vehiculo_mandante_id');
    }

    /**
     * Las reglas documentales que aplican a este sub-tipo.
     */
    public function reglasDocumentales(): BelongsToMany
    {
        return $this->belongsToMany(
            ReglaDocumental::class,
            'regla_documental_sub_tipo_vehiculo_mandante',
            'sub_tipo_vehiculo_mandante_id',
            'regla_documental_id'
        );
    }
}
