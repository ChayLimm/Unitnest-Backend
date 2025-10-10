<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use CrudTrait;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'landlord_id',
        'transaction_id',
        'room_id',
        'status',
        'qr_code',
        'md5',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function paymentItems()
    {
        return $this->hasMany(PaymentItem::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}