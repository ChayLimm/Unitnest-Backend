<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consumption extends Model
{
    use CrudTrait;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'room_id',
        'end_reading',
        'photo_url',
        'consumption',
        'type'
    ];

    // Relationships
    public function room()
    {
        return $this->belongsTo(Room::class,'room_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class,'service_id');
    }
}