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
                'phone' => '012-345-6789',
                'address' => '123 Main St, Cityville',
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

    public function testReceipt(Request $request)
    {
        $data = [
            'tenant_name' => $request->input('tenant_name'),
            'building_name' => $request->input('building_name'),
            'room_name' => $request->input('room_name'),

            // Old/new meter readings
            'readings' => [
                ['item' => 'Water', 'new' => '00266', 'old' => '00250', 'total' => 16],
                ['item' => 'Electricity', 'new' => '02618', 'old' => '02512', 'total' => 106],
            ],

            // Invoice line items
            'items' => [
                ['name' => 'Water', 'quantity' => 16, 'price' => 0.50],
                ['name' => 'Electricity', 'quantity' => 106, 'price' => 0.25],
                ['name' => 'Motor', 'quantity' => 2, 'price' => 8.00],
                ['name' => 'Room', 'quantity' => 1, 'price' => 100.00],
            ],
        ];

        $invoice = ReceiptService::generate($data);

        return $invoice->stream();
    }
}