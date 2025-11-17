<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $rooms = Room::with(['building', 'roomType', 'contracts', 'currentContract.tenant'])
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $rooms->items(),
            'pagination' => [
                'current_page' => $rooms->currentPage(),
                'per_page' => $rooms->perPage(),
                'total' => $rooms->total(),
                'last_page' => $rooms->lastPage(),
                'from' => $rooms->firstItem(),
                'to' => $rooms->lastItem(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'room_type_id' => 'required|exists:room_types,id',
            'price' => 'nullable|numeric|min:0',
            'barcode' => 'nullable|string|max:255',
            'room_number' => 'required|string|max:50',
            'floor' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
        ]);

        $room = Room::create($validated);

        return response()->json($room, 201);
    }

    public function show(Room $room)
    {
        $room->load([
            'building.landlord', 
            'roomType', 
            'contracts.tenant', 
            'consumptions.service',
            'payments'
        ]);
        return response()->json($room);
    }

    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'building_id' => 'sometimes|exists:buildings,id',
            'room_type_id' => 'sometimes|exists:room_types,id',
            'price' => 'nullable|numeric|min:0',
            'barcode' => 'nullable|string|max:255',
            'room_number' => 'sometimes|string|max:50',
            'floor' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
        ]);

        $room->update($validated);

        return response()->json($room);
    }

    public function destroy(Room $room)
    {
        $room->delete();
        return response()->json(null, 204);
    }

    public function getByBuilding(Request $request, $buildingId)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $rooms = Room::where('building_id', $buildingId)
            ->with(['roomType', 'currentContract.tenant'])
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $rooms->items(),
            'pagination' => [
                'current_page' => $rooms->currentPage(),
                'per_page' => $rooms->perPage(),
                'total' => $rooms->total(),
                'last_page' => $rooms->lastPage(),
                'from' => $rooms->firstItem(),
                'to' => $rooms->lastItem(),
            ]
        ]);
    }

    public function updateStatus(Request $request, Room $room)
    {
        $validated = $request->validate([
            'status' => 'required|string|max:50',
        ]);

        $room->update($validated);

        return response()->json($room);
    }
}