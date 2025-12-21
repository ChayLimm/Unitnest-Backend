<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\TelegramBotService;
use App\Models\Telegrambot;
use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::paginate(15);
        return response()->json($notifications);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'notification_type' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'read' => 'nullable|boolean',
        ]);

        $notification = Notification::create($validated);

        return response()->json($notification, 201);
    }

    public function show(Notification $notification)
    {
        $notification->load('payment.tenant');
        return response()->json($notification);
    }

    public function update(Request $request, Notification $notification)
    {
        $validated = $request->validate([
            'payment_id' => 'nullable|exists:payments,id',
            'notification_type' => 'nullable|enum:\App\Enums\NotificationType',
            'email' => 'nullable|email|max:255',
            'read' => 'nullable|boolean',
            'status' => 'nullable|string|max:50',
            'payload' => 'nullable|array',
            'landlord_id' => 'nullable|exists:users,id',
            'chat_id' => 'nullable|integer',
        ]);

        $notification->update($validated);

        return response()->json($notification);
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();
        return response()->json(null, 204);
    }

    public function getNotificationsByLandlord(Request $request, $landlordId)
    {

        $notifications = Notification::where('landlord_id', $landlordId)
            ->get();
        
        return response()->json($notifications);
    }

    public function markAsRead(Notification $notification)
    {
        $notification->update(['read' => true]);
        return response()->json($notification);
    }

    public function getUnreadNotifications()
    {
        $notifications = Notification::where('read', false)
            // ->with('payment')
            ->get();
        
        return response()->json($notifications);
    }

    // reject payment notification
    public function rejectPaymentNotification(Notification $notification, NotificationService $notificationService)
    {   
        // check if read true / marked as read, if not
        if (!$notification->read){
            $notification->update(['read' => true]);
        }

        // check
        if ($notification->notification_type !== NotificationType::PAYMENT) {
            return response()->json(['message' => 'Only payment notifications can be rejected.'], 400);
        }
        //
        $notification->update(['status' => NotificationStatus::REJECTED]);

        // notify
        $chatId = $notification->chat_id;
        $landlord = $notification->landlord_id;
        $bot = TelegramBot::where('user_id', $landlord)->first();
        $notify = $notificationService->notifyPaymentRejectedTenant($bot, $chatId);

        return response()->json([
            'message' => 'Rejection notification sent successfully.',
            'notification' => $notification,
            'notify' => $notify
        ]);
    }

    // approve payment notification 
    public function approvePaymentNotification($notificationId){
        $telegramBot = new TelegramBotService();
        $notificationService = new NotificationService($telegramBot);
        $res = $notificationService->approvalePaymentRequest($notificationId);

        return response()->json([
            'message' => 'Approval notification sent successfully.',
            'res' => $res
        ]);
    }

    // reject registration notification
    public function rejectRegistrationNotification(Notification $notification, NotificationService $notificationService)
    {   
        //
        if (!$notification->read) {
            $notification->update(['read' => true]);
        }  
        // check
        if ($notification->notification_type !== NotificationType::REGISTRATION) {
            return response()->json(['message' => 'Only registration notifications can be rejected.'], 400);
        }
        //
        $notification->update(['status' => NotificationStatus::REJECTED]);

        // notify
        $chatId = $notification->chat_id;
        $landlord = $notification->landlord_id;
        $bot = TelegramBot::where('user_id', $landlord)->first();
        $notify = $notificationService->notifyRegistrationRejectedTenant($bot, $chatId);

        return response()->json([
            'message' => 'Rejection notification sent successfully.',
            'notification' => $notification,
            'notify' => $notify
        ]);
    
    }

    // approve registration notification
    public function approveRegistrationNotification(Request $request, $notificationId){
        //
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'deposit' => 'required|numeric|min:0',
            'start_date' => 'required|date',
        ]);
        //
        $notification = Notification::find($notificationId);
        $telegramBotService = new TelegramBotService();
        $notificationService = new NotificationService($telegramBotService);
    
        // call service to handle approval process
        try {
            $result = $notificationService->approveRegistrationRequest($notificationId, $validated);
            return response()->json([
                'message' => 'Approval notification sent successfully.',
                'notification' => $notification,
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error approving registration notification.', 'error' => $e->getMessage()], 500);
        }

        // result return to frontend with recepit for show landlord preview
    }
    
}

