<?php

namespace App\Http\Controllers;

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
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $amount = $request->input('amount');

        $bakongService = new BakongService();
        $khqrData = $bakongService->generateKHQR($amount);

        return response()->json($khqrData);
    } 

    public function checkTransactionStatus(Request $request)
    {
        $request->validate([
            'md5' => 'required|string',
            'timeout' => 'sometimes|integer|min:1|max:60'
        ]);

        $md5 = $request->input('md5');
        $timeout = $request->input('timeout', 30); // Default to 30 seconds if not provided

        $bakongService = new BakongService();
        $status = $bakongService->checkTransactionStatus($md5, $timeout);

        return response()->json($status);
    }
}
