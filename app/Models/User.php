<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, CrudTrait;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'remember_token',
        'role_id',
        'telegram_id',
        'username',
        'phonenumber',
        'identify_id',
        'profile_image_url',
        'identify_image_url',
        'deleted_at',
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
       
    public function scopeByTelegramId($query, $telegramId)
    {
        return $query->where('telegram_id', $telegramId);
    }

    public function scopeByIdentifyId($query, $identifyId)
    {
        return $query->where('identify_id', $identifyId);
    }

    public function scopeByPhone($query, $phonenumber)
    {
        return $query->where('phonenumber', $phonenumber);
    }
}


