<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function index()
    {
        $contracts = Contract::with(['room.building', 'tenant'])->get();
        return response()->json($contracts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'tenant_id' => 'required|exists:tenants,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'deposit_amount' => 'nullable|numeric|min:0',
            'status' => 'required|string|max:50',
        ]);

        $contract = Contract::create($validated);

        return response()->json($contract, 201);
    }

    public function show(Contract $contract)
    {
        $contract->load(['room.building.landlord', 'tenant', 'room.consumptions']);
        return response()->json($contract);
    }

    public function update(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'room_id' => 'sometimes|exists:rooms,id',
            'tenant_id' => 'sometimes|exists:users,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'deposit_amount' => 'nullable|numeric|min:0',
            'status' => 'sometimes|string|max:50',
        ]);

        $contract->update($validated);

        return response()->json($contract);
    }

    public function destroy(Contract $contract)
    {
        $contract->delete();
        return response()->json(null, 204);
    }

    public function getActiveContracts()
    {
        $contracts = Contract::where('status', 'active')
            ->with(['room.building', 'tenant'])
            ->get();
        
        return response()->json($contracts);
    }

    public function getTenantContracts($tenantId)
    {
        $contracts = Contract::where('tenant_id', $tenantId)
            ->with(['room.building', 'room.roomType'])
            ->get();
        
        return response()->json($contracts);
    }
}