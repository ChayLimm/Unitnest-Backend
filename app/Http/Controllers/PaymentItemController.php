<?php

namespace App\Http\Controllers;

use App\Models\PaymentItem;
use Illuminate\Http\Request;

class PaymentItemController extends Controller
{
    public function index()
    {
        $paymentItems = PaymentItem::with(['payment', 'service'])->get();
        return response()->json($paymentItems);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'service_id' => 'required|exists:services,id',
            'unit_price' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'subtotal' => 'nullable|numeric|min:0',
        ]);

        $paymentItem = PaymentItem::create($validated);

        return response()->json($paymentItem, 201);
    }

    public function show(PaymentItem $paymentItem)
    {
        $paymentItem->load(['payment.tenant', 'service']);
        return response()->json($paymentItem);
    }

    public function update(Request $request, PaymentItem $paymentItem)
    {
        $validated = $request->validate([
            'payment_id' => 'sometimes|exists:payments,id',
            'service_id' => 'sometimes|exists:services,id',
            'unit_price' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'subtotal' => 'nullable|numeric|min:0',
        ]);

        $paymentItem->update($validated);

        return response()->json($paymentItem);
    }

    public function destroy(PaymentItem $paymentItem)
    {
        $paymentItem->delete();
        return response()->json(null, 204);
    }

    public function getPaymentItems($paymentId)
    {
        $paymentItems = PaymentItem::where('payment_id', $paymentId)
            ->with(['service'])
            ->get();
        
        return response()->json($paymentItems);
    }
}