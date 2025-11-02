<?php

namespace App\Services;

use LaravelDaily\Invoices\Classes\Buyer;
use LaravelDaily\Invoices\Classes\Seller;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use LaravelDaily\Invoices\Invoice;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ReceiptService
{
    public static function generate($data)
    {
        try {
            // Seller info
            $seller = new Seller([
                'name' => config('app.name'),
                'phone' => '012-345-6789',
                'custom_fields' => [
                    'Email' => 'info@example.com',
                    'Address' => '123 Business Street, City',
                ],
            ]);

            // Buyer info
            $buyer = new Buyer([
                'name' => $data['customer_name'],
                'custom_fields' => [
                    'Email' => $data['customer_email'] ?? '',
                ],
            ]);

            // Items
            $items = collect($data['items'])->map(function ($item) {
                return (new InvoiceItem())
                    ->title($item['name'])
                    ->pricePerUnit($item['price'])
                    ->quantity($item['qty']);
            });

            // Generate QR code as base64
            $qrContent = $data['qr_content'] ?? 'https://example.com/verify/' . ($data['id'] ?? uniqid());
            $qrImage = QrCode::format('png')->size(200)->generate($qrContent);
            $qrBase64 = 'data:image/png;base64,' . base64_encode($qrImage);

            // Log::info('QR Code generated', [
            //     'path' => Storage::disk('public')->path($qrCodePath),
            //     'url' => asset('storage/' . $qrCodePath),
            // ]);

            // $qrPublicUrl = asset('storage/' . $qrCodePath);

            // $qrBinary = Storage::disk('public')->get($qrCodePath);
            // $qrBase64 = 'data:image/png;base64,' . base64_encode($qrBinary);

            // Create invoice
            $invoice = Invoice::make('RECEIPT')
                ->buyer($buyer)
                ->seller($seller)
                ->logo(public_path('vendor/invoices/lomnov_logo.png'))
                ->currencySymbol('$')
                ->currencyCode('USD')
                ->date(now())
                ->addItems($items->toArray())
                ->notes('Thank you for your purchase!')
                ->filename('receipt_' . $data['id'])
                ->setCustomData([
                    // 'qr_code_url' => $qrPublicUrl,      // optional
                    'qr_code_base64' => $qrBase64,      // recommended
                ])
                ->save('public');

            // Attach QR code
            // $invoice->qr_code_url = 'storage/' . $qrCodePath;

            // Log::info('Invoice generated', [
            //     'filename' => $invoice->name,
            //     'path' => Storage::disk('public')->path('invoices/' . $invoice->name . '.pdf'),
            // ]);

            return $invoice;

        } catch (\Throwable $e) {
            Log::error('Receipt generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
