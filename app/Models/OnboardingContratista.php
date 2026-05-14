<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingContratista extends Model
{
    use HasFactory;

    protected $table = 'onboarding_contratista';

    protected $fillable = [
        'contratista_id',
        'paso1_capacitacion_completo', 'paso1_fecha', 'paso1_user_id', 'paso1_comentario',
        'paso2_prueba_carga_completo', 'paso2_fecha', 'paso2_user_id', 'paso2_comentario',
        'paso3_generico_completo', 'paso3_fecha', 'paso3_user_id', 'paso3_comentario',
        'paso4_generico_completo', 'paso4_fecha', 'paso4_user_id', 'paso4_comentario',
        'paso5_generico_completo', 'paso5_fecha', 'paso5_user_id', 'paso5_comentario',
        'paso6_generico_completo', 'paso6_fecha', 'paso6_user_id', 'paso6_comentario',
        'paso7_generico_completo', 'paso7_fecha', 'paso7_user_id', 'paso7_comentario',
        'estado_onboarding',
        'comentarios_proceso',
    ];

    protected $casts = [
        'paso1_capacitacion_completo' => 'boolean',
        'paso2_prueba_carga_completo' => 'boolean',
        'paso3_generico_completo' => 'boolean',
        'paso4_generico_completo' => 'boolean',
        'paso5_generico_completo' => 'boolean',
        'paso6_generico_completo' => 'boolean',
        'paso7_generico_completo' => 'boolean',
        'paso1_fecha' => 'datetime',
        'paso2_fecha' => 'datetime',
        'paso3_fecha' => 'datetime',
        'paso4_fecha' => 'datetime',
        'paso5_fecha' => 'datetime',
        'paso6_fecha' => 'datetime',
        'paso7_fecha' => 'datetime',
    ];

    public function contratista(): BelongsTo
    {
        return $this->belongsTo(Contratista::class, 'contratista_id');
    }

    public function usuarioPaso1(): BelongsTo { return $this->belongsTo(User::class, 'paso1_user_id'); }
    public function usuarioPaso2(): BelongsTo { return $this->belongsTo(User::class, 'paso2_user_id'); }
    public function usuarioPaso3(): BelongsTo { return $this->belongsTo(User::class, 'paso3_user_id'); }
    public function usuarioPaso4(): BelongsTo { return $this->belongsTo(User::class, 'paso4_user_id'); }
    public function usuarioPaso5(): BelongsTo { return $this->belongsTo(User::class, 'paso5_user_id'); }
    public function usuarioPaso6(): BelongsTo { return $this->belongsTo(User::class, 'paso6_user_id'); }
    public function usuarioPaso7(): BelongsTo { return $this->belongsTo(User::class, 'paso7_user_id'); }
}