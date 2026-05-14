<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExclusionVerificacionPeriodo extends Model
{
    protected $table = 'exclusiones_verificacion_periodo';

    protected $fillable = [
        'mandante_id',
        'contratista_unidad_organizacional_id',
        'periodo',
        'excluido_por_user_id',
    ];

    protected $casts = [
        'periodo' => 'date',
    ];

    public function mandante()
    {
        return $this->belongsTo(Mandante::class , 'mandante_id');
    }

    public function vinculacion()
    {
        return $this->belongsTo(ContratistaUnidadOrganizacional::class , 'contratista_unidad_organizacional_id');
    }

    public function excluidoPor()
    {
        return $this->belongsTo(User::class , 'excluido_por_user_id');
    }
}
