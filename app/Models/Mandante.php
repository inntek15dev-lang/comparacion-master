<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\TipoEntidadControlable;
use App\Models\Contratista;
use App\Models\UnidadOrganizacionalMandante;

class Mandante extends Model
{
    use HasFactory;

    protected $table = 'mandantes'; 

    protected $fillable = [
        'razon_social',
        'rut',
        'persona_contacto_nombre',
        'persona_contacto_email',
        'persona_contacto_telefono',
        'is_active',
        'tiene_oval',
        'oval_cod',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tiene_oval' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class); 
    }

    public function unidadesOrganizacionales(): HasMany
    {
        return $this->hasMany(UnidadOrganizacionalMandante::class, 'mandante_id');
    }

    public function cargosDefinidos(): HasMany
    {
        return $this->hasMany(CargoMandante::class, 'mandante_id');
    }

    public function tiposEntidadControlable(): BelongsToMany
    {
        return $this->belongsToMany(
            TipoEntidadControlable::class,
            'mandante_tipo_entidad',      
            'mandante_id',                
            'tipo_entidad_controlable_id' 
        );
    }
    
    public function getContratistasAsociadosAttribute()
    {
        return $this->unidadesOrganizacionales()->with('contratistasHabilitados')->get()
            ->pluck('contratistasHabilitados')
            ->flatten()
            ->unique('id')
            ->values();
    }

    public function contratistasPrincipalesAprobados(): BelongsToMany
    {
        return $this->belongsToMany(Contratista::class, 'solicitudes_vinculacion', 'mandante_id', 'contratista_id')
                    ->wherePivot('estado', 'APROBADA')
                    ->wherePivot('tipo_solicitud', 'CONTRATISTA')
                    ->withTimestamps();
    }

    public function configuracionAsignacion(): HasOne
    {
        return $this->hasOne(ConfiguracionAsignacionAutomatica::class, 'mandante_id');
    }

    public function colorConfiguraciones(): HasMany
    {
        return $this->hasMany(MandanteColorConfiguracion::class, 'mandante_id')->orderBy('horas_inicio');
    }

    /**
     * Define la relación "uno a muchos" con sus dependencias.
     */
    public function dependencias(): HasMany
    {
        return $this->hasMany(Dependencia::class, 'mandante_id');
    }

    public function requisitosVerificacion(): HasMany
    {
        return $this->hasMany(RequisitoVerificacion::class, 'mandante_id');
    }
}