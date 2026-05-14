<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequisitoVerificacion extends Model
{
    use HasFactory;

    protected $table = 'requisitos_verificacion';

    protected $fillable = [
        'mandante_id',
        'clasificacion_id',
        'codigo',
        'nombre',
        'descripcion',
        'is_active',
        'es_obligatorio',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'es_obligatorio' => 'boolean',
    ];

    public function mandante()
    {
        return $this->belongsTo(Mandante::class);
    }

    public function clasificacion()
    {
        return $this->belongsTo(ClasificacionVerificacion::class, 'clasificacion_id');
    }
}
