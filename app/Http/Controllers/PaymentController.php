<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use App\Models\Consumption;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $payments = Payment::paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'data' => $payments->items(),
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage(),
                'from' => $payments->firstItem(),
                'to' => $payments->lastItem(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:users,id',
            'landlord_id' => 'required|exists:users,id',
            'transaction_id' => 'required|exists:transactions,id',
            'room_id' => 'required|exists:rooms,id',
            'status' => 'nullable|string|max:50',
            'qr_code' => 'nullable|string|max:255',
            'md5' => 'nullable|string|max:255',
            'deep_link' => 'nullable|string|max:255',
            'receipt_url' => 'nullable|string|max:255',
        ]);

        $payment = Payment::create($validated);

        return response()->json($payment, 201);
    }

    public function show(Payment $payment)
    {
        $payment->load([
            'tenant', 
            'landlord', 
            'transaction', 
            'room.building',
            'paymentItems.service',
            'notifications',
            'receipt_url'
        ]);
        
        return response()->json($payment);
    }

    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'tenant_id' => 'sometimes|exists:users,id',
            'landlord_id' => 'sometimes|exists:users,id',
            'transaction_id' => 'sometimes|exists:transactions,id',
            'room_id' => 'sometimes|exists:rooms,id',
            'status' => 'nullable|string|max:50',
            'qr_code' => 'nullable|string|max:255',
            'md5' => 'nullable|string|max:255',
            'deep_link' => 'nullable|string|max:255',
            'receipt_url' => 'nullable|string|max:255',
        ]);

        $payment->update($validated);

        return response()->json($payment);
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();
        return response()->json(null, 204);
    }

    public function getPaymentByLandlord(Request $request, $landlordId)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
    
        // Get paginated payments
        $payments = Payment::where('landlord_id', $landlordId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    
        // Check for pending receipts
        PaymentService::checkPendingReceipts($landlordId);
        
        // Get landlord with buildings and rooms
        $landlord = User::with(['buildings.rooms.contract.payments' => function($query) {
            $query->whereMonth('created_at', now()->month)
                  ->whereYear('created_at', now()->year);
        }])->findOrFail($landlordId);
    
        // Initialize counters
        $unpaid = 0;
        $pending = 0;
        $complete = 0;
    
        // Calculate room statuses
        foreach ($landlord->buildings as $building) {
            foreach ($building->rooms as $room) {
                // Check if room has an active contract
                $activeContract = $room->contracts()
                    ->where('status', 'active')
                    ->where('start_date', '<=', now())
                    ->where(function($query) {
                        $query->where('end_date', '>=', now())
                              ->orWhereNull('end_date');
                    })
                    ->first();
    
                if (!$activeContract) {
                    continue; // Skip available rooms
                }
    
                // Get current month's payment for this contract
                $currentMonthPayment = $room->contract->payments
                    ->where('payment_month', now()->format('Y-m'))
                    ->first();
    
                if (!$currentMonthPayment) {
                    $unpaid++; // No payment record for current month
                } else {
                    switch ($currentMonthPayment->status) {
                        case PaymentStatus::PENDING->value():
                            $pending++;
                            break;
                        case PaymentStatus::COMPLETED->value():
                            $complete++;
                            break;

                        default:
                            $unpaid++;
                    }
                }
            }
        }
    
        return response()->json([
            'data' => $payments->items(),
            'stats' => [
                'unpaid' => $unpaid,
                'pending' => $pending,
                'complete' => $complete,
            ],
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage(),
                'from' => $payments->firstItem(),
                'to' => $payments->lastItem(),
            ]
        ]);
    }
    public function getTenantPayments(Request $request, $tenantId)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $payments = Payment::where('tenant_id', $tenantId)
            ->with(['room.building', 'paymentItems.service'])
            ->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'data' => $payments->items(),
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage(),
                'from' => $payments->firstItem(),
                'to' => $payments->lastItem(),
            ]
        ]);
    }

    public function updateStatus(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'status' => 'required|string|max:50',
        ]);

        $payment->update($validated);

        return response()->json($payment);
    }

    public function processPayment(Request $request)  {

        $room_id = $request->input('room_id');
        $consumptions = $request->input('consumptions');

        $penalty = $request->input('penalty');
        $lastPayment = $request->input('lastPayment');
       
        $consumptionModels = [];
        if(empty($consumptions) || $consumptions == null){
            //do nth
            $consumptions = null;
        }else{
            foreach ($consumptions as $data) {
                $consumptionModels[] = new Consumption($data);
            }
        }

       
        Log::info(("calling payment service"));
        $payment_service = new PaymentService($room_id,);
        // $consumptions = Consumption::all();
        $response = $payment_service->processPayment($lastPayment,$penalty,...$consumptionModels);
        
        return response()->json($response);

    }
}