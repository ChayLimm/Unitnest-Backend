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
   
    public function roomsService(Request $request, $roomId)
    {
        $room = Room::find($roomId);
        
        if (!$room) {
            return response()->json([
                'message' => 'Room not found'
            ], 404);
        }

        $services = $room->services;
        
        return response()->json([
            'data' => $services
        ]);
    }

    public function attachService(Request $request, $roomId)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
        ]);

        $room = Room::find($roomId);
        
        if (!$room) {
            return response()->json([
                'message' => 'Room not found'
            ], 404);
        }

        // Check if service already attached
        if ($room->services()->where('service_id', $validated['service_id'])->exists()) {
            return response()->json([
                'message' => 'Service already attached to this room'
            ], 400);
        }

        // Attach service to room
        $room->services()->attach($validated['service_id']);

        // Load the attached service with details
        $service = Service::find($validated['service_id']);
        
        return response()->json([
            'message' => 'Service attached successfully',
            'data' => $service
        ], 201);
    }

    public function detachService(Request $request, $roomId, $serviceId)
    {
        $room = Room::find($roomId);
        
        if (!$room) {
            return response()->json([
                'message' => 'Room not found'
            ], 404);
        }

        // Check if service is attached
        if (!$room->services()->where('service_id', $serviceId)->exists()) {
            return response()->json([
                'message' => 'Service not attached to this room'
            ], 404);
        }

        // Detach service from room
        $room->services()->detach($serviceId);

        return response()->json([
            'message' => 'Service detached successfully'
        ], 200);
    }

    public function updateService(Request $request, $roomId, $serviceId)
    {
        $validated = $request->validate([
            'price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $room = Room::find($roomId);
        
        if (!$room) {
            return response()->json([
                'message' => 'Room not found'
            ], 404);
        }

        // Check if service is attached
        if (!$room->services()->where('service_id', $serviceId)->exists()) {
            return response()->json([
                'message' => 'Service not attached to this room'
            ], 404);
        }

        // Update pivot table data
        $room->services()->updateExistingPivot($serviceId, $validated);

        // Get updated service with pivot data
        $service = $room->services()->where('service_id', $serviceId)->first();
        
        return response()->json([
            'message' => 'Service updated successfully',
            'data' => $service
        ], 200);
    }
}