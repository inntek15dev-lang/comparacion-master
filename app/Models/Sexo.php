<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sexo extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'sexos'; // Nombre correcto de la tabla (probablemente coincide con Eloquent default)

    protected $fillable = [
        'nombre',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Los trabajadores que tienen este sexo asignado.
     */
    public function trabajadores(): HasMany
    {
        return $this->hasMany(Trabajador::class);
        // Asume FK 'sexo_id' en la tabla 'trabajadores'
    }
}