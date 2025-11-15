<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReceiptService;

class ReceiptController extends Controller
{
    public function testReceipt(Request $request)
    {
        $receiptData = [
            'id' => $request->id ?? 12345,
            'tenant_name' => $request->tenant_name ?? 'John Doe',
            'building_name' => $request->building_name ?? 'Sky Tower',
            'room_name' => $request->room_name ?? 'A101',
            
            // QR Code Generation Options
            'generate_qr' => $request->generate_qr ?? true, // Set to true to auto-generate
            
            // Bakong KHQR Fields (matching your microservice)
            'merchant_account' => $request->merchant_account ?? 'lomnov@aba',
            'merchant_name' => $request->merchant_name ?? 'Lomnov Real Estate',
            'merchant_city' => $request->merchant_city ?? 'Phnom Penh',
            'currency' => $request->currency ?? 'USD',
            'store_label' => $request->store_label ?? 'Lomnov Store',
            'phone_number' => $request->phone_number ?? '85512345678',
            'terminal_label' => $request->terminal_label ?? 'Rental-01',
            'static' => $request->static ?? false,
            'reference' => $request->reference ?? ('RENT-' . ($request->id ?? uniqid())),
            
            // Utility Readings
            'readings' => $request->readings ?? [
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
            ],
            
            // Invoice Items
            'items' => $request->items ?? [
                [
                    'name' => 'Monthly Rent',
                    'price' => 450.00,
                    'quantity' => 1
                ],
                [
                    'name' => 'Utilities',
                    'price' => 50.00,
                    'quantity' => 1
                ],
                [
                    'name' => 'Security Deposit',
                    'price' => 50.00,
                    'quantity' => 1
                ]
            ]
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
            'merchant_name' => 'Lomnov Real Estate',
            'merchant_city' => 'Phnom Penh',
            'currency' => 'USD',
            'store_label' => 'Lomnov Store',
            'terminal_label' => 'Rental-01',
            'static' => false,
            'reference' => 'RENT-' . time(),
        ]);

        $invoice = ReceiptService::generate($receiptData);
        
        return $invoice->stream();
    }
}