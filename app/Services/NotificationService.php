<?php

namespace App\Services;

use App\Models\Consumption;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Telegrambot;
use App\Models\Notification;
use App\Enums\NotificationType;
use App\Enums\NotificationStatus;

// handle notifications such as payment, registration,
// reminders, store notify and notify tenants.
class NotificationService {
    
    protected $telegramBotService;

    public function __construct(TelegramBotService $telegramBotService) {
        $this->telegramBotService = $telegramBotService;

    }

    // store notification 
    public function storeNotification(int $landlordId, int $chatId, $type, $payload = []){   
        try{
            $notification = Notification::create([
                'landlord_id' => $landlordId,
                'chat_id' => $chatId,
                'read' => false,
                'notification_type' => $type,
                'status' => NotificationStatus::PENDING,
                'payload' => $payload,
            ]);
            return $notification;
        }catch (\Exception $e){
            Log::error("Failed to store notification: " . $e->getMessage());
            return null;
        }
    }

    //
    // Registration (store info reponse from form register to notification table, notify tenant)
    //
    public function handleRegistrationSubmmision($data){
        // extract data
        $fields = $data['fields'] ?? [];
        $timestamp = $data['timestamp'] ?? null;
        $responseId = $data['response_id'] ?? null;

        // extract data from 'fields' object
        $firstName = $fields['First Name'] ?? null;
        $lastName = $fields['Last Name'] ?? null;
        $email = $fields['Email (Optional)'] ?? null;
        $phone = $fields['Phone Number'] ?? null;
        $chatId = $fields['Tenant Chat ID'] ?? null;
        $landlordId = $fields['Landlord ID'] ?? null;
        $identityCardUrls = $fields['Identity Card ID_urls'] ?? [];
        $identityImageUrl = !empty($identityCardUrls) ? $identityCardUrls[0] : null;

        // prepare payload
        $payload = [
            // 'name' => $name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'email' => $email,
            'identity_image_url' => $identityImageUrl,
        ];
        $bot = Telegrambot::where('user_id', $landlordId)->first();

        Log::info('Processing registration submission', compact('landlordId','chatId','responseId'));
        if (!$landlordId || !$chatId) {
            return [
                'success' => false,
                'message' => 'Registration failed: Missing landlord ID or chat ID.',
                'notification_id' => null,
            ];
        }

        // store to db
        $notification = null;
        $notification = $this->storeNotification($landlordId, $chatId, NotificationType::REGISTRATION, $payload);

        // notify user with define bot
        if ($notification) {
            $this->notifyRegistrationTenant($bot, $chatId, $payload, true);
            return [
                'success' => true,
                'message' => 'Form submission received',
                'notification_id' => $notification ->id,
            ];
        } else {
            $this->notifyRegistrationTenant($bot, $chatId, $payload, false);
            return [
                'success' => false,
                'message' => 'Registration failed: Could not store notification.',
                'notification_id' => null,
            ];
        }
    }


    // handle notify message regisetration tenant
    public function notifyRegistrationTenant($bot, $chatId, $payload, $success = true){
        if ($success) {
            // $name = $payload['name'] ?? 'N/A';
            $firstName = $payload['first_name'] ?? 'N/A';
            $lastName = $payload['last_name'] ?? 'N/A';
            $name = trim($firstName . ' ' . $lastName);
            $phone = $payload['phone'] ?? 'N/A';
            $email = $payload['email'] ?? 'N/A';
            $identityImageUrl = $payload['identity_image_url'] ?? null;
            $message = "✅ We received your registration info:\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "Name: {$name}\n"
                . "Phone: {$phone}\n"
                . "Email: {$email}\n"
                . ($identityImageUrl ? "ID Card: {$identityImageUrl}\n" : "")
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "Please wait for your landlord to approve your registration.";
        } else {
            $message = "❌ Registration failed: Missing landlord ID or chat ID. Please try again.";
        }

        if ($bot && $chatId) {
            try {
                $this->telegramBotService->sendMessage($bot, $chatId, $message);
                return true;
            } catch (\Exception $e) {
                Log::error('Failed to send Telegram message: ' . $e->getMessage());
            }
        }
        return false;
    }


    //
    // Payment (store info reponse from ai to payment table, notify tenant)
    //
    public function handlePaymentRequest($data){
        // extract data
        $result = $data['result'] ?? [];
        $landlordId = $result['landlord_id'] ?? null;
        $chatId = $result['chat_id'] ?? null; 
        $meta = $result['data'] ?? null;

        // meta data of meter reponse
        $waterMeter = $waterAccuracy = $electricityMeter = $electricityAccuracy = null;
        if (!empty($meta) && is_array($meta)) {
            foreach ($meta as $meter){
                if ($meter['type'] === 'water'){
                    $waterMeter = isset($meter['meter_number']) ? (float)$meter['meter_number'] : null;
                    $waterAccuracy = isset($meter['accuracy']) ? (float)$meter['accuracy'] : null;
                }elseif($meter['type'] === 'electricity'){
                    $electricityMeter = isset($meter['meter_number']) ? (float)$meter['meter_number'] : null;
                    $electricityAccuracy = isset($meter['accuracy']) ? (float)$meter['accuracy'] : null;
                }
            }
        }

        // get url image
        $image1 = $result['image_1'] ?? null;
        $image2 = $result['image_2'] ?? null;
        
        // prepare payload
        $payload = [
            'water_meter' => $waterMeter,
            'water_accuracy' => $waterAccuracy,
            'electricity_meter' => $electricityMeter,
            'electricity_accuracy' => $electricityAccuracy,
            'image_1' => $image1,
            'image_2' => $image2,
        ];

        $bot = Telegrambot::where('user_id', $landlordId)->first();

        // store to db
        $notification = null;
        if ($landlordId && $chatId) {
            $notification = $this->storeNotification($landlordId, $chatId, NotificationType::PAYMENT, $payload);
        }

        // notify tenant
        if ($notification){
            $this->notifyPaymentRequestTenant($bot, $chatId, $payload, true);
            return [
                'success' => true,
                'message' => 'Payment request sent',
                'notification_id' => $notification->id,
            ];
        }else{
            $this->notifyPaymentRequestTenant($bot, $chatId, $payload, false);
            return [
                'success' => false,
                'message' => 'Payment request failed: Could not store notification.',
                'notification_id' => null,
            ];
        }


    }

