<?php

namespace App\Http\Controllers;

use App\Models\BakongAccount;
use Illuminate\Http\Request;

class BakongAccountController extends Controller
{
    public function index()
    {
        $accounts = BakongAccount::with('landlord')->get();
        return response()->json($accounts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'landlord_id' => 'required|exists:users,id',
            'bakong_id' => 'required|string|max:255',
            'bakong_name' => 'required|string|max:255',
            'bakong_location' => 'required|string|max:255',
        ]);

        $account = BakongAccount::create($validated);

        return response()->json($account, 201);
    }

    public function show(BakongAccount $bakongAccount)
    {
        $bakongAccount->load('landlord');
        return response()->json($bakongAccount);
    }

    public function update(Request $request, BakongAccount $bakongAccount)
    {
        $validated = $request->validate([
            'landlord_id' => 'sometimes|exists:users,id',
            'bakong_id' => 'sometimes|string|max:255',
            'bakong_name' => 'sometimes|string|max:255',
            'bakong_location' => 'sometimes|string|max:255',
        ]);

        $bakongAccount->update($validated);

        return response()->json($bakongAccount);
    }

    public function destroy(BakongAccount $bakongAccount)
    {
        $bakongAccount->delete();
        return response()->json(null, 204);
    }
}