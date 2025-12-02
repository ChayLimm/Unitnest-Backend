<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Receipt;
use Illuminate\Support\Facades\Storage;

use App\Services\ReceiptService;

class ReceiptController extends Controller
{
    public function index(){
        return response()->json(['message' => 'Receipt Controller is working!'], 200);
    }

    public function show()
    {
        $receipts = Receipt::with('payment')->get();
        return response()->json($receipts);
    }

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

    public function getAllReceipts(){
        try{
            $files = Storage::disk('invoices')->files();
            return response()->json(['files' => $files], 200);
        }catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function previewReceipt($filename){

        if (!Storage::disk('invoices')->exists($filename)) {
            return response()->json(['error' => 'Receipt not found'], 404);
        }
        
        $file = Storage::disk('invoices')->get($filename);

        return response($file, 200)->header('Content-Type', 'application/pdf');
    }

    public function destroyReceipt($filename){

        if (Storage::disk('invoices')->exists($filename)) {
            Storage::disk('invoices')->delete($filename);
        }else{
            return response()->json(['error' => 'Receipt not found'], 404);
        }

        return response()->json(['message' => 'Receipt deleted successfully'], 200);
    }
}