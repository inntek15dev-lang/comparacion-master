<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail; // Si vas a usar verificación de email
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Para Sanctum
use Spatie\Permission\Traits\HasRoles; // Importante para Spatie Laravel Permission
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable // Opcionalmente: implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles; // Añadido HasRoles

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'mandante_id',
        'contratista_id',
        'is_platform_admin',
        'is_active',
        'rut',
        'telefono',
        'cargo',
        'two_factor_code',
        'two_factor_expires_at',
        'created_by_user_id',
    ];

    /**
     * Los atributos que deben ocultarse para la serialización.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_code',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_platform_admin' => 'boolean',
        'is_active' => 'boolean',
        'two_factor_expires_at' => 'datetime',
    ];

    /**
     * Obtener la empresa mandante a la que pertenece el usuario (si aplica).
     */
    public function mandante(): BelongsTo
    {
        return $this->belongsTo(Mandante::class);
    }

    /**
     * Obtener la empresa contratista a la que pertenece el usuario (si aplica).
     */
    public function contratista(): BelongsTo
    {
        return $this->belongsTo(Contratista::class);
    }

    /**
     * Obtener el usuario que creó a este usuario.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // Métodos auxiliares para verificar el tipo de usuario de forma más legible
    public function isAsem(): bool
    {
        return $this->user_type === 'asem';
    }

    public function isMandante(): bool
    {
        return $this->user_type === 'mandante';
    }

    public function isContratista(): bool
    {
        return $this->user_type === 'contratista';
    }

    /**
     * Get the trusted devices for the user.
     */
    public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDevice::class);
    }

    /**
     * Vinculaciones asignadas a este usuario (solo para Contratista_User).
     * Permite restringir qué entidades puede gestionar.
     */
    public function vinculacionesAsignadas(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\ContratistaUnidadOrganizacional::class,
            'user_vinculaciones',
            'user_id',
            'contratista_unidad_organizacional_id'
        )->withTimestamps();
    }

    /**
     * Generate a new two-factor authentication code for the user.
     */
    public function generateTwoFactorCode(): void
    {
        $this->timestamps = false; // No queremos actualizar 'updated_at'
        $this->two_factor_code = random_int(10000000, 99999999);
        $this->two_factor_expires_at = now()->addMinutes(15);
        $this->save();
    }

    /**
     * Reset the two-factor authentication code for the user.
     */
    public function resetTwoFactorCode(): void
    {
        $this->timestamps = false;
        $this->two_factor_code = null;
        $this->two_factor_expires_at = null;
        $this->save();
    }
}