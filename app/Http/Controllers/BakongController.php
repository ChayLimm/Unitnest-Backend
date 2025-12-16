<?php

namespace App\Http\Controllers;

use App\Jobs\CheckTransactionStatusJob;
use Illuminate\Http\Request;
use App\Services\BakongService;

class BakongController extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'Bakong Controller is working!'], 200);
    }

    public function generateKHQR(Request $request)
    {
        // Production the constraint need to be required
        $validated = $request->validate([
            'tenant_id' => 'nullable|integer',
            'amount' => 'required|numeric|min:0.01',
            'landlord_id' => 'nullable|integer',
            'room_id' => 'nullable|integer',
        ]);

        $bakongService = new BakongService();
        
        if (!empty($validated['landlord_id'])) {
             $bakongService->setBakongAccountFromLandlord($validated['landlord_id']);
        }

        $khqrData = $bakongService->generateKHQR($validated['amount']);

        return response()->json($khqrData);
    }

    // public function checkTransactionStatus(Request $request)
    // {
    //     $validated = $request->validate([
    //         'tenant_id' => 'nullable|integer',
    //         'amount' => 'required|numeric|min:0.01',
    //         'landlord_id' => 'nullable|integer',
    //         'room_id' => 'nullable|integer',
    //     ]);

    //     $bakongService = new BakongService();
    //     $khqrData = $bakongService->generateKHQR($validated['amount'], $validated);

    //     return response()->json($khqrData);
    // } 

    public function checkTransactionStatus(Request $request)
    {
        $request->validate([
            'md5' => 'required|string',
            'timeout' => 'sometimes|integer|min:1|max:60'
        ]);

        $md5 = $request->input('md5');
        $timeout = $request->input('timeout', 30); // Default to 30 seconds if not provided

        $transaction = new CheckTransactionStatusJob($md5, $timeout);
        $status = $transaction->handle();

        return response()->json($status);
    }
}
