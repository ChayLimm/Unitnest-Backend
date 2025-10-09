<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function buildings()
    {
        return $this->hasMany(Building::class, 'landlord_id');
    }

    public function bakongAccounts()
    {
        return $this->hasMany(BakongAccount::class, 'landlord_id');
    }

    public function settings()
    {
        return $this->hasOne(Setting::class);
    }

    public function tenantContracts()
    {
        return $this->hasMany(Contract::class, 'tenant_id');
    }

    public function tenantPayments()
    {
        return $this->hasMany(Payment::class, 'tenant_id');
    }

    public function landlordPayments()
    {
        return $this->hasMany(Payment::class, 'landlord_id');
    }
}