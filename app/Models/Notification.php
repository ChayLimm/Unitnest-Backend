<?php

namespace App\Models;

use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
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
        'notification_type' => 'string',
        'status' => 'string',
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

    // In app/Models/Notification.php
    public function toArray()
    {
        $array = parent::toArray();

        // Convert enum objects to strings
        if (isset($array['notification_type']) && is_object($array['notification_type'])) {
            $array['notification_type'] = $array['notification_type']->value ?? (string) $array['notification_type'];
        }

        if (isset($array['status']) && is_object($array['status'])) {
            $array['status'] = $array['status']->value ?? (string) $array['status'];
        }

        // Add your appended attributes
        $array['room_id'] = $this->room_id;
        $array['room_number'] = $this->room_number;
        $array['building_id'] = $this->building_id;

        return $array;
    }
}
