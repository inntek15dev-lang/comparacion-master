<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClasificacionVerificacion extends Model
{
    use HasFactory;

    protected $table = 'clasificaciones_verificacion';

    protected $fillable = [
        'nombre',
        'descripcion',
        'orden',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function requisitos()
    {
        return $this->hasMany(RequisitoVerificacion::class, 'clasificacion_id');
    }
}
