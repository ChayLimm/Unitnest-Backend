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

    // Relationships
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }
}