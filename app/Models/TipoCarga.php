<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoCarga extends Model
{
    use HasFactory;

    protected $table = 'tipos_carga';

    protected $fillable = [
        'nombre',
        'descripcion',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Podría tener una relación HasMany con la tabla de documentos subidos en el futuro
    // public function documentosSubidos()
    // {
    //     return $this->hasMany(SubmittedDocument::class, 'tipo_carga_id');
    // }
}