<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Dependencia extends Model
{
    use HasFactory;

    protected $table = 'dependencias';

    protected $fillable = [
        'mandante_id',
        'nombre',
        'dependencia_padre_id',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    protected $appends = ['nombre_jerarquico'];

    public function mandante(): BelongsTo
    {
        return $this->belongsTo(Mandante::class, 'mandante_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class, 'dependencia_padre_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Dependencia::class, 'dependencia_padre_id');
    }

    public function contratistas(): BelongsToMany
    {
        return $this->belongsToMany(Contratista::class, 'contratista_dependencia', 'dependencia_id', 'contratista_id')->withTimestamps();
    }

    public function vinculaciones(): HasMany
    {
        return $this->hasMany(ContratistaUnidadOrganizacional::class, 'dependencia_id');
    }

    public function trabajadores(): BelongsToMany
    {
        return $this->belongsToMany(Trabajador::class, 'trabajador_vinculaciones', 'dependencia_id', 'trabajador_id')
                    ->withTimestamps()
                    ->withPivot(['id', 'unidad_organizacional_mandante_id', 'cargo_mandante_id', 'is_active']);
    }

    // ================== INICIO DE LA MODIFICACIÓN ==================
    /**
     * Genera un nombre jerárquico para la dependencia.
     * Ejemplo: "Planta Principal < Área de Producción < Línea 1"
     *
     * @return string
     */
    public function getNombreJerarquicoAttribute(): string
    {
        $path = $this->nombre ?? '';
        $parent = $this->parent;
        
        while ($parent) {
            $parentName = $parent->nombre ?? '';
            $path = $parentName . ' / ' . $path;
            $parent = $parent->parent;
        }
        
        return $path;
    }
    // ================== FIN DE LA MODIFICACIÓN ====================

    public static function getDescendantIdsAndSelf(array $parentDepIds): array
    {
        if (empty($parentDepIds)) {
            return [];
        }

        $allIds = $parentDepIds;
        $currentIds = $parentDepIds;

        while (!empty($currentIds)) {
            $childrenIds = self::whereIn('dependencia_padre_id', $currentIds)->pluck('id')->all();
            if (empty($childrenIds)) {
                break;
            }
            $allIds = array_merge($allIds, $childrenIds);
            $currentIds = $childrenIds;
        }

        return array_unique($allIds);
    }
}