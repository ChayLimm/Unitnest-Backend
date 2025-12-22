<?php

namespace App\Models;

use App\Enums\NotificationType;
use App\Enums\NotificationStatus;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use CrudTrait;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_id',
        'notification_type',
        'email',
        'read',
        'payload',
        'status',
        'landlord_id',
        'chat_id',
        'archived'
    ];

    protected $casts = [
        'read' => 'boolean',
        'archived' => 'boolean',
        'notification_type' => NotificationType::class,
        'status' => NotificationStatus::class,
        'payload' => 'array',
    ];
    protected $appends = ['room_id', 'room_number'];

    // Relationships
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'chat_id', 'telegram_id');
    }

    // Room ID accessor
    public function getRoomIdAttribute()
    {
        return optional(optional(optional($this->tenant)->contract)->room)->id;
    }

    // Room number accessor  
    public function getRoomNumberAttribute()
    {
        return optional(optional(optional($this->tenant)->contract)->room)->room_number;
    }

    public function getBuildingIdAttribute()
    {
        return optional(optional(optional($this->tenant)->contract)->room)->building_id;
    }
}