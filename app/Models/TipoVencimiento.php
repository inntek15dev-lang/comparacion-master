<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoVencimiento extends Model
{
    use HasFactory;

    protected $table = 'tipos_vencimiento';

    protected $fillable = [
        'nombre',
        'descripcion',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Un TipoVencimiento podría estar asociado a muchos 'Requisitos de Documento' en el futuro
    // para indicar cómo se calcula la vigencia de ese requisito.
}