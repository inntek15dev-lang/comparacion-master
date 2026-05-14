<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratistaUoCargo extends Model
{
    protected $table = 'contratista_uo_cargos';

    protected $fillable = [
        'contratista_uo_id',
        'cargo_mandante_id',
        'cuota',
    ];

    public function vinculacion(): BelongsTo
    {
        return $this->belongsTo(ContratistaUnidadOrganizacional::class, 'contratista_uo_id');
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(CargoMandante::class, 'cargo_mandante_id');
    }
}
