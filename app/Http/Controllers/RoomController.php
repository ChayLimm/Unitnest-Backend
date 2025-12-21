<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Room;
use App\Models\Service;
use App\Models\RoomService;
use App\Models\Consumption;
use App\Services\ConsumptionService;
use App\Services\StorageService;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function __construct(private StorageService $storageService) {
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $rooms = Room::paginate($perPage, ['*'], 'page', $page);

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
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $room = Room::create(collect($validated)->except('image')->toArray());

        $consumption = Consumption::create([
            'room_id' => $room->id,
            'consumption' => 0,
            'photo_url' => "Hi",
            'end_reading' => 0,
            'type' => 'electricity',
        ]);

        $consumption = Consumption::create([
            'room_id' => $room->id,
            'consumption' => 0,
            'photo_url' => "Hi",
            'end_reading' => 0,
            'type' => 'water',
        ]);

        if ($request->hasFile('image')) {
            $result = $this->storageService->upload($request->file('image'));
            $room->update([
                'image_url' => $result['url'],
            ]);
        }

        return response()->json($room, 201);
    }

    public function show(Room $room)
    {
        $room->load([
            'building.landlord', 
            'roomType', 
            'contracts.tenant', 
            'consumptions',
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
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $room->update(collect($validated)->except('image')->toArray());

        if ($request->hasFile('image')) {
            $result = $this->storageService->upload($request->file('image'));
            $room->update([
                'image_url' => $result['url'],
            ]);
        }

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

        // Get services with pivot data
        $services = $room->services()->get();
        
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
        if (RoomService::where('room_id', $roomId)
            ->where('service_id', $validated['service_id'])
            ->exists()) {
            return response()->json([
                'message' => 'Service already attached to this room'
            ], 400);
        }

        // Attach service to room
        $room->services()->attach($validated['service_id']);

        // Get the attached service
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
        $roomService = RoomService::where('room_id', $roomId)
            ->where('service_id', $serviceId)
            ->first();

        if (!$roomService) {
            return response()->json([
                'message' => 'Service not attached to this room'
            ], 404);
        }

        // Detach service from room
        $roomService->delete();

        return response()->json([
            'message' => 'Service detached successfully'
        ], 200);
    }

    public function getServiceDetails(Request $request, $roomId, $serviceId)
    {
        $roomService = RoomService::with(['room', 'service'])
            ->where('room_id', $roomId)
            ->where('service_id', $serviceId)
            ->first();

        if (!$roomService) {
            return response()->json([
                'message' => 'Service not found for this room'
            ], 404);
        }

        return response()->json([
            'data' => $roomService
        ]);
    }

    public function getActiveContract($roomId)
    {
        if (!$roomId) {
            return response()->json([
                "message" => "room id is required"
            ], 200);
        }
    
        $contract = Contract::with('tenant')
            ->where('room_id', $roomId)
            ->whereNull('end_date')
            ->where('status', 'active')
            ->first(); // Use first() instead of get() if you expect only one active contract
    
        if (!$contract) {
            return response()->json([
                "message" => "No active contract found for this room"
            ], 200);
        }
    
        return response()->json([
            "contract" => $contract,
            "tenant" => $contract->tenant // Assuming you have a relationship defined
        ]);
    }
    public function latestConsumption($roomId)
    {
        $consumptionService = new ConsumptionService();
        $latest = $consumptionService->getLatestConsumptions($roomId);
        
        return response()->json([
            'water' => $latest['water'],
            'electricity' => $latest['electricity']
        ]);
    }

}