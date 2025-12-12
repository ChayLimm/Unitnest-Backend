<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomType extends Model
{
    use CrudTrait;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'landlord_id',
        'room_type_name',
        'description',
    ];

    // Relationships
    public function rooms()
    {
        return $this->hasMany(Room::class, 'room_type_id');
    }
    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }
}