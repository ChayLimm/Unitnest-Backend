<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReceiptService;

class ReceiptController extends Controller
{
    public function testReceipt(Request $request)
    {
        $receiptData = [
            'id' => $request->input('id', 12345),
            'tenant_name' => $request->input('tenant_name', 'John Doe'),
            'building_name' => $request->input('building_name', 'Sky Tower'),
            'room_name' => $request->input('room_name', 'A101'),

            'name' => $request->input('landlord_name', 'Lomnov Real Estate'),
            'address' => $request->input('landlord_address', 'Phnom Penh, Cambodia'),
            'phone' => $request->input('landlord_phone'),
            'custom_fields' => $request->input('landlord_custom_fields', []),

            // QR config
            'generate_qr' => $request->input('generate_qr', true),

            // Bakong
            'merchant_account' => $request->input('merchant_account', 'chaylim_cheng@aclb'),
            'merchant_name' => $request->input('merchant_name', 'CHAY LIM Cheng'),
            'merchant_city' => $request->input('merchant_city', 'PHNOM PENH'),
            'currency' => $request->input('currency', 'USD'),
            'store_label' => $request->input('store_label', 'Lomnov Store'),
            'phone_number' => $request->input('phone_number', '85585382962'),
            'terminal_label' => $request->input('terminal_label', 'Rental-01'),
            'static' => $request->input('static', true),
            'reference' => $request->input('reference', 'RENT-' . uniqid()),

            // Utility readings
            'readings' => $request->input('readings', [
                [
                    'item' => 'Electricity',
                    'new' => 1250,
                    'old' => 1100,
                    'total' => 150,
                    'unit' => 'kWh'
                ],
                [
                    'item' => 'Water',
                    'new' => 350,
                    'old' => 300,
                    'total' => 50,
                    'unit' => 'm³'
                ]
            ]),

            // Invoice items
            'items' => $request->input('items', [
                ['name' => 'Monthly Rent', 'price' => 450.00, 'quantity' => 1],
                ['name' => 'Utilities', 'price' => 50.00, 'quantity' => 1],
                ['name' => 'Security Deposit', 'price' => 50.00, 'quantity' => 1],
            ]),
        ];

        try {
            $invoice = ReceiptService::generate($receiptData);
            return $invoice->stream();
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate receipt',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Generate receipt for a specific rental payment
     */
    public function generateRentalReceipt(Request $request)
    {
        $validated = $request->validate([
            'landlord_name' => 'required|string|max:255',          // landlord name
            'landlord_address' => 'required|string|max:255',       // landlord address
            'landlord_phone' => 'nullable|string|max:255',
            'landlord_custom_fields' => 'nullable|array',

            'tenant_name' => 'required|string',
            'building_name' => 'required|string',
            'room_name' => 'required|string',

            'merchant_account' => 'required|string',
            'phone_number' => 'required|string',

            'items' => 'required|array',
            'items.*.name' => 'required|string',
            'items.*.price' => 'required|numeric',
            'items.*.quantity' => 'required|integer',

            'readings' => 'sometimes|array',
        ]);

        $receiptData = array_merge($validated, [
            'generate_qr' => true,

            // Official merchant profile
            'merchant_name' => 'Lomnov Real Estate',
            'merchant_city' => 'Phnom Penh',
            'currency' => 'USD',
            'store_label' => 'Lomnov Store',
            'terminal_label' => 'Rental-01',

            // Dynamic reference
            'reference' => 'RENT-' . time(),
            'static' => false,
        ]);

        $invoice = ReceiptService::generate($receiptData);

        return $invoice->stream();
    }
}