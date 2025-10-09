<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'read' => 'boolean',
    ];

    // Relationships
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}