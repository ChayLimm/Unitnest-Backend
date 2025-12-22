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
    ];

    protected $casts = [
        'read' => 'boolean',
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
     public function getNotificationTypeAttribute($value)
{
    $enum = $this->castAttribute('notification_type', $value);
    return $enum instanceof NotificationType ? $enum->value : $value;
}

public function getStatusAttribute($value)
{
    $enum = $this->castAttribute('status', $value);
    return $enum instanceof NotificationStatus ? $enum->value : $value;
}

// OR simpler accessor that always returns string
public function getNotificationTypeDisplayAttribute()
{
    return $this->getRawOriginal('notification_type');
}

public function getStatusDisplayAttribute()
{
    return $this->getRawOriginal('status');
}
}