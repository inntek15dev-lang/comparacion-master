<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoEmpresaLegal extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'tipos_empresa_legal'; // Nombre correcto de la tabla

    protected $fillable = [
        'nombre',
        'sigla',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Los contratistas que tienen este tipo de empresa legal.
     */
    public function contratistas(): HasMany
    {
        return $this->hasMany(Contratista::class);
        // Asume FK 'tipo_empresa_legal_id' en la tabla 'contratistas'
    }
}