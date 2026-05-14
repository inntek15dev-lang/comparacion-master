<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ConfiguracionAsignacionAutomatica extends Model
{
    use HasFactory;

    protected $table = 'configuraciones_asignacion_automatica';

    protected $fillable = [
        'mandante_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function mandante(): BelongsTo
    {
        return $this->belongsTo(Mandante::class, 'mandante_id');
    }

    public function validadores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'configuracion_asignacion_validadores', 'configuracion_id', 'user_id')
                    ->withPivot('orden')
                    ->orderBy('orden');
    }
}