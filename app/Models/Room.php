<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use KHQR\Models\Timestamp;

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
        return $this->hasOne(RoomType::class,'room_type_id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class,'room_id');
    }

    public function consumptions()
    {
        return $this->hasMany(Consumption::class,'consumpution');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class,'room_id');
    }

    public function currentContract()
    {
        return $this->hasOne(Contract::class)->where('status', 'active')->latest();
    }

    public function services()
    {
        return $this->belongsToMany(Service::class)->using(RoomService::class)->withPivot(['room_id']);
    }
}