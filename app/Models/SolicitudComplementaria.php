<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SolicitudComplementaria extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_complementarias';

    protected $fillable = [
        'folio',                              // Generado automáticamente (SC-XXXX)
        'carpeta_trabajador_contingencia_id', // Legacy (flujo viejo 1:1)
        'carpeta_verificacion_id',            // Nuevo flujo consolidado
        'contratista_unidad_organizacional_id',
        'auditor_id',
        'estado',
        'fecha_envio',
        'fecha_revision',
        'observaciones_auditor',
    ];

    protected $casts = [
        'fecha_envio' => 'datetime',
        'fecha_revision' => 'datetime',
    ];

    /**
     * Generar Folio automático al crear
     */
    protected static function booted()
    {
        static::created(function ($solicitud) {
            $solicitud->load(['carpeta', 'vinculacion']);
            $idRegistro = $solicitud->vinculacion->id_registro ?? '0000';
            
            if ($solicitud->carpeta) {
                $anio = $solicitud->carpeta->anio ?? date('Y');
                $mes  = str_pad($solicitud->carpeta->mes ?? date('m'), 2, '0', STR_PAD_LEFT);
                $solicitud->folio = 'SC-' . $idRegistro . '-' . $anio . '-' . $mes . '-' . str_pad($solicitud->id, 4, '0', STR_PAD_LEFT);
            } else {
                $solicitud->folio = 'SC-' . $idRegistro . '-' . date('Y-m') . '-' . str_pad($solicitud->id, 4, '0', STR_PAD_LEFT);
            }
            $solicitud->saveQuietly();
        });
    }

    /**
     * [LEGACY] Relación directa con una única incidencia (flujo antiguo 1:1).
     * Mantener para compatibilidad con registros históricos.
     */
    public function contingencia()
    {
        return $this->belongsTo(CarpetaTrabajadorContingencia::class, 'carpeta_trabajador_contingencia_id');
    }

    /**
     * [NUEVO] Ítems del complementario consolidado (flujo 1:N).
     * Cada ítem referencia una incidencia individual dentro del paquete.
     */
    public function items()
    {
        return $this->hasMany(SolicitudComplementariaItem::class, 'solicitud_complementaria_id');
    }

    /**
     * [NUEVO] Carpeta/certificado que agrupa este complementario consolidado.
     */
    public function carpeta()
    {
        return $this->belongsTo(CarpetaVerificacion::class, 'carpeta_verificacion_id');
    }

    public function vinculacion()
    {
        return $this->belongsTo(ContratistaUnidadOrganizacional::class, 'contratista_unidad_organizacional_id');
    }

    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function documentos()
    {
        return $this->hasMany(DocumentoSolicitudComplementaria::class, 'solicitud_complementaria_id');
    }
}
