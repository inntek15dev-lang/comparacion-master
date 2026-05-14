<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SistemaConfiguracion extends Model
{
    use HasFactory;

    protected $table = 'sistema_configuraciones';

    protected $fillable = [
        'clave',
        'valor',
        'descripcion',
    ];
}