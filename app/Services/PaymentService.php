<?php
namespace App\Services;
use App\Enums\PaymentStatus;
use App\Models\Bakong;
use App\Models\Consumption;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Models\Room;
use App\Models\Service;
use App\Models\PaymentItem;
use App\Services\BakongService;
use App\Services\ConsumptionService;
use Illuminate\Database\Eloquent\PendingHasThroughRelationship;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;


class PaymentService{
    protected int $landlord_id;
    protected int $tenant_id;
    protected Room $room;

    protected ConsumptionService $consumptionService;
    protected BakongService $bakongService;

    public function __construct(int $room_id)
    {
        // resolve other services automatically
        $this->consumptionService = app(ConsumptionService::class);
        $this->bakongService = app(BakongService::class);

        // initialize your other properties
        $this->room = Room::find($room_id);
        $this->tenant_id = $this->room->currentContract->tenant_id;
        $this->landlord_id = $this->room->building->landlord->id;
    }

    public function processPayment(?bool $lastPayment,?bool $chargePenalty,?Consumption ...$consumptions){
        Log::info("\n################################################################\n################################################################\n################################################################");
        Log::info("consumptions count: " . count($consumptions));
        $status = PaymentStatus::PENDING->value;
        $setting = Setting::where('user_id', $this->landlord_id)->first();
        $receiptService = new ReceiptService();


        Log::info("already init status {$status}");
    
        
        $payment = Payment::create([
            'landlord_id' => $this->landlord_id,
            'tenant_id' => $this->tenant_id,
            'room_id' => $this->room->id,
            'status' => $status,
        ]);

        if($chargePenalty){
            Log::info("charging penalty");
            $dateTime = Carbon::today();
            $fine_after = now()->setDay($setting->fine_after)->startOfDay();

            $days = $dateTime->diffInDays($fine_after);

            $fine_fee = $days * $setting->fine_per_day;
         
            PaymentItem::create([
                'payment_id' => $payment->id,
                // 'service_id' => $service->id,
                'service_name' => "Fine",
                'unit_price' => $setting->fine_per_day,
                'quantity' => $days,
                'subtotal' => $fine_fee,
            ]);
        }
        
        Log::info('Payment created', [
            'payment_id' => $payment->id,
            'room_id' => $payment->room_id,
            'status' => $payment->status,
        ]);
    
        $room = Room::find($this->room->id);
        Log::info("pin1");
        Log::info("room services : {$room->services}");

        foreach($room->services as $service){
                PaymentItem::create([
                    'payment_id' => $payment->id,
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'unit_price' => $service->unit_price,
                    'quantity' => 1,
                    'subtotal' => $service->unit_price,
                ]);
            // }
        }

      
            
        if(empty($consumptions) || $consumptions == null){
            // Handle no consumption case
        }else{
            $latest_consumption = $this->consumptionService->getLatestConsumptions($this->room->id);

            //calculate consumption then create payment item
            foreach($consumptions as $consumption){
                if($consumption->type == "water"){
                    $quantity = $consumption->end_reading - $latest_consumption['water']->end_reading;
                    if($quantity < 0){
                        $quantity = 0 ;
                        $consumption->end_reading = $latest_consumption['water']->end_reading;
                    }
                    
                
                    $temp = Consumption::create([
                        'room_id' => $this->room->id,
                        'end_reading'=> $consumption->end_reading,
                        'photo_url'=>$consumption->photo_url,
                        'consumption' =>  $quantity,
                        'type'=> $consumption->type,
                    ]);
                    PaymentItem::create([
                        'payment_id' => $payment->id,
                        'consumption_id'=>  $temp->id,
                        'service_name' => $consumption->type,
                        'unit_price' => $setting->water_price,
                        'quantity' => $quantity,
                        'subtotal' => ($quantity * $setting->water_price),
                    ]);
                } else {
                    $quantity = $consumption->end_reading - $latest_consumption['electricity']->end_reading;
                    if($quantity < 0){
                        $quantity = 0 ;
                        $consumption->end_reading = $latest_consumption['electricity']->end_reading;
                    }
                    $temp= Consumption::create([
                        'room_id' => $this->room->id,
                        'end_reading'=> $consumption->end_reading,
                        'photo_url'=>$consumption->photo_url,
                        'consumption' =>  $quantity,
                        'type'=> $consumption->type,
                    ]);
                    PaymentItem::create([
                        'payment_id' => $payment->id,
                        'consumption_id'=>  $temp->id,
                        'service_name' => $consumption->type,
                        'unit_price' => $setting->electricity_price,
                        'quantity' => $quantity,
                        'subtotal' => ($quantity * $setting->electricity_price),
                    ]);
                
                }
            }
        }
        Log::info("done processing payment items");
               
        //last payment
        if(!$lastPayment){
            PaymentItem::create([
                'payment_id' => $payment->id,
                'service_name' => "Room",
                'unit_price' => $room->price,
                'quantity' => 1,
                'subtotal' => $room->price,
            ]);
        }
    
        $total = $this->getTotalPayment($payment->id);
    
        // Get QR, md5 and deep link
        $response = $this->bakongService->generateKHQR($total);
        $responseData = $response->getData(true); 

        if ($responseData['success']) {
            $transaction = $responseData['data']['transaction'];
            
            Log::info("response from bakong = " . json_encode($responseData));
            
            $payment->update([
                'transaction_id' => $transaction['id'],
                'qr_code' => $responseData['data']['qr_code'],
                'md5' => $responseData['data']['md5'],
            ]);
        } else {
            // Handle error
            Log::error("Bakong KHQR generation failed: " . $responseData['message']);
        }

        //generate reciept
        $receiptService->generate($payment->id);
        $payment->refresh();
        
        
        return response()->json([
            'status' => 200,
            'payment_id' => $payment->id,
            'payment' => $payment,
            'paymnet_items'=> $payment->paymentItems,
            'total' => $total
        ]);
    }

