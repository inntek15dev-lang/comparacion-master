<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObservacionDocumento extends Model
{
    use HasFactory;

    protected $table = 'observaciones_documento';

    protected $fillable = [
        'titulo', // Este es el texto principal de la observación
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}