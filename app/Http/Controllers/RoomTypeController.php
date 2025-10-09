<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    public function index()
    {
        $roomTypes = RoomType::with('rooms')->get();
        return response()->json($roomTypes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_type_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $roomType = RoomType::create($validated);

        return response()->json($roomType, 201);
    }

    public function show(RoomType $roomType)
    {
        $roomType->load('rooms.building');
        return response()->json($roomType);
    }

    public function update(Request $request, RoomType $roomType)
    {
        $validated = $request->validate([
            'room_type_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $roomType->update($validated);

        return response()->json($roomType);
    }

    public function destroy(RoomType $roomType)
    {
        $roomType->delete();
        return response()->json(null, 204);
    }
}