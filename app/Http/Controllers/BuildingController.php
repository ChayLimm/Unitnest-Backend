<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    public function index()
    {
        $buildings = Building::with(['landlord', 'rooms'])->get();
        return response()->json($buildings);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'landlord_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'image_url' => 'nullable|url',
            'floor' => 'nullable|integer',
            'unit' => 'nullable|integer',
        ]);

        $building = Building::create($validated);

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
            'image_url' => 'nullable|url',
            'floor' => 'nullable|integer',
            'unit' => 'nullable|integer',
        ]);

        $building->update($validated);

        return response()->json($building);
    }

    public function destroy(Building $building)
    {
        $building->delete();
        return response()->json(null, 204);
    }

    public function getByLandlord($landlordId)
    {
        $buildings = Building::where('landlord_id', $landlordId)
            ->with(['rooms', 'rooms.contracts'])
            ->get();
        
        return response()->json($buildings);
    }
}