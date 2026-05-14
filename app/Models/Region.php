<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory;

    protected $table = 'regiones';

    protected $fillable = [
        'nombre',
        'is_active', // Aunque para regiones es menos común desactivarlas, mantenemos consistencia
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all of the comunas for the Region
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comunas(): HasMany
    {
        return $this->hasMany(Comuna::class, 'region_id');
    }
}