<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use CrudTrait;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'unit_price',
        'description',
    ];

    // Relationships
    public function consumptions()
    {
        return $this->hasMany(Consumption::class);
    }

    public function paymentItems()
    {
        return $this->hasMany(PaymentItem::class);
    }
}