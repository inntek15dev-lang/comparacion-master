<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RangoCantidadTrabajadores extends Model
{
    use HasFactory;

    protected $table = 'rangos_cantidad_trabajadores';

    protected $fillable = [
        'nombre',
        'descripcion',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Aquí podríamos añadir relaciones en el futuro si fueran necesarias,
    // por ejemplo, con la tabla Contratista:
    // public function contratistas()
    // {
    //     return $this->hasMany(Contratista::class, 'rango_cantidad_trabajadores_id');
    // }
}