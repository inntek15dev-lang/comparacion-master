<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TipoCondicion extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'tipos_condicion'; // Nombre correcto de la tabla

    protected $fillable = [
        'mandante_id',
        'nombre',
        'descripcion',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * El Mandante al que pertenece esta condición de empresa.
     */
    public function mandante(): BelongsTo
    {
        return $this->belongsTo(Mandante::class);
    }

    /**
     * Los contratistas que tienen esta condición especial.
     */
    public function contratistas(): BelongsToMany
    {
        return $this->belongsToMany(
            Contratista::class,
            'contratista_tipo_condicion', // Tabla pivote
            'tipo_condicion_id',          // FK de este modelo (TipoCondicion) en la pivote
            'contratista_id'              // FK del modelo relacionado (Contratista) en la pivote
        );
    }
}