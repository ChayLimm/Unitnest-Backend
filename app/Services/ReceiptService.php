<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;

use LaravelDaily\Invoices\Invoice;
use LaravelDaily\Invoices\Classes\Buyer;
use LaravelDaily\Invoices\Classes\Seller;
use LaravelDaily\Invoices\Classes\InvoiceItem;

use Illuminate\Support\Facades\Log;

class ReceiptService
{
    public static function generate(array $data)
    {
        try {
            $landlord = new Seller([
                'name' => 'Lomnov Real Estate',
                'phone' => '023-456-7890',
                'custom_fields' => [
                    'Email' => 'info@lomnov.com',
                    'Address' => '456 Property Ave, Metropolis',
                    'National ID' => '123-45-6789',
                ],
            ]);

            $tenant = new Buyer([
                'name' => $data['tenant_name'],
                'custom_fields' => [
                    'Building' => $data['building_name'] ?? '',
                    'Room' => $data['room_name'] ?? '',
                ],
            ]);

            $readingsInfo = collect($data['readings'])->map(function ($r) {
                return [
                    'item' => $r['item'],
                    'new' => $r['new'],
                    'old' => $r['old'],
                    'total' => $r['total'],
                ];
            });
 

            // Items
            $items = collect($data['items'])->map(function ($item) {
                return (new InvoiceItem())
                    ->title($item['name'])
                    ->pricePerUnit($item['price'])
                    ->quantity($item['quantity']);
            });

            // Generate QR code as base64
            $qrContent = $data['qr_content'] ?? 'https://bakong.page.link/h8DnkCxJDEsCSV3F8' . ($data['id'] ?? uniqid());
            $logoPath = public_path('vendor/invoices/lomnov_logo.png');

            try {
                if (!file_exists($logoPath)) {
                    Log::warning('Bakong logo not found at ' . $logoPath);
                    $logoPath = null;
                }

                $qrCodeResult = Builder::create()
                    ->writer(new PngWriter())
                    ->data($qrContent)
                    ->encoding(new Encoding('UTF-8'))
                    ->size(200)
                    ->margin(10)
                    ->logoPath($logoPath)
                    ->logoResizeToWidth(50)
                    ->logoPunchoutBackground(true)
                    ->build();

                $qrImageString = $qrCodeResult->getString();
                $qrBase64 = 'data:image/png;base64,' . base64_encode($qrImageString);

            } catch (\Throwable $e) {
                Log::error('QR Code generation failed', [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }

            // Create invoice
            $invoice = Invoice::make('RECEIPT')
                ->buyer($tenant)
                ->seller($landlord)
                ->logo(public_path('vendor/invoices/lomnov_logo.png'))
                ->template('lomnov_receipt')
                ->currencySymbol('$')
                ->currencyCode('USD')
                ->date(now())
                ->addItems($items->toArray())
                ->notes('Thank you for your rent payment.')
                ->filename('receipt_' . ($data['id'] ?? uniqid()))
                ->setCustomData([
                    'qr_code_base64' => $qrBase64,
                    'readings' => $readingsInfo,
                ])
                ->save('public');

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
