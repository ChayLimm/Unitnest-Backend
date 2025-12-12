<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'landlord_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'telegram_id',
        'identify_id',
        'profile_image_url',
        'identify_image_url',
        'emergency_contact'
    ];

    protected $casts = [
        'emergency_contact' => 'array',
    ];

    /**
     * Get the landlord (user) that owns the tenant.
     */
    public function landlord(): BelongsTo
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    /**
     * Get the full name of the tenant.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}