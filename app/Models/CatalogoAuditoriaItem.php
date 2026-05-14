<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogoAuditoriaItem extends Model
{
    protected $table = 'catalogo_auditoria_items';

    protected $fillable = [
        'tipo',
        'texto',
        'texto_plural',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActivos($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeObservaciones($query)
    {
        return $query->where('tipo', 'observacion');
    }

    public function scopeContingencias($query)
    {
        return $query->where('tipo', 'contingencia');
    }
}