    // hanle notify messahe for payment request tenant
    public function notifyPaymentRequestTenant($bot, $chatId, $payload, $success = true){
        if ($success) {
            $waterMeter = $payload['water_meter'] ?? 'N/A';
            $waterAccuracy = $payload['water_accuracy'] ?? 'N/A';
            $electricityMeter = $payload['electricity_meter'] ?? 'N/A';
            $electricityAccuracy = $payload['electricity_accuracy'] ?? 'N/A';
            $image1 = $payload['image_1'] ?? null;
            $image2 = $payload['image_2'] ?? null;

            $message = "✅ We received your payment request info:\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "Water Meter: {$waterMeter} (Accuracy: {$waterAccuracy})\n"
                . "Electricity Meter: {$electricityMeter} (Accuracy: {$electricityAccuracy})\n"
                . ($image1 ? "Image 1: {$image1}\n" : "")
                . ($image2 ? "Image 2: {$image2}\n" : "")
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "Please wait for your landlord to approve and send you a receipt before making a rent payment.";
        } else {
            $message = "❌ Payment request failed: Could not store your request. Please try again or contact your landlord.";
        }

        if ($bot && $chatId) {
            try {
                $this->telegramBotService->sendMessage($bot, $chatId, $message);
                return true;
            } catch (\Exception $e) {
                Log::error('Failed to send Telegram payment message: ' . $e->getMessage());
            }
        }
        return false;
    }

    // handle notify payment rejected tenant
    public function notifyPaymentRejectedTenant($bot, $chatId){
        $message = "❌ Payment Rejection Notice:\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n"
                 . "Your payment request has been rejected by your landlord.\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n"
                 . "Please try again or contact your landlord for more information.";

        if ($bot && $chatId) {
            try {
                $this->telegramBotService->sendMessage($bot, $chatId, $message);
                return [
                    'success' => true,
                    'message' => 'Notification sent successfully.'
                ];
            } catch (\Exception $e) {
                Log::error('Failed to send rejected message: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Failed to send notification.'
                ];
            }
        }
        return [
            'success' => false,
            'message' => 'Failed to send notification, missinfg bot or chat ID'
        ];
    }

    // handle notify payment approved tenant


    // hadnle notify registration rejected by landlord
    public function notifyRegistrationRejectedTenant($bot, $chatId){
        $message = "❌ Registration Rejection Notice:\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n"
                 . "Your registration has been rejected by your landlord.\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n"
                 . "Please try again or contact your landlord for more information.";

        if ($bot && $chatId) {
            try {
                $this->telegramBotService->sendMessage($bot, $chatId, $message);
                return [
                    'success' => true,
                    'message' => 'Notification sent successfully.'
                ];
            } catch (\Exception $e) {
                Log::error('Failed to send rejected message: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Failed to send notification.'
                ];
            }
        }
        return [
            'success' => false,
            'message' => 'Failed to send notification, missinfg bot or chat ID'
        ];
    }


    // handle notify reigstration approved by landlord
    public function approvalePaymentRequest($notificationId){
        //validate
        $notification = Notification::find($notificationId);
        if(!($notification->notification_type == NotificationType::PAYMENT)){
            return response()->json([
                "message"=>"Must be Payment type",
            ]);
        }
     
        $paylaod = $notification->payload['result'];
        //find room id
        $tenant = Tenant::where('telegram_id', $notification->chat_id)->first();

        if ($tenant && $tenant->contract) {
            $roomId = $tenant->contract->room_id;
        } else {
            $roomId = null; // or throw exception, etc.
            return response()->json([
                "message"=>"room is not found in approving payment request"
            ]);
        }
        $paymentService = new PaymentService( $roomId);

        $water_consumption = new Consumption([
            "room_id" => $roomId,
            'end_reading' => $paylaod['water_meter'],
            'photo_url'=> $paylaod['water_image'],
            'type' => "water"
        ]) ;
        $electricity_consumption = new Consumption([
            "room_id" => $roomId,
            'end_reading' => $paylaod['electricity_meter'],
            'photo_url'=> $paylaod['electricity_image'],
            'type' => "electricity"
        ]);
        $data = [$water_consumption,$electricity_consumption];
        $response =  $paymentService->processPayment(false,false,   ...$data );
        // $rceiptUrl = $paymentData['payment']['receipt_url'];
        $receiptUrl = $response->original['payment']['receipt_url'];
        
        $telegramSerivce = new TelegramBotService();
        $user = User::find($notification->landlord_id);
        $bot = $user->telegrambots;
        $telegramSerivce->sendMessage(
            $bot,
            $paylaod['chat_id'],
            "Your Payment have been APPROVED, please proceed the payment via receipt download bellow : $receiptUrl"
        );
        $notification->update([
            "read" => true,
            "status"=> NotificationStatus::APPROVED
        ]);
        
        return $response;

    }


    // protected $fillable = [
    //     'room_id',
    //     'service_id',
    //     'end_reading',
    //     'photo_url',
    //     'consumption',
    //     'type'
    // ];
}

