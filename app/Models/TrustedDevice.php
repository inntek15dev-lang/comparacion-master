<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrustedDevice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'remember_token',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the user that owns the trusted device.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}