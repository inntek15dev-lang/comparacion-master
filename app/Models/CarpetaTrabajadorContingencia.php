<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarpetaTrabajadorContingencia extends Model
{
    use HasFactory;

    protected $table = 'carpeta_trabajador_contingencias';

    protected $fillable = [
        'carpeta_verificacion_id',          // FK a la carpeta (para empresa)
        'carpeta_verificacion_trabajador_id', // FK al trabajador (nullable si empresa)
        'tipo',                              // 'observacion' | 'contingencia'
        'subtipo',                           // 'retenible' | 'no_retenible' | null
        'clasificacion',                     // string obligatorio
        'aplica_empresa',                    // bool: aplica a toda la empresa
        'codigo',                            // int incremental desde 100001
        'catalogo_item_id',                  // FK al catálogo (nullable = texto libre)
        'causal',                            // texto de la incidencia
        'monto',                             // decimal nullable
        'es_retenible',                      // bool (derivado de subtipo, se mantiene por compatibilidad)
        'estado_subsanacion',
    ];

    protected $casts = [
        'es_retenible'   => 'boolean',
        'aplica_empresa' => 'boolean',
        'codigo'         => 'integer',
    ];

    // ------------------------------------
    // Clasificaciones predefinidas
    // ------------------------------------

    public static function clasificacionesObservacion(): array
    {
        return [
            'Deuda menor',
            'Error u omisión de información a externos',
            'Falta de documentación',
            'Actualización de pactos',
            'Ausencia de pacto',
            'Contrato de trabajo',
            'Documentos para cálculo',
            'Liquidación',
            'Información a instituciones de previsión',
            'Ingreso y desvinculación',
            'Otros',
            'Finiquitos antigüedad menor a 1 año',
            'Información relevante a la empresa',
            'Documentación para certificar',
            'Constitución de sindicatos',
            'Instrumentos colectivos vigentes',
            'Estructura del contrato de trabajo',
            'Información a la empresa principal',
            'Informativas',
            'Pagos mayores a lo estipulado o permitido legalmente',
            'Finiquitos presentados anteriormente',
            'Error u omisión en la documentación',
            'Observación al comprobante de pago',
            'Descuento superior a lo establecido (15%)',
        ];
    }

    public static function clasificacionesContingenciaRetenible(): array
    {
        return [
            'Remuneraciones',
            'Cotizaciones',
            'Aviso Previo',
            'Año Servicio',
            'Feriado',
            'Reserva de derecho',
            'Finiquito',
        ];
    }

    public static function clasificacionesContingenciaNoRetenible(): array
    {
        return [
            'Año Servicio',
            'Aviso Previo',
            'Otra Contingencia NO Retenibles',
        ];
    }

    // ------------------------------------
    // Relaciones
    // ------------------------------------

    public function carpetaVerificacion()
    {
        return $this->belongsTo(CarpetaVerificacion::class, 'carpeta_verificacion_id');
    }

    public function carpetaTrabajador()
    {
        return $this->belongsTo(CarpetaVerificacionTrabajador::class, 'carpeta_verificacion_trabajador_id');
    }

    public function catalogoItem()
    {
        return $this->belongsTo(CatalogoAuditoriaItem::class, 'catalogo_item_id');
    }

    public function solicitudComplementaria()
    {
        return $this->hasOne(SolicitudComplementaria::class, 'carpeta_trabajador_contingencia_id');
    }

    /**
     * Todos los ítems de Solicitudes Complementarias en que este código ha participado.
     * Permite rastrear el historial completo a lo largo de múltiples SCs.
     */
    public function itemsComplementarios()
    {
        return $this->hasMany(SolicitudComplementariaItem::class, 'carpeta_trabajador_contingencia_id');
    }

    // ------------------------------------
    // Helpers
    // ------------------------------------

    public function getLabelTipoAttribute(): string
    {
        if ($this->tipo === 'observacion') {
            return 'OBS';
        }
        return $this->subtipo === 'retenible' ? 'CONT-RET' : 'CONT-NRET';
    }

    public function getColorBadgeAttribute(): string
    {
        if ($this->tipo === 'observacion') {
            return 'blue';
        }
        return $this->subtipo === 'retenible' ? 'red' : 'amber';
    }
}
