<?php

namespace App\Http\Controllers;

use App\Models\Consumption;
use Illuminate\Http\Request;

class ConsumptionController extends Controller
{
    public function index()
    {
        $consumptions = Consumption::with(['room.building', 'service'])->get();
        return response()->json($consumptions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'service_id' => 'required|exists:services,id',
            'end_reading' => 'nullable|numeric|min:0',
            'photo_url' => 'nullable|url',
            'consumption' => 'nullable|numeric|min:0',
        ]);

        $consumption = Consumption::create($validated);

        return response()->json($consumption, 201);
    }

    public function show(Consumption $consumption)
    {
        $consumption->load(['room.building.landlord', 'service']);
        return response()->json($consumption);
    }

    public function update(Request $request, Consumption $consumption)
    {
        $validated = $request->validate([
            'room_id' => 'sometimes|exists:rooms,id',
            'service_id' => 'sometimes|exists:services,id',
            'end_reading' => 'nullable|numeric|min:0',
            'photo_url' => 'nullable|url',
            'consumption' => 'nullable|numeric|min:0',
        ]);

        $consumption->update($validated);

        return response()->json($consumption);
    }

    public function destroy(Consumption $consumption)
    {
        $consumption->delete();
        return response()->json(null, 204);
    }

    public function getRoomConsumptions($roomId)
    {
        $consumptions = Consumption::where('room_id', $roomId)
            ->with(['service'])
            ->get();
        
        return response()->json($consumptions);
    }
}