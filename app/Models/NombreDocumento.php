<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// <<-- INICIO: IMPORTACIÓN NECESARIA PARA LA NUEVA RELACIÓN -->>
use Illuminate\Database\Eloquent\Relations\HasMany;
// <<-- FIN: IMPORTACIÓN NECESARIA PARA LA NUEVA RELACIÓN -->>


class NombreDocumento extends Model
{
    use HasFactory;

    protected $table = 'nombre_documentos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'aplica_a',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    public function tipoEntidadControlable(): BelongsTo
    {
        return $this->belongsTo(TipoEntidadControlable::class, 'aplica_a');
    }

    // =================================================================================
    // INICIO: NUEVA RELACIÓN INVERSA
    // Un NombreDocumento puede estar en muchas Reglas Documentales.
    // =================================================================================
    public function reglasDocumentales(): HasMany
    {
        return $this->hasMany(ReglaDocumental::class, 'nombre_documento_id');
    }
    // =================================================================================
    // FIN: NUEVA RELACIÓN INVERSA
    // =================================================================================
}