    public static function checkPendingReceipts(int $landlordId): array
    {
        Log::info("checkPendingReceipts called for landlord: {$landlordId}");

        $bakongService = app(BakongService::class);
        $paymentStatus = PaymentStatus::PENDING->value;

        $payments = Payment::where('landlord_id', $landlordId)
            ->where('status', $paymentStatus)
            ->whereNotNull('md5')
            ->get();

        if ($payments->isEmpty()) {
            return [
                'message' => 'No pending transactions found',
                'count'   => 0,
            ];
        }

        $processed = 0;

        foreach ($payments as $payment) {
            try {
                Log::info("🔍 Checking Bakong transaction md5={$payment->md5}");

                $bakongService->checkAndUpdate($payment->md5);
                $processed++;

            } catch (\Throwable $e) {
                // Never fail the whole batch
                Log::error(
                    "Bakong check failed for md5={$payment->md5}: {$e->getMessage()}"
                );
            }
        }

        return [
            'message' => 'Check Transaction Status Successfully',
            'count'   => $processed,
        ];
    }
    
    // public function processPayment(?Consumption ...$consumptions){
    //     Log::info("\n################################################################\n################################################################\n################################################################");
    //     Log::info("consumptions:\n" . json_encode($consumptions, JSON_PRETTY_PRINT));        $status = PaymentStatus::PENDING->value;

    //     Log::info("already init status {$status} ");
        
    //     if(!$consumptions){
    //         //no soncumpiton
    //     }else{
    //         // initialize the payment record
    //         $payment = Payment::create([
    //             'landlord_id' => $this->landlord_id,
    //             'tenant_id' => $this->tenant_id,
    //             'room_id' => $this->room->id,
    //             'status' => $status,
    //         ]);
    //         Log::info('Payment created', [
    //             'payment_id' => $payment->id,
    //             'room_id' => $payment->room_id,
    //             'status' => $payment->status,
    //         ]);
    //         //find total price
    //         $room = Room::find($this->room->id);
    //         // Log::info("proccessing payment items ");
    //         // Log::info("room service {$room->services}");
    //         Log::info("pin1");
    //         //find total consumption usage price
    //         foreach($room->services as $service){
    //             if($service->name == 'electricity' || $service->name == "water"){
    //                 foreach($consumptions as $consumption){
    //                     Log::info("consumption : ${consumption}");
    //                     $new_consumptuion = Consumption::create($consumption);
    //                     $consumption_usage = $this->consumptionService->getConsumptionUsage($new_consumptuion)?? $consumption->end_reading;
    //                     Log::info("consumption usage = ${consumption_usage}");

    //                     PaymentItem::create([
    //                         'payment_id' => $payment->id,
    //                         'service_id' => $consumption->service_id,
    //                         'service_name' => $consumption->service->name,
    //                         'unit_price' => $consumption->service()->price_per_unit,
    //                         'quantity' => $consumption_usage,
    //                         'subtotal' => $consumption_usage * $consumption->service->price_per_unit,
    //                     ]);
    //                 }
    //             }else{
    //                 PaymentItem::create([
    //                     'payment_id' => $payment->id,
    //                     'service_id' => $service->id,
    //                     'service_name' => $service->name,
    //                     'unit_price' => $service->unit_price,
    //                     'quantity' => 1,
    //                     'subtotal' => $service->unit_price,
    //                 ]);
    //             }
    //         }        
    //         Log::info("done proccessing payment items ");

        
    //         //store room price
    //         PaymentItem::create([
    //             'payment_id' => $payment->id,
    //             'service_name' => "room",
    //             'unit_price' => $service->unit_price,
    //             'quantity' => 1,
    //             'subtotal' => $service->unit_price,
    //         ]);

    //         $total = $this->getTotalPayment($payment->id); //need room price

    //         //get qr, md5 and deep link
    //         $response =  $this->bakongService->generateKHQR($total);
    //         $transaction =  $response['data']['transaction'];

    //         $payment->update([
    //             'transaction_id' => $transaction->id
    //         ]);
    //     }
    //     return response()->json(([
    //         'stutus' => 200,
    //         'payment_id' => $payment->id
    //     ]));
    // }

    public function getTotalPayment(int $payment_id){
        $payment = Payment::find($payment_id);
        $room = Room::find($payment->room->id);

        //if have transaction and paid = get data from payload
        if($payment->transaction_id){

        }else{
        //else compute it 
        $total = 0;
        $payment_items = $payment->paymentItems;
        foreach($payment_items as $item){
            //sum all subtotal
            $total += $item->subtotal;
        }

        return $total;
    }
    }
}