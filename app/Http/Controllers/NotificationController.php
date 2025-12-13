<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\TelegramBotService;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::with('payment')->get();
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
            'payment_id' => 'sometimes|exists:payments,id',
            'notification_type' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'read' => 'nullable|boolean',
        ]);

        $notification->update($validated);

        return response()->json($notification);
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();
        return response()->json(null, 204);
    }

    public function markAsRead(Notification $notification)
    {
        $notification->update(['read' => true]);
        return response()->json($notification);
    }

    public function getUnreadNotifications()
    {
        $notifications = Notification::where('read', false)
            ->with('payment')
            ->get();
        
        return response()->json($notifications);
    }

 
}