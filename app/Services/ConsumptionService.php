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

    public function getConsumptionUsage($room_id, $consumption_id)
    {
        Log::info("in getConsumptionUsage for room: {$room_id}, consumption: {$consumption_id}");
        
        // Find room and consumption with validation
        $room = Room::find($room_id);
        $consumption = Consumption::find($consumption_id);
        
        if (!$room) {
            Log::error("Room not found: {$room_id}");
            return response()->json(['error' => 'Room not found'], 404);
        }
        
        if (!$consumption) {
            Log::error("Consumption not found: {$consumption_id}");
            return response()->json(['error' => 'Consumption not found'], 404);
        }
        
        // Check if consumption belongs to the room
        if ($consumption->room_id != $room_id) {
            Log::warning("Consumption {$consumption_id} does not belong to room {$room_id}");
            return response()->json(['error' => 'Consumption does not belong to this room'], 400);
        }
        
        // Get previous consumption of the same type
        $previous_consumption = $room->consumptions()
            ->where('type', $consumption->type)
            ->where('created_at', '<', $consumption->created_at)
            ->orderBy('created_at', 'desc')
            ->first();
        
        if ($previous_consumption) {
            Log::info("Previous consumption found: ID {$previous_consumption->id}, end_reading: {$previous_consumption->end_reading}");
            
            // Check if both readings are valid numbers
            $current_reading = $consumption->end_reading ?? 0;
            $previous_reading = $previous_consumption->end_reading ?? 0;
            
            // Calculate usage (ensure positive value)
            $usage = max(0, $current_reading - $previous_reading);
            
            return response()->json([
                "previous_consumption_id" => $previous_consumption->id,
                "previous_reading" => $previous_reading,
                "current_consumption_id" => $consumption->id,
                "current_reading" => $current_reading,
                "usage" => $usage
            ]);
        } else {
            Log::info("No previous consumption found for type: {$consumption->type}");
            
            // If no previous consumption, check if there's a start_reading to use
            $start_reading = $consumption->start_reading ?? 0;
            $current_reading = $consumption->end_reading ?? 0;
            $usage = max(0, $current_reading - $start_reading);
            
            return response()->json([
                "message" => "No previous consumption record found",
                "start_reading" => $start_reading,
                "current_reading" => $current_reading,
                "usage" => $usage
            ]);
        }
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