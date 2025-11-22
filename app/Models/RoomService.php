<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RoomService extends Pivot
{
    //
    public function room(){
        return $this->belongsTo(Room::class,'room_id');
    }
}
