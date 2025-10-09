<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use CrudTrait;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'building_id',
        'room_type_id',
        'price',
        'barcode',
        'room_number',
        'floor',
        'status',
    ];

    // Relationships
    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function consumptions()
    {
        return $this->hasMany(Consumption::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function currentContract()
    {
        return $this->hasOne(Contract::class)->where('status', 'active')->latest();
    }
}