<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Http\Request;
use App\Services\StorageService;

class BuildingController extends Controller
{
    public function __construct(
        private StorageService $storageService
    ) {}

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $buildings = Building::with(['landlord', 'rooms'])
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $buildings->items(),
            'pagination' => [
                'current_page' => $buildings->currentPage(),
                'per_page' => $buildings->perPage(),
                'total' => $buildings->total(),
                'last_page' => $buildings->lastPage(),
                'from' => $buildings->firstItem(),
                'to' => $buildings->lastItem(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'landlord_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'floor' => 'nullable|integer',
            'unit' => 'nullable|integer',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $building = Building::create(collect($validated)->except('image')->toArray());

        if ($request->hasFile('image')) {
            $result = $this->storageService->upload($request->file('image'));
            $building->update([
                'image_url' => $result['url'],
            ]);
        }

        return response()->json($building, 201);
    }

    public function show(Building $building)
    {
        $building->load(['landlord', 'rooms.roomType', 'rooms.contracts.tenant']);
        return response()->json($building);
    }

    public function update(Request $request, Building $building)
    {
        $validated = $request->validate([
            'landlord_id' => 'sometimes|exists:users,id',
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string',
            'floor' => 'nullable|integer',
            'unit' => 'nullable|integer',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $building->update(collect($validated)->except('image')->toArray());

        if ($request->hasFile('image')) {
            $result = $this->storageService->upload($request->file('image'));
            $building->update([
                'image_url' => $result['url'],
            ]);
        }

        return response()->json($building);
    }

    public function destroy(Building $building)
    {
        $building->delete();
        return response()->json(null, 204);
    }

    public function getByLandlord(Request $request, $landlordId)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $buildings = Building::where('landlord_id', $landlordId)
            ->with(['rooms', 'rooms.contracts'])
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $buildings->items(),
            'pagination' => [
                'current_page' => $buildings->currentPage(),
                'per_page' => $buildings->perPage(),
                'total' => $buildings->total(),
                'last_page' => $buildings->lastPage(),
                'from' => $buildings->firstItem(),
                'to' => $buildings->lastItem(),
            ]
        ]);
    }
}