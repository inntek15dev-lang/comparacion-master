<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenenciaVehiculo extends Model
{
    use HasFactory;

    protected $table = 'tenencias_vehiculo'; // Nombre de tabla actualizado

    protected $fillable = [
        'nombre',
        'descripcion',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}