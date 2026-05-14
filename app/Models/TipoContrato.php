<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoContrato extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada con el modelo.
     */
    protected $table = 'tipos_contrato';

    protected $fillable = [
        'nombre',
        'descripcion',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Las vinculaciones que tienen este tipo de contrato.
     */
    public function vinculacionesContratista(): HasMany
    {
        return $this->hasMany(ContratistaUnidadOrganizacional::class, 'tipo_contrato_id');
    }
}
