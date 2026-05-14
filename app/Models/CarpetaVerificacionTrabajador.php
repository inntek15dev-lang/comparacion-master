<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarpetaVerificacionTrabajador extends Model
{
    use HasFactory;

    protected $table = 'carpetas_verificacion_trabajadores';

    protected $fillable = [
        'carpeta_verificacion_id',
        'trabajador_vinculacion_id',
        'destino_trabajador_vinculacion_id',
        'snapshot_rut',
        'snapshot_nombres',
        'snapshot_cargo',
        'snapshot_fecha_ingreso',
        'snapshot_fecha_contrato',
        'tipo_registro',
        'estado_revision',
        'observaciones',
    ];

    public function carpeta()
    {
        return $this->belongsTo(CarpetaVerificacion::class, 'carpeta_verificacion_id');
    }

    public function vinculacion()
    {
        return $this->belongsTo(TrabajadorVinculacion::class, 'trabajador_vinculacion_id');
    }

    public function destinoVinculacion()
    {
        return $this->belongsTo(TrabajadorVinculacion::class, 'destino_trabajador_vinculacion_id');
    }

    public function contingencias()
    {
        return $this->hasMany(CarpetaTrabajadorContingencia::class, 'carpeta_verificacion_trabajador_id');
    }
}
