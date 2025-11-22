<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReceiptService;

class ReceiptController extends Controller
{
    public function testReceipt(Request $request)
    {
        try{
            $validated = $request->validate([
                'payment_id' => 'required|exists:payments,id',
            ]);

            $receipt_service = new ReceiptService();

            $invoice_info = $receipt_service->e_receipt_format_test($validated['payment_id']);

            return response()->json($invoice_info);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
}



    /**
     * Generate receipt for a specific rental payment
     */
    public function generateRentalReceipt(Request $request)
    {
        $validated = $request->validate([
            'payment_id' => 'required|exists:payments,id',
        ]);

        $invoice = ReceiptService::generate($validated['payment_id']);

        return $invoice->stream();
    }
}