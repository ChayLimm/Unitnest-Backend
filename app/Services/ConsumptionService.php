<?php

namespace App\Services;
use App\Models\Consumption;
use App\Models\Room;
use Carbon\Carbon;
use KHQR\Models\Timestamp;
use Illuminate\Support\Facades\Log;
class ConsumptionService{
    

    public function __construct(){
    }

    public function getConsumptionUsage($room_id, $consumption_id){
        Log::info("in getConsumptionUsage");
        $room = Room::find($room_id);
        $consumption = Consumption::find($consumption_id);

        $previous_consumption = $room->consumptions()
            ->where('type', "=",$consumption->type) // Default is '='
            ->where('created_at', '<', $consumption->created_at)
            ->orderBy('created_at', 'desc')
            ->first();
            
        if($previous_consumption){
            Log::info("previous consumtion {$previous_consumption->end_reading}");
        }else{
            Log::info("previous_consumption not found");
        }

        if (!$previous_consumption) {
            return null; 
        }
    
        return $consumption->end_reading - $previous_consumption->end_reading;
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
    
    public function getLatestConsumptions($roomId)
    {
        $latestWater = Consumption::where('room_id', $roomId)
            ->where('type', 'water')
            ->latest('created_at')
            ->first();

        $latestElectricity = Consumption::where('room_id', $roomId)
            ->where('type', 'electricity')
            ->latest('created_at')
            ->first();

        return [
            'water' => $latestWater,
            'electricity' => $latestElectricity
        ];
    }
}