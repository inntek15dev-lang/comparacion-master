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

class Maquinaria extends Model
{
    use HasFactory;

    protected $table = 'maquinarias';
    protected $fillable = [ 'contratista_id', 'identificador_letras', 'identificador_numeros', 'ano_fabricacion', 'marca_vehiculo_id', 'tipo_maquinaria_id', 'tenencia_vehiculo_id', 'is_active' ];
    protected $casts = [ 'is_active' => 'boolean' ];
    
    protected static function booted(): void
    {
        static::deleted(function (Maquinaria $maquinaria) {
            if ($maquinaria->isForceDeleting()) {
                $maquinaria->vinculaciones()->delete();
            }
        });
    }

    public function vinculaciones(): HasMany
    {
        return $this->hasMany(MaquinariaAsignacion::class, 'maquinaria_id');
    }

    public function contratista(): BelongsTo { return $this->belongsTo(Contratista::class, 'contratista_id'); }
    public function tipoMaquinaria(): BelongsTo { return $this->belongsTo(TipoMaquinaria::class, 'tipo_maquinaria_id'); }
    public function marca(): BelongsTo { return $this->belongsTo(MarcaVehiculo::class, 'marca_vehiculo_id'); }
    public function tenencia(): BelongsTo { return $this->belongsTo(TenenciaVehiculo::class, 'tenencia_vehiculo_id'); }
    public function getIdentificadorCompletoAttribute(): string { return strtoupper($this->identificador_letras) . ' - ' . $this->identificador_numeros; }
    
    // ================== INICIO DE LA MODIFICACIÓN ==================
    public function getIdentificadorUnicoAttribute(): string 
    {
        return strtoupper($this->identificador_letras . $this->identificador_numeros);
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