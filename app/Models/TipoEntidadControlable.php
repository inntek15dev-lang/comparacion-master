<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoEntidadControlable extends Model
{
    use HasFactory;

    protected $table = 'tipos_entidad_controlable';

    protected $fillable = [
        'nombre_entidad',
        'descripcion',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Un Tipo de Entidad Controlable puede estar en muchas Reglas Documentales.
     */
    public function reglasDocumentales(): HasMany
    {
        return $this->hasMany(ReglaDocumental::class, 'tipo_entidad_controlada_id');
    }

    // ================== INICIO DE LA CORRECCIÓN CANÓNICA ==================
    /**
     * Los mandantes que tienen esta entidad como controlable.
     */
    public function mandantes(): BelongsToMany
    {
        return $this->belongsToMany(
            Mandante::class,
            'mandante_tipo_entidad',
            'tipo_entidad_controlable_id',
            'mandante_id'
        );
    }
    // ================== FIN DE LA CORRECCIÓN CANÓNICA ====================
}