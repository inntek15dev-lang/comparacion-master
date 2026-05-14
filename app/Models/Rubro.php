<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rubro extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Los contratistas que pertenecen a este rubro.
     */
    public function contratistas(): HasMany
    {
        return $this->hasMany(Contratista::class);
        // Asume FK 'rubro_id' en la tabla 'contratistas'
    }
}