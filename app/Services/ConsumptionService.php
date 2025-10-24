<?php

namespace App\Services;
use App\Models\Consumption;
use App\Models\Room;
use Carbon\Carbon;
use KHQR\Models\Timestamp;

class ConsumptionService{
    
    protected Room $room;

    public function __construct(Room $room){
        $this->room = $room;
    }

    public function getConsumptionUsage(Consumption $consumption){
        $previous_consumption = $this->room->consumptions()->where('service_id', '==', $consumption->service_id)
            ->where('created_at', '<', $consumption->created_at)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$previous_consumption) {
            return null; 
        }

        return $consumption->current_reading - $previous_consumption->current_reading;
    }
    // check if the room is paid or not in the given month
    public function getConsumptionThisMonth(?Carbon $timestamp){

        $timestamp = $timestamp ?? Carbon::now();

        $consumptions = $this->room->consumptions()->orderBy('created_at', 'desc');
        foreach($consumptions as $consumption){
            if($consumption->created_at->month == $timestamp->month && $consumption->created_at->year == $timestamp->year){
              return  $consumption;
            }
        }
        return null;
    }
    
    
}