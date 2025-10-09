<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentItem extends Model
{
    use CrudTrait;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_id',
        'service_id',
        'unit_price',
        'quantity',
        'subtotal',
    ];

    // Relationships
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}