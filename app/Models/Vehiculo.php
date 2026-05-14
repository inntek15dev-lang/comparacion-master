<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Services\CriticidadDocumentoService;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Vehiculo extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'vehiculos';
    protected $fillable = [ 'contratista_id', 'patente_letras', 'patente_numeros', 'ano_fabricacion', 'marca_vehiculo_id', 'color_vehiculo_id', 'tipo_vehiculo_id', 'tenencia_vehiculo_id', 'is_active', 'modelo' ];
    protected $casts = [ 'is_active' => 'boolean' ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['patente_letras', 'patente_numeros', 'ano_fabricacion', 'marca_vehiculo_id', 'color_vehiculo_id', 'tipo_vehiculo_id', 'tenencia_vehiculo_id', 'is_active', 'modelo'])
            ->logOnlyDirty()
            ->useLogName('audit')
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => "Creó vehículo: " . $this->patente_completa,
                'updated' => "Editó ficha de vehículo: " . $this->patente_completa,
                'deleted' => "Eliminó vehículo: " . $this->patente_completa,
                default => "Acción sobre vehículo: {$eventName}"
            })
            ->dontSubmitEmptyLogs();
    }

    protected static function booted(): void
    {
        static::deleted(function (Vehiculo $vehiculo) {
            if ($vehiculo->isForceDeleting()) {
                $vehiculo->vinculaciones()->delete();
            }
        });
    }

    public function contratista(): BelongsTo { return $this->belongsTo(Contratista::class, 'contratista_id'); }
    public function marcaVehiculo(): BelongsTo { return $this->belongsTo(MarcaVehiculo::class, 'marca_vehiculo_id'); }
    public function colorVehiculo(): BelongsTo { return $this->belongsTo(ColorVehiculo::class, 'color_vehiculo_id'); }
    public function tipoVehiculo(): BelongsTo { return $this->belongsTo(TipoVehiculo::class, 'tipo_vehiculo_id'); }
    public function tenenciaVehiculo(): BelongsTo { return $this->belongsTo(TenenciaVehiculo::class, 'tenencia_vehiculo_id'); }
    
    public function vinculaciones(): HasMany
    {
        return $this->hasMany(VehiculoAsignacion::class, 'vehiculo_id');
    }

    public function getPatenteCompletaAttribute(): string { return strtoupper($this->patente_letras) . ' • ' . $this->patente_numeros; }
    
    // ================== INICIO DE LA MODIFICACIÓN ==================
    public function getIdentificadorUnicoAttribute(): string 
    {
        return strtoupper($this->patente_letras . $this->patente_numeros);
    }
    // ================== FIN DE LA MODIFICACIÓN ====================

    public function anulacionManualActiva(): MorphOne { $hoy = Carbon::today(); return $this->morphOne(DocumentoExcepcionCriticidad::class, 'excepcionable')->where('nombre_documento_id', 99999)->whereNotNull('accion_override')->where(function ($query) use ($hoy) { $query->where('valido_hasta', '>=', $hoy)->orWhereNull('valido_hasta'); })->latestOfMany(); }

    public function calcularPorcentajeCumplimiento(int $mandanteId, int $unidadOrganizacionalId): int
    {
        return app(CriticidadDocumentoService::class)
            ->calcularPorcentajeCumplimientoParaEntidad($this, $mandanteId, $unidadOrganizacionalId);
    }

    public function determinarAccesoHabilitado(int $mandanteId, int $unidadOrganizacionalId): array
    {
        return app(CriticidadDocumentoService::class)->determinarAccesoFinalRecurso($this, $mandanteId, $unidadOrganizacionalId);
    }
}