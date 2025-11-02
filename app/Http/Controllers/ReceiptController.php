<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use LaravelDaily\Invoices\Invoice;
use LaravelDaily\Invoices\Classes\Buyer;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use App\Services\ReceiptService;

class ReceiptController extends Controller
{

    public function download()
    {
        $customer = new Buyer([
            'name' => 'John Doe',
            'custom_fields' => [
                'email' => 'john@example.com',
            ],
        ]);

        $item = (new InvoiceItem())
            ->title('1 Year Subscription')
            ->pricePerUnit(129);

        $invoice = Invoice::make('receipt')
            ->buyer($customer)
            ->addItem($item)
            ->logo(public_path('vendor/invoices/lomnov_logo.png'))
            ->filename('receipt_123')
            ->save('invoices'); // will save to storage/app/invoices/

        return $invoice->stream(); // or ->download()
    }

    public function testReceipt()
    {
        $data = [
            'id' => 123,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'items' => [
                ['name' => 'Subscription Plan', 'qty' => 1, 'price' => 29.99],
                ['name' => 'Rental', 'qty' => 1, 'price' => 250.00],
                ['name' => 'Service', 'qty' => 2, 'price' => 100.00],
            ],
            'qr_content' => 'https://example.com/verify/123'
        ];

        $invoice = ReceiptService::generate($data);

        return $invoice->stream();
    }
}