<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoEmbarcacion extends Model
{
    use HasFactory;

    protected $table = 'tipos_embarcacion'; // Especifica el nombre de la tabla

    protected $fillable = [
        'nombre',
        'descripcion',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}