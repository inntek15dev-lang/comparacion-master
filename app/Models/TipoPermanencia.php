<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoPermanencia extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'tipos_permanencias';

    protected $fillable = [
        'nombre',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Los trabajadores que tienen este tipo de permanencia asignado.
     */
    public function trabajadores(): HasMany
    {
        return $this->hasMany(Trabajador::class, 'tipo_permanencia_id');
    }
}
