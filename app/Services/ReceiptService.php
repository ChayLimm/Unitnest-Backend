<?php

namespace App\Services;

use LaravelDaily\Invoices\Invoice;
use LaravelDaily\Invoices\Classes\Buyer;
use LaravelDaily\Invoices\Classes\Seller;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ReceiptService
{
    /**
     * Generate a receipt PDF with optional Bakong KHQR QR code.
     *
     * @param array $data Receipt data including 'qr_base64' from Python microservice
     * @return \LaravelDaily\Invoices\Invoice
     */
    public static function generate(array $data)
    {
        try {
            // Seller / Landlord
            $landlord = new Seller([
                'name' => 'Lomnov Real Estate',
                'address' => '456 Property Ave, Metropolis',
                'phone' => '023-456-7890',
                'custom_fields' => [
                    'Email' => 'info@lomnov.com',
                    'National ID' => '123-45-6789',
                ],
            ]);

            // Buyer / Tenant
            $tenant = new Buyer([
                'name' => $data['tenant_name'],
                'address' => $data['building_name'] . ', Room ' . $data['room_name'],
                'custom_fields' => [
                    'Building' => $data['building_name'] ?? '',
                    'Room' => $data['room_name'] ?? '',
                ],
            ]);

            // Readings
            $readingsInfo = collect($data['readings'] ?? [])->map(function ($r) {
                return [
                    'item' => $r['item'],
                    'new' => $r['new'],
                    'old' => $r['old'],
                    'total' => $r['total'],
                    'unit' => $r['unit'] ?? ''
                ];
            });

            // Items
            $items = collect($data['items'] ?? [])->map(function ($item) {
                return (new InvoiceItem())
                    ->title($item['name'])
                    ->pricePerUnit($item['price'])
                    ->quantity($item['quantity']);
            });

            // Create invoice first to get total_amount
            $invoice = Invoice::make()
                ->name('RENT RECEIPT')
                ->buyer($tenant)
                ->seller($landlord)
                ->template('lomnov_receipt')
                ->logo(public_path('vendor/invoices/lomnov_logo.png'))
                ->currencySymbol('$')
                ->currencyCode('USD')
                ->date(now())
                ->addItems($items->toArray())
                ->notes('Thank you for your rent payment. Please pay by the due date.');

            $totalAmount = $invoice->total_amount;
            
            Log::info('Invoice total calculated', [
                'total_amount' => $totalAmount,
                'has_qr' => isset($data['qr_base64'])
            ]);

            // Get QR code from microservice if not provided
            $qrBase64 = $data['qr_base64'] ?? null;

            if (!$qrBase64 || !self::isValidBase64Image($qrBase64)) {
                $qrBase64 = self::fetchQRFromMicroservice([
                    'merchant_account' => $data['merchant_account'] ?? 'lomnov@aba',
                    'merchant_name' => $data['merchant_name'] ?? 'Lomnov Real Estate',
                    'merchant_city' => $data['merchant_city'] ?? 'Phnom Penh',
                    'amount' => $totalAmount ?? 0,
                    'reference' => $data['reference'] ?? 'N/A',
                    'currency' => $data['currency'] ?? 'USD',
                    'store_label' => $data['store_label'] ?? 'Lomnov Store',
                    'phone_number' => $data['phone_number'] ?? '85512345678',
                    'terminal_label' => $data['terminal_label'] ?? 'Rental-01',
                    'static' => $data['static'] ?? false,
                ]);
            }

            // Custom data including Bakong QR
            $customData = [
                'readings' => $readingsInfo,
                'bakong_data' => [
                    'amount' => $invoice->total_amount,
                    'currency' => 'USD',
                    'recipient' => 'Lomnov Real Estate',
                    'account' => $data['merchant_account'] ?? 'lomnov@aba',
                    'reference' => $data['reference'] ?? 'N/A',
                    'description' => 'Rent Payment - ' . ($data['reference'] ?? 'N/A'),
                    'qr_base64' => $qrBase64,
                ],
            ];

            $invoice->setCustomData($customData);

            // Save invoice
            $invoice->save('invoices');

            return $invoice;

        } catch (\Throwable $e) {
            Log::error('Receipt generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Fetch QR code base64 from Python microservice
     *
     * @param array $qrData
     * @return string|null
     */
    protected static function fetchQRFromMicroservice(array $qrData): ?string
    {
        try {
            $microserviceUrl = config('services.qr_microservice.url', 'http://localhost:5001');
            
            $requestBody = [
                'merchant_account' => $qrData['merchant_account'],
                'merchant_name' => $qrData['merchant_name'] ?? 'Lomnov Real Estate',
                'merchant_city' => $qrData['merchant_city'] ?? 'Phnom Penh',
                'amount' => $qrData['amount'],
                'reference' => $qrData['reference'],
                'currency' => $qrData['currency'],
                'store_label' => $qrData['store_label'] ?? 'Lomnov Store',
                'phone_number' => $qrData['phone_number'] ?? '85512345678',
                'terminal_label' => $qrData['terminal_label'] ?? 'Rental-01',
                'static' => $qrData['static'] ?? false,
            ];
            
            // Add this logging to see what's being sent
            Log::info('📤 Sending to microservice', [
                'url' => "{$microserviceUrl}/api/khqr",
                'body' => $requestBody
            ]);
            
            $response = Http::withHeaders([
                    'Content-Type' => 'application/json',  // ← Add this!
                    'Accept' => 'application/json',
                ])
                ->timeout(10)
                ->post("{$microserviceUrl}/api/khqr", $requestBody);

            Log::info('📥 Microservice response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['qr_base64'] ?? null;
            }

            Log::warning('QR microservice failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;

        } catch (\Throwable $e) {
            Log::error('QR microservice connection failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Validate base64 image data
     *
     * @param string|null $base64
     * @return bool
     */
    protected static function isValidBase64Image(?string $base64): bool
    {
        if (empty($base64)) {
            return false;
        }

        // Remove data URL prefix if present
        $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);

        // Check if valid base64
        if (base64_decode($base64, true) === false) {
            return false;
        }

        return true;
    }
}