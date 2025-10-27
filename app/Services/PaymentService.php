<?php

use App\Enums\PaymentStatus;
use App\Models\Bakong;
use App\Models\Consumption;
use App\Models\Payment;
use App\Models\User;
use App\Models\Room;
use App\Models\Service;
use App\Models\PaymentItem;
use App\Services\BakongService;
use App\Services\ConsumptionService;


class PaymentService{
    protected int $landlord_id;
    protected int $tenant_id;
    protected int $room_id;
    protected ?Consumption $consumption;


    public function __construct(private ConsumptionService $consumptionService,private BakongService $bakongService,$landlord_id, $tenant_id, $room_id, ?Consumption $consumption = null)
    {
        $this->landlord_id = $landlord_id;
        $this->tenant_id = $tenant_id;
        $this->room_id = $room_id;
        $this->consumption = $consumption;
    }
    
    public function processPayment(?Consumption ...$consumptions){
        if(!$consumptions){
            //no soncumpiton
        }else{
            //define all needed variable
            $status = PaymentStatus::Pending;
            $total_consumption_price = 0;
            $service_fee = 0;

            // initialize the payment record
            $payment = Payment::create([
                'landlord_id' => $this->landlord_id,
                'tenant_id' => $this->tenant_id,
                'room_id' => $this->room_id,
                'status' => $status,
        ]);

        //find total price
        $room = Room::find($this->room_id);
        
        //find total consumption usage price
        foreach($room->services as $service){
            if($service->name == 'electricity' || $service->name == "water"){
                foreach($consumptions as $consumption){
                    $consumption_usage = $this->consumptionService->getConsumptionUsage($consumption)?? $consumption->end_reading;
                    $payment_item = PaymentItem::create([
                        'payment_id' => $payment->id,
                        'service_id' => $consumption->service_id,
                        'unit_price' => $consumption->service()->price_per_unit,
                        'quantity' => $consumption_usage,
                        'subtotal' => $consumption_usage * $consumption->service()->price_per_unit,
                    ]);
                    $total_consumption_price += $consumption_usage * $consumption->service()->price_per_unit;
                }
            }
        }
       
        
        foreach($room->services() as $service){
            $payment_item = PaymentItem::create([
                'payment_id' => $payment->id,
                'service_id' => $service->id,
                'unit_price' => $service->unit_price,
                'quantity' => 1,
                'subtotal' => $service->unit_price,
            ]);
            $service_fee += $service->monthly_fee;
        }

        //store room price
        $payment_item = PaymentItem::create([
            'payment_id' => $payment->id,
            'service_id' => $service->id,
            'unit_price' => $service->unit_price,
            'quantity' => 1,
            'subtotal' => $service->unit_price,
        ]);

        $total = $this->getTotalPayment($payment->id); //need room price
        //get qr, md5 and deep link

        $response =  $this->bakongService->generateKHQR($total);
        $transaction =  $response['transaction'];

        $payment->update([
            'transaction_id' => $transaction->id
        ]);
        }
      
    }

    public function getTotalPayment(int $payment_id){
        //if have transaction and paid = get data from payload
        //else compute it 
        $total = 0;
        $payment = Payment::find($payment_id);
        $room = Room::find($payment->room->id);
        $payment_items = $payment->paymentItems;
        foreach($payment_items as $item){
            //sum all subtotal
            $total += $item->subtotal;
        }

        return $total;
    }
}