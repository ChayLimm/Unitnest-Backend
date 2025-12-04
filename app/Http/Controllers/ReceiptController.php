<?php

namespace App\Http\Controllers;

use App\Models\Receipt;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Services\ReceiptService;

class ReceiptController extends Controller
{
    public function index()
    {
        $receipts = Receipt::paginate(15);
        return response()->json($receipts);
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

    public function getAllReceiptsFromDisk(){
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

    public function downloadReceipt($filename){
        if (!Storage::disk('invoices')->exists($filename)) {
            return response()->json(['error' => 'Receipt not found'], 404);
        }

        return Storage::disk('invoices')->download($filename);
    }

    public function destroyReceipt($filename){
        $receipt = Receipt::where('receipt_name', $filename)->first();
        
        if (!$receipt) {
            return response()->json(['error' => 'Receipt not found'], 404);
        }

        if (Storage::disk('invoices')->exists($filename)) {
            Storage::disk('invoices')->move($filename, 'trash/'.$filename);
        }else{
            return response()->json(['error' => 'Receipt not found'], 404);
        }

        // Soft delete the DB record
        $receipt->delete();

        return response()->json(['message' => 'Receipt deleted successfully'], 200);
    }

    public function restoreReceipt($filename)
    {
        $receipt = Receipt::withTrashed()->where('receipt_name', $filename)->first();

        if (!$receipt) {
            return response()->json(['error' => 'Receipt not found in trash'], 404);
        }

        // Restore file from trash if exists.
        if (Storage::disk('invoices')->exists("trash/$filename")) {
            Storage::disk('invoices')->move("trash/$filename", $filename);
        } else {
            Log::warning("Receipt file $filename not found in trash during restoration.");
        }

        // Restore DB record
        $receipt->restore();

        return response()->json(['message' => 'Receipt restored successfully'], 200);
    }

}