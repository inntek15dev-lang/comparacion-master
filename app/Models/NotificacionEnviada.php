<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificacionEnviada extends Model
{
    use HasFactory;

    protected $table = 'notificaciones_enviadas';

    protected $fillable = [
        'tipo_notificacion',
        'contratista_id',
        'email_destino',
        'asunto',
        'mensaje',
        'documentos_notificados_ids',
        'despachado_por_user_id',
    ];

    protected $casts = [
        'documentos_notificados_ids' => 'array',
    ];

    /**
     * Obtiene el contratista al que se envió la notificación.
     */
    public function contratista(): BelongsTo
    {
        return $this->belongsTo(Contratista::class, 'contratista_id');
    }

    /**
     * Obtiene el usuario que despachó la notificación (si aplica).
     */
    public function despachadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'despachado_por_user_id');
    }
}