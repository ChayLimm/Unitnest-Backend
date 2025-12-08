<?php

namespace App\Services;

use LaravelDaily\Invoices\Invoice;
use LaravelDaily\Invoices\Classes\Buyer;
use LaravelDaily\Invoices\Classes\Party;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\BakongService;
use App\Services\ConsumptionService;
use App\Models\Payment;
use App\Models\Receipt;

class ReceiptService
{
    public static function generate($payment_id)
    {
        try {

            $payload = self::e_receipt_format($payment_id);

            // Seller / Landlord
            $landlord = new Party([
                'name'          => $payload['landlord_name'] ?? 'Lomnov Real Estate',
                'address'       => $payload['landlord_address'] ?? 'Phnom Penh, Cambodia',
                'phone'         => $payload['landlord_phone'] ?? null,
                'custom_fields' => [
                    'Email' => $payload['landlord_email'] ?? null,
                    'Building' => $payload['landlord_building'] ?? null,
                ],
            ]);

            // Buyer / Tenant
            $tenant = new Buyer([
                'name' => $payload['tenant_name'],
                'address' => ($payload['tenant_room'] ?? '') . ' - Floor ' . ($payload['tenant_room_floor'] ?? ''),
                'custom_fields' => [
                    'Room' => $payload['tenant_room'] ?? '',
                    'Floor' => $payload['tenant_room_floor'] ?? '',
                ],
            ]);

            // Readings (optional)
            $readingsInfo = collect($payload['readings'] ?? [])->map(function ($r) {
                return [
                    'item' => $r['item'] ?? '',
                    'new' => $r['new'] ?? 0,
                    'old' => $r['old'] ?? 0,
                    'total' => $r['total'] ?? 0,
                    'unit' => $r['unit'] ?? ''
                ];
            });

            // Items
            $items = collect($payload['items'] ?? [])->map(function ($item) {
                return (new InvoiceItem())
                    ->title($item['name'])
                    ->pricePerUnit($item['price'])
                    ->quantity($item['quantity']);
            });

            $customName = 'receipt_' . $payload['room_id'] . '_' . now()->format('YmdHis');

            // Create invoice to calculate total
            $invoice = Invoice::make()
                ->name('RENT RECEIPT')
                ->seller($landlord)
                ->buyer($tenant)
                ->template('lomnov_receipt')
                ->logo(public_path('vendor/invoices/lomnov_logo.png'))
                ->currencySymbol('$')
                ->currencyCode('USD')
                ->date(now())
                ->addItems($items->toArray())
                ->notes('Thank you for your rent payment.')
                ->filename($customName)
                ->serialNumberFormat($payload['room_id'] . '_' . now()->format('YmdHis'));

            $totalAmount = $invoice->calculate()->total_amount;

            Log::info('Invoice total calculated', [
                'total_amount' => $totalAmount,
                'has_qr' => isset($payload['qr_base64'])
            ]);

            // Request KHQR only if generate_qr is true
            $qrBase64 = null;
            if (!empty($payload['generate_qr'])) {
                $bakong_payload = self::requestBakongQR($totalAmount);

                $bakongData = is_object($bakong_payload)
                    ? $bakong_payload->getData(true)
                    : $bakong_payload;

                $qrcode = $bakongData['data']['qr_code'] ?? null;

                // Get QR from microservice if not provided
                $qrBase64 = self::fetchQRFromMicroservice([
                    "qr_code" => $qrcode
                ]);
            }

            // Invoice custom data
            $invoice->setCustomData([
                'readings' => $readingsInfo,
                'bakong_data' => [
                    'amount' => $totalAmount,
                    'currency' => 'USD',
                    'recipient' => $payload['landlord_name'] ?? 'Lomnov Real Estate',
                    'account' => $payload['merchant_account'] ?? 'lomnov@aba',
                    'reference' => $payload['reference'] ?? 'PAY-' . $payload['id'],
                    'description' => 'Rent Payment - ' . ($payload['reference'] ?? 'PAY-' . $payload['id']),
                    'qr_base64' => $qrBase64,
                ],
            ]);

            Receipt::create([
                'receipt_name' => $customName . '.pdf',
                'payment_id' => $payment_id,
            ]);

            // Save PDF
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

    public function e_receipt_format_test($payment_id){
        $payment = Payment::find($payment_id);

        if(!$payment){
            throw new \Exception("Payment not found");
        }

        $tenant_info = $payment->tenant;
        $landlord_info = $payment->landlord;

        $room_info = $payment->room;

        $building_info = $room_info->building;

        $payment_items = $payment->paymentItems;

        $items = [];
        $readingsInfo = [];

        foreach($payment_items as $item){
            $service = $item->service;
            $items[] = [
                "name" => $service->name,
                "price" => $item->unit_price,
                "quantity" => $item->quantity
            ];
        };

        $consumptions = $room_info->consumptions()->orderBy('created_at')->get();

        $consumptionService = new ConsumptionService();

        foreach($consumptions as $cons){
            $usage = $consumptionService->getConsumptionUsage($room_info->id, $cons->id) ?? $cons->end_reading;

            $previous = $room_info->consumptions()
                ->where('service_id', $cons->service_id)
                ->where('created_at', '<', $cons->created_at)
                ->orderBy('created_at', 'desc')
                ->first();

            $readingsInfo[] = [
                "item" => $cons->service->name,
                "new" => $cons->end_reading,
                "old" => $previous ? $previous->end_reading : 0,
                "total" => $usage,
                "unit" => $cons->service->unit_name,
            ];

            Log::info("consumption usage for service {$cons->service_id} is {$usage}");
        }

        

        return [
            "id" => $payment->id,
            "landlord_name" => $landlord_info->name,
            "landlord_email" => $landlord_info->email,
            "landlord_phone" => $landlord_info->phonenumber,
            "landlord_address" => $building_info->address,
            "landlord_building" => $building_info->name,

            "tenant_name" => $tenant_info->name,
            "tenant_email" => $tenant_info->email,
            "tenant_phone" => $tenant_info->phonenumber,
            "tenant_room" => $room_info->room_number,
            "tenant_room_floor" => $room_info->floor,

            "items" => $items,

            "readings" => $readingsInfo,

            "generate_qr" => true,
        ];
    }

    protected static function e_receipt_format($payment_id){
        $payment = Payment::find($payment_id);

        if(!$payment){
            throw new \Exception("Payment not found");
        }

        $tenant_info = $payment->tenant;
        $landlord_info = $payment->landlord;

        $room_info = $payment->room;

        $building_info = $room_info->building;

        $payment_items = $payment->paymentItems;

        $items = [];

        foreach($payment_items as $item){
            $service = $item->service;
            $items[] = [
                "name" => $service->name,
                "price" => $item->unit_price,
                "quantity" => $item->quantity
            ];
        };

        $readingsInfo = [];

        $consumptions = $room_info->consumptions()->orderBy('created_at')->get();

        $consumptionService = new ConsumptionService();

        foreach($consumptions as $cons){
            $usage = $consumptionService->getConsumptionUsage($room_info->id, $cons->id) ?? $cons->end_reading;

            $previous = $room_info->consumptions()
                ->where('service_id', $cons->service_id)
                ->where('created_at', '<', $cons->created_at)
                ->orderBy('created_at', 'desc')
                ->first();

            $readingsInfo[] = [
                "item" => $cons->service->name,
                "new" => $cons->end_reading,
                "old" => $previous ? $previous->end_reading : 0,
                "total" => $usage,
                "unit" => $cons->service->unit_name,
            ];

            Log::info("consumption usage for service {$cons->service_id} is {$usage}");
        }


        return [
            "id" => $payment->id,
            "room_id" => $room_info->id,
            "landlord_name" => $landlord_info->name,
            "landlord_email" => $landlord_info->email,
            "landlord_phone" => $landlord_info->phonenumber,
            "landlord_address" => $building_info->address,
            "landlord_building" => $building_info->name,

            "tenant_name" => $tenant_info->name,
            "tenant_email" => $tenant_info->email,
            "tenant_phone" => $tenant_info->phonenumber,
            "tenant_room" => $room_info->room_number,
            "tenant_room_floor" => $room_info->floor,

            "items" => $items,

            "readings" => $readingsInfo,

            "generate_qr" => true,
        ];
    }

    protected static function requestBakongQR($amount)
    {
        $bakongService = new BakongService();
        return $bakongService->generateKHQR($amount);
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
            $microserviceUrl = config('services.qr_microservice');
            
            $requestBody = [
                'qr_code' => $qrData['qr_code'] ?? ''
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