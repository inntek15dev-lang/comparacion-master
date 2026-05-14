<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCondicionVehiculo extends Model
{
    protected $table = 'tipos_condicion_vehiculo';

    protected $fillable = [
        'nombre',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
